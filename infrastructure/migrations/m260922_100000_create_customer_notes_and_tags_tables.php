<?php

use yii\db\Migration;

/**
 * CustomerController::actionAddNote()/actionUpdateTags() (backend/modules/admin/controllers/
 * CustomerController.php) читают/пишут в {{%customer_notes}} и {{%customer_tags}} напрямую
 * через Yii::$app->db->createCommand()->insert()/delete() — обе таблицы никогда не создавались
 * ни одной миграцией. Каждый вызов ловится try/catch внутри контроллера и тихо возвращает
 * success=false с текстом SQL-ошибки "Table ... doesn't exist" — 100% реальных обращений к
 * /admin/customer/{id}/add-note и /admin/customer/{id}/update-tags падали без видимого 500,
 * баг был скрыт catch-блоком. Найдено живым HTTP-прогоном CMP-417 (то же семейство багов,
 * что и CMP-410 catalog_inquiry — см. m260921_141500_create_catalog_inquiry_table.php).
 */
class m260922_100000_create_customer_notes_and_tags_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%customer_notes}}', true) === null) {
            $this->createTable('{{%customer_notes}}', [
                'id' => $this->primaryKey(),
                'customer_id' => $this->integer()->notNull(),
                'author_id' => $this->integer()->null(),
                'author_name' => $this->string(255)->null(),
                'text' => $this->text()->notNull(),
                'created_at' => $this->integer()->notNull(),
            ]);

            $this->createIndex('idx_customer_notes_customer', '{{%customer_notes}}', 'customer_id');

            $this->addForeignKey(
                'fk_customer_notes_customer',
                '{{%customer_notes}}',
                'customer_id',
                '{{%customer}}',
                'id',
                'CASCADE'
            );
        } else {
            echo "    > skip: {{%customer_notes}} already exists\n";
        }

        if ($this->db->getTableSchema('{{%customer_tags}}', true) === null) {
            $this->createTable('{{%customer_tags}}', [
                'id' => $this->primaryKey(),
                'customer_id' => $this->integer()->notNull(),
                'tag' => $this->string(100)->notNull(),
                'created_at' => $this->integer()->notNull(),
            ]);

            $this->createIndex('idx_customer_tags_customer', '{{%customer_tags}}', 'customer_id');

            $this->addForeignKey(
                'fk_customer_tags_customer',
                '{{%customer_tags}}',
                'customer_id',
                '{{%customer}}',
                'id',
                'CASCADE'
            );
        } else {
            echo "    > skip: {{%customer_tags}} already exists\n";
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%customer_tags}}');
        $this->dropTable('{{%customer_notes}}');
    }
}
