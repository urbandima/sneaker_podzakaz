<?php

namespace app\frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\common\models\Feedback;

class FeedbackController extends Controller
{
    public $layout = 'main';

    /**
     * Feedback form page
     */
    public function actionIndex()
    {
        $customer = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;

        return $this->render('index', [
            'customer' => $customer,
        ]);
    }

    /**
     * Submit feedback (AJAX POST)
     */
    public function actionSubmit()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        if (!$request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $rating  = (int)$request->post('rating', 5);
        $message = trim((string)$request->post('message', ''));
        $name    = trim((string)$request->post('name', ''));
        $email   = trim((string)$request->post('email', ''));

        if (empty($message)) {
            return ['success' => false, 'message' => 'Пожалуйста, напишите сообщение'];
        }
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $customer = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;

        $customerName = $name;
        $customerEmail = $email;
        if ($customer) {
            if (method_exists($customer, 'getFullName')) {
                $fullName = (string) $customer->getFullName();
                if ($fullName !== '') {
                    $customerName = $fullName;
                }
            } elseif (!empty($customer->name)) {
                $customerName = (string) $customer->name;
            } elseif (!empty($customer->username)) {
                $customerName = (string) $customer->username;
            }
            if (!empty($customer->email)) {
                $customerEmail = (string) $customer->email;
            }
        }

        $fb = new Feedback();
        $fb->rating         = $rating;
        $fb->message        = $message;
        $fb->customer_id    = $customer?->id;
        $fb->customer_name  = $customerName;
        $fb->customer_email = $customerEmail;

        if ($fb->save()) {
            return ['success' => true, 'message' => 'Спасибо! Ваш отзыв передан директору.'];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения. Попробуйте позже.'];
    }
}
