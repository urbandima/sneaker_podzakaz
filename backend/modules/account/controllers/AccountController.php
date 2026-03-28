<?php

/**
 * AccountController — Контроллер личного кабинета покупателя
 * 
 * НАЗНАЧЕНИЕ:
 * Управление аккаунтом покупателя: регистрация, авторизация, профиль, история заказов.
 * 
 * ФУНКЦИИ:
 * - Регистрация и авторизация покупателей (login, register, logout)
 * - Личный кабинет: просмотр профиля, редактирование данных (profile, settings)
 * - История заказов покупателя (orders, order-view)
 * - Восстановление пароля (forgot-password)
 * - Быстрый доступ к заказам по email/телефону без регистрации (find-orders, quick-access)
 * 
 * СВЯЗИ:
 * - Customer (модель покупателя)
 * - Order (модель заказа)
 * - CustomerLoginForm / CustomerRegisterForm (формы авторизации/регистрации)
 * 
 * БЕЗОПАСНОСТЬ:
 * - Доступ к профилю только для авторизованных покупателей
 * - Проверка владельца заказа при просмотре
 */
namespace app\backend\modules\account\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\account\models\Customer;
use app\backend\modules\account\models\CustomerLoginForm;
use app\backend\modules\account\models\CustomerRegisterForm;
use app\backend\shared\components\RateLimiter;

class AccountController extends Controller
{
    public $layout = 'main';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['profile', 'orders', 'order-view', 'settings', 'logout', 'wishlist'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['profile', 'orders', 'order-view', 'settings', 'logout', 'wishlist'],
                        'matchCallback' => function ($rule, $action) {
                            return $this->isCustomerLoggedIn();
                        },
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    return $this->redirect(['account/login']);
                },
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
     * Проверка авторизации покупателя
     */
    protected function isCustomerLoggedIn()
    {
        return Yii::$app->session->get('customer_id') !== null;
    }

    /**
     * Получение текущего покупателя
     */
    protected function getCustomer()
    {
        $customerId = Yii::$app->session->get('customer_id');
        if ($customerId) {
            return Customer::findOne($customerId);
        }
        return null;
    }

