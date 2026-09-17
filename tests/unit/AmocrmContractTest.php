<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\shared\components\Settings;
use app\backend\modules\admin\services\AmocrmStatusMapper;

/**
 * Contract test seam для AmoCRM (CMP-371, п.1.1).
 *
 * Покрывает контракт lead -> deal -> webhook без единого живого HTTP-вызова к AmoCRM:
 * - AmocrmClient: transport() подменяется канонированными ответами (см. FakeAmocrmClient).
 * - AmocrmStatusMapper: DB-запросы недоступны в тестовом окружении и по дизайну класса
 *   деградируют в try/catch до settings-фолбэка / name-heuristic — тестируем именно эту
 *   деградацию, не поднимая реальную БД.
 *
 * @group amocrm
 */
class AmocrmContractTest extends TestCase
{
    public $appConfig = '@tests/config.php';

    protected function setUp(): void
    {
        parent::setUp();
        // AmocrmStatusMapper обращается к Yii::$app->settings; тестовый app-конфиг (tests/config.php)
        // его не регистрирует, поэтому регистрируем здесь — не трогая глобальный конфиг.
        Yii::$app->set('settings', ['class' => Settings::class]);
    }

    // ── lead -> deal ──────────────────────────────────────────────────

    public function testCreateLeadSendsExpectedPayloadAndParsesResponse(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(200, json_encode([
            '_embedded' => ['leads' => [['id' => 12345, 'name' => 'Order #100']]],
        ]));

        $lead = $client->createLead(['name' => 'Order #100', 'price' => 15000]);

        $this->assertSame(12345, $lead['id']);
        $this->assertCount(1, $client->calls);
        $this->assertSame('POST', $client->calls[0]['method']);
        $this->assertStringContainsString('/api/v4/leads', $client->calls[0]['url']);
        $this->assertStringContainsString('Order #100', $client->calls[0]['payload']);
    }

    // ── deal status update (orchestrator -> AmoCRM) ────────────────────

    public function testUpdateLeadSendsPatchWithNewStatus(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(200, json_encode(['id' => 12345, 'status_id' => 142]));

        $result = $client->updateLead(12345, ['status_id' => 142, 'pipeline_id' => 7]);

        $this->assertSame(142, $result['status_id']);
        $this->assertSame('PATCH', $client->calls[0]['method']);
        $this->assertStringContainsString('/api/v4/leads/12345', $client->calls[0]['url']);
    }

    // ── rate-limit contract ─────────────────────────────────────────────

    public function testRateLimitRetriesOnceThenSucceeds(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(429, null);
        $client->queueResponse(200, json_encode(['_embedded' => ['leads' => [['id' => 999]]]]));

        $lead = $client->createLead(['name' => 'Order #101']);

        $this->assertSame(999, $lead['id']);
        $this->assertCount(2, $client->calls);
    }

    // ── webhook -> our order status (name heuristic, no DB row) ────────

    public function testWebhookStatusMapsToOrderStatusViaNameHeuristic(): void
    {
        $status = AmocrmStatusMapper::fromAmocrm(0, 999999, 'Выкуплен');
        $this->assertSame('bought_at_source', $status);
    }

    public function testWebhookShouldCreateOrderDetectsTriggerPhrase(): void
    {
        $this->assertTrue(AmocrmStatusMapper::shouldCreateOrder(0, 'Купили заказ'));
        $this->assertFalse(AmocrmStatusMapper::shouldCreateOrder(0, 'Новый'));
    }

    // ── webhook -> our order status (legacy settings fallback) ─────────

    public function testWebhookStatusMapsViaLegacySettingsFallback(): void
    {
        Yii::$app->settings->set('amocrm', 'status_id_paid', 555);

        $status = AmocrmStatusMapper::fromAmocrm(0, 555, null);

        $this->assertSame('paid', $status);
    }
}
