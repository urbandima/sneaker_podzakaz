<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\LoginForm;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        $this->layout = 'public';
        return $this->render('index');
    }
    
    /**
     * Страница "О нас"
     */
    public function actionAbout()
    {
        $this->layout = 'public';
        return $this->render('about');
    }
    
    /**
     * Страница "Контакты"
     */
    public function actionContacts()
    {
        $this->layout = 'public';
        return $this->render('contacts');
    }
    
    /**
     * Отслеживание заказа
     */
    public function actionTrack()
    {
        $this->layout = 'public';
        $orderNumber = Yii::$app->request->get('order');
        
        return $this->render('track', [
            'orderNumber' => $orderNumber,
        ]);
    }
    
    /**
     * Корзина
     */
    public function actionCart()
    {
        $this->layout = 'public';
        return $this->render('cart');
    }
    
    /**
     * Личный кабинет
     */
    public function actionAccount()
    {
        $this->layout = 'public';
        return $this->render('account');
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['admin/index']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['admin/index']);
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['login']);
    }
    
    /**
     * Публичная страница договора оферты
     * Доступна без авторизации
     */
    public function actionOfferAgreement()
    {
        $this->layout = 'public';
        return $this->render('offer-agreement');
    }
    
    /**
     * Инструкция по оплате на юридическое лицо
     * Доступна без авторизации
     */
    public function actionPaymentInstruction()
    {
        $this->layout = 'public';
        return $this->render('payment-instruction');
    }
}
