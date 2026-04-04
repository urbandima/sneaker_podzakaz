<?php

namespace app\backend\modules\admin\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Модель пунктов бокового меню (Sidebar Menu)
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $url
 * @property string|null $route
 * @property array|null $params
 * @property string|null $icon
 * @property string|null $image
 * @property string $type
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_visible
 * @property bool $target_blank
 * @property string|null $css_class
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property SidebarMenuItem $parent
 * @property SidebarMenuItem[] $children
 */
class SidebarMenuItem extends ActiveRecord
{
    const TYPE_LINK = 'link';
    const TYPE_BANNER = 'banner';
    const TYPE_DIVIDER = 'divider';
    const TYPE_HEADER = 'header';

    const TYPES = [
        self::TYPE_LINK => 'Ссылка',
        self::TYPE_BANNER => 'Баннер',
        self::TYPE_DIVIDER => 'Разделитель',
        self::TYPE_HEADER => 'Заголовок',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%sidebar_menu}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title', 'url', 'route', 'icon', 'image', 'css_class'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['params'], 'safe'],
            [['sort_order'], 'integer'],
            [['is_active', 'is_visible', 'target_blank'], 'boolean'],
            [['parent_id'], 'integer'],
            [['parent_id'], 'exist', 'targetClass' => self::class, 'targetAttribute' => 'id'],
            ['type', 'in', 'range' => array_keys(self::TYPES)],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Родитель',
            'title' => 'Название',
            'url' => 'URL',
            'route' => 'Route',
            'params' => 'Параметры',
            'icon' => 'Иконка',
            'image' => 'Изображение',
            'type' => 'Тип',
            'sort_order' => 'Порядок',
            'is_active' => 'Активен',
            'is_visible' => 'Видимый',
            'target_blank' => 'Новая вкладка',
            'css_class' => 'CSS класс',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    /**
     * Родительский элемент
     */
    public function getParent(): \yii\db\ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * Дочерние элементы
     */
    public function getChildren(): \yii\db\ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Получить URL для пункта меню
     */
    public function getLinkUrl(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->route) {
            $params = $this->params ?? [];
            return \Yii::$app->urlManager->createUrl(array_merge([$this->route], $params));
        }

        return null;
    }

    /**
     * Получить все активные пункты меню
     */
    public static function getActiveItems(): array
    {
        return self::find()
            ->where(['is_active' => 1, 'is_visible' => 1, 'parent_id' => null])
            ->orderBy(['sort_order' => SORT_ASC])
            ->with('children')
            ->all();
    }

    /**
     * Получить структуру меню с подпунктами
     */
    public static function getMenuTree(): array
    {
        $items = self::getActiveItems();
        $tree = [];

        foreach ($items as $item) {
            $tree[] = [
                'item' => $item,
                'children' => $item->children,
            ];
        }

        return $tree;
    }

    /**
     * Получить список типов для dropdown
     */
    public static function getTypesList(): array
    {
        return self::TYPES;
    }

    /**
     * Получить список родителей для dropdown
     */
    public static function getParentsList(): array
    {
        return self::find()
            ->select(['id', 'title'])
            ->where(['is_active' => 1])
            ->andWhere(['OR', ['parent_id' => null], ['parent_id' => 0]])
            ->indexBy('id')
            ->column();
    }

    /**
     * Получить иконку с префиксом bi
     */
    public function getIconClass(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        return str_starts_with($this->icon, 'bi ') ? $this->icon : 'bi ' . $this->icon;
    }

    /**
     * Перед сохранением обработка params
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->params)) {
            $this->params = json_encode($this->params);
        }

        return true;
    }

    /**
     * После загрузки декодируем params
     */
    public function afterFind(): void
    {
        parent::afterFind();

        if (is_string($this->params)) {
            $this->params = json_decode($this->params, true) ?? [];
        }
    }
}
