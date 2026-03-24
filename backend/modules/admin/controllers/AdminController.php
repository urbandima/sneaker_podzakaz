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
use app\backend\modules\admin\models\TemporaryAdminIdentity;
use app\backend\modules\admin\assets\AdminAsset;

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
        // ВРЕМЕННО: Для разработки - прямой доступ в админку
        if (YII_ENV_DEV && Yii::$app->request->get('dev') === 'true') {
            // Очищаем старые сессии
            TemporaryAdminIdentity::clearAllSessions();
            
            $identity = new TemporaryAdminIdentity();
            Yii::$app->user->login($identity, 3600*24*30);
            return $this->redirect(['/admin']);
        }
        
        // Если пользователь уже авторизован, перенаправляем в админку
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin']);
        }

        $model = new LoginForm();
        
        if ($model->load(Yii::$app->request->post())) {
            // Проверка на bruteforce - ограничение попыток
            $session = Yii::$app->session;
            $attemptsKey = 'login_attempts_' . md5(Yii::$app->request->getUserIP());
            $attempts = $session->get($attemptsKey, 0);
            
            if ($attempts >= 5) {
                $blockTime = $session->get($attemptsKey . '_blocked_until', 0);
                if ($blockTime > time()) {
                    $remainingTime = $blockTime - time();
                    $model->addError('password', "Слишком много попыток входа. Попробуйте снова через {$remainingTime} секунд.");
                    return $this->render('login', ['model' => $model]);
                } else {
                    // Сбрасываем счетчик после истечения блокировки
                    $session->remove($attemptsKey);
                    $session->remove($attemptsKey . '_blocked_until');
                    $attempts = 0;
                }
            }
            
            // ВРЕМЕННО: Простая проверка для разработки
            if ($model->username === 'admin' && $model->password === 'AdminSecure2026!') {
                // Очищаем старые сессии перед входом
                TemporaryAdminIdentity::clearAllSessions();
                
                Yii::info("Admin login successful: {$model->username}", 'admin');
                
                // Сбрасываем счетчик неудачных попыток
                $session->remove($attemptsKey);
                $session->remove($attemptsKey . '_blocked_until');
                
                // Создаем временного пользователя для сессии
                $identity = new TemporaryAdminIdentity();
                
                if (Yii::$app->user->login($identity, 3600*24*30)) {
                    Yii::info("Session created successfully", 'admin');
                    return $this->redirect(['/admin']);
                } else {
                    Yii::error("Failed to create session", 'admin');
                    $model->addError('password', 'Ошибка создания сессии');
                }
            } else {
                Yii::warning("Failed login attempt: {$model->username}", 'admin');
                
                // Увеличиваем счетчик неудачных попыток
                $attempts++;
                $session->set($attemptsKey, $attempts);
                
                // Блокируем на 15 минут после 5 попыток
                if ($attempts >= 5) {
                    $session->set($attemptsKey . '_blocked_until', time() + 900); // 15 минут
                    $model->addError('password', 'Слишком много попыток входа. Попробуйте снова через 15 минут.');
                } else {
                    $remainingAttempts = 5 - $attempts;
                    $model->addError('password', "Неверное имя пользователя или пароля. Осталось попыток: {$remainingAttempts}");
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
