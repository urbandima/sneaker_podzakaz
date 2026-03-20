<?php

/**
 * CouponController — Контроллер управления купонами в админке
 * 
 * НАЗНАЧЕНИЕ:
 * CRUD операции для купонов, статистика использования, управление активностью.
 * 
 * ФУНКЦИИ:
 * - index() - список всех купонов
 * - view() - просмотр купона и статистики
 * - create() - создание нового купона
 * - update() - редактирование купона
 * - delete() - удаление купона
 * - toggle() - активация/деактивация купона
 * - statistics() - статистика использования
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\backend\modules\coupon\models\Coupon;
use app\backend\modules\coupon\models\CouponUsage;

class CouponController extends BaseAdminController
{
    /**
     * Behaviors
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Список всех купонов
     */
    public function actionIndex()
    {
        $query = Coupon::find()->orderBy(['created_at' => SORT_DESC]);

        // Фильтрация
        $status = Yii::$app->request->get('status');
        if ($status !== null && $status !== '') {
            $query->andWhere(['is_active' => (int)$status]);
        }

        $type = Yii::$app->request->get('type');
        if ($type) {
            $query->andWhere(['type' => $type]);
        }

        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere(['or',
                ['like', 'code', $search],
                ['like', 'name', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр купона
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Статистика использования
        $usageQuery = CouponUsage::find()
            ->where(['coupon_id' => $id])
            ->orderBy(['created_at' => SORT_DESC]);

        $usageProvider = new ActiveDataProvider([
            'query' => $usageQuery,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->render('view', [
            'model' => $model,
            'usageProvider' => $usageProvider,
        ]);
    }

    /**
     * Создание нового купона
     */
    public function actionCreate()
    {
        $model = new Coupon();

        if ($model->load(Yii::$app->request->post())) {
            // Преобразуем код в верхний регистр
            $model->code = strtoupper($model->code);
            
            // Обработка JSON полей
            if (Yii::$app->request->post('applicable_products')) {
                $model->applicable_products = json_encode(
                    array_filter(explode(',', Yii::$app->request->post('applicable_products')))
                );
            }
            
            if (Yii::$app->request->post('applicable_categories')) {
                $model->applicable_categories = json_encode(
                    array_filter(explode(',', Yii::$app->request->post('applicable_categories')))
                );
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Купон успешно создан');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при создании купона');
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование купона
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Преобразуем код в верхний регистр
            $model->code = strtoupper($model->code);
            
            // Обработка JSON полей
            if (Yii::$app->request->post('applicable_products')) {
                $model->applicable_products = json_encode(
                    array_filter(explode(',', Yii::$app->request->post('applicable_products')))
                );
            }
            
            if (Yii::$app->request->post('applicable_categories')) {
                $model->applicable_categories = json_encode(
                    array_filter(explode(',', Yii::$app->request->post('applicable_categories')))
                );
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Купон успешно обновлён');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при обновлении купона');
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление купона
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, использовался ли купон
        $usageCount = CouponUsage::find()->where(['coupon_id' => $id])->count();
        
        if ($usageCount > 0) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить купон, который уже использовался. Деактивируйте его вместо удаления.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Купон успешно удалён');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении купона');
        }

        return $this->redirect(['index']);
    }

    /**
     * Переключение активности купона
     */
    public function actionToggle($id)
    {
        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        
        if ($model->save(false, ['is_active'])) {
            $status = $model->is_active ? 'активирован' : 'деактивирован';
            Yii::$app->session->setFlash('success', "Купон {$status}");
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при изменении статуса');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Статистика использования купонов
     */
    public function actionStatistics()
    {
        $stats = [
            'total' => Coupon::find()->count(),
            'active' => Coupon::find()->where(['is_active' => 1])->count(),
            'inactive' => Coupon::find()->where(['is_active' => 0])->count(),
            'used' => Coupon::find()->where(['>', 'current_uses', 0])->count(),
            'totalUsages' => CouponUsage::find()->count(),
            'totalDiscount' => CouponUsage::find()->sum('discount_amount') ?: 0,
        ];

        // Топ купонов по использованию
        $topCoupons = Coupon::find()
            ->where(['>', 'current_uses', 0])
            ->orderBy(['current_uses' => SORT_DESC])
            ->limit(10)
            ->all();

        // Статистика по типам
        $typeStats = Coupon::find()
            ->select(['type', 'COUNT(*) as count', 'SUM(current_uses) as uses'])
            ->groupBy('type')
            ->asArray()
            ->all();

        return $this->render('statistics', [
            'stats' => $stats,
            'topCoupons' => $topCoupons,
            'typeStats' => $typeStats,
        ]);
    }

    /**
     * Генерация случайного кода купона
     */
    public function actionGenerateCode()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $prefix = Yii::$app->request->post('prefix', '');
        $length = Yii::$app->request->post('length', 8);
        
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = $prefix;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Проверяем уникальность
        while (Coupon::find()->where(['code' => $code])->exists()) {
            $code = $prefix;
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        }
        
        return ['code' => $code];
    }

    /**
     * Найти модель по ID
     */
    protected function findModel($id)
    {
        if (($model = Coupon::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Купон не найден');
    }
}
