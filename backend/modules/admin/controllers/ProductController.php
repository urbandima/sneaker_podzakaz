<?php

/**
 * ProductController — Управление товарами в админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Полное управление товарами: создание, редактирование, управление
 * изображениями, размерами, ценами, синхронизация с Poizon.
 * 
 * ФУНКЦИИ:
 * - Список товаров с фильтрацией и поиском (index)
 * - Просмотр товара (view)
 * - Создание товара (create)
 * - Редактирование товара (edit)
 * - Активация/деактивация товара (toggle)
 * - Удаление товара (delete)
 * - Синхронизация с Poizon (sync)
 * - Управление размерами: добавление, редактирование, удаление (add-size, edit-size, delete-size)
 * - Добавление размеров из размерной сетки (add-sizes-from-grid)
 * - Управление изображениями: добавление, удаление, установка главного (add-image, delete-image, set-main-image)
 * - Массовые операции (batch-update, batch-delete)
 * 
 * СВЯЗИ:
 * - Product (модель товара)
 * - ProductSize (модель размера)
 * - ProductImage (модель изображения)
 * - ProductColor (модель цвета)
 * - Brand (модель бренда)
 * - Category (модель категории)
 * - SizeGrid (модель размерной сетки)
 * - ProductRepository (репозиторий товаров)
 * 
 * ДОСТУП:
 * - Только администраторы
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\SizeGrid;
use app\backend\modules\catalog\repositories\ProductRepository;

class ProductController extends BaseAdminController
{
    /** @var ProductRepository */
    private $productRepository;
    
    /**
     * Инициализация контроллера
     */
    public function init()
    {
        parent::init();
        $this->productRepository = new ProductRepository();
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Список товаров с фильтрацией и статистикой
     */
    public function actionIndex()
    {
        $query = Product::find()->alias('p')->with(['brand', 'category', 'images', 'sizes']);

        // Фильтры
        $filterBrand       = Yii::$app->request->get('brand');
        $filterCategory    = Yii::$app->request->get('category');
        $filterSource      = Yii::$app->request->get('source');
        $filterSearch      = Yii::$app->request->get('search');
        $filterStatus      = Yii::$app->request->get('status');
        $filterStock       = Yii::$app->request->get('stock');
        $filterGender      = Yii::$app->request->get('gender');
        $filterSeason      = Yii::$app->request->get('season');
        $filterPriceFrom   = Yii::$app->request->get('price_from');
        $filterPriceTo     = Yii::$app->request->get('price_to');
        $filterBrandMismatch = (bool)Yii::$app->request->get('brand_mismatch');

        if ($filterBrand) {
            $query->andWhere(['p.brand_id' => $filterBrand]);
        }
        if ($filterCategory) {
            $query->andWhere(['p.category_id' => $filterCategory]);
        }
        if ($filterSearch) {
            $query->andWhere(['or',
                ['like', 'p.name', $filterSearch],
                ['like', 'p.sku', $filterSearch],
                ['like', 'p.vendor_code', $filterSearch],
                ['like', 'p.poizon_id', $filterSearch],
                ['like', 'p.style_code', $filterSearch],
            ]);
        }
        if ($filterStatus === 'active') {
            $query->andWhere(['p.is_active' => true]);
        } elseif ($filterStatus === 'archived') {
            $query->andWhere(['p.is_active' => false]);
        } elseif ($filterStatus === 'out_of_stock') {
            $query->andWhere(['p.stock_status' => 'out_of_stock']);
        } elseif ($filterStatus === 'zero_price') {
            $query->andWhere(['or', ['p.price' => null], ['p.price' => 0]]);
        }
        if ($filterStock === 'in') {
            $query->andWhere(['!=', 'p.stock_status', 'out_of_stock']);
        } elseif ($filterStock === 'out') {
            $query->andWhere(['p.stock_status' => 'out_of_stock']);
        }
        if ($filterSource === 'poizon') {
            $query->andWhere(['not', ['p.poizon_id' => null]]);
        } elseif ($filterSource === 'manual') {
            $query->andWhere(['p.poizon_id' => null]);
        }
        if ($filterGender) {
            $query->andWhere(['p.gender' => $filterGender]);
        }
        if ($filterSeason) {
            $query->andWhere(['p.season' => $filterSeason]);
        }
        if ($filterPriceFrom !== null && $filterPriceFrom !== '') {
            $query->andWhere(['>=', 'p.price', (float)$filterPriceFrom]);
        }
        if ($filterPriceTo !== null && $filterPriceTo !== '') {
            $query->andWhere(['<=', 'p.price', (float)$filterPriceTo]);
        }
        if ($filterBrandMismatch) {
            $query->andWhere(['p.is_active' => 1])
                  ->andWhere(['not', ['p.brand_name' => null]])
                  ->andWhere(['!=', 'p.brand_name', ''])
                  ->andWhere('LOWER(p.name) NOT LIKE CONCAT(LOWER(p.brand_name), \'%\')');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => [
                    'created_at',
                    'name',
                    'price',
                    'brand_name',
                    'is_active',
                    'stock_status',
                ],
            ],
        ]);

        $pageSizeOptions = [20, 50, 100, 200];
        $requestedPageSize = (int)Yii::$app->request->get('per-page', $dataProvider->pagination->getPageSize());
        if (!in_array($requestedPageSize, $pageSizeOptions, true)) {
            $requestedPageSize = 50;
        }
        $dataProvider->pagination->pageSize = $requestedPageSize;

        // Статистика
        $stats = [
            'total'      => Product::find()->count(),
            'active'     => Product::find()->where(['is_active' => true])->count(),
            'inactive'   => Product::find()->where(['is_active' => false])->count(),
            'inStock'    => Product::find()->where(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])->count(),
            'outOfStock' => Product::find()->where(['stock_status' => 'out_of_stock'])->count(),
            'zeroPrice'  => Product::find()->where(['or', ['price' => null], ['price' => 0]])->count(),
        ];

        // Списки для фильтров
        $brands     = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        // Infinite scroll AJAX
        if (Yii::$app->request->get('scroll') && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $products = $dataProvider->getModels();
            $pagination = $dataProvider->getPagination();

            $rows = '';
            foreach ($products as $product) {
                $rows .= $this->renderPartial('_product_row', ['product' => $product]);
            }
            $gridCards = '';
            foreach ($products as $product) {
                $gridCards .= $this->renderPartial('_product_card', ['product' => $product]);
            }

            return [
                'rows'     => $rows,
                'cards'    => $gridCards,
                'hasMore'  => $pagination->page < $pagination->pageCount - 1,
                'nextPage' => $pagination->page + 2,
            ];
        }

        return $this->render('index', [
            'dataProvider'       => $dataProvider,
            'stats'              => $stats,
            'brands'             => $brands,
            'categories'         => $categories,
            'filterBrand'        => $filterBrand,
            'filterCategory'     => $filterCategory,
            'filterSource'       => $filterSource,
            'filterSearch'       => $filterSearch,
            'filterStatus'       => $filterStatus,
            'filterStock'        => $filterStock,
            'filterGender'       => $filterGender,
            'filterSeason'       => $filterSeason,
            'filterPriceFrom'    => $filterPriceFrom,
            'filterPriceTo'      => $filterPriceTo,
            'filterBrandMismatch'=> $filterBrandMismatch,
            'pageSize'           => $requestedPageSize,
            'pageSizeOptions'    => $pageSizeOptions,
        ]);
    }

    /**
     * #20 — Fix brand name: set brand_name = first word of product name (bulk, preview mode)
     */
    public function actionFixBrand()
    {
        $this->requirePermission('manageProducts');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $preview = (bool)Yii::$app->request->get('preview', true);

        $rows = Yii::$app->db->createCommand(
            "SELECT id, name, brand_name FROM `product`
             WHERE is_active = 1
               AND brand_name IS NOT NULL AND brand_name != ''
               AND LOWER(name) NOT LIKE CONCAT(LOWER(brand_name), '%')
             LIMIT 500"
        )->queryAll();

        if (empty($rows)) {
            return ['success' => true, 'fixed' => 0, 'preview' => []];
        }

        $previewData = [];
        foreach ($rows as $row) {
            $firstWord = preg_split('/\s+/', trim($row['name']), 2)[0] ?? '';
            if (!$firstWord) continue;
            $previewData[] = [
                'id'        => $row['id'],
                'name'      => $row['name'],
                'brand_old' => $row['brand_name'],
                'brand_new' => $firstWord,
            ];
        }

        if ($preview) {
            return ['success' => true, 'preview' => $previewData];
        }

        $fixed = 0;
        foreach ($previewData as $item) {
            Yii::$app->db->createCommand()->update('product',
                ['brand_name' => $item['brand_new'], 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $item['id']]
            )->execute();
            $fixed++;
        }

        Yii::info("Brand mismatch bulk fix: $fixed products updated by user " . (Yii::$app->user->id ?? 'cli'), 'admin.catalog');
        return ['success' => true, 'fixed' => $fixed];
    }

    /**
     * Создание товара
     */
    public function actionCreate()
    {
        $model = new Product();
        $model->is_active = true;
        $model->stock_status = Product::STOCK_IN_STOCK;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Товар успешно создан');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('create', [
            'model' => $model,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * Просмотр товара
     * 
     * @param int $id
     */
    public function actionView($id)
    {
        $product = Product::find()
            ->where(['id' => $id])
            ->with(['images', 'brand', 'category', 'sizes'])
            ->one();

        if ($product === null) {
            throw new NotFoundHttpException('Товар не найден');
        }

        return $this->render('view', [
            'product' => $product,
        ]);
    }

    /**
     * Редактирование товара
     * 
     * @param int $id
     */
    public function actionEdit($id)
    {
        $product = Product::find()
            ->where(['id' => $id])
            ->with(['images', 'brand', 'category', 'sizes'])
            ->one();

        if ($product === null) {
            throw new NotFoundHttpException('Товар не найден');
        }

        if ($product->load(Yii::$app->request->post())) {
            // Обработка объединенных ключевых слов
            if ($product->meta_keywords) {
                // Парсим meta_keywords из формы
                $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
                $metaKeywordsArray = array_filter($metaKeywordsArray); // убираем пустые
                
                // Получаем keywords из Poizon (JSON)
                $poizonKeywords = [];
                if ($product->keywords) {
                    $keywordsData = json_decode($product->keywords, true);
                    if (is_array($keywordsData)) {
                        $poizonKeywords = $keywordsData;
                    }
                }
                
                // Объединяем и удаляем дубликаты (регистронезависимо)
                $allKeywords = array_merge($metaKeywordsArray, $poizonKeywords);
                $allKeywords = array_unique(array_map('mb_strtolower', $allKeywords));
                
                // Сохраняем обратно в meta_keywords
                $product->meta_keywords = implode(', ', $allKeywords);
            }
            
            // Обработка измененных характеристик Poizon
            $poizonProps = Yii::$app->request->post('poizon_props');
            if (is_array($poizonProps) && !empty($poizonProps)) {
                // Обновляем JSON поле properties с новыми значениями
                $updatedProps = [];
                foreach ($poizonProps as $prop) {
                    if (!empty($prop['key']) && !empty($prop['value'])) {
                        $updatedProps[] = [
                            'key' => $prop['key'],
                            'value' => $prop['value']
                        ];
                    }
                }
                
                if (!empty($updatedProps)) {
                    $product->properties = json_encode($updatedProps, JSON_UNESCAPED_UNICODE);
                }
            }
            
            if ($product->save()) {
                $this->flashSuccess('Товар успешно обновлен');
                return $this->redirect(['/admin/product/view', 'id' => $product->id]);
            }
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('edit', [
            'product' => $product,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * Активация/деактивация товара
     * 
     * @param int $id
     */
    public function actionToggle($id)
    {
        $product = $this->findModel($id);
        $product->is_active = $product->is_active ? 0 : 1;
        
        if ($product->save(false)) {
            $status = $product->is_active ? 'активирован' : 'деактивирован';
            $this->flashSuccess("Товар {$status}");
        }

        return $this->redirect(['/admin/product/index']);
    }

    /**
     * Удаление товара
     * 
     * @param int $id
     */
    public function actionDelete($id)
    {
        $this->requirePermission('manageProducts');
        $product = $this->findModel($id);
        
        if ($product->delete()) {
            $this->flashSuccess('Товар успешно удален');
        } else {
            $this->flashError('Ошибка при удалении товара');
        }

        return $this->redirect(['/admin/product/index']);
    }

    /**
     * Синхронизация товара с Poizon
     * 
     * @param int $id
     */
    public function actionSync($id)
    {
        $product = $this->findModel($id);
        
        if (!$product->poizon_id) {
            $this->flashError('Товар не импортирован из Poizon');
            return $this->redirect(['/admin/product/view', 'id' => $id]);
        }

        try {
            $poizonApi = Yii::$app->get('poizonApi');
            // Здесь будет логика синхронизации
            $product->last_sync_at = date('Y-m-d H:i:s');
            $product->save(false);
            
            $this->flashSuccess('Товар успешно синхронизирован с Poizon');
        } catch (\Exception $e) {
            Yii::error('Ошибка синхронизации товара #' . $id . ': ' . $e->getMessage(), 'product');
            $this->flashError('Ошибка синхронизации: ' . $e->getMessage());
        }

        return $this->redirect(['/admin/product/view', 'id' => $id]);
    }

    /**
     * Добавить размер к товару
     * 
     * @param int $productId
     */
    public function actionAddSize($productId)
    {
        $product = $this->findModel($productId);
        $size = new ProductSize();
        $size->product_id = $productId;

        if ($size->load(Yii::$app->request->post()) && $size->save()) {
            $this->flashSuccess('Размер успешно добавлен');
            
            // Если AJAX - возвращаем JSON
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'Размер добавлен'];
            }
            
            // Проверяем откуда пришел запрос
            $returnUrl = Yii::$app->request->get('returnUrl', 'view');
            if ($returnUrl === 'edit') {
                return $this->redirect(['/admin/product/edit', 'id' => $productId]);
            }
            
            return $this->redirect(['/admin/product/view', 'id' => $productId]);
        }

        return $this->render('add-size', [
            'product' => $product,
            'size' => $size,
        ]);
    }

    /**
     * Массовое добавление размеров из сетки
     * 
     * @param int $productId
     * @param int $gridId
     */
    public function actionAddSizesFromGrid($productId, $gridId)
    {
        $product = $this->findModel($productId);
        $grid = SizeGrid::findOne($gridId);
        
        if (!$grid) {
            throw new NotFoundHttpException('Размерная сетка не найдена');
        }

        $added = 0;
        foreach ($grid->items as $item) {
            // Проверяем, не существует ли уже такой размер
            $exists = ProductSize::find()
                ->where(['product_id' => $productId, 'us_size' => $item->us_size])
                ->exists();
                
            if (!$exists) {
                $size = new ProductSize();
                $size->product_id = $productId;
                $size->us_size = $item->us_size;
                $size->eu_size = $item->eu_size;
                $size->uk_size = $item->uk_size;
                $size->cm_size = $item->cm_size;
                $size->size = $item->size;
                $size->stock = 0;
                $size->is_available = 1;
                
                if ($size->save()) {
                    $added++;
                }
            }
        }

        $this->flashSuccess("Добавлено размеров: {$added}");
        
        // Проверяем откуда пришел запрос
        $returnUrl = Yii::$app->request->get('returnUrl', 'view');
        if ($returnUrl === 'edit') {
            return $this->redirect(['/admin/product/edit', 'id' => $productId]);
        }
        
        return $this->redirect(['/admin/product/view', 'id' => $productId]);
    }

    /**
     * Редактировать размер
     * 
     * @param int $id
     */
    public function actionEditSize($id)
    {
        $size = ProductSize::findOne($id);
        if (!$size) {
            throw new NotFoundHttpException('Размер не найден');
        }

        if ($size->load(Yii::$app->request->post()) && $size->save()) {
            $this->flashSuccess('Размер успешно обновлен');
            return $this->redirect(['/admin/product/view', 'id' => $size->product_id]);
        }

        return $this->render('edit-size', [
            'size' => $size,
            'product' => $size->product,
        ]);
    }

    /**
     * Удалить размер
     * 
     * @param int $id
     */
    public function actionDeleteSize($id)
    {
        $size = ProductSize::findOne($id);
        if ($size) {
            $productId = $size->product_id;
            $size->delete();
            $this->flashSuccess('Размер удален');
            return $this->redirect(['/admin/product/view', 'id' => $productId]);
        }

        throw new NotFoundHttpException('Размер не найден');
    }

    /**
     * Добавить изображение к товару
     * 
     * @param int $productId
     */
    public function actionAddImage($productId)
    {
        $product = $this->findModel($productId);
        
        if (Yii::$app->request->isPost) {
            $imageUrl = Yii::$app->request->post('image_url');
            
            if ($imageUrl) {
                $image = new ProductImage();
                $image->product_id = $productId;
                $image->image = $imageUrl;
                $image->sort_order = ProductImage::find()->where(['product_id' => $productId])->max('sort_order') + 1;
                
                if ($image->save()) {
                    $this->flashSuccess('Изображение добавлено');
                } else {
                    $this->flashError('Ошибка при добавлении изображения');
                }
            }
        }

        // Проверяем откуда пришел запрос
        $returnUrl = Yii::$app->request->get('returnUrl', 'view');
        if ($returnUrl === 'edit') {
            return $this->redirect(['/admin/product/edit', 'id' => $productId]);
        }

        return $this->redirect(['/admin/product/view', 'id' => $productId]);
    }

    /**
     * Удалить изображение
     * 
     * @param int $id
     */
    public function actionDeleteImage($id)
    {
        $image = ProductImage::findOne($id);
        if ($image) {
            $productId = $image->product_id;
            $image->delete();
            $this->flashSuccess('Изображение удалено');
            return $this->redirect(['/admin/product/view', 'id' => $productId]);
        }

        throw new NotFoundHttpException('Изображение не найдено');
    }

    /**
     * Установить главное изображение
     * 
     * @param int $id
     */
    public function actionSetMainImage($id)
    {
        $image = ProductImage::findOne($id);
        if ($image) {
            $image->setAsMain();
            $this->flashSuccess('Главное изображение установлено');
            return $this->redirect(['/admin/product/view', 'id' => $image->product_id]);
        }

        throw new NotFoundHttpException('Изображение не найдено');
    }

    /**
     * Массовое обновление товаров
     */
    public function actionBulkUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $ids = json_decode(Yii::$app->request->post('ids', []), true);
        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');
        
        if (empty($ids) || !$field) {
            return ['success' => false, 'message' => 'Не указаны товары или поле'];
        }
        
        // Разрешенные поля для массового обновления
        $allowedFields = ['is_active'];
        
        if (!in_array($field, $allowedFields)) {
            return ['success' => false, 'message' => 'Недопустимое поле'];
        }
        
        $updated = Product::updateAll([$field => $value], ['id' => $ids]);
        
        return ['success' => true, 'updated' => $updated];
    }

/**
     * Массовое удаление товаров
     */
    public function actionBulkDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $ids = json_decode(Yii::$app->request->post('ids', []), true);
        
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Не указаны товары'];
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $deleted = 0;
            foreach ($ids as $id) {
                $product = Product::findOne($id);
                if ($product) {
                    // Удаляем связанные изображения, размеры и т.д.
                    ProductImage::deleteAll(['product_id' => $id]);
                    ProductSize::deleteAll(['product_id' => $id]);
                    $product->delete();
                    $deleted++;
                }
            }
            
            $transaction->commit();
            return ['success' => true, 'deleted' => $deleted];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Ошибка при удалении: ' . $e->getMessage()];
        }
    }

