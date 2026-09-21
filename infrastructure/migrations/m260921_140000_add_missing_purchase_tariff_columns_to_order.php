<?php

use yii\db\Migration;

/**
 * checkout\models\Order::rules() валидирует purchase_cost/purchase_status/purchase_id/
 * purchase_receipt_url/purchase_date/tariff_weight_kg на каждом save()/validate(), но
 * миграции их никогда не создавали — были только в рантайм-патчере
 * TariffSetupService::ensureOrderSupport() (вызывается только со страницы /admin/tariff)
 * и в ручных addColumn-миграциях, которые добавили соседние purchase_currency/
 * purchase_user_id, но пропустили сам purchase_cost. Итог: validate()/save() на Order
 * обращается к несуществующим колонкам и падает с "Getting unknown property" — весь
 * чекаут (оформление заказа) был сломан на 100% заказов, обнаружено при живом прогоне
 * CMP-410. china_delivery_status — тот же класс бага, ещё одна rules()-колонка без
 * миграции.
 */
class m260921_140000_add_missing_purchase_tariff_columns_to_order extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema === null) {
            echo "    > skip: {{%order}} table does not exist yet\n";
            return;
        }
        $cols = $schema->columnNames;

        if (!in_array('purchase_cost', $cols, true)) {
            $this->addColumn('order', 'purchase_cost', $this->decimal(10, 2)->null()->after('purchase_currency'));
        }
        if (!in_array('purchase_status', $cols, true)) {
            $this->addColumn('order', 'purchase_status', $this->string(50)->null()->after('purchase_cost'));
        }
        if (!in_array('purchase_id', $cols, true)) {
            $this->addColumn('order', 'purchase_id', $this->string(50)->null()->after('purchase_status'));
        }
        if (!in_array('purchase_receipt_url', $cols, true)) {
            $this->addColumn('order', 'purchase_receipt_url', $this->string(500)->null()->after('purchase_id'));
        }
        if (!in_array('purchase_date', $cols, true)) {
            $this->addColumn('order', 'purchase_date', $this->dateTime()->null()->after('purchase_receipt_url'));
        }
        if (!in_array('tariff_weight_kg', $cols, true)) {
            $this->addColumn('order', 'tariff_weight_kg', $this->decimal(6, 2)->null()->defaultValue(0.5)->after('tariff_id'));
        }
        if (!in_array('china_delivery_status', $cols, true)) {
            $this->addColumn('order', 'china_delivery_status', $this->string(50)->null()->after('local_delivery_status'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        $cols = $schema ? $schema->columnNames : [];

        foreach (['china_delivery_status', 'tariff_weight_kg', 'purchase_date', 'purchase_receipt_url', 'purchase_id', 'purchase_status', 'purchase_cost'] as $col) {
            if (in_array($col, $cols, true)) {
                $this->dropColumn('order', $col);
            }
        }
    }
}
