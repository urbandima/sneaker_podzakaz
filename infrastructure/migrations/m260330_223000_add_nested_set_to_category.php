<?php

/**
 * Миграция: Nested Set Model для категорий
 * 
 * Рекомендация #25: Category Tree
 */
class m260330_223000_add_nested_set_to_category extends \yii\db\Migration
{
    public function safeUp()
    {
        // Добавляем поля Nested Set
        $this->addColumn('{{%category}}', 'tree', $this->integer()->null());
        $this->addColumn('{{%category}}', 'lft', $this->integer()->notNull());
        $this->addColumn('{{%category}}', 'rgt', $this->integer()->notNull());
        $this->addColumn('{{%category}}', 'depth', $this->integer()->notNull()->defaultValue(0));
        
        // Индексы для быстрого поиска
        $this->createIndex('idx-category-tree', '{{%category}}', 'tree');
        $this->createIndex('idx-category-lft', '{{%category}}', 'lft');
        $this->createIndex('idx-category-rgt', '{{%category}}', 'rgt');
        $this->createIndex('idx-category-lft-rgt', '{{%category}}', ['lft', 'rgt']);
        
        // Инициализация корневых категорий
        $this->initializeTree();
        
        echo "Nested Set Model добавлена в category\n";
    }
    
    public function safeDown()
    {
        $this->dropColumn('{{%category}}', 'tree');
        $this->dropColumn('{{%category}}', 'lft');
        $this->dropColumn('{{%category}}', 'rgt');
        $this->dropColumn('{{%category}}', 'depth');
    }
    
    /**
     * Инициализация дерева
     */
    private function initializeTree(): void
    {
        $categories = (new \yii\db\Query())
            ->from('{{%category}}')
            ->orderBy('id')
            ->all();
        
        // Простая инициализация: все как корневые
        $counter = 1;
        foreach ($categories as $category) {
            $this->update('{{%category}}', [
                'tree' => $category['id'],
                'lft' => $counter++,
                'rgt' => $counter++,
                'depth' => 0,
            ], ['id' => $category['id']]);
        }
    }
}
