<?php

namespace app\backend\modules\admin\services\import;

use Symfony\Component\DomCrawler\Crawler;
use Yii;

/**
 * StockXParser — Парсер товаров со StockX.com
 * 
 * Рынок кроссовок, цены в USD
 */
class StockXParser extends BaseParser
{
    /**
     * {@inheritdoc}
     */
    public function getSourceName()
    {
        return 'StockX';
    }

    /**
     * Получить URL главной категории (sneakers)
     * @return string
     */
    public function getMainCategoryUrl()
    {
        return 'https://stockx.com/sneakers';
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

        // Проверяем на CAPTCHA (Cloudflare)
        if ($this->hasCaptcha($html) || strpos($html, 'challenge') !== false) {
            Yii::warning("CAPTCHA/Cloudflare detected on StockX: {$url}", 'import');
            $token = $this->solveCaptcha($html, $url);
            
            if ($token) {
                $html = $this->request($url, 'GET', [
                    'headers' => ['cf-turnstile-response' => $token],
                ]);
            }
        }

        // StockX использует JSON-LD и Next.js данные
        $data = $this->extractNextData($html);
        
        if ($data) {
            return $data;
        }

        // Fallback: JSON-LD
        $data = $this->extractJsonLd($html);
        
        if ($data) {
            return $data;
        }

        // Fallback: HTML
        return $this->parseFromHtml($url, $html);
    }

    /**
     * Извлечь данные из Next.js __NEXT_DATA__
     * @param string $html
     * @return array|null
     */
    protected function extractNextData($html)
    {
        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            try {
                $json = json_decode($matches[1], true);
                
                if ($json && isset($json['props']['pageProps']['product'])) {
                    return $this->parseNextProduct($json['props']['pageProps']['product']);
                }
            } catch (\Exception $e) {
                Yii::error("StockX Next.js parse error: " . $e->getMessage(), 'import');
            }
        }

