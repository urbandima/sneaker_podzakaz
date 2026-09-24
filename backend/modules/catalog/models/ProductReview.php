<?php

/**
 * ProductReview — Отзыв покупателя о товаре
 *
 * НАЗНАЧЕНИЕ:
 * Отзывы покупателей о товарах: оценка, текст, модерация.
 *
 * СВЯЗАННЫЕ ТАБЛИЦЫ:
 * - product_review.user_id хранит id покупателя (customer), не админского пользователя —
 *   имя поля исторически совпало с колонкой user_id, но по смыслу это customer_id.
 */

namespace app\backend\modules\catalog\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\backend\modules\account\models\Customer;

/**
 * @property int $id
 * @property int $product_id
 * @property int $user_id Id покупателя (Customer), не админского пользователя
 * @property string $name
 * @property string|null $email
 * @property int $rating
 * @property string $comment
 * @property bool|null $is_verified
 * @property bool|null $is_approved
 * @property bool $is_published
 * @property string|null $title
 * @property string|null $pros
 * @property string|null $cons
 * @property string|null $photos_json
 * @property bool|null $is_featured
 * @property string|null $admin_response
 * @property string|null $admin_response_at
 * @property int|null $helpful_count
 * @property string $status
 * @property int|null $moderated_by
 * @property int|null $moderated_at
 * @property string|null $moderation_note
 * @property int|null $spam_score
 * @property string|null $sentiment_score
 * @property int $created_at
 *
 * @property Product $product
 * @property Customer $customer
 */
class ProductReview extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_review}}';
    }

    public function behaviors()
    {
        return [
            [
                // product_review не имеет колонки updated_at (только created_at,
                // int) — дефолтный TimestampBehavior пытается писать оба поля и
                // роняет ЛЮБОЙ save() (insert и update) с
                // UnknownPropertyException: Setting unknown property ...::updated_at.
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['product_id', 'user_id', 'rating', 'comment', 'name'], 'required'],
            [['product_id', 'user_id', 'rating', 'moderated_by', 'helpful_count', 'spam_score'], 'integer'],
            [['comment', 'pros', 'cons', 'moderation_note', 'admin_response'], 'string'],
            [['name', 'email', 'title'], 'string', 'max' => 255],
            ['email', 'email'],
            ['rating', 'in', 'range' => [1, 2, 3, 4, 5]],
            [['is_verified', 'is_approved', 'is_published', 'is_featured'], 'boolean'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => 'pending'],
            [['admin_response_at', 'photos_json', 'sentiment_score'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'user_id' => 'Покупатель',
            'name' => 'Имя',
            'email' => 'Email',
            'rating' => 'Оценка',
            'comment' => 'Текст отзыва',
            'pros' => 'Достоинства',
            'cons' => 'Недостатки',
            'title' => 'Заголовок',
            'status' => 'Статус',
            'is_published' => 'Опубликован',
            'is_approved' => 'Одобрен',
            'is_verified' => 'Подтверждённая покупка',
            'helpful_count' => 'Полезность',
        ];
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'user_id']);
    }

    /**
     * Имя автора отзыва для админки.
     *
     * CMP-457: review/index.php вызывал getDisplayName(), которого никогда не
     * было на модели — 500 на всей странице модерации отзывов (не только на
     * кнопке «Отклонить»), обнаружено при живой проверке этой карточки.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->name !== null && $this->name !== '') {
            return $this->name;
        }

        return $this->customer ? $this->customer->getFullName() : 'Аноним';
    }

    /**
     * Опубликовать отзыв.
     *
     * Зеркалит логику actionModerate(action=publish): выставляет is_published,
     * фронт (ReviewController::actionList) фильтрует именно по этому полю, не по status.
     *
     * @return bool
     */
    public function publish(): bool
    {
        $this->is_published = true;
        return $this->save(false, ['is_published']);
    }

    /**
     * Снять отзыв с публикации.
     *
     * Отличие от reject(): unpublish — обратимое скрытие уже опубликованного
     * отзыва (status не трогается), reject — терминальное решение модератора
     * по отзыву, ещё не опубликованному (is_published=false + status=rejected).
     *
     * @return bool
     */
    public function unpublish(): bool
    {
        $this->is_published = false;
        return $this->save(false, ['is_published']);
    }

    /**
     * Отклонить отзыв при модерации.
     *
     * @return bool
     */
    public function reject(): bool
    {
        $this->is_published = false;
        $this->status = 'rejected';
        return $this->save(false, ['is_published', 'status']);
    }

    /**
     * Сохранить ответ администрации на отзыв.
     *
     * @param string|null $response
     * @return bool
     */
    public function addAdminResponse(?string $response): bool
    {
        $this->admin_response = $response;
        $this->admin_response_at = date('Y-m-d H:i:s');
        return $this->save(false, ['admin_response', 'admin_response_at']);
    }
}
