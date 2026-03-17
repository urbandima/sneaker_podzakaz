<?php

namespace app\backend\modules\admin\services\import;

use Symfony\Component\DomCrawler\Crawler;
use Yii;

/**
 * LamodaParser — Парсер товаров с Lamoda.by
 * 
 * Парсинг карточки товара, категорий, поиска
 */
class LamodaParser extends BaseParser
{
    /**
     * {@inheritdoc}
     */
    public function getSourceName()
    {
        return 'Lamoda';
    }

    /**
     * Получить URL главной категории (обувь)
     * @return string
     */
    public function getMainCategoryUrl()
    {
        return 'https://www.lamoda.by/c/15/shoes-zhenskaya-obuv/';
    }

    /**
     * {@inheritdoc}
     */
    public function parseProduct($url)
    {
        $html = $this->request($url);
        
        if (!$html) {
            return null;
        }

        // Проверяем на CAPTCHA
        if ($this->hasCaptcha($html)) {
            Yii::warning("CAPTCHA detected on Lamoda: {$url}", 'import');
            
            // Пробуем решить
            $token = $this->solveCaptcha($html, $url);
            
            if ($token) {
                // Повторяем запрос с токеном
                $html = $this->request($url, 'GET', [
                    'query' => ['captcha_token' => $token],
                ]);
            }
        }

        $crawler = new Crawler($html);

        try {
            // Извлекаем данные
            $data = [
                'url' => $url,
                'name' => $this->extractName($crawler),
                'brand_name' => $this->extractBrand($crawler),
                'price' => $this->extractPrice($crawler),
                'old_price' => $this->extractOldPrice($crawler),
                'description' => $this->extractDescription($crawler),
                'images' => $this->extractImages($crawler),
                'sizes' => $this->extractSizes($crawler),
                'sku' => $this->extractSku($crawler),
                'category_name' => $this->extractCategory($crawler),
                'characteristics' => $this->extractCharacteristics($crawler),
            ];

            return $data;
        } catch (\Exception $e) {
            Yii::error("Lamoda parse error: " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseCategory($url, $page = 1)
    {
        $urlWithPage = $url . '?page=' . $page;
        $html = $this->request($urlWithPage);
        
        if (!$html) {
            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        try {
            $crawler->filter('.products-listing__item')->each(function (Crawler $node) use (&$products) {
                $link = $node->filter('a.products-listing__item-link');
                
                if ($link->count() > 0) {
                    $products[] = [
                        'url' => 'https://www.lamoda.by' . $link->attr('href'),
                        'name' => trim($node->filter('.products-listing__item-name')->text('')),
                        'brand' => trim($node->filter('.products-listing__item-brand')->text('')),
                        'price' => $this->parsePriceText($node->filter('.products-listing__item-price')->text('')),
                    ];
                }
            });
        } catch (\Exception $e) {
            Yii::error("Lamoda category parse error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function searchProducts($query, $limit = 50)
    {
        $url = 'https://www.lamoda.by/catalogsearch/result/?q=' . urlencode($query) . '&submit=y';
        $html = $this->request($url);
        
        if (!$html) {
            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        try {
            $crawler->filter('.products-listing__item')->slice(0, $limit)->each(function (Crawler $node) use (&$products) {
                $link = $node->filter('a.products-listing__item-link');
                
                if ($link->count() > 0) {
                    $products[] = [
                        'url' => 'https://www.lamoda.by' . $link->attr('href'),
                        'name' => trim($node->filter('.products-listing__item-name')->text('')),
                        'brand' => trim($node->filter('.products-listing__item-brand')->text('')),
                        'price' => $this->parsePriceText($node->filter('.products-listing__item-price')->text('')),
                    ];
                }
            });
        } catch (\Exception $e) {
            Yii::error("Lamoda search error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUrls($categoryUrl)
    {
        $urls = [];
        $page = 1;
        $maxPages = 10; // Ограничение

        while ($page <= $maxPages) {
            $products = $this->parseCategory($categoryUrl, $page);
            
            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                $urls[] = $product['url'];
            }

            $page++;
            $this->delay();
        }

        return array_unique($urls);
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeProductData(array $rawData)
    {
        $normalized = [
            'name' => $rawData['name'] ?? '',
            'brand_name' => $rawData['brand_name'] ?? null,
            'price' => $rawData['price'] ?? 0,
            'old_price' => $rawData['old_price'] ?? null,
            'description' => $rawData['description'] ?? null,
            'sku' => $rawData['sku'] ?? null,
            'category_name' => $rawData['category_name'] ?? null,
            'images' => $rawData['images'] ?? [],
            'sizes' => [],
        ];

        // Нормализуем характеристики
        if (!empty($rawData['characteristics'])) {
            $chars = $rawData['characteristics'];
            
            $normalized['material'] = $chars['Материал'] ?? $chars['material'] ?? null;
            $normalized['season'] = $this->normalizeSeason($chars['Сезон'] ?? $chars['season'] ?? null);
            $normalized['gender'] = $this->normalizeGender($chars['Пол'] ?? $chars['gender'] ?? null);
            $normalized['country'] = $chars['Страна производства'] ?? $chars['country'] ?? null;
            $normalized['style_code'] = $chars['Артикул'] ?? $chars['style_code'] ?? null;
        }

        // Нормализуем размеры
        if (!empty($rawData['sizes'])) {
            foreach ($rawData['sizes'] as $size) {
                $normalized['sizes'][] = [
                    'size' => $size['size'] ?? null,
                    'size_eu' => $size['size_eu'] ?? $size['size'] ?? null,
                    'price' => $normalized['price'],
                    'is_available' => $size['available'] ?? true,
                ];
            }
        }

        return $normalized;
    }

    // ==================== МЕТОДЫ ИЗВЛЕЧЕНИЯ ДАННЫХ ====================

    /**
     * Извлечь название товара
     */
    protected function extractName(Crawler $crawler)
    {
        try {
            $name = $crawler->filter('.product-title__brand-name')->text('');
            $model = $crawler->filter('.product-title__model-name')->text('');
            
            return trim($name . ' ' . $model);
        } catch (\Exception $e) {
            return $crawler->filter('h1')->text('');
        }
    }

    /**
     * Извлечь бренд
     */
    protected function extractBrand(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('.product-title__brand-name')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь цену
     */
    protected function extractPrice(Crawler $crawler)
    {
        try {
            $priceText = $crawler->filter('.product-prices__price')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Извлечь старую цену
     */
    protected function extractOldPrice(Crawler $crawler)
    {
        try {
            $priceText = $crawler->filter('.product-prices__old-price')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь описание
     */
    protected function extractDescription(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('.product-description__text')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь изображения
     */
    protected function extractImages(Crawler $crawler)
    {
        $images = [];

        try {
            $crawler->filter('.product-images__item img')->each(function (Crawler $node) use (&$images) {
                $src = $node->attr('src') ?: $node->attr('data-src');
                if ($src) {
                    // Увеличиваем размер изображения
                    $src = str_replace(['_1.jpg', '_2.jpg'], '_9.jpg', $src);
                    $images[] = $src;
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $images;
    }

    /**
     * Извлечь размеры
     */
    protected function extractSizes(Crawler $crawler)
    {
        $sizes = [];

        try {
            $crawler->filter('.product-size-selector__item')->each(function (Crawler $node) use (&$sizes) {
                $sizeText = trim($node->text(''));
                
                if ($sizeText && strpos($sizeText, 'Нет в наличии') === false) {
                    $sizes[] = [
                        'size' => $sizeText,
                        'size_eu' => $sizeText,
                        'available' => !$node->hasClass('product-size-selector__item_disabled'),
                    ];
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $sizes;
    }

    /**
     * Извлечь SKU
     */
    protected function extractSku(Crawler $crawler)
    {
        try {
            // Lamoda использует артикул как SKU
            $sku = $crawler->filter('[itemprop="sku"]')->attr('content');
            
            if (!$sku) {
                // Пробуем извлечь из характеристик
                $crawler->filter('.product-characteristics__item')->each(function (Crawler $node) use (&$sku) {
                    $label = trim($node->filter('.product-characteristics__label')->text(''));
                    if (strpos($label, 'Артикул') !== false) {
                        $sku = trim($node->filter('.product-characteristics__value')->text(''));
                    }
                });
            }
            
            return $sku;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь категорию
     */
    protected function extractCategory(Crawler $crawler)
    {
        try {
            // Извлекаем из хлебных крошек
            $categories = [];
            $crawler->filter('.breadcrumbs__item a')->each(function (Crawler $node) use (&$categories) {
                $categories[] = trim($node->text(''));
            });
            
            // Возвращаем последнюю категорию (самую конкретную)
            return end($categories) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь характеристики
     */
    protected function extractCharacteristics(Crawler $crawler)
    {
        $characteristics = [];

        try {
            $crawler->filter('.product-characteristics__item')->each(function (Crawler $node) use (&$characteristics) {
                $label = trim($node->filter('.product-characteristics__label')->text(''));
                $value = trim($node->filter('.product-characteristics__value')->text(''));
                
                if ($label && $value) {
                    $characteristics[$label] = $value;
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $characteristics;
    }

    /**
     * Распарсить текст цены
     */
    protected function parsePriceText($text)
    {
        // Удаляем все кроме цифр и точки
        $price = preg_replace('/[^0-9.]/', '', $text);
        
        return (float) $price;
    }

    /**
     * Нормализовать сезон
     */
    protected function normalizeSeason($season)
    {
        if (!$season) {
            return null;
        }

        $seasonLower = mb_strtolower($season, 'UTF-8');

        if (strpos($seasonLower, 'лет') !== false || strpos($seasonLower, 'summer') !== false) {
            return 'summer';
        }
        
        if (strpos($seasonLower, 'зим') !== false || strpos($seasonLower, 'winter') !== false) {
            return 'winter';
        }
        
        if (strpos($seasonLower, 'деми') !== false || strpos($seasonLower, 'осен') !== false || strpos($seasonLower, 'весен') !== false) {
            return 'demi';
        }

        return 'all';
    }

    /**
     * Нормализовать пол
     */
    protected function normalizeGender($gender)
    {
        if (!$gender) {
            return null;
        }

        $genderLower = mb_strtolower($gender, 'UTF-8');

        if (strpos($genderLower, 'муж') !== false || strpos($genderLower, 'male') !== false) {
            return 'male';
        }
        
        if (strpos($genderLower, 'жен') !== false || strpos($genderLower, 'female') !== false) {
            return 'female';
        }

        return 'unisex';
    }
}
