<?php

/**
 * ImportBatch — Модель батча импорта
 * 
 * НАЗНАЧЕНИЕ:
 * Отслеживание пакетов импорта товаров: статистика, логи, статус.
 * Используется для импорта из Poizon, ручного импорта, API импорта.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - source: источник (poizon, manual, api)
 * - type: тип (full, update, sizes)
 * - status: статус (pending, processing, completed, failed)
 * - started_at, finished_at: время выполнения
 * - duration_seconds: длительность
 * - total_items: всего товаров
 * - created_count, updated_count, skipped_count, error_count: статистика
 * 
 * СТАТУСЫ:
 * - STATUS_PENDING: ожидает запуска
 * - STATUS_PROCESSING: выполняется
 * - STATUS_COMPLETED: завершен успешно
 * - STATUS_FAILED: завершен с ошибкой
 * 
 * ИСТОЧНИКИ:
 * - SOURCE_POIZON: Poizon API
 * - SOURCE_MANUAL: ручной импорт
 * - SOURCE_API: API
 * 
 * ТИПЫ:
 * - TYPE_FULL: полный импорт
 * - TYPE_UPDATE: обновление
 * - TYPE_SIZES: только размеры
 * 
 * СВЯЗИ:
 * - User (creator): запустивший импорт
 * - ImportLog[] (logs): логи батча
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - PoizonController (импорт из Poizon)
 * - ImportController (ручной импорт)
 * - Отслеживание прогресса импорта
 */
namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\admin\models\User;

/**
 * Модель ImportBatch (Батч импорта)
 *
 * @property int $id
 * @property string $source
 * @property string $type
 * @property string $status
 * @property string $started_at
 * @property string $finished_at
 * @property int $duration_seconds
 * @property int $total_items
 * @property int $created_count
 * @property int $updated_count
 * @property int $skipped_count
 * @property int $error_count
 * @property string $config
 * @property string $summary
 * @property string $error_message
 * @property int $created_by
 * @property string $created_at
 * 
 * @property User $creator
 * @property ImportLog[] $logs
 */
class ImportBatch extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const SOURCE_POIZON = 'poizon';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_API = 'api';

    const TYPE_FULL = 'full';
    const TYPE_UPDATE = 'update';
    const TYPE_SIZES = 'sizes';

    public static function tableName()
    {
        return '{{%import_batch}}';
    }

    public function rules()
    {
        return [
            [['source', 'type', 'status'], 'required'],
            [['source'], 'in', 'range' => [self::SOURCE_POIZON, self::SOURCE_MANUAL, self::SOURCE_API]],
            [['type'], 'in', 'range' => [self::TYPE_FULL, self::TYPE_UPDATE, self::TYPE_SIZES]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED]],
            [['started_at', 'finished_at'], 'safe'],
            [['duration_seconds', 'total_items', 'created_count', 'updated_count', 'skipped_count', 'error_count', 'created_by'], 'integer'],
            [['config', 'summary', 'error_message'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source' => 'Источник',
            'type' => 'Тип импорта',
            'status' => 'Статус',
            'started_at' => 'Начало',
            'finished_at' => 'Завершение',
            'duration_seconds' => 'Длительность (сек)',
            'total_items' => 'Всего товаров',
            'created_count' => 'Создано',
            'updated_count' => 'Обновлено',
            'skipped_count' => 'Пропущено',
            'error_count' => 'Ошибок',
            'created_by' => 'Запустил',
            'created_at' => 'Создан',
        ];
    }

    /**
     * Пользователь, запустивший импорт
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Логи этого батча
     */
    public function getLogs()
    {
        return $this->hasMany(ImportLog::class, ['batch_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Старт импорта
     */
    public function start()
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_at = date('Y-m-d H:i:s');
        return $this->save(false);
    }

    /**
     * Завершение импорта
     */
    public function complete($success = true)
    {
        $this->status = $success ? self::STATUS_COMPLETED : self::STATUS_FAILED;
        $this->finished_at = date('Y-m-d H:i:s');
        
        if ($this->started_at) {
            $start = strtotime($this->started_at);
            $end = strtotime($this->finished_at);
            $this->duration_seconds = $end - $start;
        }
        
        return $this->save(false);
    }

    /**
     * Получить процент успеха
     */
    public function getSuccessRate()
    {
        if ($this->total_items == 0) {
            return 0;
        }
        $successful = $this->created_count + $this->updated_count;
        return round(($successful / $this->total_items) * 100, 1);
    }

    /**
     * Получить label статуса
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'Ожидание',
            self::STATUS_PROCESSING => 'В процессе',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_FAILED => 'Ошибка',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Получить badge class для статуса
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            self::STATUS_PENDING => 'bg-secondary',
            self::STATUS_PROCESSING => 'bg-primary',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
        ];
        return $classes[$this->status] ?? 'bg-secondary';
    }

    /**
     * Получить label источника
     */
    public function getSourceLabel()
    {
        $labels = [
            self::SOURCE_POIZON => 'Poizon API',
            self::SOURCE_MANUAL => 'Ручной импорт',
            self::SOURCE_API => 'API',
        ];
        return $labels[$this->source] ?? $this->source;
    }

    /**
     * Получить label типа
     */
    public function getTypeLabel()
    {
        $labels = [
            self::TYPE_FULL => 'Полный импорт',
            self::TYPE_UPDATE => 'Обновление',
            self::TYPE_SIZES => 'Только размеры',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Форматировать длительность
     */
    public function getFormattedDuration()
    {
        if (!$this->duration_seconds) {
            return '-';
        }
        
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d мин %d сек', $minutes, $seconds);
        }
        return sprintf('%d сек', $seconds);
    }

    /**
     * Получить конфиг как массив
     */
    public function getConfigArray()
    {
        return $this->config ? json_decode($this->config, true) : [];
    }

    /**
     * Получить summary как массив
     */
    public function getSummaryArray()
    {
        return $this->summary ? json_decode($this->summary, true) : [];
    }
}
