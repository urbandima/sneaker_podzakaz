<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%customer_social_account}}`.
 */
class m241228_170500_create_customer_social_account_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%customer_social_account}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'provider' => $this->string(50)->notNull(),
            'provider_id' => $this->string(128)->notNull(),
            'access_token' => $this->text(),
            'refresh_token' => $this->text(),
            'expires_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-customer_social_account-provider-provider_id',
            '{{%customer_social_account}}',
            ['provider', 'provider_id'],
            true
        );

        $this->createIndex(
            'idx-customer_social_account-customer_id',
            '{{%customer_social_account}}',
            'customer_id'
        );

        // FK to customer table: customer table is created by m241228_213000 which runs AFTER
        // this migration (21:30 > 17:05), so we can't add the FK here.
        // It is added by m241228_213000_add_customer_social_fk (see that migration).
    }

    public function safeDown()
    {
        // FK was not created in safeUp — nothing to drop here.
        $this->dropIndex('idx-customer_social_account-provider-provider_id', '{{%customer_social_account}}');
        $this->dropIndex('idx-customer_social_account-customer_id', '{{%customer_social_account}}');
        $this->dropTable('{{%customer_social_account}}');
    }
}
