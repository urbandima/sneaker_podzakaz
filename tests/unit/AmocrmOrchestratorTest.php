<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\shared\components\Settings;
use app\backend\shared\services\AmocrmOrchestrator;

/**
 * AmocrmOrchestrator (CMP-371, п.2.1) — unit-тесты за флагом AMOCRM_USE_ORCHESTRATOR.
 *
 * Покрывает "вычислительную" часть оркестратора (createDeal/pushStatus/resolveIncomingLeadStatus)
 * через тот же FakeAmocrmClient-сеам, что и контракт-тест из [1.1]. *ForOrder-обёртки, которые
 * трогают ActiveRecord Order, здесь не тестируются — как и AmocrmStatusMapper, Order требует
 * схему из живой БД (недоступна в тестовом окружении); они переезжают на call sites в CMP-386
 * вместе с функциональным покрытием.
 *
 * @group amocrm
 */
class AmocrmOrchestratorTest extends TestCase
{
    public $appConfig = '@tests/config.php';

    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->set('settings', ['class' => Settings::class]);
    }

    // ── flag ──────────────────────────────────────────────────────────

    public function testEnabledDefaultsToFalse(): void
    {
        putenv('AMOCRM_USE_ORCHESTRATOR');
        unset($_ENV['AMOCRM_USE_ORCHESTRATOR'], $_SERVER['AMOCRM_USE_ORCHESTRATOR']);

        $this->assertFalse(AmocrmOrchestrator::enabled());
    }

    public function testEnabledReadsEnvFlag(): void
    {
        putenv('AMOCRM_USE_ORCHESTRATOR=1');
        $_ENV['AMOCRM_USE_ORCHESTRATOR'] = '1';

        try {
            $this->assertTrue(AmocrmOrchestrator::enabled());
        } finally {
            putenv('AMOCRM_USE_ORCHESTRATOR');
            unset($_ENV['AMOCRM_USE_ORCHESTRATOR'], $_SERVER['AMOCRM_USE_ORCHESTRATOR']);
        }
    }

    // ── buildLeadPayload / createDeal ────────────────────────────────────

    public function testBuildLeadPayloadComposesNameWithRecipientAndPrice(): void
    {
        $orchestrator = new AmocrmOrchestrator(new FakeAmocrmClient());

        $payload = $orchestrator->buildLeadPayload([
            'id'                   => 100,
            'recipient_first_name' => 'Иван',
            'recipient_last_name'  => 'Иванов',
            'total_amount'         => 15000,
            'pipeline_id'          => 7,
            'responsible_user_id'  => 42,
        ]);

        $this->assertSame('Заказ #100 — Иван Иванов', $payload['name']);
        $this->assertSame(15000, $payload['price']);
        $this->assertSame(7, $payload['pipeline_id']);
        $this->assertSame(42, $payload['responsible_user_id']);
        $this->assertArrayNotHasKey('status_id', $payload);
    }

    public function testCreateDealCreatesLeadContactAndNote(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(200, json_encode(['_embedded' => ['leads' => [['id' => 555]]]])); // createLead
        $client->queueResponse(200, json_encode(['_embedded' => ['contacts' => []]]));            // findContactByPhone: none found
        $client->queueResponse(200, json_encode(['_embedded' => ['contacts' => [['id' => 9]]]])); // createContact
        $client->queueResponse(200, null);                                                        // addNote

        $orchestrator = new AmocrmOrchestrator($client);
        $leadId = $orchestrator->createDeal([
            'id'            => 100,
            'total_amount'  => 15000,
            'client_phone'  => '+375291234567',
            'status'        => 'new',
        ]);

        $this->assertSame(555, $leadId);
        $this->assertCount(4, $client->calls);
        $this->assertSame('POST', $client->calls[0]['method']);
        $this->assertStringContainsString('/api/v4/leads', $client->calls[0]['url']);
        $this->assertStringContainsString('/api/v4/contacts', $client->calls[1]['url']);
        $this->assertStringContainsString('/api/v4/contacts', $client->calls[2]['url']);
        $this->assertStringContainsString('/api/v4/leads/notes', $client->calls[3]['url']);
    }

    public function testCreateDealSkipsContactCreationWhenContactAlreadyExists(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(200, json_encode(['_embedded' => ['leads' => [['id' => 555]]]]));
        $client->queueResponse(200, json_encode(['_embedded' => ['contacts' => [['id' => 9]]]]));
        $client->queueResponse(200, null); // addNote

        $orchestrator = new AmocrmOrchestrator($client);
        $orchestrator->createDeal(['id' => 100, 'client_phone' => '+375291234567']);

        $this->assertCount(3, $client->calls, 'createContact must not be called when findContactByPhone already found one');
    }

    public function testCreateDealReturnsNullWhenClientNotConfigured(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('', ''); // force unconfigured regardless of real .env AmoCRM credentials

        $orchestrator = new AmocrmOrchestrator($client);
        $leadId = $orchestrator->createDeal(['id' => 100]);

        $this->assertNull($leadId);
        $this->assertCount(0, $client->calls);
    }

    public function testCreateDealReturnsNullWhenLeadCreationFails(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(500, null);

        $orchestrator = new AmocrmOrchestrator($client);
        $leadId = $orchestrator->createDeal(['id' => 100]);

        $this->assertNull($leadId);
    }

    // ── pushStatus ────────────────────────────────────────────────────

    public function testPushStatusSendsMappedPipelineAndStatusViaLegacySettingsFallback(): void
    {
        Yii::$app->settings->set('amocrm', 'status_id_paid', 142);
        Yii::$app->settings->set('amocrm', 'pipeline_id', 7);

        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');
        $client->queueResponse(200, json_encode(['id' => 555, 'status_id' => 142]));

        $orchestrator = new AmocrmOrchestrator($client);
        $ok = $orchestrator->pushStatus(555, 'paid');

        $this->assertTrue($ok);
        $this->assertSame('PATCH', $client->calls[0]['method']);
        $this->assertStringContainsString('/api/v4/leads/555', $client->calls[0]['url']);
        $this->assertStringContainsString('"status_id":142', $client->calls[0]['payload']);
        $this->assertStringContainsString('"pipeline_id":7', $client->calls[0]['payload']);
    }

    public function testPushStatusReturnsFalseWhenNoMappingConfigured(): void
    {
        $client = new FakeAmocrmClient();
        $client->setCredentialsForTesting('example.amocrm.ru', 'fake-token');

        $orchestrator = new AmocrmOrchestrator($client);
        $ok = $orchestrator->pushStatus(555, 'some_unmapped_status');

        $this->assertFalse($ok);
        $this->assertCount(0, $client->calls);
    }

    // ── resolveIncomingLeadStatus ─────────────────────────────────────

    public function testResolveIncomingLeadStatusCreatesWhenNoOrderAndTriggerPhrase(): void
    {
        $orchestrator = new AmocrmOrchestrator(new FakeAmocrmClient());

        $decision = $orchestrator->resolveIncomingLeadStatus(false, null, 999999, 0, 'Купили заказ');

        $this->assertSame('create', $decision['action']);
    }

    public function testResolveIncomingLeadStatusIgnoresWhenNoOrderAndNoTrigger(): void
    {
        $orchestrator = new AmocrmOrchestrator(new FakeAmocrmClient());

        $decision = $orchestrator->resolveIncomingLeadStatus(false, null, 999999, 0, 'Новый');

        $this->assertSame('ignore', $decision['action']);
    }

    public function testResolveIncomingLeadStatusUpdatesWhenStatusChangedViaNameHeuristic(): void
    {
        $orchestrator = new AmocrmOrchestrator(new FakeAmocrmClient());

        $decision = $orchestrator->resolveIncomingLeadStatus(true, 'new', 999999, 0, 'Выкуплен');

        $this->assertSame('update', $decision['action']);
        $this->assertSame('bought_at_source', $decision['newStatus']);
    }

    public function testResolveIncomingLeadStatusIgnoresWhenStatusUnchanged(): void
    {
        $orchestrator = new AmocrmOrchestrator(new FakeAmocrmClient());

        $decision = $orchestrator->resolveIncomingLeadStatus(true, 'bought_at_source', 999999, 0, 'Выкуплен');

        $this->assertSame('ignore', $decision['action']);
    }
}
