<?php

/**
 * OrderController — Управление заказами в админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Полное управление заказами: создание, редактирование, смена статусов,
 * назначение логистов, экспорт в Excel, отслеживание доставки.
 * 
 * ФУНКЦИИ:
 * - Список заказов с фильтрацией и статистикой (index)
 * - Создание заказа вручную (create)
 * - Просмотр заказа (view)
 * - Редактирование заказа (update)
 * - Смена статуса заказа (change-status)
 * - Назначение логиста (assign-logist)
 * - Экспорт заказов в Excel (export)
 * - Отмена заказа (cancel)
 * - История изменений заказа (history)
 * - Управление товарами в заказе (add-item, remove-item, update-item)
 * 
 * СВЯЗИ:
 * - Order (модель заказа)
 * - OrderItem (модель позиции заказа)
 * - OrderHistory (модель истории заказа)
 * - User (модель пользователя)
 * - DeliveryTracking (модель отслеживания доставки)
 * 
 * ДОСТУП:
 * - Админы: все заказы
 * - Менеджеры: все заказы
 * - Логисты: только назначенные им заказы
 * 
 * ОСОБЕННОСТИ:
 * - Экспорт в Excel с форматированием (PhpSpreadsheet)
 * - Автоматическое логирование всех изменений статуса
 * - Email-уведомления при смене статуса
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\checkout\models\OrderHistory;
use app\backend\modules\admin\models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderController extends BaseAdminController
{
    /**
     * Disable CSRF validation for specific actions (e.g. file upload via fetch).
     */
    public function beforeAction($action): bool
    {
        $csrfFreeActions = ['upload-file', 'auto-fill-dp'];
        if (in_array($action->id, $csrfFreeActions, true)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Список заказов с фильтрацией и статистикой
     */
    public function actionIndex()
    {
        $user = $this->getCurrentUser();
        $query = Order::find()->with(['creator', 'logist', 'orderItems', 'customer']);

        // Логист видит только свои заказы
        if ($this->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }

        // Фильтры
        $filterStatus     = Yii::$app->request->get('status');

        // By default, exclude trashed/invalid-import orders (keep for audit, hide from main list)
        if ($filterStatus !== 'imported_invalid') {
            $query->andWhere(['!=', 'status', 'imported_invalid']);
        }
        $filterLogist     = Yii::$app->request->get('logist');
        $filterSearch     = Yii::$app->request->get('search');
        $filterProcessed  = Yii::$app->request->get('processed');
        $filterDateFrom   = Yii::$app->request->get('date_from');
        $filterDateTo     = Yii::$app->request->get('date_to');
        $filterDelivery   = Yii::$app->request->get('delivery_method');
        $filterPayment    = Yii::$app->request->get('payment_method');
        $filterSource     = Yii::$app->request->get('source');
        $filterCity       = Yii::$app->request->get('city');
        $filterChinaTrack = Yii::$app->request->get('china_track');
        $filterDpTrack    = Yii::$app->request->get('dp_track');
        $filterAmountFrom = Yii::$app->request->get('amount_from');
        $filterAmountTo   = Yii::$app->request->get('amount_to');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes'   => [
                    'created_at',
                    'total_amount',
                    'client_name',
                    'status',
                    'order_number',
                ],
            ],
        ]);

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
                ['like', 'china_track_number', $filterSearch],
                ['like', 'ms_number', $filterSearch],
            ]);
        }

        if ($filterProcessed !== null && $filterProcessed !== '') {
            $query->andWhere(['is_processed' => (int)$filterProcessed]);
        }

        if ($filterDateFrom) {
            $fromTs = strtotime($filterDateFrom . ' 00:00:00');
            if ($fromTs) {
                $query->andWhere(['>=', 'created_at', $fromTs]);
            }
        }

        if ($filterDateTo) {
            $toTs = strtotime($filterDateTo . ' 23:59:59');
            if ($toTs) {
                $query->andWhere(['<=', 'created_at', $toTs]);
            }
        }

        if ($filterDelivery) {
            $query->andWhere(['delivery_method' => $filterDelivery]);
        }
        if ($filterPayment) {
            $query->andWhere(['payment_method' => $filterPayment]);
        }
        if ($filterSource) {
            $query->andWhere(['source' => $filterSource]);
        }
        if ($filterCity) {
            $query->andWhere(['like', 'city', $filterCity]);
        }
        if ($filterChinaTrack) {
            $query->andWhere(['like', 'china_track_number', $filterChinaTrack]);
        }
        if ($filterDpTrack) {
            $query->andWhere(['like', 'dp_track_number', $filterDpTrack]);
        }
        if ($filterAmountFrom !== null && $filterAmountFrom !== '') {
            $query->andWhere(['>=', 'total_amount', (float)$filterAmountFrom]);
        }
        if ($filterAmountTo !== null && $filterAmountTo !== '') {
            $query->andWhere(['<=', 'total_amount', (float)$filterAmountTo]);
        }

        $statuses = Yii::$app->settings->getStatuses();
        $statusDescriptions = [
            'new' => 'Заказ поступил и ожидает оплаты',
            'paid' => 'Клиент предоставил чек оплаты',
            'confirmed_and_paid' => 'Паспортные данные переданы, заказ готов к отправке',
            'ordered' => 'Товар заказан у поставщика',
            'awaiting_warehouse' => 'Ожидается на международном складе',
            'international_delivery' => 'В международной доставке',
            'at_warehouse' => 'Заказ на складе в Беларуси',
            'local_delivery' => 'Доставка внутри Беларуси',
            'delivered' => 'Заказ выдан клиенту',
            'canceled' => 'Заказ отменен',
        ];

        // Настройка размера страницы
        $pageSizeOptions = [20, 50, 100, 200];
        $requestedPageSize = (int)Yii::$app->request->get('per-page', $dataProvider->pagination->pageSize);
        if (!in_array($requestedPageSize, $pageSizeOptions, true)) {
            $requestedPageSize = 20;
        }
        $dataProvider->pagination->pageSize = $requestedPageSize;

        // Статистика по статусам (с кешированием)
        $statusCounts = $this->getStatusCounts($user);
        $totalCount = array_sum($statusCounts ?? []);

        // Месячная статистика (с кешированием)
        $monthlySummary = $this->getMonthlySummary($user);

        // KPI для хэдера
        $statsBaseQuery = Order::find();
        if ($this->isLogist()) {
            $statsBaseQuery->andWhere(['assigned_logist' => $user->id]);
        }

        $todayStart = strtotime('today midnight');
        $weekStart = strtotime('monday this week');
        $monthStart = strtotime(date('Y-m-01 00:00:00'));

        $kpiSummary = [
            'today' => [
                'title' => 'Сегодня',
                'orders' => (int)(clone $statsBaseQuery)->andWhere(['>=', 'created_at', $todayStart])->count(),
                'amount' => (float)((clone $statsBaseQuery)->andWhere(['>=', 'created_at', $todayStart])->sum('total_amount') ?: 0),
            ],
            'week' => [
                'title' => 'Неделя',
                'orders' => (int)(clone $statsBaseQuery)->andWhere(['>=', 'created_at', $weekStart])->count(),
                'amount' => (float)((clone $statsBaseQuery)->andWhere(['>=', 'created_at', $weekStart])->sum('total_amount') ?: 0),
            ],
            'month' => [
                'title' => 'Месяц',
                'orders' => (int)(clone $statsBaseQuery)->andWhere(['>=', 'created_at', $monthStart])->count(),
                'amount' => (float)((clone $statsBaseQuery)->andWhere(['>=', 'created_at', $monthStart])->sum('total_amount') ?: 0),
            ],
        ];

        $processedCount = (int)(clone $statsBaseQuery)->andWhere(['is_processed' => 1])->count();
        $shippedCount = (int)(clone $statsBaseQuery)->andWhere(['is_shipped' => 1])->count();
        $customsClearedCount = (int)(clone $statsBaseQuery)->andWhere(['customs_cleared' => 1])->count();
        $inWorkCount = max($totalCount - $processedCount, 0);

        $pipelineStats = [
            [
                'label' => 'В работе',
                'value' => $inWorkCount,
                'target' => $totalCount,
                'accent' => 'neutral',
            ],
            [
                'label' => 'Отправлено',
                'value' => $shippedCount,
                'target' => $totalCount,
                'accent' => 'primary',
            ],
            [
                'label' => 'Таможня пройдена',
                'value' => $customsClearedCount,
                'target' => $totalCount,
                'accent' => 'success',
            ],
        ];

        try {
            $logists = User::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all();
        } catch (\Exception $e) {
            $logists = [];
        }
        $logistMap = ArrayHelper::map($logists, 'id', 'username');

        // Duplicate china track detection (global — across all orders)
        $dupMap = [];
        try {
            $dupTracks = (new Query())
                ->select(['china_track_number', 'COUNT(*) as cnt'])
                ->from('{{%order}}')
                ->where(['not', ['china_track_number' => null]])
                ->andWhere(['!=', 'china_track_number', ''])
                ->groupBy('china_track_number')
                ->having('COUNT(*) > 1')
                ->all();
            foreach ($dupTracks as $dt) {
                $dupMap[$dt['china_track_number']] = (int)$dt['cnt'];
            }
        } catch (\Exception $e) {
            $dupMap = [];
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->get('scroll') === '1') {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $orders = $dataProvider->getModels();
            $rowsHtml = $this->renderPartial('_index_rows', [
                'orders'    => $orders,
                'statuses'  => $statuses,
                'logistMap' => $logistMap,
                'dupMap'    => $dupMap,
            ]);
            $pagination = $dataProvider->pagination;
            $hasMore = ($pagination->page + 1) < $pagination->pageCount;

            return [
                'rows'     => $rowsHtml,
                'hasMore'  => $hasMore,
                // +2: Yii2 URL page param is 1-based; $pagination->page is 0-based
                'nextPage' => $hasMore ? ($pagination->page + 2) : null,
            ];
        }

        $this->view->params['breadcrumbs'][] = 'Заказы';

        // Используем основной интерфейс списка заказов
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'pagination' => $dataProvider->pagination,
            'filterStatus' => $filterStatus,
            'filterLogist' => $filterLogist,
            'filterSearch' => $filterSearch,
            'filterProcessed' => $filterProcessed,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
            'statusCounts' => $statusCounts,
            'totalCount' => $totalCount,
            'monthlySummary' => $monthlySummary,
            'kpiSummary' => $kpiSummary,
            'pipelineStats' => $pipelineStats,
            'processedCount' => $processedCount,
            'shippedCount' => $shippedCount,
            'customsClearedCount' => $customsClearedCount,
            'pageSize' => $requestedPageSize,
            'pageSizeOptions' => $pageSizeOptions,
            'logists' => $logists,
            'logistMap' => $logistMap,
            'statuses' => $statuses,
            'statusDescriptions' => $statusDescriptions,
            'dupMap' => $dupMap,
            'user' => $user,
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

                $items = Yii::$app->request->post('OrderItem', []);
                [$totalAmount, $itemCount] = $this->saveOrderItems($model, $items, false);

                // Инвалидируем кеш статистики
                TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);

                $transaction->commit();

                Yii::info('Заказ #' . $model->id . ' создан пользователем #' . Yii::$app->user->id . ', товаров: ' . $itemCount . ', сумма: ' . $totalAmount, 'order');
                $this->flashSuccess('Заказ успешно создан!');
                
                // Очищаем черновик после успешного создания
                if (Yii::$app->request->isPost) {
                    // JavaScript очистит localStorage
                }
                
                return $this->redirect(['/admin/order/view', 'id' => $model->id]);
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Ошибка создания заказа: ' . $e->getMessage(), 'order');
                $this->flashError('Ошибка при создании заказа: ' . $e->getMessage());
            }
        }

        // Загружаем способы доставки из DB для выпадающего списка
        $shippingMethods = [];
        $rawShipping = Yii::$app->settings->get('checkout', 'shipping_methods');
        if ($rawShipping) {
            $decoded = json_decode($rawShipping, true);
            if (is_array($decoded)) {
                $shippingMethods = array_values(array_filter($decoded, fn($m) => ($m['status'] ?? '') === 'active'));
            }
        }

        // Используем новый wizard интерфейс
        return $this->render('create-wizard', [
            'model' => $model,
            'shippingMethods' => $shippingMethods,
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

        // Загружаем динамические статусы
        $statuses = Yii::$app->settings->getStatuses();

        // Загружаем методы оплаты и доставки из настроек
        $paymentMethods = Yii::$app->settings->get('checkout', 'payment_methods');
        $shippingMethods = Yii::$app->settings->get('checkout', 'shipping_methods');
        if ($paymentMethods) {
            $paymentMethods = json_decode($paymentMethods, true);
        }
        if ($shippingMethods) {
            $shippingMethods = json_decode($shippingMethods, true);
        }

        // Определяем, можно ли редактировать заказ
        $canEdit = $this->canEditOrder($model);

        // Загружаем справочник источников заказов
        $sourcesSaved = Yii::$app->settings->get('order', 'sources', '');
        $orderSources = $sourcesSaved ? (json_decode($sourcesSaved, true) ?: []) : [];

        return $this->render('view', [
            'model' => $model,
            'editing' => Yii::$app->request->get('editing', false),
            'statuses' => $statuses,
            'paymentMethods' => $paymentMethods ?: [],
            'shippingMethods' => $shippingMethods ?: [],
            'canEdit' => $canEdit,
            'orderSources' => $orderSources,
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

        if (!Yii::$app->request->isPost) {
            return $this->redirect(['/admin/order/view', 'id' => $model->id, 'editing' => 1]);
        }

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

                $items = Yii::$app->request->post('OrderItem', []);
                $this->saveOrderItems($model, $items, true);

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

        // Загружаем динамические статусы
        $statuses = Yii::$app->settings->getStatuses();
        
        // Определяем, можно ли редактировать заказ
        $canEdit = $this->canEditOrder($model);

        return $this->render('view', [
            'model' => $model,
            'editing' => true,
            'statuses' => $statuses,
            'canEdit' => $canEdit,
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
                
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Нет прав на изменение этого статуса'];
                }
                
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

                if (Yii::$app->has('automation')) {
                    Yii::$app->automation->fireEvent('order.status_changed', [
                        'order'      => $model,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                }

                // Push status update to МойСклад if Site→MS sync is enabled
                if (Yii::$app->settings->get('moysklad', 'sync_site_to_ms', '1') === '1') {
                    try {
                        Yii::$app->moysklad->pushOrderToMS($model->id);
                    } catch (\Throwable $msErr) {
                        Yii::warning('MoySklad push on status change: ' . $msErr->getMessage(), 'moysklad');
                    }
                }

                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => true, 'message' => 'Статус изменен'];
                }
                
                $this->flashSuccess('Статус заказа изменен.');
            } else {
                Yii::error('Ошибка изменения статуса заказа #' . $id . ': ' . json_encode($model->errors), 'order');
                
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Ошибка при изменении статуса'];
                }
                
                $this->flashError('Ошибка при изменении статуса.');
            }
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'Invalid request'];
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
        $query = Order::find()->with(['creator', 'logist', 'orderItems', 'customer']);

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

    protected function saveOrderItems(Order $order, array $items, bool $replaceExisting): array
    {
        if ($replaceExisting) {
            OrderItem::deleteAll(['order_id' => $order->id]);
        }

        $totalAmount = 0;
        $itemCount = 0;

        foreach ($items as $itemData) {
            $productName = trim((string)($itemData['product_name'] ?? ''));
            $priceRaw = $itemData['price'] ?? null;

            if ($productName === '' || $priceRaw === null || $priceRaw === '') {
                continue;
            }

            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->product_name = $productName;
            $item->quantity = isset($itemData['quantity']) ? (int)$itemData['quantity'] : 1;
            $item->price = (float)$priceRaw;

            if (!$item->save()) {
                throw new \Exception('Ошибка сохранения товара: ' . json_encode($item->errors));
            }

            $totalAmount += $item->total;
            $itemCount++;
        }

        if ($itemCount === 0) {
            throw new \Exception('Необходимо добавить хотя бы один товар в заказ');
        }

        $order->total_amount = $totalAmount;
        if (!$order->save(false)) {
            throw new \Exception('Ошибка обновления суммы заказа');
        }

        return [$totalAmount, $itemCount];
    }

    /**
     * Обновление статуса через AJAX (для Канбан-доски и быстрых действий)
     */
    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод должен быть POST'];
        }

        $id     = (int)Yii::$app->request->post('id');
        $status = Yii::$app->request->post('status');

        if (!$id || !$status) {
            return ['success' => false, 'message' => 'Не указан id или status'];
        }

        $model = Order::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $user = $this->getCurrentUser();
        if ($this->isLogist() && $model->assigned_logist != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        $oldStatus  = $model->status;
        $model->status = $status;

        if ($model->save(false)) {
            if ($oldStatus !== $status) {
                OrderHistory::log($model->id, 'status_changed', 'status', $oldStatus, $status);
            }
            TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения', 'errors' => $model->errors];
    }

    /**
     * Массовые действия над заказами (смена статуса, назначение логиста, экспорт CSV)
     */
    public function actionBulkAction()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод должен быть POST'];
        }

        $action  = Yii::$app->request->post('action');
        $ids     = Yii::$app->request->post('ids', []);
        $extra   = Yii::$app->request->post('extra'); // статус или logist_id

        if (empty($ids)) {
            return ['success' => false, 'message' => 'Не выбраны заказы'];
        }

        if ($action === 'change_status') {
            if (!$extra) {
                return ['success' => false, 'message' => 'Не указан статус'];
            }
            $updated = Order::updateAll(['status' => $extra], ['id' => $ids]);
            TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);
            return ['success' => true, 'updated' => $updated, 'message' => "Статус изменён у $updated заказов"];
        }

        if ($action === 'assign_logist') {
            if (!$this->isAdmin()) {
                return ['success' => false, 'message' => 'Доступ запрещён'];
            }
            $updated = Order::updateAll(['assigned_logist' => $extra ?: null], ['id' => $ids]);
            return ['success' => true, 'updated' => $updated, 'message' => "Логист назначен у $updated заказов"];
        }

        if ($action === 'export_csv') {
            // Отдаём CSV для выбранных заказов прямо из AJAX ответа (redirect)
            // Для CSV экспорта — редиректим на export action с id списком
            return ['success' => true, 'redirect' => \yii\helpers\Url::to(['/admin/order/export-csv', 'ids' => implode(',', $ids)])];
        }

        return ['success' => false, 'message' => 'Неизвестное действие'];
    }

    /**
     * Добавление заметки к заказу (AJAX)
     */
    public function actionAddNote($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод должен быть POST'];
        }

        $model = $this->findModel($id);
        $user  = $this->getCurrentUser();

        if ($this->isLogist() && $model->assigned_logist != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        $text = trim(Yii::$app->request->post('text', ''));
        if ($text === '') {
            return ['success' => false, 'message' => 'Текст заметки не может быть пустым'];
        }

        // Сохраняем в OrderHistory с пометкой что это заметка
        $note = new OrderHistory();
        $note->order_id   = $model->id;
        $note->action     = 'note_added';
        $note->old_status = $model->status;
        $note->new_status = $model->status;
        $note->changed_by = $user->id;
        $note->user_name  = $user->username ?? null;
        $note->ip_address = Yii::$app->request->userIP ?? null;
        $note->comment    = $text;
        $note->save(false);

        $html = $this->renderPartial('_notes', [
            'notes' => [$note],
            'statuses' => Yii::$app->settings->getStatuses(),
        ]);

        return ['success' => true, 'html' => $html];
    }

    /**
     * PDF / printable invoice для заказа
     */
    public function actionPdf($id)
    {
        $model  = $this->findModel($id);
        $user   = $this->getCurrentUser();

        if ($this->isLogist() && $model->assigned_logist != $user->id) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        $statuses = Yii::$app->settings->getStatuses();
        $html = $this->renderPartial('_invoice_print', [
            'model'    => $model,
            'statuses' => $statuses,
        ]);

        // Пытаемся использовать TCPDF если есть, иначе — HTML с print script
        if (class_exists('\TCPDF')) {
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('Sneaker Shop Admin');
            $pdf->SetTitle('Заказ №' . ($model->order_number ?: $model->id));
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('order_' . $model->id . '.pdf', 'D');
            Yii::$app->end();
        }

        // Fallback — отдаём HTML-страницу с автопечатью
        Yii::$app->response->content = '<!DOCTYPE html><html><head>'
            . '<meta charset="UTF-8">'
            . '<title>Заказ №' . ($model->order_number ?: $model->id) . '</title>'
            . '<style>body{font-family:Arial,sans-serif;padding:20px;}@media print{.no-print{display:none}}</style>'
            . '</head><body onload="window.print()">'
            . '<div class="no-print" style="margin-bottom:1rem;">'
            . '<button onclick="window.print()">Печать</button> '
            . '<button onclick="window.close()">Закрыть</button>'
            . '</div>'
            . $html
            . '</body></html>';
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        return Yii::$app->response;
    }

    /**
     * Экспорт выбранных заказов в CSV (UTF-8 BOM для Excel)
     */
    public function actionExportCsv()
    {
        $user  = $this->getCurrentUser();
        $idsRaw = Yii::$app->request->get('ids', '');
        $ids = $idsRaw ? array_map('intval', explode(',', $idsRaw)) : [];

        $query = Order::find()->with(['creator', 'logist', 'orderItems', 'customer']);

        if ($this->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }

        // Фильтры из GET (если не переданы ids — применяем текущие фильтры)
        if (!empty($ids)) {
            $query->andWhere(['id' => $ids]);
        } else {
            $filterStatus  = Yii::$app->request->get('status');
            $filterLogist  = Yii::$app->request->get('logist');
            $filterSearch  = Yii::$app->request->get('search');
            $filterDateFrom = Yii::$app->request->get('date_from');
            $filterDateTo  = Yii::$app->request->get('date_to');

            if ($filterStatus)  $query->andWhere(['status' => $filterStatus]);
            if ($filterLogist)  $query->andWhere(['assigned_logist' => $filterLogist]);
            if ($filterSearch)  $query->andWhere(['or', ['like', 'order_number', $filterSearch], ['like', 'client_name', $filterSearch], ['like', 'client_phone', $filterSearch]]);
            if ($filterDateFrom) $query->andWhere(['>=', 'created_at', strtotime($filterDateFrom . ' 00:00:00')]);
            if ($filterDateTo)  $query->andWhere(['<=', 'created_at', strtotime($filterDateTo . ' 23:59:59')]);
        }

        $orders   = $query->orderBy(['created_at' => SORT_ASC])->all();
        $statuses = Yii::$app->settings->getStatuses();

        $headers = ['Номер', 'Дата', 'Клиент', 'Телефон', 'Товар', 'Размер', 'Сумма', 'Статус', 'Менеджер', 'Трек', 'Метод доставки'];

        $rows = [];
        $rows[] = $headers;

        foreach ($orders as $order) {
            $itemNames = [];
            $itemSizes = [];
            foreach ($order->orderItems as $item) {
                $itemNames[] = $item->product_name;
                $itemSizes[] = $item->quantity . ' шт.';
            }

            $rows[] = [
                $order->order_number ?: $order->id,
                date('d.m.Y H:i', $order->created_at),
                $order->client_name,
                $order->client_phone,
                implode('; ', $itemNames),
                implode('; ', $itemSizes),
                number_format($order->total_amount, 2),
                $statuses[$order->status] ?? $order->status,
                $order->creator ? $order->creator->username : '-',
                $order->china_track_number ?: '-',
                $order->delivery_method ?: '-',
            ];
        }

        $filename = 'orders_export_' . date('Y-m-d') . '.csv';
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM
        foreach ($rows as $row) {
            $escaped = array_map(function($cell) {
                $cell = str_replace('"', '""', (string)$cell);
                return '"' . $cell . '"';
            }, $row);
            $csvContent .= implode(';', $escaped) . "\r\n";
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->content = $csvContent;
        return Yii::$app->response;
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

    /**
     * Обновление отдельного поля заказа (AJAX inline редактирование)
     * 
     * @param int $id
     */
    public function actionUpdateField($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        $user = $this->getCurrentUser();
        
        // Проверка прав доступа
        if ($this->isLogist() && $model->assigned_logist != $user->id) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }
        
        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');
        
        // Разрешенные поля для inline редактирования
        $allowedFields = [
            'client_name', 'client_phone', 'client_email', 'delivery_date', 'comment',
            'china_track_number', 'shipment_value_cny', 'status',
            'recipient_last_name', 'recipient_first_name', 'recipient_middle_name',
            'passport_series', 'passport_number', 'passport_issue_date', 'birth_date', 'inn',
            'full_address', 'city', 'region', 'postal_code',
            'customs_description', 'item_quantity', 'item_price_cny', 'product_link',
            'dobropost_tariff', 'sneakerhead_order_link', 'ms_number',
            'is_processed', 'is_shipped', 'customs_cleared',
            'product_price', 'logistics_price', 'commission_price'
        ];
        
        if (!in_array($field, $allowedFields)) {
            return ['success' => false, 'message' => 'Недопустимое поле: ' . $field];
        }
        
        // Логист может менять только определенные поля
        $logistFields = [
            'china_track_number', 'is_processed', 'is_shipped', 'customs_cleared',
            'ms_number', 'status', 'comment', 'delivery_date'
        ];
        
        if ($this->isLogist() && !in_array($field, $logistFields)) {
            return ['success' => false, 'message' => 'Недостаточно прав для изменения этого поля'];
        }
        
        // Преобразование типов
        if (in_array($field, ['is_processed', 'is_shipped', 'customs_cleared'])) {
            $value = (bool)$value;
        }
        if (in_array($field, ['shipment_value_cny', 'item_price_cny', 'product_price', 'logistics_price', 'commission_price'])) {
            $value = $value ? (float)$value : null;
        }
        if ($field === 'item_quantity') {
            $value = $value ? (int)$value : null;
        }
        
        $oldValue = $model->$field;
        $model->$field = $value;
        
        if ($model->save(false)) {
            // Логируем изменение статуса
            if ($field === 'status' && $oldValue !== $value) {
                $history = new OrderHistory();
                $history->order_id = $model->id;
                $history->old_status = $oldValue;
                $history->new_status = $value;
                $history->changed_by = $user->id;
                $history->save();
            }
            
            // Инвалидируем кеш
            \yii\caching\TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);
            
            Yii::info("Поле '$field' заказа #{$id} изменено: '$oldValue' -> '$value' (пользователь #{$user->id})", 'order');
            
            return ['success' => true, 'value' => $value];
        }
        
        return ['success' => false, 'message' => 'Ошибка сохранения', 'errors' => $model->errors];
    }

    /**
     * Массовое обновление статуса заказов
     */
    public function actionBulkUpdateStatus()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->isAdmin() && !$this->isManager()) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }
        
        $ids = Yii::$app->request->post('ids', []);
        $status = Yii::$app->request->post('status');
        
        if (empty($ids) || !$status) {
            return ['success' => false, 'message' => 'Не указаны заказы или статус'];
        }
        
        $updated = Order::updateAll(['status' => $status], ['id' => $ids]);
        
        \yii\caching\TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);
        
        return ['success' => true, 'updated' => $updated];
    }

    /**
     * Проверяет, может ли текущий пользователь редактировать заказ
     * 
     * @param Order $model
     * @return bool
     */
    protected function canEditOrder($model)
    {
        // Админ и менеджер могут редактировать все заказы
        if ($this->isAdmin() || $this->isManager()) {
            return true;
        }
        
        // Логист может редактировать только свои заказы
        if ($this->isLogist()) {
            $user = $this->getCurrentUser();
            return $model->assigned_logist == $user->id;
        }
        
        return false;
    }

    /**
     * Массовое назначение логиста
     */
    public function actionBulkAssignLogist()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }
        
        $ids = Yii::$app->request->post('ids', []);
        $logistId = Yii::$app->request->post('logist_id');
        
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Не указаны заказы'];
        }
        
        $updated = Order::updateAll(['assigned_logist' => $logistId ?: null], ['id' => $ids]);
        
        return ['success' => true, 'updated' => $updated];
    }

    /**
     * Массовое обновление полей заказов
     */
    public function actionBulkUpdateField()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->isAdmin() && !$this->isManager()) {
            return ['success' => false, 'message' => 'Доступ запрещен'];
        }
        
        $ids = json_decode(Yii::$app->request->post('ids', []), true);
        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');
        
        if (empty($ids) || !$field) {
            return ['success' => false, 'message' => 'Не указаны заказы или поле'];
        }
        
        // Разрешенные поля для массового обновления
        $allowedFields = ['is_processed', 'is_shipped', 'customs_cleared'];
        
        if (!in_array($field, $allowedFields)) {
            return ['success' => false, 'message' => 'Недопустимое поле'];
        }
        
        $updated = Order::updateAll([$field => $value], ['id' => $ids]);

        \yii\caching\TagDependency::invalidate(Yii::$app->cache, ['orders-stats']);

        return ['success' => true, 'updated' => $updated];
    }

    /** B5 — Save single order field (track, address, delivery status) */
    public function actionSaveField()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id = (int)($data['id'] ?? 0);
        $field = $data['field'] ?? '';
        $value = $data['value'] ?? '';
        $allowed = ['china_track_number','local_track_number','delivery_method','delivery_address',
            'pickup_point','china_delivery_status','warehouse_arrival_date','delivery_date'];
        if (!in_array($field, $allowed)) return ['success' => false, 'message' => 'Недопустимое поле'];
        $order = Order::findOne($id);
        if (!$order) return ['success' => false, 'message' => 'Не найден'];
        $oldValue = $order->getAttribute($field);
        $order->$field = $value;
        $order->save(false);
        if ((string)$oldValue !== (string)$value) {
            OrderHistory::log($order->id, 'field_changed', $field, $oldValue, $value);
        }
        return ['success' => true];
    }

    /** Feature 6 — MoySklad sync */
    public function actionSyncMoysklad()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data  = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $id    = (int)($data['id'] ?? Yii::$app->request->post('id', 0));
        $order = Order::findOne($id);
        if (!$order) return ['success' => false, 'message' => 'Заказ не найден'];

        try {
            $result = Yii::$app->moysklad->pushOrder($order);
            $msId   = $result['id'] ?? null;
            return ['success' => true, 'message' => 'Заказ отправлен в МойСклад', 'ms_id' => $msId];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Поиск пунктов выдачи Европочты (PVZ) — для автокомплита в админке заказа.
     * Источник данных: app_setting[section='shipping', key='europochta_points'] — JSON массив.
     * GET ?q=<строка поиска> — фильтрует по city/name/full
     */
    public function actionPvzSearch()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $q     = mb_strtolower(trim((string) Yii::$app->request->get('q', '')));
        $limit = max(1, min(500, (int) Yii::$app->request->get('limit', 60)));

        $raw  = Yii::$app->settings->get('shipping', 'europochta_points', '');
        $list = $raw ? (json_decode($raw, true) ?: []) : [];
        if (!is_array($list) || empty($list)) {
            return [];
        }

        $results = [];
        foreach ($list as $pvz) {
            if ($q !== '') {
                $haystack = mb_strtolower(
                    ($pvz['city'] ?? '') . ' '
                    . ($pvz['name'] ?? '') . ' '
                    . ($pvz['full'] ?? '') . ' '
                    . ($pvz['num']  ?? '')
                );
                if (mb_strpos($haystack, $q) === false) {
                    continue;
                }
            }
            $results[] = [
                'id'       => $pvz['id'] ?? ($pvz['num'] ?? ''),
                'num'      => $pvz['num'] ?? '',
                'city'     => $pvz['city'] ?? '',
                'address'  => $pvz['name'] ?? '',
                'full'     => $pvz['full'] ?? '',
                'schedule' => $pvz['schedule'] ?? ($pvz['work_time'] ?? ''),
            ];
            if (count($results) >= $limit) break;
        }

        // При пустом запросе — группируем по городу для удобства
        if ($q === '' && !empty($results)) {
            usort($results, function($a, $b) {
                $c = strcmp($a['city'] ?? '', $b['city'] ?? '');
                if ($c !== 0) return $c;
                return strcmp($a['num'] ?? '', $b['num'] ?? '');
            });
        }

        return $results;
    }

    /** B5/B11 — Track check. Accepts ?track=XXX&orderId=YYY */
    public function actionCheckTrack()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $track   = trim(Yii::$app->request->get('track', ''));
        $orderId = (int)Yii::$app->request->get('orderId', 0);

        if (empty($track)) {
            return ['success' => false, 'status' => 'Трек не указан'];
        }

        // Determine delivery method from order (if provided)
        $deliveryMethod = '';
        if ($orderId) {
            $order = Order::findOne($orderId);
            if ($order) {
                $deliveryMethod = strtolower($order->delivery_method ?? '');
            }
        }

        try {
            $result = $this->callTrackingService($track, $deliveryMethod);
        } catch (\Exception $e) {
            Yii::warning('Track check error: ' . $e->getMessage(), 'tracking');
            return ['success' => false, 'status' => 'Ошибка: ' . $e->getMessage()];
        }

        // Format status text for display
        $statusName = $result['status_name'] ?? $result['message'] ?? $result['status'] ?? 'Нет данных';
        $date       = $result['status_date'] ?? null;
        $location   = $result['location'] ?? null;

        $text = $statusName;
        if ($date) {
            $ts = is_numeric($date) ? (int)$date : strtotime($date);
            if ($ts) $text .= ' (' . date('d.m.Y', $ts) . ')';
        }
        if ($location) {
            $text .= ' — ' . $location;
        }

        return ['success' => true, 'status' => $text, 'raw' => $result];
    }

    /** Pick and call the right tracking service */
    private function callTrackingService(string $track, string $deliveryMethod): array
    {
        switch ($deliveryMethod) {
            case 'europochta':
                if (Yii::$app->has('europochtaTracking')) {
                    return Yii::$app->europochtaTracking->getStatus($track);
                }
                break;
            case 'belpochta':
                if (Yii::$app->has('belpochtaTracking')) {
                    return Yii::$app->belpochtaTracking->getStatus($track);
                }
                break;
            case 'cdek':
            case 'sdek':
                if (Yii::$app->has('cdekTracking')) {
                    return Yii::$app->cdekTracking->getStatus($track);
                }
                break;
        }

        // No specific provider — try each configured one in order
        foreach (['europochtaTracking' => 'europochta', 'belpochtaTracking' => 'belpochta', 'cdekTracking' => 'cdek'] as $component => $name) {
            if (!Yii::$app->has($component)) continue;
            $svc = Yii::$app->$component;
            if (!$svc->isConfigured()) continue;
            $result = $svc->getStatus($track);
            if (!in_array($result['status'] ?? '', ['not_found', 'not_configured', 'error'])) {
                return $result;
            }
        }

        return ['status' => 'not_found', 'message' => 'Отправление не найдено. Проверьте на сайте перевозчика.'];
    }

    /**
     * POST /admin/order/{id}/send-to-dp
     * Вручную отправить заказ в Таможня:ДП
     */
    public function actionSendToDp($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        if (!$model->isPassportComplete()) {
            return ['success' => false, 'message' => 'Паспортные данные получателя заполнены не полностью'];
        }

        if ($model->isSubmittedToDP()) {
            return ['success' => false, 'message' => 'Заказ уже отправлен в Таможня:ДП (шипмент #' . $model->dp_shipment_id . ')'];
        }

        try {
            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp = Yii::$app->dobropost;
            $response = $dp->createShipment($model);

            return [
                'success'    => true,
                'message'    => 'Заказ успешно отправлен в Таможня:ДП',
                'shipment_id'  => $response['id'] ?? null,
                'track_number' => $response['dptrackNumber'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('Исключение при отправке заказа #' . $id . ' в Таможня:ДП: ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * GET /admin/order/{id}/dp-status
     * Актуализировать статус шипмента из Таможня:ДП
     */
    public function actionDpStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        if (!$model->isSubmittedToDP()) {
            return ['success' => false, 'message' => 'Заказ не отправлен в Таможня:ДП'];
        }

        try {
            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp = Yii::$app->dobropost;

            // Запрашиваем список шипментов и ищем наш по ID
            $result  = $dp->getShipments(['statusId' => null]);
            $content = $result['content'] ?? ($result[0] ?? null);

            // Поддерживаем оба формата ответа: объект (одна запись) и массив (список)
            $shipment = null;
            if (isset($result['id']) && $result['id'] == $model->dp_shipment_id) {
                $shipment = $result;
            } elseif (is_array($result)) {
                foreach ($result as $s) {
                    if (isset($s['id']) && $s['id'] == $model->dp_shipment_id) {
                        $shipment = $s;
                        break;
                    }
                }
            }

            if ($shipment) {
                $dp->handleCreateResponse($model, $shipment);
                $statusName = $shipment['status']['name'] ?? $model->dp_status;
                return ['success' => true, 'message' => 'Статус обновлён: ' . $statusName, 'status' => $statusName];
            }

            return ['success' => true, 'message' => 'Шипмент не найден в ответе API', 'status' => $model->dp_status];
        } catch (\Exception $e) {
            Yii::error('Ошибка обновления статуса ДП заказа #' . $id . ': ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * POST /admin/order/{id}/retry-dp
     * Повторить отправку в Таможня:ДП (сброс и пересоздание шипмента)
     */
    public function actionRetryDp($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        if (!$model->isPassportComplete()) {
            return ['success' => false, 'message' => 'Паспортные данные получателя заполнены не полностью'];
        }

        try {
            // Сброс DP-полей перед повторной отправкой
            $model->dp_shipment_id  = null;
            $model->dp_track_number = null;
            $model->dp_status       = null;
            $model->dp_status_date  = null;
            $model->dp_sent_at      = null;
            $model->dp_response     = null;
            $model->save(false);

            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp       = Yii::$app->dobropost;
            $response = $dp->createShipment($model);

            Yii::info('Повторная отправка заказа #' . $id . ' в Таможня:ДП успешна, шипмент #' . ($response['id'] ?? '-'), 'dp-api');
            return [
                'success'      => true,
                'message'      => 'Повторная отправка выполнена успешно',
                'shipment_id'  => $response['id'] ?? null,
                'track_number' => $response['dptrackNumber'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка повторной отправки заказа #' . $id . ' в Таможня:ДП: ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Сохранение состава заказа (товары) из формы редактирования.
     * POST /admin/order/update-items?id=...
     */
    public function actionUpdateItems($id)
    {
        $model = $this->findModel($id);

        if ($this->isLogist()) {
            throw new \yii\web\ForbiddenHttpException('Доступ запрещён.');
        }

        if (!Yii::$app->request->isPost) {
            return $this->redirect(['/admin/order/view', 'id' => $id]);
        }

        $items = Yii::$app->request->post('OrderItem', []);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            [$totalAmount, $itemCount] = $this->saveOrderItems($model, $items, true);
            $transaction->commit();
            $this->flashSuccess('Состав заказа обновлён. Итого: ' . number_format($totalAmount, 2) . ' Br (' . $itemCount . ' позиций)');
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->flashError('Ошибка: ' . $e->getMessage());
        }

        return $this->redirect(['/admin/order/view', 'id' => $id]);
    }

    /**
     * Upload files for an order (called via fetch from the order view page).
     * Accepts POST with multipart files[].
     * Saves to uploads/orders/{order_id}/ and returns JSON with file info.
     *
     * @param int $id Order ID
     * @return array JSON response
     */
    public function actionUploadFile($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = $this->findModel($id);
        if (!$order) {
            return ['success' => false, 'error' => 'Заказ не найден'];
        }

        $uploadDir = Yii::getAlias('@app') . '/uploads/orders/' . $order->id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedFiles = \yii\web\UploadedFile::getInstancesByName('files');
        if (empty($uploadedFiles)) {
            return ['success' => false, 'error' => 'Файлы не были загружены'];
        }

        $savedFiles = [];
        $firstImagePath = null;

        foreach ($uploadedFiles as $file) {
            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->name);
            $filePath = $uploadDir . '/' . $safeName;

            if ($file->saveAs($filePath)) {
                $url = '/uploads/orders/' . $order->id . '/' . $safeName;
                $savedFiles[] = [
                    'name' => $file->name,
                    'url'  => $url,
                ];

                // Track first image for payment_proof
                if ($firstImagePath === null && in_array($file->type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $firstImagePath = $url;
                }
            }
        }

        // Set payment_proof to first uploaded image if not already set
        if ($firstImagePath !== null && empty($order->payment_proof)) {
            $order->payment_proof = $firstImagePath;
            $order->save(false, ['payment_proof']);
        }

        return [
            'success' => true,
            'files'   => $savedFiles,
        ];
    }

    /**
     * Авто-заполнение полей ДП из данных заказа и профиля клиента.
     * Заполняет только пустые поля, не перезаписывает существующие данные.
     */
    public function actionAutoFillDp($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $filled = [];

        // 1. Парсим client_name → recipient_last_name, recipient_first_name, recipient_middle_name
        $clientName = trim($order->client_name ?? '');
        if ($clientName !== '') {
            $parts = preg_split('/\s+/', $clientName, 3);
            if (empty($order->recipient_last_name) && !empty($parts[0])) {
                $order->recipient_last_name = $parts[0];
                $filled[] = 'recipient_last_name';
            }
            if (empty($order->recipient_first_name) && !empty($parts[1])) {
                $order->recipient_first_name = $parts[1];
                $filled[] = 'recipient_first_name';
            }
            if (empty($order->recipient_middle_name) && !empty($parts[2])) {
                $order->recipient_middle_name = $parts[2];
                $filled[] = 'recipient_middle_name';
            }
        }

        // 2. delivery_address → full_address
        if (empty($order->full_address) && !empty($order->delivery_address)) {
            $order->full_address = $order->delivery_address;
            $filled[] = 'full_address';
        }

        // 3. Поля адреса (city, region, postal_code) — уже могут быть в заказе,
        //    но если пустые, пробуем из профиля клиента
        // (city, region, postal_code уже хранятся в самом заказе, заполнять нечего)

        // 4. Устанавливаем package_type по умолчанию
        if (empty($order->package_type)) {
            $order->package_type = 'parcel';
            $filled[] = 'package_type';
        }

        // 5. Копируем паспортные данные из профиля клиента (если есть)
        if ($order->customer_id) {
            $customer = \app\backend\modules\account\models\Customer::findOne($order->customer_id);
            if ($customer) {
                // Паспортные данные
                if (empty($order->passport_series) && !empty($customer->passport_series)) {
                    $order->passport_series = $customer->passport_series;
                    $filled[] = 'passport_series';
                }
                if (empty($order->passport_number) && !empty($customer->passport_number)) {
                    $order->passport_number = $customer->passport_number;
                    $filled[] = 'passport_number';
                }
                if (empty($order->passport_issue_date) && !empty($customer->passport_issue_date)) {
                    $order->passport_issue_date = $customer->passport_issue_date;
                    $filled[] = 'passport_issue_date';
                }
                if (empty($order->inn) && !empty($customer->inn)) {
                    $order->inn = $customer->inn;
                    $filled[] = 'inn';
                }
                if (empty($order->birth_date) && !empty($customer->birth_date)) {
                    $order->birth_date = $customer->birth_date;
                    $filled[] = 'birth_date';
                }

                // ФИО из профиля клиента (если из client_name не заполнилось)
                if (empty($order->recipient_last_name) && !empty($customer->last_name)) {
                    $order->recipient_last_name = $customer->last_name;
                    $filled[] = 'recipient_last_name';
                }
                if (empty($order->recipient_first_name) && !empty($customer->first_name)) {
                    $order->recipient_first_name = $customer->first_name;
                    $filled[] = 'recipient_first_name';
                }
                if (empty($order->recipient_middle_name) && !empty($customer->middle_name)) {
                    $order->recipient_middle_name = $customer->middle_name;
                    $filled[] = 'recipient_middle_name';
                }

                // Адресные данные из профиля клиента
                if (empty($order->city) && !empty($customer->default_city)) {
                    $order->city = $customer->default_city;
                    $filled[] = 'city';
                }
                if (empty($order->full_address) && !empty($customer->default_address)) {
                    $order->full_address = $customer->default_address;
                    $filled[] = 'full_address';
                }
            }
        }

        if (!empty($filled)) {
            $order->save(false, $filled);
        }

        return [
            'success' => true,
            'filled'  => $filled,
            'message' => empty($filled)
                ? 'Все поля уже заполнены, нечего обновлять'
                : 'Заполнено полей: ' . count($filled),
        ];
    }

    /**
     * Mark bad import orders as 'imported_invalid' (keep for audit, hide from main list).
     * Targets: orders with email='1@mail.ru' AND 0 items.
     * POST /admin/order/clean-bad-import
     */
    public function actionCleanBadImport()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        $count = Yii::$app->db->createCommand("
            UPDATE `order`
            SET status = 'imported_invalid'
            WHERE status = 'imported'
              AND client_email = '1@mail.ru'
              AND (
                SELECT COUNT(*) FROM order_item oi WHERE oi.order_id = `order`.id
              ) = 0
        ")->execute();

        return [
            'success' => true,
            'marked' => $count,
            'message' => "Помечено как imported_invalid: {$count} заказов",
        ];
    }
}
