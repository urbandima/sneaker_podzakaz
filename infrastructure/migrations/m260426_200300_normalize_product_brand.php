<?php
use yii\db\Migration;

class m260426_200300_normalize_product_brand extends Migration
{
    public function safeUp()
    {
        $this->db->createCommand("UPDATE {{%product}} SET brand_name = NULL WHERE brand_name IN ('-', '', 'null', 'NULL')")->execute();
    }

    public function safeDown()
    {
        // Intentionally empty — cannot reliably restore NULL → '-'
    }
}
