<?php

use yii\db\Migration;

class m260424_300000_deduplicate_product_characteristics extends Migration
{
    public function safeUp()
    {
        try {
            $this->execute("
                DELETE c1 FROM {{%product_characteristic_value}} c1
                INNER JOIN {{%product_characteristic_value}} c2
                WHERE c1.id > c2.id
                  AND c1.product_id = c2.product_id
                  AND c1.characteristic_id = c2.characteristic_id
            ");
        } catch (\Exception $e) {
            // Table may not exist
        }

        try {
            $indexes = array_column(
                $this->db->createCommand('SHOW INDEX FROM {{%product_characteristic_value}}')->queryAll(),
                'Key_name'
            );
            if (!in_array('idx_pcv_product_characteristic', $indexes)) {
                $this->createIndex('idx_pcv_product_characteristic', '{{%product_characteristic_value}}', ['product_id', 'characteristic_id'], true);
            }
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    public function safeDown()
    {
        try {
            $this->dropIndex('idx_pcv_product_characteristic', '{{%product_characteristic_value}}');
        } catch (\Exception $e) {}
    }
}
