<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Response;
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

    /**
     * Страница отправки — заказы готовые к отгрузке
     * Показывает заказы со статусом paid/processing с методом Европочта/Белпочта/СДЭК
     */
    public function actionDispatch()
    {
        $carrier  = Yii::$app->request->get('carrier', '');
        $carriers = ['europochta', 'belpochta', 'cdek'];

        $query = Order::find()
            ->where(['in', 'status', ['paid', 'processing']])
            ->andWhere(['in', 'delivery_method', $carriers])
            ->orderBy(['created_at' => SORT_ASC]);

        if ($carrier && in_array($carrier, $carriers)) {
            $query->andWhere(['delivery_method' => $carrier]);
        }

        $orders = $query->all();

        // Counts per carrier for tab badges
        $counts = [];
        foreach ($carriers as $c) {
            $counts[$c] = Order::find()
                ->where(['in', 'status', ['paid', 'processing']])
                ->andWhere(['delivery_method' => $c])
                ->count();
        }

        return $this->render('dispatch', [
            'orders'   => $orders,
            'carrier'  => $carrier,
            'carriers' => $carriers,
            'counts'   => $counts,
        ]);
    }

    /**
     * POST — пометить выбранные заказы как отправленные
     */
    public function actionMarkShipped()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ids = Yii::$app->request->post('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => 'Не выбраны заказы'];
        }
        $ids = array_map('intval', $ids);
        $updated = Order::updateAll(
            ['status' => 'shipped', 'is_shipped' => 1],
            ['id' => $ids]
        );
        return ['success' => true, 'updated' => $updated];
    }
}
