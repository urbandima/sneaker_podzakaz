<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\admin\AdminModule;
use app\backend\modules\admin\controllers\ActivityLogController;
use app\backend\modules\admin\controllers\AmoCrmController;
use app\backend\modules\admin\controllers\AutomationController;
use app\backend\modules\admin\controllers\BuyoutController;
use app\backend\modules\admin\controllers\DashboardController;
use app\backend\modules\admin\controllers\CharacteristicController;
use app\backend\modules\admin\controllers\CustomerController;
use app\backend\modules\admin\controllers\ExchangeRateController;
use app\backend\modules\admin\controllers\FinanceController;
use app\backend\modules\admin\controllers\MoyskladController;
use app\backend\modules\admin\controllers\OrderController;
use app\backend\modules\admin\controllers\ProductController;
use app\backend\modules\admin\controllers\ProductTagController;
use app\backend\modules\admin\controllers\ReceivingController;
use app\backend\modules\admin\controllers\ReviewController;
use app\backend\modules\admin\controllers\SeoController;
use app\backend\modules\admin\controllers\SidebarMenuController;

/**
 * Регрессионные тесты CMP-418: систематический аудит backend/modules/admin/controllers/*.php
 * на state-changing actions без VerbFilter(POST)/isPost — GET-CSRF (Yii2 не проверяет
 * CSRF-токен на GET, значит любое такое действие исполнялось голой ссылкой/`<img src>`).
 *
 * Каждый case ниже — подтверждённая живым HTTP GET-прогоном находка (405 после фикса,
 * до фикса — реальное исполнение мутации, см. комментарии в behaviors() каждого
 * контроллера). Флагманский случай из issue — ReviewController::actionToggleFeatured —
 * уже был описан в тексте задачи как подтверждённо эксплуатируемый.
 *
 * Отдельная находка вне списка actions: BaseAdminController раньше объявлял
 * 'delete-*' => ['POST'] в VerbFilter::$actions — yii\filters\VerbFilter матчит
 * действия только по точному ключу (или литералу '*'), префиксный wildcard не
 * поддерживается, так что эта запись не защищала ни один реальный action id вида
 * delete-item/delete-size/delete-image. Запись убрана как мёртвая/вводящая в
 * заблуждение (testDeleteWildcardPatternRemoved).
 */
class Cmp418CsrfGetAuditTest extends TestCase
{
    private static ?AdminModule $adminModule = null;

    private static function adminModule(): AdminModule
    {
        return self::$adminModule ??= new AdminModule('admin');
    }

    private function assertPostOnly($controller, string $actionId, string $message): void
    {
        $behaviors = $controller->behaviors();
        $this->assertArrayHasKey('verbs', $behaviors, $message . ' (no verbs behavior at all)');
        $this->assertSame(['POST'], $behaviors['verbs']['actions'][$actionId] ?? null, $message);
    }

    public function testDeleteWildcardPatternRemoved()
    {
        // DashboardController does not override behaviors() at all, so this
        // exercises BaseAdminController::behaviors() directly (it's abstract,
        // can't be instantiated on its own).
        $controller = new DashboardController('dashboard', self::adminModule());
        $behaviors = $controller->behaviors();

        $this->assertArrayHasKey('verbs', $behaviors);
        $this->assertArrayNotHasKey(
            'delete-*',
            $behaviors['verbs']['actions'],
            "'delete-*' matched no real action id under yii\\filters\\VerbFilter (exact-key match only) — keeping it was misleading, it protected nothing"
        );
        $this->assertSame(['POST'], $behaviors['verbs']['actions']['delete'] ?? null);
    }

    public function testActivityLogCleanupIsPostOnly()
    {
        $controller = new ActivityLogController('activity-log', self::adminModule());
        $this->assertPostOnly($controller, 'cleanup', 'actionCleanup deletes activity_log rows unconditionally, no VerbFilter before fix');
    }

    public function testAmoCrmCreateDealAndUpdateStatusArePostOnly()
    {
        $controller = new AmoCrmController('amo-crm', self::adminModule());
        $this->assertPostOnly($controller, 'create-deal', 'actionCreateDeal writes Order::amocrm_deal_id + creates a live AmoCRM deal from route $orderId alone');
        $this->assertPostOnly($controller, 'update-status', 'actionUpdateStatus pushes a live AmoCRM status update from route $orderId alone');
    }

    public function testAutomationToggleIsPostOnly()
    {
        $controller = new AutomationController('automation', self::adminModule());
        $this->assertPostOnly($controller, 'toggle', 'actionToggle flips AutomationTrigger::is_active unconditionally');
    }

    public function testBuyoutMutatingActionsArePostOnly()
    {
        $controller = new BuyoutController('buyout', self::adminModule());
        foreach (['link-order', 'unlink-order', 'accept', 'cancel', 'bulk-status', 'update-status'] as $actionId) {
            $this->assertPostOnly($controller, $actionId, "Buyout::$actionId mutates Buyout/BuyoutOrderLink/PurchaseOrder from route/body params with no isPost/VerbFilter guard");
        }
    }

