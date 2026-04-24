<?php

namespace app\backend\modules\automation\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property string $template
 * @property string $variables  JSON
 * @property int    $is_active
 * @property int    $created_at
 * @property int    $updated_at
 */
class SmsTemplate extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%sms_template}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['code', 'name', 'template'], 'required'],
            [['code'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 255],
            [['template', 'variables'], 'string'],
            [['is_active'], 'boolean'],
            [['code'], 'unique'],
        ];
    }

    /** Render template with context variables */
    public function render(array $context): string
    {
        $text = $this->template;
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{' . $key . '}', (string)$value, $text);
            }
        }
        return $text;
    }

    public static function findByCode(string $code): ?self
    {
        return static::find()->where(['code' => $code, 'is_active' => 1])->one();
    }
}
