<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Product;
use app\models\Brand;
use app\models\Category;

/**
 * Импорт товаров из XML
 */
class ImportController extends Controller
{
    /**
     * Импорт товаров из DEWU/Poizon XML
     * Usage: php yii import/products <url>
     */
    public function actionProducts($url = 'https://s3.q-parser.ru/export/3539/668/4elk6gc/3539668--poizonshop.ru.xml')
    {
        $this->stdout("🔄 Начинаю импорт товаров из XML...\n");
        $this->stdout("📍 URL: {$url}\n\n");

        // Загружаем XML
        $xml = @file_get_contents($url);
        if (!$xml) {
            $this->stderr("❌ Ошибка загрузки XML!\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("✅ XML загружен (" . strlen($xml) . " байт)\n");

        // Парсим XML
        libxml_use_internal_errors(true);
        $xmlObj = simplexml_load_string($xml);
        if (!$xmlObj) {
            $this->stderr("❌ Ошибка парсинга XML!\n");
            foreach(libxml_get_errors() as $error) {
                $this->stderr("  - {$error->message}\n");
            }
            return ExitCode::DATAERR;
        }

        $offers = $xmlObj->shop->offers->offer;
        $totalOffers = count($offers);
        $this->stdout("📦 Найдено товаров: {$totalOffers}\n\n");

        // Получаем категорию "Кроссовки"
        $category = Category::findOne(['name' => 'Кроссовки']);
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
        $num = 0;

        foreach ($offers as $offer) {
            $num++;
            
            try {
                // Извлекаем данные
                $name = (string) $offer->name;
                $vendorCode = (string) $offer->vendorCode;
                $price = (float) $offer->price;
                $description = strip_tags((string) $offer->description);
                $imageUrl = (string) $offer->picture[0];

                // Определяем бренд из названия
                $brandName = $this->extractBrand($name);
                $brand = $this->getOrCreateBrand($brandName);

                // Проверяем существующий товар по slug (vendor_code может не быть в таблице)
                $slug = $this->generateSlug($name);
                $existing = Product::findOne(['slug' => $slug]);
                if ($existing) {
                    $skipped++;
                    $this->stdout("⏭️  [{$num}/{$totalOffers}] Пропущен: {$name} (уже есть)\n");
                    continue;
                }

                // Конвертируем цену: если в CNY - используем правильную формулу, иначе RUB → BYN
                // Определяем валюту по величине цены (CNY обычно 200-2000, RUB обычно > 5000)
                if ($price < 3000) {
                    // Скорее всего CNY - используем правильную калькуляцию
                    $priceInBYN = \app\models\CurrencySetting::convertFromCny($price, 'BYN');
                } else {
                    // Скорее всего RUB - конвертируем
                    $priceInBYN = round($price / 35, 2); // 1 BYN ≈ 35 RUB
                }

                // Определяем пол
                $gender = $this->detectGender($name);

                // Создаём товар
                $product = new Product();
                $product->name = $name;
                $product->slug = $slug;
                $product->brand_id = $brand->id;
                $product->category_id = $category->id;
                // $product->vendor_code = $vendorCode; // Колонки может не быть
                $product->price = $priceInBYN;
                $product->old_price = round($priceInBYN * 1.3, 2); // +30% для старой цены
                $product->stock_status = 'in_stock';
                $product->description = $description;
                $product->main_image = $this->downloadImage($imageUrl, $slug);
                $product->gender = $gender;
                $product->is_active = 1;
                $product->is_featured = rand(0, 1);
                $product->created_at = time();
                $product->updated_at = time();

                if ($product->save()) {
                    $imported++;
                    $this->stdout("✅ [{$num}/{$totalOffers}] Импортирован: {$name} ({$priceInBYN} BYN)\n");
                } else {
                    $errors++;
                    $this->stderr("❌ [{$num}/{$totalOffers}] Ошибка сохранения: {$name}\n");
                    foreach ($product->getErrors() as $attr => $errs) {
                        $this->stderr("   - {$attr}: " . implode(', ', $errs) . "\n");
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                $this->stderr("❌ [{$num}/{$totalOffers}] Exception: {$e->getMessage()}\n");
            }

            // Пауза чтобы не перегружать
            if ($num % 10 == 0) {
                usleep(100000); // 0.1 сек
            }
        }

        $this->stdout("\n" . str_repeat("=", 50) . "\n");
        $this->stdout("📊 РЕЗУЛЬТАТЫ ИМПОРТА:\n");
        $this->stdout("✅ Импортировано: {$imported}\n");
        $this->stdout("⏭️  Пропущено: {$skipped}\n");
        $this->stdout("❌ Ошибок: {$errors}\n");
        $this->stdout(str_repeat("=", 50) . "\n");

        return ExitCode::OK;
    }

    /**
     * Извлекает бренд из названия
     */
    private function extractBrand($name)
    {
        $brands = ['Nike', 'Adidas', 'New Balance', 'Puma', 'Reebok', 'Converse', 'Vans', 'Asics', 'Jordan', 'Yeezy'];
        
        foreach ($brands as $brand) {
            if (stripos($name, $brand) !== false) {
                return $brand;
            }
        }

        // По умолчанию
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
        $slug = strtolower($name);
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
        if (stripos($name, 'Wmns') !== false || stripos($name, 'Women') !== false) {
            return 'female';
        }
        if (stripos($name, 'Men') !== false) {
            return 'male';
        }
        return 'unisex';
    }

    /**
     * Скачивает изображение и сохраняет локально
     */
    private function downloadImage($url, $slug)
    {
        try {
            // Создаём директорию если нет
            $uploadDir = Yii::getAlias('@webroot/uploads/products');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Генерируем имя файла
            $extension = 'jpg';
            $filename = $slug . '-' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            // Скачиваем изображение
            $imageData = @file_get_contents($url);
            if ($imageData && file_put_contents($filepath, $imageData)) {
                return '/uploads/products/' . $filename;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки загрузки изображений
        }

        // Если не получилось - возвращаем placeholder
        return '/uploads/products/placeholder.jpg';
    }

    /**
     * Очистка всех товаров (осторожно!)
     */
    public function actionClear()
    {
        $this->stdout("⚠️  ВНИМАНИЕ! Будут удалены ВСЕ товары!\n");
        $this->stdout("Продолжить? (yes/no): ");
        $answer = trim(fgets(STDIN));

        if ($answer !== 'yes') {
            $this->stdout("❌ Отменено\n");
            return ExitCode::OK;
        }

        $count = Product::deleteAll();
        $this->stdout("✅ Удалено товаров: {$count}\n");

        return ExitCode::OK;
    }
}
