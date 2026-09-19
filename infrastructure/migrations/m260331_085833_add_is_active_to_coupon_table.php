<?php

use yii\db\Migration;

class m260331_085833_add_is_active_to_coupon_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Колонка уже создаётся миграцией m250315_120000_create_coupon_tables — делаем no-op,
        // чтобы не ронять чистые инсталляции (CI, новые окружения).
        $schema = $this->db->schema->getTableSchema('{{%coupon}}', true);
        if ($schema !== null && in_array('is_active', $schema->columnNames, true)) {
            echo "    > skipped: {{%coupon}}.is_active уже создана миграцией m250315_120000_create_coupon_tables\n";
            return true;
        }

        $this->addColumn('{{%coupon}}', 'is_active', $this->boolean()->notNull()->defaultValue(1));
    }

    public function safeDown()
    {
        // Не удаляем: колонка принадлежит m250315_120000_create_coupon_tables.
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260331_085833_add_is_active_to_coupon_table cannot be reverted.\n";

        return false;
    }
    */
}
