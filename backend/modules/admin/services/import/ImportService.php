<?php

namespace app\backend\modules\admin\services\import;

use Yii;
use yii\base\Component;
use app\backend\modules\admin\models\import\ImportSource;
use app\backend\modules\admin\models\import\ImportTask;
use app\backend\modules\admin\models\import\ImportLog;
use app\backend\modules\admin\models\import\ImportProductPrice;
use app\backend\modules\admin\models\import\ImportCategoryMap;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;

/**
 * ImportService — Основной сервис импорта товаров
 * 
 * Управление процессом импорта, обработка товаров, сопоставление категорий
 */
class ImportService extends Component
{
    /**
     * Сервисы
     */
    public $proxyService;
    public $captchaService;
    public $currencyService;

    /**
     * Парсеры по источникам
     */
    private $parsers = [];

    /**
     * Запустить импорт из источника
     * @param int $sourceId ID источника
     * @param array $config Конфигурация импорта
     * @return ImportTask
     */
    public function runImport($sourceId, array $config = [])
    {
        $source = ImportSource::findOne($sourceId);
        
        if (!$source || !$source->is_active) {
            throw new \Exception("Источник не найден или неактивен");
        }

        // Создаем задачу
        $task = new ImportTask([
            'source_id' => $sourceId,
            'config' => json_encode($config),
            'created_by' => Yii::$app->user->id ?? null,
        ]);
        $task->save(false);

        try {
            // Запускаем задачу
            $task->start();

            // Инициализируем парсер
            $parser = $this->getParser($source);
            
            // Получаем список товаров для импорта
            $productUrls = $this->getProductUrls($parser, $config);
            $task->total_products = count($productUrls);
            $task->save(false, ['total_products']);

            // Обрабатываем каждый товар
            foreach ($productUrls as $url) {
                $this->processProduct($task, $parser, $url);
                $task->incrementProcessed();
                
                // Задержка между запросами
                $parser->delay();
            }

            // Завершаем задачу
            $task->complete();
            
            // Создаем уведомление об успехе
            \app\backend\modules\admin\models\import\ImportNotification::createSuccess($task);
        } catch (\Exception $e) {
            $task->fail($e->getMessage());
            Yii::error("Import failed: " . $e->getMessage(), 'import');
            
            // Создаем уведомление об ошибке
            \app\backend\modules\admin\models\import\ImportNotification::createError($task);
        }

        return $task;
    }

    /**
     * Получить парсер для источника
     * @param ImportSource $source
     * @return BaseParser
     */
    protected function getParser(ImportSource $source)
    {
        $code = $source->code;
        
        if (!isset($this->parsers[$code])) {
            $parserClass = $this->getParserClass($code);
            $parser = new $parserClass($source);
            
            // Устанавливаем сервисы
            if ($this->proxyService) {
                $parser->setProxyService($this->proxyService);
            }
            
            if ($this->captchaService) {
                $this->captchaService->configureFromSource($source);
                $parser->setCaptchaService($this->captchaService);
            }
            
            $this->parsers[$code] = $parser;
        }

        return $this->parsers[$code];
    }

    /**
     * Получить класс парсера по коду источника
     * @param string $code
     * @return string
     */
    protected function getParserClass($code)
    {
        return match($code) {
            'lamoda' => LamodaParser::class,
            'dewu' => DewuParser::class,
            'zalando' => ZalandoParser::class,
            'stockx' => StockXParser::class,
            default => throw new \Exception("Unknown source: {$code}"),
        };
    }

    /**
     * Получить список URL товаров для импорта
     * @param BaseParser $parser
     * @param array $config
     * @return array
     */
    protected function getProductUrls($parser, array $config)
    {
        // Если указаны конкретные URL
        if (!empty($config['urls'])) {
            return $config['urls'];
        }

        // Если указаны категории
        if (!empty($config['categories'])) {
            $urls = [];
            foreach ($config['categories'] as $categoryUrl) {
                $urls = array_merge($urls, $parser->getProductUrls($categoryUrl));
            }
            return $urls;
        }

        // Если указан поисковый запрос
        if (!empty($config['search'])) {
            $products = $parser->searchProducts($config['search'], $config['limit'] ?? 50);
            return array_column($products, 'url');
        }

        // По умолчанию - все товары из главной категории
        return $parser->getProductUrls($parser->getMainCategoryUrl());
    }