    public function testCharacteristicSizeDeleteKeyMatchesRealActionId()
    {
        $controller = new CharacteristicController('characteristic', self::adminModule());
        // Before fix: the key here was 'delete-size', but actionSizeDelete's real
        // Yii2 action id (camel2id) is 'size-delete' — the old key matched nothing.
        $this->assertPostOnly($controller, 'size-delete', "actionSizeDelete's real action id is 'size-delete', not 'delete-size' — the old key never matched, so GET deleted a size grid unprotected");
        $this->assertPostOnly($controller, 'size-delete-item', 'actionSizeDeleteItem had no VerbFilter entry at all');
    }

    public function testCustomerMutatingActionsArePostOnly()
    {
        $controller = new CustomerController('customer', self::adminModule());
        foreach (['toggle-status', 'reset-password', 'link-orders', 'mark-phantoms'] as $actionId) {
            $this->assertPostOnly($controller, $actionId, "Customer::$actionId mutates customer data (block/reset-password/link-orders/bulk-status) with no isPost/VerbFilter guard");
        }
    }

    public function testExchangeRateUpdateIsPostOnly()
    {
        $controller = new ExchangeRateController('exchange-rate', self::adminModule());
        $this->assertPostOnly($controller, 'update', 'actionUpdate overwrites the live CNY rate unconditionally; its only UI caller already used POST');
    }

    public function testFinanceCreateAndConfirmPaymentArePostOnly()
    {
        $controller = new FinanceController('finance', self::adminModule());
        $this->assertPostOnly($controller, 'create-payment', "Payment's required-validator does not reject amount=0, so a bare GET (defaults to 0) still passes save()");
        $this->assertPostOnly($controller, 'confirm-payment', 'actionConfirmPayment marks a Payment confirmed with no isPost/VerbFilter guard');
    }

    public function testMoyskladMutatingActionsArePostOnly()
    {
        $controller = new MoyskladController('moysklad', self::adminModule());
        foreach (['save-status-mapping', 'push-all', 'periodic-sync', 'pull', 'register-webhook'] as $actionId) {
            $this->assertPostOnly($controller, $actionId, "Moysklad::$actionId runs unconditionally on GET (post()-read params default silently) — pushes/pulls live orders or registers an external webhook");
        }
    }

    public function testOrderMutatingActionsArePostOnly()
    {
        $controller = new OrderController('order', self::adminModule());
        foreach (['send-to-dp', 'dp-status', 'retry-dp', 'auto-fill-dp', 'clean-bad-import', 'delete-item'] as $actionId) {
            $this->assertPostOnly($controller, $actionId, "Order::$actionId mutates Order/OrderItem/DP-shipment state from route params alone with no isPost/VerbFilter guard");
        }
    }

    public function testProductMutatingActionsArePostOnly()
    {
        $controller = new ProductController('product', self::adminModule());
        foreach (['sync', 'clone', 'delete-size', 'delete-image', 'set-main-image', 'add-sizes-from-grid'] as $actionId) {
            $this->assertPostOnly($controller, $actionId, "Product::$actionId mutates Product/ProductSize/ProductImage from route \$id alone with no isPost/VerbFilter guard");
        }
    }

    public function testProductTagToggleActiveIsPostOnly()
    {
        $controller = new ProductTagController('product-tag', self::adminModule());
        $this->assertPostOnly($controller, 'toggle-active', 'actionToggleActive flips ProductTag::is_active unconditionally');
    }

    public function testReceivingFromBuyoutIsPostOnly()
    {
        $controller = new ReceivingController('receiving', self::adminModule());
        $this->assertPostOnly($controller, 'from-buyout', 'actionFromBuyout creates a Receiving from route $buyoutId alone with no isPost/VerbFilter guard');
    }

    /**
     * The flagship confirmed bug from the CMP-418 issue text: live GET actually
     * executed actionToggleFeatured (is_featured flip persisted).
     */
    public function testReviewToggleFeaturedIsPostOnly()
    {
        $controller = new ReviewController('review', self::adminModule());
        $this->assertPostOnly($controller, 'toggle-featured', 'actionToggleFeatured saves is_featured unconditionally — confirmed GET-CSRF exploitable per CMP-417 finding');
    }

    public function testSeoRedirectDeleteIsPostOnly()
    {
        $controller = new SeoController('seo', self::adminModule());
        $this->assertPostOnly($controller, 'redirect-delete', 'actionRedirectDelete deletes a Redirect from route $id alone, controller had no behaviors() override at all before this fix');
    }

    public function testSidebarMenuToggleIsPostOnly()
    {
        $controller = new SidebarMenuController('sidebar-menu', self::adminModule());
        $this->assertPostOnly($controller, 'toggle', 'actionToggle flips SidebarMenuItem::is_active unconditionally');
    }
}
