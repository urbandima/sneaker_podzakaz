<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\Json;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\Customer;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;

class PosController extends BaseAdminController
{
    /**
     * Главная страница POS-терминала
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Поиск товара по штрихкоду или артикулу
     */
    public function actionScan()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $code = Yii::$app->request->post('code');
        
        if (!$code) {
            return ['success' => false, 'message' => 'Код не указан'];
        }
        
        // Ищем товар по артикулу или vendor_code
        $product = Product::find()
            ->where(['article' => $code])
            ->orWhere(['vendor_code' => $code])
            ->with('sizes')
            ->one();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }
        
        // Получаем доступные размеры
        $sizes = [];
        foreach ($product->sizes as $size) {
            if ($size->stock > 0) {
                $sizes[] = [
                    'id' => $size->id,
                    'size' => $size->size,
                    'stock' => $size->stock,
                    'price' => $size->price ?: $product->price,
                ];
            }
        }
        
        return [
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'article' => $product->article,
                'price' => $product->price,
                'image' => $product->getMainImageUrl(),
                'sizes' => $sizes,
            ]
        ];
    }
    
    /**
     * Быстрое создание заказа
     */
    public function actionQuickOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = Yii::$app->request->post();
        
        if (empty($data['items'])) {
            return ['success' => false, 'message' => 'Корзина пуста'];
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Создаём или находим клиента
            $customer = null;
            if (!empty($data['customer_phone'])) {
                $customer = Customer::findOne(['phone' => $data['customer_phone']]);
                
                if (!$customer && !empty($data['customer_name'])) {
                    $customer = new Customer();
                    $customer->phone = $data['customer_phone'];
                    $customer->first_name = $data['customer_name'];
                    $customer->email = $data['customer_email'] ?? null;
                    $customer->status = 10;
                    
                    if (!$customer->save()) {
                        throw new \Exception('Ошибка создания клиента');
                    }
                }
            }
            
            // Создаём заказ
            $order = new Order();
            $order->customer_id = $customer ? $customer->id : null;
            $order->customer_name = $data['customer_name'] ?? 'Гость';
            $order->customer_phone = $data['customer_phone'] ?? '';
            $order->customer_email = $data['customer_email'] ?? '';
            $order->status = 'created';
            $order->payment_method = $data['payment_method'] ?? 'cash';
            $order->delivery_method = 'pickup';
            $order->created_by = Yii::$app->user->id;
            $order->created_at = time();
            $order->updated_at = time();
            
            $totalAmount = 0;
            
            // Добавляем товары
            foreach ($data['items'] as $item) {
                $size = ProductSize::findOne($item['size_id']);
                
                if (!$size || $size->stock < $item['quantity']) {
                    throw new \Exception("Недостаточно товара: {$size->product->name} размер {$size->size}");
                }
                
                $price = $size->price ?: $size->product->price;
                $totalAmount += $price * $item['quantity'];
            }
            
            $order->subtotal = $totalAmount;
            $order->total_amount = $totalAmount;
            
            if (!$order->save()) {
                throw new \Exception('Ошибка создания заказа: ' . Json::encode($order->errors));
            }
            
            // Создаём позиции заказа
            foreach ($data['items'] as $item) {
                $size = ProductSize::findOne($item['size_id']);
                $price = $size->price ?: $size->product->price;
                
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $size->product_id;
                $orderItem->product_name = $size->product->name;
                $orderItem->size = $size->size;
                $orderItem->quantity = $item['quantity'];
                $orderItem->price = $price;
                $orderItem->total = $price * $item['quantity'];
                
                if (!$orderItem->save()) {
                    throw new \Exception('Ошибка добавления товара');
                }
                
                // Уменьшаем остаток
                $size->stock -= $item['quantity'];
                $size->save(false);
            }
            
            $transaction->commit();
            
            return [
                'success' => true,
                'message' => 'Заказ создан успешно',
                'order_id' => $order->id,
                'order_number' => $order->order_number ?: $order->id,
                'total' => $order->total_amount,
            ];
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Поиск клиента по телефону
     */
    public function actionSearchCustomer()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $phone = Yii::$app->request->get('phone');
        
        if (!$phone) {
            return ['success' => false, 'message' => 'Телефон не указан'];
        }
        
        $customer = Customer::findOne(['phone' => $phone]);
        
        if (!$customer) {
            return ['success' => false, 'message' => 'Клиент не найден'];
        }
        
        return [
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->getFullName(),
                'phone' => $customer->phone,
                'email' => $customer->email,
                'orders_count' => $customer->orders_count,
                'total_spent' => $customer->total_spent,
            ]
        ];
    }
    
    /**
     * Получить список товаров для POS
     */
    public function actionGetProducts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $products = Product::find()
                ->where(['is_active' => true])
                ->with(['sizes', 'category', 'brand'])
                ->orderBy(['name' => SORT_ASC])
                ->limit(500)
                ->all();
            
            $result = [];
            foreach ($products as $product) {
                $sizes = [];
                $totalStock = 0;
                
                foreach ($product->sizes as $size) {
                    $sizes[] = [
                        'id' => $size->id,
                        'size' => $size->size,
                        'stock' => (int)$size->stock,
                        'price' => (float)($size->price ?: $product->price),
                    ];
                    $totalStock += (int)$size->stock;
                }
                
                $result[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'article' => $product->article,
                    'sku' => $product->vendor_code,
                    'price' => (float)$product->price,
                    'image' => $product->getMainImageUrl(),
                    'category_id' => $product->category_id,
                    'brand_id' => $product->brand_id,
                    'total_stock' => $totalStock,
                    'sizes' => $sizes,
                ];
            }
            
            return ['success' => true, 'products' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'products' => []];
        }
    }
    
    /**
     * Получить список клиентов для POS
     */
    public function actionGetCustomers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $customers = Customer::find()
                ->where(['status' => 10])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(100)
                ->all();
            
            $result = [];
            foreach ($customers as $customer) {
                $result[] = [
                    'id' => $customer->id,
                    'name' => $customer->getFullName(),
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ];
            }
            
            return ['success' => true, 'customers' => $result];
        } catch (\Exception $e) {
            // Возвращаем пустой список если таблица не существует
            return ['success' => true, 'customers' => []];
        }
    }
    
    /**
     * Создать нового клиента из POS
     */
    public function actionCreateCustomer()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Имя обязательно'];
        }
        
        try {
            $customer = new Customer();
            $customer->first_name = $data['name'];
            $customer->phone = $data['phone'] ?? null;
            $customer->status = 10;
            $customer->created_at = time();
            
            if ($customer->save()) {
                return [
                    'success' => true,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->getFullName(),
                        'phone' => $customer->phone,
                    ]
                ];
            }
            
            return ['success' => false, 'message' => 'Ошибка сохранения: ' . implode(', ', $customer->getFirstErrors())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Получить готовые заказы клиента для выдачи
     */
    public function actionGetCustomerOrders()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $customerId = Yii::$app->request->get('customer_id');
        
        if (!$customerId) {
            return ['success' => false, 'orders' => []];
        }
        
        try {
            $orders = Order::find()
                ->where(['customer_id' => $customerId])
                ->andWhere(['in', 'status', ['paid', 'processing', 'ready']])
                ->with(['items.product', 'items.size'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(20)
                ->all();
            
            $result = [];
            foreach ($orders as $order) {
                $items = [];
                foreach ($order->items as $item) {
                    $items[] = [
                        'product_name' => $item->product->name,
                        'size' => $item->size ? $item->size->size : '-',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }
                
                $result[] = [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'items' => $items,
                    'items_count' => count($items),
                ];
            }
            
            return ['success' => true, 'orders' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'orders' => [], 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Выдать заказ клиенту
     */
    public function actionCompleteOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $orderId = Yii::$app->request->post('order_id');
        
        if (!$orderId) {
            return ['success' => false, 'message' => 'ID заказа не указан'];
        }
        
        try {
            $order = Order::findOne($orderId);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Заказ не найден'];
            }
            
            $order->status = 'completed';
            $order->completed_at = time();
            $order->updated_at = time();
            
            if ($order->save()) {
                return [
                    'success' => true,
                    'message' => 'Заказ выдан клиенту',
                    'order_number' => $order->order_number,
                ];
            }
            
            return ['success' => false, 'message' => 'Ошибка обновления заказа'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Применить скидку к заказу
     */
    public function actionApplyDiscount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        
        $discountType = $data['type'] ?? 'percent'; // percent или fixed
        $discountValue = (float)($data['value'] ?? 0);
        $subtotal = (float)($data['subtotal'] ?? 0);
        
        if ($discountValue <= 0 || $subtotal <= 0) {
            return ['success' => false, 'message' => 'Некорректные данные'];
        }
        
        $discountAmount = 0;
        
        if ($discountType === 'percent') {
            if ($discountValue > 100) {
                return ['success' => false, 'message' => 'Скидка не может быть больше 100%'];
            }
            $discountAmount = $subtotal * ($discountValue / 100);
        } else {
            if ($discountValue > $subtotal) {
                return ['success' => false, 'message' => 'Скидка не может быть больше суммы заказа'];
            }
            $discountAmount = $discountValue;
        }
        
        $total = $subtotal - $discountAmount;
        
        return [
            'success' => true,
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
        ];
    }
    
    /**
     * Поиск товаров для автокомплита
     */
    public function actionSearchProducts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q');
        
        if (!$query || mb_strlen($query) < 2) {
            return ['results' => []];
        }
        
        $products = Product::find()
            ->where(['like', 'name', $query])
            ->orWhere(['like', 'article', $query])
            ->orWhere(['like', 'vendor_code', $query])
            ->andWhere(['is_active' => true])
            ->limit(10)
            ->all();
        
        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'text' => $product->name . ' (' . $product->article . ')',
                'name' => $product->name,
                'article' => $product->article,
                'price' => $product->price,
                'image' => $product->getMainImageUrl(),
            ];
        }
        
        return ['results' => $results];
    }
}
