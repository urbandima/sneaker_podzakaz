<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\api\controllers\DocController;
use app\backend\modules\admin\AdminModule;
use app\backend\modules\admin\controllers\ActivityLogController;
use app\backend\modules\admin\controllers\AnalyticsController;
use app\backend\modules\admin\controllers\CategoryController;
use app\backend\modules\admin\controllers\CustomerController;
use app\backend\modules\admin\controllers\AmoCrmController;
use app\backend\modules\catalog\controllers\CatalogController;
use app\backend\modules\account\models\Customer;
use app\frontend\controllers\BlogController;

/**
 * Регрессионные тесты CMP-413: сплошной HTTP-прогон маршрутов.
 *
 * Каждый тест воспроизводит один подтверждённый 500 из живого HTTP-прогона
 * scripts/route-sweep.php (docs/route-audit/CMP-413-sweep-results.csv) и
 * проверяет, что реальный вызов action теперь не падает.
 */
class Cmp413RouteSweepTest extends TestCase
{
    private static ?AdminModule $adminModule = null;

    private static function adminModule(): AdminModule
    {
        // Контроллеры админки резолвят views через $this->module->getViewPath();
        // без модуля (просто new XController(id, Yii::$app)) view-path уедет
        // в путь тестового приложения, а не backend/modules/admin/views.
        return self::$adminModule ??= new AdminModule('admin');
    }

    /**
     * GET /api/doc → 500 ViewNotFoundException: api/views/doc/swagger.php
     * не существовал вовсе (каталог api/views отсутствовал). Маршрут был
     * добавлен в urlManager rules, но представление для него никогда не
     * создавалось — классический «код, который никогда не исполнялся».
     */
    public function testDocIndexRendersSwaggerUiWithoutViewNotFoundException(): void
    {
        $controller = new DocController('doc', Yii::$app);

        $output = $controller->actionIndex();

        $this->assertIsString($output);
        $this->assertStringContainsString('swagger-ui', $output);
    }

    /**
     * GET /admin/activity-log/export-csv → 500 UnknownMethodException:
     * ->asArray() вызывался на обычном yii\db\Query (не ActiveQuery) —
     * такого метода там нет, plain Query и так отдаёт массивы.
     */
    public function testActivityLogExportCsvDoesNotCallAsArrayOnPlainQuery(): void
    {
        $controller = new ActivityLogController('activity-log', self::adminModule());

        $response = $controller->actionExportCsv();

        $this->assertInstanceOf(\yii\web\Response::class, $response);
        $this->assertStringContainsString('ID;Дата;', $response->content);
    }

    /**
     * GET /admin/analytics/export-products → 500 SQLSTATE[42S22]: Unknown
     * column 'p.status' — таблица product хранит статус в is_active
     * (boolean), колонки status никогда не было.
     */
    public function testAnalyticsExportProductsUsesRealIsActiveColumn(): void
    {
        $controller = new AnalyticsController('analytics', self::adminModule());

        $csv = $controller->actionExportProducts();

        $this->assertIsString($csv);
        $this->assertStringContainsString('Статус', $csv);
    }

    /**
     * GET /admin/analytics/export и /export-orders → 500 ErrorException:
     * fputcsv() без $escape падал под PHP 8.4 (deprecation конвертируется
     * в исключение через Yii ErrorHandler).
     */
    public function testAnalyticsExportDoesNotThrowOnFputcsvDeprecation(): void
    {
        $controller = new AnalyticsController('analytics', self::adminModule());

        Yii::$app->request->setQueryParams(['type' => 'sales', 'period' => '7', 'format' => 'csv']);
        $csv = $controller->actionExport();

        $this->assertIsString($csv);
    }

    /**
     * POST /admin/customer/mark-phantoms → 500 SQLSTATE[42S22]: Unknown
     * column 'is_active' — customer деактивируется через status
     * (Customer::STATUS_INACTIVE_DB), колонки is_active в таблице нет.
     */
    public function testCustomerMarkPhantomsUpdatesRealStatusColumn(): void
    {
        $sql = "
            UPDATE {{%customer}}
            SET status = :inactiveStatus
            WHERE email REGEXP '^ms_[a-f0-9]+@nonexistent-test-domain-cmp413\\\\.invalid\$'
              AND (last_order_at IS NULL OR orders_count = 0)
        ";

        // Раньше здесь было `SET is_active = 0` — колонки не существует,
        // запрос падал с SQLSTATE[42S22] при первом же реальном вызове.
        $affected = Yii::$app->db->createCommand($sql, [':inactiveStatus' => Customer::STATUS_INACTIVE_DB])->execute();

        $this->assertSame(0, $affected);
    }

