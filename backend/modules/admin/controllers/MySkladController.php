<?php
/**
 * MySkladController — Интеграция с МойСклад
 *
 * B12.1: Синхронизация остатков
 * B12.2: Автоматическое резервирование
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\checkout\models\Order;

class MySkladController extends BaseAdminController
{
    private $apiUrl = 'https://api.moysklad.ru/api/remap/1.2';

    /**
     * Синхронизация остатков с МойСклад
     */
    public function actionSyncStock()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = Yii::$app->settings->get('moysklad', 'api_token', '');
        if (empty($token)) {
            return ['success' => false, 'message' => 'API токен не настроен'];
        }

        try {
            $stockData = $this->fetchStockFromMoysklad($token);
            $updated = 0;

            foreach ($stockData as $item) {
                $product = Product::find()
                    ->where(['vendor_code' => $item['code']])
                    ->orWhere(['sku' => $item['code']])
                    ->one();

                if ($product) {
                    $product->stock_quantity = $item['quantity'];
                    $product->stock_status = $item['quantity'] > 0 ? 'in_stock' : 'out_of_stock';
                    $product->save(false);
                    $updated++;
                }
            }

            Yii::info("MySklad sync: {$updated} products updated", 'admin');

            return [
                'success' => true,
                'message' => "Обновлено {$updated} товаров",
                'updated' => $updated,
            ];

        } catch (\Exception $e) {
            Yii::error('MySklad sync error: ' . $e->getMessage(), 'admin');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Автоматическое резервирование товара при заказе
     */
    public function actionReserve($orderId)
    {
        $order = Order::findOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $token = Yii::$app->settings->get('moysklad', 'api_token', '');
        if (empty($token)) {
            return ['success' => false, 'message' => 'API токен не настроен'];
        }

        try {
            foreach ($order->orderItems as $item) {
                $product = Product::findOne($item->product_id);
                if ($product && $product->moysklad_id) {
                    $this->reserveInMoysklad($token, $product->moysklad_id, $item->quantity);
                }
            }

            return ['success' => true, 'message' => 'Товары зарезервированы'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX endpoint для ручной синхронизации
     */
    public function actionAjaxSync()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->actionSyncStock();
    }

    /**
     * Получить остатки из МойСклад API
     */
    private function fetchStockFromMoysklad($token)
    {
        $url = $this->apiUrl . '/report/stock/all';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('MySklad API error: HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        $stock = [];

        foreach ($data['rows'] ?? [] as $item) {
            $stock[] = [
                'id' => $item['id'] ?? '',
                'code' => $item['code'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity' => $item['stock'] ?? 0,
            ];
        }

        return $stock;
    }

    /**
     * Зарезервировать товар в МойСклад
     */
    private function reserveInMoysklad($token, $productId, $quantity)
    {
        $url = $this->apiUrl . '/entity/reserve';

        $data = [
            'product' => ['meta' => ['href' => $this->apiUrl . '/entity/product/' . $productId]],
            'quantity' => $quantity,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Страница настроек интеграции
     */
    public function actionSettings()
    {
        return $this->render('settings');
    }
}
