<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\frontend\controllers\OrderController;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\checkout\models\OrderHistory;
use app\backend\modules\coupon\models\Coupon;
use app\backend\modules\coupon\models\CouponUsage;
use app\backend\modules\account\models\Customer;

/**
 * CMP-423: живой прогон купона через реальный чекаут-путь.
 *
 * До фикса JS корзины слал POST /api/v1/coupon/validate (404 — маршрут нигде
 * не был смонтирован), а живой OrderController::actionCreate() вообще не читал
 * coupon_code — купон существовал в админке, но не работал ни на одном шаге
 * покупки. Эти тесты гоняют именно живой путь (OrderController::actionCreate()),
 * а не только модель Coupon в изоляции (это уже покрыто CouponTest.php).
 */
class Cmp423CouponOrderCreateTest extends TestCase
{
    private ?Product $product = null;
    private array $cleanupOrderIds = [];
    private array $cleanupCouponIds = [];
    private ?Customer $customer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = new Product();
        $this->product->name = 'CMP-423 Test Sneaker';
        $this->product->price = 200.00;
        $this->product->stock_status = 'in_stock';
        $this->product->is_active = 1;
        $this->product->category_id = 1;
        $this->product->brand_id = 1;
        $this->assertTrue($this->product->save(), json_encode($this->product->errors, JSON_UNESCAPED_UNICODE));

        $size = new ProductSize();
        $size->product_id = $this->product->id;
        $size->size = '42';
        $size->stock = 5;
        $size->is_available = 1;
        $this->assertTrue($size->save(), json_encode($size->errors, JSON_UNESCAPED_UNICODE));