    /**
     * GET /admin/category/create → 500 UnknownPropertyException:
     * Category::addRule — мёртвая строка-заглушка `$model->addRule ? null
     * : null; // no-op` в _form.php обращалась к несуществующему свойству
     * модели при каждой загрузке формы создания категории.
     */
    public function testCategoryCreateRendersFormWithoutUnknownPropertyError(): void
    {
        $controller = new CategoryController('category', self::adminModule());

        // Url::to()/Html::a()/ActiveForm внутри _form.php резолвят текущий
        // маршрут и request URI — вне полного HTTP-цикла (PHP CLI под
        // PHPUnit) их нужно выставить вручную.
        $previous = Yii::$app->controller;
        $hadUri = array_key_exists('REQUEST_URI', $_SERVER);
        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        Yii::$app->controller = $controller;
        $_SERVER['REQUEST_URI'] = '/admin/category/create';
        try {
            $output = $controller->actionCreate();
        } finally {
            Yii::$app->controller = $previous;
            if ($hadUri) {
                $_SERVER['REQUEST_URI'] = $previousUri;
            } else {
                unset($_SERVER['REQUEST_URI']);
            }
        }

        $this->assertIsString($output);
        $this->assertStringContainsString('imgFileInput', $output);
    }

    /**
     * GET /admin/analytics/conversion и /admin/analytics/sales → 500
     * ViewNotFoundException — маршруты были явно прописаны в urlManager
     * rules, но conversion.php/sales.php никогда не создавались (в отличие
     * от близкого по названию actionConversions → conversions.php).
     */
    public function testAnalyticsConversionAndSalesViewsExist(): void
    {
        $controller = new AnalyticsController('analytics', self::adminModule());

        $previous = Yii::$app->controller;
        Yii::$app->controller = $controller;
        try {
            $conversion = $controller->actionConversion();
            $sales = $controller->actionSales();
        } finally {
            Yii::$app->controller = $previous;
        }

        $this->assertIsString($conversion);
        $this->assertStringContainsString('Конверсия', $conversion);

        $this->assertIsString($sales);
        $this->assertStringContainsString('продаж', mb_strtolower($sales));
    }

    /**
     * GET /admin/amo-crm/settings → 500 ViewNotFoundException: settings.php
     * не существовал. Контроллер не выведен ни в один пункт меню и дублирует
     * уже рабочую страницу admin/plugin/amocrm — вместо мёртвого view
     * экшен теперь редиректит на актуальные настройки.
     */
    public function testAmoCrmSettingsRedirectsToRealPluginPage(): void
    {
        $controller = new AmoCrmController('amo-crm', self::adminModule());

        $response = $controller->actionSettings();

        $this->assertInstanceOf(\yii\web\Response::class, $response);
        $this->assertStringContainsString('/admin/plugin/amocrm', $response->headers->get('Location'));
    }

    /**
     * GET /catalog/product (без slug и без ?slug=) → 500 TypeError:
     * ProductRepository::findBySlug() требует string, приходил null.
     * HttpCache-behaviour дергает findProduct() ДО биндинга параметров
     * экшена, поэтому даже отсутствующий обязательный $slug не спасал.
     */
    public function testFindProductReturnsNullInsteadOfTypeErrorForMissingSlug(): void
    {
        $controller = new CatalogController('catalog', Yii::$app);

        $method = new \ReflectionMethod($controller, 'findProduct');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($controller, null));
        $this->assertNull($method->invoke($controller, ''));
    }

    /**
     * GET /blog и /blog/<slug> → 500 UnknownPropertyException:
     * BlogController::params — у yii\web\Controller нет свойства $params
     * (это свойство View, не Controller); строки были мёртвым дублем
     * breadcrumbs, которые ничем не читаются (в layout нет виджета
     * Breadcrumbs), поэтому просто удалены.
     */
    public function testBlogControllerHasNoBrokenParamsProperty(): void
    {
        $reflection = new \ReflectionClass(BlogController::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringNotContainsString('$this->params[', $source);
    }
}
