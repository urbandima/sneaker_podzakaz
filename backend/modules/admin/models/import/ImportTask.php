<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;

/**
 * ImportTask — Модель задачи импорта
 * 
 * @property int $id
 * @property int $source_id ID источника
 * @property string $status Статус (pending, running, completed, failed, cancelled)
 * @property int $total_products Всего товаров в задаче
 * @property int $processed_products Обработано товаров
 * @property int $imported_count Импортировано новых
 * @property int $updated_count Обновлено существующих
 * @property int $failed_count Ошибок
 * @property int $duplicate_count Дубликатов найдено
 * @property string|null $started_at Время старта
 * @property string|null $finished_at Время завершения
 * @property int|null $duration_seconds Длительность в секундах
 * @property string|null $config JSON с параметрами запуска
 * @property string|null $error_message Сообщение об ошибке
 * @property int|null $created_by Кто запустил
 * @property string $created_at
 * 
 * @property ImportSource $source Источник
 * @property ImportLog[] $logs Логи импорта
 */
class ImportTask extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_task}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_id'], 'required'],
            [['source_id', 'total_products', 'processed_products', 'imported_count', 'updated_count', 'failed_count', 'duplicate_count', 'duration_seconds', 'created_by'], 'integer'],
            [['total_products', 'processed_products', 'imported_count', 'updated_count', 'failed_count', 'duplicate_count'], 'default', 'value' => 0],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['config', 'error_message'], 'string'],
            [['started_at', 'finished_at', 'created_at'], 'safe'],
            [['source_id'], 'exist', 'targetClass' => ImportSource::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_id' => 'Источник',
            'status' => 'Статус',
            'total_products' => 'Всего товаров',
            'processed_products' => 'Обработано',
            'imported_count' => 'Импортировано',
            'updated_count' => 'Обновлено',
            'failed_count' => 'Ошибок',
            'duplicate_count' => 'Дубликатов',
            'started_at' => 'Запущен',
            'finished_at' => 'Завершен',
            'duration_seconds' => 'Длительность (сек)',
            'config' => 'Конфигурация',
            'error_message' => 'Ошибка',
            'created_by' => 'Запустил',
            'created_at' => 'Создан',
        ];
    }

    /**
     * Источник импорта
     */
    public function getSource()
    {
        return $this->hasOne(ImportSource::class, ['id' => 'source_id']);
    }

    /**
     * Логи импорта
     */
    public function getLogs()
    {
        return $this->hasMany(ImportLog::class, ['task_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Получить конфигурацию
     * @return array
     */
    public function getConfigArray()
    {
        if (empty($this->config)) {
            return [];
        }

        $config = json_decode($this->config, true);
        return is_array($config) ? $config : [];
    }

    /**
     * Установить конфигурацию
     * @param array $config
     */
    public function setConfigArray(array $config)
    {
        $this->config = json_encode($config, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Запустить задачу
     */
    public function start()
    {
        $this->status = self::STATUS_RUNNING;
        $this->started_at = date('Y-m-d H:i:s');
        $this->save(false, ['status', 'started_at']);
    }

    /**
     * Завершить задачу успешно
     */
    public function complete()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->finished_at = date('Y-m-d H:i:s');
        
        if ($this->started_at) {
            $this->duration_seconds = strtotime($this->finished_at) - strtotime($this->started_at);
        }
        
        $this->save(false, ['status', 'finished_at', 'duration_seconds']);
        
        // Обновляем статистику источника
        $this->source->updateStats(true, $this->imported_count + $this->updated_count);
    }

    /**
     * Завершить задачу с ошибкой
     * @param string $message
     */
    public function fail($message)
    {
        $this->status = self::STATUS_FAILED;
        $this->finished_at = date('Y-m-d H:i:s');
        $this->error_message = $message;
        
        if ($this->started_at) {
            $this->duration_seconds = strtotime($this->finished_at) - strtotime($this->started_at);
        }
        
        $this->save(false, ['status', 'finished_at', 'duration_seconds', 'error_message']);
        
        // Обновляем статистику источника
        $this->source->updateStats(false);
    }

    /**
     * Отменить задачу
     */
    public function cancel()
    {
        $this->status = self::STATUS_CANCELLED;
        $this->finished_at = date('Y-m-d H:i:s');
        
        if ($this->started_at) {
            $this->duration_seconds = strtotime($this->finished_at) - strtotime($this->started_at);
        }
        
        $this->save(false, ['status', 'finished_at', 'duration_seconds']);
    }

    /**
     * Увеличить счетчик обработанных
     */
    public function incrementProcessed()
    {
        $this->processed_products++;
        $this->save(false, ['processed_products']);
    }

    /**
     * Увеличить счетчик импортированных
     */
    public function incrementImported()
    {
        $this->imported_count++;
        $this->save(false, ['imported_count']);
    }

    /**
     * Увеличить счетчик обновленных
     */
    public function incrementUpdated()
    {
        $this->updated_count++;
        $this->save(false, ['updated_count']);
    }

    /**
     * Увеличить счетчик ошибок
     */
    public function incrementFailed()
    {
        $this->failed_count++;
        $this->save(false, ['failed_count']);
    }

    /**
     * Увеличить счетчик дубликатов
     */
    public function incrementDuplicate()
    {
        $this->duplicate_count++;
        $this->save(false, ['duplicate_count']);
    }

    /**
     * Получить прогресс выполнения
     * @return int Процент выполнения
     */
    public function getProgress()
    {
        if ($this->total_products == 0) {
            return 0;
        }

        return round(($this->processed_products / $this->total_products) * 100);
    }

    /**
     * Получить статус в текстовом виде
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_RUNNING => 'Выполняется',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_CANCELLED => 'Отменен',
        ];

        return $labels[$this->status] ?? 'Неизвестно';
    }

    /**
     * Получить длительность в читаемом формате
     * @return string
     */
    public function getDurationFormatted()
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dч %dм %dс', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dм %dс', $minutes, $seconds);
        } else {
            return sprintf('%dс', $seconds);
        }
    }

    /**
     * Получить последние активные задачи
     * @param int $limit
     * @return ImportTask[]
     */
    public static function getRecentTasks($limit = 10)
    {
        return static::find()
            ->with('source')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить запущенные задачи
     * @return ImportTask[]
     */
    public static function getRunningTasks()
    {
        return static::find()
            ->where(['status' => self::STATUS_RUNNING])
            ->with('source')
            ->all();
    }
}
