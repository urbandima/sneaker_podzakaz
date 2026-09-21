<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\DeliveryTracking;
use app\backend\modules\checkout\services\ShippingService;
use app\backend\modules\account\models\Customer;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Characteristic;
use app\backend\modules\catalog\models\CatalogInquiry;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\procurement\models\PurchaseOrder;
use app\backend\modules\procurement\models\PurchaseOrderItem;
use app\backend\modules\procurement\models\SupplierReturn;
use app\backend\modules\procurement\models\SupplierReturnItem;
use app\backend\modules\coupon\models\Coupon;
use app\backend\modules\procurement\models\Supplier;

/**
 * Регрессионные тесты CMP-410: класс багов «код, который никогда не исполнялся».
 *
 * Каждый тест воспроизводит один подтверждённый баг (сохранение AR-модели падало
 * с "Getting/Setting unknown property" или SQL-ошибкой strict mode до фикса) и
 * проверяет, что реальный save()/validate() в БД теперь проходит.
 */
class Cmp410SchemaDriftTest extends TestCase
{
    private array $cleanup = [];

    protected function tearDown(): void
    {
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

    /**
     * Order::rules() ссылалось на purchase_cost/purchase_status/purchase_id/
     * purchase_receipt_url/purchase_date/tariff_weight_kg/china_delivery_status —
     * колонок не было в схеме, поэтому validate() падал с "Getting unknown property"
     * на КАЖДОМ оформлении заказа (100% чекаута).
     */
    public function testOrderValidatesAndSavesWithoutUnknownPropertyError()
    {
        $order = new Order();
        $order->client_name = 'Иван Иванов';
        $order->client_phone = '+375291234567';
        $order->delivery_address = 'г. Минск, ул. Ленина, д. 5, кв. 10';
        $order->status = 'new';

        $this->assertTrue($order->validate(), 'Order::validate() упал: ' . json_encode($order->errors, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($order->save(false));
        $this->cleanup[] = $order;
    }

    /**
     * mb_stripos-регрессия: адрес с обычной капитализацией "г. Минск" должен
     * проходить валидацию (stripos() не умеет в кириллицу).
     */
    public function testShippingServiceValidatesCapitalizedCyrillicCity()
    {
        $service = new ShippingService();
        [$isValid, $errors] = array_values($service->validateAddress('г. Минск, ул. Ленина, д. 5', 'belarus'));

        $this->assertTrue($isValid, 'Адрес с "г. Минск" не прошёл валидацию: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Customer::rules()/findByEmail() ссылались на несуществующую колонку is_active
     * (реальная — status). Логин по email был сломан для всех покупателей.
     */
    public function testCustomerLoginLookupFindsActiveCustomerByRealStatusColumn()
    {
        $email = 'cmp410-' . uniqid() . '@example.com';
        $customer = new Customer();
        $customer->email = $email;
        $customer->phone = '+375291112233';
        $customer->setPassword('test-password-123');
        $customer->status = Customer::STATUS_ACTIVE_DB;

        $this->assertTrue($customer->save(false), json_encode($customer->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $customer;

        $found = Customer::findByEmail($email);
        $this->assertNotNull($found, 'findByEmail() не нашёл активного покупателя (status=10)');
        $this->assertSame($email, $found->email);
    }

    /**
     * Category — TimestampBehavior писал date('Y-m-d H:i:s') в int-колонку
     * created_at/updated_at, MySQL обрезал строку до "2026". lft/rgt были
     * NOT NULL без дефолта и без единого места в коде, которое их заполняет.
     */
    public function testCategorySavesWithValidUnixTimestamp()
    {
        $category = new Category();
        $category->name = 'CMP-410 тестовая категория';

        $this->assertTrue($category->save(), json_encode($category->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $category;

        $this->assertIsInt($category->created_at);
        $this->assertGreaterThan(1_700_000_000, $category->created_at, 'created_at усечён до года вместо unix-времени');
    }

    /** Тот же класс бага, что и Category, но на brand.created_at/updated_at (int). */
    public function testBrandSavesWithValidUnixTimestamp()
    {
        $brand = new Brand();
        $brand->name = 'CMP-410 тестовый бренд';

        $this->assertTrue($brand->save(), json_encode($brand->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $brand;

        $this->assertIsInt($brand->created_at);
        $this->assertGreaterThan(1_700_000_000, $brand->created_at);
    }

    /**
     * Characteristic::logHistory() импортировал несуществующий класс
     * app\...\models\history\CharacteristicHistory (реальный — без под-неймспейса
     * history\) — любое сохранение (create и update) падало с "Class not found".
     */
    public function testCharacteristicSaveDoesNotThrowClassNotFound()
    {
        $characteristic = new Characteristic();
        $characteristic->name = 'CMP-410 материал';
        $characteristic->key = 'material' . uniqid();
        $characteristic->type = 'select';

        $this->assertTrue($characteristic->save(), json_encode($characteristic->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $characteristic;
    }

    /**
     * Coupon.name — NOT NULL без дефолта в БД, но не было 'required' в rules().
     * Купон без имени падал с SQL-ошибкой strict mode вместо чистой валидации.
     */
    public function testCouponWithoutNameFailsValidationNotSqlError()
    {
        $coupon = new Coupon();
        $coupon->code = 'CMP410-' . uniqid();
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 10;

        $this->assertFalse($coupon->validate());
        $this->assertArrayHasKey('name', $coupon->errors);
    }

    /**
     * Coupon::findByCode() и CouponController оба должны одинаково регистронезависимо
     * складывать код через mb_strtoupper — round-trip с кириллическим кодом.
     */
    public function testCouponFindByCodeMatchesCyrillicCodeCaseInsensitively()
    {
        $coupon = new Coupon();
        $coupon->code = mb_strtoupper('скидка' . uniqid());
        $coupon->name = 'CMP-410 кириллический код';
        $coupon->type = Coupon::TYPE_FIXED;
        $coupon->value = 5;
        $coupon->is_active = 1;

        $this->assertTrue($coupon->save(), json_encode($coupon->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $coupon;

        $found = Coupon::findByCode(mb_strtolower($coupon->code));
        $this->assertNotNull($found, 'findByCode() не нашёл купон по кириллическому коду в другом регистре');
        $this->assertSame($coupon->id, $found->id);
    }

    /**
     * CatalogInquiry — полноценная модель "быстрого заказа" с карточки товара,
     * но таблица catalog_inquiry никогда не создавалась ни одной миграцией.
     */
    public function testCatalogInquirySavesAgainstRealTable()
    {
        $product = Product::find()->one();
        if (!$product) {
            $this->markTestSkipped('Нет ни одного товара в тестовой БД для CatalogInquiry');
        }

        $inquiry = new CatalogInquiry();
        $inquiry->product_id = $product->id;
        $inquiry->name = 'CMP-410 клиент';
        $inquiry->phone = '+375291234567';

        $this->assertTrue($inquiry->save(), json_encode($inquiry->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $inquiry;
    }

    /**
     * DeliveryTracking::getEvents()/addEvent() читали/писали events_json,
     * которого нет в схеме (реальная колонка — tracking_events).
     */
    public function testDeliveryTrackingAddEventPersistsToRealColumn()
    {
        $order = new Order();
        $order->client_name = 'CMP-410 клиент трекинга';
        $order->status = 'new';
        $this->assertTrue($order->save(false));
        $this->cleanup[] = $order;

        $tracking = new DeliveryTracking();
        $tracking->order_id = $order->id;
        $tracking->addEvent(DeliveryTracking::STATUS_IN_TRANSIT, 'В пути', 'Минск');

        $this->assertTrue($tracking->save(), json_encode($tracking->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $tracking;

        $reloaded = DeliveryTracking::findOne($tracking->id);
        $this->assertNotEmpty($reloaded->getEvents());
        $this->assertSame('В пути', $reloaded->getEvents()[0]['description']);
    }

    /**
     * PurchaseOrder::recalcTotals() писал total_amount_cny/total_amount_byn,
     * которых не было в схеме (были total_cny/total_byn без "_amount_").
     * PurchaseOrderItem::rules() требовал несуществующий product_id.
     */
    public function testPurchaseOrderRecalcTotalsPersistsToRenamedColumns()
    {
        $supplier = new Supplier();
        $supplier->name = 'CMP-410 поставщик';
        $this->assertTrue($supplier->save());
        $this->cleanup[] = $supplier;

        $po = new PurchaseOrder();
        $po->purchase_number = 'PO-CMP410-' . uniqid();
        $po->supplier_id = $supplier->id;
        $po->status = PurchaseOrder::STATUS_DRAFT;
        $this->assertTrue($po->save(), json_encode($po->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $po;

        $item = new PurchaseOrderItem();
        $item->purchase_order_id = $po->id;
        $item->product_name = 'CMP-410 товар';
        $item->quantity = 2;
        $item->price_cny = 100;
        $item->price_byn = 50;
        $this->assertTrue($item->save(), json_encode($item->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $item;

        $po->recalcTotals();
        $this->assertTrue($po->save(false));

        $po->refresh();
        $this->assertEquals(200.0, (float) $po->total_amount_cny);
        $this->assertEquals(100.0, (float) $po->total_amount_byn);
    }

    /**
     * SupplierReturnItem::rules() требовал несуществующий price_cny
     * (реальная колонка — только price_byn).
     */
    public function testSupplierReturnItemSavesWithRealPriceColumn()
    {
        $supplier = new Supplier();
        $supplier->name = 'CMP-410 поставщик возврата';
        $this->assertTrue($supplier->save());
        $this->cleanup[] = $supplier;

        $po = new PurchaseOrder();
        $po->purchase_number = 'PO-CMP410-SR-' . uniqid();
        $po->supplier_id = $supplier->id;
        $po->status = PurchaseOrder::STATUS_DRAFT;
        $this->assertTrue($po->save(), json_encode($po->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $po;

        $sr = new SupplierReturn();
        $sr->return_number = 'SR-CMP410-' . uniqid();
        $sr->purchase_order_id = $po->id;
        $sr->supplier_id = $supplier->id;
        $sr->status = SupplierReturn::STATUS_DRAFT;
        $this->assertTrue($sr->save(), json_encode($sr->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $sr;

        $item = new SupplierReturnItem();
        $item->supplier_return_id = $sr->id;
        $item->product_name = 'CMP-410 возврат товара';
        $item->quantity = 1;
        $item->price_byn = 30;
        $this->assertTrue($item->save(), json_encode($item->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $item;
    }

    /**
     * Product A14 placeholder-guard сравнивал strtolower($name) === 'товар' —
     * байтовое сравнение не находит "Товар" (заглавная кириллица).
     */
    public function testProductPlaceholderNameGuardCatchesCapitalizedCyrillic()
    {
        $product = new Product();
        $product->name = 'Товар';
        $product->price = 100;
        $product->is_active = true;

        $product->beforeSave(true);

        $this->assertFalse($product->is_active, 'Плейсхолдер "Товар" с заглавной буквы не был пойман guard-ом');
    }
}
