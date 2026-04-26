<?php

namespace app\backend\modules\procurement\models;

use Yii;
use yii\db\ActiveRecord;

class ReceivingDocument extends ActiveRecord
{
    const TYPE_INVOICE      = 'invoice';
    const TYPE_CUSTOMS      = 'customs_declaration';
    const TYPE_PHOTO        = 'photo';
    const TYPE_OTHER        = 'other';

    public static function tableName(): string
    {
        return '{{%receiving_document}}';
    }

    public function rules(): array
    {
        return [
            [['receiving_id', 'file_path', 'original_name'], 'required'],
            [['receiving_id', 'size_bytes', 'uploaded_by', 'uploaded_at'], 'integer'],
            [['type'], 'in', 'range' => array_keys(self::getTypes())],
            [['file_path'], 'string', 'max' => 512],
            [['original_name', 'mime_type'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'            => 'ID',
            'receiving_id'  => 'Приёмка',
            'type'          => 'Тип документа',
            'file_path'     => 'Путь к файлу',
            'original_name' => 'Исходное имя',
            'mime_type'     => 'MIME тип',
            'size_bytes'    => 'Размер (байт)',
            'uploaded_by'   => 'Загрузил',
            'uploaded_at'   => 'Дата загрузки',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_INVOICE  => 'Инвойс',
            self::TYPE_CUSTOMS  => 'Таможенная декларация',
            self::TYPE_PHOTO    => 'Фотография',
            self::TYPE_OTHER    => 'Прочее',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getReceiving()
    {
        return $this->hasOne(Receiving::class, ['id' => 'receiving_id']);
    }

    public function getPublicUrl(): string
    {
        return Yii::$app->request->baseUrl . '/' . ltrim($this->file_path, '/');
    }

    public function getFormattedSize(): string
    {
        $bytes = (int)$this->size_bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
