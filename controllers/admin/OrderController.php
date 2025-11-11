<?php

namespace app\controllers\admin;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderHistory;
use app\models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * OrderController - Управление заказами
 * 
 * Методы:
 * - index() - Список заказов
 * - create() - Создание заказа
 * - view($id) - Просмотр заказа
 * - update($id) - Редактирование заказа
 * - changeStatus($id) - Смена статуса
 * - assignLogist($id) - Назначение логиста
 * - export() - Экспорт заказов в Excel
 */
class OrderController extends BaseAdminController
{
    /**
     * Список заказов с фильтрацией и статистикой
     */
    public function actionIndex()
    {
        $user = $this->getCurrentUser();
        $query = Order::find()->with(['creator', 'logist', 'orderItems']);

        // Логист видит только свои заказы
        if ($this->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        // Фильтры
        $filterStatus = Yii::$app->request->get('status');
        $filterLogist = Yii::$app->request->get('logist');
        $filterSearch = Yii::$app->request->get('search');

        if ($filterStatus) {
            $query->andWhere(['status' => $filterStatus]);
        }

        if ($filterLogist) {
            $query->andWhere(['assigned_logist' => $filterLogist]);
        }

        if ($filterSearch) {
            $query->andWhere([
                'or',
                ['like', 'order_number', $filterSearch],
                ['like', 'client_name', $filterSearch],
                ['like', 'client_phone', $filterSearch],
                ['like', 'client_email', $filterSearch],
            ]);
        }

        // Статистика по статусам (с кешированием)
        $statusCounts = $this->getStatusCounts($user);
        $totalCount = array_sum($statusCounts ?? []);

        // Месячная статистика (с кешированием)
        $monthlySummary = $this->getMonthlySummary($user);

        return $this->render('/admin/orders', [
            'dataProvider' => $dataProvider,
            'filterStatus' => $filterStatus,
            'filterLogist' => $filterLogist,
            'filterSearch' => $filterSearch,
            'statusCounts' => $statusCounts,
            'totalCount' => $totalCount,
            'monthlySummary' => $monthlySummary,
        ]);
    }

    /**
     * Создание нового заказа
     */
    public function actionCreate()
    {
        $model = new Order();
        $model->created_by = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Валидация заказа
                if (!$model->save()) {
                    throw new \Exception('Ошибка сохранения заказа: ' . json_encode($model->errors));
                }

                // Сохраняем товары
                $items = Yii::$app->request->post('OrderItem', []);
                $totalAmount = 0;
                $itemCount = 0;

                foreach ($items as $itemData) {
                    if (!empty($itemData['product_name']) && !empty($itemData['price'])) {
                        $item = new OrderItem();
                        $item->order_id = $model->id;
                        $item->product_name = $itemData['product_name'];
                        $item->quantity = $itemData['quantity'] ?? 1;
                        $item->price = $itemData['price'];
                        
                        if (!$item->save()) {
                            throw new \Exception('Ошибка сохранения товара: ' . json_encode($item->errors));
                        }

                        $totalAmount += $item->total;
                        $itemCount++;
                    }
                }

                // Проверка что добавлен хотя бы один товар
                if ($itemCount === 0) {
                    throw new \Exception('Необходимо добавить хотя бы один товар в заказ');
                }

                // Обновляем общую сумму
                $model->total_amount = $totalAmount;
                if (!$model->save(false)) {
                    throw new \Exception('Ошибка обновления суммы заказа');
                }

                // Инвалидируем кеш статистики
                TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);

                $transaction->commit();

                Yii::info('Заказ #' . $model->id . ' создан пользователем #' . Yii::$app->user->id . ', товаров: ' . $itemCount . ', сумма: ' . $totalAmount, 'order');
                $this->flashSuccess('Заказ успешно создан!');
                return $this->redirect(['/admin/order/view', 'id' => $model->id]);
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Ошибка создания заказа: ' . $e->getMessage(), 'order');
                $this->flashError('Ошибка при создании заказа: ' . $e->getMessage());
            }
        }

        return $this->render('/admin/create-order', [
            'model' => $model,
        ]);
    }

    /**
     * Просмотр заказа
     * 
     * @param int $id
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Проверка прав доступа
        $user = $this->getCurrentUser();
        if ($this->isLogist() && $model->assigned_logist != $user->id) {
            Yii::warning('Попытка доступа к чужому заказу: пользователь #' . $user->id . ' к заказу #' . $id, 'security');
            throw new NotFoundHttpException('Заказ не найден.');
        }

        return $this->render('/admin/view-order', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование заказа
     * 
     * @param int $id
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Логист не может редактировать заказ, только менять статус
        if ($this->isLogist()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $oldStatus = $model->status;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Проверяем, может ли пользователь изменить статус
                if ($model->status != $oldStatus && !$model->canChangeStatus($model->status)) {
                    $this->flashError('У вас нет прав на изменение этого статуса.');
                    return $this->redirect(['/admin/order/view', 'id' => $model->id]);
                }

                // Валидация заказа
                if (!$model->save()) {
                    throw new \Exception('Ошибка сохранения заказа: ' . json_encode($model->errors));
                }

                // Обновляем товары
                OrderItem::deleteAll(['order_id' => $model->id]);
                
                $items = Yii::$app->request->post('OrderItem', []);
                $totalAmount = 0;
                $itemCount = 0;

                foreach ($items as $itemData) {
                    if (!empty($itemData['product_name']) && !empty($itemData['price'])) {
                        $item = new OrderItem();
                        $item->order_id = $model->id;
                        $item->product_name = $itemData['product_name'];
                        $item->quantity = $itemData['quantity'] ?? 1;
                        $item->price = $itemData['price'];
                        
                        if (!$item->save()) {
                            throw new \Exception('Ошибка сохранения товара: ' . json_encode($item->errors));
                        }

                        $totalAmount += $item->total;
                        $itemCount++;
                    }
                }

                // Проверка что добавлен хотя бы один товар
                if ($itemCount === 0) {
                    throw new \Exception('Необходимо добавить хотя бы один товар в заказ');
                }

                $model->total_amount = $totalAmount;
                if (!$model->save(false)) {
                    throw new \Exception('Ошибка обновления суммы заказа');
                }

                // Инвалидируем кеш статистики
                TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);

                $transaction->commit();

                Yii::info('Заказ #' . $model->id . ' обновлен пользователем #' . Yii::$app->user->id, 'order');
                $this->flashSuccess('Заказ успешно обновлен!');
                return $this->redirect(['/admin/order/view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->flashError('Ошибка при обновлении заказа: ' . $e->getMessage());
            }
        }

        return $this->render('/admin/update-order', [
            'model' => $model,
        ]);
    }

    /**
     * Изменение статуса заказа
     * 
     * @param int $id
     */
    public function actionChangeStatus($id)
    {
        $model = $this->findModel($id);
        $user = $this->getCurrentUser();

        if (Yii::$app->request->isPost) {
            $newStatus = Yii::$app->request->post('status');
            $comment = Yii::$app->request->post('comment', '');

            if (!$model->canChangeStatus($newStatus)) {
                Yii::warning('Попытка изменить статус без прав: пользователь #' . $user->id . ', заказ #' . $id . ', статус: ' . $newStatus, 'security');
                $this->flashError('У вас нет прав на изменение этого статуса.');
                return $this->redirect(['/admin/order/view', 'id' => $model->id]);
            }

            $oldStatus = $model->status;
            $model->status = $newStatus;

            if ($model->save()) {
                // Добавляем запись в историю с комментарием
                if ($comment) {
                    $history = OrderHistory::find()
                        ->where(['order_id' => $model->id])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->one();
                    
                    if ($history && $history->new_status == $newStatus) {
                        $history->comment = $comment;
                        $history->save(false);
                    }
                }

                Yii::info('Статус заказа #' . $id . ' изменен с "' . $oldStatus . '" на "' . $newStatus . '" пользователем #' . $user->id, 'order');
                $this->flashSuccess('Статус заказа изменен.');
            } else {
                Yii::error('Ошибка изменения статуса заказа #' . $id . ': ' . json_encode($model->errors), 'order');
                $this->flashError('Ошибка при изменении статуса.');
            }
        }

        return $this->redirect(['/admin/order/view', 'id' => $model->id]);
    }

    /**
     * Назначение логиста на заказ
     * 
     * @param int $id
     */
    public function actionAssignLogist($id)
    {
        $model = $this->findModel($id);
        $user = $this->getCurrentUser();

        // Только админ может назначать логистов
        if (!$this->isAdmin()) {
            Yii::warning('Попытка назначить логиста без прав: пользователь #' . $user->id . ', заказ #' . $id, 'security');
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        if (Yii::$app->request->isPost) {
            $oldLogist = $model->assigned_logist;
            $logistId = Yii::$app->request->post('logist_id');
            $model->assigned_logist = $logistId ?: null;

            if ($model->save(false)) {
                Yii::info('Логист назначен на заказ #' . $id . ': старый=' . $oldLogist . ', новый=' . $logistId . ' (админ #' . $user->id . ')', 'order');
                $this->flashSuccess('Логист назначен.');
            }
        }

        return $this->redirect(['/admin/order/view', 'id' => $model->id]);
    }

    /**
     * Экспорт заказов в Excel
     */
    public function actionExport()
    {
        $user = Yii::$app->user->identity;
        $month = Yii::$app->request->get('month');
        $year = Yii::$app->request->get('year');

        if (!$month || !$year) {
            $this->flashError('Не указан месяц или год для экспорта');
            return $this->redirect(['/admin/order/index']);
        }

        // Формируем запрос для получения заказов за указанный месяц
        $query = Order::find()->with(['creator', 'logist', 'orderItems']);

        // Логист видит только свои заказы
        if ($user->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }

        // Фильтр по месяцу и году
        $startDate = mktime(0, 0, 0, $month, 1, $year);
        $endDate = mktime(23, 59, 59, $month, date('t', $startDate), $year);
        $query->andWhere(['between', 'created_at', $startDate, $endDate]);

        $orders = $query->orderBy(['created_at' => SORT_ASC])->all();

        if (empty($orders)) {
            $this->flashWarning('Нет заказов за выбранный период');
            return $this->redirect(['/admin/order/index']);
        }

        // Создаем Excel файл
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Устанавливаем заголовок
        $monthName = Yii::$app->formatter->asDate($startDate, 'LLLL yyyy');
        $sheet->setTitle(mb_substr($monthName, 0, 31)); // Excel ограничивает длину названия листа

        // Заголовки колонок
        $headers = ['№', 'Номер заказа', 'Дата создания', 'ФИО клиента', 'Телефон', 'Email', 'Сумма (BYN)', 'Статус', 'Менеджер', 'Логист', 'Товары'];
        $sheet->fromArray($headers, null, 'A1');

        // Стилизация заголовка
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Устанавливаем ширину колонок
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(40);

        // Заполняем данными
        $row = 2;
        $totalAmount = 0;
        foreach ($orders as $index => $order) {
            // Безопасно получаем товары
            $itemsList = [];
            if ($order->orderItems) {
                foreach ($order->orderItems as $item) {
                    $itemsList[] = $item->product_name . ' (' . $item->quantity . ' шт × ' . number_format($item->price, 2) . ' BYN)';
                }
            }
            
            $data = [
                $index + 1,
                $order->order_number,
                Yii::$app->formatter->asDatetime($order->created_at, 'php:d.m.Y H:i'),
                $order->client_name,
                $order->client_phone,
                $order->client_email,
                number_format($order->total_amount, 2),
                $order->getStatusLabel(),
                $order->creator ? $order->creator->username : '-',
                $order->logist ? $order->logist->username : 'Не назначен',
                !empty($itemsList) ? implode("\n", $itemsList) : '-'
            ];
            
            $sheet->fromArray($data, null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':K' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $totalAmount += $order->total_amount;
            $row++;
        }

        // Добавляем строку с итогом
        $sheet->setCellValue('F' . $row, 'ИТОГО:');
        $sheet->setCellValue('G' . $row, number_format($totalAmount, 2));
        $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row . ':G' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E7E6E6');

        // Устанавливаем автофильтр
        $sheet->setAutoFilter('A1:K' . ($row - 1));

        // Сохраняем файл
        $fileName = 'orders_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.xlsx';
        $tempFile = Yii::getAlias('@runtime') . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Отправляем файл пользователю
        Yii::info('Экспорт заказов за ' . $monthName . ' (пользователь #' . $user->id . ')', 'order');
        
        return Yii::$app->response->sendFile($tempFile, $fileName, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'inline' => false
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function($event) use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });
    }

    /**
     * Найти модель заказа по ID
     * 
     * @param int $id
     * @return Order
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = Order::findOne($id);
        
        if ($model === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }
        
        return $model;
    }

    /**
     * Получить статистику по статусам заказов (с кешированием)
     * 
     * @param User $user
     * @return array
     */
    protected function getStatusCounts($user)
    {
        $cache = Yii::$app->cache;

        $callback = function () use ($user) {
            $query = Order::find()
                ->select(['status', 'cnt' => new Expression('COUNT(*)')]);

            if ($this->isLogist()) {
                $query->andWhere(['assigned_logist' => $user->id]);
            }

            $rows = $query
                ->groupBy('status')
                ->asArray()
                ->all();

            return ArrayHelper::map($rows, 'status', 'cnt');
        };

        if ($cache !== null) {
            return $cache->getOrSet([
                'admin',
                'status-counts',
                'role' => $user->role,
                'user' => $this->isLogist() ? $user->id : 'all',
            ], $callback, 300, new TagDependency(['tags' => ['orders-stats']]));
        }

        return $callback();
    }

    /**
     * Получить месячную статистику (с кешированием)
     * 
     * @param User $user
     * @return array
     */
    protected function getMonthlySummary($user)
    {
        $cache = Yii::$app->cache;

        $callback = function () use ($user) {
            $query = (new Query())
                ->select([
                    'month' => new Expression('DATE_FORMAT(FROM_UNIXTIME(created_at), "%Y-%m")'),
                    'orders_count' => new Expression('COUNT(*)'),
                    'total_amount' => new Expression('SUM(total_amount)')
                ])
                ->from(Order::tableName());

            if ($this->isLogist()) {
                $query->where(['assigned_logist' => $user->id]);
            }

            return $query
                ->groupBy(['month'])
                ->orderBy(['month' => SORT_DESC])
                ->all();
        };

        if ($cache !== null) {
            return $cache->getOrSet([
                'admin',
                'monthly-summary',
                'user' => $this->isLogist() ? $user->id : 'all',
            ], $callback, 300, new TagDependency(['tags' => ['orders-stats']]));
        }

        return $callback();
    }
}
