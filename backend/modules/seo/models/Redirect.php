<?php

/**
 * Redirect — Модель редиректов
 * 
 * Таблица: redirect
 */
namespace app\backend\modules\seo\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $from_url
 * @property string $to_url
 * @property int $type (301, 302, 404)
 * @property bool $is_active
 * @property int $hit_count
 * @property string|null $last_hit_at
 * @property int $created_at
 * @property int $updated_at
 */
class Redirect extends ActiveRecord
{
    const TYPE_301 = 301; // Permanent
    const TYPE_302 = 302; // Temporary
    const TYPE_404 = 404; // Not Found (без редиректа, просто логирование)

    public static function tableName()
    {
        return '{{%redirect}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['from_url', 'to_url'], 'required'],
            [['from_url', 'to_url'], 'string', 'max' => 500],
            [['type'], 'in', 'range' => [self::TYPE_301, self::TYPE_302, self::TYPE_404]],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['type'], 'default', 'value' => self::TYPE_301],
            [['hit_count'], 'integer'],
            [['hit_count'], 'default', 'value' => 0],
            [['from_url'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'from_url' => 'Откуда (URL)',
            'to_url' => 'Куда (URL)',
            'type' => 'Тип',
            'is_active' => 'Активен',
            'hit_count' => 'Переходов',
            'last_hit_at' => 'Последний переход',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getTypeLabel()
    {
        $types = [
            self::TYPE_301 => '301 (Постоянный)',
            self::TYPE_302 => '302 (Временный)',
            self::TYPE_404 => '404 (Не найдено)',
        ];
        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Найти и применить редирект
     * @param string $url Текущий URL
     * @return array|null [url, type] или null
     */
    public static function findAndApply($url)
    {
        $redirect = self::findOne([
            'from_url' => $url,
            'is_active' => true,
        ]);

        if ($redirect) {
            // Увеличиваем счётчик
            $redirect->hit_count++;
            $redirect->last_hit_at = date('Y-m-d H:i:s');
            $redirect->save(false, ['hit_count', 'last_hit_at']);

            return [
                'url' => $redirect->to_url,
                'type' => $redirect->type,
            ];
        }

        return null;
    }
}