    /**
     * Главная страница личного кабинета
     */
    public function actionIndex()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->redirect(['account/profile']);
        }
        return $this->redirect(['account/login']);
    }

    /**
     * Страница входа
     */
    public function actionLogin()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->redirect(['account/profile']);
        }

        // Rate limiting: 5 попыток за 15 минут
        $ip = Yii::$app->request->userIP;
        RateLimiter::check('login', $ip, 5, 900);

        $model = new CustomerLoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Сбрасываем лимит после успешного входа
            RateLimiter::reset('login', $ip);
            Yii::$app->session->setFlash('success', 'Добро пожаловать!');
            return $this->redirect(['account/profile']);
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Страница регистрации
     */
    public function actionRegister()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->redirect(['account/profile']);
        }

        // Rate limiting: 3 попытки за 15 минут
        $ip = Yii::$app->request->userIP;
        RateLimiter::check('register', $ip, 3, 900);

        $model = new CustomerRegisterForm();

        if ($model->load(Yii::$app->request->post())) {
            $customer = $model->register();
            if ($customer) {
                // Сбрасываем лимит после успешной регистрации
                RateLimiter::reset('register', $ip);
                Yii::$app->session->setFlash('success', 'Регистрация успешна! Добро пожаловать!');
                return $this->redirect(['account/profile']);
            }
        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    /**
     * Выход из аккаунта
     */
    public function actionLogout()
    {
        Yii::$app->session->remove('customer_id');
        Yii::$app->session->remove('customer_email');
        Yii::$app->session->remove('customer_phone');
        Yii::$app->session->remove('customer_name');
        
        // Удаляем cookie
        Yii::$app->response->cookies->remove('customer_token');
        
        Yii::$app->session->setFlash('success', 'Вы успешно вышли из аккаунта');
        return $this->redirect(['account/login']);
    }

    /**
     * Профиль покупателя
     */
    public function actionProfile()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['account/login']);
        }

        $customer->scenario = 'profile';

        if ($customer->load(Yii::$app->request->post()) && $customer->save()) {
            // Обновляем имя в сессии
            Yii::$app->session->set('customer_name', $customer->getFullName());
            Yii::$app->session->set('customer_phone', $customer->phone);
            
            Yii::$app->session->setFlash('success', 'Профиль успешно обновлен');
            return $this->refresh();
        }

        // Получаем последние заказы
        $orders = Order::find()
            ->where(['customer_id' => $customer->id])
            ->orWhere(['client_email' => $customer->email])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        return $this->render('profile', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    /**
     * История заказов
     */
    public function actionOrders()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['account/login']);
        }

        $orders = Order::find()
            ->where(['customer_id' => $customer->id])
            ->orWhere(['client_email' => $customer->email])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('orders', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    /**
     * Просмотр заказа
     */
    public function actionOrderView($id)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['account/login']);
        }

        $order = Order::find()
            ->where(['id' => $id])
            ->andWhere(['or',
                ['customer_id' => $customer->id],
                ['client_email' => $customer->email]
            ])
            ->one();

        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Заказ не найден');
        }

        return $this->render('order-view', [
            'customer' => $customer,
            'order' => $order,
        ]);
    }

    /**
     * Настройки аккаунта (смена пароля)
     */
    public function actionSettings()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['account/login']);
        }

        $passwordChanged = false;
        $newPassword = Yii::$app->request->post('new_password');
        $confirmPassword = Yii::$app->request->post('confirm_password');
        $currentPassword = Yii::$app->request->post('current_password');

        if (Yii::$app->request->isPost && $newPassword) {
            if (!$customer->validatePassword($currentPassword)) {
                Yii::$app->session->setFlash('error', 'Неверный текущий пароль');
            } elseif (strlen($newPassword) < 6) {
                Yii::$app->session->setFlash('error', 'Новый пароль должен содержать минимум 6 символов');
            } elseif ($newPassword !== $confirmPassword) {
                Yii::$app->session->setFlash('error', 'Пароли не совпадают');
            } else {
                $customer->setPassword($newPassword);
                $customer->generateAuthKey();
                if ($customer->save(false)) {
                    $passwordChanged = true;
                    Yii::$app->session->setFlash('success', 'Пароль успешно изменен');
                }
            }
        }

        return $this->render('settings', [
            'customer' => $customer,
            'passwordChanged' => $passwordChanged,
        ]);
    }

    /**
     * Восстановление пароля
     */
    public function actionForgotPassword()
    {
        if ($this->isCustomerLoggedIn()) {
            return $this->redirect(['account/profile']);
        }

        $email = Yii::$app->request->post('email');
        $sent = false;

        if ($email) {
            $customer = Customer::findByEmail($email);
            if ($customer) {
                $customer->generatePasswordResetToken();
                if ($customer->save(false)) {
                    // Здесь можно добавить отправку email
                    $sent = true;
                    Yii::$app->session->setFlash('success', 'Инструкции по восстановлению пароля отправлены на вашу почту');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Пользователь с таким email не найден');
            }
        }

        return $this->render('forgot-password', [
            'sent' => $sent,
        ]);
    }

    /**
     * API: Поиск заказов по email/телефону (для неавторизованных)
     */
    public function actionFindOrders()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $email = Yii::$app->request->post('email');
        $phone = Yii::$app->request->post('phone');

        if (!$email && !$phone) {
            return ['success' => false, 'message' => 'Укажите email или телефон'];
        }

        $query = Order::find()->orderBy(['created_at' => SORT_DESC]);

        // Используем andWhere + OR внутри одного условия, чтобы is_active и прочие
        // фильтры применялись к обоим вариантам поиска (избегаем утечки данных)
        $conditions = [];
        if ($email) {
            $conditions[] = ['client_email' => $email];
            Yii::$app->session->set('customer_email', $email);
        }
        if ($phone) {
            $conditions[] = ['client_phone' => $phone];
            Yii::$app->session->set('customer_phone', $phone);
        }

        if (count($conditions) === 1) {
            $query->andWhere($conditions[0]);
        } else {
            $query->andWhere(array_merge(['or'], $conditions));
        }

        $orders = $query->all();

        return [
            'success' => true,
            'count' => count($orders),
            'orders' => array_map(function($order) {
                return [
                    'id' => $order->id,
                    'token' => $order->token,
                    'status' => $order->status,
                    'statusLabel' => $order->getStatusLabel(),
                    'total' => Yii::$app->formatter->asCurrency($order->total_amount, 'BYN'),
                    'created_at' => Yii::$app->formatter->asDate($order->created_at, 'long'),
                ];
            }, $orders),
        ];
    }

    /**
     * Быстрый вход по email (для гостей с заказами)
     */
    public function actionQuickAccess()
    {
        $email = Yii::$app->request->post('email');
        $phone = Yii::$app->request->post('phone');

        if ($email || $phone) {
            // Проверяем, есть ли аккаунт
            $customer = Customer::find()
                ->where(['is_active' => Customer::STATUS_ACTIVE])
                ->andWhere(['or', ['email' => $email], ['phone' => $phone]])
                ->one();

            if ($customer) {
                // Есть аккаунт - направляем на вход
                Yii::$app->session->setFlash('info', 'У вас уже есть аккаунт. Войдите, используя ваш пароль.');
                return $this->redirect(['account/login', 'email' => $email]);
            }

            // Нет аккаунта - сохраняем в сессию и показываем заказы
            if ($email) {
                Yii::$app->session->set('customer_email', $email);
            }
            if ($phone) {
                Yii::$app->session->set('customer_phone', $phone);
            }
        }

        return $this->redirect(['account/index']);
    }

    /**
     * Избранные товары
     */
    public function actionWishlist()
    {
        $customer = $this->getCustomer();
        
        if (!$customer) {
            Yii::$app->session->setFlash('error', 'Необходимо войти в систему');
            return $this->redirect(['account/login']);
        }

        // Получаем избранные товары из сессии
        $wishlistIds = Yii::$app->session->get('wishlist', []);
        $products = [];
        
        if (!empty($wishlistIds)) {
            $products = \app\backend\modules\catalog\models\Product::find()
                ->where(['id' => $wishlistIds, 'status' => 1])
                ->with(['brand', 'category', 'images'])
                ->all();
        }
        
        return $this->render('wishlist', [
            'customer' => $customer,
            'products' => $products,
        ]);
    }
}
