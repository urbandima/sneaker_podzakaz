<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use app\backend\modules\checkout\models\Order;

/**
 * Контроллер управления доставкой
 * Показывает заказы в пути (статусы processing, shipped)
 */
class ShippingController extends BaseAdminController
{
    /**
     * Главная страница - заказы в пути
     */
    public function actionIndex()
    {
        // Получаем заказы в пути (статусы: processing, shipped)
        $query = Order::find()
            ->where(['in', 'status', ['processing', 'shipped']])
            ->orWhere(['is_shipped' => 1])
            ->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
}
