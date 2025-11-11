<?php

use yii\db\Migration;

/**
 * Добавление версии и истории изменений характеристик
 */
class m251109_142500_add_characteristic_history extends Migration
{
    public function safeUp()
    {
        // Дополнительные поля для characteristic
        $this->addColumn('{{%characteristic}}', 'version', $this->integer()->notNull()->defaultValue(1)->after('is_active'));
        $this->addColumn('{{%characteristic}}', 'updated_by', $this->integer()->null()->after('version'));

        // Таблица истории изменений
        $this->createTable('{{%characteristic_history}}', [
            'id' => $this->primaryKey(),
            'characteristic_id' => $this->integer()->notNull(),
            'field_name' => $this->string(100)->notNull(),
            'old_value' => $this->text()->null(),
            'new_value' => $this->text()->null(),
            'changed_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_characteristic_history_char', '{{%characteristic_history}}', 'characteristic_id');
        $this->createIndex('idx_characteristic_history_field', '{{%characteristic_history}}', 'field_name');

        $this->addForeignKey(
            'fk_characteristic_history_characteristic',
            '{{%characteristic_history}}',
            'characteristic_id',
            '{{%characteristic}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_characteristic_history_user',
            '{{%characteristic_history}}',
            'changed_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_characteristic_history_user', '{{%characteristic_history}}');
        $this->dropForeignKey('fk_characteristic_history_characteristic', '{{%characteristic_history}}');
        $this->dropTable('{{%characteristic_history}}');
        $this->dropColumn('{{%characteristic}}', 'updated_by');
        $this->dropColumn('{{%characteristic}}', 'version');
    }
}