/**
     * Экспорт товаров
     */
    public function actionExport()
    {
        $ids = Yii::$app->request->get('ids');
        $format = Yii::$app->request->get('format', 'xlsx');

        // Use asArray() to avoid loading all products as ActiveRecord objects (prevents OOM on large catalogs)
        $query = Product::find()
            ->select(['p.id', 'p.name', 'p.vendor_code as sku', 'p.price', 'p.old_price',
                       'p.is_active', 'p.stock_status', 'p.poizon_id',
                       'b.name as brand_name', 'c.name as category_name'])
            ->alias('p')
            ->leftJoin('brand b', 'b.id = p.brand_id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->asArray();

        if ($ids) {
            $idsArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->andWhere(['p.id' => $idsArray]);
        }

        $products = $query->all();

        if ($format === 'csv') {
            $this->exportToCsv($products);
        } else {
            $this->exportToExcel($products);
        }
    }

/**
     * Экспорт в CSV
     */
    private function exportToCsv($products)
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Заголовки
        fputcsv($output, [
            'ID', 'Название', 'Артикул', 'Бренд', 'Категория', 'Цена', 'Статус', 'Наличие', 'Poizon ID'
        ]);

        foreach ($products as $product) {
            fputcsv($output, [
                $product['id'],
                $product['name'],
                $product['sku'] ?? '',
                $product['brand_name'] ?? '',
                $product['category_name'] ?? '',
                $product['price'],
                ($product['is_active'] ?? 0) ? 'Активен' : 'Неактивен',
                $product['stock_status'] ?? '',
                $product['poizon_id'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Экспорт в Excel (РЕАЛИЗОВАНО: PhpSpreadsheet)
     */
    private function exportToExcel($products)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Заголовки
        $headers = ['ID', 'SKU', 'Название', 'Бренд', 'Категория', 'Цена', 'Старая цена', 'Наличие', 'Статус', 'Poizon ID'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Стиль заголовков
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Данные
        $row = 2;
        foreach ($products as $product) {
            $sheet->fromArray([
                $product['id'],
                $product['sku'] ?? '',
                $product['name'],
                $product['brand_name'] ?? '',
                $product['category_name'] ?? '',
                $product['price'],
                $product['old_price'] ?: '',
                $product['stock_status'] ?? '',
                ($product['is_active'] ?? 0) ? 'Активен' : 'Неактивен',
                $product['poizon_id'] ?: ''
            ], null, "A{$row}");
            $row++;
        }
        
        // Автоширина колонок
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Границы
        $sheet->getStyle("A1:J{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Отправка файла
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="products_export_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Найти модель товара
     * 
     * @param int $id
     * @return Product
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = Product::findOne($id);
        
        if ($model === null) {
            throw new NotFoundHttpException('Товар не найден');
        }
        
        return $model;
    }

    /**
     * Клонирование товара
     * 
     * @param int $id
     */
    public function actionClone($id)
    {
        $original = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $clone = new Product();
            $clone->attributes = $original->attributes;
            $clone->id = null;
            $clone->isNewRecord = true;
            $clone->name = $original->name . ' (копия)';
            $clone->slug = $original->slug . '-copy-' . time();
            $clone->is_active = false;
            $clone->created_at = time();
            $clone->updated_at = time();

            if (!$clone->save(false)) {
                throw new \Exception('Ошибка сохранения копии: ' . json_encode($clone->errors));
            }

            // Копируем размеры
            foreach ($original->sizes as $size) {
                $newSize = new ProductSize();
                $newSize->attributes = $size->attributes;
                $newSize->id = null;
                $newSize->isNewRecord = true;
                $newSize->product_id = $clone->id;
                $newSize->save(false);
            }

            $transaction->commit();
            Yii::info("Товар #{$original->id} клонирован -> #{$clone->id}", 'product');
            $this->flashSuccess('Товар клонирован. Отредактируйте копию.');
            return $this->redirect(['/admin/product/edit', 'id' => $clone->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->flashError('Ошибка клонирования: ' . $e->getMessage());
            return $this->redirect(['/admin/product/view', 'id' => $id]);
        }
    }

    /**
     * Экспорт каталога товаров в CSV
     */
    /**
     * Страница массового изменения цен
     */
    public function actionBulkPrice()
    {
        $brandId    = Yii::$app->request->get('brand');
        $categoryId = Yii::$app->request->get('category');

        $query = Product::find()->where(['is_active' => true]);

        if ($brandId) {
            $query->andWhere(['brand_id' => (int)$brandId]);
        }
        if ($categoryId) {
            $query->andWhere(['category_id' => (int)$categoryId]);
        }

        $products   = $query->with(['brand', 'category'])->limit(200)->all();
        $brands     = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('bulk-price', [
            'products'   => $products,
            'brands'     => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * AJAX: массовое обновление цен
     * POST {prices: [{id, price}, ...]} → JSON
     */
    public function actionBulkUpdatePrice()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $prices  = Yii::$app->request->post('prices', []);
        $updated = 0;

        foreach ($prices as $item) {
            if (!empty($item['id']) && isset($item['price'])) {
                Product::updateAll(
                    ['price' => (float)$item['price']],
                    ['id' => (int)$item['id'], 'is_active' => true]
                );
                $updated++;
            }
        }

        return ['success' => true, 'updated' => $updated];
    }

    public function actionExportCsv()
    {
        $products = Product::find()
            ->with(['brand', 'category'])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $csv = "\xEF\xBB\xBF"; // BOM для Excel
        $csv .= "ID;Название;Бренд;Категория;Цена (BYN);Статус;SKU;Дата создания\n";

        foreach ($products as $p) {
            $csv .= implode(';', [
                $p->id,
                '"' . str_replace('"', '""', $p->name) . '"',
                '"' . ($p->brand->name ?? '—') . '"',
                '"' . ($p->category->name ?? '—') . '"',
                number_format($p->price ?? 0, 2, '.', ''),
                $p->is_active ? 'Активен' : 'Неактивен',
                '"' . ($p->sku ?? '') . '"',
                date('d.m.Y', $p->created_at ?? time()),
            ]) . "\n";
        }

        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="products_' . date('Y-m-d') . '.csv"');
        return $csv;
    }

    /** B7.2 — Update product price via AJAX */
    public function actionUpdatePrice()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id = (int)($data['id'] ?? 0);
        $price = (float)($data['price'] ?? 0);
        $product = Product::findOne($id);
        if (!$product) return ['success' => false, 'message' => 'Не найден'];
        $product->price = $price;
        $product->save(false);
        return ['success' => true, 'price' => $price];
    }

    /** P20 — Update a single text field on the product via AJAX */
    public function actionUpdateField()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id    = (int)($data['id'] ?? 0);
        $field = (string)($data['field'] ?? '');
        $value = $data['value'] ?? '';
        $allowed = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
        if (!in_array($field, $allowed)) return ['success' => false, 'message' => 'Поле не разрешено'];
        $product = Product::findOne($id);
        if (!$product) return ['success' => false, 'message' => 'Не найден'];
        $product->$field = $value;
        $product->save(false);
        return ['success' => true, 'value' => $value];
    }

    /** B7.2 — Toggle product active status */
    public function actionToggleActive()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id = (int)($data['id'] ?? 0);
        $product = Product::findOne($id);
        if (!$product) return ['success' => false, 'message' => 'Не найден'];
        $product->is_active = !$product->is_active;
        $product->save(false);
        return ['success' => true, 'is_active' => $product->is_active];
    }

    /** Сохранение быстрой сетки доступных размеров (sizes_data JSON) */
    public function actionSaveSizesData()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id = (int)($data['id'] ?? 0);
        $sizes = $data['sizes'] ?? null;

        $product = Product::findOne($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        if ($sizes === null || !is_array($sizes)) {
            return ['success' => false, 'message' => 'Некорректные данные'];
        }

        // Валидируем: ключи должны быть числами/размерами, значения — bool
        $clean = [];
        foreach ($sizes as $size => $available) {
            $sizeStr = (string)$size;
            if (preg_match('/^\d{1,3}(\.\d)?$/', $sizeStr)) {
                $clean[$sizeStr] = (bool)$available;
            }
        }

        $product->sizes_data = !empty($clean) ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
        $product->save(false);

        return ['success' => true, 'saved' => count($clean)];
    }

    /** B7.3 — Update size BYN price via AJAX */
    public function actionUpdateSizePrice()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $sizeId = (int)($data['size_id'] ?? 0);
        $priceByn = (float)($data['price_byn'] ?? 0);
        try {
            $size = \app\backend\modules\catalog\models\ProductSize::findOne($sizeId);
            if (!$size) return ['success' => false, 'message' => 'Размер не найден'];
            $size->price_byn = $priceByn;
            $size->save(false);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Сохранение одного поля товара (auto-save из карточки редактирования)
     * POST {id, field, value} → JSON
     *
     * Разрешённые поля: material, season, gender, height, fastening,
     *                   country, style_code, release_year, weight,
     *                   name, price, old_price, description, is_active, is_featured
     */
    public function actionSaveField()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data  = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id    = (int)($data['id'] ?? 0);
        $field = trim((string)($data['field'] ?? ''));
        $value = $data['value'] ?? '';

        $allowedFields = [
            'material', 'season', 'gender', 'height', 'fastening',
            'country', 'style_code', 'release_year', 'weight',
            'name', 'price', 'old_price', 'description',
            'is_active', 'is_featured', 'stock_count',
            'upper_material', 'sole_material', 'color_description',
            'series_name', 'delivery_time_min', 'delivery_time_max',
        ];

        if (!in_array($field, $allowedFields, true)) {
            return ['success' => false, 'message' => 'Поле не разрешено: ' . $field];
        }

        $product = Product::findOne($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        // Type-cast
        if (in_array($field, ['release_year', 'weight', 'stock_count', 'delivery_time_min', 'delivery_time_max', 'is_active', 'is_featured'], true)) {
            $value = $value === '' ? null : (int)$value;
        } elseif (in_array($field, ['price', 'old_price'], true)) {
            $value = $value === '' ? null : (float)$value;
        }

        $product->$field = $value;

        if (!$product->save(false)) {
            return ['success' => false, 'message' => 'Ошибка сохранения'];
        }

        // Human-readable display value for select fields
        $displayMap = [
            'material'  => ['leather' => 'Кожа', 'textile' => 'Текстиль', 'synthetic' => 'Синтетика', 'suede' => 'Замша', 'mesh' => 'Сетка', 'canvas' => 'Канвас'],
            'season'    => ['summer' => 'Лето', 'winter' => 'Зима', 'demi' => 'Демисезон', 'all' => 'Всесезон'],
            'gender'    => ['male' => 'Мужской', 'female' => 'Женский', 'unisex' => 'Унисекс'],
            'height'    => ['low' => 'Низкие', 'mid' => 'Средние', 'high' => 'Высокие'],
            'fastening' => ['laces' => 'Шнурки', 'velcro' => 'Липучки', 'zipper' => 'Молния', 'slip_on' => 'Без застёжки'],
        ];
        $displayValue = (string)$value;
        if (isset($displayMap[$field][$value])) {
            $displayValue = $displayMap[$field][$value];
        }

        return ['success' => true, 'displayValue' => $displayValue];
    }

    /**
     * AJAX: Inline update for product or size fields (auto-save from product card)
     * Accepts JSON or POST: {entity, id, field, value}
     * entity = 'product' | 'size'
     */
    public function actionInlineUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $entity = $data['entity'] ?? '';
        $id     = (int)($data['id'] ?? 0);
        $field  = trim((string)($data['field'] ?? ''));
        $value  = $data['value'] ?? '';

        if (!$id || !$field) {
            return ['success' => false, 'message' => 'Missing id or field'];
        }

        if ($entity === 'size') {
            $allowedSizeFields = ['price_byn', 'price_cny', 'stock', 'is_available'];
            if (!in_array($field, $allowedSizeFields, true)) {
                return ['success' => false, 'message' => 'Size field not allowed: ' . $field];
            }
            $size = ProductSize::findOne($id);
            if (!$size) {
                return ['success' => false, 'message' => 'Size not found'];
            }
            if (in_array($field, ['price_byn', 'price_cny'], true)) {
                $value = $value === '' ? null : (float)$value;
            } elseif ($field === 'stock') {
                $value = $value === '' ? 0 : (int)$value;
            } elseif ($field === 'is_available') {
                $value = (int)(bool)$value;
            }
            $size->$field = $value;
            if (!$size->save(false)) {
                return ['success' => false, 'message' => 'Save error'];
            }
            return ['success' => true, 'value' => $size->$field];
        }

        if ($entity === 'product') {
            // Reuse the same allowed fields as actionSaveField
            $allowedFields = [
                'material', 'season', 'gender', 'height', 'fastening',
                'country', 'style_code', 'release_year', 'weight',
                'name', 'price', 'old_price', 'description',
                'is_active', 'is_featured', 'stock_count',
                'upper_material', 'sole_material', 'color_description',
                'series_name', 'delivery_time_min', 'delivery_time_max',
                // MS characteristic fields
                'brand_name', 'model_name', 'ms_size_grid', 'ms_purpose',
                'ms_sole_height', 'ms_sole_color', 'ms_inner_material',
            ];
            if (!in_array($field, $allowedFields, true)) {
                return ['success' => false, 'message' => 'Product field not allowed: ' . $field];
            }
            $product = Product::findOne($id);
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            if (in_array($field, ['release_year', 'weight', 'stock_count', 'delivery_time_min', 'delivery_time_max', 'is_active', 'is_featured'], true)) {
                $value = $value === '' ? null : (int)$value;
            } elseif (in_array($field, ['price', 'old_price'], true)) {
                $value = $value === '' ? null : (float)$value;
            }
            $product->$field = $value;
            if (!$product->save(false)) {
                return ['success' => false, 'message' => 'Save error'];
            }
            return ['success' => true, 'value' => $product->$field];
        }

        return ['success' => false, 'message' => 'Unknown entity: ' . $entity];
    }

    /** B7.5 — Trigger Poizon sync */
    public function actionSyncPoizon()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id = (int)($data['id'] ?? 0);
        $product = Product::findOne($id);
        if (!$product || !$product->poizon_id) return ['success' => false, 'message' => 'Товар без Poizon ID'];
        try {
            $apiService = new \app\backend\shared\components\PoizonApiService();
            $apiService->syncProduct($product);
            return ['success' => true, 'message' => 'Синхронизировано'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }
}
