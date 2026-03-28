<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;
use app\backend\modules\catalog\models\Product;

/**
 * ImportLog — Модель лога импорта
 * 
 * @property int $id
 * @property int $task_id ID задачи
 * @property int|null $product_id ID товара
 * @property string $action Действие (created, updated, skipped, error)
 * @property string $level Уровень (info, warning, error)
 * @property string|null $sku SKU товара
 * @property string|null $poizon_id ID в Poizon
 * @property string|null $product_name Название товара
 * @property string|null $message Сообщение лога
 * @property string|null $data JSON с данными
 * @property string|null $error_details Детали ошибки
 * @property string $created_at
 * 
 * @property ImportTask $task Задача
 * @property Product $product Товар
 */
class ImportLog extends ActiveRecord
{
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_ERROR = 'error';
    const ACTION_DUPLICATE = 'duplicate';

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'action'], 'required'],
            [['task_id', 'product_id'], 'integer'],
            [['action', 'level'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => [self::ACTION_CREATED, self::ACTION_UPDATED, self::ACTION_SKIPPED, self::ACTION_ERROR, self::ACTION_DUPLICATE]],
            [['level'], 'in', 'range' => [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR]],
            [['level'], 'default', 'value' => self::LEVEL_INFO],
            [['sku', 'poizon_id'], 'string', 'max' => 100],
            [['product_name'], 'string', 'max' => 255],
            [['message', 'data', 'error_details'], 'string'],
            [['created_at'], 'safe'],
            [['task_id'], 'exist', 'targetClass' => ImportTask::class, 'targetAttribute' => 'id'],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Задача',
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
     * Задача импорта
     */
    public function getTask()
    {
        return $this->hasOne(ImportTask::class, ['id' => 'task_id']);
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
     * Создать лог создания товара
     * @param int $taskId
     * @param int $productId
     * @param string $sku
     * @param string $productName
     * @return ImportLog
     */
    public static function logCreated($taskId, $productId, $sku, $productName)
    {
        $log = new self([
            'task_id' => $taskId,
            'product_id' => $productId,
            'action' => self::ACTION_CREATED,
            'level' => self::LEVEL_INFO,
            'sku' => $sku,
            'product_name' => $productName,
            'message' => "Товар создан: {$productName}",
        ]);
        $log->save(false);
        return $log;
    }

    /**
     * Создать лог обновления товара
     * @param int $taskId
     * @param int $productId
     * @param string $sku
     * @param string $productName
     * @param array $changes
     * @return ImportLog
     */
    public static function logUpdated($taskId, $productId, $sku, $productName, array $changes = [])
    {
        $log = new self([
            'task_id' => $taskId,
            'product_id' => $productId,
            'action' => self::ACTION_UPDATED,
            'level' => self::LEVEL_INFO,
            'sku' => $sku,
            'product_name' => $productName,
            'message' => "Товар обновлен: {$productName}",
        ]);
        
        if (!empty($changes)) {
            $log->setDataArray($changes);
        }
        
        $log->save(false);
        return $log;
    }

    /**
     * Создать лог дубликата
     * @param int $taskId
     * @param int $productId
     * @param string $sku
     * @param string $productName
     * @return ImportLog
     */
    public static function logDuplicate($taskId, $productId, $sku, $productName)
    {
        $log = new self([
            'task_id' => $taskId,
            'product_id' => $productId,
            'action' => self::ACTION_DUPLICATE,
            'level' => self::LEVEL_INFO,
            'sku' => $sku,
            'product_name' => $productName,
            'message' => "Дубликат найден: {$productName}",
        ]);
        $log->save(false);
        return $log;
    }

    /**
     * Создать лог ошибки
     * @param int $taskId
     * @param string $sku
     * @param string $productName
     * @param string $error
     * @param array $data
     * @return ImportLog
     */
    public static function logError($taskId, $sku, $productName, $error, array $data = [])
    {
        $log = new self([
            'task_id' => $taskId,
            'action' => self::ACTION_ERROR,
            'level' => self::LEVEL_ERROR,
            'sku' => $sku,
            'product_name' => $productName,
            'message' => "Ошибка при импорте: {$productName}",
            'error_details' => $error,
        ]);
        
        if (!empty($data)) {
            $log->setDataArray($data);
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
