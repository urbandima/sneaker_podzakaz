<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\modules\admin\AdminModule;
use app\backend\modules\admin\controllers\OrderController;
use app\backend\modules\admin\controllers\CustomerController;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\account\models\Customer;
use app\backend\modules\loyalty\models\LoyaltyPoints;

/**
 * Регрессионные тесты CMP-417: живой HTTP-прогон create/update заказов и
 * update/adjust-points/add-note/update-tags покупателей в админке.
 *
 * Предыдущий сканер (CMP-413) делал только GET на admin/*\/create — реальные POST
 * с кириллическими данными (ФИО, адреса, комментарии) под MySQL 8 strict mode
 * и через настоящий HTTP-стек контроллеров ни разу не выполнялись. Каждый тест
 * здесь воспроизводит один подтверждённый живым curl-прогоном баг.
 */
class Cmp417OrderCustomerAdminTest extends TestCase
{
    private static ?AdminModule $adminModule = null;
    private array $cleanupOrders = [];
    private array $cleanupCustomers = [];

    private static function adminModule(): AdminModule
    {
        return self::$adminModule ??= new AdminModule('admin');
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupOrders as $order) {
            try {
                OrderItem::deleteAll(['order_id' => $order->id]);
                \app\backend\modules\checkout\models\OrderHistory::deleteAll(['order_id' => $order->id]);
                $order->delete();
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
        foreach ($this->cleanupCustomers as $customer) {
            try {
                Yii::$app->db->createCommand()->delete('{{%customer_notes}}', ['customer_id' => $customer->id])->execute();
                Yii::$app->db->createCommand()->delete('{{%customer_tags}}', ['customer_id' => $customer->id])->execute();
                LoyaltyPoints::deleteAll(['customer_id' => $customer->id]);
                $customer->delete();
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
        $this->cleanupOrders = [];
        $this->cleanupCustomers = [];

        // Request-компонент кеширует query/body params между вызовами get()/post() —
        // сбрасываем, чтобы следующий тест не унаследовал $_GET/$_POST этого теста.
        Yii::$app->request->setQueryParams([]);
        Yii::$app->request->setBodyParams([]);
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tearDown();
    }

    private function setGet(array $data): void
    {
        $_GET = $data;
        Yii::$app->request->setQueryParams($data);
    }

    private function setPost(array $data): void
    {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams($data);
    }

    /**
     * CMP-417: actionUpdate() читал `Yii::$app->request->post('OrderItem', [])` —
     * если запрос вообще не редактировал состав заказа (реалистичный сценарий:
     * форма меняет только адрес/комментарий/статус), ключа 'OrderItem' в POST нет,
     * post(..., []) молча подставлял пустой массив, saveOrderItems() удалял ВСЕ
     * существующие позиции заказа, находил 0 непустых и бросал исключение —
     * транзакция откатывалась целиком, и заказ молча терял ВСЕ только что
     * сохранённые кириллические изменения (client_name/full_address/city/comment)
     * без видимого 500 (обычный 200 с невидимым flash-сообщением).
     */
    public function testOrderUpdateWithoutOrderItemKeyPersistsCyrillicFieldsAndKeepsExistingItem(): void
    {
        $order = new Order();
        $order->client_name = 'Черновой Клиент';
        $order->status = 'new';
        $this->assertTrue($order->save(), json_encode($order->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupOrders[] = $order;

        $item = new OrderItem();
        $item->order_id = $order->id;
        $item->product_name = 'Existing Nike Item';
        $item->quantity = 1;
        $item->price = 189.0;
        $this->assertTrue($item->save());

        $controller = new OrderController('order', self::adminModule());

        $this->setPost([
            'Order' => [
                'client_name' => 'Караткевіч-Ярашэвіч Уладзіслаў Багданавіч',
                'city' => 'посёлак Ёдкавічы',
                'region' => 'Мінская вобласць',
                'full_address' => 'г. Мінск, вул. Незалежнасці, д. 15, корп. 2, кв. 34-а',
                'postal_code' => '220100',
                'recipient_last_name' => 'Караткевіч-Ярашэвіч',
                'comment' => 'Тэставы каментар з "лапкамі" і эмодзі 🚀 — праверка strict mode MySQL 8.',
                'status' => 'new',
            ],
            // Намеренно НЕТ ключа 'OrderItem' — форма не редактировала состав.
        ]);

        $response = $controller->actionUpdate($order->id);

        $this->assertInstanceOf(\yii\web\Response::class, $response);
        $this->assertSame(302, $response->statusCode);

        $fresh = Order::findOne($order->id);
        $this->assertSame('Караткевіч-Ярашэвіч Уладзіслаў Багданавіч', $fresh->client_name);
        $this->assertSame('посёлак Ёдкавічы', $fresh->city);
        $this->assertSame('Мінская вобласць', $fresh->region);
        $this->assertSame('г. Мінск, вул. Незалежнасці, д. 15, корп. 2, кв. 34-а', $fresh->full_address);
        $this->assertSame(
            'Тэставы каментар з "лапкамі" і эмодзі 🚀 — праверка strict mode MySQL 8.',
            $fresh->comment
        );

        // Существующая позиция должна остаться нетронутой — не удалена rollback'ом.
        $this->assertSame(1, OrderItem::find()->where(['order_id' => $order->id])->count());
        $this->assertSame('Existing Nike Item', OrderItem::find()->where(['order_id' => $order->id])->one()->product_name);
    }

    /**
     * CMP-417: actionCreate() создаёт черновик через GET-параметры product_id/
     * customer_id — базовый happy-path (уже покрыт CMP-410 для validate(), здесь
     * фиксируем, что реальный вызов контроллера с товаром и клиентом отрабатывает
     * без исключений и настоящая позиция товара создаётся).
     */
    public function testOrderCreateWithProductAndCustomerPersistsDraft(): void
    {
        $customer = new Customer();
        $customer->email = 'cmp417.create.' . uniqid('', false) . '@example.com';
        $customer->first_name = 'Тэст';
        $customer->last_name = 'Кліентка';
        $customer->password_hash = '!';
        $this->assertTrue($customer->save(), json_encode($customer->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupCustomers[] = $customer;

        $product = \app\backend\modules\catalog\models\Product::find()->one();
        $this->assertNotNull($product, 'В тестовой БД должен быть хотя бы один товар');

        $controller = new OrderController('order', self::adminModule());
        $this->setGet([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'source' => 'phone',
        ]);

        $response = $controller->actionCreate();

        $this->assertInstanceOf(\yii\web\Response::class, $response);
        $this->assertSame(302, $response->statusCode);
        $this->assertStringContainsString('/admin/order/view', $response->headers->get('Location'));

        $order = Order::find()->where(['customer_id' => $customer->id])->one();
        $this->assertNotNull($order);
        $this->cleanupOrders[] = $order;
        $this->assertSame(1, OrderItem::find()->where(['order_id' => $order->id])->count());
    }

    /**
     * CMP-417: у request-компонента нет parsers для application/json, поэтому
     * admin-customers.js (который шлёт все свои fetch() как JSON-тело {id, text})
     * никогда не мог передать $id в actionAddNote($id) — Yii2 биндит параметры
     * action-метода только из query. Плюс customer_notes/customer_tags таблиц не
     * существовало вовсе ни в одной миграции (обе созданы в
     * m260922_100000_create_customer_notes_and_tags_tables.php).
     */
    public function testAddNoteAcceptsIdFromBodyAndPersistsLongCyrillicText(): void
    {
        $customer = $this->makeCustomer();

        $longText = 'Доўгая тэставая заметка: ' . str_repeat('кірылічны тэкст з "двукоссямі" №%&<>; ', 15);

        $controller = new CustomerController('customer', self::adminModule());
        $this->setPost([
            'id' => $customer->id, // как реально шлёт admin-customers.js — не в query!
            'text' => $longText,
        ]);

        $result = $controller->actionAddNote(null);

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));

        $stored = Yii::$app->db->createCommand(
            'SELECT text FROM {{%customer_notes}} WHERE customer_id = :id ORDER BY id DESC LIMIT 1',
            [':id' => $customer->id]
        )->queryScalar();

        $this->assertSame(trim($longText), $stored);
    }

    /**
     * CMP-417: actionUpdateTags($id) имел тот же баг с $id-из-body, а его
     * единственный реальный контракт (`tags`, полная замена списка) не совпадал
     * с тем, что шлёт admin-customers.js::addTag()/removeTag()
     * ({action: 'add'|'remove', tag}) — до фикса точечное добавление одного тега
     * читало отсутствующий 'tags' как [] и УДАЛЯЛО ВСЕ существующие теги вместо
     * добавления одного.
     */
    public function testUpdateTagsIncrementalAddAndRemoveDoNotWipeExistingTags(): void
    {
        $customer = $this->makeCustomer();

        Yii::$app->db->createCommand()->insert('{{%customer_tags}}', [
            'customer_id' => $customer->id,
            'tag' => 'ExistingTag',
            'created_at' => time(),
        ])->execute();

        $controller = new CustomerController('customer', self::adminModule());
        $this->setPost(['id' => $customer->id, 'action' => 'add', 'tag' => 'VIP']);
        $result = $controller->actionUpdateTags(null);

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $tags = Yii::$app->db->createCommand(
            'SELECT tag FROM {{%customer_tags}} WHERE customer_id = :id ORDER BY tag',
            [':id' => $customer->id]
        )->queryColumn();
        $this->assertSame(['ExistingTag', 'VIP'], $tags);

        $controller2 = new CustomerController('customer', self::adminModule());
        $this->setPost(['id' => $customer->id, 'action' => 'remove', 'tag' => 'VIP']);
        $result2 = $controller2->actionUpdateTags(null);

        $this->assertTrue($result2['success'] ?? false, json_encode($result2, JSON_UNESCAPED_UNICODE));
        $tagsAfterRemove = Yii::$app->db->createCommand(
            'SELECT tag FROM {{%customer_tags}} WHERE customer_id = :id ORDER BY tag',
            [':id' => $customer->id]
        )->queryColumn();
        $this->assertSame(['ExistingTag'], $tagsAfterRemove);
    }

    /**
     * CMP-417: actionAddPoints($id)/actionDeductPoints($id) читали 'points' из
     * POST, но admin-customers.js::submitPoints() шлёт ключ 'amount' (и id — в
     * теле, не в query). До фикса начисление всегда читало 0 баллов и падало на
     * проверке "Количество баллов должно быть больше 0", независимо от суммы,
     * реально введённой в форме.
     */
    public function testAddPointsAndDeductPointsAcceptAmountKeyAndIdFromBody(): void
    {
        $customer = $this->makeCustomer();

        $controller = new CustomerController('customer', self::adminModule());
        $this->setPost(['id' => $customer->id, 'amount' => 40, 'comment' => 'Кірылічны каментар начыслення']);
        $result = $controller->actionAddPoints(null);

        $this->assertTrue($result['success'] ?? false, json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertSame(40, $result['new_balance']);

        $controller2 = new CustomerController('customer', self::adminModule());
        $this->setPost(['id' => $customer->id, 'amount' => 15, 'comment' => 'Кірылічны каментар спісання']);
        $result2 = $controller2->actionDeductPoints(null);

        $this->assertTrue($result2['success'] ?? false, json_encode($result2, JSON_UNESCAPED_UNICODE));
        $this->assertSame(25, $result2['new_balance']);
    }

    private function makeCustomer(): Customer
    {
        $customer = new Customer();
        $customer->email = 'cmp417.' . uniqid('', false) . '@example.com';
        $customer->first_name = 'Тэст';
        $customer->last_name = 'Кліент';
        $customer->password_hash = '!';
        $this->assertTrue($customer->save(), json_encode($customer->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanupCustomers[] = $customer;

        return $customer;
    }
}
