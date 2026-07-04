<?php

use yii\db\Migration;

/**
 * Добавляет недостающие столбцы в таблицу import_task
 */
class m260411_150000_add_missing_columns_to_import_task extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Добавляем недостающие столбцы
        $this->addColumn('{{%import_task}}', 'total_products', $this->integer()->defaultValue(0)->comment('Всего товаров в задаче'));
        $this->addColumn('{{%import_task}}', 'processed_products', $this->integer()->defaultValue(0)->comment('Обработано товаров'));
        $this->addColumn('{{%import_task}}', 'duplicate_count', $this->integer()->defaultValue(0)->comment('Дубликатов найдено'));
        $this->addColumn('{{%import_task}}', 'finished_at', $this->integer()->comment('Время завершения'));
        $this->addColumn('{{%import_task}}', 'duration_seconds', $this->integer()->comment('Длительность в секундах'));
        $this->addColumn('{{%import_task}}', 'config', $this->text()->comment('JSON с параметрами запуска'));
        $this->addColumn('{{%import_task}}', 'created_by', $this->integer()->comment('Кто запустил'));
        
        // Переименовываем completed_at в finished_at для соответствия модели
        // Сначала проверяем, существует ли completed_at
        $columnExists = Yii::$app->db->schema->getTableSchema('{{%import_task}}')->getColumn('completed_at');
        if ($columnExists) {
            $this->renameColumn('{{%import_task}}', 'completed_at', 'finished_at_old');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%import_task}}', 'total_products');
        $this->dropColumn('{{%import_task}}', 'processed_products');
        $this->dropColumn('{{%import_task}}', 'duplicate_count');
        $this->dropColumn('{{%import_task}}', 'finished_at');
        $this->dropColumn('{{%import_task}}', 'duration_seconds');
        $this->dropColumn('{{%import_task}}', 'config');
        $this->dropColumn('{{%import_task}}', 'created_by');
        
        // Возвращаем обратно
        $columnExists = Yii::$app->db->schema->getTableSchema('{{%import_task}}')->getColumn('finished_at_old');
        if ($columnExists) {
            $this->renameColumn('{{%import_task}}', 'finished_at_old', 'completed_at');
        }
    }
}
