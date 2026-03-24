<?php

use yii\db\Migration;

/**
 * Создание таблицы уведомлений
 */
class m260325_000000_create_notification_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull()->comment('ID покупателя'),
            'type' => $this->string(50)->notNull()->comment('Тип уведомления'),
            'title' => $this->string(255)->notNull()->comment('Заголовок'),
            'message' => $this->text()->notNull()->comment('Сообщение'),
            'data' => $this->text()->comment('Дополнительные данные (JSON)'),
            'is_read' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Прочитано'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'read_at' => $this->timestamp()->null()->comment('Дата прочтения'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Индексы
        $this->createIndex('idx_notification_customer', '{{%notification}}', 'customer_id');
        $this->createIndex('idx_notification_type', '{{%notification}}', 'type');
        $this->createIndex('idx_notification_is_read', '{{%notification}}', 'is_read');
        $this->createIndex('idx_notification_created', '{{%notification}}', 'created_at');

        // Внешний ключ
        try {
            $this->addForeignKey(
                'fk_notification_customer',
                '{{%notification}}',
                'customer_id',
                '{{%customer}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        } catch (\Exception $e) {
            echo "Warning: Could not add foreign key for customer_id\n";
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%notification}}');
    }
}
