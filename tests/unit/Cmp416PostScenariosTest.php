<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\modules\account\AccountModule;
use app\backend\modules\account\controllers\AccountController;
use app\backend\modules\account\controllers\LoyaltyController;
use app\backend\modules\account\controllers\ReturnController;
use app\backend\modules\account\models\Customer;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\loyalty\models\LoyaltyProgram;
use app\frontend\controllers\OrderController;

/**
 * Регрессионные тесты CMP-416: живой HTTP-прогон покупательских POST-сценариев
 * (регистрация/вход/чекаут/отзывы с кириллицей), дочерняя задача CMP-413.
 *
 * Каждый тест воспроизводит один подтверждённый живой баг, найденный прогоном
 * реальных HTTP-запросов на php -S (см. docs/route-audit/CMP-416-report.md).
 */
class Cmp416PostScenariosTest extends TestCase
{
    private static ?AccountModule $accountModule = null;
    private array $cleanup = [];

    private static function accountModule(): AccountModule
    {
        // AccountModule::init() резолвит viewPath в @frontend/views, а не
        // в свою собственную views-директорию — без модуля рендер уедет
        // в путь тестового приложения. tests/config.php (в отличие от
        // infrastructure/config/web.php) не регистрирует @frontend вообще
        // (bootstrap.php вешает алиасы только при уже загруженном классе Yii,
        // а в tests/bootstrap.php Yii.php require'ится позже) — регистрируем
        // явно, как это делает реальное веб-приложение.
        if (!Yii::getAlias('@frontend', false)) {
            Yii::setAlias('@frontend', dirname(__DIR__, 2) . '/frontend');
        }
        return self::$accountModule ??= new AccountModule('account');
    }

