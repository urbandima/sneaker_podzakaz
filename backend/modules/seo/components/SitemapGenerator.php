<?php

/**
 * SitemapGenerator — Генератор sitemap.xml
 */
namespace app\backend\modules\seo\components;

use Yii;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;

class SitemapGenerator
{
    public $baseUrl;
    public $maxUrls = 50000;
    public $filePath;

    public function __construct()
    {
        $this->baseUrl = Yii::$app->request->hostInfo;
        $this->filePath = Yii::getAlias('@frontend/web/sitemap.xml');
    }

    /**
     * Генерировать sitemap
     * @return array ['urls' => int, 'file' => string]
     */
    public function generate()
    {
        $urls = [];

        // Статические страницы
        $urls[] = $this->createUrlEntry('/', '1.0', 'daily');
        $urls[] = $this->createUrlEntry('/catalog', '0.9', 'daily');
        $urls[] = $this->createUrlEntry('/about', '0.5', 'monthly');
        $urls[] = $this->createUrlEntry('/contacts', '0.5', 'monthly');
        $urls[] = $this->createUrlEntry('/brands', '0.6', 'weekly');

        // Категории
        $categories = Category::find()
            ->where(['is_active' => true])
            ->select(['slug', 'updated_at'])
            ->all();

        foreach ($categories as $category) {
            $urls[] = $this->createUrlEntry(
                '/catalog/category/' . $category->slug,
                '0.8',
                'weekly',
                $this->formatDate($category->updated_at)
            );
        }

        // Бренды
        $brands = Brand::find()
            ->where(['is_active' => true])
            ->select(['slug', 'updated_at'])
            ->all();

        foreach ($brands as $brand) {
            $urls[] = $this->createUrlEntry(
                '/catalog/brand/' . $brand->slug,
                '0.7',
                'weekly',
                $this->formatDate($brand->updated_at)
            );
        }

        // Товары
        $products = Product::find()
            ->where(['is_active' => true])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
            ->select(['slug', 'updated_at'])
            ->limit($this->maxUrls - count($urls))
            ->all();

        foreach ($products as $product) {
            $urls[] = $this->createUrlEntry(
                '/catalog/product/' . $product->slug,
                '0.6',
                'weekly',
                $this->formatDate($product->updated_at)
            );
        }

        // Генерируем XML
        $xml = $this->buildXml($urls);

        // Сохраняем
        file_put_contents($this->filePath, $xml);

        return [
            'urls' => count($urls),
            'file' => 'sitemap.xml',
            'size' => strlen($xml),
        ];
    }

    /**
     * Создать запись URL
     */
    private function createUrlEntry($loc, $priority, $changefreq, $lastmod = null)
    {
        $entry = [
            'loc' => $this->baseUrl . $loc,
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];

        if ($lastmod) {
            $entry['lastmod'] = $lastmod;
        }

        return $entry;
    }

    /**
     * Форматировать дату
     */
    private function formatDate($timestamp)
    {
        if (is_numeric($timestamp)) {
            return date('Y-m-d', $timestamp);
        }
        if (is_string($timestamp)) {
            return date('Y-m-d', strtotime($timestamp));
        }
        return date('Y-m-d');
    }

    /**
     * Построить XML
     */
    private function buildXml($urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . $this->escapeXml($url['loc']) . "</loc>\n";
            if (isset($url['lastmod'])) {
                $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Экранировать XML
     */
    private function escapeXml($string)
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
