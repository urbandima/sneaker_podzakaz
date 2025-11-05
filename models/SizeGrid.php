<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель SizeGrid (Размерная сетка)
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $gender
 * @property string $name
 * @property string|null $description
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property Brand $brand
 * @property SizeGridItem[] $items
 */
class SizeGrid extends ActiveRecord
{
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_UNISEX = 'unisex';
    const GENDER_KIDS = 'kids';

    public static function tableName()
    {
        return 'size_grid';
    }

    public function rules()
    {
        return [
            [['gender', 'name'], 'required'],
            [['brand_id'], 'integer'],
            [['description'], 'string'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
            [['gender'], 'string', 'max' => 20],
            [['gender'], 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_UNISEX, self::GENDER_KIDS]],
            [['name'], 'string', 'max' => 255],
            [['brand_id'], 'exist', 'targetClass' => Brand::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand_id' => 'Бренд',
            'gender' => 'Пол',
            'name' => 'Название сетки',
            'description' => 'Описание',
            'is_active' => 'Активна',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getBrand()
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    public function getItems()
    {
        return $this->hasMany(SizeGridItem::class, ['size_grid_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getGenderLabel()
    {
        $labels = [
            self::GENDER_MALE => 'Мужские',
            self::GENDER_FEMALE => 'Женские',
            self::GENDER_UNISEX => 'Унисекс',
            self::GENDER_KIDS => 'Детские',
        ];
        return $labels[$this->gender] ?? $this->gender;
    }

    public function getFullName()
    {
        $parts = [];
        if ($this->brand) {
            $parts[] = $this->brand->name;
        }
        $parts[] = $this->getGenderLabel();
        $parts[] = $this->name;
        return implode(' - ', $parts);
    }

    public static function getGenderOptions()
    {
        return [
            self::GENDER_MALE => 'Мужские',
            self::GENDER_FEMALE => 'Женские',
            self::GENDER_UNISEX => 'Унисекс',
            self::GENDER_KIDS => 'Детские',
        ];
    }
}
