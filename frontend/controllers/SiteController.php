<?php

/**
 * SiteController — Базовый контроллер сайта
 * 
 * НАЗНАЧЕНИЕ:
 * Основной контроллер для обработки публичных страниц сайта.
 * Отображение главной страницы, ошибок, базовой навигации.
 * 
 * ФУНКЦИИ:
 * - actionIndex(): главная страница
 * - actionError(): обработка ошибок
 * - actions(): внешние действия (captcha, etc.)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * Автоматически обрабатывает маршруты:
 * - / → actionIndex()
 * - /site/error → actionError()
 */
namespace app\frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Базовый контроллер сайта
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'login'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?', '@'],
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

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Главная страница сайта
     *
     * @return string
     */
    public function actionIndex()
    {
        // Проверяем, есть ли главная страница в landing
        $landingView = '@frontend/views/landing/index';
        if (file_exists(Yii::getAlias($landingView . '.php'))) {
            return $this->render($landingView);
        }
        
        // Если нет landing страницы, показываем базовую главную
        return $this->render('index');
    }
    
    /**
     * Страница входа в систему
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        // Если пользователь уже авторизован, перенаправляем в зависимости от роли
        if (!Yii::$app->user->isGuest) {
            // Проверяем, является ли пользователь администратором
            if (Yii::$app->user->identity->isAdmin ?? false) {
                return $this->redirect(['/admin']);
            }
            // Иначе перенаправляем в личный кабинет
            return $this->redirect(['/account']);
        }
        
        // Для CLI режима показываем страницу входа напрямую
        if (php_sapi_name() === 'cli') {
            // Создаем модель формы
            $model = new \app\backend\modules\account\models\CustomerLoginForm();
            return $this->render('//account/login', ['model' => $model]);
        }
        
        // Перенаправляем на соответствующий контроллер входа
        return $this->redirect(['/account/login']);
    }
    
    /**
     * Выход из системы
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        
        return $this->goHome();
    }
    
    /**
     * Отображение страницы ошибки
     *
     * @return string
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        
        if ($exception !== null) {
            $statusCode = $exception->statusCode;
            $name = $exception->getName();
            $message = $exception->getMessage();
            
            // Если AJAX запрос, возвращаем JSON
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'error' => [
                        'code' => $statusCode,
                        'name' => $name,
                        'message' => $message,
                    ],
                ];
            }
            
            // Иначе показываем страницу ошибки
            return $this->render('error', [
                'exception' => $exception,
                'statusCode' => $statusCode ?? 500,
                'name' => $name,
                'message' => $message,
            ]);
        }
        
        return $this->render('error');
    }
}
