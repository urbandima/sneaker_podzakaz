<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Product;
use app\models\ProductSize;
use app\models\ProductImage;
use app\models\Brand;
use app\models\Category;
use app\models\ImportBatch;
use app\models\ImportLog;
use app\components\PoizonApiService;

/**
 * Импорт товаров из Poizon/Dewu
 * 
 * Usage:
 *   php yii poizon-import/run [--limit=100]
 *   php yii poizon-import/update-prices
 *   php yii poizon-import/update-sizes
 */
class PoizonImportController extends Controller
{
    /**
     * @var int Лимит товаров за один запуск (0 = без лимита)
     */
    public $limit = 0;
    
    /**
     * @var bool Тестовый режим (не сохраняет в БД)
     */
    public $dryRun = false;
    
    /**
     * @var int ID пользователя, запустившего импорт
     */
    public $userId = null;
    
    /**
     * @var PoizonApiService
     */
    private $poizonApi;
    
    /**
     * @var ImportBatch
     */
    private $batch;

    /**
     * Options
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['limit', 'dryRun', 'userId']);
    }

    /**
     * Init
     */
    public function init()
    {
        parent::init();
        $this->poizonApi = Yii::$app->get('poizonApi');
    }

    /**
     * Полный импорт товаров из Poizon
     * 
     * @return int
     */
    public function actionRun()
    {
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  🚀 ИМПОРТ ТОВАРОВ ИЗ POIZON/DEWU                  ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::BOLD);
        $this->stdout("\n");
        
        if ($this->dryRun) {
            $this->stdout("⚠️  ТЕСТОВЫЙ РЕЖИМ (изменения не сохраняются)\n\n", \yii\helpers\Console::FG_YELLOW);
        }
        
        // Создаем batch
        $this->batch = new ImportBatch();
        $this->batch->source = ImportBatch::SOURCE_POIZON;
        $this->batch->type = ImportBatch::TYPE_FULL;
        $this->batch->status = ImportBatch::STATUS_PENDING;
        $this->batch->config = json_encode([
            'limit' => $this->limit,
            'dry_run' => $this->dryRun,
        ]);
        $this->batch->save(false);
        
        $this->batch->start();
        
