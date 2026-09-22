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
 * CMP-422: живая проверка пост-обработки заказа.
 *
 * До фикса письмо клиенту и письмо менеджеру отправлялись в ОДНОМ try/catch.
 * Если отправка клиенту падала (самый частый случай — опечатка в его email,
 * либо временный сбой SMTP на первом же вызове), исключение обрывало try ДО
 * строки с письмом менеджеру — магазин не узнавал о заказе вообще ни по
 * одному каналу, при этом заказ уже был зафиксирован в БД и покупатель видел
 * спасибо-страницу. Ровно тот риск, который описан в CMP-422: заказ формально
 * успешен и невидим для магазина.
 */
class Cmp422OrderEmailIndependenceTest extends TestCase
{
    private ?Product $product = null;
    private array $cleanupOrderIds = [];
    private $originalMailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = new Product();
        $this->product->name = 'CMP-422 Test Sneaker';
        $this->product->price = 199.00;
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

        if ($this->originalMailer !== null) {
            Yii::$app->set('mailer', $this->originalMailer);
            $this->originalMailer = null;
        }

        Yii::$app->request->setBodyParams([]);
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    public function testManagerEmailStillSentWhenCustomerEmailFails(): void
    {
        // Форсируем инициализацию реального mailer-компонента, чтобы было что
        // восстановить в tearDown, затем подменяем его на фейковый транспорт,
        // который специально роняет ТОЛЬКО письмо клиенту (order-created).
        $this->originalMailer = Yii::$app->mailer;
        $fakeMailer = new Cmp422FakeMailer();
        Yii::$app->set('mailer', $fakeMailer);

        $this->assertTrue(Cart::add($this->product->id, 1, '42'));

        $controller = new OrderController('order', Yii::$app);

        Yii::$app->request->setBodyParams([
            'name' => 'Тестовый Покупатель CMP-422',
            'phone' => '+375291234567',
            'email' => 'broken-address@example.com',
            'delivery' => 'pickup_minsk',
            'country' => 'belarus',
            'comment' => 'CMP-422 email independence regression',
        ]);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $result = $controller->actionCreate();

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrderIds[] = $result['order_id'];

        // Письмо клиенту "упало" (фейковый транспорт бросает исключение для
        // order-created) — но письмо менеджеру обязано уйти независимо от этого.
        $this->assertSame(
            ['order-created-manager'],
            $fakeMailer->sentViews,
            'Письмо менеджеру должно отправляться независимо от сбоя письма клиенту'
        );
    }
}

class Cmp422FakeMailer extends \yii\base\Component
{
    /** @var string[] */
    public array $sentViews = [];

    public function compose($view = null, $params = [])
    {
        return new Cmp422FakeMessage($this, $view);
    }
}

class Cmp422FakeMessage
{
    private Cmp422FakeMailer $mailer;
    private ?string $view;

    public function __construct(Cmp422FakeMailer $mailer, ?string $view)
    {
        $this->mailer = $mailer;
        $this->view = $view;
    }

    public function setFrom($from)
    {
        return $this;
    }

    public function setTo($to)
    {
        return $this;
    }

    public function setSubject($subject)
    {
        return $this;
    }

    public function send(): bool
    {
        if ($this->view === 'order-created') {
            throw new \RuntimeException('CMP-422 simulated SMTP failure: customer mailbox rejected');
        }

        $this->mailer->sentViews[] = $this->view;

        return true;
    }
}
