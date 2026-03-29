<?php

namespace app\backend\modules\marketing\services;

use Yii;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;

class UpsellService
{
    /**
     * Получить рекомендации для товара (cross-sell)
     */
    public function getProductRecommendations(Product $product, int $limit = 4): array
    {
        // Стратегия 1: Товары из той же категории
        $sameCategory = Product::find()
            ->where(['category_id' => $product->category_id])
            ->andWhere(['!=', 'id', $product->id])
            ->andWhere(['is_active' => true])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
        
        if (count($sameCategory) >= $limit) {
            return $sameCategory;
        }
        
        // Стратегия 2: Товары того же бренда
        $sameBrand = Product::find()
            ->where(['brand_id' => $product->brand_id])
            ->andWhere(['!=', 'id', $product->id])
            ->andWhere(['is_active' => true])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit - count($sameCategory))
            ->all();
        
        return array_merge($sameCategory, $sameBrand);
    }
    
    /**
     * Получить товары "Часто покупают вместе"
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 3): array
    {
        // Находим заказы, содержащие этот товар
        $orderIds = OrderItem::find()
            ->select('order_id')
            ->where(['product_id' => $product->id])
            ->column();
        
        if (empty($orderIds)) {
            return [];
        }
        
        // Находим другие товары из этих заказов
        $productIds = OrderItem::find()
            ->select(['product_id', 'COUNT(*) as frequency'])
            ->where(['order_id' => $orderIds])
            ->andWhere(['!=', 'product_id', $product->id])
            ->groupBy('product_id')
            ->orderBy(['frequency' => SORT_DESC])
            ->limit($limit)
            ->column();
        
        return Product::find()
            ->where(['id' => $productIds])
            ->andWhere(['is_active' => true])
            ->all();
    }
    
    /**
     * Получить персональные рекомендации для клиента
     */
    public function getPersonalizedRecommendations(int $customerId, int $limit = 6): array
    {
        // Получаем историю покупок клиента
        $purchasedProductIds = OrderItem::find()
            ->select('product_id')
            ->innerJoin('order', 'order.id = order_item.order_id')
            ->where(['order.customer_id' => $customerId])
            ->column();
        
        if (empty($purchasedProductIds)) {
            // Если нет истории, показываем популярные товары
            return Product::find()
                ->where(['is_active' => true])
                ->orderBy(['views_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }
        
        // Получаем категории и бренды из истории
        $products = Product::find()
            ->where(['id' => $purchasedProductIds])
            ->all();
        
        $categoryIds = [];
        $brandIds = [];
        
        foreach ($products as $product) {
            if ($product->category_id) {
                $categoryIds[] = $product->category_id;
            }
            if ($product->brand_id) {
                $brandIds[] = $product->brand_id;
            }
        }
        
        // Рекомендуем товары из тех же категорий/брендов, которые клиент еще не покупал
        return Product::find()
            ->where(['is_active' => true])
            ->andWhere(['not in', 'id', $purchasedProductIds])
            ->andWhere([
                'or',
                ['category_id' => array_unique($categoryIds)],
                ['brand_id' => array_unique($brandIds)],
            ])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Получить товары для upsell (более дорогие альтернативы)
     */
    public function getUpsellProducts(Product $product, int $limit = 3): array
    {
        $minPrice = $product->price * 1.2; // На 20% дороже
        $maxPrice = $product->price * 2.0; // Не более чем в 2 раза
        
        return Product::find()
            ->where(['category_id' => $product->category_id])
            ->andWhere(['!=', 'id', $product->id])
            ->andWhere(['between', 'price', $minPrice, $maxPrice])
            ->andWhere(['is_active' => true])
            ->orderBy(['rating' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Получить статистику эффективности рекомендаций
     */
    public function getRecommendationStats(): array
    {
        // TODO: добавить отслеживание кликов и конверсий по рекомендациям
        return [
            'total_views' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'conversion_rate' => 0,
            'revenue_generated' => 0,
        ];
    }
}
