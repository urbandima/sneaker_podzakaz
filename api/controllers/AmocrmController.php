<?php

/**
 * AmocrmController — API-эндпоинты для интеграции с AmoCRM
 *
 * ENDPOINTS:
 * - POST /api/amocrm/create-order — Создание заказа из виджета AmoCRM
 * - GET  /api/amocrm/products     — Автокомплит товаров (поиск по названию)
 *
 * АУТЕНТИФИКАЦИЯ:
 * Все запросы проверяются по заголовку X-Api-Key.
 * Ключ хранится в настройках: Yii::$app->settings->get('amocrm', 'api_key')
 */
namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\admin\services\OrderFromLeadService;

class AmocrmController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Проверяем API-ключ перед каждым действием
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
        $headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Api-Key, Authorization');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');

        // OPTIONS preflight
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 204;
            Yii::$app->end();
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
            throw new UnauthorizedHttpException('Invalid API key');
        }

        // Логируем запрос
        Yii::info(
            sprintf('[AmoCRM API] %s %s | IP: %s', Yii::$app->request->method, Yii::$app->request->url, Yii::$app->request->userIP),
            'amocrm'
        );

        return parent::beforeAction($action);
    }

    /**
     * POST /api/amocrm/create-order
     *
     * Принимает JSON:
     * {
     *   "name": "Иван Иванов",
     *   "phone": "+375291234567",
     *   "email": "ivan@example.com",      // необязательно
     *   "product": "Nike Dunk Low",        // необязательно
     *   "size": "42",                      // необязательно
     *   "price": 350.00,                   // необязательно
     *   "deal_id": 12345,                  // ID сделки в AmoCRM
     *   "deal_link": "https://...",        // ссылка на сделку
     *   "notes": "Доп. примечание"         // необязательно
     * }
     *
     * @return array
     */
    public function actionCreateOrder()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $name  = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (empty($name) && empty($phone)) {
            return [
                'success' => false,
                'message' => 'Укажите имя или телефон клиента',
            ];
        }

        try {
            $order = new Order();
            $order->client_name  = $name;
            $order->client_phone = $phone;
            $order->client_email = trim($data['email'] ?? '');
            $order->source       = 'amoCRM';
            $order->source_id    = !empty($data['deal_id']) ? (int)$data['deal_id'] : null;
            $order->comment      = $this->buildComment($data);
            $order->total_amount = !empty($data['price']) ? (float)$data['price'] : 0;
            $order->status       = 'new';

            if (!$order->save()) {
                Yii::error('[AmoCRM API] Ошибка сохранения заказа: ' . json_encode($order->errors), 'amocrm');
                return [
                    'success' => false,
                    'message' => 'Ошибка создания заказа',
                    'errors'  => $order->errors,
                ];
            }

            Yii::info('[AmoCRM API] Создан заказ #' . $order->id . ' (deal_id: ' . ($data['deal_id'] ?? '-') . ')', 'amocrm');

            return [
                'success'      => true,
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'message'      => 'Заказ #' . $order->order_number . ' создан',
            ];
        } catch (\Exception $e) {
            Yii::error('[AmoCRM API] Exception: ' . $e->getMessage(), 'amocrm');
            return [
                'success' => false,
                'message' => 'Внутренняя ошибка сервера',
            ];
        }
    }

    /**
     * GET /api/amocrm/products?q=search
     *
     * Автокомплит товаров для виджета. Возвращает до 20 товаров.
     *
     * @return array
     */
    public function actionProducts()
    {
        $q = trim(Yii::$app->request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return ['results' => []];
        }

        $products = Product::find()
            ->select(['id', 'name', 'price', 'main_image', 'brand_name', 'sku'])
            ->where(['is_active' => 1])
            ->andWhere(['or',
                ['like', 'name', $q],
                ['like', 'sku', $q],
                ['like', 'brand_name', $q],
                ['like', 'vendor_code', $q],
            ])
            ->orderBy(['name' => SORT_ASC])
            ->limit(20)
            ->asArray()
            ->all();

        $results = [];
        foreach ($products as $p) {
            $results[] = [
                'id'    => $p['id'],
                'name'  => $p['name'],
                'price' => (float)$p['price'],
                'sku'   => $p['sku'] ?? '',
                'brand' => $p['brand_name'] ?? '',
                'image' => $p['main_image'] ?? '',
            ];
        }

        return ['results' => $results];
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

    /**
     * Формирует комментарий к заказу из данных AmoCRM
     */
    private function buildComment(array $data): string
    {
        $parts = ['Источник: AmoCRM'];

        if (!empty($data['product'])) {
            $parts[] = 'Товар: ' . $data['product'];
        }
        if (!empty($data['size'])) {
            $parts[] = 'Размер: ' . $data['size'];
        }
        if (!empty($data['deal_id'])) {
            $parts[] = 'Сделка AmoCRM: #' . $data['deal_id'];
        }
        if (!empty($data['deal_link'])) {
            $parts[] = 'Ссылка: ' . $data['deal_link'];
        }
        if (!empty($data['notes'])) {
            $parts[] = 'Примечание: ' . $data['notes'];
        }

        return implode("\n", $parts);
    }
}