        try {
            // Получаем товары из Poizon
            $this->stdout("📡 Загружаю данные из Poizon...\n");
            $result = $this->poizonApi->getPopularShoes([
                'limit' => $this->limit > 0 ? $this->limit : 10000,
            ]);
            
            if (isset($result['error'])) {
                throw new \Exception('Poizon API Error: ' . $result['error']);
            }
            
            $products = $result['items'] ?? [];
            $total = count($products);
            
            $this->stdout("✅ Получено товаров: {$total}\n\n");
            $this->batch->total_items = $total;
            $this->batch->save(false);
            
            if ($total === 0) {
                $this->stdout("⚠️  Товары не найдены\n", \yii\helpers\Console::FG_YELLOW);
                $this->batch->complete(true);
                return ExitCode::OK;
            }
            
            // Прогресс бар
            $this->stdout("🔄 Импортирую товары...\n\n");
            $processed = 0;
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;
            
            foreach ($products as $productData) {
                $processed++;
                
                try {
                    $result = $this->importProduct($productData);
                    
                    if ($result['status'] === 'created') {
                        $created++;
                        $this->stdout("✅ ", \yii\helpers\Console::FG_GREEN);
                    } elseif ($result['status'] === 'updated') {
                        $updated++;
                        $this->stdout("🔄 ", \yii\helpers\Console::FG_CYAN);
                    } else {
                        $skipped++;
                        $this->stdout("⏭️  ", \yii\helpers\Console::FG_YELLOW);
                    }
                    
                    // Прогресс
                    if ($processed % 50 == 0) {
                        $percent = round(($processed / $total) * 100, 1);
                        $this->stdout(" {$processed}/{$total} ({$percent}%)\n");
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->stdout("❌ ", \yii\helpers\Console::FG_RED);
                    
                    ImportLog::log(
                        $this->batch->id,
                        ImportLog::ACTION_ERROR,
                        'Ошибка импорта: ' . $e->getMessage(),
                        [
                            'poizon_id' => $productData['poizon_id'] ?? null,
                            'product_name' => $productData['name'] ?? null,
                            'error_details' => $e->getTraceAsString(),
                        ]
                    );
                }
            }
            
            $this->stdout("\n\n");
            
            // Обновляем статистику batch
            $this->batch->created_count = $created;
            $this->batch->updated_count = $updated;
            $this->batch->skipped_count = $skipped;
            $this->batch->error_count = $errors;
            $this->batch->summary = json_encode([
                'brands_processed' => $this->getBrandsProcessedCount(),
                'categories_processed' => $this->getCategoriesProcessedCount(),
            ]);
            $this->batch->complete(true);
            
            // Выводим итоги
            $this->printSummary($created, $updated, $skipped, $errors, $total);
            
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $this->stderr("\n❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            $this->stderr($e->getTraceAsString() . "\n");
            
            $this->batch->error_message = $e->getMessage();
            $this->batch->complete(false);
            
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Импортировать один товар
     * 
     * @param array $data
     * @return array ['status' => 'created'|'updated'|'skipped', 'product' => Product]
     */
    private function importProduct($data)
    {
        $poizonId = $data['poizon_id'];
        $sku = $this->generateSKU($data);
        
        // Ищем существующий товар по SKU или Poizon ID
        $product = Product::find()
            ->where(['sku' => $sku])
            ->orWhere(['poizon_id' => $poizonId])
            ->one();
        
        $isNew = !$product;
        if ($isNew) {
            $product = new Product();
        }
        
        // Базовые поля
        $product->name = $data['name'];
        $product->sku = $sku;
        $product->poizon_id = $poizonId;
        $product->poizon_spu_id = $data['spu_id'] ?? null;
        $product->poizon_url = $data['url'] ?? null;
        $product->poizon_price_cny = $data['price_cny'];
        $product->description = $data['description'] ?? '';
        
        // Цена (формула: CNY * курс * 1.5 + 40 BYN) с красивым округлением
        $currencyService = Yii::$app->currency;
        $product->price = $currencyService->calculatePoizonPriceByn($data['price_cny']);
        $product->old_price = null; // Без скидки, все товары по одной цене
        
        // Бренд
        $brand = $this->getOrCreateBrand($data['brand']);
        $product->brand_id = $brand->id;
        
        // Категория (все в "Кроссовки")
        $category = $this->getOrCreateCategory('Кроссовки', 'sneakers');
        $product->category_id = $category->id;
        
        // Статус: все товары "Под заказ"
        $product->stock_status = Product::STOCK_PREORDER;
        $product->is_active = 1;
        
        // Главное изображение (используем CDN Poizon)
        if (!empty($data['images'])) {
            $product->main_image = $data['images'][0];
        }
        
        // Характеристики обуви
        $this->setProductCharacteristics($product, $data);
        
        // Последняя синхронизация
        $product->last_sync_at = date('Y-m-d H:i:s');
        
        if ($this->dryRun) {
            // В тестовом режиме только валидируем
            if (!$product->validate()) {
                throw new \Exception('Validation error: ' . json_encode($product->errors));
            }
            $status = $isNew ? 'created' : 'updated';
        } else {
            // Сохраняем товар
            if (!$product->save()) {
                throw new \Exception('Save error: ' . json_encode($product->errors));
            }
            
            // Импортируем размеры
            if (!empty($data['sizes'])) {
                $this->importProductSizes($product, $data['sizes'], $data);
            }
            
            $status = $isNew ? 'created' : 'updated';
        }
        
        // Логируем
        ImportLog::log(
            $this->batch->id,
            $isNew ? ImportLog::ACTION_CREATED : ImportLog::ACTION_UPDATED,
            ($isNew ? 'Создан товар: ' : 'Обновлен товар: ') . $product->name,
            [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'poizon_id' => $product->poizon_id,
                'product_name' => $product->name,
                'data' => [
                    'price_byn' => $product->price,
                    'price_cny' => $product->poizon_price_cny,
                    'brand' => $brand->name,
                ],
            ]
        );
        
        return ['status' => $status, 'product' => $product];
    }

    /**
     * Установить характеристики обуви
     */
    private function setProductCharacteristics(Product $product, array $data)
    {
        $params = $data['params'] ?? [];
        
        // Материал верха
        if (isset($params['Материал верха']) || isset($params['Upper Material'])) {
            $product->upper_material = $params['Материал верха'] ?? $params['Upper Material'];
        }
        
        // Материал подошвы
        if (isset($params['Материал подошвы']) || isset($params['Sole Material'])) {
            $product->sole_material = $params['Материал подошвы'] ?? $params['Sole Material'];
        }
        
        // Цвет
        if (isset($params['Цвет']) || isset($params['Color'])) {
            $product->color_description = $params['Цвет'] ?? $params['Color'];
        }
        
        // Код модели
        if (isset($data['vendor_code']) || isset($params['Артикул'])) {
            $product->style_code = $data['vendor_code'] ?? $params['Артикул'];
        }
        
        // Год выпуска
        if (isset($params['Год выпуска']) || isset($params['Release Year'])) {
            $product->release_year = (int) ($params['Год выпуска'] ?? $params['Release Year']);
        }
        
        // Пол (определяем из названия)
        $product->gender = $this->detectGender($data['name']);
        
        // Сезон (определяем из названия/описания)
        $product->season = $this->detectSeason($data['name'], $data['description'] ?? '');
        
        // Высота (по умолчанию low)
        $product->height = $this->detectHeight($data['name']);
        
        // Вес
        if (isset($params['Вес']) || isset($params['Weight'])) {
            $weightStr = $params['Вес'] ?? $params['Weight'];
            $product->weight = (int) preg_replace('/[^0-9]/', '', $weightStr);
        }
    }

    /**
     * Импортировать размеры товара
     */
    private function importProductSizes(Product $product, array $sizes, array $productData)
    {
        foreach ($sizes as $sizeData) {
            // Если размер передан как строка
            if (is_string($sizeData)) {
                $sizeValue = $sizeData;
                $poizonSkuId = null;
                $stock = 1; // По умолчанию доступен
                $priceCny = $productData['price_cny'];
            } else {
                // Если передан массив с данными
                $sizeValue = $sizeData['size'] ?? $sizeData['value'];
                $poizonSkuId = $sizeData['sku_id'] ?? null;
                $stock = $sizeData['stock'] ?? 1;
                $priceCny = $sizeData['price_cny'] ?? $productData['price_cny'];
            }
            
            // Ищем существующий размер
            $productSize = ProductSize::find()
                ->where(['product_id' => $product->id])
                ->andWhere(['or', 
                    ['size' => $sizeValue],
                    ['poizon_sku_id' => $poizonSkuId]
                ])
                ->one();
            
            if (!$productSize) {
                $productSize = new ProductSize();
                $productSize->product_id = $product->id;
            }
            
            $productSize->size = $sizeValue;
            $productSize->poizon_sku_id = $poizonSkuId;
            $productSize->poizon_stock = $stock;
            $productSize->poizon_price_cny = $priceCny;
            $productSize->price_cny = $priceCny;
            $productSize->is_available = $stock > 0 ? 1 : 0;
            
            // КАЛЬКУЛЯЦИЯ ЦЕНЫ ДЛЯ КЛИЕНТА
            // Используем CurrencyService для расчета цены в BYN
            $currencyService = Yii::$app->currency;
            $productSize->price_byn = $currencyService->calculatePoizonPriceByn($priceCny);
            
            // Конвертируем размерные сетки
            $this->convertSizeGrids($productSize, $sizeValue, $product->gender);
            
            $productSize->save(false);
        }
    }

    /**
     * Конвертировать размерные сетки (US, EU, UK, CM)
     */
    private function convertSizeGrids(ProductSize $productSize, $sizeValue, $gender)
    {
        // Определяем исходную систему размеров
        // Если размер вида "42", "43" - это EU
        // Если "7.5", "8.5" - это US
        // Если "26", "27" - это CM
        
        if (is_numeric($sizeValue)) {
            if ($sizeValue >= 35 && $sizeValue <= 50) {
                // EU размер
                $productSize->eu_size = $sizeValue;
                $productSize->us_size = $this->poizonApi->convertSize($sizeValue, 'eu', 'us', $gender);
                $productSize->uk_size = $this->poizonApi->convertSize($sizeValue, 'eu', 'uk', $gender);
                $productSize->cm_size = $this->poizonApi->convertSize($sizeValue, 'eu', 'cm', $gender);
            } elseif ($sizeValue >= 20 && $sizeValue <= 34) {
                // CM размер
                $productSize->cm_size = $sizeValue;
                $productSize->us_size = $this->poizonApi->convertSize($sizeValue, 'cm', 'us', $gender);
                $productSize->eu_size = $this->poizonApi->convertSize($sizeValue, 'cm', 'eu', $gender);
                $productSize->uk_size = $this->poizonApi->convertSize($sizeValue, 'cm', 'uk', $gender);
            } elseif ($sizeValue >= 5 && $sizeValue <= 15) {
                // US размер
                $productSize->us_size = $sizeValue;
                $productSize->eu_size = $this->poizonApi->convertSize($sizeValue, 'us', 'eu', $gender);
                $productSize->uk_size = $this->poizonApi->convertSize($sizeValue, 'us', 'uk', $gender);
                $productSize->cm_size = $this->poizonApi->convertSize($sizeValue, 'us', 'cm', $gender);
            }
        }
    }

    /**
     * Генерация SKU
     */
    private function generateSKU($data)
    {
        // Формат: POIZON-{BRAND_CODE}-{STYLE_CODE}
        $brandCode = strtoupper(substr($data['brand'], 0, 3));
        $styleCode = $data['vendor_code'] ?? $data['poizon_id'];
        
        return "POIZON-{$brandCode}-{$styleCode}";
    }

    /**
     * Получить или создать бренд
     */
    private function getOrCreateBrand($brandName)
    {
        $brand = Brand::find()->where(['name' => $brandName])->one();
        
        if (!$brand) {
            $brand = new Brand();
            $brand->name = $brandName;
            $brand->slug = \yii\helpers\Inflector::slug($brandName);
            $brand->is_active = 1;
            $brand->created_at = time();
            $brand->save(false);
            
            $this->stdout("✨ Создан бренд: {$brandName}\n", \yii\helpers\Console::FG_CYAN);
        }
        
        return $brand;
    }

    /**
     * Получить или создать категорию
     */
    private function getOrCreateCategory($name, $slug)
    {
        $category = Category::find()->where(['slug' => $slug])->one();
        
        if (!$category) {
            $category = new Category();
            $category->name = $name;
            $category->slug = $slug;
            $category->is_active = 1;
            $category->created_at = time();
            $category->save(false);
            
            $this->stdout("✨ Создана категория: {$name}\n", \yii\helpers\Console::FG_CYAN);
        }
        
        return $category;
    }

    /**
     * Определить пол из названия
     */
    private function detectGender($name)
    {
        $nameLower = mb_strtolower($name);
        
        if (preg_match('/(wmns|women|女)/iu', $nameLower)) {
            return 'female';
        }
        if (preg_match('/(men|男)/iu', $nameLower)) {
            return 'male';
        }
        
        return 'unisex';
    }

    /**
     * Определить сезон
     */
    private function detectSeason($name, $description)
    {
        $text = mb_strtolower($name . ' ' . $description);
        
        if (preg_match('/(winter|зима|утеплен)/iu', $text)) {
            return 'winter';
        }
        if (preg_match('/(summer|лето|легк)/iu', $text)) {
            return 'summer';
        }
        if (preg_match('/(demi|демисезон|весна|осень)/iu', $text)) {
            return 'demi';
        }
        
        return 'all';
    }

    /**
     * Определить высоту
     */
    private function detectHeight($name)
    {
        $nameLower = mb_strtolower($name);
        
        if (preg_match('/(high|высок|hi)/iu', $nameLower)) {
            return 'high';
        }
        if (preg_match('/(mid|средн)/iu', $nameLower)) {
            return 'mid';
        }
        
        return 'low';
    }

    /**
     * Количество обработанных брендов
     */
    private function getBrandsProcessedCount()
    {
        return Brand::find()->count();
    }

    /**
     * Количество обработанных категорий
     */
    private function getCategoriesProcessedCount()
    {
        return Category::find()->count();
    }

    /**
     * Вывести итоги
     */
    private function printSummary($created, $updated, $skipped, $errors, $total)
    {
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  📊 ИТОГИ ИМПОРТА                                  ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╠══════════════════════════════════════════════════════╣\n", \yii\helpers\Console::BOLD);
        $this->stdout(sprintf("║  ✅ Создано:      %-32s ║\n", $created), \yii\helpers\Console::FG_GREEN);
        $this->stdout(sprintf("║  🔄 Обновлено:    %-32s ║\n", $updated), \yii\helpers\Console::FG_CYAN);
        $this->stdout(sprintf("║  ⏭️  Пропущено:   %-32s ║\n", $skipped), \yii\helpers\Console::FG_YELLOW);
        $this->stdout(sprintf("║  ❌ Ошибок:       %-32s ║\n", $errors), \yii\helpers\Console::FG_RED);
        $this->stdout("╠══════════════════════════════════════════════════════╣\n", \yii\helpers\Console::BOLD);
        $this->stdout(sprintf("║  📦 Всего:        %-32s ║\n", $total), \yii\helpers\Console::BOLD);
        $this->stdout(sprintf("║  ⏱️  Время:        %-32s ║\n", $this->batch->getFormattedDuration()), \yii\helpers\Console::BOLD);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::BOLD);
        $this->stdout("\n");
        
        if ($errors > 0) {
            $this->stdout("⚠️  Есть ошибки! Смотрите логи: php yii poizon-import/logs {$this->batch->id}\n", \yii\helpers\Console::FG_YELLOW);
        }
    }

    /**
     * Обновить только цены товаров
     * Пересчитывает price_byn для всех размеров на основе price_cny
     */
    public function actionUpdatePrices()
    {
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  🔄 ПЕРЕСЧЕТ ЦЕН ТОВАРОВ                           ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::BOLD);
        $this->stdout("\n");
        
        $currencyService = Yii::$app->currency;
        $currentRate = $currencyService->getCnyToBynRate();
        
        $this->stdout("📊 Текущий курс CNY → BYN: {$currentRate}\n");
        $this->stdout("📐 Формула: (CNY × курс × 1.5) + 40 BYN\n\n");
        
        // Пересчитываем цены товаров
        $products = Product::find()
            ->where(['not', ['poizon_price_cny' => null]])
            ->all();
        
        $productsUpdated = 0;
        foreach ($products as $product) {
            $oldPrice = $product->price;
            $product->price = $currencyService->calculatePoizonPriceByn($product->poizon_price_cny);
            
            if ($product->save(false, ['price'])) {
                $productsUpdated++;
                $this->stdout("✅ Товар #{$product->id}: {$oldPrice} → {$product->price} BYN\n");
            }
        }
        
        $this->stdout("\n");
        
        // Пересчитываем цены размеров
        $sizes = ProductSize::find()
            ->where(['not', ['price_cny' => null]])
            ->orWhere(['not', ['poizon_price_cny' => null]])
            ->all();
        
        $sizesUpdated = 0;
        foreach ($sizes as $size) {
            $priceCny = $size->price_cny ?? $size->poizon_price_cny;
            
            if ($priceCny) {
                $oldPrice = $size->price_byn;
                $size->price_byn = $currencyService->calculatePoizonPriceByn($priceCny);
                
                if ($size->save(false, ['price_byn'])) {
                    $sizesUpdated++;
                    $this->stdout("✅ Размер #{$size->id} (товар #{$size->product_id}): {$oldPrice} → {$size->price_byn} BYN\n");
                }
            }
        }
        
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::BOLD);
        $this->stdout("║  ✅ ПЕРЕСЧЕТ ЗАВЕРШЕН                              ║\n", \yii\helpers\Console::BOLD);
        $this->stdout("╠══════════════════════════════════════════════════════╣\n", \yii\helpers\Console::BOLD);
        $this->stdout(sprintf("║  Товаров обновлено:  %-29s ║\n", $productsUpdated), \yii\helpers\Console::FG_GREEN);
        $this->stdout(sprintf("║  Размеров обновлено: %-29s ║\n", $sizesUpdated), \yii\helpers\Console::FG_GREEN);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::BOLD);
        $this->stdout("\n");
        
