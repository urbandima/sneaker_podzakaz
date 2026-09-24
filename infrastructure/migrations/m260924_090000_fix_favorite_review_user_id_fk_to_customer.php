<?php

use yii\db\Migration;

/**
 * CMP-446: тот же класс бага, что CMP-435 (cart.user_id).
 *
 * product_favorite.user_id и product_review.user_id FK ссылались на {{%user}}
 * (таблица сотрудников), а FavoriteController/ReviewController пишут туда
 * Customer::getCurrentCustomerId() / session('customer_id') — то есть customer.id.
 * У залогиненного покупателя, чей id отсутствует в user, MySQL отдавал
 * SQLSTATE[23000] 1452 на любой INSERT в эти таблицы.
 *
 * Колонки не переименовываем (тот же принцип, что в CMP-435): риск rename
 * выше пользы, единообразие с cart важнее красоты имени.
 *
 * product_favorite: ON DELETE CASCADE, как было.
 * product_review: ON DELETE SET NULL, как было (отзыв не должен исчезать
 * при удалении покупателя).
 */
class m260924_090000_fix_favorite_review_user_id_fk_to_customer extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('fk-favorite-user_id', '{{%product_favorite}}');
        $this->addForeignKey(
            'fk-favorite-user_id',
            '{{%product_favorite}}',
            'user_id',
            '{{%customer}}',
            'id',
            'CASCADE'
        );

        $this->dropForeignKey('fk-review-user_id', '{{%product_review}}');
        $this->addForeignKey(
            'fk-review-user_id',
            '{{%product_review}}',
            'user_id',
            '{{%customer}}',
            'id',
            'SET NULL'
        );

        echo "✓ product_favorite.user_id и product_review.user_id теперь ссылаются на customer (CMP-446)\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-review-user_id', '{{%product_review}}');
        $this->addForeignKey(
            'fk-review-user_id',
            '{{%product_review}}',
            'user_id',
            '{{%user}}',
            'id',
            'SET NULL'
        );

        $this->dropForeignKey('fk-favorite-user_id', '{{%product_favorite}}');
        $this->addForeignKey(
            'fk-favorite-user_id',
            '{{%product_favorite}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }
}
