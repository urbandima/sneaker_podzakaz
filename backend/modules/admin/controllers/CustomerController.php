<?php

/**
 * CustomerController — Управление покупателями в админ-панели
 *
 * НАЗНАЧЕНИЕ:
 * Просмотр и управление зарегистрированными покупателями,
 * их заказами, баллами лояльности, тегами и заметками.
 *
 * ФУНКЦИИ:
 * - Список покупателей с поиском и фильтрами (index)
 * - Просмотр профиля покупателя (view)
 * - Редактирование данных покупателя (update)
 * - Блокировка/разблокировка покупателя (toggle-status)
 * - История заказов покупателя (orders)
 * - Отправка уведомления покупателю (notify)
 * - Добавление заметки команды (add-note)
 * - Начисление баллов лояльности (add-points)
 * - Списание баллов лояльности (deduct-points)
 * - Обновление тегов покупателя (update-tags)
 *
 * СВЯЗИ:
 * - Customer (модель покупателя)
 * - Order (модель заказа)
 * - LoyaltyPoints (модель баллов)
 *
 * ДОСТУП:
 * - Администраторы и менеджеры
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\backend\modules\account\models\Customer;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\loyalty\models\LoyaltyPoints;

class CustomerController extends BaseAdminController
{
    public function beforeAction($action): bool
    {
        // CSRF отключён для AJAX-действий, которые вызываются фронтендом через fetch/XMLHttpRequest.
        // Защиту обеспечивает проверка заголовка X-Requested-With + ограничение доступа через
        // BaseAdminController (только авторизованные пользователи с ролью admin/logist).
        // TODO: перейти на передачу CSRF-токена через заголовок X-CSRF-Token в JS-клиенте,
        //       после чего убрать это исключение.
        $ajaxActions = ['adjust-points', 'add-points', 'deduct-points', 'add-tag', 'remove-tag', 'add-note', 'toggle-status', 'notify', 'create-from-order', 'search'];
        if (in_array($action->id, $ajaxActions, true)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Список покупателей (с поддержкой бесконечной прокрутки)
     */
    public function actionIndex()
    {
        $query = Customer::find();

        // A12: hide auto-generated import phantom accounts by default
        $showPhantoms = Yii::$app->request->get('show_phantoms');
        if (!$showPhantoms) {
            $query->andWhere(['NOT LIKE', 'email', 'ms_%', false]);
        }

        // Поиск
        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere(['or',
                ['like', 'email', $search],
                ['like', 'phone', $search],
                ['like', 'first_name', $search],
                ['like', 'last_name', $search],
            ]);
        }

        // Фильтр по статусу
        $status = Yii::$app->request->get('status');
        if ($status !== null && $status !== '') {
            $query->andWhere(['status' => (int)$status]);
        }

        // W10: фильтр по наличию заказов
        $hasOrders = Yii::$app->request->get('has_orders');
        if ($hasOrders === '1') {
            $query->innerJoin('{{%order}} o_filter', 'o_filter.customer_id = customer.id')->distinct();
        } elseif ($hasOrders === '0') {
            $query->leftJoin('{{%order}} o_filter', 'o_filter.customer_id = customer.id')
                  ->andWhere(['o_filter.id' => null])->distinct();
        }

        // Сортировка (поддержка формата: sort=col или sort=-col для DESC)
        $sortParam = Yii::$app->request->get('sort', '-created_at');
        $allowedSortCols = ['created_at', 'first_name', 'email', 'phone', 'orders_count', 'total_spent', 'last_order_at', 'status'];
        if ($sortParam) {
            $desc = false;
            $col = $sortParam;
            if (strpos($sortParam, '-') === 0) {
                $desc = true;
                $col = substr($sortParam, 1);
            }
            if (in_array($col, $allowedSortCols, true)) {
                $query->orderBy([$col => $desc ? SORT_DESC : SORT_ASC]);
            } else {
                $query->orderBy(['created_at' => SORT_DESC]);
            }
        } else {
            $query->orderBy(['created_at' => SORT_DESC]);
        }

        $pageSize = 50;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

        // AJAX infinite scroll response
        if (Yii::$app->request->isAjax && Yii::$app->request->get('scroll') === '1') {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $customers = $dataProvider->getModels();
            $rowsHtml = $this->renderPartial('_index_rows', [
                'customers' => $customers,
            ]);
            $pagination = $dataProvider->pagination;
            $hasMore = ($pagination->page + 1) < $pagination->pageCount;

            return [
                'rows'     => $rowsHtml,
                'hasMore'  => $hasMore,
                'nextPage' => $hasMore ? ($pagination->page + 2) : null,
            ];
        }

        // Статистика
        $withOrdersSql = 'SELECT COUNT(DISTINCT c.id) FROM {{%customer}} c INNER JOIN {{%order}} o ON o.customer_id = c.id';
        $withOrdersCount = (int)Yii::$app->db->createCommand($withOrdersSql)->queryScalar();
        $totalCustomers  = (int)Customer::find()->count();
        $stats = [
            'total'          => $totalCustomers,
            'active'         => Customer::find()->where(['status' => Customer::STATUS_ACTIVE])->count(),
            'inactive'       => Customer::find()->where(['status' => Customer::STATUS_INACTIVE])->count(),
            'new_today'      => Customer::find()->where(['>=', 'created_at', strtotime('today')])->count(),
            'new_week'       => Customer::find()->where(['>=', 'created_at', strtotime('-7 days')])->count(),
            'with_orders'    => $withOrdersCount,
            'without_orders' => $totalCustomers - $withOrdersCount,
        ];

        $totalCount = $dataProvider->getTotalCount();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Просмотр покупателя
     */
    /**
     * AJAX quick-view popup: returns HTML fragment with customer summary.
     * GET /admin/customer/quick-view?id=X
     */
    public function actionQuickView($id)
    {
        $customer = Customer::findOne($id);
        if (!$customer) {
            throw new \yii\web\NotFoundHttpException('Покупатель не найден');
        }

        $loyaltyBalance = 0;
        try { $loyaltyBalance = LoyaltyPoints::getBalance($id); } catch (\Exception $e) {}

        $recentOrders = Order::find()
            ->where(['customer_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        $notes = $this->getCustomerNotes($id);
        $tags  = $this->getCustomerTags($customer);

        return $this->renderPartial('_quick_view', [
            'customer'       => $customer,
            'loyaltyBalance' => $loyaltyBalance,
            'recentOrders'   => $recentOrders,
            'notes'          => $notes,
            'tags'           => $tags,
        ]);
    }

    public function actionView($id)
    {
        $customer = Customer::findOne($id);
        if (!$customer) {
            throw new \yii\web\NotFoundHttpException('Покупатель не найден');
        }

        // Заказы покупателя с пагинацией (10 per page)
        $ordersQuery = Order::find()
            ->where(['customer_id' => $id])
            ->orWhere(['client_email' => $customer->email])
            ->orderBy(['created_at' => SORT_DESC]);

        $ordersDataProvider = new ActiveDataProvider([
            'query' => $ordersQuery,
            'pagination' => ['pageSize' => 10],
        ]);

        // Все заказы для статистики
        $allOrders = Order::find()
            ->where(['customer_id' => $id])
            ->orWhere(['client_email' => $customer->email])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Статистика заказов
        $orderStats = [
            'count' => count($allOrders),
            'total' => array_sum(array_map(fn($o) => $o->total_amount, $allOrders)),
            'average' => count($allOrders) > 0 ? array_sum(array_map(fn($o) => $o->total_amount, $allOrders)) / count($allOrders) : 0,
            'first_at' => count($allOrders) > 0 ? min(array_map(fn($o) => $o->created_at, $allOrders)) : null,
            'last_at' => count($allOrders) > 0 ? max(array_map(fn($o) => $o->created_at, $allOrders)) : null,
            'orders_total' => array_sum(array_map(fn($o) => $o->total_amount, $allOrders)),
        ];

        // Баллы лояльности
        $loyaltyBalance = LoyaltyPoints::getBalance($id);
        $loyaltyTotalEarned = LoyaltyPoints::getTotalEarned($id);
        $loyaltyHistory = LoyaltyPoints::getHistory($id, 20);

        // Заметки команды (хранятся в отдельной таблице или в JSON поле)
        // Используем таблицу customer_notes если она есть, иначе пустой массив
        $notes = $this->getCustomerNotes($id);

        // Теги покупателя
        $tags = $this->getCustomerTags($customer);

        return $this->render('view', [
            'customer' => $customer,
            'ordersDataProvider' => $ordersDataProvider,
            'allOrders' => $allOrders,
            'orderStats' => $orderStats,
            'loyaltyBalance' => $loyaltyBalance,
            'loyaltyTotalEarned' => $loyaltyTotalEarned,
            'loyaltyHistory' => $loyaltyHistory,
            'notes' => $notes,
            'tags' => $tags,
        ]);
    }

    /**
     * Редактирование покупателя
     */
    public function actionUpdate($id)
    {
        $customer = Customer::findOne($id);
        if (!$customer) {
            throw new \yii\web\NotFoundHttpException('Покупатель не найден');
        }

        $customer->scenario = 'profile';

        if ($customer->load(Yii::$app->request->post()) && $customer->save()) {
            Yii::$app->session->setFlash('success', 'Данные покупателя обновлены');
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('update', [
            'customer' => $customer,
        ]);
    }

    /**
     * Блокировка/разблокировка покупателя
     */
    public function actionToggleStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        if ($customer->status === Customer::STATUS_ACTIVE) {
            $customer->status = Customer::STATUS_INACTIVE;
            $message = 'Покупатель заблокирован';
        } else {
            $customer->status = Customer::STATUS_ACTIVE;
            $message = 'Покупатель разблокирован';
        }

        if ($customer->save(false)) {
            return ['success' => true, 'message' => $message, 'status' => $customer->status];
        }

        return ['success' => false, 'message' => 'Ошибка при изменении статуса'];
    }

    /**
     * Удаление покупателя (soft delete)
     */
    public function actionDelete($id)
    {
        $customer = Customer::findOne($id);
        if (!$customer) {
            throw new \yii\web\NotFoundHttpException('Покупатель не найден');
        }

        $customer->status = Customer::STATUS_INACTIVE;
        $customer->save(false);

        Yii::$app->session->setFlash('success', 'Покупатель удален');
        return $this->redirect(['index']);
    }

    /**
     * Сброс пароля покупателя
     */
    public function actionResetPassword($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        // Генерируем новый пароль
        $newPassword = Yii::$app->security->generateRandomString(8);
        $customer->setPassword($newPassword);
        $customer->generateAuthKey();

        if ($customer->save(false)) {
            return [
                'success' => true,
                'message' => 'Новый пароль: ' . $newPassword,
                'password' => $newPassword,
            ];
        }

        return ['success' => false, 'message' => 'Ошибка при сбросе пароля'];
    }

    /**
     * Экспорт списка покупателей
     */
    public function actionExport()
    {
        $customers = Customer::find()
            ->where(['!=', 'status', Customer::STATUS_INACTIVE])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/csv; charset=utf-8');
        Yii::$app->response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM для Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Заголовки
        fputcsv($output, [
            'ID', 'Email', 'Телефон', 'Имя', 'Фамилия', 'Город',
            'Заказов', 'Потрачено', 'Статус', 'Дата регистрации'
        ], ';');

        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer->id,
                $customer->email,
                $customer->phone,
                $customer->first_name,
                $customer->last_name,
                $customer->default_city,
                $customer->orders_count,
                $customer->total_spent,
                $customer->getStatusLabel(),
                date('d.m.Y H:i', $customer->created_at),
            ], ';');
        }

        fclose($output);
        return Yii::$app->response;
    }

    /**
     * Связать заказы с покупателем
     */
    public function actionLinkOrders($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        // Связываем заказы по email и телефону
        $conditions = ['or'];
        if ($customer->email) {
            $conditions[] = ['client_email' => $customer->email];
        }
        if ($customer->phone) {
            $conditions[] = ['client_phone' => $customer->phone];
        }

        if (count($conditions) > 1) {
            $count = Order::updateAll(
                ['customer_id' => $customer->id],
                ['and', ['customer_id' => null], $conditions]
            );

            // Обновляем статистику
            $customer->updateOrderStats();

            return [
                'success' => true,
                'message' => "Связано заказов: {$count}",
                'linked' => $count,
            ];
        }

        return ['success' => false, 'message' => 'Нет данных для связывания'];
    }

    /**
     * Добавить заметку команды к покупателю
     */
    public function actionAddNote($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        $text = trim(Yii::$app->request->post('text', ''));
        if (empty($text)) {
            return ['success' => false, 'message' => 'Текст заметки не может быть пустым'];
        }

        $db = Yii::$app->db;
        $authorId = Yii::$app->user->id;
        $authorName = Yii::$app->user->identity->username ?? 'Admin';

        // Проверяем, существует ли таблица customer_notes
        try {
            $db->createCommand()->insert('{{%customer_notes}}', [
                'customer_id' => $id,
                'author_id' => $authorId,
                'author_name' => $authorName,
                'text' => $text,
                'created_at' => time(),
            ])->execute();

            $noteId = $db->getLastInsertID();

            return [
                'success' => true,
                'message' => 'Заметка добавлена',
                'note' => [
                    'id' => $noteId,
                    'author_name' => $authorName,
                    'text' => $text,
                    'created_at' => date('d.m.Y H:i'),
                ],
            ];
        } catch (\Exception $e) {
            Yii::error('CustomerController::actionAddNote error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Ошибка при сохранении заметки: ' . $e->getMessage()];
        }
    }

    /**
     * Начислить баллы лояльности покупателю
     */
    public function actionAddPoints($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        $points = (int)Yii::$app->request->post('points', 0);
        $comment = trim(Yii::$app->request->post('comment', ''));

        if ($points <= 0) {
            return ['success' => false, 'message' => 'Количество баллов должно быть больше 0'];
        }

        if (empty($comment)) {
            return ['success' => false, 'message' => 'Комментарий обязателен'];
        }

        $success = LoyaltyPoints::earn(
            $id,
            $points,
            LoyaltyPoints::TYPE_ADMIN,
            null,
            $comment
        );

        if ($success) {
            $newBalance = LoyaltyPoints::getBalance($id);
            return [
                'success' => true,
                'message' => "Начислено {$points} баллов",
                'new_balance' => $newBalance,
            ];
        }

        return ['success' => false, 'message' => 'Ошибка при начислении баллов'];
    }

    /**
     * Списать баллы лояльности у покупателя
     */
    public function actionDeductPoints($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        $points = (int)Yii::$app->request->post('points', 0);
        $comment = trim(Yii::$app->request->post('comment', ''));

        if ($points <= 0) {
            return ['success' => false, 'message' => 'Количество баллов должно быть больше 0'];
        }

        if (empty($comment)) {
            return ['success' => false, 'message' => 'Комментарий обязателен'];
        }

        $balance = LoyaltyPoints::getBalance($id);
        if ($balance < $points) {
            return ['success' => false, 'message' => "Недостаточно баллов. Текущий баланс: {$balance}"];
        }

        $success = LoyaltyPoints::redeem($id, $points, null, $comment);

        if ($success) {
            $newBalance = LoyaltyPoints::getBalance($id);
            return [
                'success' => true,
                'message' => "Списано {$points} баллов",
                'new_balance' => $newBalance,
            ];
        }

        return ['success' => false, 'message' => 'Ошибка при списании баллов'];
    }

    /**
     * Обновить теги покупателя
     */
    public function actionUpdateTags($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $customer = Customer::findOne($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }

        $tags = Yii::$app->request->post('tags', []);
        if (!is_array($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        // Очищаем теги от опасных символов
        $tags = array_map(function($tag) {
            return preg_replace('/[^а-яёА-ЯЁa-zA-Z0-9\s\-_]/u', '', $tag);
        }, $tags);
        $tags = array_filter(array_unique($tags));

        try {
            $db = Yii::$app->db;
            // Удаляем старые теги
            $db->createCommand()->delete('{{%customer_tags}}', ['customer_id' => $id])->execute();

            // Добавляем новые
            foreach ($tags as $tag) {
                if (!empty(trim($tag))) {
                    $db->createCommand()->insert('{{%customer_tags}}', [
                        'customer_id' => $id,
                        'tag' => trim($tag),
                        'created_at' => time(),
                    ])->execute();
                }
            }

            return [
                'success' => true,
                'message' => 'Теги обновлены',
                'tags' => array_values($tags),
            ];
        } catch (\Exception $e) {
            Yii::error('CustomerController::actionUpdateTags error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Ошибка при обновлении тегов: ' . $e->getMessage()];
        }
    }

    /**
     * Начислить или списать баллы (универсальный endpoint для JS-формы)
     * Body JSON: { customer_id, points (>0 начислить, <0 списать), comment }
     */
    public function actionAdjustPoints()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $raw        = Yii::$app->request->getRawBody();
        $body       = json_decode($raw, true) ?? Yii::$app->request->post();
        $customerId = (int)($body['customer_id'] ?? 0);
        $points     = (int)($body['points'] ?? 0);
        $comment    = trim($body['comment'] ?? '');

        if (!$customerId || !Customer::findOne($customerId)) {
            return ['success' => false, 'message' => 'Покупатель не найден'];
        }
        if ($points === 0) {
            return ['success' => false, 'message' => 'Количество баллов не может быть 0'];
        }
        if (empty($comment)) {
            return ['success' => false, 'message' => 'Комментарий обязателен'];
        }

        if ($points > 0) {
            $ok = LoyaltyPoints::earn($customerId, $points, LoyaltyPoints::TYPE_ADMIN, null, $comment);
        } else {
            $ok = LoyaltyPoints::redeem($customerId, abs($points), null, $comment);
        }

        if ($ok) {
            return [
                'success'     => true,
                'message'     => $points > 0 ? "Начислено {$points} баллов" : 'Списано ' . abs($points) . ' баллов',
                'new_balance' => LoyaltyPoints::getBalance($customerId),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка при изменении баллов'];
    }

    /**
     * Создать профиль клиента из гостевого заказа
     * Body JSON: { order_id }
     */
    public function actionCreateFromOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw  = Yii::$app->request->getRawBody();
        $body = json_decode($raw, true) ?? [];
        $orderId = (int)($body['order_id'] ?? 0);

        if (!$orderId) {
            return ['success' => false, 'message' => 'Заказ не указан'];
        }

        try {
            $order = \app\backend\modules\checkout\models\Order::findOne($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Заказ не найден'];
            }
            if ($order->customer_id) {
                return ['success' => false, 'message' => 'Заказ уже привязан к клиенту'];
            }

            // Find existing customer by email or phone
            $existing = null;
            if ($order->client_email) {
                $existing = Customer::find()->where(['email' => $order->client_email])->one();
            }
            if (!$existing && $order->client_phone) {
                $existing = Customer::find()->where(['phone' => $order->client_phone])->one();
            }

            if ($existing) {
                // Link existing customer to order
                $order->customer_id = $existing->id;
                $order->save(false);
                return [
                    'success'      => true,
                    'message'      => 'Заказ привязан к существующему клиенту',
                    'customer_url' => \yii\helpers\Url::to(['/admin/customer/view', 'id' => $existing->id]),
                ];
            }

            // Create new customer
            $customer = new Customer();
            $customer->email      = $order->client_email ?: null;
            $customer->phone      = $order->client_phone ?: null;
            $customer->first_name = $order->client_name  ?: null;
            $customer->created_at = time();
            $customer->updated_at = time();
            $customer->password_hash = '!';
            $customer->status        = 1;
            $customer->is_active     = 1;
            $customer->auth_key      = Yii::$app->security->generateRandomString();
            if ($customer->save(false)) {
                $order->customer_id = $customer->id;
                $order->save(false);
                return [
                    'success'      => true,
                    'message'      => 'Профиль клиента создан',
                    'customer_url' => \yii\helpers\Url::to(['/admin/customer/view', 'id' => $customer->id]),
                ];
            }
            return ['success' => false, 'message' => 'Не удалось создать профиль'];
        } catch (\Exception $e) {
            Yii::error('createFromOrder error: ' . $e->getMessage(), 'customer');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Быстрый поиск клиентов для автодополнения (используется в форме создания заказа)
     * GET /admin/customer/search?q=текст
     */
    public function actionSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim(Yii::$app->request->get('q', ''));
        if (strlen($q) < 2) {
            return [];
        }
        $like = '%' . strtr($q, ['%' => '\%', '_' => '\_']) . '%';
        $customers = Customer::find()
            ->where(['or',
                ['like', 'email', $q],
                ['like', 'phone', $q],
                ['like', 'first_name', $q],
                ['like', 'last_name', $q],
            ])
            ->limit(10)
            ->asArray()
            ->all();

        return array_map(function($c) {
            $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            return [
                'id'       => $c['id'],
                'name'     => $name ?: ('Клиент #' . $c['id']),
                'phone'    => $c['phone']    ?? '',
                'email'    => $c['email']    ?? '',
                'city'     => $c['default_city']    ?? '',
                'address'  => $c['default_address'] ?? '',
            ];
        }, $customers);
    }

    /**
     * Получить заметки команды для покупателя
     */
    private function getCustomerNotes(int $customerId): array
    {
        try {
            return Yii::$app->db->createCommand(
                'SELECT * FROM {{%customer_notes}} WHERE customer_id = :id ORDER BY created_at DESC LIMIT 50',
                [':id' => $customerId]
            )->queryAll();
        } catch (\Exception $e) {
            Yii::warning('getCustomerNotes failed: ' . $e->getMessage(), 'customer');
            return [];
        }
    }

    /**
     * Получить теги покупателя
     */
    private function getCustomerTags(Customer $customer): array
    {
        try {
            $rows = Yii::$app->db->createCommand(
                'SELECT tag FROM {{%customer_tags}} WHERE customer_id = :id ORDER BY created_at ASC',
                [':id' => $customer->id]
            )->queryColumn();
            return $rows ?: [];
        } catch (\Exception $e) {
            Yii::warning('getCustomerTags failed: ' . $e->getMessage(), 'customer');
            return [];
        }
    }
    /**
     * A12: Mark auto-generated import phantom accounts as inactive.
     * Targets: email matching ms_* AND no orders.
     */
    public function actionMarkPhantoms()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        $count = Yii::$app->db->createCommand("
            UPDATE {{%customer}}
            SET is_active = 0
            WHERE email REGEXP '^ms_[a-f0-9]+@'
              AND (last_order_at IS NULL OR orders_count = 0)
        ")->execute();

        return [
            'success' => true,
            'marked' => $count,
            'message' => "Деактивировано {$count} фантомных аккаунтов",
        ];
    }

}
