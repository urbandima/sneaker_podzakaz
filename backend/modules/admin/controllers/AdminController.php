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
    public $layout = 'admin';
    
    public function init()
    {
        parent::init();
        // Регистрируем AdminAsset для админ-панели
        AdminAsset::register(Yii::$app->view);
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
            // ВРЕМЕННО: Простая проверка для разработки
            if ($model->username === 'admin' && $model->password === 'admin123') {
                // Очищаем старые сессии перед входом
                TemporaryAdminIdentity::clearAllSessions();
                
                Yii::info("Admin login successful: {$model->username}", 'admin');
                
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
                $model->addError('password', 'Неверное имя пользователя или пароль. Используйте admin/admin123');
            }
        }

        return $this->render('//admin/login', [
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
