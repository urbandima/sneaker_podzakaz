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

/**
 * CMP-449: переквалификация остаточного риска из CMP-438.
 *
 * CMP-438 добавил транзакционный декремент product_size.stock под FOR UPDATE
 * при оформлении заказа (frontend/controllers/OrderController::actionCreate(),
 * ~строки 465-490) и честно указал, что product.stock_status и
 * product_size.is_available после этого не пересчитываются — товар с нулевым
 * остатком продолжал бы показываться на витрине как «в наличии». Эти тесты
 * гоняют живой actionCreate() (не изолированную модель) и проверяют, что:
 *  - при остатке 0 заказ отклоняется понятным сообщением, а не создаётся;
 *  - при успешном списании последней пары is_available/stock_status гасятся
 *    в той же транзакции;
 *  - при наличии других размеров product.stock_status не гасится напрасно.
 */
class Cmp449StockOversellTest extends TestCase
{
    private ?Product $product = null;
    private array $cleanupOrderIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = new Product();
        $this->product->name = 'CMP-449 Test Sneaker';
        $this->product->price = 150.00;
        $this->product->stock_status = Product::STOCK_IN_STOCK;
        $this->product->is_active = 1;
        $this->product->category_id = 1;
        $this->product->brand_id = 1;
        $this->assertTrue($this->product->save(), json_encode($this->product->errors, JSON_UNESCAPED_UNICODE));
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupOrderIds as $orderId) {
            OrderItem::deleteAll(['order_id' => $orderId]);
            OrderHistory::deleteAll(['order_id' => $orderId]);
            Order::deleteAll(['id' => $orderId]);
        }
        $this->cleanupOrderIds = [];

        if ($this->product) {
            Cart::deleteAll(['product_id' => $this->product->id]);
            ProductSize::deleteAll(['product_id' => $this->product->id]);
            $this->product->delete();
            $this->product = null;
        }

        Yii::$app->session->remove('customer_id');
        Yii::$app->request->setBodyParams([]);
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    private function makeSize(int $productId, string $size, int $stock, bool $isAvailable = true): ProductSize
    {
        $sizeModel = new ProductSize();
        $sizeModel->product_id = $productId;
        $sizeModel->size = $size;
        $sizeModel->stock = $stock;
        $sizeModel->is_available = $isAvailable ? 1 : 0;
        $this->assertTrue($sizeModel->save(), json_encode($sizeModel->errors, JSON_UNESCAPED_UNICODE));
        return $sizeModel;
    }

    private function baseOrderPost(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Тестовый Покупатель CMP-449',
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

    public function testOrderRejectedWithoutCreatingOrderWhenSizeStockIsZero(): void
    {
        // Размер уже пуст на момент оформления (например, стал 0 между
        // добавлением в корзину и сабмитом — та самая гонка из CMP-438/CMP-449).
        $size = $this->makeSize($this->product->id, '42', 0, true);

        // Cart::add() не проверяет числовой stock (только существование записи
        // размера), поэтому это единственный способ довести до actionCreate()
        // заведомо пустой размер, минуя более раннюю проверку на добавлении.
        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $ordersBefore = Order::find()->count();

        $result = $this->submitOrder($this->baseOrderPost());

        $this->assertFalse($result['success'] ?? true, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('закончился', $result['message'] ?? '');
        $this->assertSame($ordersBefore, Order::find()->count(), 'Заказ не должен создаваться при нулевом остатке');

        $size->refresh();
        $this->assertSame(0, (int) $size->stock, 'Остаток не должен уйти в минус');
    }

    public function testSuccessfulOrderDecrementsStockAndKeepsProductInStockWhenUnitsRemain(): void
    {
        $size = $this->makeSize($this->product->id, '42', 3);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->submitOrder($this->baseOrderPost());
        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        $size->refresh();
        $this->assertSame(2, (int) $size->stock);
        $this->assertSame(1, (int) $size->is_available, 'Размер ещё не пуст — должен оставаться доступным');

        $this->product->refresh();
        $this->assertSame(Product::STOCK_IN_STOCK, $this->product->stock_status);
    }

    public function testLastUnitDecrementMarksSizeUnavailableAndProductOutOfStock(): void
    {
        // Регрессия на остаточный риск из CMP-438: списание последней пары не
        // должно оставлять витрину «врущей» о наличии.
        $size = $this->makeSize($this->product->id, '42', 1);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->submitOrder($this->baseOrderPost());
        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        $size->refresh();
        $this->assertSame(0, (int) $size->stock);
        $this->assertSame(0, (int) $size->is_available, 'Проданный подчистую размер должен стать недоступным для витрины');

        $this->product->refresh();
        $this->assertSame(
            Product::STOCK_OUT_OF_STOCK,
            $this->product->stock_status,
            'Товар без единого доступного размера не должен оставаться "в наличии"'
        );
    }

    public function testProductStaysInStockWhileAnotherSizeStillHasUnits(): void
    {
        $soldOutSize = $this->makeSize($this->product->id, '42', 1);
        $remainingSize = $this->makeSize($this->product->id, '43', 5);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $result = $this->submitOrder($this->baseOrderPost());
        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        $soldOutSize->refresh();
        $this->assertSame(0, (int) $soldOutSize->stock);
        $this->assertSame(0, (int) $soldOutSize->is_available);

        $remainingSize->refresh();
        $this->assertSame(5, (int) $remainingSize->stock, 'Другой размер того же товара декремент не затрагивает');

        $this->product->refresh();
        $this->assertSame(
            Product::STOCK_IN_STOCK,
            $this->product->stock_status,
            'Пока у товара есть хоть один размер в наличии, он должен оставаться "в наличии"'
        );
    }
}
