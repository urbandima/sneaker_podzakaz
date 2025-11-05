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
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\db\Query;
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
use app\models\ImportBatch;
use app\models\ImportLog;
use app\components\PoizonApiService;
use yii\web\Response;
use app\models\Product;
use app\models\ProductSize;
use app\models\ProductImage;
use app\models\Brand;
use app\models\Category;
use app\models\SizeGrid;
use app\models\SizeGridItem;
use app\models\Characteristic;
use app\models\CharacteristicValue;
use app\models\ProductCharacteristicValue;

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

        $cache = Yii::$app->cache;

        $statusCountsCallback = function () use ($user) {
            $statusQuery = Order::find()
                ->select(['status', 'cnt' => new Expression('COUNT(*)')]);

            if ($user->isLogist()) {
                $statusQuery->andWhere(['assigned_logist' => $user->id]);
            }

            $rows = $statusQuery
                ->groupBy('status')
                ->asArray()
                ->all();

            return ArrayHelper::map($rows, 'status', 'cnt');
        };

        if ($cache instanceof CacheInterface) {
            $statusCounts = $cache->getOrSet([
                'admin',
                'status-counts',
                'role' => $user->role,
                'user' => $user->isLogist() ? $user->id : 'all',
            ], $statusCountsCallback, 300, new TagDependency(['tags' => ['orders-stats']]));
        } else {
            $statusCounts = $statusCountsCallback();
        }

        $totalCount = array_sum($statusCounts ?? []);

        $monthlySummaryCallback = function () use ($user) {
            $query = (new Query())
                ->select([
                    'month' => new Expression('DATE_FORMAT(FROM_UNIXTIME(created_at), "%Y-%m")'),
                    'orders_count' => new Expression('COUNT(*)'),
                    'total_amount' => new Expression('SUM(total_amount)')
                ])
                ->from(Order::tableName());

            if ($user->isLogist()) {
                $query->where(['assigned_logist' => $user->id]);
            }

            return $query
                ->groupBy(['month'])
                ->orderBy(['month' => SORT_DESC])
                ->all();
        };

        if ($cache instanceof CacheInterface) {
            $monthlySummary = $cache->getOrSet([
                'admin',
                'monthly-summary',
                'user' => $user->isLogist() ? $user->id : 'all',
            ], $monthlySummaryCallback, 300, new TagDependency(['tags' => ['orders-stats']]));
        } else {
            $monthlySummary = $monthlySummaryCallback();
        }

        return $this->render('orders', [
            'dataProvider' => $dataProvider,
            'filterStatus' => $filterStatus,
            'filterLogist' => $filterLogist,
            'filterSearch' => $filterSearch,
            'statusCounts' => $statusCounts,
            'totalCount' => $totalCount,
            'monthlySummary' => $monthlySummary,
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
                        
                        // ИСПРАВЛЕНО: Проверяем успешность сохранения
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
                Yii::$app->session->setFlash('success', 'Заказ успешно создан!');
                return $this->redirect(['view-order', 'id' => $model->id]);
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Ошибка создания заказа: ' . $e->getMessage(), 'order');
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
                        
                        // ИСПРАВЛЕНО: Проверяем успешность сохранения
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
                Yii::$app->session->setFlash('success', 'Заказ успешно обновлен!');
                return $this->redirect(['view-order', 'id' => $model->id]);
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

    public function actionCreateUser()
    {
        $user = Yii::$app->user->identity;

        // Только админ может создавать пользователей
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $model = new User();
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post())) {
            // Устанавливаем статус активен
            $model->status = User::STATUS_ACTIVE;
            
            // Хешируем пароль
            $model->setPassword($model->password);
            $model->generateAuthKey();
            
            if ($model->save()) {
                Yii::info('Создан новый пользователь: ' . $model->username . ' (роль: ' . $model->role . ') админом #' . $user->id, 'user');
                Yii::$app->session->setFlash('success', 'Пользователь успешно создан!');
                return $this->redirect(['users']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при создании пользователя: ' . json_encode($model->errors));
            }
        }

        return $this->render('create-user', [
            'model' => $model,
        ]);
    }

    public function actionDeleteUser($id)
    {
        $user = Yii::$app->user->identity;

        // Только админ может удалять пользователей
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        // Нельзя удалить самого себя
        if ($id == $user->id) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить самого себя.');
            return $this->redirect(['users']);
        }

        $userToDelete = User::findOne($id);
        if ($userToDelete === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        $username = $userToDelete->username;
        if ($userToDelete->delete()) {
            Yii::info('Удален пользователь: ' . $username . ' (ID: ' . $id . ') админом #' . $user->id, 'user');
            Yii::$app->session->setFlash('success', 'Пользователь успешно удален.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении пользователя.');
        }

        return $this->redirect(['users']);
    }

    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        $model = new \app\models\ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            Yii::$app->session->setFlash('success', 'Пароль успешно изменен');
            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model,
            'user' => $user,
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
                        $model->is_active = !empty($data['is_active']);
                        $model->save(false);
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
                        $m->is_active = 1; // Новые статусы по умолчанию активны
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

    // ==================== PRODUCT MANAGEMENT ACTIONS ====================

    /**
     * Список всех товаров с фильтрацией
     */
    public function actionProducts()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $query = Product::find()->with(['brand', 'category', 'images']);

        // Фильтры
        $filterBrand = Yii::$app->request->get('brand');
        $filterCategory = Yii::$app->request->get('category');
        $filterSource = Yii::$app->request->get('source'); // poizon, manual
        $filterActive = Yii::$app->request->get('is_active');
        $filterSearch = Yii::$app->request->get('search');

        if ($filterBrand) {
            $query->andWhere(['brand_id' => $filterBrand]);
        }

        if ($filterCategory) {
            $query->andWhere(['category_id' => $filterCategory]);
        }

        if ($filterSource === 'poizon') {
            $query->andWhere(['not', ['poizon_id' => null]]);
        } elseif ($filterSource === 'manual') {
            $query->andWhere(['poizon_id' => null]);
        }

        if ($filterActive !== null && $filterActive !== '') {
            $query->andWhere(['is_active' => $filterActive]);
        }

        if ($filterSearch) {
            $query->andWhere([
                'or',
                ['like', 'name', $filterSearch],
                ['like', 'sku', $filterSearch],
                ['like', 'poizon_id', $filterSearch],
                ['like', 'vendor_code', $filterSearch],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        // Статистика
        $stats = [
            'total' => Product::find()->count(),
            'active' => Product::find()->where(['is_active' => 1])->count(),
            'poizon' => Product::find()->where(['not', ['poizon_id' => null]])->count(),
            'manual' => Product::find()->where(['poizon_id' => null])->count(),
        ];

        // Списки для фильтров
        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('products', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'brands' => $brands,
            'categories' => $categories,
            'filterBrand' => $filterBrand,
            'filterCategory' => $filterCategory,
            'filterSource' => $filterSource,
            'filterActive' => $filterActive,
            'filterSearch' => $filterSearch,
        ]);
    }

    /**
     * Просмотр товара
     */
    public function actionViewProduct($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($id);

        return $this->render('view-product', [
            'product' => $product,
        ]);
    }

    /**
     * Редактирование товара
     */
    public function actionEditProduct($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($id);

        if ($product->load(Yii::$app->request->post())) {
            // Обработка объединенных ключевых слов
            if ($product->meta_keywords) {
                // Парсим meta_keywords из формы
                $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
                $metaKeywordsArray = array_filter($metaKeywordsArray); // убираем пустые
                
                // Получаем keywords из Poizon (JSON)
                $poizonKeywords = [];
                if ($product->keywords) {
                    $keywordsData = json_decode($product->keywords, true);
                    if (is_array($keywordsData)) {
                        $poizonKeywords = $keywordsData;
                    }
                }
                
                // Объединяем и удаляем дубликаты (регистронезависимо)
                $allKeywords = array_merge($metaKeywordsArray, $poizonKeywords);
                $allKeywords = array_unique(array_map('mb_strtolower', $allKeywords));
                
                // Сохраняем обратно в meta_keywords
                $product->meta_keywords = implode(', ', $allKeywords);
            }
            
            // Обработка измененных характеристик Poizon
            $poizonProps = Yii::$app->request->post('poizon_props');
            if (is_array($poizonProps) && !empty($poizonProps)) {
                // Обновляем JSON поле properties с новыми значениями
                $updatedProps = [];
                foreach ($poizonProps as $prop) {
                    if (!empty($prop['key']) && !empty($prop['value'])) {
                        $updatedProps[] = [
                            'key' => $prop['key'],
                            'value' => $prop['value']
                        ];
                    }
                }
                
                if (!empty($updatedProps)) {
                    $product->properties = json_encode($updatedProps, JSON_UNESCAPED_UNICODE);
                }
            }
            
            if ($product->save()) {
                Yii::$app->session->setFlash('success', 'Товар успешно обновлен');
                return $this->redirect(['view-product', 'id' => $product->id]);
            }
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('edit-product', [
            'product' => $product,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * Активация/деактивация товара
     */
    public function actionToggleProduct($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($id);
        $product->is_active = $product->is_active ? 0 : 1;
        
        if ($product->save(false)) {
            $status = $product->is_active ? 'активирован' : 'деактивирован';
            Yii::$app->session->setFlash('success', "Товар {$status}");
        }

        return $this->redirect(['products']);
    }

    /**
     * Удаление товара
     */
    public function actionDeleteProduct($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($id);
        
        if ($product->delete()) {
            Yii::$app->session->setFlash('success', 'Товар успешно удален');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении товара');
        }

        return $this->redirect(['products']);
    }

    /**
     * Синхронизация товара с Poizon
     */
    public function actionSyncProduct($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($id);
        
        if (!$product->poizon_id) {
            Yii::$app->session->setFlash('error', 'Товар не импортирован из Poizon');
            return $this->redirect(['view-product', 'id' => $id]);
        }

        try {
            $poizonApi = Yii::$app->get('poizonApi');
            // Здесь будет логика синхронизации
            $product->last_sync_at = date('Y-m-d H:i:s');
            $product->save(false);
            
            Yii::$app->session->setFlash('success', 'Товар успешно синхронизирован с Poizon');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Ошибка синхронизации: ' . $e->getMessage());
        }

        return $this->redirect(['view-product', 'id' => $id]);
    }

    /**
     * Найти товар или вернуть 404
     */
    protected function findProduct($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Товар не найден');
    }

    // ==================== SIZE MANAGEMENT ACTIONS ====================

    /**
     * Добавить размер к товару
     */
    public function actionAddSize($productId)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($productId);
        $size = new ProductSize();
        $size->product_id = $productId;

        if ($size->load(Yii::$app->request->post()) && $size->save()) {
            Yii::$app->session->setFlash('success', 'Размер успешно добавлен');
            
            // Если AJAX - возвращаем JSON, иначе редирект
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'Размер добавлен'];
            }
            
            // Проверяем откуда пришел запрос
            $returnUrl = Yii::$app->request->get('returnUrl', 'view-product');
            if ($returnUrl === 'edit-product') {
                return $this->redirect(['edit-product', 'id' => $productId]);
            }
            
            return $this->redirect(['view-product', 'id' => $productId]);
        }

        return $this->render('add-size', [
            'product' => $product,
            'size' => $size,
        ]);
    }

    /**
     * Массовое добавление размеров из сетки
     */
    public function actionAddSizesFromGrid($productId, $gridId)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($productId);
        $grid = SizeGrid::findOne($gridId);
        
        if (!$grid) {
            throw new NotFoundHttpException('Размерная сетка не найдена');
        }

        $added = 0;
        foreach ($grid->items as $item) {
            // Проверяем, не существует ли уже такой размер
            $exists = ProductSize::find()
                ->where(['product_id' => $productId, 'us_size' => $item->us_size])
                ->exists();
                
            if (!$exists) {
                $size = new ProductSize();
                $size->product_id = $productId;
                $size->us_size = $item->us_size;
                $size->eu_size = $item->eu_size;
                $size->uk_size = $item->uk_size;
                $size->cm_size = $item->cm_size;
                $size->size = $item->size;
                $size->stock = 0;
                $size->is_available = 1;
                
                if ($size->save()) {
                    $added++;
                }
            }
        }

        Yii::$app->session->setFlash('success', "Добавлено размеров: {$added}");
        
        // Проверяем откуда пришел запрос
        $returnUrl = Yii::$app->request->get('returnUrl', 'view-product');
        if ($returnUrl === 'edit-product') {
            return $this->redirect(['edit-product', 'id' => $productId]);
        }
        
        return $this->redirect(['view-product', 'id' => $productId]);
    }

    /**
     * Редактировать размер
     */
    public function actionEditSize($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $size = ProductSize::findOne($id);
        if (!$size) {
            throw new NotFoundHttpException('Размер не найден');
        }

        if ($size->load(Yii::$app->request->post()) && $size->save()) {
            Yii::$app->session->setFlash('success', 'Размер успешно обновлен');
            return $this->redirect(['view-product', 'id' => $size->product_id]);
        }

        return $this->render('edit-size', [
            'size' => $size,
            'product' => $size->product,
        ]);
    }

    /**
     * Удалить размер
     */
    public function actionDeleteSize($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $size = ProductSize::findOne($id);
        if ($size) {
            $productId = $size->product_id;
            $size->delete();
            Yii::$app->session->setFlash('success', 'Размер удален');
            return $this->redirect(['view-product', 'id' => $productId]);
        }

        throw new NotFoundHttpException('Размер не найден');
    }

    // ==================== IMAGE MANAGEMENT ACTIONS ====================

    /**
     * Добавить изображение к товару
     */
    public function actionAddImage($productId)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $product = $this->findProduct($productId);
        
        if (Yii::$app->request->isPost) {
            $imageUrl = Yii::$app->request->post('image_url');
            
            if ($imageUrl) {
                $image = new ProductImage();
                $image->product_id = $productId;
                $image->image = $imageUrl;
                $image->sort_order = ProductImage::find()->where(['product_id' => $productId])->max('sort_order') + 1;
                
                if ($image->save()) {
                    Yii::$app->session->setFlash('success', 'Изображение добавлено');
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка при добавлении изображения');
                }
            }
        }

        // Проверяем откуда пришел запрос
        $returnUrl = Yii::$app->request->get('returnUrl', 'view-product');
        if ($returnUrl === 'edit-product') {
            return $this->redirect(['edit-product', 'id' => $productId]);
        }

        return $this->redirect(['view-product', 'id' => $productId]);
    }

    /**
     * Удалить изображение
     */
    public function actionDeleteImage($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $image = ProductImage::findOne($id);
        if ($image) {
            $productId = $image->product_id;
            $image->delete();
            Yii::$app->session->setFlash('success', 'Изображение удалено');
            return $this->redirect(['view-product', 'id' => $productId]);
        }

        throw new NotFoundHttpException('Изображение не найдено');
    }

    /**
     * Установить главное изображение
     */
    public function actionSetMainImage($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $image = ProductImage::findOne($id);
        if ($image) {
            $image->setAsMain();
            Yii::$app->session->setFlash('success', 'Главное изображение установлено');
            return $this->redirect(['view-product', 'id' => $image->product_id]);
        }

        throw new NotFoundHttpException('Изображение не найдено');
    }

    // ==================== REFERENCE GUIDES ACTIONS ====================

    /**
     * Справочник характеристик товаров
     */
    public function actionCharacteristicsGuide()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        return $this->render('characteristics-guide');
    }

    /**
     * Справочник размерных сеток (информационный)
     */
    public function actionSizeGuide()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        return $this->render('size-guide');
    }

    /**
     * Управление размерными сетками (CRUD)
     */
    public function actionSizeGrids()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => SizeGrid::find()->with(['brand'])->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('size-grids', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создать размерную сетку
     */
    public function actionCreateSizeGrid()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $model = new SizeGrid();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка создана');
            return $this->redirect(['edit-size-grid', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('create-size-grid', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    /**
     * Редактировать размерную сетку
     */
    public function actionEditSizeGrid($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $model = SizeGrid::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Размерная сетка не найдена');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка обновлена');
            return $this->redirect(['size-grids']);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('edit-size-grid', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    /**
     * Удалить размерную сетку
     */
    public function actionDeleteSizeGrid($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $model = SizeGrid::findOne($id);
        if ($model && $model->delete()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка удалена');
        }

        return $this->redirect(['size-grids']);
    }

    /**
     * Добавить размер в сетку
     */
    public function actionAddSizeGridItem($gridId)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $grid = SizeGrid::findOne($gridId);
        if (!$grid) {
            throw new NotFoundHttpException('Размерная сетка не найдена');
        }

        $item = new SizeGridItem();
        $item->size_grid_id = $gridId;

        if ($item->load(Yii::$app->request->post())) {
            // Автоматически определяем sort_order
            $maxSort = SizeGridItem::find()
                ->where(['size_grid_id' => $gridId])
                ->max('sort_order');
            $item->sort_order = ($maxSort ?? -1) + 1;

            if ($item->save()) {
                Yii::$app->session->setFlash('success', 'Размер добавлен в сетку');
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при добавлении размера');
            }
        }

        return $this->redirect(['edit-size-grid', 'id' => $gridId]);
    }

    /**
     * Удалить размер из сетки
     */
    public function actionDeleteSizeGridItem($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $item = SizeGridItem::findOne($id);
        if ($item) {
            $gridId = $item->size_grid_id;
            $item->delete();
            Yii::$app->session->setFlash('success', 'Размер удален из сетки');
            return $this->redirect(['edit-size-grid', 'id' => $gridId]);
        }

        throw new NotFoundHttpException('Размер не найден');
    }

    // ==================== POIZON IMPORT ACTIONS ====================

    /**
     * Dashboard импорта Poizon
     */
    public function actionPoizonImport()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        // Последние батчи
        $dataProvider = new ActiveDataProvider([
            'query' => ImportBatch::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Статистика
        $stats = $this->getImportStats();

        return $this->render('poizon-import', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }

    /**
     * Запустить импорт Poizon
     */
    public function actionPoizonRun()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        if (Yii::$app->request->isPost) {
            // Проверяем импорт из URL
            $importUrl = Yii::$app->request->post('import_url');
            
            if ($importUrl) {
                // Импорт из URL
                $logFile = Yii::getAlias('@runtime/logs/poizon-url-import-' . time() . '.log');
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0777, true);
                }
                
                // Запускаем импорт из URL с verbose
                $command = "php " . Yii::getAlias('@app') . "/yii poizon-import-json/run \"" . addslashes($importUrl) . "\" --verbose=1 > \"{$logFile}\" 2>&1 &";
                exec($command);
                
                Yii::$app->session->setFlash('success', 
                    "Импорт из URL запущен в фоновом режиме.<br>" .
                    "URL: <small>" . Html::encode($importUrl) . "</small><br>" .
                    "Лог: <code>" . basename($logFile) . "</code><br>" .
                    "Смотрите прогресс: <code>tail -f runtime/logs/" . basename($logFile) . "</code>"
                );
                
                return $this->redirect(['poizon-import']);
            }
            
            // Проверяем, загружен ли файл
            $file = UploadedFile::getInstanceByName('import_file');
            
            if ($file) {
                // Импорт из файла
                $uploadPath = Yii::getAlias('@webroot/uploads/import/');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $fileName = 'poizon_import_' . time() . '.' . $file->extension;
                $filePath = $uploadPath . $fileName;
                
                if ($file->saveAs($filePath)) {
                    // Запускаем импорт с файлом и ID пользователя
                    $userId = Yii::$app->user->id;
                    $logFile = Yii::getAlias('@runtime/logs/poizon-web-import-' . time() . '.log');
                    $logDir = dirname($logFile);
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0777, true);
                    }
                    
                    // Определяем тип файла и используем правильную команду
                    $extension = strtolower($file->extension);
                    if ($extension === 'json') {
                        // JSON импорт с verbose для детального лога
                        $command = "php " . Yii::getAlias('@app') . "/yii poizon-import-json/run \"{$filePath}\" --verbose=1 > \"{$logFile}\" 2>&1 &";
                    } else {
                        // Другие форматы через from-file
                        $command = "php " . Yii::getAlias('@app') . "/yii poizon-import/from-file --file=\"{$filePath}\" --userId={$userId} > \"{$logFile}\" 2>&1 &";
                    }
                    
                    exec($command);
                    
                    Yii::$app->session->setFlash('success', 
                        "Файл загружен. Импорт запущен в фоновом режиме.<br>" .
                        "Лог сохраняется в: <code>" . basename($logFile) . "</code><br>" .
                        "Проверьте статус в \"Дашборд Poizon\" или в логе: <code>runtime/logs/</code>"
                    );
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка при сохранении файла');
                }
            } else {
                // Обычный импорт через API
                $limit = Yii::$app->request->post('limit', 100);
                $command = "php " . Yii::getAlias('@app') . "/yii poizon-import/run --limit={$limit} > /dev/null 2>&1 &";
                
                exec($command);
                
                Yii::$app->session->setFlash('success', 'Импорт запущен в фоновом режиме');
            }
            
            return $this->redirect(['poizon-import']);
        }

        return $this->render('poizon-run');
    }

    /**
     * Детали батча импорта Poizon
     */
    public function actionPoizonView($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $batch = $this->findImportBatch($id);

        // Логи батча
        $logsProvider = new ActiveDataProvider([
            'query' => ImportLog::find()
                ->where(['batch_id' => $id])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('poizon-view', [
            'batch' => $batch,
            'logsProvider' => $logsProvider,
        ]);
    }

    /**
     * Просмотр лога импорта
     */
    public function actionPoizonViewLog($file = null)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }
        
        $logsDir = Yii::getAlias('@runtime/logs');
        
        // Если файл не указан, показываем последний
        if (!$file) {
            $files = glob($logsDir . '/poizon-*.log');
            if (empty($files)) {
                Yii::$app->session->setFlash('error', 'Логи импорта не найдены');
                return $this->redirect(['poizon-import']);
            }
            // Сортируем по времени модификации, берем последний
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $file = basename($files[0]);
        }
        
        $logPath = $logsDir . '/' . $file;
        
        // Проверка безопасности
        if (!file_exists($logPath) || strpos($file, '..') !== false || !preg_match('/^poizon-.+\.log$/', $file)) {
            throw new NotFoundHttpException('Лог не найден');
        }
        
        $content = file_get_contents($logPath);
        $fileSize = filesize($logPath);
        $lastModified = filemtime($logPath);
        
        // Получаем список всех логов
        $allLogs = glob($logsDir . '/poizon-*.log');
        usort($allLogs, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $logsList = [];
        foreach ($allLogs as $logFile) {
            $logsList[] = [
                'name' => basename($logFile),
                'size' => filesize($logFile),
                'time' => filemtime($logFile),
            ];
        }
        
        return $this->render('poizon-view-log', [
            'content' => $content,
            'fileName' => $file,
            'fileSize' => $fileSize,
            'lastModified' => $lastModified,
            'logsList' => $logsList,
        ]);
    }
    
    /**
     * Логи ошибок импорта Poizon
     */
    public function actionPoizonErrors()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => ImportLog::find()
                ->where(['action' => ImportLog::ACTION_ERROR])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('poizon-errors', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * API: Статус последнего импорта
     */
    public function actionPoizonStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lastBatch = ImportBatch::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$lastBatch) {
            return [
                'status' => 'no_imports',
                'message' => 'Импорты еще не запускались',
            ];
        }

        return [
            'status' => $lastBatch->status,
            'batch_id' => $lastBatch->id,
            'started_at' => $lastBatch->started_at,
            'finished_at' => $lastBatch->finished_at,
            'progress' => [
                'total' => $lastBatch->total_items,
                'created' => $lastBatch->created_count,
                'updated' => $lastBatch->updated_count,
                'errors' => $lastBatch->error_count,
            ],
            'success_rate' => $lastBatch->getSuccessRate(),
        ];
    }

    /**
     * API: Проверить наличие размера в реальном времени
     */
    public function actionPoizonCheckSize($poizonSkuId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $poizonApi = Yii::$app->get('poizonApi');
            $result = $poizonApi->checkSizeAvailability($poizonSkuId);

            return [
                'success' => true,
                'available' => $result['available'],
                'stock' => $result['stock'] ?? 0,
                'price_cny' => $result['price_cny'] ?? null,
                'delivery_days' => '14-30',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Статистика импорта
     */
    private function getImportStats()
    {
        $lastBatch = ImportBatch::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        $totalBatches = ImportBatch::find()->count();
        $successfulBatches = ImportBatch::find()
            ->where(['status' => ImportBatch::STATUS_COMPLETED])
            ->count();
        
        $totalProductsImported = ImportBatch::find()
            ->sum('created_count + updated_count');

        $totalErrors = ImportBatch::find()->sum('error_count');

        return [
            'last_batch' => $lastBatch,
            'total_batches' => $totalBatches,
            'successful_batches' => $successfulBatches,
            'total_products_imported' => $totalProductsImported ?? 0,
            'total_errors' => $totalErrors ?? 0,
            'success_rate' => $totalBatches > 0 ? round(($successfulBatches / $totalBatches) * 100, 1) : 0,
        ];
    }

    /**
     * Найти батч импорта или вернуть 404
     */
    protected function findImportBatch($id)
    {
        if (($model = ImportBatch::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Batch не найден');
    }

    // ==================== CHARACTERISTICS MANAGEMENT ====================

    /**
     * Получить список доступных характеристик для товара
     */
    public function actionGetCharacteristics($productId)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $product = $this->findProduct($productId);
        
        // Получаем все активные характеристики
        $characteristics = Characteristic::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
        
        $result = [];
        foreach ($characteristics as $char) {
            $result[] = [
                'id' => $char->id,
                'name' => $char->name,
                'key' => $char->key,
                'type' => $char->type,
                'values' => ArrayHelper::map($char->values, 'id', 'value'),
            ];
        }
        
        return [
            'success' => true,
            'characteristics' => $result,
        ];
    }

    /**
     * Добавить характеристику к товару
     */
    public function actionAddCharacteristic()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('product_id');
        $characteristicId = Yii::$app->request->post('characteristic_id');
        $valueId = Yii::$app->request->post('value_id');
        $valueText = Yii::$app->request->post('value_text');
        $valueNumber = Yii::$app->request->post('value_number');

        if (!$productId || !$characteristicId) {
            return [
                'success' => false,
                'message' => 'Не указан товар или характеристика',
            ];
        }

        $product = $this->findProduct($productId);
        $characteristic = Characteristic::findOne($characteristicId);
        
        if (!$characteristic) {
            return [
                'success' => false,
                'message' => 'Характеристика не найдена',
            ];
        }

        // Проверяем, не существует ли уже такая характеристика
        $exists = ProductCharacteristicValue::find()
            ->where([
                'product_id' => $productId,
                'characteristic_id' => $characteristicId,
            ])
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => 'Эта характеристика уже добавлена к товару',
            ];
        }

        // Создаем новую связь
        $pcv = new ProductCharacteristicValue();
        $pcv->product_id = $productId;
        $pcv->characteristic_id = $characteristicId;
        
        // В зависимости от типа характеристики сохраняем значение
        if ($valueId) {
            $pcv->characteristic_value_id = $valueId;
        } elseif ($valueText) {
            $pcv->value_text = $valueText;
        } elseif ($valueNumber !== null && $valueNumber !== '') {
            $pcv->value_number = $valueNumber;
        }

        if ($pcv->save()) {
            return [
                'success' => true,
                'message' => 'Характеристика добавлена',
                'data' => [
                    'id' => $pcv->id,
                    'name' => $characteristic->name,
                    'value' => $pcv->getDisplayValue(),
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при сохранении',
            'errors' => $pcv->errors,
        ];
    }

    /**
     * Удалить характеристику товара
     */
    public function actionDeleteCharacteristic($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $pcv = ProductCharacteristicValue::findOne($id);
        
        if (!$pcv) {
            return [
                'success' => false,
                'message' => 'Характеристика не найдена',
            ];
        }

        if ($pcv->delete()) {
            return [
                'success' => true,
                'message' => 'Характеристика удалена',
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при удалении',
        ];
    }

    /**
     * Обновить значение характеристики товара (inline editing)
     */
    public function actionUpdateCharacteristic($id)
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $pcv = ProductCharacteristicValue::findOne($id);
        
        if (!$pcv) {
            return [
                'success' => false,
                'message' => 'Характеристика не найдена',
            ];
        }

        $valueId = Yii::$app->request->post('value_id');
        $valueText = Yii::$app->request->post('value_text');
        $valueNumber = Yii::$app->request->post('value_number');

        // Сбрасываем все значения
        $pcv->characteristic_value_id = null;
        $pcv->value_text = null;
        $pcv->value_number = null;

        // Устанавливаем новое значение в зависимости от типа
        if ($valueId) {
            $pcv->characteristic_value_id = $valueId;
        } elseif ($valueText !== null && $valueText !== '') {
            $pcv->value_text = $valueText;
        } elseif ($valueNumber !== null && $valueNumber !== '') {
            $pcv->value_number = $valueNumber;
        }

        if ($pcv->save()) {
            return [
                'success' => true,
                'message' => 'Характеристика обновлена',
                'data' => [
                    'id' => $pcv->id,
                    'value' => $pcv->getDisplayValue(),
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при сохранении',
            'errors' => $pcv->errors,
        ];
    }

    /**
     * Создать новую характеристику "на лету"
     */
    public function actionCreateCharacteristic()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $name = Yii::$app->request->post('name');
        $type = Yii::$app->request->post('type', Characteristic::TYPE_TEXT);

        if (!$name) {
            return [
                'success' => false,
                'message' => 'Не указано название характеристики',
            ];
        }

        // Генерируем ключ из названия
        $key = $this->generateCharacteristicKey($name);

        // Проверяем уникальность ключа
        $exists = Characteristic::find()->where(['key' => $key])->exists();
        if ($exists) {
            return [
                'success' => false,
                'message' => 'Характеристика с таким названием уже существует',
            ];
        }

        $characteristic = new Characteristic();
        $characteristic->name = $name;
        $characteristic->key = $key;
        $characteristic->type = $type;
        $characteristic->is_active = 1;
        $characteristic->is_filter = 0;
        $characteristic->is_required = 0;
        $characteristic->sort_order = 100;

        if ($characteristic->save()) {
            return [
                'success' => true,
                'message' => 'Характеристика создана',
                'data' => [
                    'id' => $characteristic->id,
                    'name' => $characteristic->name,
                    'key' => $characteristic->key,
                    'type' => $characteristic->type,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при создании',
            'errors' => $characteristic->errors,
        ];
    }

    /**
     * Создать новое значение характеристики "на лету"
     */
    public function actionCreateCharacteristicValue()
    {
        $user = Yii::$app->user->identity;
        if (!$user->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $characteristicId = Yii::$app->request->post('characteristic_id');
        $value = Yii::$app->request->post('value');

        if (!$characteristicId || !$value) {
            return [
                'success' => false,
                'message' => 'Не указана характеристика или значение',
            ];
        }

        $characteristic = Characteristic::findOne($characteristicId);
        if (!$characteristic) {
            return [
                'success' => false,
                'message' => 'Характеристика не найдена',
            ];
        }

        // Генерируем slug из значения
        $slug = $this->generateSlug($value);

        $charValue = new CharacteristicValue();
        $charValue->characteristic_id = $characteristicId;
        $charValue->value = $value;
        $charValue->slug = $slug;
        $charValue->is_active = 1;
        $charValue->sort_order = 100;

        if ($charValue->save()) {
            return [
                'success' => true,
                'message' => 'Значение добавлено',
                'data' => [
                    'id' => $charValue->id,
                    'value' => $charValue->value,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при создании',
            'errors' => $charValue->errors,
        ];
    }

    /**
     * Генерация ключа характеристики из названия
     */
    private function generateCharacteristicKey($name)
    {
        $key = mb_strtolower($name);
        $key = preg_replace('/[^a-zа-я0-9_]/ui', '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = trim($key, '_');
        
        // Транслитерация для ключа
        $key = $this->transliterate($key);
        
        return substr($key, 0, 100);
    }

    /**
     * Генерация slug из текста
     */
    private function generateSlug($text)
    {
        $slug = mb_strtolower($text);
        $slug = $this->transliterate($slug);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return substr($slug, 0, 255);
    }

    /**
     * Простая транслитерация кириллицы
     */
    private function transliterate($text)
    {
        $translitTable = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        
        return strtr(mb_strtolower($text), $translitTable);
    }
}
