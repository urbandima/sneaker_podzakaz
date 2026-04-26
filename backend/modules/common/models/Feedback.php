<?php

namespace app\backend\modules\common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Feedback from customers to director
 *
 * @property int    $id
 * @property int    $customer_id
 * @property string $customer_name
 * @property string $customer_email
 * @property int    $rating
 * @property string $message
 * @property int    $is_read
 * @property string $status     new|read|replied
 * @property string $reply_text
 * @property int    $replied_at
 * @property int    $created_at
 */
class Feedback extends ActiveRecord
{
    const STATUS_NEW     = 'new';
    const STATUS_READ    = 'read';
    const STATUS_REPLIED = 'replied';

    public static function tableName(): string
    {
        return 'feedback';
    }

    public function rules(): array
    {
        return [
            [['message'], 'required'],
            [['rating'], 'integer', 'min' => 1, 'max' => 5],
            [['customer_name', 'customer_email'], 'string', 'max' => 200],
            [['message', 'reply_text'], 'string', 'max' => 2000],
            [['customer_id', 'is_read', 'created_at', 'replied_at'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_READ, self::STATUS_REPLIED]],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->created_at = time();
            if (!$this->status) {
                $this->status = self::STATUS_NEW;
            }
        }
        return parent::beforeSave($insert);
    }

    public static function getUnreadCount(): int
    {
        return (int) static::find()->where(['status' => self::STATUS_NEW])->count();
    }

    public function markRead(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->status  = self::STATUS_READ;
            $this->is_read = 1;
            $this->save(false);
        }
    }
}
