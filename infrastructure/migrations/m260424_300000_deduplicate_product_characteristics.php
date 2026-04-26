<?php

use yii\db\Migration;

class m260424_300000_deduplicate_product_characteristics extends Migration
{
    public function up()
    {
        // Remove duplicate rows keeping the one with the lowest id
        $this->execute("
            DELETE c1 FROM {{%product_characteristic_value}} c1
            INNER JOIN {{%product_characteristic_value}} c2
            WHERE c1.id > c2.id
              AND c1.product_id = c2.product_id
              AND c1.characteristic_id = c2.characteristic_id
        ");

        // Add unique constraint to prevent future duplicates
        // Use CREATE UNIQUE INDEX to avoid error if index already exists
        $this->execute("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_pcv_product_characteristic
            ON {{%product_characteristic_value}} (product_id, characteristic_id)
        ");
    }

    public function down()
    {
        $this->execute("DROP INDEX IF EXISTS idx_pcv_product_characteristic ON {{%product_characteristic_value}}");
    }
}
