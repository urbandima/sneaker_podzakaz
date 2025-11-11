<?php

namespace app\controllers\admin;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\base\DynamicModel;
use app\models\Characteristic;
use app\models\CharacteristicValue;
use app\models\Product;
use app\models\ProductCharacteristicValue;
use app\models\Brand;
use app\models\Category;

/**
 * CharacteristicController - Управление характеристиками товаров
 * 
 * Только для администраторов.
 * 
 * Методы:
 * - index() - Список характеристик
 * - guide() - Справочник по характеристикам
 */
class CharacteristicController extends BaseAdminController
{
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

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
                'delete-value' => ['POST'],
                'bulk-assign' => ['GET', 'POST'],
                'import' => ['GET', 'POST'],
            ],
        ];

        return $behaviors;
    }

    /**
     * Справочник характеристик товаров
     */
    public function actionGuide()
    {
        return $this->render('/admin/characteristics-guide');
    }

    /**
     * Список характеристик
     */
    public function actionIndex()
    {
        $query = Characteristic::find()->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
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
     * Массовое назначение значения характеристик товарам
     */
    public function actionBulkAssign()
    {
        $model = new DynamicModel(['characteristic_id', 'value_id', 'brand_id', 'category_id', 'price_from', 'price_to']);
        $model->addRule(['characteristic_id', 'value_id'], 'required');
        $model->addRule(['characteristic_id', 'value_id', 'brand_id', 'category_id'], 'integer');
        $model->addRule(['price_from', 'price_to'], 'number');

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $productQuery = Product::find()->where(['is_active' => 1]);

            if ($model->brand_id) {
                $productQuery->andWhere(['brand_id' => $model->brand_id]);
            }
            if ($model->category_id) {
                $productQuery->andWhere(['category_id' => $model->category_id]);
            }
            if ($model->price_from) {
                $productQuery->andWhere(['>=', 'price', $model->price_from]);
            }
            if ($model->price_to) {
                $productQuery->andWhere(['<=', 'price', $model->price_to]);
            }

            $productIds = $productQuery->select('id')->column();

            $data = [];
            foreach ($productIds as $productId) {
                $data[] = [$productId, $model->characteristic_id, $model->value_id, new \yii\db\Expression('NOW()')];
            }

            if ($data) {
                Yii::$app->db->createCommand()->batchInsert(
                    ProductCharacteristicValue::tableName(),
                    ['product_id', 'characteristic_id', 'characteristic_value_id', 'created_at'],
                    $data
                )->execute();
            }

            Yii::$app->session->setFlash('success', 'Массовое назначение выполнено (' . count($data) . ' записей).');
            return $this->redirect(['index']);
        }

        $characteristics = Characteristic::getFilterCharacteristics();
        $brands = Brand::find()->select(['name', 'id'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column();
        $categories = Category::find()->select(['name', 'id'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column();

        return $this->render('bulk-assign', [
            'model' => $model,
            'characteristics' => $characteristics,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * Импорт характеристик из CSV
     */
    public function actionImport()
    {
        $model = new DynamicModel(['file']);
        $model->addRule(['file'], 'required');
        $model->addRule(['file'], 'file', ['extensions' => ['csv'], 'checkExtensionByMimeType' => false]);

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->validate()) {
                $result = $this->importCsv($model->file);
                Yii::$app->session->setFlash($result['status'], $result['message']);
                return $this->redirect(['index']);
            }
        }

        return $this->render('import', [
            'model' => $model,
        ]);
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

    /**
     * Импорт CSV
     */
    protected function importCsv(UploadedFile $file): array
    {
        $rows = array_map('str_getcsv', file($file->tempName));
        if (!$rows) {
            return ['status' => 'error', 'message' => 'Файл пуст'];
        }

        $header = array_map('trim', array_shift($rows));
        $required = ['key', 'name', 'type', 'value'];
        foreach ($required as $column) {
            if (!in_array($column, $header, true)) {
                return ['status' => 'error', 'message' => 'В CSV отсутствует колонка ' . $column];
            }
        }

        $mappedRows = [];
        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                continue;
            }
            $mappedRows[] = array_combine($header, $row);
        }

        $imported = 0;
        foreach ($mappedRows as $row) {
            $characteristic = Characteristic::findOne(['key' => $row['key']]);
            if (!$characteristic) {
                $characteristic = new Characteristic([
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'type' => $row['type'] ?? Characteristic::TYPE_SELECT,
                    'is_filter' => 1,
                    'is_active' => 1,
                ]);
                if (!$characteristic->save()) {
                    continue;
                }
            }

            $value = CharacteristicValue::findOne([
                'characteristic_id' => $characteristic->id,
                'slug' => $row['slug'] ?? null,
            ]);

            if (!$value) {
                $value = new CharacteristicValue([
                    'characteristic_id' => $characteristic->id,
                ]);
            }

            $value->value = $row['value'];
            if (!empty($row['slug'])) {
                $value->slug = $row['slug'];
            }
            $value->sort_order = isset($row['sort_order']) ? (int)$row['sort_order'] : 0;
            $value->is_active = 1;
            if ($value->save()) {
                $imported++;
            }
        }

        return ['status' => 'success', 'message' => "Импортировано {$imported} значений"];
    }
}
