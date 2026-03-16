<?php

/**
 * ReviewController — Управление отзывами о товарах
 * 
 * НАЗНАЧЕНИЕ:
 * Модерация и управление отзывами покупателей о товарах:
 * публикация, отклонение, удаление.
 * 
 * ФУНКЦИИ:
 * - Список отзывов с фильтрацией (index)
 * - Просмотр отзыва (view)
 * - Публикация отзыва (publish)
 * - Снятие с публикации (unpublish)
 * - Удаление отзыва (delete)
 * - Массовая модерация (batch-moderate)
 * 
 * СВЯЗИ:
 * - ProductReview (модель отзыва)
 * - Product (модель товара)
 * - Customer (модель покупателя)
 * 
 * ДОСТУП:
 * - Администраторы и менеджеры
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\backend\modules\catalog\models\ProductReview;

class ReviewController extends BaseAdminController
{
    public $layout = 'admin';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            return $user->isAdmin() || $user->isManager();
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'publish' => ['POST'],
                    'unpublish' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список отзывов
     */
    public function actionIndex()
    {
        $query = ProductReview::find()->with(['product', 'user'])->orderBy(['created_at' => SORT_DESC]);
        
        // Фильтры
        $status = Yii::$app->request->get('status');
        if ($status === 'published') {
            $query->andWhere(['is_published' => true]);
        } elseif ($status === 'pending') {
            $query->andWhere(['is_published' => false]);
        } elseif ($status === 'featured') {
            $query->andWhere(['is_featured' => true]);
        }
        
        $rating = Yii::$app->request->get('rating');
        if ($rating) {
            $query->andWhere(['rating' => $rating]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
        
        // Статистика
        $stats = [
            'total' => ProductReview::find()->count(),
            'published' => ProductReview::find()->where(['is_published' => true])->count(),
            'pending' => ProductReview::find()->where(['is_published' => false])->count(),
            'featured' => ProductReview::find()->where(['is_featured' => true])->count(),
            'avg_rating' => ProductReview::find()->where(['is_published' => true])->average('rating') ?: 0,
        ];
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }

    /**
     * Просмотр отзыва
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Публикация отзыва
     */
    public function actionPublish($id)
    {
        $model = $this->findModel($id);
        $model->publish();
        
        Yii::$app->session->setFlash('success', 'Отзыв опубликован');
        return $this->redirect(['index']);
    }

    /**
     * Снятие с публикации
     */
    public function actionUnpublish($id)
    {
        $model = $this->findModel($id);
        $model->unpublish();
        
        Yii::$app->session->setFlash('success', 'Отзыв снят с публикации');
        return $this->redirect(['index']);
    }

    /**
     * Удаление отзыва
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        
        Yii::$app->session->setFlash('success', 'Отзыв удален');
        return $this->redirect(['index']);
    }

    /**
     * Ответ администратора
     */
    public function actionRespond($id)
    {
        $model = $this->findModel($id);
        
        if (Yii::$app->request->isPost) {
            $response = Yii::$app->request->post('admin_response');
            $model->addAdminResponse($response);
            
            Yii::$app->session->setFlash('success', 'Ответ сохранен');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        return $this->render('respond', [
            'model' => $model,
        ]);
    }

    /**
     * Массовая публикация
     */
    public function actionBulkPublish()
    {
        $ids = Yii::$app->request->post('ids', []);
        
        if (!empty($ids)) {
            ProductReview::updateAll(['is_published' => true], ['id' => $ids]);
            Yii::$app->session->setFlash('success', 'Отзывы опубликованы');
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Переключение избранного
     */
    public function actionToggleFeatured($id)
    {
        $model = $this->findModel($id);
        $model->is_featured = !$model->is_featured;
        $model->save();
        
        Yii::$app->session->setFlash('success', $model->is_featured ? 'Отзыв добавлен в избранные' : 'Отзыв убран из избранных');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = ProductReview::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Отзыв не найден');
    }
}
