<?php

namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\ContentNegotiator;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;

/**
 * AmoCRM Widget API — приём заказов из виджета AmoCRM
 *
 * POST /api/amocrm/create-order — создать заказ
 * GET  /api/amocrm/products     — список товаров для автодополнения
 *
 * Авторизация: заголовок X-Api-Key
 */
class AmocrmOrderController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $key = Yii::$app->request->headers->get('X-Api-Key');
        $stored = Yii::$app->settings->get('amocrm', 'widget_api_key', '');

        if (empty($stored) || $key !== $stored) {
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = ['success' => false, 'message' => 'Неверный API ключ'];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        return true;
    }

    /**
     * POST /api/amocrm/create-order
     *
     * Body JSON: {name, phone, email, product_name, size, price, notes,
     *             source, deal_id, deal_link}
     */
    public function actionCreateOrder()
    {
        $body = json_decode(Yii::$app->request->rawBody, true) ?: [];

        $name        = trim($body['name']         ?? '');
        $phone       = trim($body['phone']        ?? '');
        $email       = trim($body['email']        ?? '');
        $productName = trim($body['product_name'] ?? '');
        $size        = trim($body['size']         ?? '');
        $price       = (float)($body['price']     ?? 0);
        $notes       = trim($body['notes']        ?? '');
        $dealId      = trim($body['deal_id']      ?? '');
        $dealLink    = trim($body['deal_link']     ?? '');

        if (!$name || !$phone) {
            return ['success' => false, 'message' => 'Имя и телефон обязательны'];
        }

        $order = new Order();
        $order->client_name    = $name;
        $order->client_phone   = $phone;
        $order->client_email   = $email ?: null;
        $order->total_amount   = $price;
        $order->product_price  = $price;
        $order->status         = 'new';
        $order->source         = 'amoCRM';
        $order->comment        = $notes ?: null;
        $order->amocrm_deal_id = $dealId ?: null;
        $order->ms_deal_link   = $dealLink ?: null;
        $order->amocrm_source  = 'widget';

        if (!empty($email)) {
            $order->client_email = $email;
        }

        if (!$order->save()) {
            Yii::error('AmoCRM order save failed: ' . json_encode($order->errors), 'amocrm-widget');
            return ['success' => false, 'message' => 'Ошибка создания заказа', 'errors' => $order->errors];
        }

        // Создаём позицию заказа если указан товар
        if ($productName) {
            $item = new OrderItem();
            $item->order_id     = $order->id;
            $item->product_name = $productName;
            $item->size         = $size ?: null;
            $item->price        = $price;
            $item->quantity     = 1;
            $item->save(false);
        }

        $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]);

        Yii::info("AmoCRM widget order #{$order->order_number} created (deal_id={$dealId})", 'amocrm-widget');

        return [
            'success'      => true,
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'admin_url'    => $adminUrl,
        ];
    }

    /**
     * GET /api/amocrm/products?q=nike
     *
     * Возвращает список товаров для автодополнения в виджете.
     */
    public function actionProducts()
    {
        $q = trim(Yii::$app->request->get('q', ''));

        $query = Product::find()
            ->where(['is_active' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->limit(30);

        if ($q !== '') {
            $query->andWhere(['or',
                ['like', 'name', $q],
                ['like', 'sku', $q],
            ]);
        }

        $products = $query->all();

        $result = [];
        foreach ($products as $p) {
            $sizes = [];
            foreach ($p->sizes as $s) {
                $sizes[] = [
                    'size'  => $s->size,
                    'price' => (float)($s->price ?: $p->price),
                ];
            }

            $result[] = [
                'id'      => $p->id,
                'name'    => $p->name,
                'article' => $p->sku ?? '',
                'price'   => (float)$p->price,
                'sizes'   => $sizes,
            ];
        }

        return $result;
    }
}
