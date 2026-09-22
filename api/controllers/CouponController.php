<?php

/**
 * CouponController — публичная валидация купона (CMP-423)
 *
 * НАЗНАЧЕНИЕ:
 * Предпросмотр купона на странице корзины/оформления заказа, до отправки
 * заказа. Реальное применение купона к заказу (списание лимита использований,
 * пересчёт итоговой суммы) происходит только в OrderController::actionCreate()
 * — этот контроллер ничего не пишет в БД, только читает.
 *
 * Сумма заказа для проверки берётся из реальной корзины на сервере
 * (Cart::getTotal()), а не из тела запроса — иначе предпросмотр скидки можно
 * подделать до оформления заказа.
 */

namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\backend\modules\coupon\services\CouponService;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\account\models\Customer;

class CouponController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['validate' => ['POST']],
            ],
        ];
    }

    public function actionValidate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Фронтенд шлёт application/json — без явного JSON-парсера в
        // request.parsers getBodyParams() для него возвращает [] (тот же
        // паттерн уже используют WebhookController/AmocrmController).
        $body = Yii::$app->request->getBodyParams();
        if (empty($body)) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        $code = trim((string) ($body['code'] ?? ''));

        if ($code === '') {
            return ['success' => false, 'message' => 'Введите промокод'];
        }

        $orderAmount = Cart::getTotal();
        if ($orderAmount <= 0) {
            return ['success' => false, 'message' => 'Корзина пуста'];
        }

        $customerId = Customer::getCurrentCustomerId();

        $couponService = new CouponService();
        $coupon = $couponService->validateCoupon($code, $orderAmount, $customerId);

        if (!$coupon) {
            return ['success' => false, 'message' => $couponService->getErrorMessage() ?: 'Промокод недействителен'];
        }

        // Доставка ещё не выбрана на этом шаге — предпросмотр скидки для
        // free_shipping считается без неё; реальная сумма (с доставкой)
        // пересчитывается на сервере при оформлении заказа.
        $discount = $coupon->calculateDiscount($orderAmount, 0);

        return [
            'success' => true,
            'message' => 'Промокод применён',
            'discount' => $discount,
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'description' => $coupon->getDiscountDescription(),
                'discount' => $discount,
            ],
        ];
    }
}
