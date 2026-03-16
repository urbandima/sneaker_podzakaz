<?php

/**
 * CharacteristicController — Управление характеристиками товаров
 * 
 * НАЗНАЧЕНИЕ:
 * CRUD операции для характеристик товаров (цвет, размер, материал и т.д.),
 * управление значениями характеристик и размерными сетками.
 * 
 * ФУНКЦИИ:
 * - Справочник характеристик (guide)
 * - Список характеристик (index)
 * - Создание/редактирование характеристики (create, update)
 * - Удаление характеристики (delete)
 * - Управление значениями характеристики (create-value, update-value, delete-value)
 * - Управление размерными сетками (create-size, update-size, delete-size)
 * - Привязка характеристик к категориям/брендам
 * 
 * СВЯЗИ:
 * - Characteristic (модель характеристики)
 * - CharacteristicValue (модель значения характеристики)
 * - ProductCharacteristicValue (связь товара со значением)
 * - SizeGrid (модель размерной сетки)
 * - SizeGridItem (элемент размерной сетки)
 * - Brand, Category (для привязки характеристик)
 * 
 * ДОСТУП:
 * - Только администраторы
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\backend\modules\catalog\models\Characteristic;
use app\backend\modules\catalog\models\CharacteristicValue;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductCharacteristicValue;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\SizeGrid;
use app\backend\modules\catalog\models\SizeGridItem;

class CharacteristicController extends BaseAdminController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->adminOnly = true;
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
                'delete-value' => ['POST'],
                'delete-size' => ['POST'],
            ],
        ];
        return $behaviors;
    }

    /**
     * Справочник характеристик товаров
     */
    public function actionGuide()
    {
        return $this->render('guide');
    }

    /**
     * Список характеристик
     */
    public function actionIndex()
    {
        $tab = Yii::$app->request->get('tab', 'characteristics');

        $characteristicsProvider = new ActiveDataProvider([
            'query' => Characteristic::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        $sizeGridProvider = new ActiveDataProvider([
            'query' => SizeGrid::find()->with(['brand', 'items'])->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'characteristicsProvider' => $characteristicsProvider,
            'sizeGridProvider' => $sizeGridProvider,
            'tab' => $tab,
        ]);
    }

    /**
     * Создание характеристики
     */
    public function actionCreate()
    {
        $model = new Characteristic();
        $model->loadDefaultValues();
        $values = [new CharacteristicValue(['sort_order' => 0])];

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $model->save(false);

                $values = $this->loadCharacteristicValues($model);
                $this->saveCharacteristicValues($model, $values);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Характеристика создана.');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Ошибка сохранения характеристики.');
            }
        }

        return $this->render('create', [
            'model' => $model,
            'values' => $values,
        ]);
    }

    /**
     * Обновление характеристики
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $values = $model->values ?: [new CharacteristicValue(['sort_order' => 0])];

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $model->save(false);

                $values = $this->loadCharacteristicValues($model);
                $this->saveCharacteristicValues($model, $values);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Характеристика обновлена.');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Ошибка обновления характеристики.');
            }
        }

        return $this->render('update', [
            'model' => $model,
            'values' => $values,
        ]);
    }

    /**
     * Удаление характеристики
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            CharacteristicValue::deleteAll(['characteristic_id' => $model->id]);
            ProductCharacteristicValue::deleteAll(['characteristic_id' => $model->id]);
            $model->delete();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Характеристика удалена.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Не удалось удалить характеристику.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Ajax: удалить значение характеристики
     */
    public function actionDeleteValue($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $value = CharacteristicValue::findOne($id);
        if (!$value) {
            return ['success' => false, 'message' => 'Значение не найдено'];
        }

        ProductCharacteristicValue::deleteAll(['characteristic_value_id' => $value->id]);
        $value->delete();

        return ['success' => true];
    }

    /**
     * Поиск модели
     */
    protected function findModel($id): Characteristic
    {
        if (($model = Characteristic::findOne($id)) !== null) {
            return $model;
        }

        throw new \yii\web\NotFoundHttpException('Характеристика не найдена.');
    }

    /**
     * Загрузка значений из POST
     */
    protected function loadCharacteristicValues(Characteristic $model): array
    {
        $valuesData = Yii::$app->request->post('CharacteristicValue', []);
        $values = [];

        foreach ($valuesData as $index => $valueData) {
            $value = isset($valueData['id']) && $valueData['id']
                ? CharacteristicValue::findOne($valueData['id'])
                : new CharacteristicValue();

            $value->load($valueData, '');
            $value->characteristic_id = $model->id;
            $values[] = $value;
        }

        return $values;
    }

    /**
     * Сохранение значений
     */
    protected function saveCharacteristicValues(Characteristic $model, array $values): void
    {
        $keepIds = [];

        foreach ($values as $value) {
            if (!$value->value) {
                continue;
            }
            if (!$value->sort_order) {
                $value->sort_order = 0;
            }
            $value->slug = $value->slug ?: Yii::$app->security->generateRandomString(6);
            $value->characteristic_id = $model->id;
            if ($value->save()) {
                $keepIds[] = $value->id;
            }
        }

        // Удаляем значения, которых нет в форме
        CharacteristicValue::deleteAll([
            'and',
            ['characteristic_id' => $model->id],
            ['not in', 'id', $keepIds ?: [0]],
        ]);
    }

    public function actionSizeCreate()
    {
        $model = new SizeGrid();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка создана.');
            return $this->redirect(['size-update', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('size-create', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    public function actionSizeUpdate($id)
    {
        $model = $this->findSizeGrid($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка обновлена.');
            return $this->redirect(['size-update', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('size-update', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    public function actionSizeDelete($id)
    {
        $model = $this->findSizeGrid($id);
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Размерная сетка удалена.');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось удалить сетку.');
        }

        return $this->redirect(['index', 'tab' => 'sizes']);
    }

    public function actionSizeAddItem($gridId)
    {
        $grid = $this->findSizeGrid($gridId);
        $item = new SizeGridItem();
        $item->size_grid_id = $grid->id;

        if ($item->load(Yii::$app->request->post())) {
            $maxSort = SizeGridItem::find()->where(['size_grid_id' => $grid->id])->max('sort_order');
            $item->sort_order = ($maxSort ?? -1) + 1;
            if ($item->save()) {
                Yii::$app->session->setFlash('success', 'Размер добавлен в сетку.');
            } else {
                Yii::$app->session->setFlash('error', 'Не удалось добавить размер.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Неверные данные размера.');
        }

        return $this->redirect(['size-update', 'id' => $grid->id]);
    }

    public function actionSizeDeleteItem($id)
    {
        $item = SizeGridItem::findOne($id);
        if (!$item) {
            throw new NotFoundHttpException('Размер не найден.');
        }
        $gridId = $item->size_grid_id;
        if ($item->delete()) {
            Yii::$app->session->setFlash('success', 'Размер удален.');
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось удалить размер.');
        }
        return $this->redirect(['size-update', 'id' => $gridId]);
    }
}
