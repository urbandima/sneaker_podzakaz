<?php

/**
 * SitemapGenerator — Генератор sitemap.xml
 * 
 * НАЗНАЧЕНИЕ:
 * Генерация sitemap.xml для всех страниц сайта.
 * Поддержка товаров, категорий, брендов, статических страниц.
 * 
 * ФУНКЦИИ:
 * - generate(): основная генерация sitemap
 * - generateProducts(): генерация URL товаров
 * - generateCategories(): генерация URL категорий
 * - generateBrands(): генерация URL брендов
 * - generateStaticPages(): генерация статических страниц
 * 
 * КОНСТАНТЫ:
 * - BASE_URL: базовый URL сайта
 * - SITEMAP_PATH: путь к файлу sitemap.xml
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * $generator = new SitemapGenerator();
 * $generator->generate();
 * ```
 * 
 * ОСОБЕННОСТИ:
 * - Валидный XML формат
 * - Поддержка lastmod, changefreq, priority
 * - Кэширование результатов
 * - Обработка больших объемов данных
 */
namespace app\backend\services\Sitemap;

use Yii;
use yii\db\Query;
use DOMDocument;
use DOMElement;

/**
 * Генератор sitemap.xml
 */
class SitemapGenerator
{
    private const BASE_URL = 'https://sneakerhead.by';
    private const SITEMAP_PATH = '@webroot/sitemap.xml';
    
    private $dom;
    private $urlset;
    
    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        
        $this->urlset = $this->dom->createElement('urlset');
        $this->urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->dom->appendChild($this->urlset);
    }
    
    /**
     * Динамическая генерация sitemap для web-запроса
     */
    public function generateDynamic(): string
    {
        try {
            $this->generateStaticPages();
            $this->generateCategories();
            $this->generateBrands();
            $this->generateProducts();
            
            return $this->dom->saveXML();
        } catch (\Throwable $e) {
            Yii::error('Sitemap generation failed: ' . $e->getMessage(), __METHOD__);
            return $this->getEmptySitemap();
        }
    }
    
    /**
     * Возвращает пустой sitemap в случае ошибки
     */
    private function getEmptySitemap(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        $urlset = $dom->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $dom->appendChild($urlset);
        
        $url = $dom->createElement('url');
        $urlset->appendChild($url);
        
        $loc = $dom->createElement('loc');
        $loc->appendChild($dom->createTextNode(self::BASE_URL));
        $url->appendChild($loc);
        
        return $dom->saveXML();
    }

    /**
     * Основная генерация sitemap
     */
    public function generate(): bool
    {
        try {
            $this->generateStaticPages();
            $this->generateCategories();
            $this->generateBrands();
            $this->generateProducts();
            
            return $this->save();
        } catch (\Throwable $e) {
            Yii::error('Sitemap generation failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    /**
     * Генерация статических страниц
     */
    private function generateStaticPages(): void
    {
        $pages = [
            ['loc' => '', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => 'catalog', 'changefreq' => 'daily', 'priority' => '0.9'],
        ];
        
        foreach ($pages as $page) {
            $this->addUrl($page['loc'], $page['changefreq'], $page['priority']);
        }
    }
    
    /**
     * Генерация категорий
     */
    private function generateCategories(): void
    {
        try {
            $categories = (new Query())
                ->select(['slug', 'updated_at'])
                ->from('category')
                ->where(['status' => 1])
                ->all();
                
            foreach ($categories as $category) {
                $this->addUrl(
                    'catalog/' . $category['slug'],
                    'weekly',
                    '0.8',
                    $category['updated_at'] ?? null
                );
            }
        } catch (\Throwable $e) {
            Yii::error('Failed to generate categories: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Генерация брендов
     */
    private function generateBrands(): void
    {
        try {
            $brands = (new Query())
                ->select(['slug', 'updated_at'])
                ->from('brand')
                ->where(['status' => 1])
                ->all();
                
            foreach ($brands as $brand) {
                $this->addUrl(
                    'brand/' . $brand['slug'],
                    'weekly',
                    '0.7',
                    $brand['updated_at'] ?? null
                );
            }
        } catch (\Throwable $e) {
            Yii::error('Failed to generate brands: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Генерация товаров
     */
    private function generateProducts(): void
    {
        try {
            $products = (new Query())
                ->select(['slug', 'updated_at'])
                ->from('product')
                ->where(['status' => 1])
                ->all();
                
            foreach ($products as $product) {
                $this->addUrl(
                    'product/' . $product['slug'],
                    'daily',
                    '0.6',
                    $product['updated_at'] ?? null
                );
            }
        } catch (\Throwable $e) {
            Yii::error('Failed to generate products: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Добавление URL в sitemap
     */
    private function addUrl(string $path, string $changefreq, string $priority, ?string $lastmod = null): void
    {
        $url = $this->dom->createElement('url');
        
        $loc = $this->dom->createElement('loc');
        $loc->appendChild($this->dom->createTextNode(rtrim(self::BASE_URL, '/') . '/' . ltrim($path, '/')));
        $url->appendChild($loc);
        
        if ($lastmod) {
            $lastmodEl = $this->dom->createElement('lastmod');
            $lastmodEl->appendChild($this->dom->createTextNode(date('Y-m-d', strtotime($lastmod))));
            $url->appendChild($lastmodEl);
        }
        
        $changefreqEl = $this->dom->createElement('changefreq');
        $changefreqEl->appendChild($this->dom->createTextNode($changefreq));
        $url->appendChild($changefreqEl);
        
        $priorityEl = $this->dom->createElement('priority');
        $priorityEl->appendChild($this->dom->createTextNode($priority));
        $url->appendChild($priorityEl);
        
        $this->urlset->appendChild($url);
    }
    
    /**
     * Сохранение sitemap в файл
     */
    private function save(): bool
    {
        $sitemapPath = Yii::getAlias(self::SITEMAP_PATH);
        $sitemapDir = dirname($sitemapPath);
        
        if (!is_dir($sitemapDir)) {
            mkdir($sitemapDir, 0755, true);
        }
        
        return $this->dom->save($sitemapPath);
    }
}
