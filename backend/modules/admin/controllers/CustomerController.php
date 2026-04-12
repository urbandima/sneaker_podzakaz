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
    /**
     * Список покупателей
     */
    public function actionIndex()
    {
        $query = Customer::find();

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

        // Сортировка
        $sort = Yii::$app->request->get('sort', 'created_at');
        $order = Yii::$app->request->get('order', 'desc');
        $query->orderBy([$sort => $order === 'asc' ? SORT_ASC : SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Статистика
        $stats = [
            'total' => Customer::find()->count(),
            'active' => Customer::find()->where(['status' => Customer::STATUS_ACTIVE])->count(),
            'inactive' => Customer::find()->where(['status' => Customer::STATUS_INACTIVE])->count(),
            'new_today' => Customer::find()->where(['>=', 'created_at', strtotime('today')])->count(),
            'new_week' => Customer::find()->where(['>=', 'created_at', strtotime('-7 days')])->count(),
        ];

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    /**
     * Просмотр покупателя
     */
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
}
