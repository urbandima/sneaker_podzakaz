<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\api\controllers\CouponController;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\coupon\models\Coupon;

/**
 * CMP-423: живой прогон эндпоинта предпросмотра купона (POST /api/coupon/validate).
 *
 * До фикса фронтенд слал этот запрос на /api/v1/coupon/validate, для которого
 * не было ни одного маршрута — эндпоинт всегда отвечал 404. Тесты гоняют
 * реальный CouponController::actionValidate(), а не только модель Coupon.
 */
class Cmp423CouponValidateApiTest extends TestCase
{
    private ?Product $product = null;
    private array $cleanupCouponIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = new Product();
        $this->product->name = 'CMP-423 API Test Sneaker';
        $this->product->price = 150.00;
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
        foreach ($this->cleanupCouponIds as $couponId) {
            Coupon::deleteAll(['id' => $couponId]);
        }
        $this->cleanupCouponIds = [];

        if ($this->product) {
            Cart::deleteAll(['product_id' => $this->product->id]);
            ProductSize::deleteAll(['product_id' => $this->product->id]);
            $this->product->delete();
            $this->product = null;
        }

        Yii::$app->request->setBodyParams([]);
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    private function makeCoupon(array $attrs): Coupon
    {
        $coupon = new Coupon();
        $coupon->code = $attrs['code'] ?? ('CMP423API-' . uniqid());
        $coupon->name = $attrs['name'] ?? 'CMP-423 API test coupon';
        $coupon->type = $attrs['type'] ?? Coupon::TYPE_PERCENTAGE;
        $coupon->value = $attrs['value'] ?? 20;
        $coupon->is_active = $attrs['is_active'] ?? 1;
        $coupon->min_order_amount = $attrs['min_order_amount'] ?? null;
        $coupon->max_uses = $attrs['max_uses'] ?? null;
        $coupon->current_uses = $attrs['current_uses'] ?? 0;
        $coupon->valid_from = $attrs['valid_from'] ?? null;
        $coupon->valid_until = $attrs['valid_until'] ?? null;
        $this->assertTrue($coupon->save(), json_encode($coupon->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupCouponIds[] = $coupon->id;
        return $coupon;
    }

    private function callValidate(array $post): array
    {
        $controller = new CouponController('coupon', Yii::$app);
        Yii::$app->request->setBodyParams($post);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        return $controller->actionValidate();
    }

    public function testValidCouponReturnsDiscountComputedFromServerSideCart(): void
    {
        $coupon = $this->makeCoupon(['type' => Coupon::TYPE_PERCENTAGE, 'value' => 20]);
        $this->assertTrue(Cart::add($this->product->id, 1, '42')); // 150 BYN

        $result = $this->callValidate(['code' => $coupon->code, 'order_amount' => 1]); // подделанная сумма клиента

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        // 150 * 20% = 30, а не 20% от подделанного order_amount=1
        $this->assertEqualsWithDelta(30.0, (float) $result['discount'], 0.01);
    }

    public function testExpiredCouponReturnsErrorNot500(): void
    {
        $coupon = $this->makeCoupon([
            'valid_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'valid_until' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->callValidate(['code' => $coupon->code]);

        $this->assertFalse($result['success'] ?? true);
        $this->assertArrayNotHasKey('discount', $result);
    }

    public function testNonexistentCouponReturnsErrorNot500(): void
    {
        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->callValidate(['code' => 'DOES-NOT-EXIST-' . uniqid()]);

        $this->assertFalse($result['success'] ?? true);
    }

    public function testEmptyCartReturnsErrorNot500(): void
    {
        $coupon = $this->makeCoupon([]);

        $result = $this->callValidate(['code' => $coupon->code]);

        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('орзина пуста', $result['message'] ?? '');
    }

    public function testEmptyCodeReturnsErrorNot500(): void
    {
        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->callValidate(['code' => '']);

        $this->assertFalse($result['success'] ?? true);
    }
}
