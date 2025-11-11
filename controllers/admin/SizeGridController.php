<?php

namespace app\controllers\admin;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\models\SizeGrid;
use app\models\SizeGridItem;
use app\models\Brand;

/**
 * SizeGridController - Управление размерными сетками
 * 
 * Только для администраторов.
 * 
 * Методы:
 * - index() - Список размерных сеток
 * - create() - Создание размерной сетки
 * - edit($id) - Редактирование размерной сетки
 * - delete($id) - Удаление размерной сетки
 * - guide() - Справочная информация по размерам
 * - addItem($gridId) - Добавить размер в сетку
 * - deleteItem($id) - Удалить размер из сетки
 */
class SizeGridController extends BaseAdminController
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
        
        return $behaviors;
    }

    /**
     * Список размерных сеток
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => SizeGrid::find()->with(['brand'])->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('/admin/size-grids', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание новой размерной сетки
     */
    public function actionCreate()
    {
        $model = new SizeGrid();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->flashSuccess('Размерная сетка создана');
            return $this->redirect(['/admin/size-grid/edit', 'id' => $model->id]);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('/admin/create-size-grid', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    /**
     * Редактирование размерной сетки
     * 
     * @param int $id
     */
    public function actionEdit($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->flashSuccess('Размерная сетка обновлена');
            return $this->redirect(['/admin/size-grid/index']);
        }

        $brands = Brand::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('/admin/edit-size-grid', [
            'model' => $model,
            'brands' => $brands,
        ]);
    }

    /**
     * Удаление размерной сетки
     * 
     * @param int $id
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->delete()) {
            $this->flashSuccess('Размерная сетка удалена');
        } else {
            $this->flashError('Ошибка при удалении размерной сетки');
        }

        return $this->redirect(['/admin/size-grid/index']);
    }

    /**
     * Справочная информация по размерам
     */
    public function actionGuide()
    {
        return $this->render('/admin/size-guide');
    }

    /**
     * Добавить размер в сетку
     * 
     * @param int $gridId
     */
    public function actionAddItem($gridId)
    {
        $grid = $this->findModel($gridId);

        $item = new SizeGridItem();
        $item->size_grid_id = $gridId;

        if ($item->load(Yii::$app->request->post())) {
            // Автоматически определяем sort_order
            $maxSort = SizeGridItem::find()
                ->where(['size_grid_id' => $gridId])
                ->max('sort_order');
            $item->sort_order = ($maxSort ?? -1) + 1;

            if ($item->save()) {
                $this->flashSuccess('Размер добавлен в сетку');
            } else {
                $this->flashError('Ошибка при добавлении размера');
            }
        }

        return $this->redirect(['/admin/size-grid/edit', 'id' => $gridId]);
    }

    /**
     * Удалить размер из сетки
     * 
     * @param int $id
     */
    public function actionDeleteItem($id)
    {
        $item = SizeGridItem::findOne($id);
        
        if ($item) {
            $gridId = $item->size_grid_id;
            $item->delete();
            $this->flashSuccess('Размер удален из сетки');
            return $this->redirect(['/admin/size-grid/edit', 'id' => $gridId]);
        }

        throw new NotFoundHttpException('Размер не найден');
    }

    /**
     * Найти модель размерной сетки
     * 
     * @param int $id
     * @return SizeGrid
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = SizeGrid::findOne($id);
        
        if ($model === null) {
            throw new NotFoundHttpException('Размерная сетка не найдена');
        }
        
        return $model;
    }
}