    protected function tearDown(): void
    {
        Yii::$app->session->remove('customer_id');
        foreach (array_reverse($this->cleanup) as $model) {
            try {
                $model->delete();
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
        $this->cleanup = [];
        parent::tearDown();
    }

    private function makeCustomer(): Customer
    {
        $customer = new Customer();
        $customer->email = 'cmp416.' . uniqid() . '@example.com';
        $customer->first_name = 'Дмитрий';
        $customer->last_name = 'Иванов';
        $customer->setPassword('Passw0rd!');
        $customer->generateAuthKey();
        $customer->status = Customer::STATUS_ACTIVE;
        $this->assertTrue($customer->save(), 'Customer::save() упал: ' . json_encode($customer->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $customer;
        Yii::$app->session->set('customer_id', $customer->id);
        return $customer;
    }

    /**
     * Полный actionXxx() рендерит через layout (main.php), который читает
     * Yii::$app->controller и текущий URL (для GridView-пагинации) — в
     * "голом" unit-тесте без реального HTTP-запроса их никто не выставляет.
     * Эмулируем ровно то окружение, которое даёт php -S на реальном запросе.
     */
    private function renderControllerAction(\yii\web\Controller $controller, string $actionMethod): string
    {
        $previousController = Yii::$app->controller;
        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        $previousCurrency = Yii::$app->formatter->currencyCode;
        $previousViewPath = Yii::$app->getViewPath();

        Yii::$app->controller = $controller;
        $_SERVER['REQUEST_URI'] = '/account/' . $controller->id;
        Yii::$app->formatter->currencyCode = 'BYN';
        // infrastructure/config/web.php фиксирует app-level viewPath на
        // frontend/views (layout использует //partials/footer — путь
        // относительно него); tests/config.php этого не делает.
        Yii::$app->setViewPath(dirname(__DIR__, 2) . '/frontend/views');

        try {
            return $controller->$actionMethod();
        } finally {
            Yii::$app->controller = $previousController;
            if ($previousUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousUri;
            }
            Yii::$app->formatter->currencyCode = $previousCurrency;
            Yii::$app->setViewPath($previousViewPath);
        }
    }

    private function makePaidOrder(): Order
    {
        $order = new Order();
        $order->client_name = 'Иванова Мария Сергеевна';
        $order->client_phone = '+375291234567';
        $order->delivery_address = 'г. Минск, ул. Немига, д. 12, кв. 34';
        $order->status = 'paid';
        $this->assertTrue($order->save(false), 'Order::save() упал: ' . json_encode($order->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $order;
        return $order;
    }

    /**
     * GET /account/favorites (AccountController::actionWishlist) → 500
     * ViewNotFoundException: frontend/views/account/wishlist.php не
     * существовал. Представление физически лежало в
     * backend/modules/account/views/account/wishlist.php — мёртвом месте,
     * т.к. AccountModule::init() принудительно резолвит viewPath в
     * @frontend/views для ВСЕХ контроллеров модуля 'account'. Файл перенесён.
     */
    public function testWishlistRendersWithoutViewNotFoundException(): void
    {
        $this->makeCustomer();
        $controller = new AccountController('account', self::accountModule());

        $output = $this->renderControllerAction($controller, 'actionWishlist');

        $this->assertIsString($output);
        $this->assertStringContainsString('Избранное', $output);
    }

    /**
     * GET /account/loyalty/index (LoyaltyController::actionIndex) → 500
     * ViewNotFoundException: frontend/views/loyalty/index.php не
     * существовал (тот же класс бага — файл лежал в
     * backend/modules/account/views/loyalty/index.php, мёртвом месте).
     */
    public function testLoyaltyIndexRendersWithoutViewNotFoundException(): void
    {
        $this->makeCustomer();
        $controller = new LoyaltyController('loyalty', self::accountModule());

        $output = $this->renderControllerAction($controller, 'actionIndex');

        $this->assertIsString($output);
    }

    /**
     * GET /account/returns (ReturnController::actionIndex) → 500
     * ViewNotFoundException: frontend/views/return/index.php не
     * существовал (тот же класс бага, файл лежал в
     * backend/modules/account/views/return/index.php).
     */
    public function testReturnsIndexRendersWithoutViewNotFoundException(): void
    {
        $this->makeCustomer();
        $controller = new ReturnController('return', self::accountModule());

        $output = $this->renderControllerAction($controller, 'actionIndex');

        $this->assertIsString($output);
        $this->assertStringContainsString('возврат', mb_strtolower($output));
    }

    /**
     * GET /account/loyalty/program (LoyaltyController::actionProgram) → 500
     * UnknownPropertyException: getting unknown property
     * LoyaltyProgram::free_shipping (и priority_support) — таких колонок в
     * loyalty_program нет, есть только benefits (JSON-список), для которого
     * уже существует LoyaltyProgram::getBenefitsList(). View переписан на
     * него вместо несуществующих свойств.
     */
    public function testLoyaltyProgramRendersWithoutUnknownPropertyException(): void
    {
        $this->makeCustomer();
        $controller = new LoyaltyController('loyalty', self::accountModule());

        $output = $this->renderControllerAction($controller, 'actionProgram');

        $this->assertIsString($output);

        $levels = LoyaltyProgram::find()->all();
        $this->assertNotEmpty($levels, 'Ожидались сид-уровни программы лояльности (m250315_120200)');
        foreach ($levels as $level) {
            $this->assertIsArray($level->getBenefitsList());
        }
    }

    /**
     * GET /account/loyalty/program: сама модель LoyaltyProgram не должна
     * содержать несуществующие free_shipping/priority_support в исходниках
     * представления — регрессия на класс бага "schema drift" (CMP-410).
     */
    public function testLoyaltyProgramViewDoesNotReferenceNonExistentColumns(): void
    {
        $reflection = new \ReflectionClass(LoyaltyController::class);
        $viewFile = dirname($reflection->getFileName(), 5) . '/frontend/views/loyalty/program.php';
        $this->assertFileExists($viewFile);

        $source = file_get_contents($viewFile);
        $this->assertStringNotContainsString('$level->free_shipping', $source);
        $this->assertStringNotContainsString('$level->priority_support', $source);
    }

    /**
     * POST /order/save-passport?token=... (BY, гражданство по умолчанию) →
     * возвращал {"success":false, errors:{passport_number:"обязателен",
     * passport_series:"2-4 буквы"}} на КАЖДОЙ реальной отправке формы,
     * потому что реальный браузерный JS (frontend/views/order/_passport_form.php
     * + passport-format.js) шлёт passport_series ОДНИМ комбинированным полем
     * (серия+номер, напр. MP1234567) и вообще не шлёт passport_number для BY —
     * ровно как в Order::rules(), AccountController::actionSavePassport() и
     * Order::missingPassportFields(). Серверный код чекаута требовал их
     * раздельно (2-4 буквы + отдельные 7 цифр) — единственное расходящееся
     * место во всём кодовом пути. 100% реальных покупателей с BY-паспортом не
     * могли пройти обязательный шаг таможенных данных после оплаты.
     */
    public function testSavePassportAcceptsCombinedByFormatFromRealBrowserForm(): void
    {
        $order = $this->makePaidOrder();
        $controller = new OrderController('order', Yii::$app);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'citizenship' => 'by',
            'recipient_last_name' => 'Иванова',
            'recipient_first_name' => 'Мария',
            'birth_date' => '1995-06-15',
            // Комбинированное поле — ровно то, что шлёт реальная форма (JS
            // мержит серию+номер в один <input name="passport_series">,
            // отдельного passport_number для BY нет вообще).
            'passport_series' => 'MP1234567',
            'passport_issue_date' => '2015-06-20',
            'passport_issued_by' => 'Минский РОВД',
            'inn' => '1234567A891234',
            'full_address' => 'г. Минск, ул. Немига, д. 12, кв. 34',
            'city' => 'Минск',
            'region' => 'Минская область',
            'postal_code' => '220000',
        ]);

        $result = $controller->actionSavePassport($order->token);
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertTrue(
            $result['success'] ?? false,
            'actionSavePassport() отклонил реальный браузерный payload: ' . json_encode($result, JSON_UNESCAPED_UNICODE)
        );

        $order->refresh();
        $this->assertSame('MP1234567', $order->passport_series);
        $this->assertSame('', $order->passport_number);

        // Данные, сохранённые публичным эндпоинтом, обязаны проходить обычную
        // AR-валидацию (Order::rules()) — иначе их не открыть/не сохранить в
        // админке без ложной ошибки формата.
        $this->assertTrue(
            $order->validate(['passport_series']),
            'Order::rules() отклоняет собственные данные, сохранённые actionSavePassport(): '
                . json_encode($order->getErrors('passport_series'), JSON_UNESCAPED_UNICODE)
        );
        $this->assertTrue($order->isPassportComplete());
    }
}
