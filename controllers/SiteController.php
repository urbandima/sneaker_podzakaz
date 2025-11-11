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
            // Оставляем стандартный ErrorAction, но переопределим его view в actionError
        ];
    }
    
    /**
     * Кастомная обработка ошибок
     * Для 404 показываем премиум страницу с поиском
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            // Для 404 показываем кастомную страницу
            if ($exception->statusCode === 404) {
                return $this->render404();
            }
            
            // Для остальных ошибок - стандартная страница
            return $this->render('error', [
                'name' => $exception->getName(),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }
    
    /**
     * Кастомная 404 страница с поиском и популярными категориями
     */
    private function render404()
    {
        $this->layout = 'public';
        Yii::$app->response->statusCode = 404;
        
        // Получаем популярные бренды
        $brands = \app\models\Brand::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->limit(8)
            ->all();
        
        return $this->render('404', [
            'brands' => $brands,
        ]);
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