        Cart::deleteAll(['product_id' => $this->product->id]);
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupOrderIds as $orderId) {
            CouponUsage::deleteAll(['order_id' => $orderId]);
            OrderItem::deleteAll(['order_id' => $orderId]);
            OrderHistory::deleteAll(['order_id' => $orderId]);
            Order::deleteAll(['id' => $orderId]);
        }
        $this->cleanupOrderIds = [];

        foreach ($this->cleanupCouponIds as $couponId) {
            CouponUsage::deleteAll(['coupon_id' => $couponId]);
            Coupon::deleteAll(['id' => $couponId]);
        }
        $this->cleanupCouponIds = [];

        if ($this->product) {
            Cart::deleteAll(['product_id' => $this->product->id]);
            ProductSize::deleteAll(['product_id' => $this->product->id]);
            $this->product->delete();
            $this->product = null;
        }

        if ($this->customer) {
            $this->customer->delete();
            $this->customer = null;
        }

        Yii::$app->session->remove('customer_id');
        Yii::$app->request->setBodyParams([]);
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    private function makeCoupon(array $attrs): Coupon
    {
        $coupon = new Coupon();
        $coupon->code = $attrs['code'] ?? ('CMP423-' . uniqid());
        $coupon->name = $attrs['name'] ?? 'CMP-423 test coupon';
        $coupon->type = $attrs['type'] ?? Coupon::TYPE_PERCENTAGE;
        $coupon->value = $attrs['value'] ?? 10;
        $coupon->is_active = $attrs['is_active'] ?? 1;
        $coupon->min_order_amount = $attrs['min_order_amount'] ?? null;
        $coupon->max_uses = $attrs['max_uses'] ?? null;
        $coupon->current_uses = $attrs['current_uses'] ?? 0;
        $coupon->max_uses_per_user = $attrs['max_uses_per_user'] ?? null;
        $coupon->valid_from = $attrs['valid_from'] ?? null;
        $coupon->valid_until = $attrs['valid_until'] ?? null;
        $coupon->excluded_categories = $attrs['excluded_categories'] ?? null;
        $this->assertTrue($coupon->save(), json_encode($coupon->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupCouponIds[] = $coupon->id;
        return $coupon;
    }

    private function baseOrderPost(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Тестовый Покупатель CMP-423',
            'phone' => '+375291234567',
            'delivery' => 'pickup_minsk',
            'country' => 'belarus',
        ], $overrides);
    }

    private function submitOrder(array $post): array
    {
        $controller = new OrderController('order', Yii::$app);
        Yii::$app->request->setBodyParams($post);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        return $controller->actionCreate();
    }

    public function testValidPercentageCouponAppliesDiscountAndPersistsCorrectTotal(): void
    {
        $coupon = $this->makeCoupon(['type' => Coupon::TYPE_PERCENTAGE, 'value' => 10]);

        $this->assertTrue(Cart::add($this->product->id, 2, '42')); // 2 * 200 = 400

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => $coupon->code]));

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        $order = Order::findOne($result['order_id']);
        $this->assertNotNull($order);
        $this->assertEquals($coupon->id, $order->coupon_id);
        $this->assertEquals($coupon->code, $order->coupon_code);
        // 400 * 10% = 40 discount, pickup delivery = 0 → 360
        $this->assertEqualsWithDelta(40.0, (float) $order->discount_amount, 0.01);
        $this->assertEqualsWithDelta(360.0, (float) $order->total_amount, 0.01);

        $coupon->refresh();
        $this->assertSame(1, (int) $coupon->current_uses);

        $usage = CouponUsage::find()->where(['order_id' => $order->id, 'coupon_id' => $coupon->id])->one();
        $this->assertNotNull($usage, 'CouponUsage должен быть записан для применённого купона');
        $this->assertEqualsWithDelta(40.0, (float) $usage->discount_amount, 0.01);
    }

    public function testClientSuppliedTotalAndDiscountAreIgnored(): void
    {
        $coupon = $this->makeCoupon(['type' => Coupon::TYPE_FIXED, 'value' => 15]);

        $this->assertTrue(Cart::add($this->product->id, 1, '42')); // 200

        // Покупатель (или атакующий) пытается подделать сумму и скидку прямо в теле запроса.
        $result = $this->submitOrder($this->baseOrderPost([
            'coupon_code' => $coupon->code,
            'total_amount' => 1,
            'discount_amount' => 9999,
        ]));

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        $order = Order::findOne($result['order_id']);
        // 200 - 15 (fixed) = 185, никак не 1 и скидка не 9999
        $this->assertEqualsWithDelta(15.0, (float) $order->discount_amount, 0.01);
        $this->assertEqualsWithDelta(185.0, (float) $order->total_amount, 0.01);
    }

    public function testExpiredCouponRejectsWithoutCreatingOrder(): void
    {
        $coupon = $this->makeCoupon([
            'valid_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'valid_until' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));
        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => $coupon->code]));

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('истёк', $result['message'] ?? '');
        $this->assertSame($ordersBefore, Order::find()->count(), 'Заказ не должен создаваться с истёкшим купоном');
    }

    public function testNonexistentCouponRejectsWithoutCreatingOrder(): void
    {
        $this->assertTrue(Cart::add($this->product->id, 1, '42'));
        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => 'NOPE-' . uniqid()]));

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame($ordersBefore, Order::find()->count());
    }

    public function testUsageLimitExceededRejectsWithoutCreatingOrder(): void
    {
        $coupon = $this->makeCoupon(['max_uses' => 1, 'current_uses' => 1]);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));
        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => $coupon->code]));

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('споль', $result['message'] ?? '');
        $this->assertSame($ordersBefore, Order::find()->count());
    }

    public function testMinOrderAmountNotMetRejectsWithoutCreatingOrder(): void
    {
        $coupon = $this->makeCoupon(['min_order_amount' => 1000]);

        $this->assertTrue(Cart::add($this->product->id, 1, '42')); // 200 < 1000
        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => $coupon->code]));

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('Минимальная сумма', $result['message'] ?? '');
        $this->assertSame($ordersBefore, Order::find()->count());
    }

    public function testCouponWithEmptyCartRejectsWithEmptyCartMessageNot500(): void
    {
        // Корзина намеренно пуста
        Cart::deleteAll(['product_id' => $this->product->id]);

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => 'ANYTHING']));

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('орзина пуста', $result['message'] ?? '');
    }

    public function testCouponExcludedForProductCategoryRejectsWithoutCreatingOrder(): void
    {
        // Регрессия на баг в CouponService::checkApplicability(): раньше метод
        // обращался к несуществующему $item->category_id на OrderItem (в таблице
        // order_item такой колонки нет) и падал с UnknownPropertyException.
        $coupon = $this->makeCoupon(['excluded_categories' => json_encode([(int) $this->product->category_id])]);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));
        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost(['coupon_code' => $coupon->code]));

        $this->assertFalse($result['success'] ?? true, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('не применим', $result['message'] ?? '');
        $this->assertSame($ordersBefore, Order::find()->count());
    }

    /**
     * Проверяет max_uses_per_user на реальном CouponService (та же цепочка
     * validateCoupon() -> isValidForOrder() -> CouponUsage::find()->count(),
     * что вызывает и OrderController::actionCreate(), и CouponController::
     * actionValidate()).
     *
     * Намеренно НЕ идёт через Cart::add() с залогиненным покупателем: cart.user_id
     * имеет FK на таблицу user (сотрудники), а не customer — отдельный,
     * не связанный с CMP-423 баг схемы, из-за которого Cart::add() падает с
     * IntegrityException для любого покупателя с customer.id, отсутствующим в
     * user (т.е. почти для всех реальных покупателей). Вынесено в отдельный
     * repro и эскалировано отдельно от купонов.
     */
    public function testMaxUsesPerUserBlocksSecondApplicationBySameCustomer(): void
    {
        $this->customer = new Customer();
        $this->customer->email = 'cmp423-' . uniqid() . '@example.com';
        $this->customer->phone = '+375291112233';
        $this->customer->first_name = 'CMP423';
        $this->customer->last_name = 'Test';
        $this->customer->password_hash = Yii::$app->security->generatePasswordHash('irrelevant');
        $this->assertTrue($this->customer->save(), json_encode($this->customer->errors, JSON_UNESCAPED_UNICODE));

        $coupon = $this->makeCoupon(['max_uses_per_user' => 1]);
        $couponService = new \app\backend\modules\coupon\services\CouponService();

        // Первое применение — должно пройти валидацию.
        $firstCheck = $couponService->validateCoupon($coupon->code, 300, $this->customer->id);
        $this->assertNotNull($firstCheck, (string) $couponService->getErrorMessage());

        $order = new Order();
        $order->client_name = 'CMP-423 max-uses-per-user helper order';
        $order->customer_id = $this->customer->id;
        $order->total_amount = 300;
        $order->status = 'new';
        $this->assertTrue($order->save(), json_encode($order->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $order->id;

        $this->assertTrue(CouponUsage::record($coupon->id, $order->id, 30, $this->customer->id));

        // Повторное применение тем же покупателем — должно быть отклонено.
        $secondCheck = $couponService->validateCoupon($coupon->code, 300, $this->customer->id);
        $this->assertNull($secondCheck, 'Повторное применение купона тем же покупателем должно быть отклонено');
        $this->assertStringContainsString('максимальное количество', $couponService->getErrorMessage() ?? '');
    }
}
