<?php

namespace app\services\Sitemap\sections;

use XMLWriter;
use app\models\Product;

/**
 * Секция товаров для sitemap
 * Использует батч-обработку для минимизации памяти
 */
class ProductSection extends AbstractSitemapSection
{
    private int $batchSize;

    public function __construct(string $baseUrl)
    {
        parent::__construct($baseUrl);
        $this->batchSize = \Yii::$app->params['sitemap']['productBatchSize'] ?? 500;
    }

    public function write(XMLWriter $writer): int
    {
        $query = Product::find()
            ->select(['id', 'slug', 'name', 'main_image_url', 'main_image', 'updated_at'])
            ->where(['is_active' => 1])
            ->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_ASC]);

        $count = 0;
        foreach ($query->batch($this->batchSize) as $products) {
            /** @var Product $product */
            foreach ($products as $product) {
                $count += $this->writeProduct($writer, $product);
            }
        }

        return $count;
    }

    private function writeProduct(XMLWriter $writer, Product $product): int
    {
        $options = [
            'priority' => 0.7,
            'changefreq' => 'weekly',
            'lastmod' => $product->updated_at,
        ];

        $image = $this->resolveImage($product);
        if ($image) {
            $options['images'] = [$image];
        }

        return $this->writeUrl($writer, '/catalog/product/' . $product->slug, $options);
    }

    /**
     * Получение изображения товара для sitemap
     * 
     * @param Product $product
     * @return array|null
     */
    private function resolveImage(Product $product): ?array
    {
        $url = $product->main_image_url ?: $product->main_image;

        if (!$url && method_exists($product, 'getImages')) {
            $image = $product->getImages()
                ->select(['image'])
                ->orderBy(['is_main' => SORT_DESC, 'sort_order' => SORT_ASC])
                ->limit(1)
                ->scalar();
            if ($image) {
                $url = $image;
            }
        }

        if (!$url) {
            return null;
        }

        return [
            'loc' => $this->absoluteUrl($url),
            'title' => $product->name,
        ];
    }
}
