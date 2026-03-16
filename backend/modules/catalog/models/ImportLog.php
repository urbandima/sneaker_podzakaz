<?php

/**
 * ImportLog — Временная заглушка для модели лога импорта
 * 
 * НАЗНАЧЕНИЕ:
 * Временная реализация для предотвращения ошибок.
 * TODO: Создать полную реализацию модели.
 */
namespace app\backend\modules\catalog\models;

use yii\db\ActiveRecord;

class ImportLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%import_log}}';
    }
    
    public function rules()
    {
        return [
            [['batch_id', 'message'], 'required'],
            [['batch_id'], 'integer'],
            [['message'], 'string'],
            [['level'], 'string', 'max' => 20],
            [['created_at'], 'safe'],
        ];
    }
}
