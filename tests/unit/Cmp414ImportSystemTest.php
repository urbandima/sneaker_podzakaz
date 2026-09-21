<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\catalog\models\ImportBatch;
use app\backend\modules\catalog\models\ImportLog;

/**
 * Регрессионные тесты CMP-414: разведение двух конфликтующих систем импорта.
 *
 * До фикса реальные Poizon-импортёры (PoizonImportController, PoizonImportJsonController,
 * PoizonController) либо импортировали несуществующий класс
 * app\backend\modules\catalog\models\ImportLog ("Class not found"), либо использовали
 * модель app\backend\modules\admin\models\import\ImportLog, чьи rules()/relations
 * требовали колонку task_id — которой в физической таблице import_log никогда не было
 * (там только batch_id, FK на import_batch). Любая попытка залогировать товар при
 * импорте падала. Канонической выбрана система ImportBatch+ImportLog (catalog\models),
 * обслуживающая прямой API-импорт из Poizon — основной бизнес-сценарий сайта.
 */
class Cmp414ImportSystemTest extends TestCase
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
     * ImportBatch::getLogs() ссылался на ImportLog::class без use-импорта — в
     * пространстве имён catalog\models это резолвилось в несуществующий класс.
     * После создания канонической модели связь должна реально работать через БД.
     */
    public function testImportBatchLogsRelationReturnsRealLogs()
    {
        $batch = new ImportBatch();
        $batch->source = ImportBatch::SOURCE_POIZON;
        $batch->type = ImportBatch::TYPE_FULL;
        $batch->status = ImportBatch::STATUS_PROCESSING;
        $this->assertTrue($batch->save(false), json_encode($batch->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $batch;

        $log = ImportLog::log($batch->id, ImportLog::ACTION_CREATED, 'Товар создан', [
            'sku' => 'CMP414-SKU-1',
            'poizon_id' => 'poizon-1',
            'product_name' => 'CMP-414 тестовый товар',
        ]);
        $this->cleanup[] = $log;

        $reloaded = ImportBatch::findOne($batch->id);
        $logs = $reloaded->logs;

        $this->assertCount(1, $logs);
        $this->assertSame($batch->id, $logs[0]->batch_id);
        $this->assertSame('CMP414-SKU-1', $logs[0]->sku);
    }

    /**
     * Регрессия ровно того паттерна, который ломал реальный консольный импортёр:
     * ImportLog::log($batchId, ACTION_ERROR, $message, [...]) должен сохраняться
     * без UnknownPropertyException и без Class-not-found на несуществующий task_id.
     */
    public function testImportLogStaticLogHelperPersistsErrorEntryAgainstRealSchema()
    {
        $batch = new ImportBatch();
        $batch->source = ImportBatch::SOURCE_POIZON;
        $batch->type = ImportBatch::TYPE_FULL;
        $batch->status = ImportBatch::STATUS_PROCESSING;
        $this->assertTrue($batch->save(false));
        $this->cleanup[] = $batch;

        $log = ImportLog::log($batch->id, ImportLog::ACTION_ERROR, 'Ошибка импорта: тест', [
            'poizon_id' => 'poizon-err-1',
            'product_name' => 'CMP-414 сломанный товар',
            'error_details' => "Trace...",
        ]);
        $this->cleanup[] = $log;

        $this->assertFalse($log->isNewRecord);
        $this->assertSame(ImportLog::LEVEL_ERROR, $log->level);

        $found = ImportLog::find()->where(['batch_id' => $batch->id, 'action' => ImportLog::ACTION_ERROR])->one();
        $this->assertNotNull($found);
        $this->assertSame('poizon-err-1', $found->poizon_id);
    }

    /** getActionBadgeClass() использовался в admin/poizon/view.php, но не существовал ни в одной модели. */
    public function testImportLogHasActionBadgeClassUsedByAdminView()
    {
        $log = new ImportLog();
        $log->action = ImportLog::ACTION_CREATED;

        $this->assertSame('bg-success', $log->getActionBadgeClass());
    }

    /**
     * Система ImportSource/ImportTask (admin/import/*) полностью удалена как
     * дублирующая — таблицы должны отсутствовать в схеме после миграции
     * m260922_110000_drop_advanced_import_system.
     */
    public function testAdvancedImportSystemTablesNoLongerExist()
    {
        $db = \Yii::$app->db;
        $schema = $db->schema;

        foreach (['import_source', 'import_task', 'import_product_price', 'import_category_map', 'import_notification', 'currency_rate'] as $table) {
            $this->assertNull(
                $schema->getTableSchema('{{%' . $table . '}}', true),
                "Таблица {$table} должна быть удалена вместе с неканонической системой импорта"
            );
        }
    }
}
