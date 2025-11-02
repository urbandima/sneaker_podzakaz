<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Product;
use app\models\Brand;
use app\models\Category;

/**
 * Парсер товаров с poizonshop.ru
 */
class ParserController extends Controller
{
    private $baseUrl = 'https://poizonshop.ru';
    
    /**
     * Парсинг товаров с poizonshop.ru
     * Usage: php yii parser/poizon <limit>
     */
    public function actionPoizon($limit = 100)
    {
        $this->stdout("🔄 Начинаю парсинг товаров с poizonshop.ru...\n");
        $this->stdout("📦 Лимит: {$limit} товаров\n\n");

        // Получаем категорию "Кроссовки"
        $category = Category::findOne(['slug' => 'sneakers']);
        if (!$category) {
            $category = new Category();
            $category->name = 'Кроссовки';
            $category->slug = 'sneakers';
            $category->save();
            $this->stdout("✨ Создана категория: Кроссовки\n");
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $page = 1;
        
        while ($imported < $limit && $page <= 10) { // Максимум 10 страниц
            $this->stdout("\n📄 Страница {$page}...\n");
            
            $url = $this->baseUrl . '/sneakers?page=' . $page;
            $html = @file_get_contents($url);
            
            if (!$html) {
                $this->stderr("❌ Ошибка загрузки страницы {$page}\n");
                break;
            }

            // Парсим товары из HTML
            $products = $this->parseProducts($html);
            
            if (empty($products)) {
                $this->stdout("⚠️  Товары не найдены на странице {$page}\n");
                break;
            }

            $this->stdout("✅ Найдено товаров: " . count($products) . "\n");

            foreach ($products as $productData) {
                if ($imported >= $limit) {
                    break 2;
                }

                try {
                    // Проверяем существующий товар
                    $slug = $this->generateSlug($productData['name']);
                    $existing = Product::findOne(['slug' => $slug]);
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Определяем бренд
                    $brandName = $this->extractBrand($productData['name']);
                    $brand = $this->getOrCreateBrand($brandName);

                    // Создаём товар
                    $product = new Product();
                    $product->name = $productData['name'];
                    $product->slug = $slug;
                    $product->brand_id = $brand->id;
                    $product->category_id = $category->id;
                    $product->price = $productData['price'];
                    $product->old_price = isset($productData['old_price']) ? $productData['old_price'] : ($productData['price'] * 1.3);
                    $product->stock_status = 'in_stock';
                    $product->description = $productData['description'] ?? '';
                    $product->main_image = $productData['image'];
                    $product->gender = $this->detectGender($productData['name']);
                    $product->is_active = 1;
                    $product->is_featured = rand(0, 1);
                    $product->created_at = time();
                    $product->updated_at = time();

                    if ($product->save()) {
                        $imported++;
                        $this->stdout("✅ [{$imported}/{$limit}] {$productData['name']} ({$productData['price']} BYN)\n");
                    } else {
                        $errors++;
                        $this->stderr("❌ Ошибка: {$productData['name']}\n");
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->stderr("❌ Exception: {$e->getMessage()}\n");
                }
            }

            $page++;
            usleep(500000); // Пауза 0.5 сек между страницами
        }

        $this->stdout("\n" . str_repeat("=", 50) . "\n");
        $this->stdout("📊 РЕЗУЛЬТАТЫ ПАРСИНГА:\n");
        $this->stdout("✅ Импортировано: {$imported}\n");
        $this->stdout("⏭️  Пропущено: {$skipped}\n");
        $this->stdout("❌ Ошибок: {$errors}\n");
        $this->stdout(str_repeat("=", 50) . "\n");

        return ExitCode::OK;
    }

    /**
     * Парсит товары из HTML
     */
    private function parseProducts($html)
    {
        $products = [];
        
        // Используем DOMDocument для парсинга
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Ищем карточки товаров (нужно адаптировать под реальную структуру)
        // Попробуем разные варианты селекторов
        $selectors = [
            "//div[contains(@class, 'product-card')]",
            "//article[contains(@class, 'product')]",
            "//div[contains(@class, 'item')]//a[contains(@href, '/product/')]",
            "//a[contains(@href, '/product/')]",
        ];
        
        $nodes = null;
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                break;
            }
        }
        
        if (!$nodes || $nodes->length == 0) {
            return $products;
        }

        foreach ($nodes as $node) {
            try {
                $product = $this->extractProductData($node, $xpath);
                if ($product) {
                    $products[] = $product;
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки парсинга отдельных товаров
            }
        }

        return $products;
    }

    /**
     * Извлекает данные товара из узла
     */
    private function extractProductData($node, $xpath)
    {
        // Название
        $nameNode = $xpath->query(".//h3 | .//h2 | .//span[contains(@class, 'name')] | .//a[contains(@class, 'title')]", $node)->item(0);
        $name = $nameNode ? trim($nameNode->textContent) : null;

        // Цена
        $priceNode = $xpath->query(".//span[contains(@class, 'price')] | .//*[contains(@class, 'price')]", $node)->item(0);
        $priceText = $priceNode ? trim($priceNode->textContent) : '0';
        $price = $this->parsePrice($priceText);

        // Изображение
        $imgNode = $xpath->query(".//img", $node)->item(0);
        $image = $imgNode ? ($imgNode->getAttribute('src') ?: $imgNode->getAttribute('data-src')) : '/uploads/products/placeholder.jpg';

        // Ссылка
        if ($node->nodeName === 'a') {
            $link = $node->getAttribute('href');
        } else {
            $linkNode = $xpath->query(".//a", $node)->item(0);
            $link = $linkNode ? $linkNode->getAttribute('href') : null;
        }

        if (!$name || !$link) {
            return null;
        }

        // Конвертируем цену (если в рублях)
        if ($price > 1000) {
            $price = round($price / 35, 2); // RUB -> BYN
        }

        return [
            'name' => $name,
            'price' => $price ?: 100.00,
            'image' => $this->normalizeImageUrl($image),
            'link' => $this->baseUrl . $link,
            'description' => '',
        ];
    }

    /**
     * Парсит цену из текста
     */
    private function parsePrice($text)
    {
        $text = preg_replace('/[^0-9,.]/', '', $text);
        $text = str_replace(',', '.', $text);
        return (float) $text;
    }

    /**
     * Нормализует URL изображения
     */
    private function normalizeImageUrl($url)
    {
        if (empty($url) || $url === '/uploads/products/placeholder.jpg') {
            return $url;
        }

        if (strpos($url, 'http') === 0) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        if (strpos($url, '/') === 0) {
            return $this->baseUrl . $url;
        }

        return $url;
    }

    /**
     * Извлекает бренд из названия
     */
    private function extractBrand($name)
    {
        $brands = [
            'Nike', 'Adidas', 'New Balance', 'Puma', 'Reebok', 'Converse', 
            'Vans', 'Asics', 'Jordan', 'Yeezy', 'Salomon', 'Hoka', 
            'Brooks', 'Saucony', 'Mizuno', 'Under Armour', 'Fila'
        ];
        
        foreach ($brands as $brand) {
            if (stripos($name, $brand) !== false) {
                return $brand;
            }
        }

        return 'Other';
    }

    /**
     * Получает или создаёт бренд
     */
    private function getOrCreateBrand($brandName)
    {
        $brand = Brand::findOne(['name' => $brandName]);
        if (!$brand) {
            $brand = new Brand();
            $brand->name = $brandName;
            $brand->slug = strtolower($brandName);
            $brand->save();
            $this->stdout("✨ Создан бренд: {$brandName}\n");
        }
        return $brand;
    }

    /**
     * Генерирует slug из названия
     */
    private function generateSlug($name)
    {
        // Транслитерация
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $slug = mb_strtolower($name);
        $slug = strtr($slug, $converter);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 100);
        
        // Проверяем уникальность
        $originalSlug = $slug;
        $counter = 1;
        while (Product::findOne(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        return $slug;
    }

    /**
     * Определяет пол из названия
     */
    private function detectGender($name)
    {
        if (stripos($name, 'Wmns') !== false || stripos($name, 'Women') !== false || stripos($name, 'женские') !== false) {
            return 'female';
        }
        if (stripos($name, 'Men') !== false || stripos($name, 'мужские') !== false) {
            return 'male';
        }
        return 'unisex';
    }
}
