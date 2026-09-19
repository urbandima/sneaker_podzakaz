<?php

namespace tests\unit;

use app\backend\modules\admin\services\OrderShippingService;
use app\backend\modules\checkout\models\Order;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для OrderShippingService (CMP-392, план CMP-371 п.3.2).
 *
 * Покрывает только чистую логику (фильтрация ПВЗ, форматирование трек-статуса,
 * валидация logistics_status) — без обращения к БД/внешним API.
 */
class OrderShippingServiceTest extends TestCase
{
    public function testAllowedLogisticsStatusesAreAccepted()
    {
        foreach (OrderShippingService::ALLOWED_LOGISTICS_STATUSES as $status) {
            $this->assertTrue(OrderShippingService::isAllowedLogisticsStatus($status));
        }
    }

    public function testUnknownLogisticsStatusIsRejected()
    {
        $this->assertFalse(OrderShippingService::isAllowedLogisticsStatus('delivered'));
        $this->assertFalse(OrderShippingService::isAllowedLogisticsStatus(''));
    }

    public function testFilterPvzListMatchesByCityOrAddress()
    {
        $list = [
            ['id' => 1, 'num' => '0101', 'city' => 'Минск', 'name' => 'ул. Ленина, 1'],
            ['id' => 2, 'num' => '0202', 'city' => 'Гомель', 'name' => 'пр. Ленина, 5'],
        ];

        $results = OrderShippingService::filterPvzList($list, 'минск');

        $this->assertCount(1, $results);
        $this->assertSame('Минск', $results[0]['city']);
        $this->assertSame('ул. Ленина, 1', $results[0]['address']);
    }

    public function testFilterPvzListEmptyQueryReturnsAllSortedByCity()
    {
        $list = [
            ['id' => 1, 'num' => '0202', 'city' => 'Гомель', 'name' => 'A'],
            ['id' => 2, 'num' => '0101', 'city' => 'Брест', 'name' => 'B'],
        ];

        $results = OrderShippingService::filterPvzList($list, '');

        $this->assertCount(2, $results);
        $this->assertSame('Брест', $results[0]['city']);
        $this->assertSame('Гомель', $results[1]['city']);
    }

    public function testFilterPvzListRespectsLimit()
    {
        $list = array_map(fn ($i) => ['id' => $i, 'num' => (string)$i, 'city' => 'Минск'], range(1, 10));

        $results = OrderShippingService::filterPvzList($list, '', 3);

        $this->assertCount(3, $results);
    }

    public function testFormatTrackStatusTextWithDateAndLocation()
    {
        $text = OrderShippingService::formatTrackStatusText([
            'status_name' => 'В пути',
            'status_date' => '2026-09-01',
            'location'    => 'Минск',
        ]);

        $this->assertSame('В пути (01.09.2026) — Минск', $text);
    }

    public function testFormatTrackStatusTextFallsBackToDefaultMessage()
    {
        $this->assertSame('Нет данных', OrderShippingService::formatTrackStatusText([]));
    }

    public function testCheckTrackRejectsEmptyTrackWithoutTouchingComponents()
    {
        $service = new OrderShippingService();
        $result = $service->checkTrack('', null);

        $this->assertFalse($result['success']);
        $this->assertSame('Трек не указан', $result['status']);
    }
}
