<?php

/**
 * CategoryController — Управление категориями товаров в админ-панели
 *
 * ФУНКЦИИ:
 * - Список категорий с миниатюрой изображения (index)
 * - Просмотр категории (view)
 * - Создание категории (create)
 * - Редактирование категории (update)
 * - Удаление категории (delete)
 * - Загрузка изображения категории (upload-image)
 *
 * СВЯЗИ:
 * - Category (модель категории)
 * - Product (для подсчёта товаров)
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Product;

class CategoryController extends BaseAdminController
{
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Список категорий с миниатюрами
     */
    public function actionIndex()
    {
        $query = Category::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр категории
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Создание категории
     */
    public function actionCreate()
    {
        $model = new Category();
        $model->is_active = true;
        $model->sort_order = 0;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            // Handle file upload
            $imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($imageFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/categories/';
                $fileName = 'cat_' . time() . '_' . uniqid() . '.' . $imageFile->extension;
                if ($imageFile->saveAs($uploadPath . $fileName)) {
                    $model->image = '/uploads/categories/' . $fileName;
                }
            }

            if ($model->save()) {
                $this->flashSuccess('Категория успешно создана');
                $this->logCreate('category', $model->id, $model->name);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $parentCategories = Category::find()
            ->where(['parent_id' => null])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        return $this->render('_form', [
            'model' => $model,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Редактирование категории
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldValues = $model->getAttributes();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            // Handle file upload
            $imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($imageFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/categories/';
                $fileName = 'cat_' . time() . '_' . uniqid() . '.' . $imageFile->extension;
                if ($imageFile->saveAs($uploadPath . $fileName)) {
                    $model->image = '/uploads/categories/' . $fileName;
                }
            }

            if ($model->save()) {
                $this->flashSuccess('Категория успешно обновлена');
                $this->logUpdate('category', $model->id, $model->name, $oldValues, $model->getAttributes());
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $parentCategories = Category::find()
            ->where(['parent_id' => null])
            ->andWhere(['<>', 'id', $model->id])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        return $this->render('_form', [
            'model' => $model,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Удаление категории
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Check if category has products
        $productCount = Product::find()->where(['category_id' => $id])->count();
        if ($productCount > 0) {
            $this->flashError("Нельзя удалить категорию: в ней {$productCount} товаров");
            return $this->redirect(['index']);
        }

        $name = $model->name;
        $model->delete();
        $this->flashSuccess("Категория «{$name}» удалена");
        $this->logDelete('category', $id, $name);

        return $this->redirect(['index']);
    }

    /**
     * Быстрая загрузка изображения (AJAX или обычная форма)
     */
    public function actionUploadImage($id)
    {
        $model = $this->findModel($id);

        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException();
        }

        $imageFile = UploadedFile::getInstanceByName('image');
        if (!$imageFile) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Файл не выбран'];
            }
            $this->flashError('Файл не выбран');
            return $this->redirect(['index']);
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($imageFile->extension), $allowedTypes)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Недопустимый тип файла'];
            }
            $this->flashError('Недопустимый тип файла');
            return $this->redirect(['index']);
        }

        $uploadPath = Yii::getAlias('@webroot') . '/uploads/categories/';
        $fileName = 'cat_' . $id . '_' . time() . '.' . $imageFile->extension;

        if ($imageFile->saveAs($uploadPath . $fileName)) {
            $model->image = '/uploads/categories/' . $fileName;
            $model->save(false);

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'url' => $model->image];
            }
            $this->flashSuccess('Изображение загружено');
        } else {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Ошибка сохранения файла'];
            }
            $this->flashError('Ошибка сохранения файла');
        }

        return $this->redirect(['index']);
    }

    /**
     * Найти модель по ID
     */
    protected function findModel($id)
    {
        $model = Category::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Категория не найдена');
        }
        return $model;
    }
}
