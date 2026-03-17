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

        // Skip foreign key for now - will be added after customer table is created
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-customer_social_account-customer_id', '{{%customer_social_account}}');
        $this->dropIndex('idx-customer_social_account-provider-provider_id', '{{%customer_social_account}}');
        $this->dropIndex('idx-customer_social_account-customer_id', '{{%customer_social_account}}');
        $this->dropTable('{{%customer_social_account}}');
    }
}
