<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%import_source}}`.
 */
class m260329_110000_create_import_source_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%import_source}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->comment('Название источника'),
            'type' => $this->string()->notNull()->comment('Тип: xml, json, csv, excel'),
            'url' => $this->string()->comment('URL источника'),
            'config' => $this->text()->comment('JSON конфигурация'),
            'is_active' => $this->boolean()->defaultValue(true)->comment('Активность'),
            'last_import_at' => $this->integer()->comment('Время последнего импорта'),
            'total_imported' => $this->integer()->defaultValue(0)->comment('Всего импортировано'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-import_source-is_active',
            '{{%import_source}}',
            'is_active'
        );

        $this->createIndex(
            'idx-import_source-type',
            '{{%import_source}}',
            'type'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%import_source}}');
    }
}
