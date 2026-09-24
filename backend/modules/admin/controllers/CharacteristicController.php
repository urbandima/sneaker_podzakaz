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
                // CMP-418: this key was 'delete-size', but actionSizeDelete's real
                // action id (Yii2 camel2id) is 'size-delete' — the old key matched
                // nothing, so GET actually deleted a size grid unprotected. Also
                // added 'size-delete-item' (actionSizeDeleteItem), which had no
                // entry here at all. Both links already use data-method="post" in
                // characteristic/index.php and size-update.php.
                'size-delete' => ['POST'],
                'size-delete-item' => ['POST'],
                // CMP-430/I: привязка характеристики к конкретному товару —
                // этого сценария в контроллере не было вовсе (только справочник
                // типов и размерные сетки), хотя UI на product/edit.php его
                // подразумевал. Минимальный CRUD для ProductCharacteristicValue.
                'product-attach' => ['POST'],
                'product-update' => ['POST'],
                'product-detach' => ['POST'],
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

    /**
     * CMP-430/I: привязать характеристику к товару (карточка product/edit).
     * Для типов select/text/number/boolean держим одну строку на пару
     * (товар, характеристика) — повторный вызов обновляет значение вместо
     * дублирования строки. Для multiselect допускаем несколько строк.
     */
    public function actionProductAttach()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = (int) Yii::$app->request->post('product_id');
        $characteristicId = (int) Yii::$app->request->post('characteristic_id');

        $product = Product::findOne($productId);
        $characteristic = Characteristic::findOne($characteristicId);
        if (!$product || !$characteristic) {
            return ['success' => false, 'message' => 'Товар или характеристика не найдены'];
        }

        $pcv = null;
        if ($characteristic->type !== Characteristic::TYPE_MULTISELECT) {
            $pcv = ProductCharacteristicValue::findOne([
                'product_id' => $productId,
                'characteristic_id' => $characteristicId,
            ]);
        }
        $pcv = $pcv ?: new ProductCharacteristicValue();
        $pcv->product_id = $productId;
        $pcv->characteristic_id = $characteristicId;

        if (!$this->fillCharacteristicValue($pcv, $characteristic, Yii::$app->request->post())) {
            return ['success' => false, 'message' => 'Укажите значение характеристики'];
        }

        if (!$pcv->save()) {
            return ['success' => false, 'message' => 'Не удалось сохранить: ' . implode(', ', $pcv->getFirstErrors())];
        }

        return ['success' => true];
    }

    /**
     * CMP-430/I: изменить значение уже привязанной характеристики.
     */
    public function actionProductUpdate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pcv = ProductCharacteristicValue::findOne($id);
        if (!$pcv) {
            return ['success' => false, 'message' => 'Привязка не найдена'];
        }
        $characteristic = $pcv->characteristic;
        if (!$characteristic) {
            return ['success' => false, 'message' => 'Характеристика не найдена'];
        }

        if (!$this->fillCharacteristicValue($pcv, $characteristic, Yii::$app->request->post())) {
            return ['success' => false, 'message' => 'Укажите значение характеристики'];
        }

        if (!$pcv->save()) {
            return ['success' => false, 'message' => 'Не удалось сохранить: ' . implode(', ', $pcv->getFirstErrors())];
        }

        return ['success' => true];
    }

    /**
     * CMP-430/I: отвязать характеристику от товара.
     */
    public function actionProductDetach($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pcv = ProductCharacteristicValue::findOne($id);
        if (!$pcv) {
            return ['success' => false, 'message' => 'Привязка не найдена'];
        }
        $pcv->delete();

        return ['success' => true];
    }

    /**
     * Заполняет value_* поля ProductCharacteristicValue по типу характеристики
     * из массива POST-данных ('value_id', 'value_text', 'value_number', 'value_boolean').
     */
    private function fillCharacteristicValue(ProductCharacteristicValue $pcv, Characteristic $characteristic, array $post): bool
    {
        switch ($characteristic->type) {
            case Characteristic::TYPE_SELECT:
            case Characteristic::TYPE_MULTISELECT:
                $valueId = (int) ($post['value_id'] ?? 0);
                if (!$valueId || !CharacteristicValue::findOne(['id' => $valueId, 'characteristic_id' => $characteristic->id])) {
                    return false;
                }
                $pcv->characteristic_value_id = $valueId;
                $pcv->value_text = null;
                $pcv->value_number = null;
                $pcv->value_boolean = null;
                return true;

            case Characteristic::TYPE_NUMBER:
                if (!isset($post['value_number']) || $post['value_number'] === '') {
                    return false;
                }
                $pcv->value_number = (float) $post['value_number'];
                $pcv->characteristic_value_id = null;
                $pcv->value_text = null;
                $pcv->value_boolean = null;
                return true;

            case Characteristic::TYPE_BOOLEAN:
                if (!isset($post['value_boolean']) || $post['value_boolean'] === '') {
                    return false;
                }
                $pcv->value_boolean = (int) (bool) $post['value_boolean'];
                $pcv->characteristic_value_id = null;
                $pcv->value_text = null;
                $pcv->value_number = null;
                return true;

            default: // text
                $text = trim((string) ($post['value_text'] ?? ''));
                if ($text === '') {
                    return false;
                }
                $pcv->value_text = $text;
                $pcv->characteristic_value_id = null;
                $pcv->value_number = null;
                $pcv->value_boolean = null;
                return true;
        }
    }
}
