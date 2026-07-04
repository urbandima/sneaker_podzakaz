<?php
/**
 * AdminController — Контроллер входа в админ-панель
 * 
 * НАЗНАЧЕНИЕ:
 * Обработка входа в админ-панель, выход, управление сессией.
 * 
 * ФУНКЦИИ:
 * - actionLogin(): страница входа и обработка формы
 * - actionLogout(): выход из системы
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - /admin/login - вход в админ-панель
 * - /admin/logout - выход из админ-панели
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\backend\modules\admin\models\LoginForm;

class AdminController extends BaseAdminController
{
    public $layout = 'admin'; // Admin layout
    public $viewPath = '@backend/modules/admin/views'; // Явно указываем путь к views
    
    public function init()
    {
        parent::init();
        // AdminAsset регистрируется автоматически через BaseAdminController
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if ($action->id === 'login') {
            // Для страницы входа используем специальный layout
            $this->layout = 'login';
        }
        return parent::beforeAction($action);
    }
    
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
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

    /**
     * Страница входа в админ-панель
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        // Если пользователь уже авторизован, перенаправляем в админку
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin']);
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post())) {
            // AUDIT-70: anti-bruteforce - ограничение попыток входа по IP через кэш
            // (не через session — сессию атакующий может просто не отправлять/пересоздавать)
            $cache = Yii::$app->cache;
            $attemptsKey = 'login_attempts_' . md5(Yii::$app->request->getUserIP());
            $attempts = (int) $cache->get($attemptsKey);

            if ($attempts >= 5) {
                $model->addError('password', 'Слишком много попыток входа. Попробуйте снова через 15 минут.');
                return $this->render('login', ['model' => $model]);
            }

            if ($model->login()) {
                Yii::info("Admin login successful: {$model->username}", 'admin');
                $cache->delete($attemptsKey);
                return $this->redirect(['/admin']);
            } else {
                Yii::warning("Failed login attempt: {$model->username}", 'admin');
                $attempts++;
                $cache->set($attemptsKey, $attempts, 900); // TTL 15 минут
                if ($attempts >= 5) {
                    $model->addError('password', 'Слишком много попыток входа. Попробуйте снова через 15 минут.');
                } else {
                    $remainingAttempts = 5 - $attempts;
                    $model->addError('password', "Неверное имя пользователя или пароль. Осталось попыток: {$remainingAttempts}");
                }
            }
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Выход из админ-панели
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        
        return $this->redirect(['/admin/login']);
    }
}
