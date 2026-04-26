<?php

/**
 * ProductTagController — Контроллер управления тегами товаров
 * 
 * НАЗНАЧЕНИЕ:
 * CRUD операции для тегов товаров в админ-панели.
 * Управление тегами, массовое назначение тегов товарам.
 * 
 * ФУНКЦИИ:
 * - Список тегов (index)
 * - Создание тега (create)
 * - Редактирование тега (update)
 * - Удаление тега (delete)
 * - Массовое назначение тегов товарам (assign)
 * - Получение тегов товара AJAX (get-product-tags)
 * 
 * СВЯЗИ:
 * - ProductTag (модель тега)
 * - ProductTagSearch (модель поиска)
 * - Product (модель товара)
 * - ProductTagAssignment (связь товаров и тегов)
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\backend\modules\catalog\models\ProductTag;
use app\backend\modules\catalog\models\ProductTagSearch;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductTagAssignment;

class ProductTagController extends BaseAdminController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Список тегов
     */
    public function actionIndex()
    {
        $searchModel = new ProductTagSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание нового тега
     */
    public function actionCreate()
    {
        $model = new ProductTag();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тег «' . $model->name . '» успешно создан');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование тега
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тег «' . $model->name . '» обновлен');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление тега
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $name = $model->name;
        
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Тег «' . $name . '» удален');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось удалить тег «' . $name . '»');
        }

        return $this->redirect(['index']);
    }

    /**
     * Массовое назначение тегов товарам
     */
    public function actionAssign()
    {
        $products = Product::find()
            ->select(['id', 'name', 'sku'])
            ->where(['is_active' => true])
            ->orderBy(['name' => SORT_ASC])
            ->asArray()
            ->all();

        $tags = ProductTag::getActiveTags();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $productIds = $post['product_ids'] ?? [];
            $tagIds = $post['tag_ids'] ?? [];
            $replaceExisting = !empty($post['replace_existing']);

            if (empty($productIds) || empty($tagIds)) {
                Yii::$app->session->setFlash('warning', 'Выберите товары и теги для назначения');
                return $this->redirect(['assign']);
            }

            $assignedCount = 0;
            foreach ($productIds as $productId) {
                if ($replaceExisting) {
                    // Заменяем все существующие теги
                    ProductTagAssignment::assignTags($productId, $tagIds);
                    $assignedCount++;
                } else {
                    // Добавляем к существующим
                    $existingIds = ProductTagAssignment::getTagIdsByProduct($productId);
                    $newIds = array_unique(array_merge($existingIds, $tagIds));
                    ProductTagAssignment::assignTags($productId, $newIds);
                    $assignedCount++;
                }
            }

            Yii::$app->session->setFlash('success', 
                'Теги назначены для ' . $assignedCount . ' товаров');
            return $this->redirect(['index']);
        }

        return $this->render('assign', [
            'products' => $products,
            'tags' => $tags,
        ]);
    }

    /**
     * Получить теги товара (AJAX)
     */
    public function actionGetProductTags($productId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $product = Product::findOne($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $tags = $product->getTags()->all();
        $tagData = array_map(function($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ];
        }, $tags);

        return [
            'success' => true,
            'tags' => $tagData,
            'productId' => $productId,
            'productName' => $product->name,
        ];
    }

    /**
     * Изменить статус активности тега (AJAX)
     */
    public function actionToggleActive($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        
        if ($model->save(false)) {
            return [
                'success' => true,
                'is_active' => $model->is_active,
                'message' => $model->is_active ? 'Тег активирован' : 'Тег деактивирован',
            ];
        }

        return ['success' => false, 'message' => 'Не удалось изменить статус'];
    }

    /**
     * Найти модель тега
     * @param int $id
     * @return ProductTag
     * @throws \yii\web\NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = ProductTag::findOne($id);
        
        if ($model === null) {
            throw new \yii\web\NotFoundHttpException('Тег не найден');
        }

        return $model;
    }
}