    /**
     * Обработать один товар
     * @param ImportTask $task
     * @param BaseParser $parser
     * @param string $url
     */
    protected function processProduct($task, $parser, $url)
    {
        try {
            // Парсим товар
            $data = $parser->parseProduct($url);
            
            if (!$data) {
                $task->incrementFailed();
                ImportLog::logError($task->id, null, null, "Failed to parse product", ['url' => $url]);
                return;
            }

            // Нормализуем данные
            $normalizedData = $parser->normalizeProductData($data);
            
            // Конвертируем цены
            if ($this->currencyService) {
                $normalizedData = $this->convertPrices($normalizedData, $task->source);
            }

            // Сопоставляем категорию
            $categoryId = $this->matchCategory($task->source_id, $normalizedData['category_name'] ?? null);
            
            if ($categoryId) {
                $normalizedData['category_id'] = $categoryId;
            }

            // Ищем или создаем бренд
            $brandId = $this->findOrCreateBrand($normalizedData['brand_name'] ?? null);
            
            if ($brandId) {
                $normalizedData['brand_id'] = $brandId;
            }

            // Проверяем дубликат
            $existingProduct = $this->findDuplicate($normalizedData['sku'] ?? null, $normalizedData['sizes'] ?? []);

            if ($existingProduct) {
                // Обновляем существующий товар
                $this->updateProduct($task, $existingProduct, $normalizedData, $task->source);
            } else {
                // Создаем новый товар
                $this->createProduct($task, $normalizedData, $task->source);
            }
        } catch (\Exception $e) {
            $task->incrementFailed();
            ImportLog::logError($task->id, null, null, $e->getMessage(), ['url' => $url]);
            Yii::error("Product processing error: " . $e->getMessage(), 'import');
        }
    }

    /**
     * Конвертировать цены в BYN
     * @param array $data
     * @param ImportSource $source
     * @return array
     */
    protected function convertPrices(array $data, ImportSource $source)
    {
        if ($source->currency_code === 'BYN') {
            return $data;
        }

        $rate = $this->currencyService->getRate($source->currency_code);
        
        if (!$rate) {
            return $data;
        }

        // Конвертируем основную цену
        if (isset($data['price'])) {
            $data['price_original'] = $data['price'];
            $data['price'] = $data['price'] * $rate;
            $data['exchange_rate'] = $rate;
        }

        // Конвертируем старую цену
        if (isset($data['old_price'])) {
            $data['old_price'] = $data['old_price'] * $rate;
        }

        // Конвертируем цены размеров
        if (isset($data['sizes'])) {
            foreach ($data['sizes'] as &$size) {
                if (isset($size['price'])) {
                    $size['price_original'] = $size['price'];
                    $size['price'] = $size['price'] * $rate;
                    $size['price_byn'] = $size['price'];
                    $size['exchange_rate'] = $rate;
                }
            }
        }

        return $data;
    }

    /**
     * Сопоставить категорию
     * @param int $sourceId
     * @param string|null $sourceCategoryName
     * @return int|null
     */
    protected function matchCategory($sourceId, $sourceCategoryName)
    {
        if (!$sourceCategoryName) {
            return null;
        }

        // Ищем в маппинге
        $map = ImportCategoryMap::findByCategoryName($sourceId, $sourceCategoryName);
        
        if ($map && $map->category_id) {
            return $map->category_id;
        }

        // Автоматическое сопоставление
        return ImportCategoryMap::autoMapCategory($sourceId, $sourceCategoryName);
    }

