<?php

/**
 * LoyaltyController — Контроллер программы лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * Управление баллами лояльности в личном кабинете: просмотр баланса,
 * история начислений/списаний, информация о программе.
 * 
 * ФУНКЦИИ:
 * - index() - история баллов
 * - program() - информация о программе лояльности
 * - balance() - AJAX получение баланса
 */
namespace app\backend\modules\account\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\backend\modules\loyalty\models\LoyaltyPoints;
use app\backend\modules\loyalty\models\LoyaltyProgram;
use app\backend\modules\loyalty\services\LoyaltyService;

class LoyaltyController extends Controller
{
    public $layout = 'main';

    /**
     * Behaviors
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Только для авторизованных
                    ],
                ],
            ],
        ];
    }

    /**
     * История баллов лояльности
     */
    public function actionIndex()
    {
        $customerId = Yii::$app->user->id;
        $loyaltyService = new LoyaltyService();
        
        // Получаем информацию о программе
        $info = $loyaltyService->getCustomerInfo($customerId);
        
        // История операций
        $query = LoyaltyPoints::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'info' => $info,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Информация о программе лояльности
     */
    public function actionProgram()
    {
        $loyaltyService = new LoyaltyService();
        $levels = LoyaltyProgram::find()
            ->orderBy(['min_points' => SORT_ASC])
            ->all();
        
        $customerId = Yii::$app->user->id;
        $info = $loyaltyService->getCustomerInfo($customerId);

        return $this->render('program', [
            'levels' => $levels,
            'info' => $info,
            'service' => $loyaltyService,
        ]);
    }

    /**
     * AJAX получение баланса
     */
    public function actionBalance()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $customerId = Yii::$app->user->id;
        $loyaltyService = new LoyaltyService();
        
        $balance = $loyaltyService->getCustomerBalance($customerId);
        $level = $loyaltyService->getCustomerLevel($customerId);
        
        return [
            'success' => true,
            'balance' => $balance,
            'level' => $level ? [
                'name' => $level->name,
                'multiplier' => $level->points_multiplier,
            ] : null,
        ];
    }
}
