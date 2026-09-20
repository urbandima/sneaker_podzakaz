<?php

namespace tests\unit;

use app\backend\modules\admin\services\OrderPaymentService;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для OrderPaymentService (CMP-392, план CMP-371 п.3.2).
 *
 * НЕ мёржить логику этого сервиса в main до security review (payment-adjacent:
 * закупочная стоимость, чек, сумма заказа). Тесты покрывают только чистую
 * валидацию файла чека — без БД и без реального upload.
 */
class OrderPaymentServiceTest extends TestCase
{
    public function testAllowedReceiptExtensionsWithMatchingMimeAreAccepted()
    {
        $this->assertTrue(OrderPaymentService::isAllowedReceiptFile('jpg', 'image/jpeg'));
        $this->assertTrue(OrderPaymentService::isAllowedReceiptFile('PNG', 'image/png'));
        $this->assertTrue(OrderPaymentService::isAllowedReceiptFile('pdf', 'application/pdf'));
    }

    public function testDisallowedExtensionIsRejected()
    {
        $this->assertFalse(OrderPaymentService::isAllowedReceiptFile('php', 'image/jpeg'));
        $this->assertFalse(OrderPaymentService::isAllowedReceiptFile('phtml', 'application/pdf'));
    }

    public function testExtensionMimeMismatchIsRejected()
    {
        // Расширение подделано под изображение, а реальный MIME — не из белого списка
        // (защита от RCE через двойное расширение / поддельный content-type).
        $this->assertFalse(OrderPaymentService::isAllowedReceiptFile('jpg', 'application/x-php'));
    }

    public function testSaveOrderItemsRejectsEmptyItemList()
    {
        $service = new OrderPaymentService();
        $order = new \app\backend\modules\checkout\models\Order();
        $order->id = 999999;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Необходимо добавить хотя бы один товар в заказ');

        $service->saveOrderItems($order, [
            ['product_name' => '', 'price' => ''],
        ], false);
    }
}
