<?php

namespace app\backend\modules\admin\models;

use yii\base\Model;
use yii\web\UploadedFile;

/**
 * ImportUploadForm — Форма загрузки файла для импорта
 */
class ImportUploadForm extends Model
{
    public $file;
    public $source_id;
    public $format;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['file', 'source_id', 'format'], 'required'],
            [['source_id'], 'integer'],
            [['format'], 'string', 'max' => 10],
            [['format'], 'in', 'range' => ['json', 'csv', 'xlsx']],
            [['file'], 'file', 
                'skipOnEmpty' => false,
                'extensions' => ['json', 'csv', 'xlsx'],
                'maxSize' => 10 * 1024 * 1024, // 10MB
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'file' => 'Файл',
            'source_id' => 'Источник импорта',
            'format' => 'Формат файла',
        ];
    }

    /**
     * Загрузить файл
     * @return bool
     */
    public function upload()
    {
        if ($this->validate()) {
            $this->file = UploadedFile::getInstance($this, 'file');
            return true;
        }
        return false;
    }
}
