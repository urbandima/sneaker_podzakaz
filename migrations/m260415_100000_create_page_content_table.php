<?php

use yii\db\Migration;

/**
 * Таблица page_content — редактируемые статические страницы сайта
 * Хранит контент для страниц /privacy, /payment-terms, /return-policy, /contacts, /sale и т.д.
 */
class m260415_100000_create_page_content_table extends Migration
{
    public function up()
    {
        $this->createTable('{{%page_content}}', [
            'id'         => $this->primaryKey(),
            'slug'       => $this->string(100)->notNull()->unique()->comment('URL-слаг страницы'),
            'title'      => $this->string(255)->notNull()->comment('Заголовок страницы'),
            'content'    => $this->text()->comment('HTML-контент страницы'),
            'meta_desc'  => $this->string(500)->comment('Meta-описание'),
            'is_active'  => $this->boolean()->defaultValue(1),
            'updated_by' => $this->integer()->comment('ID пользователя, сохранившего'),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        // Базовые страницы
        $now = time();
        $this->batchInsert('{{%page_content}}', ['slug', 'title', 'is_active', 'created_at', 'updated_at'], [
            ['privacy',       'Политика конфиденциальности', 1, $now, $now],
            ['payment-terms', 'Способы оплаты',              1, $now, $now],
            ['return-policy', 'Возврат и обмен',             1, $now, $now],
            ['contacts',      'Контакты',                    1, $now, $now],
            ['sale',          'Скидки и акции',              1, $now, $now],
            ['about',         'О компании',                  1, $now, $now],
            ['delivery-terms','Условия доставки',            1, $now, $now],
        ]);
    }

    public function down()
    {
        $this->dropTable('{{%page_content}}');
    }
}
