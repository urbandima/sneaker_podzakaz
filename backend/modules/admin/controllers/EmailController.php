<?php
/**
 * EmailController — Email уведомления
 *
 * B19.1: Транзакционные письма клиентам
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class EmailController extends BaseAdminController
{
    /**
     * Отправить email уведомление
     */
    public function actionSend($orderId, $template)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $templates = [
            'confirmed' => 'order_confirmed',
            'paid' => 'order_paid',
            'shipped' => 'order_shipped',
            'delivered' => 'order_delivered',
            'completed' => 'order_completed',
        ];

        if (!isset($templates[$template])) {
            return ['success' => false, 'message' => 'Шаблон не найден'];
        }

        try {
            $sent = $this->sendEmail($order, $templates[$template]);

            if ($sent) {
                return ['success' => true, 'message' => 'Письмо отправлено'];
            }

            return ['success' => false, 'message' => 'Ошибка отправки'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Тест отправки
     */
    public function actionTest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $email = Yii::$app->request->post('email');
        $template = Yii::$app->request->post('template');

        if (empty($email)) {
            return ['success' => false, 'message' => 'Email не указан'];
        }

        try {
            Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->settings->getCompany()['email'] => 'СНИКЕРХЭД'])
                ->setSubject('Тестовое письмо')
                ->setTextBody('Это тестовое письмо из админ-панели.')
                ->send();

            return ['success' => true, 'message' => 'Тестовое письмо отправлено'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Страница настроек шаблонов
     */
    public function actionTemplates()
    {
        $templates = [
            'order_confirmed' => 'Заказ подтвержден',
            'order_paid' => 'Оплата получена',
            'order_shipped' => 'Заказ отправлен',
            'order_delivered' => 'Заказ доставлен',
            'order_completed' => 'Заказ завершен',
        ];

        return $this->render('templates', [
            'templates' => $templates,
        ]);
    }

    /**
     * Отправка email
     */
    private function sendEmail($order, $template)
    {
        $company = Yii::$app->settings->getCompany();

        return Yii::$app->mailer->compose($template, [
            'order' => $order,
            'company' => $company,
        ])
        ->setTo($order->client_email)
        ->setFrom([$company['email'] => $company['name']])
        ->setSubject('Статус заказа #' . $order->order_number)
        ->send();
    }
}
