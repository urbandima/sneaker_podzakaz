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
use app\backend\shared\traits\CatalogSeoTrait;

/**
 * Базовый контроллер сайта
 */
class SiteController extends Controller
{
    use CatalogSeoTrait;
    public $layout = 'main'; // Единый layout
    
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
        // SEO метаданные для главной страницы
        $this->view->title = 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД';
        
        // Meta description
        $this->view->registerMetaTag([
            'name' => 'description',
            'content' => 'Оригинальные кроссовки Nike, Adidas, Jordan с доставкой по Беларуси. 100% подлинность, гарантия качества, лучшие цены. Заказывайте онлайн с быстрой доставкой!'
        ]);
        
        // Open Graph теги
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Оригинальные кроссовки Nike, Adidas, Jordan с доставкой по Беларуси. 100% подлинность, гарантия качества.']);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => Yii::$app->request->hostInfo]);
        $this->view->registerMetaTag(['property' => 'og:image', 'content' => Yii::$app->request->hostInfo . '/images/hero-sneakers.png']);
        $this->view->registerMetaTag(['property' => 'og:image:width', 'content' => '1200']);
        $this->view->registerMetaTag(['property' => 'og:image:height', 'content' => '630']);
        
        // Twitter Card
        $this->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
        $this->view->registerMetaTag(['name' => 'twitter:title', 'content' => 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['name' => 'twitter:description', 'content' => 'Оригинальные кроссовки Nike, Adidas, Jordan с доставкой по Беларуси. 100% подлинность.']);
        $this->view->registerMetaTag(['name' => 'twitter:image', 'content' => Yii::$app->request->hostInfo . '/images/hero-sneakers.png']);
        
        // Canonical URL
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->hostInfo]);
        
        // Schema.org Organization
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'СНИКЕРХЭД',
            'url' => Yii::$app->request->hostInfo,
            'logo' => Yii::$app->request->hostInfo . '/images/logo-white.png',
            'description' => 'Оригинальные кроссовки и обувь с доставкой по Беларуси. 100% подлинность, гарантия качества.',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+375-XX-XXX-XX-XX',
                'contactType' => 'customer service',
                'availableLanguage' => 'Russian'
            ],
            'sameAs' => [
                // Добавить ссылки на соцсети при наличии
            ]
        ];
        $this->registerJsonLd($organizationSchema, 'organization');
        
        // Schema.org WebSite с SearchAction
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'СНИКЕРХЭД',
            'url' => Yii::$app->request->hostInfo,
            'description' => 'Оригинальные кроссовки Nike, Adidas, Jordan с доставкой по Беларуси',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => Yii::$app->request->hostInfo . '/catalog?search={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        $this->registerJsonLd($websiteSchema, 'website');
        
        // Загрузка данных для главной страницы
        $popularProducts = $this->getPopularProducts();
        $categories = $this->getCategories();
        $brands = $this->getBrands();
        
        // Проверяем, есть ли главная страница в landing
        $landingView = '@frontend/views/landing/index';
        if (file_exists(Yii::getAlias($landingView . '.php'))) {
            return $this->render($landingView, [
                'popularProducts' => $popularProducts,
                'categories' => $categories,
                'brands' => $brands,
            ]);
        }
        
        // Если нет landing страницы, показываем базовую главную
        return $this->render('index', [
            'popularProducts' => $popularProducts,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
    
    /**
     * Получение популярных товаров для главной страницы
     */
    private function getPopularProducts()
    {
        try {
            // Загружаем 6-8 популярных/новых товаров
            $products = \app\backend\modules\catalog\models\Product::find()
                ->where(['is_active' => true])
                ->with(['brand'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(8)
                ->all();
                
            return $products;
        } catch (\Throwable $e) {
            Yii::error('Failed to load popular products: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Получение категорий для главной страницы
     */
    private function getCategories()
    {
        try {
            $categories = \app\backend\modules\catalog\models\Category::find()
                ->where(['is_active' => true])
                ->orderBy(['sort_order' => SORT_ASC])
                ->limit(6)
                ->all();
                
            return $categories;
        } catch (\Throwable $e) {
            Yii::error('Failed to load categories: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Получение брендов для главной страницы
     */
    private function getBrands()
    {
        try {
            $brands = \app\backend\modules\catalog\models\Brand::find()
                ->where(['is_active' => true])
                ->orderBy(['name' => SORT_ASC])
                ->limit(12)
                ->all();
                
            return $brands;
        } catch (\Throwable $e) {
            Yii::error('Failed to load brands: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Страница договора оферты
     */
    public function actionOfferAgreement()
    {
        return $this->render('offer-agreement');
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
