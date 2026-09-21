<?php

use yii\db\Migration;

/**
 * procurement\models\PurchaseOrder::recalcTotals()/rules() и все admin/views/procurement/*
 * (index.php, view.php) читают/пишут total_amount_cny / total_amount_byn — это
 * единственная используемая в коде форма имени. Миграция, создавшая таблицу,
 * назвала колонки total_cny / total_byn (без "_amount_") — любой save() после
 * recalcTotals() падал с "Setting unknown property". Переименовываем колонки под
 * код (а не наоборот), т.к. соглашение total_amount_* уже используется в 5+ местах.
 * Найдено при живом прогоне CMP-410.
 */
class m260921_143000_rename_purchase_order_total_columns extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%purchase_order}}', true);
        if ($schema === null) {
            echo "    > skip: {{%purchase_order}} does not exist\n";
            return;
        }
        $cols = $schema->columnNames;

        if (in_array('total_cny', $cols, true) && !in_array('total_amount_cny', $cols, true)) {
            $this->renameColumn('{{%purchase_order}}', 'total_cny', 'total_amount_cny');
        }
        if (in_array('total_byn', $cols, true) && !in_array('total_amount_byn', $cols, true)) {
            $this->renameColumn('{{%purchase_order}}', 'total_byn', 'total_amount_byn');
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%purchase_order}}', true);
        $cols = $schema ? $schema->columnNames : [];

        if (in_array('total_amount_cny', $cols, true) && !in_array('total_cny', $cols, true)) {
            $this->renameColumn('{{%purchase_order}}', 'total_amount_cny', 'total_cny');
        }
        if (in_array('total_amount_byn', $cols, true) && !in_array('total_byn', $cols, true)) {
            $this->renameColumn('{{%purchase_order}}', 'total_amount_byn', 'total_byn');
        }
    }
}
