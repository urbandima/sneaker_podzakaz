<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;

/**
 * ImportNotification — Модель уведомления об импорте
 * 
 * @property int $id
 * @property int $task_id ID задачи
 * @property string $type Тип уведомления (success, error, warning)
 * @property string $title Заголовок
 * @property string $message Сообщение
 * @property bool $is_read Прочитано
 * @property string $created_at
 * 
 * @property ImportTask $task Задача
 */
class ImportNotification extends ActiveRecord
{
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_notification}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'type', 'title', 'message'], 'required'],
            [['task_id'], 'integer'],
            [['message'], 'string'],
            [['is_read'], 'boolean'],
            [['is_read'], 'default', 'value' => false],
            [['type'], 'string', 'max' => 20],
            [['type'], 'in', 'range' => [self::TYPE_SUCCESS, self::TYPE_ERROR, self::TYPE_WARNING, self::TYPE_INFO]],
            [['title'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
            [['task_id'], 'exist', 'targetClass' => ImportTask::class, 'targetAttribute' => 'id'],
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
            'type' => 'Тип',
            'title' => 'Заголовок',
            'message' => 'Сообщение',
            'is_read' => 'Прочитано',
            'created_at' => 'Создано',
        ];
    }

    /**
     * Задача
     */
    public function getTask()
    {
        return $this->hasOne(ImportTask::class, ['id' => 'task_id']);
    }

    /**
     * Создать уведомление об успешном импорте
     * @param ImportTask $task
     * @return ImportNotification
     */
    public static function createSuccess(ImportTask $task)
    {
        $notification = new self([
            'task_id' => $task->id,
            'type' => self::TYPE_SUCCESS,
            'title' => "Импорт завершен: {$task->source->name}",
            'message' => "Импортировано: {$task->imported_count}\n" .
                        "Обновлено: {$task->updated_count}\n" .
                        "Ошибок: {$task->failed_count}\n" .
                        "Длительность: {$task->getDurationFormatted()}",
        ]);
        $notification->save(false);
        return $notification;
    }

    /**
     * Создать уведомление об ошибке импорта
     * @param ImportTask $task
     * @return ImportNotification
     */
    public static function createError(ImportTask $task)
    {
        $notification = new self([
            'task_id' => $task->id,
            'type' => self::TYPE_ERROR,
            'title' => "Ошибка импорта: {$task->source->name}",
            'message' => $task->error_message ?: 'Неизвестная ошибка',
        ]);
        $notification->save(false);
        return $notification;
    }

    /**
     * Создать уведомление с предупреждением
     * @param ImportTask $task
     * @param string $warning
     * @return ImportNotification
     */
    public static function createWarning(ImportTask $task, $warning)
    {
        $notification = new self([
            'task_id' => $task->id,
            'type' => self::TYPE_WARNING,
            'title' => "Предупреждение: {$task->source->name}",
            'message' => $warning,
        ]);
        $notification->save(false);
        return $notification;
    }

    /**
     * Получить непрочитанные уведомления
     * @param int $limit
     * @return ImportNotification[]
     */
    public static function getUnread($limit = 10)
    {
        return static::find()
            ->where(['is_read' => false])
            ->with('task.source')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить количество непрочитанных
     * @return int
     */
    public static function getUnreadCount()
    {
        return static::find()
            ->where(['is_read' => false])
            ->count();
    }

    /**
     * Отметить как прочитанное
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save(false, ['is_read']);
    }

    /**
     * Отметить все как прочитанные
     * @return int Количество обновленных
     */
    public static function markAllAsRead()
    {
        return static::updateAll(['is_read' => true], ['is_read' => false]);
    }

    /**
     * Получить иконку для типа
     * @return string
     */
    public function getIcon()
    {
        return match($this->type) {
            self::TYPE_SUCCESS => 'check-circle',
            self::TYPE_ERROR => 'exclamation-circle',
            self::TYPE_WARNING => 'exclamation-triangle',
            self::TYPE_INFO => 'info-circle',
            default => 'bell',
        };
    }

    /**
     * Получить CSS класс для типа
     * @return string
     */
    public function getCssClass()
    {
        return match($this->type) {
            self::TYPE_SUCCESS => 'success',
            self::TYPE_ERROR => 'danger',
            self::TYPE_WARNING => 'warning',
            self::TYPE_INFO => 'info',
            default => 'secondary',
        };
    }
}
