<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\ImportBatch;
use app\backend\modules\catalog\models\ImportLog;
use app\backend\shared\helpers\SlugHelper;
use app\backend\modules\catalog\models\ProductSizeImage;
use app\backend\modules\admin\models\CurrencySetting;
use app\backend\modules\catalog\models\Characteristic;
use app\backend\modules\catalog\models\CharacteristicValue;
use app\backend\modules\catalog\models\ProductCharacteristicValue;

/**
 * Импорт товаров из JSON файла Poizon
 * 
 * Usage:
 *   php yii poizon-import-json/run <json_url>
 *   php yii poizon-import-json/run https://storage.yandexcloud.net/.../export_04_11_2025__15_32_15.json
 */
class PoizonImportJsonController extends Controller
{
    public $defaultAction = 'run';
    
    /**
     * @var bool Детальный вывод (verbose mode)
     */
    public $verbose = false;
    
    private $stats = [
        'categories_created' => 0,
        'brands_created' => 0,
        'products_created' => 0,
        'products_updated' => 0,
        'variants_created' => 0,
        'images_created' => 0,
        'errors' => [],
    ];
    
    /**
     * @var ImportBatch Батч импорта
     */
    private $batch;
    
    /**
     * @var string Путь к файлу лога
     */
    private $logFile;
    
    /**
     * @var array Кэш брендов для быстрого поиска (name => Brand)
     */
    private $brandsCache = [];
    
    /**
     * @var array Кэш категорий для быстрого поиска (poizon_id => Category)
     */
    private $categoriesCache = [];
    
    /**
     * @var array Буфер размеров для батч-вставки
     */
    private $sizesBuffer = [];

