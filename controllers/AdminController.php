<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderHistory;
use app\models\User;
use app\models\CompanySettings;
use app\models\OrderStatus;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdminController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['orders']);
    }

    public function actionOrders()
    {
        $user = Yii::$app->user->identity;
        $query = Order::find()->with(['creator', 'logist', 'orderItems']);

        // Логист видит только свои заказы
        if ($user->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }

        // Менеджер видит свои и все заказы (в зависимости от требований)
        // Пока делаем так, что менеджер видит все

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

        return $this->render('orders', [
            'dataProvider' => $dataProvider,
            'filterStatus' => $filterStatus,
            'filterLogist' => $filterLogist,
            'filterSearch' => $filterSearch,
        ]);
    }

    public function actionExportOrders()
    {
        $user = Yii::$app->user->identity;
        $month = Yii::$app->request->get('month');
        $year = Yii::$app->request->get('year');

        if (!$month || !$year) {
            Yii::$app->session->setFlash('error', 'Не указан месяц или год для экспорта.');
            return $this->redirect(['orders']);
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
            Yii::$app->session->setFlash('warning', 'Нет заказов за выбранный период.');
            return $this->redirect(['orders']);
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

    public function actionCreateOrder()
    {
        $model = new Order();
        $model->created_by = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Сохраняем товары
                    $items = Yii::$app->request->post('OrderItem', []);
                    $totalAmount = 0;

                    foreach ($items as $itemData) {
                        if (!empty($itemData['product_name']) && !empty($itemData['price'])) {
                            $item = new OrderItem();
                            $item->order_id = $model->id;
                            $item->product_name = $itemData['product_name'];
                            $item->quantity = $itemData['quantity'] ?? 1;
                            $item->price = $itemData['price'];
                            $item->save();

                            $totalAmount += $item->total;
                        }
                    }

                    // Обновляем общую сумму
                    $model->total_amount = $totalAmount;
                    $model->save(false);

                    $transaction->commit();

                    Yii::$app->session->setFlash('success', 'Заказ успешно создан!');
                    return $this->redirect(['view-order', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при создании заказа: ' . $e->getMessage());
            }
        }

        return $this->render('create-order', [
            'model' => $model,
        ]);
    }

    public function actionViewOrder($id)
    {
        $model = $this->findModel($id);
        
        // Проверка прав доступа
        $user = Yii::$app->user->identity;
        if ($user->isLogist() && $model->assigned_logist != $user->id) {
            Yii::warning('Попытка доступа к чужому заказу: пользователь #' . $user->id . ' к заказу #' . $id, 'security');
            throw new NotFoundHttpException('Заказ не найден.');
        }

        return $this->render('view-order', [
            'model' => $model,
        ]);
    }

    public function actionUpdateOrder($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // Логист не может редактировать заказ, только менять статус
        if ($user->isLogist()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $oldStatus = $model->status;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Проверяем, может ли пользователь изменить статус
                if ($model->status != $oldStatus && !$model->canChangeStatus($model->status)) {
                    Yii::$app->session->setFlash('error', 'У вас нет прав на изменение этого статуса.');
                    return $this->redirect(['view-order', 'id' => $model->id]);
                }

                if ($model->save()) {
                    // Обновляем товары
                    OrderItem::deleteAll(['order_id' => $model->id]);
                    
                    $items = Yii::$app->request->post('OrderItem', []);
                    $totalAmount = 0;

                    foreach ($items as $itemData) {
                        if (!empty($itemData['product_name']) && !empty($itemData['price'])) {
                            $item = new OrderItem();
                            $item->order_id = $model->id;
                            $item->product_name = $itemData['product_name'];
                            $item->quantity = $itemData['quantity'] ?? 1;
                            $item->price = $itemData['price'];
                            $item->save();

                            $totalAmount += $item->total;
                        }
                    }

                    $model->total_amount = $totalAmount;
                    $model->save(false);

                    $transaction->commit();

                    Yii::$app->session->setFlash('success', 'Заказ успешно обновлен!');
                    return $this->redirect(['view-order', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при обновлении заказа: ' . $e->getMessage());
            }
        }

        return $this->render('update-order', [
            'model' => $model,
        ]);
    }

    public function actionChangeStatus($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        if (Yii::$app->request->isPost) {
            $newStatus = Yii::$app->request->post('status');
            $comment = Yii::$app->request->post('comment', '');

            if (!$model->canChangeStatus($newStatus)) {
                Yii::warning('Попытка изменить статус без прав: пользователь #' . $user->id . ', заказ #' . $id . ', статус: ' . $newStatus, 'security');
                Yii::$app->session->setFlash('error', 'У вас нет прав на изменение этого статуса.');
                return $this->redirect(['view-order', 'id' => $model->id]);
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
                Yii::$app->session->setFlash('success', 'Статус заказа изменен.');
            } else {
                Yii::error('Ошибка изменения статуса заказа #' . $id . ': ' . json_encode($model->errors), 'order');
                Yii::$app->session->setFlash('error', 'Ошибка при изменении статуса.');
            }
        }

        return $this->redirect(['view-order', 'id' => $model->id]);
    }

    public function actionAssignLogist($id)
    {
        $model = $this->findModel($id);
        $user = Yii::$app->user->identity;

        // Только админ может назначать логистов
        if (!$user->isAdmin()) {
            Yii::warning('Попытка назначить логиста без прав: пользователь #' . $user->id . ', заказ #' . $id, 'security');
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        if (Yii::$app->request->isPost) {
            $oldLogist = $model->assigned_logist;
            $logistId = Yii::$app->request->post('logist_id');
            $model->assigned_logist = $logistId ?: null;

            if ($model->save(false)) {
                Yii::info('Логист назначен на заказ #' . $id . ': старый=' . $oldLogist . ', новый=' . $logistId . ' (админ #' . $user->id . ')', 'order');
                Yii::$app->session->setFlash('success', 'Логист назначен.');
            }
        }

        return $this->redirect(['view-order', 'id' => $model->id]);
    }

    public function actionUsers()
    {
        $user = Yii::$app->user->identity;

        // Только админ может управлять пользователями
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('users', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionStatistics()
    {
        $user = Yii::$app->user->identity;

        // Статистика по статусам (используем БД)
        $statusStats = [];
        foreach (Yii::$app->settings->getStatuses() as $key => $label) {
            $count = Order::find()->where(['status' => $key])->count();
            $statusStats[$label] = $count;
        }

        // Статистика по менеджерам
        $managerStats = User::find()
            ->where(['role' => User::ROLE_MANAGER])
            ->with(['createdOrders'])
            ->all();

        // Статистика по логистам
        $logistStats = User::find()
            ->where(['role' => User::ROLE_LOGIST])
            ->with(['assignedOrders'])
            ->all();

        // Общая статистика
        $totalOrders = Order::find()->count();
        $totalAmount = Order::find()->sum('total_amount');
        $pendingPayment = Order::find()->where(['status' => 'created'])->count();
        $completedOrders = Order::find()->where(['status' => 'issued'])->count();

        return $this->render('statistics', [
            'statusStats' => $statusStats,
            'managerStats' => $managerStats,
            'logistStats' => $logistStats,
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,
            'pendingPayment' => $pendingPayment,
            'completedOrders' => $completedOrders,
        ]);
    }

    public function actionSettings()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $settings = CompanySettings::find()->orderBy(['id' => SORT_ASC])->one();
        if (!$settings) {
            $settings = new CompanySettings();
        }

        $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            // Сохранение реквизитов компании
            if ($settings->load($post) && $settings->validate()) {
                $settings->updated_at = time();
                $settings->save(false);
            }

            // Обновление статусов
            $tx = Yii::$app->db->beginTransaction();
            try {
                // Обновляем существующие
                $postedStatuses = $post['statuses'] ?? [];
                foreach ($postedStatuses as $key => $data) {
                    $model = OrderStatus::findOne(['key' => $key]);
                    if ($model) {
                        $model->label = trim($data['label'] ?? $model->label);
                        $model->sort = (int)($data['sort'] ?? $model->sort);
                        $model->logist_available = !empty($data['logist_available']);
                        $model->save(false);
                    }
                }

                // Удаление помеченных
                $deleteKeys = $post['delete_keys'] ?? [];
                if (!empty($deleteKeys)) {
                    foreach ($deleteKeys as $delKey) {
                        // не позволяем удалить базовые статусы created/paid/accepted/ordered/received/issued
                        if (in_array($delKey, ['created','paid','accepted','ordered','received','issued'])) {
                            continue;
                        }
                        OrderStatus::deleteAll(['key' => $delKey]);
                    }
                }

                // Добавление нового
                $new = $post['new_status'] ?? [];
                if (!empty($new['key']) && !empty($new['label'])) {
                    if (!OrderStatus::find()->where(['key' => $new['key']])->exists()) {
                        $m = new OrderStatus();
                        $m->key = trim($new['key']);
                        $m->label = trim($new['label']);
                        $m->sort = (int)($new['sort'] ?? 999);
                        $m->logist_available = !empty($new['logist_available']);
                        $m->save(false);
                    }
                }

                $tx->commit();
                Yii::$app->session->setFlash('success', 'Настройки сохранены');
                // Обновляем данные для рендера
                $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('settings', [
            'settings' => $settings,
            'statuses' => $statuses,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Order::findOne($id)) !== null) {
            return $model;
        }

        Yii::warning('Попытка доступа к несуществующему заказу #' . $id . ' от пользователя #' . Yii::$app->user->id, 'security');
        throw new NotFoundHttpException('Заказ не найден.');
    }
}
