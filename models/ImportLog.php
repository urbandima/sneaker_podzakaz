<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ImportLog (Лог импорта)
 *
 * @property int $id
 * @property int $batch_id
 * @property int $product_id
 * @property string $action
 * @property string $level
 * @property string $sku
 * @property string $poizon_id
 * @property string $product_name
 * @property string $message
 * @property string $data
 * @property string $error_details
 * @property string $created_at
 * 
 * @property ImportBatch $batch
 * @property Product $product
 */
class ImportLog extends ActiveRecord
{
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_ERROR = 'error';

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    public static function tableName()
    {
        return '{{%import_log}}';
    }

    public function rules()
    {
        return [
            [['batch_id', 'action', 'level'], 'required'],
            [['batch_id', 'product_id'], 'integer'],
            [['action'], 'in', 'range' => [self::ACTION_CREATED, self::ACTION_UPDATED, self::ACTION_SKIPPED, self::ACTION_ERROR]],
            [['level'], 'in', 'range' => [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR]],
            [['sku', 'poizon_id', 'product_name'], 'string'],
            [['message', 'data', 'error_details'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'batch_id' => 'Батч',
            'product_id' => 'Товар',
            'action' => 'Действие',
            'level' => 'Уровень',
            'sku' => 'SKU',
            'poizon_id' => 'Poizon ID',
            'product_name' => 'Название',
            'message' => 'Сообщение',
            'created_at' => 'Время',
        ];
    }

    /**
     * Батч импорта
     */
    public function getBatch()
    {
        return $this->hasOne(ImportBatch::class, ['id' => 'batch_id']);
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Получить label действия
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_CREATED => 'Создан',
            self::ACTION_UPDATED => 'Обновлен',
            self::ACTION_SKIPPED => 'Пропущен',
            self::ACTION_ERROR => 'Ошибка',
        ];
        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Получить badge class для действия
     */
    public function getActionBadgeClass()
    {
        $classes = [
            self::ACTION_CREATED => 'bg-success',
            self::ACTION_UPDATED => 'bg-info',
            self::ACTION_SKIPPED => 'bg-warning',
            self::ACTION_ERROR => 'bg-danger',
        ];
        return $classes[$this->action] ?? 'bg-secondary';
    }

    /**
     * Получить иконку для уровня
     */
    public function getLevelIcon()
    {
        $icons = [
            self::LEVEL_INFO => 'bi-info-circle',
            self::LEVEL_WARNING => 'bi-exclamation-triangle',
            self::LEVEL_ERROR => 'bi-x-circle',
        ];
        return $icons[$this->level] ?? 'bi-info-circle';
    }

    /**
     * Получить data как массив
     */
    public function getDataArray()
    {
        return $this->data ? json_decode($this->data, true) : [];
    }

    /**
     * Статический метод для быстрого логирования
     */
    public static function log($batchId, $action, $message, $params = [])
    {
        $log = new self();
        $log->batch_id = $batchId;
        $log->action = $action;
        $log->message = $message;
        
        // Определяем level автоматически
        if ($action === self::ACTION_ERROR) {
            $log->level = self::LEVEL_ERROR;
        } elseif ($action === self::ACTION_SKIPPED) {
            $log->level = self::LEVEL_WARNING;
        } else {
            $log->level = self::LEVEL_INFO;
        }
        
        // Заполняем дополнительные поля
        if (isset($params['product_id'])) $log->product_id = $params['product_id'];
        if (isset($params['sku'])) $log->sku = $params['sku'];
        if (isset($params['poizon_id'])) $log->poizon_id = $params['poizon_id'];
        if (isset($params['product_name'])) $log->product_name = $params['product_name'];
        if (isset($params['data'])) $log->data = is_array($params['data']) ? json_encode($params['data'], JSON_UNESCAPED_UNICODE) : $params['data'];
        if (isset($params['error_details'])) $log->error_details = $params['error_details'];
        if (isset($params['level'])) $log->level = $params['level'];
        
        return $log->save(false);
    }
}
