<?php

use yii\db\Migration;

/**
 * Создание таблиц для стилей и технологий (many-to-many)
 */
class m250102_000002_create_style_and_technology_tables extends Migration
{
    public function safeUp()
    {
        // Таблица стилей
        $this->createTable('{{%style}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        // Таблица связи товар-стиль
        $this->createTable('{{%product_style}}', [
            'product_id' => $this->integer()->notNull(),
            'style_id' => $this->integer()->notNull(),
            'PRIMARY KEY(product_id, style_id)',
        ]);
        
        // Таблица технологий
        $this->createTable('{{%technology}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'brand_id' => $this->integer(), // NULL = общая технология
            'created_at' => $this->integer()->notNull(),
        ]);
        
        // Таблица связи товар-технология
        $this->createTable('{{%product_technology}}', [
            'product_id' => $this->integer()->notNull(),
            'technology_id' => $this->integer()->notNull(),
            'PRIMARY KEY(product_id, technology_id)',
        ]);
        
        // Foreign keys
        $this->addForeignKey(
            'fk-product_style-product_id',
            '{{%product_style}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-product_style-style_id',
            '{{%product_style}}',
            'style_id',
            '{{%style}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-product_technology-product_id',
            '{{%product_technology}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-product_technology-technology_id',
            '{{%product_technology}}',
            'technology_id',
            '{{%technology}}',
            'id',
            'CASCADE'
        );
        
        // Добавляем тестовые данные
        $this->batchInsert('{{%style}}', ['name', 'slug', 'created_at'], [
            ['Спортивный', 'sport', time()],
            ['Повседневный', 'casual', time()],
            ['Уличный', 'street', time()],
            ['Классический', 'classic', time()],
            ['Для бега', 'running', time()],
            ['Баскетбольный', 'basketball', time()],
            ['Скейтбординг', 'skate', time()],
        ]);
        
        $this->batchInsert('{{%technology}}', ['name', 'slug', 'brand_id', 'created_at'], [
            ['Nike Air', 'air', null, time()],
            ['Adidas Boost', 'boost', null, time()],
            ['Gore-Tex', 'gore_tex', null, time()],
            ['Nike Zoom', 'zoom', null, time()],
            ['Nike React', 'react', null, time()],
        ]);
        
        echo "✓ Созданы таблицы стилей и технологий\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-product_technology-technology_id', '{{%product_technology}}');
        $this->dropForeignKey('fk-product_technology-product_id', '{{%product_technology}}');
        $this->dropForeignKey('fk-product_style-style_id', '{{%product_style}}');
        $this->dropForeignKey('fk-product_style-product_id', '{{%product_style}}');
        
        $this->dropTable('{{%product_technology}}');
        $this->dropTable('{{%technology}}');
        $this->dropTable('{{%product_style}}');
        $this->dropTable('{{%style}}');
    }
}
