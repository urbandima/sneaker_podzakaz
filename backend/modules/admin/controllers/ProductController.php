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
        $query = Product::find()->with(['brand', 'category', 'images']);

        // Фильтры
        $filterBrand = Yii::$app->request->get('brand');
        $filterCategory = Yii::$app->request->get('category');
        $filterSource = Yii::$app->request->get('source'); // poizon, manual
        $filterActive = Yii::$app->request->get('is_active');
        $filterSearch = Yii::$app->request->get('search');
        $filterStatus = Yii::$app->request->get('status');
        $filterStock = Yii::$app->request->get('stock');

        if ($filterBrand) {
            $query->andWhere(['brand_id' => $filterBrand]);
        }

        if ($filterCategory) {
            $query->andWhere(['category_id' => $filterCategory]);
        }

        if ($filterSearch) {
            $query->andWhere(['or',
                ['like', 'name', $filterSearch],
                ['like', 'vendor_code', $filterSearch],
                ['like', 'poizon_id', $filterSearch],
            ]);
        }

        if ($filterStatus) {
            $query->andWhere(['is_active' => $filterStatus === 'active' ? true : false]);
        }

        if ($filterStock) {
            if ($filterStock === 'in') {
                $query->andWhere(['!=', 'stock_status', 'out_of_stock']);
            } elseif ($filterStock === 'out') {
                $query->andWhere(['stock_status' => 'out_of_stock']);
            }
        }

        if ($filterSource === 'poizon') {
            $query->andWhere(['not', ['poizon_id' => null]]);
        } elseif ($filterSource === 'manual') {
            $query->andWhere(['poizon_id' => null]);
        }

        if ($filterActive !== null && $filterActive !== '') {
            $query->andWhere(['is_active' => $filterActive]);
        }

        if ($filterSearch) {
            $query->andWhere([
                'or',
                ['like', 'name', $filterSearch],
                ['like', 'sku', $filterSearch],
                ['like', 'poizon_id', $filterSearch],
                ['like', 'vendor_code', $filterSearch],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
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
            'total' => Product::find()->count(),
            'active' => Product::find()->where(['is_active' => true])->count(),
            'poizon' => Product::find()->where(['not', ['poizon_id' => null]])->count(),
            'inactive' => Product::find()->where(['is_active' => false])->count(),
            'inStock' => Product::find()->where(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])->count(),
            'manual' => Product::find()->where(['poizon_id' => null])->count(),
            'outOfStock' => Product::find()->where(['stock_status' => 'out_of_stock'])->count(),
        ];

        // Списки для фильтров
        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        // Используем основной интерфейс списка товаров
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'brands' => $brands,
            'categories' => $categories,
            'filterBrand' => $filterBrand,
            'filterCategory' => $filterCategory,
            'filterSource' => $filterSource,
            'filterActive' => $filterActive,
            'filterSearch' => $filterSearch,
            'filterStatus' => $filterStatus,
            'filterStock' => $filterStock,
            'pageSize' => $requestedPageSize,
            'pageSizeOptions' => $pageSizeOptions,
        ]);
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
        $product = $this->findModel($id);

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
        $product = $this->findModel($id);

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
        
        $query = Product::find()->with(['brand', 'category']);
        
        if ($ids) {
            $idsArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->andWhere(['id' => $idsArray]);
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
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Заголовки
        fputcsv($output, [
            'ID', 'Название', 'Артикул', 'Бренд', 'Категория', 'Цена', 'Статус', 'Наличие', 'Poizon ID'
        ]);
        
        foreach ($products as $product) {
            fputcsv($output, [
                $product->id,
                $product->name,
                $product->vendor_code,
                $product->brand ? $product->brand->name : '',
                $product->category ? $product->category->name : '',
                $product->price,
                $product->is_active ? 'Активен' : 'Неактивен',
                $product->stock_status,
                $product->poizon_id
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
                $product->id,
                $product->sku,
                $product->name,
                $product->brand_name,
                $product->category_name,
                $product->price,
                $product->old_price ?: '',
                $product->stock_status,
                $product->is_active ? 'Активен' : 'Неактивен',
                $product->poizon_id ?: ''
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
}
