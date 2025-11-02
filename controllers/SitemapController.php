<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Product;
use app\models\Category;
use app\models\Brand;

/**
 * Контроллер генерации sitemap.xml
 */
class SitemapController extends Controller
{
    /**
     * Генерация sitemap.xml
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/xml; charset=utf-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Главная страница
        $xml .= $this->generateUrl('/', '1.0', 'daily', date('Y-m-d'));

        // Главная каталога
        $xml .= $this->generateUrl('/catalog', '0.9', 'daily', date('Y-m-d'));

        // Бренды
        $brands = Brand::find()->where(['is_active' => 1])->all();
        foreach ($brands as $brand) {
            $xml .= $this->generateUrl(
                '/catalog/brand/' . $brand->slug,
                '0.8',
                'weekly',
                $brand->updated_at
            );
        }

        // Категории
        $categories = Category::find()->where(['is_active' => 1])->all();
        foreach ($categories as $category) {
            $xml .= $this->generateUrl(
                '/catalog/category/' . $category->slug,
                '0.8',
                'weekly',
                $category->updated_at
            );
        }

        // Товары
        $products = Product::find()->where(['is_active' => 1])->all();
        foreach ($products as $product) {
            $xml .= $this->generateUrl(
                '/catalog/product/' . $product->slug,
                '0.7',
                'weekly',
                $product->updated_at
            );
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Генерация одного URL для sitemap
     */
    protected function generateUrl($path, $priority, $changefreq, $lastmod)
    {
        $baseUrl = Yii::$app->request->hostInfo;
        $url = $baseUrl . $path;
        
        $lastmodFormatted = date('Y-m-d', is_string($lastmod) ? strtotime($lastmod) : $lastmod);

        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>\n",
            htmlspecialchars($url),
            $lastmodFormatted,
            $changefreq,
            $priority
        );
    }
}
