<?php
/**
 * Миграция для создания таблицы sidebar_menu
 * Управление блоками бокового меню в шапке
 */

use yii\db\Migration;

class m260401_100000_create_sidebar_menu_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%sidebar_menu}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null()->comment('Родительский элемент'),
            'title' => $this->string(255)->notNull()->comment('Название пункта меню'),
            'url' => $this->string(500)->null()->comment('URL ссылка'),
            'route' => $this->string(255)->null()->comment('Yii route (например: catalog/index)'),
            'params' => $this->json()->null()->comment('Параметры route'),
            'icon' => $this->string(100)->null()->comment('CSS класс иконки (bi bi-house)'),
            'image' => $this->string(500)->null()->comment('Изображение для баннера'),
            'type' => $this->string(50)->notNull()->defaultValue('link')->comment('Тип: link, banner, divider, header'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0)->comment('Порядок сортировки'),
            'is_active' => $this->boolean()->notNull()->defaultValue(1)->comment('Активен'),
            'is_visible' => $this->boolean()->notNull()->defaultValue(1)->comment('Видимый'),
            'target_blank' => $this->boolean()->notNull()->defaultValue(0)->comment('Открывать в новой вкладке'),
            'css_class' => $this->string(100)->null()->comment('Дополнительный CSS класс'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Индексы
        $this->createIndex('idx-sidebar_menu-parent_id', '{{%sidebar_menu}}', 'parent_id');
        $this->createIndex('idx-sidebar_menu-sort_order', '{{%sidebar_menu}}', 'sort_order');
        $this->createIndex('idx-sidebar_menu-is_active', '{{%sidebar_menu}}', 'is_active');
        $this->createIndex('idx-sidebar_menu-type', '{{%sidebar_menu}}', 'type');

        // Внешний ключ
        $this->addForeignKey(
            'fk-sidebar_menu-parent_id',
            '{{%sidebar_menu}}',
            'parent_id',
            '{{%sidebar_menu}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Демо данные
        $this->batchInsert('{{%sidebar_menu}}', [
            'title', 'url', 'icon', 'type', 'sort_order', 'is_active'
        ], [
            ['Каталог', '/catalog', 'bi bi-grid', 'link', 10, 1],
            ['Бренды', '/brands', 'bi bi-tag', 'link', 20, 1],
            ['Новинки', '/catalog?sort=new', 'bi bi-stars', 'link', 30, 1],
            ['Скидки', '/sale', 'bi bi-percent', 'link', 40, 1],
            ['Разделитель', null, null, 'divider', 50, 1],
            ['Информация', null, null, 'header', 60, 1],
            ['О нас', '/about', 'bi bi-info-circle', 'link', 70, 1],
            ['Контакты', '/contacts', 'bi bi-telephone', 'link', 80, 1],
            ['Доставка', '/delivery', 'bi bi-truck', 'link', 90, 1],
            ['Оплата', '/payment', 'bi bi-credit-card', 'link', 100, 1],
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-sidebar_menu-parent_id', '{{%sidebar_menu}}');
        $this->dropTable('{{%sidebar_menu}}');
    }
}
