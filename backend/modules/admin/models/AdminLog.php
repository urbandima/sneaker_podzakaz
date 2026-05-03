<?php

namespace app\backend\modules\admin\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Модель лога операций администратора
 *
 * @property int $id
 * @property int $user_id
 * @property string $username
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property string|null $entity_name
 * @property string|null $description
 * @property string|null $old_values
 * @property string|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $created_at
 */
class AdminLog extends ActiveRecord
{
    // Типы действий
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_EXPORT = 'export';
    const ACTION_IMPORT = 'import';
    const ACTION_STATUS_CHANGE = 'status_change';
    const ACTION_BULK_ACTION = 'bulk_action';

    // Типы сущностей
    const ENTITY_ORDER = 'order';
    const ENTITY_PRODUCT = 'product';
    const ENTITY_CUSTOMER = 'customer';
    const ENTITY_COUPON = 'coupon';
    const ENTITY_USER = 'user';
    const ENTITY_SETTING = 'setting';
    const ENTITY_IMPORT = 'import';
    const ENTITY_SHIPPING = 'shipping';
    const ENTITY_RETURN = 'return';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%admin_log}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'username', 'action', 'entity_type', 'created_at'], 'required'],
            [['user_id', 'entity_id', 'created_at'], 'integer'],
            [['description', 'old_values', 'new_values'], 'string'],
            [['username'], 'string', 'max' => 100],
            [['action', 'entity_type'], 'string', 'max' => 50],
            [['entity_name'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь ID',
            'username' => 'Пользователь',
            'action' => 'Действие',
            'entity_type' => 'Тип сущности',
            'entity_id' => 'ID сущности',
            'entity_name' => 'Название',
            'description' => 'Описание',
            'old_values' => 'Старые значения',
            'new_values' => 'Новые значения',
            'ip_address' => 'IP адрес',
            'user_agent' => 'User Agent',
            'created_at' => 'Дата',
        ];
    }

    /**
     * Получить метку действия
     */
    public function getActionLabel(): string
    {
        $labels = [
            self::ACTION_CREATE => 'Создание',
            self::ACTION_UPDATE => 'Изменение',
            self::ACTION_DELETE => 'Удаление',
            self::ACTION_VIEW => 'Просмотр',
            self::ACTION_LOGIN => 'Вход',
            self::ACTION_LOGOUT => 'Выход',
            self::ACTION_EXPORT => 'Экспорт',
            self::ACTION_IMPORT => 'Импорт',
            self::ACTION_STATUS_CHANGE => 'Смена статуса',
            self::ACTION_BULK_ACTION => 'Массовое действие',
        ];
        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Получить CSS класс для действия
     */
    public function getActionClass(): string
    {
        $classes = [
            self::ACTION_CREATE => 'success',
            self::ACTION_UPDATE => 'warning',
            self::ACTION_DELETE => 'danger',
            self::ACTION_VIEW => 'info',
            self::ACTION_LOGIN => 'primary',
            self::ACTION_LOGOUT => 'secondary',
            self::ACTION_EXPORT => 'info',
            self::ACTION_IMPORT => 'info',
            self::ACTION_STATUS_CHANGE => 'warning',
            self::ACTION_BULK_ACTION => 'warning',
        ];
        return $classes[$this->action] ?? 'secondary';
    }

    /**
     * Получить иконку для действия
     */
    public function getActionIcon(): string
    {
        $icons = [
            self::ACTION_CREATE => 'bi-plus-circle',
            self::ACTION_UPDATE => 'bi-pencil',
            self::ACTION_DELETE => 'bi-trash',
            self::ACTION_VIEW => 'bi-eye',
            self::ACTION_LOGIN => 'bi-box-arrow-in-right',
            self::ACTION_LOGOUT => 'bi-box-arrow-right',
            self::ACTION_EXPORT => 'bi-download',
            self::ACTION_IMPORT => 'bi-upload',
            self::ACTION_STATUS_CHANGE => 'bi-arrow-repeat',
            self::ACTION_BULK_ACTION => 'bi-check2-square',
        ];
        return $icons[$this->action] ?? 'bi-activity';
    }

    /**
     * Получить сущность по типу
     */
    public function getEntityUrl(): ?string
    {
        $routes = [
            self::ENTITY_ORDER => ['/admin/order/view', 'id' => $this->entity_id],
            self::ENTITY_PRODUCT => ['/admin/product/view', 'id' => $this->entity_id],
            self::ENTITY_CUSTOMER => ['/admin/customer/view', 'id' => $this->entity_id],
            self::ENTITY_COUPON => ['/admin/coupon/view', 'id' => $this->entity_id],
            self::ENTITY_USER => ['/admin/user/view', 'id' => $this->entity_id],
            self::ENTITY_SETTING => ['/admin/settings'],
            self::ENTITY_IMPORT => ['/admin/import'],
            self::ENTITY_SHIPPING => ['/admin/shipping'],
            self::ENTITY_RETURN => ['/admin/return/view', 'id' => $this->entity_id],
        ];
        
        return isset($routes[$this->entity_type]) 
            ? Yii::$app->urlManager->createUrl($routes[$this->entity_type]) 
            : null;
    }

    /**
     * Получить недавние логи
     */
    public static function getRecent(int $limit = 10): array
    {
        return self::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить логи пользователя
     */
    public static function getByUser(int $userId, int $limit = 50): array
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить логи по сущности
     */
    public static function getByEntity(string $entityType, int $entityId, int $limit = 20): array
    {
        return self::find()
            ->where(['entity_type' => $entityType, 'entity_id' => $entityId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Форматировать дату относительно
     */
    public function getRelativeTime(): string
    {
        return Yii::$app->formatter->asRelativeTime($this->created_at);
    }
}
