<?php

/**
 * BrandController — Управление брендами в админ-панели
 *
 * ФУНКЦИИ:
 * - Список брендов с миниатюрой логотипа (index)
 * - Просмотр бренда (view)
 * - Создание бренда (create)
 * - Редактирование бренда (update)
 * - Удаление бренда (delete)
 * - Загрузка логотипа (upload-logo)
 *
 * СВЯЗИ:
 * - Brand (модель бренда)
 * - Product (для подсчёта товаров)
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Product;

class BrandController extends BaseAdminController
{
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Список брендов с миниатюрами логотипов
     */
    public function actionIndex()
    {
        $query = Brand::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр бренда
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Создание бренда
     */
    public function actionCreate()
    {
        $model = new Brand();
        $model->is_active = true;
        $model->sort_order = 0;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            // Handle logo upload
            $logoFile = UploadedFile::getInstance($model, 'logoFile');
            if ($logoFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/brands/';
                $fileName = 'brand_' . time() . '_' . uniqid() . '.' . $logoFile->extension;
                if ($logoFile->saveAs($uploadPath . $fileName)) {
                    $model->logo = '/uploads/brands/' . $fileName;
                }
            }

            // Handle cover image upload
            $coverFile = UploadedFile::getInstance($model, 'coverFile');
            if ($coverFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/brands/';
                $fileName = 'brand_cover_' . time() . '_' . uniqid() . '.' . $coverFile->extension;
                if ($coverFile->saveAs($uploadPath . $fileName)) {
                    $model->cover_image = '/uploads/brands/' . $fileName;
                }
            }

            if ($model->save()) {
                $this->flashSuccess('Бренд успешно создан');
                $this->logCreate('brand', $model->id, $model->name);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('_form', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование бренда
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldValues = $model->getAttributes();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            // Handle logo upload
            $logoFile = UploadedFile::getInstance($model, 'logoFile');
            if ($logoFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/brands/';
                $fileName = 'brand_' . time() . '_' . uniqid() . '.' . $logoFile->extension;
                if ($logoFile->saveAs($uploadPath . $fileName)) {
                    $model->logo = '/uploads/brands/' . $fileName;
                }
            }

            // Handle cover image upload
            $coverFile = UploadedFile::getInstance($model, 'coverFile');
            if ($coverFile) {
                $uploadPath = Yii::getAlias('@webroot') . '/uploads/brands/';
                $fileName = 'brand_cover_' . time() . '_' . uniqid() . '.' . $coverFile->extension;
                if ($coverFile->saveAs($uploadPath . $fileName)) {
                    $model->cover_image = '/uploads/brands/' . $fileName;
                }
            }

            if ($model->save()) {
                $this->flashSuccess('Бренд успешно обновлён');
                $this->logUpdate('brand', $model->id, $model->name, $oldValues, $model->getAttributes());
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('_form', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление бренда
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Check if brand has products
        $productCount = Product::find()->where(['brand_id' => $id])->count();
        if ($productCount > 0) {
            $this->flashError("Нельзя удалить бренд: привязано {$productCount} товаров");
            return $this->redirect(['index']);
        }

        $name = $model->name;
        $model->delete();
        $this->flashSuccess("Бренд «{$name}» удалён");
        $this->logDelete('brand', $id, $name);

        return $this->redirect(['index']);
    }

    /**
     * Быстрая загрузка логотипа (AJAX или обычная форма)
     */
    public function actionUploadLogo($id)
    {
        $model = $this->findModel($id);

        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException();
        }

        $logoFile = UploadedFile::getInstanceByName('logo');
        if (!$logoFile) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Файл не выбран'];
            }
            $this->flashError('Файл не выбран');
            return $this->redirect(['index']);
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array(strtolower($logoFile->extension), $allowedTypes)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Недопустимый тип файла'];
            }
            $this->flashError('Недопустимый тип файла');
            return $this->redirect(['index']);
        }

        $uploadPath = Yii::getAlias('@webroot') . '/uploads/brands/';
        $fileName = 'brand_' . $id . '_' . time() . '.' . $logoFile->extension;

        if ($logoFile->saveAs($uploadPath . $fileName)) {
            $model->logo = '/uploads/brands/' . $fileName;
            $model->save(false);

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'url' => $model->logo];
            }
            $this->flashSuccess('Логотип загружен');
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
        $model = Brand::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Бренд не найден');
        }
        return $model;
    }
}