    /**
     * Найти или создать бренд
     * @param string|null $brandName
     * @return int|null
     */
    protected function findOrCreateBrand($brandName)
    {
        if (!$brandName) {
            return null;
        }

        $brand = Brand::find()
            ->where(['like', 'name', $brandName, false])
            ->one();

        if ($brand) {
            return $brand->id;
        }

        // Создаем новый бренд
        $brand = new Brand([
            'name' => $brandName,
            'slug' => \yii\helpers\Inflector::slug($brandName),
            'is_active' => true,
        ]);

        if ($brand->save(false)) {
            return $brand->id;
        }

        return null;
    }

    /**
     * Найти дубликат товара
     * @param string|null $sku
     * @param array $sizes
     * @return Product|null
     */
    protected function findDuplicate($sku, array $sizes = [])
    {
        if (!$sku) {
            return null;
        }

        // Ищем по SKU
        return Product::find()
            ->where(['external_sku' => $sku])
            ->orWhere(['sku' => $sku])
            ->one();
    }

    /**
     * Создать новый товар
     * @param ImportTask $task
     * @param array $data
     * @param ImportSource $source
     */
    protected function createProduct($task, array $data, ImportSource $source)
    {
        $product = new Product([
            'name' => $data['name'],
            'slug' => \yii\helpers\Inflector::slug($data['name']),
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'price' => $data['price'] ?? 0,
            'old_price' => $data['old_price'] ?? null,
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'] ?? null,
            'external_sku' => $data['sku'] ?? null,
            'import_source_id' => $source->id,
            'last_import_at' => date('Y-m-d H:i:s'),
            'is_active' => true,
            'stock_status' => Product::STOCK_IN_STOCK,
            
            // Характеристики
            'material' => $data['material'] ?? null,
            'season' => $data['season'] ?? null,
            'gender' => $data['gender'] ?? null,
            'country' => $data['country'] ?? null,
            'style_code' => $data['style_code'] ?? null,
        ]);

        if ($product->save(false)) {
            // Сохраняем изображение
            if (!empty($data['images'])) {
                $this->saveImages($product, $data['images']);
            }

            // Сохраняем размеры
            if (!empty($data['sizes'])) {
                $this->saveSizes($product, $data['sizes'], $source);
            }

            // Сохраняем цены в историю
            $this->savePriceHistory($product, $data, $source);

            $task->incrementImported();
            ImportLog::logCreated($task->id, $product->id, $product->sku, $product->name);
        } else {
            $task->incrementFailed();
            ImportLog::logError($task->id, $data['sku'] ?? null, $data['name'] ?? null, 
                'Failed to create product: ' . json_encode($product->errors));
        }
    }

    /**
     * Обновить существующий товар
     * @param ImportTask $task
     * @param Product $product
     * @param array $data
     * @param ImportSource $source
     */
    protected function updateProduct($task, Product $product, array $data, ImportSource $source)
    {
        $changes = [];

        // Обновляем цену если новая лучше
        if (isset($data['price']) && $data['price'] < $product->price) {
            $changes['price'] = ['from' => $product->price, 'to' => $data['price']];
            $product->price = $data['price'];
            $product->best_price_source_id = $source->id;
        }

        // Обновляем описание если пустое
        if (!$product->description && !empty($data['description'])) {
            $product->description = $data['description'];
            $changes['description'] = 'added';
        }

        $product->last_import_at = date('Y-m-d H:i:s');

        if ($product->save(false)) {
            // Обновляем размеры
            if (!empty($data['sizes'])) {
                $this->updateSizes($product, $data['sizes'], $source);
            }

            // Сохраняем цены в историю
            $this->savePriceHistory($product, $data, $source);

            $task->incrementUpdated();
            ImportLog::logUpdated($task->id, $product->id, $product->sku, $product->name, $changes);
        } else {
            $task->incrementFailed();
        }
    }

