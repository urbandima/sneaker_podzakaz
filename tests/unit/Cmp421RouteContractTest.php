<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\admin\controllers\BuyoutController;
use app\backend\modules\admin\controllers\ReceivingController;
use app\backend\modules\admin\controllers\UserController;
use app\backend\modules\admin\controllers\ProductController;
use app\backend\modules\account\controllers\LoyaltyController;
use app\backend\modules\catalog\controllers\CatalogApiController;

/**
 * Регрессионные тесты CMP-421: сверка контракта фронтенд-JS ↔ реальные маршруты.
 *
 * Каждый тест воспроизводит один подтверждённый живым HTTP-прогоном 404
 * (docs/route-audit/CMP-421-report.md) и проверяет, что исправленный URL
 * ссылается на реально существующий action с совместимой сигнатурой.
 */
class Cmp421RouteContractTest extends TestCase
{
    /**
     * GET /api/v1/loyalty/balance → 404 (несуществующий "api/v1" неймспейс,
     * такого маршрута и модуля v1 нет вовсе). Рабочий эндпоинт с тем же
     * контрактом ({success, balance, level}) — /account/loyalty/balance.
     */
    public function testCartPromoLoyaltyUsesRealBalanceEndpoint(): void
    {
        $source = file_get_contents(__DIR__ . '/../../frontend/web/js/cart-promo-loyalty.js');

        $this->assertStringNotContainsString('/api/v1/loyalty/balance', $source);
        $this->assertStringContainsString("fetch('/account/loyalty/balance'", $source);
        $this->assertTrue(method_exists(LoyaltyController::class, 'actionBalance'));
    }

    /**
     * GET /catalog/get-brands → 404 (нет такого "controller" внутри модуля
     * catalog по умолчанию). Реальный маршрут — /api/catalog/get-brands
     * (явное правило urlManager → CatalogApiController::actionGetBrands).
     */
    public function testPublicLayoutUsesRealGetBrandsEndpoint(): void
    {
        $source = file_get_contents(__DIR__ . '/../../frontend/web/js/public-layout.js');

        $this->assertStringNotContainsString("fetch('/catalog/get-brands')", $source);
        $this->assertStringContainsString("fetch('/api/catalog/get-brands')", $source);
        $this->assertTrue(method_exists(CatalogApiController::class, 'actionGetBrands'));
    }

    /**
     * GET /catalog/products-by-ids → 404 по той же причине. Рабочий
     * маршрут (уже используется на странице избранного) —
     * /api/catalog/products-by-ids.
     */
    public function testViewHistoryUsesRealProductsByIdsEndpoint(): void
    {
        $source = file_get_contents(__DIR__ . '/../../frontend/web/js/view-history.js');

        $this->assertStringNotContainsString('`/catalog/products-by-ids?', $source);
        $this->assertStringContainsString('`/api/catalog/products-by-ids?', $source);
        $this->assertTrue(method_exists(CatalogApiController::class, 'actionProductsByIds'));
    }

    /**
     * quick-view.js вызывал /api/v1/product/<id>/quick-view,
     * /api/v1/cart/add и /api/v1/wishlist/toggle — все три 404. Файл не
     * подключён ни через один реальный Yii2 AssetBundle (CatalogAsset::$js
     * его не содержит) и его openQuickView()/closeQuickView() затенены
     * одноимёнными функциями в catalog/index.php — код был недостижим.
     * Удалён как мёртвый JS, а не оставлен с обманчиво нерабочими вызовами.
     */
    public function testDeadQuickViewJsFileWasRemoved(): void
    {
        $this->assertFileDoesNotExist(__DIR__ . '/../../frontend/web/js/quick-view.js');

        $gulpfile = file_get_contents(__DIR__ . '/../../frontend/gulpfile.js');
        $this->assertStringNotContainsString('quick-view.js', $gulpfile);

        $catalogAsset = file_get_contents(__DIR__ . '/../../frontend/assets/CatalogAsset.php');
        $this->assertStringNotContainsString('quick-view.js', $catalogAsset);
    }

    /**
     * Кнопка «Дублировать товар» на /admin/product/<id> генерировала
     * Url::to(['/admin/product/duplicate', ...]) → 404 (реальный action
     * называется actionClone, никакого actionDuplicate нет и не было).
     */
    public function testProductViewDuplicateButtonLinksToRealCloneAction(): void
    {
        $view = file_get_contents(__DIR__ . '/../../backend/modules/admin/views/product/view.php');

        $this->assertStringNotContainsString("'/admin/product/duplicate'", $view);
        $this->assertStringContainsString("'/admin/product/clone'", $view);
        $this->assertTrue(method_exists(ProductController::class, 'actionClone'));
    }

    /**
     * Ссылки на объект в /admin/activity-log для типов Buyout/Receiving/User
     * вели на несуществующие маршруты (лишний сегмент "procurement/" для
     * buyout и receiving; у UserController вообще нет actionView).
     * Исправлено на реальные action'ы этих контроллеров.
     */
    public function testActivityLogTargetUrlsPointToRealActions(): void
    {
        $view = file_get_contents(__DIR__ . '/../../backend/modules/admin/views/activity-log/index.php');

        $this->assertStringNotContainsString("'Buyout'    => '/admin/procurement/buyout/view'", $view);
        $this->assertStringNotContainsString("'Receiving' => '/admin/procurement/receiving/view'", $view);
        $this->assertStringNotContainsString("'User'      => '/admin/user/view'", $view);

        $this->assertStringContainsString("'Buyout'    => '/admin/buyout/view'", $view);
        $this->assertStringContainsString("'Receiving' => '/admin/receiving/view'", $view);
        $this->assertStringContainsString("'User'      => '/admin/user/edit'", $view);

        $this->assertTrue(method_exists(BuyoutController::class, 'actionView'));
        $this->assertTrue(method_exists(ReceivingController::class, 'actionView'));
        $this->assertTrue(method_exists(UserController::class, 'actionEdit'));
        $this->assertFalse(method_exists(UserController::class, 'actionView'));
    }
}
