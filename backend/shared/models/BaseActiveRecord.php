<?php

namespace app\backend\shared\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Базовый ActiveRecord для всех моделей проекта.
 * Предоставляет: TimestampBehavior, общие метки атрибутов, findOrFail().
 */
abstract class BaseActiveRecord extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
            'is_active'  => 'Активен',
            'status'     => 'Статус',
        ];
    }

    /**
     * Найти запись или выбросить 404.
     */
    public static function findOrFail(int $id): static
    {
        $model = static::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Запись не найдена.');
        }
        return $model;
    }
}
