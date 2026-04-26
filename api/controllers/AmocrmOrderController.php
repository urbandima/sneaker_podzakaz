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
use app\backend\modules\admin\services\OrderFromLeadService;

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
     * Two modes:
     *   1. {lead_id: 123}                 → fetch lead from AmoCRM API and create order
     *   2. {name, phone, email, ...}       → create order from provided data (legacy widget mode)
     */
    public function actionCreateOrder()
    {
        $body = json_decode(Yii::$app->request->rawBody, true) ?: [];

        // Mode 1: fetch from AmoCRM by lead_id
        $leadId = (int)($body['lead_id'] ?? 0);
        if ($leadId) {
            try {
                $service = new OrderFromLeadService();
                $order   = $service->createFromLeadId($leadId);
                $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]);
                return [
                    'success'      => true,
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'order_url'    => $adminUrl,
                    'admin_url'    => $adminUrl,
                ];
            } catch (\Throwable $e) {
                Yii::error('AmoCRM create-order from lead failed: ' . $e->getMessage(), 'amocrm-widget');
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // Mode 2: legacy widget — create from provided data
        $name        = trim($body['name']         ?? '');
        $phone       = trim($body['phone']        ?? '');
        $email       = trim($body['email']        ?? '');
        $productName = trim($body['product_name'] ?? '');
        $size        = trim($body['size']         ?? '');
        $price       = (float)($body['price']     ?? 0);
        $notes       = trim($body['notes']        ?? '');
        $dealId      = trim($body['deal_id']      ?? '');
        $dealLink    = trim($body['deal_link']     ?? '');

        if (!$name && !$phone) {
            return ['success' => false, 'message' => 'Укажите lead_id или имя/телефон клиента'];
        }

        $order = new Order();
        $order->client_name    = $name;
        $order->client_phone   = $phone;
        $order->client_email   = $email ?: null;
        $order->total_amount   = $price;
        $order->status         = 'new';
        $order->source         = 'amoCRM';
        $order->comment        = $notes ?: null;
        $order->amocrm_deal_id = $dealId ? (int)$dealId : null;
        $order->amocrm_lead_id = $dealId ? (int)$dealId : null;
        $order->ms_deal_link   = $dealLink ?: null;
        $order->amocrm_source  = 'widget';

        if (!$order->save()) {
            Yii::error('AmoCRM order save failed: ' . json_encode($order->errors), 'amocrm-widget');
            return ['success' => false, 'message' => 'Ошибка создания заказа', 'errors' => $order->errors];
        }

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
            'order_url'    => $adminUrl,
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
