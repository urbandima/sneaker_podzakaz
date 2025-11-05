<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель SizeGridItem (Элемент размерной сетки)
 *
 * @property int $id
 * @property int $size_grid_id
 * @property string|null $us_size
 * @property string|null $eu_size
 * @property string|null $uk_size
 * @property float|null $cm_size
 * @property string $size
 * @property int $sort_order
 * 
 * @property SizeGrid $sizeGrid
 */
class SizeGridItem extends ActiveRecord
{
    public static function tableName()
    {
        return 'size_grid_item';
    }

    public function rules()
    {
        return [
            [['size_grid_id', 'size'], 'required'],
            [['size_grid_id', 'sort_order'], 'integer'],
            [['cm_size'], 'number'],
            [['us_size', 'eu_size', 'uk_size', 'size'], 'string', 'max' => 20],
            [['sort_order'], 'default', 'value' => 0],
            [['size_grid_id'], 'exist', 'targetClass' => SizeGrid::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'size_grid_id' => 'Размерная сетка',
            'us_size' => 'US',
            'eu_size' => 'EU',
            'uk_size' => 'UK',
            'cm_size' => 'CM',
            'size' => 'Размер',
            'sort_order' => 'Порядок',
        ];
    }

    public function getSizeGrid()
    {
        return $this->hasOne(SizeGrid::class, ['id' => 'size_grid_id']);
    }
}
