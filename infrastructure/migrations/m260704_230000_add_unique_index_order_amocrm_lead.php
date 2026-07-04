<?php

use yii\db\Migration;
use yii\db\IndexConstraint;

/**
 * Race-condition fix for OrderFromLeadService::createFromLeadData().
 *
 * The service does a check-then-insert on `order`.`amocrm_lead_id`
 * (SELECT by amocrm_lead_id, INSERT if not found) but the existing
 * idx_order_amocrm_lead index is NOT unique. Two concurrent AmoCRM
 * webhooks for the same lead can both pass the "not found" check and
 * insert duplicate orders.
 *
 * This migration:
 *   1) Deduplicates any existing amocrm_lead_id collisions — keeps the
 *      oldest order (lowest id) per lead as canonical and clears
 *      amocrm_lead_id on the newer duplicate orders so they stop
 *      colliding. NULL values are not touched — MySQL/InnoDB unique
 *      indexes allow multiple NULLs, so orders without a lead link are
 *      unaffected.
 *   2) Converts idx_order_amocrm_lead into a UNIQUE index so the
 *      database itself enforces "one order per AmoCRM lead" going
 *      forward, closing the race window.
 *
 * IMPORTANT — before running this migration against a production
 * database, manually check for existing duplicates:
 *   SELECT amocrm_lead_id, COUNT(*) AS cnt
 *   FROM `order`
 *   WHERE amocrm_lead_id IS NOT NULL
 *   GROUP BY amocrm_lead_id
 *   HAVING cnt > 1;
 * The dedup step below is a best-effort safeguard: it only clears the
 * amocrm_lead_id link on the newer duplicate rows so the unique index
 * can be created — it does NOT merge order items/payments/history
 * between duplicate orders. If duplicates are found, review them
 * manually before or after deploy.
 */
class m260704_230000_add_unique_index_order_amocrm_lead extends Migration
{
    private const INDEX_NAME = 'idx_order_amocrm_lead';

    public function safeUp()
    {
        // 1) Dedup existing collisions (keep oldest order per lead id).
        $dupLeadIds = $this->db->createCommand(
            'SELECT amocrm_lead_id FROM {{%order}} WHERE amocrm_lead_id IS NOT NULL GROUP BY amocrm_lead_id HAVING COUNT(*) > 1'
        )->queryColumn();

        foreach ($dupLeadIds as $leadId) {
            $minId = $this->db->createCommand(
                'SELECT MIN(id) FROM {{%order}} WHERE amocrm_lead_id = :lid',
                [':lid' => $leadId]
            )->queryScalar();

            $this->update('{{%order}}', ['amocrm_lead_id' => null], [
                'and',
                ['amocrm_lead_id' => $leadId],
                ['not', ['id' => $minId]],
            ]);
        }

        // 2) Ensure a UNIQUE index exists on amocrm_lead_id.
        $existing = $this->findIndex();

        if ($existing !== null && !$existing->isUnique) {
            $this->dropIndex(self::INDEX_NAME, '{{%order}}');
            $existing = null;
        }

        if ($existing === null) {
            $this->createIndex(self::INDEX_NAME, '{{%order}}', 'amocrm_lead_id', true);
        }
    }

    public function safeDown()
    {
        $existing = $this->findIndex();

        if ($existing !== null && $existing->isUnique) {
            $this->dropIndex(self::INDEX_NAME, '{{%order}}');
            $existing = null;
        }

        if ($existing === null) {
            $this->createIndex(self::INDEX_NAME, '{{%order}}', 'amocrm_lead_id');
        }
        // Note: dedup step in safeUp() (clearing amocrm_lead_id on newer
        // duplicate orders) is intentionally not reversed — the original
        // duplicate values are not recoverable and were only ever
        // redundant duplicates of an existing canonical order.
    }

    private function findIndex(): ?IndexConstraint
    {
        $indexes = $this->db->getSchema()->getTableIndexes('order', true);
        foreach ($indexes as $index) {
            if ($index instanceof IndexConstraint && $index->name === self::INDEX_NAME) {
                return $index;
            }
        }
        return null;
    }
}
