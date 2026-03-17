<?php

namespace app\backend\modules\admin\services\import;

use Symfony\Component\DomCrawler\Crawler;
use Yii;

/**
 * ZalandoParser — Парсер товаров с Zalando.com
 * 
 * Европейский маркетплейс, цены в EUR
 */
class ZalandoParser extends BaseParser
{
    /**
     * {@inheritdoc}
     */
    public function getSourceName()
    {
        return 'Zalando';
    }

    /**
     * Получить URL главной категории (обувь)
     * @return string
     */
    public function getMainCategoryUrl()
    {
        return 'https://www.zalando.com/shoes-women/';
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
            Yii::warning("CAPTCHA detected on Zalando: {$url}", 'import');
            $token = $this->solveCaptcha($html, $url);
            
            if ($token) {
                $html = $this->request($url, 'GET', [
                    'headers' => ['captcha-token' => $token],
                ]);
            }
        }

        // Zalando использует JSON-LD для данных о товаре
        $data = $this->extractJsonLd($html);
        
        if ($data) {
            return $data;
        }

        // Fallback: парсим HTML
        return $this->parseFromHtml($url, $html);
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
                    return $this->parseJsonLdProduct($data, $html);
                }
            }
        } catch (\Exception $e) {
            // Игнорируем
        }

        return null;
    }

    /**
     * Парсинг из JSON-LD
     * @param array $data
     * @param string $html
     * @return array
     */
    protected function parseJsonLdProduct($data, $html)
    {
        $crawler = new Crawler($html);
        
        $product = [
            'url' => $data['url'] ?? null,
            'name' => $data['name'] ?? '',
            'brand_name' => $data['brand']['name'] ?? null,
            'price' => (float) ($data['offers']['price'] ?? 0),
            'old_price' => $this->extractOldPrice($crawler),
            'description' => $data['description'] ?? null,
            'images' => [],
            'sizes' => [],
            'sku' => $data['sku'] ?? $data['mpn'] ?? null,
            'category_name' => $data['category'] ?? null,
            'characteristics' => [],
        ];

        // Извлекаем изображения
        if (isset($data['image'])) {
            $images = is_array($data['image']) ? $data['image'] : [$data['image']];
            $product['images'] = array_map(function($img) {
                // Увеличиваем размер
                return str_replace(['_1.jpg', '_2.jpg'], '_9.jpg', $img);
            }, $images);
        }

        // Извлекаем размеры из HTML
        $product['sizes'] = $this->extractSizes($crawler);

        // Извлекаем характеристики
        $product['characteristics'] = $this->extractCharacteristics($crawler);

        return $product;
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
            Yii::error("Zalando HTML parse error: " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseCategory($url, $page = 1)
    {
        // Zalando использует пагинацию в URL
        $urlWithPage = $url . '?p=' . $page;
        $html = $this->request($urlWithPage);
        
        if (!$html) {
            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        try {
            $crawler->filter('[data-testid="product-card"]')->each(function (Crawler $node) use (&$products) {
                $link = $node->filter('a');
                
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    
                    $products[] = [
                        'url' => 'https://www.zalando.com' . $href,
                        'name' => trim($node->filter('[data-testid="product-card-name"]')->text('')),
                        'brand' => trim($node->filter('[data-testid="product-card-brand"]')->text('')),
                        'price' => $this->parsePriceText($node->filter('[data-testid="product-card-price"]')->text('')),
                    ];
                }
            });
        } catch (\Exception $e) {
            Yii::error("Zalando category parse error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function searchProducts($query, $limit = 50)
    {
        $url = 'https://www.zalando.com/catalog/?q=' . urlencode($query);
        $html = $this->request($url);
        
        if (!$html) {
            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        try {
            $crawler->filter('[data-testid="product-card"]')->slice(0, $limit)->each(function (Crawler $node) use (&$products) {
                $link = $node->filter('a');
                
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    
                    $products[] = [
                        'url' => 'https://www.zalando.com' . $href,
                        'name' => trim($node->filter('[data-testid="product-card-name"]')->text('')),
                        'brand' => trim($node->filter('[data-testid="product-card-brand"]')->text('')),
                        'price' => $this->parsePriceText($node->filter('[data-testid="product-card-price"]')->text('')),
                    ];
                }
            });
        } catch (\Exception $e) {
            Yii::error("Zalando search error: " . $e->getMessage(), 'import');
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
            'category_name' => $rawData['category_name'] ?? null,
            'images' => $rawData['images'] ?? [],
            'sizes' => [],
        ];

        // Нормализуем характеристики
        if (!empty($rawData['characteristics'])) {
            $chars = $rawData['characteristics'];
            
            $normalized['material'] = $chars['Material'] ?? $chars['material'] ?? null;
            $normalized['season'] = $this->normalizeSeason($chars['Season'] ?? $chars['season'] ?? null);
            $normalized['gender'] = $this->normalizeGender($chars['Gender'] ?? $chars['gender'] ?? null);
            $normalized['country'] = $chars['Country of origin'] ?? $chars['country'] ?? null;
            $normalized['style_code'] = $chars['Article number'] ?? $chars['style_code'] ?? null;
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

    protected function extractName(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="product-title"]')->text(''));
        } catch (\Exception $e) {
            return $crawler->filter('h1')->text('');
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

    protected function extractPrice(Crawler $rawler)
    {
        try {
            $priceText = $crawler->filter('[data-testid="product-price"]')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function extractOldPrice(Crawler $crawler)
    {
        try {
            $priceText = $crawler->filter('[data-testid="original-price"]')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractDescription(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="product-description"]')->text(''));
        } catch (\Exception $e) {
            return null;
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

    protected function extractSizes(Crawler $crawler)
    {
        $sizes = [];

        try {
            $crawler->filter('[data-testid="size-option"]')->each(function (Crawler $node) use (&$sizes) {
                $sizeText = trim($node->text(''));
                
                if ($sizeText) {
                    $sizes[] = [
                        'size' => $sizeText,
                        'size_eu' => $sizeText,
                        'available' => !$node->hasClass('disabled'),
                    ];
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $sizes;
    }

    protected function extractSku(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('[data-testid="article-number"]')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractCategory(Crawler $crawler)
    {
        try {
            $categories = [];
            $crawler->filter('[data-testid="breadcrumb"] a')->each(function (Crawler $node) use (&$categories) {
                $categories[] = trim($node->text(''));
            });
            
            return end($categories) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractCharacteristics(Crawler $crawler)
    {
        $characteristics = [];

        try {
            $crawler->filter('[data-testid="product-characteristic"]')->each(function (Crawler $node) use (&$characteristics) {
                $label = trim($node->filter('[data-testid="characteristic-label"]')->text(''));
                $value = trim($node->filter('[data-testid="characteristic-value"]')->text(''));
                
                if ($label && $value) {
                    $characteristics[$label] = $value;
                }
            });
        } catch (\Exception $e) {
            // Игнорируем
        }

        return $characteristics;
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
        if (strpos($seasonLower, 'spring') !== false || strpos($seasonLower, 'autumn') !== false) return 'demi';

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