    /**
     * Options
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['verbose']);
    }
    
    /**
     * Импорт из JSON файла
     * 
     * @param string $jsonUrl URL или путь к JSON файлу
     * @return int
     */
    public function actionRun($jsonUrl)
    {
        // Создаем файл лога
        $this->logFile = Yii::getAlias('@runtime/logs/poizon-import-' . date('Y-m-d_H-i-s') . '.log');
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Создаем батч импорта
        $this->batch = new ImportBatch();
        $this->batch->type = 'poizon_json';
        $this->batch->source = basename($jsonUrl);
        $this->batch->status = ImportBatch::STATUS_PROCESSING;
        $this->batch->started_at = date('Y-m-d H:i:s');
        $this->batch->save(false);
        
        $this->log("╔══════════════════════════════════════════════════════╗");
        $this->log("║  🚀 ИМПОРТ ТОВАРОВ ИЗ POIZON JSON                  ║");
        $this->log("╚══════════════════════════════════════════════════════╝");
        $this->log("");
        
        if ($this->verbose) {
            $this->stdout("💾 Лог сохраняется в: {$this->logFile}\n\n", \yii\helpers\Console::FG_CYAN);
        }
        
        $this->stdout("\n╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  🚀 ИМПОРТ ТОВАРОВ ИЗ POIZON JSON                  ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n\n", \yii\helpers\Console::BOLD);
        
        // Загружаем JSON
        $this->stdout("📡 Загружаю JSON из: $jsonUrl\n");
        $this->log("📡 Загружаю JSON из: $jsonUrl");
        $this->stdout("   Проверяю доступность...\n");
        
        // Проверка доступности файла/URL
        if (filter_var($jsonUrl, FILTER_VALIDATE_URL)) {
            // Это URL - проверяем доступность
            $this->stdout("   Тип: URL\n");
            $this->log("Проверка URL: $jsonUrl");
            
            $headers = @get_headers($jsonUrl);
            if (!$headers || strpos($headers[0], '200') === false) {
                $errorMsg = "URL недоступен или файл не найден: $jsonUrl";
                $this->stderr("❌ $errorMsg\n");
                $this->stderr("   Проверьте правильность ссылки и доступность сервера\n");
                $this->log("ОШИБКА: $errorMsg");
                if ($headers) {
                    $this->log("HTTP Response: " . $headers[0]);
                }
                return ExitCode::DATAERR;
            }
            
            $this->stdout("   ✅ URL доступен\n");
            $this->log("URL доступен: " . $headers[0]);
        } else {
            // Это локальный файл - проверяем существование
            $this->stdout("   Тип: Локальный файл\n");
            $this->log("Проверка локального файла: $jsonUrl");
            
            if (!file_exists($jsonUrl)) {
                $errorMsg = "Файл не найден: $jsonUrl";
                $this->stderr("❌ $errorMsg\n");
                $this->log("ОШИБКА: $errorMsg");
                return ExitCode::DATAERR;
            }
            
            $fileSize = filesize($jsonUrl);
            $this->stdout("   ✅ Файл найден (размер: " . $this->formatFileSize($fileSize) . ")\n");
            $this->log("Файл найден. Размер: $fileSize bytes");
        }
        
        // Загружаем с обработкой ошибок
        $json = @file_get_contents($jsonUrl);
        
        if ($json === false) {
            $error = error_get_last();
            $this->stderr("❌ Ошибка загрузки: " . ($error['message'] ?? 'Неизвестная ошибка') . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        if (empty($json)) {
            $errorMsg = "Файл пустой";
            $this->stderr("❌ $errorMsg\n");
            $this->log("ОШИБКА: $errorMsg");
            return ExitCode::DATAERR;
        }
        
        $jsonSize = strlen($json);
        $this->stdout("✅ JSON загружен (" . $this->formatFileSize($jsonSize) . ")\n");
        $this->log("JSON загружен. Размер: $jsonSize bytes");
        $this->stdout("   Парсинг JSON...\n");
        
        $data = json_decode($json, true);
        
        if (!$data) {
            $errorMsg = "Ошибка парсинга JSON: " . json_last_error_msg();
            $this->stderr("❌ $errorMsg\n");
            $this->log("ОШИБКА: $errorMsg");
            return ExitCode::DATAERR;
        }
        
        $this->stdout("✅ JSON успешно распарсен\n");
        $this->log("JSON распарсен успешно");
        
        // Проверяем структуру JSON
        $this->stdout("   Проверяю структуру JSON...\n");
        $categoriesCount = count($data['categories'] ?? []);
        $brandsCount = count($data['brands'] ?? []);
        $productsCount = count($data['products'] ?? []);
        
        $this->stdout("   📂 Категорий в JSON: $categoriesCount\n");
        $this->stdout("   🏷️  Брендов в JSON: $brandsCount\n");
        $this->stdout("   📦 Товаров в JSON: $productsCount\n");
        
        $this->log("Структура JSON: categories=$categoriesCount, brands=$brandsCount, products=$productsCount");
        
        if ($productsCount === 0) {
            $this->stderr("\n⚠️  ВНИМАНИЕ: В JSON файле нет товаров для импорта!\n", \yii\helpers\Console::FG_YELLOW);
            $this->log("ПРЕДУПРЕЖДЕНИЕ: Нет товаров в JSON");
        }
        
        $this->stdout("\n");
        
        // Импортируем категории
        if (!empty($data['categories'])) {
            $this->stdout("📂 Импортирую категории...\n");
            $this->log("Начинаю импорт категорий");
            
            try {
                $this->importCategories($data['categories']);
                $this->stdout("✅ Категорий создано: {$this->stats['categories_created']}\n\n");
                $this->log("Категории импортированы: {$this->stats['categories_created']}");
            } catch (\Exception $e) {
                $this->stderr("❌ Ошибка импорта категорий: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
                $this->log("ОШИБКА импорта категорий: " . $e->getMessage());
                $this->log($e->getTraceAsString());
            }
        } else {
            $this->stdout("⏭️  Категорий нет, пропускаю\n\n");
            $this->log("Категорий нет в JSON");
        }
        
        // Импортируем бренды
        if (!empty($data['brands'])) {
            $this->stdout("🏷️  Импортирую бренды...\n");
            $this->log("Начинаю импорт брендов");
            
            try {
                $this->importBrands($data['brands']);
                $this->stdout("✅ Брендов создано: {$this->stats['brands_created']}\n\n");
                $this->log("Бренды импортированы: {$this->stats['brands_created']}");
            } catch (\Exception $e) {
                $this->stderr("❌ Ошибка импорта брендов: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
                $this->log("ОШИБКА импорта брендов: " . $e->getMessage());
                $this->log($e->getTraceAsString());
            }
        } else {
            $this->stdout("⏭️  Брендов нет, пропускаю\n\n");
            $this->log("Брендов нет в JSON");
        }
        
        // Импортируем товары
        if (!empty($data['products'])) {
            $total = count($data['products']);
            $this->stdout("📦 Импортирую товары... (всего: $total)\n");
            $this->log("Начинаю импорт товаров. Всего: $total");
            
            // ОПТИМИЗАЦИЯ: Загружаем все бренды и категории в кэш для устранения N+1
            $this->stdout("⚡ Загружаю кэш брендов и категорий...\n");
            $this->initializeCaches();
            $this->stdout("   ✅ Загружено брендов: " . count($this->brandsCache) . ", категорий: " . count($this->categoriesCache) . "\n");
            
            if ($this->verbose) {
                $this->stdout("   Режим: VERBOSE (детальный вывод)\n");
            }
            $this->stdout("\n");
            
            foreach ($data['products'] as $index => $productData) {
                $productName = $productData['title'] ?? $productData['name'] ?? 'unknown';
                $productId = $productData['productId'] ?? $productData['id'] ?? 'N/A';
                
                try {
                    if ($this->verbose) {
                        $this->stdout("  🔄 [" . ($index + 1) . "/$total] Импортирую: $productName (ID: $productId)...\n");
                        $this->log("[" . ($index + 1) . "/$total] Импорт: $productName (ID: $productId)");
                    }
                    
                    $this->importProduct($productData);
                    
                    if ($this->verbose) {
                        $this->stdout("     ✅ Успешно (создано вариантов: {$this->stats['variants_created']})\n", \yii\helpers\Console::FG_GREEN);
                        $this->log("     Успешно импортирован");
                    }
                    
                    // Прогресс каждые 10 товаров (в не-verbose режиме)
                    if (!$this->verbose && ($index + 1) % 10 == 0) {
                        $percent = round((($index + 1) / $total) * 100, 1);
                        $this->stdout("  ⏳ Обработано: " . ($index + 1) . "/$total ($percent%)\n");
                    }
                    
                } catch (\Exception $e) {
                    $errorData = [
                        'index' => $index + 1,
                        'product' => $productName,
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'data' => $productData,
                    ];
                    
                    $this->stats['errors'][] = $errorData;
                    
                    // Записываем в ImportLog
                    if ($this->batch) {
                        $log = new ImportLog();
                        $log->batch_id = $this->batch->id;
                        $log->action = ImportLog::ACTION_ERROR;
                        $log->level = ImportLog::LEVEL_ERROR;
                        $log->product_name = $productName;
                        $log->poizon_id = (string)$productId;
                        $log->message = "Error importing {$productName} (ID: {$productId}): " . $e->getMessage();
                        $log->data = json_encode($productData, JSON_UNESCAPED_UNICODE);
                        $log->error_details = $e->getTraceAsString();
                        $log->save(false);
                    }
                    
                    // Вывод в консоль
                    $this->stderr("\n  ❌ ОШИБКА [" . ($index + 1) . "/$total]: $productName (ID: $productId)\n", \yii\helpers\Console::FG_RED);
                    $this->stderr("     " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
                    
                    if ($this->verbose) {
                        $this->stderr("     Trace: " . substr($e->getTraceAsString(), 0, 500) . "...\n", \yii\helpers\Console::FG_YELLOW);
                    }
                    
                    // Детальный лог в файл
                    $this->log("\n═══════════════════════════════════════════════════");
                    $this->log("ОШИБКА #" . count($this->stats['errors']) . ": $productName");
                    $this->log("═══════════════════════════════════════════════════");
                    $this->log("Product ID: $productId");
                    $this->log("Index: " . ($index + 1) . "/$total");
                    $this->log("Error: " . $e->getMessage());
                    $this->log("\nStack Trace:");
                    $this->log($e->getTraceAsString());
                    $this->log("\nProduct Data:");
                    $this->log(json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $this->log("");
                }
            }
        }
        
        // Финальная статистика
        $this->stdout("\n╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  📊 СТАТИСТИКА ИМПОРТА                              ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n\n", \yii\helpers\Console::BOLD);
        
        $this->stdout("✅ Категорий создано:        {$this->stats['categories_created']}\n");
        $this->stdout("✅ Брендов создано:          {$this->stats['brands_created']}\n");
        $this->stdout("✅ Товаров создано:          {$this->stats['products_created']}\n");
        $this->stdout("✅ Товаров обновлено:        {$this->stats['products_updated']}\n");
        $this->stdout("✅ Вариантов создано:        {$this->stats['variants_created']}\n");
        $this->stdout("✅ Изображений создано:      {$this->stats['images_created']}\n");
        
        if (!empty($this->stats['errors'])) {
            $errorCount = count($this->stats['errors']);
            $this->stdout("\n⚠️  Ошибок:                  $errorCount\n", \yii\helpers\Console::FG_YELLOW);
            
            $this->stdout("\n╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
            $this->stdout("║  ⚠️  ДЕТАЛИ ОШИБОК                                  ║\n", \yii\helpers\Console::BOLD);
            $this->stdout("╚══════════════════════════════════════════════════════╝\n\n", \yii\helpers\Console::BOLD);
            
            foreach ($this->stats['errors'] as $i => $error) {
                $num = $i + 1;
                $this->stderr("\n[$num] {$error['product']} (ID: {$error['product_id']})\n", \yii\helpers\Console::FG_RED);
                $this->stderr("    {$error['error']}\n", \yii\helpers\Console::FG_YELLOW);
            }
            
            $this->stdout("\n💾 Полный лог ошибок: {$this->logFile}\n", \yii\helpers\Console::FG_CYAN);
        }
        
        // Завершаем батч
        if ($this->batch) {
            $this->batch->status = empty($this->stats['errors']) ? ImportBatch::STATUS_COMPLETED : ImportBatch::STATUS_FAILED;
            $this->batch->finished_at = date('Y-m-d H:i:s');
            $this->batch->total_items = $this->stats['products_created'] + $this->stats['products_updated'];
            $this->batch->created_count = $this->stats['products_created'];
            $this->batch->updated_count = $this->stats['products_updated'];
            $this->batch->error_count = count($this->stats['errors']);
            $this->batch->summary = json_encode([
                'categories' => $this->stats['categories_created'],
                'brands' => $this->stats['brands_created'],
                'products_created' => $this->stats['products_created'],
                'products_updated' => $this->stats['products_updated'],
                'sizes' => $this->stats['variants_created'],
                'images' => $this->stats['images_created'],
                'errors' => count($this->stats['errors']),
            ], JSON_UNESCAPED_UNICODE);
            $this->batch->save(false);
        }
        
        $this->log("\n🎉 Импорт завершен!");
        $this->log("Лог сохранен: {$this->logFile}");
        
        $this->stdout("\n🎉 Импорт завершен!\n");
        if (!empty($this->stats['errors'])) {
            $this->stdout("💾 Детальный лог ошибок: {$this->logFile}\n", \yii\helpers\Console::FG_CYAN);
        }
        
        // Ссылка на дашборд
        if ($this->batch) {
            $this->stdout("📊 Дашборд: /admin/poizon-import\n", \yii\helpers\Console::FG_CYAN);
            $this->stdout("🔍 Просмотр батча: /admin/poizon-view?id={$this->batch->id}\n", \yii\helpers\Console::FG_CYAN);
        }
        $this->stdout("\n");
        
        return ExitCode::OK;
    }

    /**
     * Импорт категорий
     */
    private function importCategories($categories)
    {
        foreach ($categories as $catData) {
            $category = Category::find()
                ->where(['name' => $catData['name']])
                ->one();
            
            if (!$category) {
                $category = new Category();
                $category->name = $catData['name'];
                $category->slug = \yii\helpers\Inflector::slug($catData['name']);
                $category->is_active = 1;
                
                // Родительская категория
                if (!empty($catData['parentId'])) {
                    $parent = Category::find()
                        ->where(['poizon_id' => $catData['parentId']])
                        ->one();
                    if ($parent) {
                        $category->parent_id = $parent->id;
                    }
                }
                
                // Сохраняем Poizon ID если есть поле
                if (isset($catData['id'])) {
                    $category->poizon_id = $catData['id'];
                }
                
                if ($category->save()) {
                    $this->stats['categories_created']++;
                }
            }
        }
    }

    /**
     * Импорт брендов
     */
    private function importBrands($brands)
    {
        foreach ($brands as $brandData) {
            $brand = Brand::find()
                ->where(['name' => $brandData['name']])
                ->one();
            
            if (!$brand) {
                $brand = new Brand();
                $brand->name = $brandData['name'];
                $brand->slug = \yii\helpers\Inflector::slug($brandData['name']);
                $brand->is_active = 1;
                
                // Poizon ID
                if (isset($brandData['id'])) {
                    $brand->poizon_id = $brandData['id'];
                }
                
                // Logo URL из Poizon
                if (!empty($brandData['logoUrl'])) {
                    $brand->logo_url = $brandData['logoUrl'];
                }
                
                if ($brand->save()) {
                    $this->stats['brands_created']++;
                }
            } else {
                // Обновляем логотип для существующего бренда
                $updated = false;
                
                if (!empty($brandData['logoUrl']) && $brand->logo_url != $brandData['logoUrl']) {
                    $brand->logo_url = $brandData['logoUrl'];
                    $updated = true;
                }
                
                if (isset($brandData['id']) && $brand->poizon_id != $brandData['id']) {
                    $brand->poizon_id = $brandData['id'];
                    $updated = true;
                }
                
                if ($updated) {
                    $brand->save(false);
                }
            }
        }
    }

    /**
     * Импорт товара (родительский + варианты)
     */
    private function importProduct($data)
    {
        // Ищем существующий товар ТОЛЬКО по poizon_id (БЕЗ variantId!)
        // Один товар = один poizon_id, варианты = размеры через ProductSize
        $product = Product::find()
            ->where(['poizon_id' => $data['productId']])
            ->one();
        
        $isNew = !$product;
        
        if ($isNew) {
            $product = new Product();
            $this->stats['products_created']++;
        } else {
            $this->stats['products_updated']++;
        }
        
        // Основные поля
        $product->name = $data['title'];
        $product->description = $data['description'] ?? '';
        $product->vendor_code = $data['vendorCode'];
        $product->poizon_id = $data['productId'];
        // НЕ сохраняем poizon_variant_id в Product - это для размеров!
        $product->poizon_url = $data['url'];
        
        // Цены: калькулируем из CNY если доступно, иначе берем из данных
        if (!empty($data['purchasePrice'])) {
            // Используем purchasePrice (CNY) для калькуляции с правильным округлением
            $product->poizon_price_cny = $data['purchasePrice'];
            $product->price = CurrencySetting::convertFromCny($data['purchasePrice'], 'BYN');
        } else {
            // Фоллбэк на цену из данных (если нет purchasePrice)
            $product->price = $data['price'];
        }
        $product->old_price = null; // Можно вычислить если есть скидка
        
        // Бренд (ОПТИМИЗИРОВАНО: используем кэш вместо запроса к БД)
        if (!empty($data['vendor'])) {
            $brand = $this->brandsCache[$data['vendor']] ?? null;
            if ($brand) {
                $product->brand_id = $brand->id;
                $product->brand_name = $brand->name; // Денормализация
            }
        }
        
        // Категория (ОПТИМИЗИРОВАНО: используем кэш вместо запроса к БД)
        if (!empty($data['categoryId'])) {
            $category = $this->categoriesCache[$data['categoryId']] ?? null;
            if ($category) {
                $product->category_id = $category->id;
                $product->category_name = $category->name; // Денормализация
            }
        }
        
        // Изображения
        if (!empty($data['images'][0])) {
            $product->main_image = $data['images'][0];
            $product->main_image_url = $data['images'][0]; // Денормализация
        }
        
        // Дополнительные поля
        $product->country_of_origin = $data['countryOfOrigin'] ?? null;
        $product->favorite_count = $data['favoriteCount'] ?? 0;
        $product->gender = $this->mapGender($data['gender'] ?? null);
        $product->series_name = $data['seriesName'] ?? null;
        
        // НОВЫЕ ПОЛЯ - 100% покрытие
        $product->vat = $data['vat'] ?? null;
        $product->currency = $data['currency'] ?? 'BYN';
        $product->related_products_json = !empty($data['relatedProducts']) ? json_encode($data['relatedProducts'], JSON_UNESCAPED_UNICODE) : null;
        
        // JSON данные
        $product->properties = !empty($data['properties']) ? json_encode($data['properties'], JSON_UNESCAPED_UNICODE) : null;
        $product->sizes_data = !empty($data['sizes']) ? json_encode($data['sizes'], JSON_UNESCAPED_UNICODE) : null;
        $product->keywords = !empty($data['keywords']) ? json_encode($data['keywords'], JSON_UNESCAPED_UNICODE) : null;
        
        // Meta Keywords для SEO
        if (!empty($data['keywords'])) {
            $product->meta_keywords = is_array($data['keywords']) ? implode(', ', $data['keywords']) : $data['keywords'];
        }
        
        // Парсим characteristics из properties
        $this->parseProperties($product, $data['properties'] ?? []);
        
        // Статус
        $product->is_active = 1;
        $product->stock_status = Product::STOCK_PREORDER;
        $product->last_sync_at = date('Y-m-d H:i:s');
        
        if (!$product->save()) {
            $errors = $product->errors;
            $errorMessages = [];
            foreach ($errors as $field => $fieldErrors) {
                $errorMessages[] = "$field: " . implode(', ', $fieldErrors);
            }
            throw new \Exception('Ошибка сохранения товара: ' . implode('; ', $errorMessages));
        }
        
        // Сохраняем изображения
        if (!empty($data['images'])) {
            $this->importImages($product, $data['images']);
        }
        
        // Импортируем характеристики в новую систему справочников
        if (!empty($data['properties'])) {
            $this->importCharacteristicsToRegistry($product, $data['properties']);
        }
        
        // Импортируем размеры из children как ProductSize (с использованием sizes[])
        if (!empty($data['children'])) {
            $this->importSizes($product, $data['children'], $data['sizes'] ?? []);
        }
        
        // Обновляем product с delivery_time после импорта размеров
        $this->updateProductDeliveryTime($product, $data['children'] ?? []);
        
        return $product;
    }

    
    /**
     * Импорт характеристик в новую систему справочников
     */
    private function importCharacteristicsToRegistry($product, $properties)
    {
        // Удаляем старые связи
        ProductCharacteristicValue::deleteAll(['product_id' => $product->id]);
        
        // Маппинг ключей Poizon → наши характеристики
        $keyMapping = [
            'Тип закрытия' => 'fastening',
            'Высота голенища' => 'height',
            'Применимый сезон' => 'season',
            'Основной цвет' => 'color',
            'Материал верхней части' => 'upper_material',
            'Upper Material' => 'upper_material',
            'Материал подошвы' => 'sole_material',
            'Sole Material' => 'sole_material',
            'Идентификатор стиля' => 'style_code',
            'Style ID' => 'style_code',
            'Релиз Свидание' => 'release_year',
            'Release Date' => 'release_year',
        ];
        
        foreach ($properties as $prop) {
            $key = $prop['key'] ?? '';
            $value = $prop['value'] ?? '';
            
            if (empty($key) || empty($value)) {
                continue;
            }
            
            // Проверяем есть ли маппинг для этого ключа
            if (!isset($keyMapping[$key])) {
                continue; // Пропускаем неизвестные характеристики
            }
            
            $characteristicKey = $keyMapping[$key];
            
            // Находим характеристику в справочнике
            $characteristic = Characteristic::find()
                ->where(['key' => $characteristicKey])
                ->one();
            
            if (!$characteristic) {
                continue; // Если характеристика не найдена в справочнике
            }
            
            // Обработка в зависимости от типа характеристики
            if ($characteristic->type === Characteristic::TYPE_SELECT || 
                $characteristic->type === Characteristic::TYPE_MULTISELECT) {
                
                // Ищем или создаем значение в справочнике
                $characteristicValue = $this->findOrCreateCharacteristicValue($characteristic, $value);
                
                if ($characteristicValue) {
                    // Создаем связь товара с характеристикой
                    $pcv = new ProductCharacteristicValue();
                    $pcv->product_id = $product->id;
                    $pcv->characteristic_id = $characteristic->id;
                    $pcv->characteristic_value_id = $characteristicValue->id;
                    $pcv->save(false);
                }
                
            } elseif ($characteristic->type === Characteristic::TYPE_TEXT) {
                // Текстовые значения (например, style_code)
                $pcv = new ProductCharacteristicValue();
                $pcv->product_id = $product->id;
                $pcv->characteristic_id = $characteristic->id;
                $pcv->value_text = $value;
                $pcv->save(false);
                
            } elseif ($characteristic->type === Characteristic::TYPE_NUMBER) {
                // Числовые значения (например, release_year)
                $number = $this->extractYear($value);
                if ($number) {
                    $pcv = new ProductCharacteristicValue();
                    $pcv->product_id = $product->id;
                    $pcv->characteristic_id = $characteristic->id;
                    $pcv->value_number = $number;
                    $pcv->save(false);
                }
            }
        }
    }
    
    /**
     * Найти или создать значение характеристики в справочнике
     */
    private function findOrCreateCharacteristicValue($characteristic, $value)
    {
        // Нормализуем значение
        $normalizedValue = trim($value);
        
        // Специальный маппинг для некоторых значений
        $valueMapping = [
            // Застежка
            'Шнуровка' => 'laces',
            'Липучка' => 'velcro',
            'Молния' => 'zipper',
            'Слип-он' => 'slip_on',
            
            // Высота
            'Лоу-топы' => 'low',
            'Низкие' => 'low',
            'Мид-топы' => 'mid',
            'Средние' => 'mid',
            'Хай-топы' => 'high',
            'Высокие' => 'high',
        ];
        
        // Пытаемся найти по slug
        $slug = isset($valueMapping[$normalizedValue]) ? $valueMapping[$normalizedValue] : $this->slugify($normalizedValue);
        
        $characteristicValue = CharacteristicValue::find()
            ->where(['characteristic_id' => $characteristic->id])
            ->andWhere(['or', ['slug' => $slug], ['value' => $normalizedValue]])
            ->one();
        
        if (!$characteristicValue) {
            // Создаем новое значение
            $characteristicValue = new CharacteristicValue();
            $characteristicValue->characteristic_id = $characteristic->id;
            $characteristicValue->value = $normalizedValue;
            $characteristicValue->slug = $slug;
            $characteristicValue->is_active = 1;
            $characteristicValue->sort_order = CharacteristicValue::find()
                ->where(['characteristic_id' => $characteristic->id])
                ->max('sort_order') + 1;
            
            if ($characteristicValue->save()) {
                return $characteristicValue;
            }
        }
        
        return $characteristicValue;
    }
    
    /**
     * Обновление сроков доставки
     */
    private function updateProductDeliveryTime($product, $children)
    {
        if (empty($children)) {
            return;
        }
        
        $minTime = null;
        $maxTime = null;
        
        foreach ($children as $child) {
            if (!empty($child['timeDelivery'])) {
                $min = $child['timeDelivery']['min'] ?? null;
                $max = $child['timeDelivery']['max'] ?? null;
                
                if ($min !== null) {
                    $minTime = ($minTime === null) ? $min : min($minTime, $min);
                }
                if ($max !== null) {
                    $maxTime = ($maxTime === null) ? $max : max($maxTime, $max);
                }
            }
        }
        
        if ($minTime !== null || $maxTime !== null) {
            $product->delivery_time_min = $minTime;
            $product->delivery_time_max = $maxTime;
            $product->save(false);
        }
    }

    /**
     * Нормализация размера в см: 265 → 26.5, 270 → 27.0
     * Если размер >= 100, делим на 10
     * Валидация: допустимый диапазон 20-35 см
     */
    private function normalizeCmSize($cmSize)
    {
        if ($cmSize === null || $cmSize === '') {
            return null;
        }
        
        $cmSize = (float) $cmSize;
        
        // Если размер трехзначный (165, 265, 270) - делим на 10
        if ($cmSize >= 100) {
            $cmSize = $cmSize / 10;
        }
        
        // ВАЛИДАЦИЯ: Корректный диапазон размеров обуви в см: 20-35
        // Если размер выходит за пределы - возвращаем null (некорректные данные)
        if ($cmSize < 20 || $cmSize > 35) {
            \Yii::warning("Некорректный размер CM: {$cmSize}, пропускаем", 'import');
            return null;
        }
        
        return $cmSize;
    }

    /**
     * Импорт размеров товара через ProductSize (с использованием sizes[])
     * ОПТИМИЗИРОВАНО: накапливаем размеры в буфере и вставляем батчами
     */
    private function importSizes($product, $children, $sizes = [])
    {
        // Создаем lookup таблицу из sizes[]
        $sizesLookup = $this->buildSizesLookup($sizes);
        
        // Удаляем старые размеры товара (чтобы избежать дубликатов при обновлении)
        ProductSize::deleteAll(['product_id' => $product->id]);
        
        $sizesData = []; // Буфер для батч-вставки
        
        foreach ($children as $childData) {
            // Импортируем ВСЕ размеры (даже недоступные), чтобы показывать полный ассортимент
            // Флаг is_available будет установлен корректно ниже
            
            // Извлекаем размер и цвет из params
            $sizeValue = null;
            $colorValue = null;
            $euSize = null;
            $usSize = null;
            $ukSize = null;
            $cmSize = null;
            
            if (!empty($childData['params'])) {
                foreach ($childData['params'] as $param) {
                    $paramKey = mb_strtolower($param['key'] ?? $param['name'] ?? '');
                    $paramValue = $param['value'] ?? '';
                    
                    // Размер
                    if (strpos($paramKey, 'размер') !== false || strpos($paramKey, 'size') !== false) {
                        $sizeValue = $paramValue;
                        
                        // Парсим размер вида "EU 42 / US 8.5 / UK 7.5"
                        if (preg_match('/EU\s*([\d.]+)/', $paramValue, $matches)) {
                            $euSize = $matches[1];
                        }
                        if (preg_match('/US\s*([\d.]+)/', $paramValue, $matches)) {
                            $usSize = $matches[1];
                        }
                        if (preg_match('/UK\s*([\d.]+)/', $paramValue, $matches)) {
                            $ukSize = $matches[1];
                        }
                    }
                    
                    // Цвет
                    if (strpos($paramKey, 'цвет') !== false || strpos($paramKey, 'color') !== false) {
                        $colorValue = $paramValue;
                    }
                }
            }
            
            // Если размер не найден, используем title
            if (!$sizeValue && isset($childData['title'])) {
                if (preg_match('/(\d+\.?\d*)/', $childData['title'], $matches)) {
                    $sizeValue = $matches[1];
                    $usSize = $matches[1]; // По умолчанию считаем US
                }
            }
            
            // Используем lookup для заполнения всех систем размеров
            if ($sizeValue && isset($sizesLookup[$sizeValue])) {
                $euSize = $euSize ?: $sizesLookup[$sizeValue]['eu'];
                $usSize = $usSize ?: $sizesLookup[$sizeValue]['us'];
                $ukSize = $ukSize ?: $sizesLookup[$sizeValue]['uk'];
                $cmSize = $cmSize ?: $sizesLookup[$sizeValue]['cm'];
            }
            
            // ИСПРАВЛЕНИЕ: Нормализуем размер в см (265 → 26.5)
            $cmSize = $this->normalizeCmSize($cmSize);
            
            // Расчет цены BYN (ОБНОВЛЕНО: используем CurrencySetting)
            $priceCny = $childData['purchasePrice'] ?? null;
            $priceByn = null;
            if ($priceCny) {
                $priceByn = CurrencySetting::convertFromCny($priceCny, 'BYN');
            }
            
            // Формируем JSON изображений (для обратной совместимости)
            $imagesJson = null;
            $variantImages = [];
            if (!empty($childData['images']) && is_array($childData['images'])) {
                $variantImages = $childData['images'];
                $imagesJson = json_encode($variantImages, JSON_UNESCAPED_UNICODE);
            }
            
            // ОПТИМИЗИРОВАНО: Накапливаем данные для батч-вставки вместо save()
            $sizesData[] = [
                'poizon_sku_id' => (string)$childData['variantId'], // Для связки с изображениями
                'data' => [
                $product->id,                                    // product_id
                $sizeValue ?: 'Один размер',                    // size
                $colorValue,                                     // color
                $usSize,                                         // us_size
                $euSize,                                         // eu_size
                $ukSize,                                         // uk_size
                $cmSize,                                         // cm_size
                $childData['count'] ?? 0,                        // stock
                !empty($childData['available']) ? 1 : 0,        // is_available
                $childData['price'] ?? $product->price,         // price
                $priceCny,                                       // price_cny
                $priceByn,                                       // price_byn
                (string)$childData['variantId'],                // poizon_sku_id
                $childData['count'] ?? 0,                        // poizon_stock
                $priceCny,                                       // poizon_price_cny
                $colorValue,                                     // color_variant
                $childData['timeDelivery']['min'] ?? null,      // delivery_time_min
                $childData['timeDelivery']['max'] ?? null,      // delivery_time_max
                $childData['vendorCode'] ?? null,               // variant_vendor_code
                $imagesJson,                                     // images_json
                    date('Y-m-d H:i:s'),                            // created_at
                    date('Y-m-d H:i:s'),                            // updated_at
                    0,                                               // sort_order
                ],
                'images' => $variantImages, // Сохраняем для последующей обработки
            ];
            
            $this->stats['variants_created']++;
        }
        
        // БАТЧ-ВСТАВКА: вставляем все размеры одним запросом (ОГРОМНЫЙ буст производительности!)
        if (!empty($sizesData)) {
            try {
                // Извлекаем только data для batch insert
                $batchData = array_column($sizesData, 'data');
                
                Yii::$app->db->createCommand()->batchInsert(
                    'product_size',
                    [
                        'product_id', 'size', 'color', 'us_size', 'eu_size', 'uk_size', 'cm_size',
                        'stock', 'is_available', 'price', 'price_cny', 'price_byn',
                        'poizon_sku_id', 'poizon_stock', 'poizon_price_cny',
                        'color_variant', 'delivery_time_min', 'delivery_time_max',
                        'variant_vendor_code', 'images_json',
                        'created_at', 'updated_at', 'sort_order'
                    ],
                    $batchData
                )->execute();
                
                $this->log("Батч-вставка размеров: " . count($sizesData) . " шт для product_id={$product->id}");
                
                // АРХИТЕКТУРА: Импортируем изображения вариантов в product_size_image
                $this->importSizeImages($product->id, $sizesData);
                
            } catch (\Exception $e) {
                \Yii::error("Ошибка батч-вставки размеров для product {$product->id}: " . $e->getMessage(), __METHOD__);
            }
        }
    }
    
    /**
     * Импорт изображений вариантов в таблицу product_size_image
     * 
     * @param int $productId ID товара
     * @param array $sizesData Данные размеров с изображениями
     */
    private function importSizeImages($productId, $sizesData)
    {
        if (empty($sizesData)) {
            return;
        }
        
        $imagesBatch = [];
        $imagesCount = 0;
        
        foreach ($sizesData as $sizeData) {
            if (empty($sizeData['images'])) {
                continue;
            }
            
            // Находим созданный ProductSize по poizon_sku_id
            $poizonSkuId = $sizeData['poizon_sku_id'];
            $productSize = ProductSize::find()
                ->where(['product_id' => $productId, 'poizon_sku_id' => $poizonSkuId])
                ->one();
                
            if (!$productSize) {
                continue;
            }
            
            // Удаляем старые изображения этого размера
            ProductSizeImage::deleteAll(['product_size_id' => $productSize->id]);
            
            // Добавляем новые изображения
            foreach ($sizeData['images'] as $index => $imageUrl) {
                $imagesBatch[] = [
                    $productSize->id,           // product_size_id
                    $imageUrl,                  // image_url
                    $index,                     // sort_order
                    $index === 0 ? 1 : 0,      // is_main (первое главное)
                    date('Y-m-d H:i:s'),       // created_at
                    date('Y-m-d H:i:s'),       // updated_at
                ];
                $imagesCount++;
            }
        }
        
        // Батч-вставка изображений
        if (!empty($imagesBatch)) {
            try {
                Yii::$app->db->createCommand()->batchInsert(
                    'product_size_image',
                    ['product_size_id', 'image_url', 'sort_order', 'is_main', 'created_at', 'updated_at'],
                    $imagesBatch
                )->execute();
                
                $this->log("Импортировано изображений вариантов: $imagesCount для product_id=$productId");
            } catch (\Exception $e) {
                \Yii::error("Ошибка импорта изображений вариантов для product $productId: " . $e->getMessage(), __METHOD__);
            }
        }
    }

    /**
     * Импорт изображений
     */
    private function importImages($product, $images)
    {
        // Удаляем старые
        ProductImage::deleteAll(['product_id' => $product->id]);
        
        $sortOrder = 0;
        foreach ($images as $imageUrl) {
            if (empty($imageUrl)) continue;
            
            $image = new ProductImage();
            $image->product_id = $product->id;
            $image->image = $imageUrl; // Используем поле image, а не image_url
            $image->is_main = ($sortOrder === 0) ? 1 : 0;
            $image->sort_order = $sortOrder++;
            $image->created_at = date('Y-m-d H:i:s');
            
            if ($image->save(false)) { // save(false) - без валидации
                $this->stats['images_created']++;
            }
        }
    }

    /**
     * Парсинг характеристик из properties
     */
    private function parseProperties($product, $properties)
    {
        foreach ($properties as $prop) {
            $key = $prop['key'] ?? '';
            $value = $prop['value'] ?? '';
            
            switch ($key) {
                case 'Тип закрытия':
                    $product->fastening = $this->mapFastening($value);
                    break;
                    
                case 'Высота голенища':
                    $product->height = $this->mapHeight($value);
                    break;
                    
                case 'Применимый сезон':
                    $product->season = $this->mapSeason($value);
                    break;
                    
                case 'Основной цвет':
                    $product->color_description = $value;
                    break;
                    
                case 'Релиз Свидание':
                case 'Release Date':
                    $product->release_year = $this->extractYear($value);
                    break;
                    
                case 'Идентификатор стиля':
                case 'Style ID':
                    $product->style_code = $value;
                    break;
                    
                case 'Материал верхней части':
                case 'Upper Material':
                    $product->upper_material = $value;
                    break;
                    
                case 'Материал подошвы':
                case 'Sole Material':
                    $product->sole_material = $value;
                    break;
            }
        }
    }

    /**
     * Маппинг пола
     */
    private function mapGender($gender)
    {
        $map = [
            'Унисекс' => 'unisex',
            'Мужской' => 'male',
            'Женский' => 'female',
        ];
        
        return $map[$gender] ?? 'unisex';
    }

    /**
     * Маппинг застежки
     */
    private function mapFastening($value)
    {
        if (stripos($value, 'Шнуровка') !== false) return 'laces';
        if (stripos($value, 'Липучка') !== false) return 'velcro';
        if (stripos($value, 'Молния') !== false) return 'zipper';
        if (stripos($value, 'Слип') !== false) return 'slip_on';
        return 'laces';
    }

    /**
     * Маппинг высоты
     */
    private function mapHeight($value)
    {
        if (stripos($value, 'Лоу') !== false || stripos($value, 'Low') !== false) return 'low';
        if (stripos($value, 'Мид') !== false || stripos($value, 'Mid') !== false) return 'mid';
        if (stripos($value, 'Хай') !== false || stripos($value, 'High') !== false) return 'high';
        return 'low';
    }

    /**
     * Маппинг сезона
     */
    private function mapSeason($value)
    {
        if (stripos($value, 'Весна') !== false && stripos($value, 'Осен') !== false) return 'demi';
        if (stripos($value, 'Зим') !== false) return 'winter';
        if (stripos($value, 'Лето') !== false) return 'summer';
        return 'all';
    }

    /**
     * Извлечь год из строки
     */
    private function extractYear($value)
    {
        if (preg_match('/(\d{4})/', $value, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d{2})\/\d{2}\/(\d{4})/', $value, $matches)) {
            return (int) $matches[2];
        }
        return null;
    }
    
    /**
     * Построение lookup таблицы размеров из sizes[]
     */
    private function buildSizesLookup($sizes)
    {
        if (empty($sizes)) {
            return [];
        }
        
        $euSizes = [];
        $usSizes = [];
        $ukSizes = [];
        $cmSizes = [];
        
        foreach ($sizes as $sizeGrid) {
            $name = mb_strtolower($sizeGrid['name'] ?? '');
            $delimiter = $sizeGrid['delimiter'] ?? ',';
            $value = $sizeGrid['value'] ?? '';
            
            $values = array_map('trim', explode($delimiter, $value));
            
            if (strpos($name, 'европ') !== false || strpos($name, 'eu') !== false) {
                $euSizes = $values;
            } elseif (strpos($name, 'сша') !== false || strpos($name, 'us') !== false || strpos($name, 'америк') !== false) {
                $usSizes = $values;
            } elseif (strpos($name, 'англ') !== false || strpos($name, 'uk') !== false || strpos($name, 'британ') !== false) {
                $ukSizes = $values;
            } elseif (strpos($name, 'китай') !== false || strpos($name, 'chn') !== false || strpos($name, 'длин') !== false || strpos($name, 'см') !== false) {
                $cmSizes = $values;
            }
        }
        
        // Создаем карту: размер → все системы
        $lookup = [];
        $count = max(count($euSizes), count($usSizes), count($ukSizes), count($cmSizes));
        
        for ($i = 0; $i < $count; $i++) {
            // Используем US или EU как ключ
            $key = $usSizes[$i] ?? $euSizes[$i] ?? $cmSizes[$i] ?? $i;
            
            $lookup[$key] = [
                'eu' => $euSizes[$i] ?? null,
                'us' => $usSizes[$i] ?? null,
                'uk' => $ukSizes[$i] ?? null,
                'cm' => $cmSizes[$i] ?? null,
            ];
            
            // Добавляем также по EU ключу, если отличается
            if (isset($euSizes[$i]) && $euSizes[$i] != $key) {
                $lookup[$euSizes[$i]] = $lookup[$key];
            }
            
            // И по CM ключу
            if (isset($cmSizes[$i]) && $cmSizes[$i] != $key) {
                $lookup[$cmSizes[$i]] = $lookup[$key];
            }
        }
        
        return $lookup;
    }
    
    /**
     * Создание slug из строки
     */
    private function slugify($text)
    {
        return SlugHelper::slugify($text);
    }
    
    /**
     * Логирование в файл и консоль
     */
    private function log($message)
    {
        // Добавляем timestamp
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        
        // Запись в файл
        if ($this->logFile) {
            file_put_contents($this->logFile, $logMessage . "\n", FILE_APPEND);
        }
        
        // Вывод в консоль если verbose
        if ($this->verbose) {
            $this->stdout($message . "\n");
        }
    }
    
    /**
     * Инициализация кэшей брендов и категорий (устранение N+1)
     */
    private function initializeCaches()
    {
        // Загружаем все бренды в память
        $brands = Brand::find()->all();
        foreach ($brands as $brand) {
            $this->brandsCache[$brand->name] = $brand;
        }
        
        // Загружаем все категории в память
        $categories = Category::find()->all();
        foreach ($categories as $category) {
            if ($category->poizon_id) {
                $this->categoriesCache[$category->poizon_id] = $category;
            }
        }
        
        $this->log("Кэш инициализирован: brands=" . count($this->brandsCache) . ", categories=" . count($this->categoriesCache));
    }

    /**
     * Форматирование размера файла
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
