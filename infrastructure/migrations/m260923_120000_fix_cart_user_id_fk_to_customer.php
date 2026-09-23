<?php

use yii\db\Migration;

/**
 * CMP-435: cart.user_id FK ссылался на {{%user}}, а код пишет customer.id.
 *
 * Стратегия:
 * - Переименовывать колонку не нужно — имя user_id оставляем (риск rename > пользы).
 * - Строки cart, где user_id уже заполнен значениями из customer, останутся нетронутыми:
 *   old FK нарушался именно потому, что customer.id не совпадал с user.id.
 *   После смены FK эти строки валидны.
 * - Строки с user_id = NULL (гости) не затронуты — FK допускает NULL.
 * - safeDown() возвращает FK к user, что восстанавливает исходное (ошибочное) состояние.
 */
class m260923_120000_fix_cart_user_id_fk_to_customer extends Migration
{
    public function safeUp()
    {
        // Удаляем ошибочный FK → user
        $this->dropForeignKey('fk-cart-user_id', '{{%cart}}');

        // Добавляем правильный FK → customer
        $this->addForeignKey(
            'fk-cart-user_id',
            '{{%cart}}',
            'user_id',
            '{{%customer}}',
            'id',
            'CASCADE'
        );

        echo "✓ cart.user_id FK теперь ссылается на customer (CMP-435)\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-cart-user_id', '{{%cart}}');

        $this->addForeignKey(
            'fk-cart-user_id',
            '{{%cart}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }
}
