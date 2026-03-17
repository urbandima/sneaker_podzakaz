<?php

namespace app\backend\modules\admin\services\import;

use Symfony\Component\DomCrawler\Crawler;
use Yii;

/**
 * DewuParser — Парсер товаров с Dewu.com (Poizon)
 * 
 * Парсинг карточки товара, категорий, поиска
 * Китайский рынок, цены в CNY
 */
class DewuParser extends BaseParser
{
    /**
     * {@inheritdoc}
     */
    public function getSourceName()
    {
        return 'Dewu (Poizon)';
    }

    /**
     * Получить URL главной категории (обувь)
     * @return string
     */
    public function getMainCategoryUrl()
    {
        return 'https://www.dewu.com/category-sneakers';
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

        // Dewu использует JavaScript для рендеринга
        // Пробуем извлечь данные из JSON в странице
        $data = $this->extractJsonData($html);
        
        if ($data) {
            return $data;
        }

        // Fallback: парсим HTML
        return $this->parseFromHtml($url, $html);
    }

    /**
     * Извлечь JSON данные из страницы
     * @param string $html
     * @return array|null
     */
    protected function extractJsonData($html)
    {
        // Ищем JSON данные в script тегах
        if (preg_match('/window\.__INITIAL_STATE__\s*=\s*({.*?});/s', $html, $matches)) {
            try {
                $json = json_decode($matches[1], true);
                
                if ($json && isset($json['detail'])) {
                    return $this->parseJsonProduct($json['detail']);
                }
            } catch (\Exception $e) {
                Yii::error("Dewu JSON parse error: " . $e->getMessage(), 'import');
            }
        }

        return null;
    }