        return ExitCode::OK;
    }

    /**
     * Обновить только размеры товаров
     */
    public function actionUpdateSizes()
    {
        $this->stdout("🔄 Обновление размеров товаров из Poizon...\n\n");
        
        // TODO: Реализовать обновление размеров
        $this->stdout("⚠️  В разработке\n", \yii\helpers\Console::FG_YELLOW);
        
        return ExitCode::OK;
    }

    /**
     * Показать логи импорта
     */
    public function actionLogs($batchId = null)
    {
        if ($batchId) {
            $batch = ImportBatch::findOne($batchId);
            if (!$batch) {
                $this->stderr("❌ Batch #{$batchId} не найден\n");
                return ExitCode::DATAERR;
            }
            
            $this->stdout("\n📋 Логи импорта #{$batch->id}\n");
            $this->stdout("Статус: {$batch->getStatusLabel()}\n");
            $this->stdout("Время: {$batch->started_at} - {$batch->finished_at}\n\n");
            
            $logs = ImportLog::find()
                ->where(['batch_id' => $batchId])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            
            foreach ($logs as $log) {
                $icon = $log->action === ImportLog::ACTION_ERROR ? '❌' : '✅';
                $this->stdout("{$icon} [{$log->created_at}] {$log->message}\n");
            }
        } else {
            // Показать последние батчи
            $batches = ImportBatch::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
            
            $this->stdout("\n📋 Последние импорты:\n\n");
            
            foreach ($batches as $batch) {
                $this->stdout("#{$batch->id} - {$batch->getStatusLabel()} - {$batch->created_at}\n");
                $this->stdout("  Создано: {$batch->created_count}, Обновлено: {$batch->updated_count}, Ошибок: {$batch->error_count}\n\n");
            }
        }
        
        return ExitCode::OK;
    }

    /**
     * Тест подключения к Poizon API
     */
    public function actionTest()
    {
        $this->stdout("🧪 Тестирование подключения к Poizon API...\n\n");
        
        $result = $this->poizonApi->testConnection();
        
        if ($result['success']) {
            $this->stdout("✅ Подключение успешно!\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("Найдено товаров: " . ($result['items_found'] ?? 0) . "\n");
        } else {
            $this->stderr("❌ Ошибка подключения: " . $result['message'] . "\n", \yii\helpers\Console::FG_RED);
        }
        
        return ExitCode::OK;
    }

    /**
     * Импорт товаров из файла (JSON, CSV, Excel)
     * 
     * @param string $file Путь к файлу импорта
     */
    public function actionFromFile($file)
    {
        if (!file_exists($file)) {
            $this->stderr("❌ Файл не найден: {$file}\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("📁 Импорт из файла: " . basename($file) . "\n\n");

        // Создаем batch
        $this->batch = new ImportBatch();
        $this->batch->source = ImportBatch::SOURCE_POIZON; // Источник: Poizon
        $this->batch->type = ImportBatch::TYPE_FULL; // Тип: полный импорт
        $this->batch->status = ImportBatch::STATUS_PROCESSING;
        $this->batch->started_at = date('Y-m-d H:i:s');
        $this->batch->created_by = $this->userId; // ID пользователя
        $this->batch->config = json_encode([
            'file' => basename($file),
            'format' => pathinfo($file, PATHINFO_EXTENSION),
            'import_type' => 'file_upload',
            'full_path' => $file
        ]);
        
        if (!$this->batch->save()) {
            $this->stderr("❌ Ошибка создания batch: " . json_encode($this->batch->errors) . "\n");
            return ExitCode::DATAERR;
        }
        
        $this->stdout("✅ Создан batch импорта #{$this->batch->id}\n");
        $this->stdout("   Пользователь: " . ($this->userId ?: 'system') . "\n\n");

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $products = [];

        try {
            switch (strtolower($extension)) {
                case 'json':
                    $products = $this->parseJsonFile($file);
                    break;
                case 'csv':
                    $products = $this->parseCsvFile($file);
                    break;
                case 'xlsx':
                case 'xls':
                    $products = $this->parseExcelFile($file);
                    break;
                default:
                    throw new \Exception("Неподдерживаемый формат файла: {$extension}");
            }

            $totalProducts = count($products);
            $this->stdout("📦 Найдено товаров в файле: {$totalProducts}\n\n");

            // Обновляем total_items в batch
            $this->batch->total_items = $totalProducts;
            $this->batch->save(false);

            $imported = 0;
            $updated = 0;
            $errors = 0;

            foreach ($products as $index => $productData) {
                try {
                    $result = $this->importProductFromData($productData);
                    
                    if ($result['created']) {
                        $imported++;
                        $this->stdout("✅ Импортирован: " . $productData['name'] . "\n");
                    } elseif ($result['updated']) {
                        $updated++;
                        $this->stdout("🔄 Обновлен: " . $productData['name'] . "\n");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->stderr("❌ Ошибка: " . $e->getMessage() . "\n");
                    
                    $log = new ImportLog();
                    $log->batch_id = $this->batch->id;
                    $log->action = ImportLog::ACTION_ERROR;
                    $log->message = "Ошибка импорта: " . $e->getMessage();
                    $log->details = json_encode($productData);
                    $log->save(false);
                }
            }

            // Обновляем статистику batch
            $this->batch->created_count = $imported;
            $this->batch->updated_count = $updated;
            $this->batch->error_count = $errors;
            $this->batch->status = ImportBatch::STATUS_COMPLETED;
            $this->batch->finished_at = date('Y-m-d H:i:s');
            
            // Считаем длительность
            if ($this->batch->started_at) {
                $start = strtotime($this->batch->started_at);
                $end = strtotime($this->batch->finished_at);
                $this->batch->duration_seconds = $end - $start;
            }
            
            // Создаем summary
            $this->batch->summary = json_encode([
                'total' => $totalProducts,
                'created' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'success_rate' => $totalProducts > 0 ? round((($imported + $updated) / $totalProducts) * 100, 1) : 0,
                'file' => basename($file),
                'format' => $extension
            ]);
            
            $this->batch->save(false);

            $this->stdout("\n✅ Импорт завершен!\n");
            $this->stdout("═══════════════════════════════════\n");
            $this->stdout("Batch ID: {$this->batch->id}\n");
            $this->stdout("Создано: {$imported}\n");
            $this->stdout("Обновлено: {$updated}\n");
            $this->stdout("Ошибок: {$errors}\n");
            $this->stdout("Длительность: " . $this->batch->getFormattedDuration() . "\n");
            $this->stdout("═══════════════════════════════════\n");

            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->batch->status = ImportBatch::STATUS_FAILED;
            $this->batch->error_message = $e->getMessage();
            $this->batch->finished_at = date('Y-m-d H:i:s');
            $this->batch->save(false);

            $this->stderr("❌ Ошибка импорта: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Парсинг JSON файла (поддержка формата Poizon)
     */
    private function parseJsonFile($file)
    {
        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Ошибка парсинга JSON: " . json_last_error_msg());
        }

        // Формат Poizon Export
        if (isset($data['products']) && isset($data['brands']) && isset($data['categories'])) {
            return $this->parsePoizonFormat($data);
        }

        // Обычный формат - массив товаров
        if (isset($data['products'])) {
            return $data['products'];
        }

        return is_array($data) ? $data : [$data];
    }

    /**
     * Парсинг формата Poizon Export
     */
    private function parsePoizonFormat($data)
    {
        $products = [];
        
        // Создаем маппинг брендов
        $brandsMap = [];
        if (isset($data['brands'])) {
            foreach ($data['brands'] as $brand) {
                $brandsMap[$brand['id']] = $brand['name'];
            }
        }

        // Создаем маппинг категорий
        $categoriesMap = [];
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $category) {
                $categoriesMap[$category['id']] = $category['name'];
            }
        }

        // Обрабатываем товары
        foreach ($data['products'] as $product) {
            // Извлекаем основные данные
            $productData = [
                'name' => $product['title'] ?? '',
                'sku' => $product['vendorCode'] ?? '',
                'poizon_id' => $product['productId'] ?? null,
                'description' => $product['description'] ?? '',
                'price' => $product['price'] ?? 0,
                'brand' => isset($product['vendorId']) && isset($brandsMap[$product['vendorId']]) 
                    ? $brandsMap[$product['vendorId']] 
                    : ($product['vendor'] ?? null),
                'category' => isset($product['categoryId']) && isset($categoriesMap[$product['categoryId']]) 
                    ? $categoriesMap[$product['categoryId']] 
                    : 'Кроссовки и кеды',
                'is_active' => 1,
                'images' => $product['images'] ?? [],
                'gender' => $product['gender'] ?? 'Унисекс',
            ];

            // Извлекаем характеристики из properties
            if (isset($product['properties']) && is_array($product['properties'])) {
                foreach ($product['properties'] as $prop) {
                    $key = $prop['key'] ?? '';
                    $value = $prop['value'] ?? '';

                    if ($key === 'Основной цвет' || $key === 'Комбинация') {
                        $productData['color'] = $value;
                    } elseif ($key === 'Идентификатор стиля') {
                        $productData['style_code'] = $value;
                    } elseif ($key === 'Материал верхней части') {
                        $productData['upper_material'] = $value;
                    } elseif ($key === 'Материал подошвы') {
                        $productData['sole_material'] = $value;
                    }
                }
            }

            // Извлекаем размеры из children
            $sizes = [];
            if (isset($product['children']) && is_array($product['children'])) {
                foreach ($product['children'] as $child) {
                    if (!isset($child['available']) || !$child['available']) {
                        continue; // Пропускаем недоступные размеры
                    }

                    $sizeData = [
                        'poizon_sku_id' => $child['variantId'] ?? null,
                        'poizon_price_cny' => $child['purchasePrice'] ?? 0,
                        'stock' => $child['count'] ?? 0,
                        'is_available' => $child['available'] ? 1 : 0,
                    ];

                    // Извлекаем размер из params
                    if (isset($child['params']) && is_array($child['params'])) {
                        foreach ($child['params'] as $param) {
                            if ($param['key'] === 'Размер') {
                                $sizeData['eu'] = $param['value'];
                                $sizeData['size'] = $param['value'];
                            }
                        }
                    }

                    if (isset($sizeData['size'])) {
                        $sizes[] = $sizeData;
                    }
                }
            }

            $productData['sizes'] = $sizes;
            $products[] = $productData;
        }

        return $products;
    }

    /**
     * Парсинг CSV файла
     */
    private function parseCsvFile($file)
    {
        $products = [];
        $header = null;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $products[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $products;
    }

    /**
     * Парсинг Excel файла (требует PhpSpreadsheet)
     */
    private function parseExcelFile($file)
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \Exception("PhpSpreadsheet не установлен. Используйте: composer require phpoffice/phpspreadsheet");
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $header = array_shift($rows);
        $products = [];

        foreach ($rows as $row) {
            if (!empty(array_filter($row))) {
                $products[] = array_combine($header, $row);
            }
        }

        return $products;
    }

    /**
     * Импорт товара из массива данных
     */
    private function importProductFromData($data)
    {
        $created = false;
        $updated = false;

        // Ищем товар по SKU или Poizon ID
        $product = null;
        if (!empty($data['sku'])) {
            $product = Product::findOne(['sku' => $data['sku']]);
        } elseif (!empty($data['poizon_id'])) {
            $product = Product::findOne(['poizon_id' => $data['poizon_id']]);
        }

        if (!$product) {
            $product = new Product();
            $created = true;
        } else {
            $updated = true;
        }

        // Заполняем поля
        $product->name = $data['name'] ?? $product->name;
        $product->slug = $data['slug'] ?? \yii\helpers\Inflector::slug($product->name);
        $product->sku = $data['sku'] ?? $product->sku ?? 'SKU-' . time();
        $product->description = $data['description'] ?? $product->description;
        
        // Цена: если есть price_cny - калькулируем, иначе берем из данных
        if (!empty($data['poizon_price_cny'])) {
            $currencyService = Yii::$app->currency;
            $product->price = $currencyService->calculatePoizonPriceByn($data['poizon_price_cny']);
        } else {
            $product->price = $data['price'] ?? $product->price ?? 0;
        }

        // Poizon поля
        if (!empty($data['poizon_id'])) {
            $product->poizon_id = $data['poizon_id'];
        }
        if (!empty($data['poizon_spu_id'])) {
            $product->poizon_spu_id = $data['poizon_spu_id'];
        }
        if (!empty($data['poizon_price_cny'])) {
            $product->poizon_price_cny = $data['poizon_price_cny'];
        }

        // Характеристики
        if (!empty($data['color'])) {
            $product->color_description = $data['color'];
        }
        if (!empty($data['style_code'])) {
            $product->style_code = $data['style_code'];
        }
        if (!empty($data['upper_material'])) {
            $product->upper_material = $data['upper_material'];
        }
        if (!empty($data['sole_material'])) {
            $product->sole_material = $data['sole_material'];
        }

        $product->is_active = $data['is_active'] ?? 1;

        // Бренд
        if (!empty($data['brand'])) {
            $brand = Brand::findOne(['name' => $data['brand']]);
            if (!$brand) {
                $brand = new Brand(['name' => $data['brand'], 'slug' => \yii\helpers\Inflector::slug($data['brand'])]);
                $brand->save(false);
            }
            $product->brand_id = $brand->id;
        }

        // Категория
        if (!empty($data['category'])) {
            $category = Category::findOne(['name' => $data['category']]);
            if (!$category) {
                $category = new Category(['name' => $data['category'], 'slug' => \yii\helpers\Inflector::slug($data['category'])]);
                $category->save(false);
            }
            $product->category_id = $category->id;
        }

        if (!$product->save()) {
            throw new \Exception("Ошибка сохранения товара: " . json_encode($product->errors));
        }

        // Импорт изображений, если есть
        if (!empty($data['images']) && is_array($data['images'])) {
            $this->importProductImages($product->id, $data['images']);
        }

        // Импорт размеров, если есть
        if (!empty($data['sizes']) && is_array($data['sizes'])) {
            foreach ($data['sizes'] as $sizeData) {
                $this->importProductSize($product->id, $sizeData);
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Импорт изображений товара
     */
    private function importProductImages($productId, $images)
    {
        // Удаляем старые изображения
        ProductImage::deleteAll(['product_id' => $productId]);

        $isFirst = true;
        foreach ($images as $imageUrl) {
            if (empty($imageUrl)) {
                continue;
            }

            $image = new ProductImage();
            $image->product_id = $productId;
            $image->image = $imageUrl;
            $image->is_main = $isFirst ? 1 : 0;
            $image->sort_order = $isFirst ? 0 : 100;
            $image->save(false);

            $isFirst = false;
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
     * Импорт размера товара
     */
    private function importProductSize($productId, $sizeData)
    {
        $size = ProductSize::findOne([
            'product_id' => $productId,
            'size' => $sizeData['size'] ?? $sizeData['us']
        ]);

        if (!$size) {
            $size = new ProductSize();
            $size->product_id = $productId;
        }

        $size->size = $sizeData['size'] ?? $sizeData['us'];
        $size->us_size = $sizeData['us'] ?? $sizeData['us_size'] ?? null;
        $size->eu_size = $sizeData['eu'] ?? $sizeData['eu_size'] ?? null;
        $size->uk_size = $sizeData['uk'] ?? $sizeData['uk_size'] ?? null;
        
        // ИСПРАВЛЕНИЕ: Нормализуем размер в см (265 → 26.5)
        $cmSize = $sizeData['cm'] ?? $sizeData['cm_size'] ?? null;
        $size->cm_size = $this->normalizeCmSize($cmSize);
        
        $size->stock = $sizeData['stock'] ?? 0;
        $size->is_available = $sizeData['is_available'] ?? 1;

        if (!empty($sizeData['poizon_sku_id'])) {
            $size->poizon_sku_id = $sizeData['poizon_sku_id'];
        }
        if (!empty($sizeData['poizon_price_cny'])) {
            $size->poizon_price_cny = $sizeData['poizon_price_cny'];
            $size->price_cny = $sizeData['poizon_price_cny'];
            
            // КАЛЬКУЛЯЦИЯ ЦЕНЫ ДЛЯ КЛИЕНТА
            $currencyService = Yii::$app->currency;
            $size->price_byn = $currencyService->calculatePoizonPriceByn($sizeData['poizon_price_cny']);
        }

        $size->save(false);
    }
}
