<?php

/**
 * AmocrmController — единая точка входа API для интеграции с AmoCRM
 *
 * ИСТОРИЯ: до слияния (см. AUDIT-343) этот функционал был продублирован
 * в двух контроллерах — AmocrmController и AmocrmOrderController. Оба
 * реально были смонтированы в infrastructure/config/web.php, но на РАЗНЫЕ
 * действия: create-order/products маршрутизировались на AmocrmOrderController,
 * а order/sync — на AmocrmController. Дублирующиеся (недостижимые) реализации
 * create-order/products из старого AmocrmController удалены; актуальные версии
 * (из AmocrmOrderController, которые реально используют оба живых клиента —
 * frontend/web/amocrm-widget/widget.js и упакованный виджет outputs/amocrm-widget/)
 * перенесены сюда без изменений логики.
 *
 * ENDPOINTS:
 * - POST /api/amocrm/create-order — создание заказа из виджета AmoCRM.
 *     Два режима:
 *       1) { "lead_id": 123 } — подтянуть сделку из AmoCRM API и создать заказ
 *          (использует упакованный виджет outputs/amocrm-widget/script.js)
 *       2) { "name", "phone", "email", "product_name", "size", "price",
 *            "notes", "deal_id", "deal_link" } — создать заказ из данных,
 *          переданных формой (использует frontend/web/amocrm-widget/widget.js)
 * - GET  /api/amocrm/products     — автокомплит товаров (поиск по названию/SKU)
 * - GET  /api/amocrm/order        — найти заказ по external_id (amocrm_lead_id)
 * - POST /api/amocrm/sync         — создать/обновить заказ из лида AmoCRM
 *
 * АУТЕНТИФИКАЦИЯ:
 * Все запросы проверяются по заголовку X-Api-Key (либо Authorization: Bearer).
 * Ключ хранится в настройках: Yii::$app->settings->get('amocrm', 'widget_api_key')
 * с фолбэком на Yii::$app->settings->get('amocrm', 'api_key').
 */
namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\admin\services\OrderFromLeadService;

class AmocrmController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Проверяем API-ключ перед каждым действием, настраиваем CORS для виджета.
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // CORS headers для виджета AmoCRM
        $headers = Yii::$app->response->headers;
        $origin = Yii::$app->request->headers->get('Origin', '');
        if ($origin && preg_match('/\.amocrm\.(ru|com)$/i', parse_url($origin, PHP_URL_HOST) ?? '')) {
            $headers->set('Access-Control-Allow-Origin', $origin);
        }
        $headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Api-Key, Authorization, ngrok-skip-browser-warning');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $headers->set('Access-Control-Allow-Private-Network', 'true');

        // OPTIONS preflight
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 204;
            Yii::$app->end();
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        // Accept X-Api-Key header OR Authorization: Bearer <key>
        $apiKey = Yii::$app->request->headers->get('X-Api-Key', '');
        if ($apiKey === '') {
            $auth = Yii::$app->request->headers->get('Authorization', '');
            if (strncasecmp($auth, 'Bearer ', 7) === 0) {
                $apiKey = substr($auth, 7);
            }
        }

        $storedKey = Yii::$app->settings->get('amocrm', 'widget_api_key', '')
            ?: Yii::$app->settings->get('amocrm', 'api_key', '');

        if (empty($storedKey) || !hash_equals($storedKey, $apiKey)) {
            Yii::warning('[AmoCRM API] Неверный API-ключ. IP: ' . Yii::$app->request->userIP, 'amocrm');
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = ['success' => false, 'message' => 'Invalid API key'];
            Yii::$app->response->send();
            Yii::$app->end();
        }

        // Логируем запрос
        Yii::info(
            sprintf('[AmoCRM API] %s %s | IP: %s', Yii::$app->request->method, Yii::$app->request->url, Yii::$app->request->userIP),
            'amocrm'
        );

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
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $body = json_decode(Yii::$app->request->rawBody, true) ?: [];

        // Mode 1: fetch from AmoCRM by lead_id
        $leadId = (int)($body['lead_id'] ?? 0);
        if ($leadId) {
            try {
                $service  = new OrderFromLeadService();
                $order    = $service->createFromLeadId($leadId);
                $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]);
                return [
                    'success'      => true,
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'order_url'    => $adminUrl,
                    'admin_url'    => $adminUrl,
                ];
            } catch (\Throwable $e) {
                Yii::error('[AmoCRM API] create-order from lead failed: ' . $e->getMessage(), 'amocrm');
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
            Yii::error('[AmoCRM API] order save failed: ' . json_encode($order->errors), 'amocrm');
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
        Yii::info("[AmoCRM API] widget order #{$order->order_number} created (deal_id={$dealId})", 'amocrm');

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
     * Возвращает список товаров для автодополнения в виджете (с размерами).
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

    /**
     * GET /api/amocrm/order?external_id=<lead_id>
     *
     * Виджет вызывает этот endpoint при открытии карточки сделки.
     * Ищет заказ по amocrm_lead_id и возвращает его данные.
     *
     * @return array
     */
    public function actionOrder()
    {
        $leadId = (int)Yii::$app->request->get('external_id', 0);
        if (!$leadId) {
            return ['found' => false, 'error' => 'external_id required'];
        }

        $order = Order::findOne(['amocrm_lead_id' => $leadId]);
        if (!$order) {
            return ['found' => false];
        }

        $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]);

        return [
            'found'          => true,
            'order_id'       => $order->id,
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'total'          => (float)$order->total_amount,
            'recipient_name' => $order->client_name,
            'phone'          => $order->client_phone,
            'admin_url'      => $adminUrl,
        ];
    }

    /**
     * POST /api/amocrm/sync
     *
     * Виджет вызывает при нажатии кнопки "Синхронизировать".
     * Если заказ уже есть — обновляет данные из AmoCRM API.
     * Если нет — создаёт новый заказ.
     *
     * Body: { "lead_id": 123 }
     *
     * @return array
     */
    public function actionSync()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $body   = json_decode(Yii::$app->request->rawBody, true) ?: [];
        $leadId = (int)($body['lead_id'] ?? 0);

        if (!$leadId) {
            return ['success' => false, 'message' => 'lead_id required'];
        }

        try {
            $service  = new OrderFromLeadService();
            $existing = Order::findOne(['amocrm_lead_id' => $leadId]);

            if ($existing) {
                // Fetch fresh data from AmoCRM and update
                $lead = Yii::$app->amocrm->getLead($leadId, ['contacts', 'custom_fields_values']);
                if ($lead) {
                    $service->updateOrderFromLead($existing, $lead);
                    $existing->amocrm_last_sync_at = time();
                    $existing->save(false);
                }
                $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $existing->id]);
                return [
                    'success'      => true,
                    'action'       => 'updated',
                    'order_id'     => $existing->id,
                    'order_number' => $existing->order_number,
                    'admin_url'    => $adminUrl,
                ];
            }

            // No order yet — create from lead
            $order    = $service->createFromLeadId($leadId);
            $adminUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]);
            return [
                'success'      => true,
                'action'       => 'created',
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'admin_url'    => $adminUrl,
            ];
        } catch (\Throwable $e) {
            Yii::error('[AmoCRM API] sync error for lead #' . $leadId . ': ' . $e->getMessage(), 'amocrm');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
