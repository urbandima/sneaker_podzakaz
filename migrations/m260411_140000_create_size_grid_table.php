<?php

use yii\db\Migration;

/**
 * Создает таблицу size_grid для управления размерными сетками товаров
 */
class m260411_140000_create_size_grid_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%size_grid}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Название размерной сетки'),
            'slug' => $this->string(255)->notNull()->unique()->comment('SEO-friendly URL'),
            'category_id' => $this->integer()->comment('Категория товаров'),
            'description' => $this->text()->comment('Описание'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активна ли сетка'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-size_grid-category_id', '{{%size_grid}}', 'category_id');
        $this->createIndex('idx-size_grid-is_active', '{{%size_grid}}', 'is_active');
        $this->createIndex('idx-size_grid-slug', '{{%size_grid}}', 'slug');

        // Создаем таблицу для размеров в сетке
        $this->createTable('{{%size_grid_item}}', [
            'id' => $this->primaryKey(),
            'size_grid_id' => $this->integer()->notNull()->comment('ID размерной сетки'),
            'name' => $this->string(50)->notNull()->comment('Название размера (например, 42, S, M)'),
            'code' => $this->string(50)->notNull()->comment('Код размера для Poizon'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-size_grid_item-size_grid_id', '{{%size_grid_item}}', 'size_grid_id');
        $this->addForeignKey('fk-size_grid_item-size_grid_id', '{{%size_grid_item}}', 'size_grid_id', '{{%size_grid}}', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-size_grid_item-size_grid_id', '{{%size_grid_item}}');
        $this->dropTable('{{%size_grid_item}}');
        $this->dropTable('{{%size_grid}}');
    }
}