        return null;
    }

    /**
     * Парсинг из Next.js данных
     * @param array $data
     * @return array
     */
    protected function parseNextProduct($data)
    {
        $product = [
            'url' => 'https://stockx.com/' . ($data['urlKey'] ?? ''),
            'name' => $data['title'] ?? $data['name'] ?? '',
            'brand_name' => $data['brand'] ?? null,
            'price' => (float) ($data['market']['lowestAsk'] ?? $data['price'] ?? 0),
            'old_price' => isset($data['market']['highestBid']) ? (float) $data['market']['highestBid'] : null,
            'description' => $data['description'] ?? null,
            'images' => [],
            'sizes' => [],
            'sku' => $data['styleId'] ?? $data['uuid'] ?? null,
            'category_name' => $data['category'] ?? 'Sneakers',
            'characteristics' => [],
        ];

        // Извлекаем изображения
        if (isset($data['media']['imageUrl'])) {
            $product['images'][] = $data['media']['imageUrl'];
        }
        
        if (isset($data['media']['gallery']) && is_array($data['media']['gallery'])) {
            foreach ($data['media']['gallery'] as $img) {
                $product['images'][] = $img['url'] ?? $img;
            }
        }

        // Извлекаем размеры и цены
        if (isset($data['market']['variants']) && is_array($data['market']['variants'])) {
            foreach ($data['market']['variants'] as $variant) {
                $product['sizes'][] = [
                    'size' => $variant['size'] ?? $variant['sizeUS'] ?? null,
                    'size_us' => $variant['sizeUS'] ?? $variant['size'] ?? null,
                    'size_eu' => $this->convertUsToEu($variant['sizeUS'] ?? null),
                    'price' => (float) ($variant['lowestAsk'] ?? $variant['price'] ?? $product['price']),
                    'available' => ($variant['lowestAsk'] ?? 0) > 0,
                ];
            }
        }

        // Извлекаем характеристики
        if (isset($data['traits']) && is_array($data['traits'])) {
            foreach ($data['traits'] as $trait) {
                $product['characteristics'][$trait['name']] = $trait['value'];
            }
        }

        return $product;
    }

    /**
     * Извлечь данные из JSON-LD
     * @param string $html
     * @return array|null
     */
    protected function extractJsonLd($html)
    {
        $crawler = new Crawler($html);

        try {
            $jsonLd = $crawler->filter('script[type="application/ld+json"]')->first()->text('');
            
            if ($jsonLd) {
                $data = json_decode($jsonLd, true);
                
                if ($data && isset($data['@type']) && $data['@type'] === 'Product') {
                    return [
                        'url' => $data['url'] ?? null,
                        'name' => $data['name'] ?? '',
                        'brand_name' => $data['brand']['name'] ?? null,
                        'price' => (float) ($data['offers']['price'] ?? 0),
                        'old_price' => null,
                        'description' => $data['description'] ?? null,
                        'images' => is_array($data['image'] ?? null) ? ($data['image'] ?? []) : [$data['image']],
                        'sizes' => [],
                        'sku' => $data['sku'] ?? $data['mpn'] ?? null,
                        'category_name' => 'Sneakers',
                        'characteristics' => [],
                    ];
                }
            }
        } catch (\Exception $e) {
            // Игнорируем
        }

        return null;
    }

    /**
     * Парсинг из HTML
     * @param string $url
     * @param string $html
     * @return array
     */
    protected function parseFromHtml($url, $html)
    {
        $crawler = new Crawler($html);

        try {
            $data = [
                'url' => $url,
                'name' => $this->extractName($crawler),
                'brand_name' => $this->extractBrand($crawler),
                'price' => $this->extractPrice($crawler),
                'old_price' => null,
                'description' => null,
                'images' => $this->extractImages($crawler),
                'sizes' => [],
                'sku' => $this->extractSku($crawler),
                'category_name' => 'Sneakers',
                'characteristics' => [],
            ];

            return $data;
        } catch (\Exception $e) {
            Yii::error("StockX HTML parse error: " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseCategory($url, $page = 1)
    {
        // StockX использует API для списка товаров
        $apiUrl = 'https://stockx.com/api/browse?category=sneakers&page=' . $page;
        
        $response = $this->request($apiUrl);
        
        if (!$response) {
            // Fallback: парсим HTML
            return $this->parseCategoryFromHtml($url, $page);
        }

        $products = [];

        try {
            $data = json_decode($response, true);
            
            if (isset($data['Products']) && is_array($data['Products'])) {
                foreach ($data['Products'] as $item) {
                    $products[] = [
                        'url' => 'https://stockx.com/' . ($item['urlKey'] ?? $item['slug'] ?? ''),
                        'name' => $item['title'] ?? $item['name'] ?? '',
                        'brand' => $item['brand'] ?? null,
                        'price' => (float) ($item['market']['lowestAsk'] ?? $item['price'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            Yii::error("StockX category parse error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * Парсинг категории из HTML
     * @param string $url
     * @param int $page
     * @return array
     */
    protected function parseCategoryFromHtml($url, $page)
    {
        $urlWithPage = $url . '?page=' . $page;
        $html = $this->request($urlWithPage);
        
        if (!$html) {
            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        try {
            $crawler->filter('[data-testid="product-tile"]')->each(function (Crawler $node) use (&$products) {
                $link = $node->filter('a');
                
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    
                    $products[] = [
                        'url' => 'https://stockx.com' . $href,
                        'name' => trim($node->filter('[data-testid="product-name"]')->text('')),
                        'brand' => trim($node->filter('[data-testid="product-brand"]')->text('')),
                        'price' => $this->parsePriceText($node->filter('[data-testid="product-price"]')->text('')),
                    ];
                }
            });
        } catch (\Exception $e) {
            Yii::error("StockX HTML category parse error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function searchProducts($query, $limit = 50)
    {
        $apiUrl = 'https://stockx.com/api/browse?_search=' . urlencode($query) . '&size=' . $limit;
        
        $response = $this->request($apiUrl);
        
        if (!$response) {
            return [];
        }

        $products = [];

        try {
            $data = json_decode($response, true);
            
            if (isset($data['Products']) && is_array($data['Products'])) {
                foreach ($data['Products'] as $item) {
                    $products[] = [
                        'url' => 'https://stockx.com/' . ($item['urlKey'] ?? $item['slug'] ?? ''),
                        'name' => $item['title'] ?? $item['name'] ?? '',
                        'brand' => $item['brand'] ?? null,
                        'price' => (float) ($item['market']['lowestAsk'] ?? $item['price'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            Yii::error("StockX search error: " . $e->getMessage(), 'import');
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
        $maxPages = 10;

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
            'category_name' => $rawData['category_name'] ?? 'Sneakers',
            'images' => $rawData['images'] ?? [],
            'sizes' => [],
        ];

        // Нормализуем характеристики
        if (!empty($rawData['characteristics'])) {
            $chars = $rawData['characteristics'];
            
            $normalized['material'] = $chars['Upper Material'] ?? $chars['material'] ?? null;
            $normalized['season'] = $this->normalizeSeason($chars['Season'] ?? null);
            $normalized['gender'] = $this->normalizeGender($chars['Gender'] ?? null);
            $normalized['country'] = $chars['Made In'] ?? $chars['Country'] ?? null;
            $normalized['style_code'] = $chars['Style'] ?? $chars['Style ID'] ?? $rawData['sku'];
            $normalized['release_year'] = isset($chars['Release Date']) ? date('Y', strtotime($chars['Release Date'])) : null;
        }

        // Нормализуем размеры
        if (!empty($rawData['sizes'])) {
            foreach ($rawData['sizes'] as $size) {
                $normalized['sizes'][] = [
                    'size' => $size['size'] ?? $size['size_us'] ?? null,
                    'size_us' => $size['size_us'] ?? $size['size'] ?? null,
                    'size_eu' => $size['size_eu'] ?? $this->convertUsToEu($size['size_us'] ?? null),
                    'price' => $size['price'] ?? $normalized['price'],
                    'is_available' => $size['available'] ?? true,
                ];
            }
        }

        return $normalized;
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    protected function extractName(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="product-name"], h1')->text(''));
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function extractBrand(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="product-brand"]')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractPrice(Crawler $crawler)
    {
        try {
            $priceText = $crawler->filter('[data-testid="product-price"]')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function extractImages(Crawler $crawler)
    {
        $images = [];

        try {
            $crawler->filter('[data-testid="product-image"] img')->each(function (Crawler $node) use (&$images) {
                $src = $node->attr('src') ?: $node->attr('data-src');
                if ($src) {
                    $images[] = $src;
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $images;
    }

    protected function extractSku(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="product-style-id"]')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Конвертировать US размер в EU
     * @param string|null $usSize
     * @return string|null
     */
    protected function convertUsToEu($usSize)
    {
        if (!$usSize) {
            return null;
        }

        // Примерная конвертация для кроссовок
        $usToEu = [
            '4' => '36.5',
            '4.5' => '37',
            '5' => '37.5',
            '5.5' => '38',
            '6' => '38.5',
            '6.5' => '39',
            '7' => '40',
            '7.5' => '40.5',
            '8' => '41',
            '8.5' => '42',
            '9' => '42.5',
            '9.5' => '43',
            '10' => '44',
            '10.5' => '44.5',
            '11' => '45',
            '11.5' => '45.5',
            '12' => '46',
            '12.5' => '47',
            '13' => '47.5',
            '14' => '48.5',
            '15' => '49.5',
        ];

        return $usToEu[$usSize] ?? $usSize;
    }

    protected function parsePriceText($text)
    {
        $price = preg_replace('/[^0-9.]/', '', $text);
        return (float) $price;
    }

    protected function normalizeSeason($season)
    {
        if (!$season) return null;

        $seasonLower = mb_strtolower($season, 'UTF-8');

        if (strpos($seasonLower, 'summer') !== false) return 'summer';
        if (strpos($seasonLower, 'winter') !== false) return 'winter';
        if (strpos($seasonLower, 'spring') !== false || strpos($seasonLower, 'fall') !== false) return 'demi';

        return 'all';
    }

    protected function normalizeGender($gender)
    {
        if (!$gender) return null;

        $genderLower = mb_strtolower($gender, 'UTF-8');

        if (strpos($genderLower, 'men') !== false || strpos($genderLower, 'male') !== false) return 'male';
        if (strpos($genderLower, 'women') !== false || strpos($genderLower, 'female') !== false) return 'female';

        return 'unisex';
    }
}
