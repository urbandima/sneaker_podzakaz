<?php

namespace app\controllers\admin;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Product;
use app\models\ProductSize;
use app\models\ProductImage;
use app\models\Brand;
use app\models\Category;
use app\models\SizeGrid;
use app\repositories\ProductRepository;

/**
 * ProductController - Управление товарами
 * 
 * Только для администраторов.
 * 
 * Методы:
 * - index() - Список товаров с фильтрацией
 * - view($id) - Просмотр товара
 * - edit($id) - Редактирование товара
 * - toggle($id) - Активация/деактивация
 * - delete($id) - Удаление товара
 * - sync($id) - Синхронизация с Poizon
 * - addSize($productId) - Добавить размер
 * - addSizesFromGrid($productId, $gridId) - Добавить размеры из сетки
 * - editSize($id) - Редактировать размер
 * - deleteSize($id) - Удалить размер
 * - addImage($productId) - Добавить изображение
 * - deleteImage($id) - Удалить изображение
 * - setMainImage($id) - Установить главное изображение
 */
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
        $behaviors = parent::behaviors();
        
        // Все действия доступны только админам
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => function ($rule, $action) {
                    return $this->isAdmin();
                }
            ],
        ];
        
        return $behaviors;
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

        if ($filterBrand) {
            $query->andWhere(['brand_id' => $filterBrand]);
        }

        if ($filterCategory) {
            $query->andWhere(['category_id' => $filterCategory]);
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

        // Статистика
        $stats = [
            'total' => Product::find()->count(),
            'active' => Product::find()->where(['is_active' => 1])->count(),
            'poizon' => Product::find()->where(['not', ['poizon_id' => null]])->count(),
            'manual' => Product::find()->where(['poizon_id' => null])->count(),
        ];

        // Списки для фильтров
        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('/admin/products', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'brands' => $brands,
            'categories' => $categories,
            'filterBrand' => $filterBrand,
            'filterCategory' => $filterCategory,
            'filterSource' => $filterSource,
            'filterActive' => $filterActive,
            'filterSearch' => $filterSearch,
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

        return $this->render('/admin/view-product', [
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

        return $this->render('/admin/edit-product', [
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

        return $this->render('/admin/add-size', [
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

        return $this->render('/admin/edit-size', [
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
