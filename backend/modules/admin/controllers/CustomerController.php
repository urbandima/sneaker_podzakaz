<?php

/**
 * CustomerController — Управление покупателями в админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Просмотр и управление зарегистрированными покупателями,
 * их заказами и историей.
 * 
 * ФУНКЦИИ:
 * - Список покупателей с поиском и фильтрами (index)
 * - Просмотр профиля покупателя (view)
 * - Редактирование данных покупателя (update)
 * - Блокировка/разблокировка покупателя (toggle-status)
 * - История заказов покупателя (orders)
 * - Отправка уведомления покупателю (notify)
 * 
 * СВЯЗИ:
 * - Customer (модель покупателя)
 * - Order (модель заказа)
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
            $query->andWhere(['status' => $status]);
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

        // Заказы покупателя
        $orders = Order::find()
            ->where(['customer_id' => $id])
            ->orWhere(['client_email' => $customer->email])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('view', [
            'customer' => $customer,
            'orders' => $orders,
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

        $customer->status = Customer::STATUS_DELETED;
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
            // Здесь можно отправить email с новым паролем
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
            ->where(['!=', 'status', Customer::STATUS_DELETED])
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
}
