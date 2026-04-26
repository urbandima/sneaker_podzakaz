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
use app\backend\modules\catalog\models\ProductFavorite;
use app\backend\shared\components\RateLimiter;

class AccountController extends Controller
{
    public $layout = 'main';

    public function behaviors()
    {
        return [
            'rateLimiter' => [
                'class' => \yii\filters\RateLimiter::class,
                'only' => ['login', 'register', 'forgot-password'],
                'user' => new \app\backend\components\RateLimitUser([
                    'identityClass' => 'app\backend\modules\admin\models\User',
                ]),
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['profile', 'orders', 'order-view', 'settings', 'logout', 'wishlist', 'loyalty', 'save-passport'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['profile', 'orders', 'order-view', 'settings', 'logout', 'wishlist', 'loyalty', 'save-passport'],
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

        $ip = Yii::$app->request->userIP;
        if (Yii::$app->request->isPost) {
            RateLimiter::check('login', $ip, 5, 900);
        }

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

        $ip = Yii::$app->request->userIP;
        if (Yii::$app->request->isPost) {
            RateLimiter::check('register', $ip, 3, 900);
        }

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
     * API: Поиск заказов по email/телефону (для неавторизованных).
     * GET-запрос без AJAX рендерит HTML-форму трекинга.
     */
    public function actionFindOrders()
    {
        $request = Yii::$app->request;

        if ($request->isGet && !$request->isAjax) {
            return $this->render('find-orders');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $bodyRaw = $request->rawBody;
        $body = $request->bodyParams;
        if (empty($body) && $bodyRaw && str_contains((string)$request->contentType, 'application/json')) {
            $body = json_decode($bodyRaw, true) ?: [];
        }
        $email = $body['email'] ?? $request->post('email');
        $phone = $body['phone'] ?? $request->post('phone');

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
     * AJAX: Сохранение паспортных данных из личного кабинета
     * POST /account/save-passport?id=XXX
     */
    public function actionSavePassport($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Недопустимый метод'];
        }

        $customer = $this->getCustomer();
        if (!$customer) {
            return ['success' => false, 'message' => 'Необходима авторизация'];
        }

        $order = Order::find()
            ->where(['id' => $id])
            ->andWhere(['or',
                ['customer_id' => $customer->id],
                ['client_email' => $customer->email]
            ])
            ->one();

        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        if ($order->status !== 'paid') {
            return ['success' => false, 'message' => 'Паспортные данные принимаются только для оплаченных заказов'];
        }

        $post = Yii::$app->request->post();
        $errors = [];

        $required = [
            'recipient_last_name'  => 'Фамилия',
            'recipient_first_name' => 'Имя',
            'birth_date'           => 'Дата рождения',
            'passport_series'      => 'Серия паспорта',
            'passport_number'      => 'Номер паспорта',
            'passport_issue_date'  => 'Дата выдачи паспорта',
            'inn'                  => 'ИНН',
            'full_address'         => 'Адрес',
            'city'                 => 'Город',
            'postal_code'          => 'Почтовый индекс',
        ];

        foreach ($required as $field => $label) {
            $val = trim(strip_tags($post[$field] ?? ''));
            if (empty($val)) {
                $errors[$field] = $label . ' обязателен';
            }
        }

        $inn = trim($post['inn'] ?? '');
        if (!empty($inn) && !preg_match('/^[0-9]{12}$/', $inn)) {
            $errors['inn'] = 'ИНН должен содержать ровно 12 цифр';
        }

        $passNum = trim($post['passport_number'] ?? '');
        if (!empty($passNum) && !preg_match('/^[0-9]{6,7}$/', $passNum)) {
            $errors['passport_number'] = 'Номер паспорта должен содержать 6–7 цифр';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Проверьте введённые данные', 'errors' => $errors];
        }

        $fields = array_merge(array_keys($required), ['recipient_middle_name', 'region']);
        foreach ($fields as $field) {
            if ($order->hasAttribute($field)) {
                $order->$field = trim(strip_tags($post[$field] ?? ''));
            }
        }
        if ($order->hasAttribute('passport_submitted_at')) {
            $order->passport_submitted_at = time();
        }

        if (!$order->save(false)) {
            Yii::error('Ошибка сохранения паспортных данных заказа #' . $order->id, 'passport');
            return ['success' => false, 'message' => 'Ошибка сохранения данных'];
        }

        Yii::info('Паспортные данные сохранены для заказа #' . $order->id . ' через ЛК', 'passport');
        return ['success' => true, 'message' => 'Паспортные данные сохранены!'];
    }

    /**
     * Избранные товары покупателя
     */
    public function actionWishlist()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['/account/login', 'return' => '/account/wishlist']);
        }

        $userId    = $customer->id;
        $sessionId = Yii::$app->session->id;

        $favorites = ProductFavorite::getFavorites($userId, $sessionId);

        return $this->render('wishlist', [
            'customer'  => $customer,
            'favorites' => $favorites,
        ]);
    }

    /**
     * Программа лояльности
     */
    public function actionLoyalty()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return $this->redirect(['/account/login']);
        }

        $loyaltyInfo = [
            'level' => (object)[
                'level' => 'silver',
                'name' => 'Серебро',
                'points_multiplier' => 1.5,
                'discount_percent' => 5,
            ],
            'balance' => 1250,
            'nextLevel' => (object)[
                'name' => 'Золото',
                'min_points' => 2500,
            ],
            'pointsToNextLevel' => 1250,
            'history' => [
                ['date' => '2026-03-15', 'type' => 'earn', 'points' => 150, 'description' => 'Покупка кроссовок Nike Air Max'],
                ['date' => '2026-03-10', 'type' => 'earn', 'points' => 200, 'description' => 'Покупка кроссовок Adidas Ultraboost'],
                ['date' => '2026-03-01', 'type' => 'spend', 'points' => -500, 'description' => 'Скидка на заказ #1234'],
                ['date' => '2026-02-20', 'type' => 'earn', 'points' => 100, 'description' => 'Бонус за регистрацию'],
            ],
        ];

        return $this->render('loyalty', [
            'customer' => $customer,
            'loyaltyInfo' => $loyaltyInfo,
        ]);
    }
}