    /**
     * Парсинг из JSON данных
     * @param array $data
     * @return array
     */
    protected function parseJsonProduct($data)
    {
        $product = [
            'url' => $data['url'] ?? null,
            'name' => $data['title'] ?? $data['name'] ?? '',
            'brand_name' => $data['brandName'] ?? $data['brand'] ?? null,
            'price' => (float) ($data['price'] ?? $data['sellPrice'] ?? 0),
            'old_price' => isset($data['originalPrice']) ? (float) $data['originalPrice'] : null,
            'description' => $data['desc'] ?? $data['description'] ?? null,
            'images' => $data['images'] ?? $data['pics'] ?? [],
            'sizes' => [],
            'sku' => (string) ($data['spuId'] ?? $data['productId'] ?? $data['skuId'] ?? null),
            'category_name' => $data['categoryName'] ?? $data['category'] ?? null,
            'characteristics' => [],
        ];

        // Нормализуем изображения
        if (!empty($product['images'])) {
            $product['images'] = array_map(function($img) {
                if (strpos($img, 'http') !== 0) {
                    return 'https://img.dewu.com/' . ltrim($img, '/');
                }
                return $img;
            }, $product['images']);
        }

        // Извлекаем размеры
        if (isset($data['skuList']) && is_array($data['skuList'])) {
            foreach ($data['skuList'] as $sku) {
                $product['sizes'][] = [
                    'size' => $sku['size'] ?? $sku['sizeName'] ?? null,
                    'size_eu' => $this->convertChineseSize($sku['size'] ?? null),
                    'price' => (float) ($sku['price'] ?? $sku['sellPrice'] ?? $product['price']),
                    'available' => ($sku['stock'] ?? $sku['quantity'] ?? 0) > 0,
                ];
            }
        }

        // Извлекаем характеристики
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $attr) {
                $product['characteristics'][$attr['name']] = $attr['value'];
            }
        }

        return $product;
    }

    /**
     * Парсинг из HTML
     * @param string $url
     * @param string $html
     * @return array|null
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
                'images' => [],
                'sizes' => [],
                'sku' => $this->extractSku($url),
                'category_name' => null,
                'characteristics' => [],
            ];

            return $data;
        } catch (\Exception $e) {
            Yii::error("Dewu HTML parse error: " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseCategory($url, $page = 1)
    {
        // Dewu использует AJAX для загрузки товаров
        $apiUrl = str_replace('category-', 'api/category/', $url);
        $apiUrl .= '?page=' . $page;

        $response = $this->request($apiUrl);
        
        if (!$response) {
            return [];
        }

        $products = [];

        try {
            $data = json_decode($response, true);
            
            if (isset($data['list']) && is_array($data['list'])) {
                foreach ($data['list'] as $item) {
                    $products[] = [
                        'url' => 'https://www.dewu.com/product-' . ($item['productId'] ?? $item['spuId']),
                        'name' => $item['title'] ?? $item['name'] ?? '',
                        'brand' => $item['brandName'] ?? $item['brand'] ?? null,
                        'price' => (float) ($item['price'] ?? $item['sellPrice'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            Yii::error("Dewu category parse error: " . $e->getMessage(), 'import');
        }

        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function searchProducts($query, $limit = 50)
    {
        $apiUrl = 'https://www.dewu.com/api/search?keyword=' . urlencode($query) . '&size=' . $limit;
        
        $response = $this->request($apiUrl);
        
        if (!$response) {
            return [];
        }

        $products = [];

        try {
            $data = json_decode($response, true);
            
            if (isset($data['list']) && is_array($data['list'])) {
                foreach ($data['list'] as $item) {
                    $products[] = [
                        'url' => 'https://www.dewu.com/product-' . ($item['productId'] ?? $item['spuId']),
                        'name' => $item['title'] ?? $item['name'] ?? '',
                        'brand' => $item['brandName'] ?? $item['brand'] ?? null,
                        'price' => (float) ($item['price'] ?? $item['sellPrice'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
            Yii::error("Dewu search error: " . $e->getMessage(), 'import');
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
            
            $normalized['material'] = $chars['材质'] ?? $chars['material'] ?? null;
            $normalized['season'] = $this->normalizeSeason($chars['季节'] ?? $chars['season'] ?? null);
            $normalized['gender'] = $this->normalizeGender($chars['性别'] ?? $chars['gender'] ?? null);
            $normalized['country'] = $chars['产地'] ?? $chars['country'] ?? 'China';
            $normalized['style_code'] = $chars['货号'] ?? $chars['style_code'] ?? null;
        }

        // Нормализуем размеры
        if (!empty($rawData['sizes'])) {
            foreach ($rawData['sizes'] as $size) {
                $normalized['sizes'][] = [
                    'size' => $size['size'] ?? null,
                    'size_eu' => $size['size_eu'] ?? $this->convertChineseSize($size['size'] ?? null),
                    'price' => $size['price'] ?? $normalized['price'],
                    'is_available' => $size['available'] ?? true,
                ];
            }
        }

        return $normalized;
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    /**
     * Извлечь название из HTML
     */
    protected function extractName(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('.product-title, .goods-name, h1')->text(''));
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Извлечь бренд из HTML
     */
    protected function extractBrand(Crawler $crawler)
    {
        try {
            return trim($crawler->filter('.brand-name, .product-brand')->text(''));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлечь цену из HTML
     */
    protected function extractPrice(Crawler $crawler)
    {
        try {
            $priceText = $crawler->filter('.price, .product-price')->text('');
            return $this->parsePriceText($priceText);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Извлечь SKU из URL
     */
    protected function extractSku($url)
    {
        // Извлекаем ID из URL: /product-12345
        if (preg_match('/product-(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Конвертировать китайский размер в EU
     * @param string|null $chineseSize
     * @return string|null
     */
    protected function convertChineseSize($chineseSize)
    {
        if (!$chineseSize) {
            return null;
        }

        // Китайские размеры обуви примерно соответствуют EU
        // Китай 39 = EU 39, Китай 40 = EU 40 и т.д.
        // Для кроссовок обычно совпадают
        
        return $chineseSize;
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

        // Китайские названия
        if (strpos($seasonLower, '夏') !== false || strpos($seasonLower, 'summer') !== false) {
            return 'summer';
        }
        
        if (strpos($seasonLower, '冬') !== false || strpos($seasonLower, 'winter') !== false) {
            return 'winter';
        }
        
        if (strpos($seasonLower, '春秋') !== false || strpos($seasonLower, 'spring') !== false || strpos($seasonLower, 'autumn') !== false) {
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

        // Китайские названия
        if (strpos($genderLower, '男') !== false || strpos($genderLower, 'male') !== false) {
            return 'male';
        }
        
        if (strpos($genderLower, '女') !== false || strpos($genderLower, 'female') !== false) {
            return 'female';
        }

        return 'unisex';
    }
}