    /**
     * Сохранить изображения товара
     * @param Product $product
     * @param array $images
     */
    protected function saveImages(Product $product, array $images)
    {
        $mainImage = $images[0] ?? null;
        
        if ($mainImage) {
            $product->main_image = $mainImage;
            $product->main_image_url = $mainImage;
            $product->save(false, ['main_image', 'main_image_url']);
        }

        foreach ($images as $index => $imageUrl) {
            $image = new ProductImage([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'is_main' => $index === 0,
                'sort_order' => $index,
            ]);
            $image->save(false);
        }
    }

    /**
     * Сохранить размеры товара
     * @param Product $product
     * @param array $sizes
     * @param ImportSource $source
     */
    protected function saveSizes(Product $product, array $sizes, ImportSource $source)
    {
        foreach ($sizes as $sizeData) {
            $size = new ProductSize([
                'product_id' => $product->id,
                'size' => $sizeData['size'],
                'size_eu' => $sizeData['size_eu'] ?? $sizeData['size'],
                'size_us' => $sizeData['size_us'] ?? null,
                'price' => $sizeData['price'] ?? $product->price,
                'price_byn' => $sizeData['price_byn'] ?? $sizeData['price'] ?? $product->price,
                'is_available' => $sizeData['is_available'] ?? true,
            ]);
            $size->save(false);
        }
    }

    /**
     * Обновить размеры товара
     * @param Product $product
     * @param array $sizes
     * @param ImportSource $source
     */
    protected function updateSizes(Product $product, array $sizes, ImportSource $source)
    {
        foreach ($sizes as $sizeData) {
            $existingSize = ProductSize::find()
                ->where(['product_id' => $product->id, 'size' => $sizeData['size']])
                ->one();

            if ($existingSize) {
                // Обновляем цену если новая лучше
                if (isset($sizeData['price_byn']) && $sizeData['price_byn'] < $existingSize->price_byn) {
                    $existingSize->price_byn = $sizeData['price_byn'];
                    $existingSize->save(false, ['price_byn']);
                }
            } else {
                // Создаем новый размер
                $this->saveSizes($product, [$sizeData], $source);
            }
        }
    }

    /**
     * Сохранить историю цен
     * @param Product $product
     * @param array $data
     * @param ImportSource $source
     */
    protected function savePriceHistory(Product $product, array $data, ImportSource $source)
    {
        if (!isset($data['sku'])) {
            return;
        }

        // Сохраняем основную цену
        ImportProductPrice::updatePrice(
            $source->id,
            $data['sku'],
            $data['price_original'] ?? $data['price'],
            $source->currency_code,
            $data['price'],
            $data['exchange_rate'] ?? 1,
            null,
            true,
            $data['url'] ?? null,
            $product->id
        );

        // Сохраняем цены по размерам
        if (!empty($data['sizes'])) {
            foreach ($data['sizes'] as $sizeData) {
                ImportProductPrice::updatePrice(
                    $source->id,
                    $data['sku'],
                    $sizeData['price_original'] ?? $sizeData['price'],
                    $source->currency_code,
                    $sizeData['price_byn'] ?? $sizeData['price'],
                    $sizeData['exchange_rate'] ?? $data['exchange_rate'] ?? 1,
                    $sizeData['size'] ?? null,
                    $sizeData['is_available'] ?? true,
                    $data['url'] ?? null,
                    $product->id
                );
            }
        }
    }

    /**
     * Обновить лучшие цены для всех товаров
     * @return int Количество обновленных товаров
     */
    public function updateBestPrices()
    {
        $updated = 0;

        // Получаем все товары с external_sku
        $products = Product::find()
            ->where(['is not', 'external_sku', null])
            ->all();

        foreach ($products as $product) {
            $bestPrice = ImportProductPrice::getBestPrice($product->external_sku);
            
            if ($bestPrice && $bestPrice->price_byn < $product->price) {
                $product->price = $bestPrice->price_byn;
                $product->best_price_source_id = $bestPrice->source_id;
                $product->save(false, ['price', 'best_price_source_id']);
                $updated++;
            }
        }

        Yii::info("Updated best prices for {$updated} products", 'import');
        
        return $updated;
    }
}
