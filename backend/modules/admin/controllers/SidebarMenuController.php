<?php

namespace app\backend\modules\admin\controllers;

use app\backend\modules\admin\models\SidebarMenuItem;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * SidebarMenuController
 * Управление боковым меню в админ панели
 */
class SidebarMenuController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список всех пунктов меню
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => SidebarMenuItem::find()
                ->with('parent')
                ->orderBy(['sort_order' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр одного пункта
     */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Создание нового пункта
     */
    public function actionCreate()
    {
        $model = new SidebarMenuItem();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                \Yii::$app->session->setFlash('success', 'Пункт меню создан');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'types' => SidebarMenuItem::getTypesList(),
            'parents' => ArrayHelper::merge(
                ['' => 'Без родителя'],
                SidebarMenuItem::getParentsList()
            ),
        ]);
    }

    /**
     * Редактирование пункта
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            \Yii::$app->session->setFlash('success', 'Пункт меню обновлён');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'types' => SidebarMenuItem::getTypesList(),
            'parents' => ArrayHelper::merge(
                ['' => 'Без родителя'],
                SidebarMenuItem::getParentsList()
            ),
        ]);
    }

    /**
     * Удаление пункта
     */
    public function actionDelete(int $id)
    {
        $this->findModel($id)->delete();
        \Yii::$app->session->setFlash('success', 'Пункт меню удалён');

        return $this->redirect(['index']);
    }

    /**
     * Переключение активности
     */
    public function actionToggle(int $id): \yii\web\Response
    {
        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        $model->save(false);

        return $this->redirect(['index']);
    }

    /**
     * Сортировка пунктов (AJAX)
     */
    public function actionSort(): \yii\web\Response
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $items = $this->request->post('items', []);

        foreach ($items as $index => $id) {
            $model = SidebarMenuItem::findOne($id);
            if ($model) {
                $model->sort_order = $index * 10;
                $model->save(false);
            }
        }

        return ['success' => true];
    }

    /**
     * Поиск модели
     */
    protected function findModel(int $id): SidebarMenuItem
    {
        if (($model = SidebarMenuItem::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Пункт меню не найден');
    }
}
