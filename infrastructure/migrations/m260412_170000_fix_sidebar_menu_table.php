<?php

use yii\db\Migration;

/**
 * Исправление таблицы sidebar_menu - добавление недостающих колонок
 */
class m260412_170000_fix_sidebar_menu_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Проверяем существование колонки title
        $tableSchema = $this->db->getTableSchema('{{%sidebar_menu}}');
        
        if ($tableSchema) {
            // Добавляем колонки если их нет
            if (!$tableSchema->getColumn('parent_id')) {
                $this->addColumn('{{%sidebar_menu}}', 'parent_id', $this->integer()->null()->comment('Родительский элемент'));
            }
            if (!$tableSchema->getColumn('title')) {
                $this->addColumn('{{%sidebar_menu}}', 'title', $this->string(255)->notNull()->comment('Название пункта меню'));
            }
            if (!$tableSchema->getColumn('url')) {
                $this->addColumn('{{%sidebar_menu}}', 'url', $this->string(500)->null()->comment('URL ссылка'));
            }
            if (!$tableSchema->getColumn('route')) {
                $this->addColumn('{{%sidebar_menu}}', 'route', $this->string(255)->null()->comment('Yii route (например: catalog/index)'));
            }
            if (!$tableSchema->getColumn('params')) {
                $this->addColumn('{{%sidebar_menu}}', 'params', $this->text()->null()->comment('Параметры маршрута (JSON)'));
            }
            if (!$tableSchema->getColumn('icon')) {
                $this->addColumn('{{%sidebar_menu}}', 'icon', $this->string(255)->null()->comment('Иконка (например: bi bi-grid)'));
            }
            if (!$tableSchema->getColumn('image')) {
                $this->addColumn('{{%sidebar_menu}}', 'image', $this->string(255)->null()->comment('Изображение баннера'));
            }
            if (!$tableSchema->getColumn('type')) {
                $this->addColumn('{{%sidebar_menu}}', 'type', $this->string(50)->notNull()->defaultValue('link')->comment('Тип элемента'));
            }
            if (!$tableSchema->getColumn('sort_order')) {
                $this->addColumn('{{%sidebar_menu}}', 'sort_order', $this->integer()->notNull()->defaultValue(0)->comment('Порядок сортировки'));
            }
            if (!$tableSchema->getColumn('is_active')) {
                $this->addColumn('{{%sidebar_menu}}', 'is_active', $this->boolean()->notNull()->defaultValue(1)->comment('Активен'));
            }
            if (!$tableSchema->getColumn('is_visible')) {
                $this->addColumn('{{%sidebar_menu}}', 'is_visible', $this->boolean()->notNull()->defaultValue(1)->comment('Видимый'));
            }
            if (!$tableSchema->getColumn('target_blank')) {
                $this->addColumn('{{%sidebar_menu}}', 'target_blank', $this->boolean()->notNull()->defaultValue(0)->comment('Открывать в новой вкладке'));
            }
            if (!$tableSchema->getColumn('css_class')) {
                $this->addColumn('{{%sidebar_menu}}', 'css_class', $this->string(255)->null()->comment('CSS класс'));
            }
            if (!$tableSchema->getColumn('created_at')) {
                $this->addColumn('{{%sidebar_menu}}', 'created_at', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'));
            }
            if (!$tableSchema->getColumn('updated_at')) {
                $this->addColumn('{{%sidebar_menu}}', 'updated_at', $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Дата обновления'));
            }

            // Создаём индексы если их нет
            if (!$this->db->getTableSchema('{{%sidebar_menu}}')->getColumn('parent_id')) {
                $this->createIndex('idx-sidebar_menu-parent_id', '{{%sidebar_menu}}', 'parent_id');
            }
            $this->createIndex('idx-sidebar_menu-sort_order', '{{%sidebar_menu}}', 'sort_order');
            $this->createIndex('idx-sidebar_menu-is_active', '{{%sidebar_menu}}', 'is_active');
            $this->createIndex('idx-sidebar_menu-type', '{{%sidebar_menu}}', 'type');

            // Создаём внешний ключ если его нет
            try {
                $this->addForeignKey(
                    'fk-sidebar_menu-parent_id',
                    '{{%sidebar_menu}}',
                    'parent_id',
                    '{{%sidebar_menu}}',
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            } catch (\Exception $e) {
                // FK может уже существовать
            }

            // Добавляем демо данные если таблица пустая
            $count = (new \yii\db\Query())->from('{{%sidebar_menu}}')->count();
            if ($count == 0) {
                $this->batchInsert('{{%sidebar_menu}}', [
                    'title', 'url', 'icon', 'type', 'sort_order', 'is_active'
                ], [
                    ['Каталог', '/catalog', 'bi bi-grid', 'link', 10, 1],
                    ['Бренды', '/brands', 'bi bi-tag', 'link', 20, 1],
                    ['Новинки', '/catalog?sort=new', 'bi bi-stars', 'link', 30, 1],
                    ['Распродажа', '/catalog?filter=sale', 'bi bi-percent', 'link', 40, 1],
                ]);
            }
        }
    }

    public function safeDown()
    {
        // Не удаляем колонки для безопасности
        return false;
    }
}
