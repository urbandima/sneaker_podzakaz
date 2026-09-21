<?php

/**
 * ImportLog — Модель лога импорта (одна строка = один товар в рамках ImportBatch)
 *
 * НАЗНАЧЕНИЕ:
 * Детальный лог импорта из Poizon: результат обработки каждого товара
 * (создан/обновлён/пропущен/ошибка) внутри конкретного батча.
 *
 * СВЯЗИ:
 * - ImportBatch (batch): батч, в рамках которого создан лог
 * - Product (product): товар, если создан/обновлён
 *
 * ИСПОЛЬЗОВАНИЕ:
 * - PoizonController, AdminImportController (web-дашборды)
 * - PoizonImportController, PoizonImportJsonController (консольные команды)
 */

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $batch_id
 * @property int|null $product_id
 * @property string $action
 * @property string $level
 * @property string|null $sku
 * @property string|null $poizon_id
 * @property string|null $product_name
 * @property string|null $message
 * @property string|null $data
 * @property string|null $error_details
 * @property string $created_at
 *
 * @property ImportBatch $batch
 * @property Product $product
 */
class ImportLog extends ActiveRecord
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_SKIPPED = 'skipped';
    public const ACTION_ERROR = 'error';
    public const ACTION_DUPLICATE = 'duplicate';

    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    public static function tableName()
    {
        return '{{%import_log}}';
    }

    public function rules()
    {
        return [
            [['batch_id', 'action'], 'required'],
            [['batch_id', 'product_id'], 'integer'],
            [['action', 'level'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => [self::ACTION_CREATED, self::ACTION_UPDATED, self::ACTION_SKIPPED, self::ACTION_ERROR, self::ACTION_DUPLICATE]],
            [['level'], 'in', 'range' => [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR]],
            [['level'], 'default', 'value' => self::LEVEL_INFO],
            [['sku', 'poizon_id'], 'string', 'max' => 100],
            [['product_name'], 'string', 'max' => 255],
            [['message', 'data', 'error_details'], 'string'],
            [['created_at'], 'safe'],
            [['batch_id'], 'exist', 'targetClass' => ImportBatch::class, 'targetAttribute' => 'id'],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
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
            'product_name' => 'Товар',
            'message' => 'Сообщение',
            'data' => 'Данные',
            'error_details' => 'Ошибка',
            'created_at' => 'Время',
        ];
    }

    /**
     * Батч импорта, к которому относится лог
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
     * Получить данные
     * @return array
     */
    public function getDataArray()
    {
        if (empty($this->data)) {
            return [];
        }

        $data = json_decode($this->data, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Установить данные
     * @param array $data
     */
    public function setDataArray(array $data)
    {
        $this->data = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Записать строку лога импорта товара
     *
     * @param int $batchId
     * @param string $action ACTION_*
     * @param string $message
     * @param array $extra Доп. поля: product_id, sku, poizon_id, product_name, error_details, data
     * @return self
     */
    public static function log($batchId, $action, $message, array $extra = [])
    {
        $log = new self();
        $log->batch_id = $batchId;
        $log->action = $action;
        $log->level = $action === self::ACTION_ERROR ? self::LEVEL_ERROR : self::LEVEL_INFO;
        $log->message = $message;
        $log->product_id = $extra['product_id'] ?? null;
        $log->sku = $extra['sku'] ?? null;
        $log->poizon_id = $extra['poizon_id'] ?? null;
        $log->product_name = $extra['product_name'] ?? null;
        $log->error_details = $extra['error_details'] ?? null;

        if (isset($extra['data'])) {
            $log->data = is_string($extra['data']) ? $extra['data'] : json_encode($extra['data'], JSON_UNESCAPED_UNICODE);
        }

        $log->save(false);
        return $log;
    }

    /**
     * Получить действие в текстовом виде
     * @return string
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_CREATED => 'Создан',
            self::ACTION_UPDATED => 'Обновлен',
            self::ACTION_SKIPPED => 'Пропущен',
            self::ACTION_ERROR => 'Ошибка',
            self::ACTION_DUPLICATE => 'Дубликат',
        ];

        return $labels[$this->action] ?? 'Неизвестно';
    }

    /**
     * CSS-класс badge для действия (используется в admin/poizon/view)
     * @return string
     */
    public function getActionBadgeClass()
    {
        $classes = [
            self::ACTION_CREATED => 'bg-success',
            self::ACTION_UPDATED => 'bg-primary',
            self::ACTION_SKIPPED => 'bg-secondary',
            self::ACTION_ERROR => 'bg-danger',
            self::ACTION_DUPLICATE => 'bg-warning',
        ];

        return $classes[$this->action] ?? 'bg-secondary';
    }

    /**
     * Получить уровень в текстовом виде
     * @return string
     */
    public function getLevelLabel()
    {
        $labels = [
            self::LEVEL_INFO => 'Информация',
            self::LEVEL_WARNING => 'Предупреждение',
            self::LEVEL_ERROR => 'Ошибка',
        ];

        return $labels[$this->level] ?? 'Неизвестно';
    }
}
