<?php

/**
 * SizeGridController — Управление размерными сетками
 * 
 * НАЗНАЧЕНИЕ:
 * CRUD операции для размерных сеток: шаблоны размеров для разных
 * брендов, категорий и полов (мужские/женские/детские).
 * 
 * ФУНКЦИИ:
 * - Список размерных сеток с фильтрацией (index)
 * - Создание размерной сетки (create)
 * - Редактирование размерной сетки (update)
 * - Удаление размерной сетки (delete)
 * - Управление элементами сетки: добавление, редактирование, удаление
 * - Привязка к бренду и категории
 * - Копирование размерной сетки (copy)
 * 
 * СВЯЗИ:
 * - SizeGrid (модель размерной сетки)
 * - SizeGridItem (элемент размерной сетки)
 * - Brand (модель бренда)
 * - Product (для применения сетки к товару)
 * 
 * ДОСТУП:
 * - Только администраторы
 * 
 * ОСОБЕННОСТИ:
 * - Поддержка разных систем размеров (EU, US, UK, CM)
 * - Автоматический расчёт размера по gender
 */
namespace app\backend\modules\admin\controllers;

use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\SizeGrid;
use app\backend\modules\catalog\models\SizeGridItem;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class SizeGridController extends BaseAdminController
{
    public function behaviors()
    {
        $this->adminOnly = true;
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
                'delete-item' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex()
    {
        $search = Yii::$app->request->get('q');
        $gender = Yii::$app->request->get('gender');
        $brandId = Yii::$app->request->get('brand');
        $status = Yii::$app->request->get('status');

        $query = SizeGrid::find()->with(['brand', 'items'])->orderBy(['created_at' => SORT_DESC]);

        if ($search) {
            $query->andWhere(['like', 'name', $search]);
        }
        if ($gender) {
            $query->andWhere(['gender' => $gender]);
        }
        if ($brandId !== null && $brandId !== '') {
            if ($brandId === '0') {
                $query->andWhere(['brand_id' => null]);
            } else {
                $query->andWhere(['brand_id' => (int)$brandId]);
            }
        }
        if ($status !== null && $status !== '') {
            $query->andWhere(['is_active' => (int)$status]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);

        $stats = [
            'total' => (int)SizeGrid::find()->count(),
            'active' => (int)SizeGrid::find()->where(['is_active' => 1])->count(),
            'withBrand' => (int)SizeGrid::find()->where(['not', ['brand_id' => null]])->count(),
            'sizes' => (int)SizeGridItem::find()->count(),
        ];

        $brandOptions = Brand::find()->orderBy(['name' => SORT_ASC])->select(['name', 'id'])->indexBy('id')->column();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'search' => $search,
            'gender' => $gender,
            'brandId' => $brandId,
            'status' => $status,
            'brandOptions' => $brandOptions,
            'genderOptions' => SizeGrid::getGenderOptions(),
        ]);
    }

    public function actionCreate()
    {
        $model = new SizeGrid();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->flashSuccess('Размерная сетка создана.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('@frontend/views/admin/characteristic/size-create', compact('model', 'brands'));
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->flashSuccess('Размерная сетка обновлена.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('@frontend/views/admin/characteristic/size-update', compact('model', 'brands'));
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->delete()) {
            $this->flashSuccess('Размерная сетка удалена.');
        } else {
            $this->flashError('Не удалось удалить сетку.');
        }

        return $this->redirect(['index']);
    }

    public function actionAddItem($gridId)
    {
        $grid = $this->findModel($gridId);
        $item = new SizeGridItem();
        $item->size_grid_id = $grid->id;

        if ($item->load(Yii::$app->request->post())) {
            $maxSort = SizeGridItem::find()->where(['size_grid_id' => $grid->id])->max('sort_order');
            $item->sort_order = ($maxSort ?? -1) + 1;
            if ($item->save()) {
                $this->flashSuccess('Размер добавлен в сетку.');
            } else {
                $this->flashError('Не удалось добавить размер.');
            }
        } else {
            $this->flashError('Неверные данные размера.');
        }

        return $this->redirect(['update', 'id' => $grid->id]);
    }

    public function actionDeleteItem($id)
    {
        $item = SizeGridItem::findOne($id);
        if (!$item) {
            throw new NotFoundHttpException('Размер не найден.');
        }
        $gridId = $item->size_grid_id;
        if ($item->delete()) {
            $this->flashSuccess('Размер удален.');
        } else {
            $this->flashError('Не удалось удалить размер.');
        }
        return $this->redirect(['update', 'id' => $gridId]);
    }

    protected function findModel($id): SizeGrid
    {
        $model = SizeGrid::find()->where(['id' => $id])->with(['items', 'brand'])->one();
        if ($model === null) {
            throw new NotFoundHttpException('Размерная сетка не найдена.');
        }
        return $model;
    }
}
