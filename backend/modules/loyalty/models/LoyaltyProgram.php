<?php

/**
 * LoyaltyProgram — Модель программы лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * Определение уровней клиентов и правил программы лояльности.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название уровня
 * - level: уровень (bronze, silver, gold, platinum)
 * - min_points: минимум баллов для уровня
 * - points_multiplier: множитель начисления баллов
 * - benefits: преимущества (JSON)
 * 
 * УРОВНИ:
 * - Bronze: начальный уровень
 * - Silver: от 1000 баллов
 * - Gold: от 5000 баллов
 * - Platinum: от 10000 баллов
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $level = LoyaltyProgram::getLevelByPoints(2500);
 */
namespace app\backend\modules\loyalty\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель программы лояльности
 *
 * @property int $id
 * @property string $name Название уровня
 * @property string $level Код уровня
 * @property int $min_points Минимум баллов
 * @property float $points_multiplier Множитель начисления
 * @property float $discount_percent Процент скидки
 * @property string|null $benefits Преимущества (JSON)
 * @property string $color Цвет уровня
 * @property string $icon Иконка
 * @property bool $is_active Активность
 * @property int $sort_order Порядок сортировки
 */
class LoyaltyProgram extends ActiveRecord
{
    // Уровни
    const LEVEL_BRONZE = 'bronze';
    const LEVEL_SILVER = 'silver';
    const LEVEL_GOLD = 'gold';
    const LEVEL_PLATINUM = 'platinum';

    public static function tableName()
    {
        return '{{%loyalty_program}}';
    }

    public function rules()
    {
        return [
            [['name', 'level', 'min_points'], 'required'],
            [['min_points', 'sort_order'], 'integer'],
            [['points_multiplier', 'discount_percent'], 'number'],
            [['name', 'level', 'color', 'icon'], 'string', 'max' => 50],
            [['benefits'], 'string'],
            [['is_active'], 'boolean'],
            [['sort_order'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => true],
            [['points_multiplier'], 'default', 'value' => 1.0],
            [['discount_percent'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'level' => 'Уровень',
            'min_points' => 'Мин. баллов',
            'points_multiplier' => 'Множитель баллов',
            'discount_percent' => 'Скидка',
            'benefits' => 'Преимущества',
            'color' => 'Цвет',
            'icon' => 'Иконка',
            'is_active' => 'Активен',
        ];
    }

    /**
     * Получить уровень по количеству баллов
     * 
     * @param int $points
     * @return LoyaltyProgram|null
     */
    public static function getLevelByPoints(int $points): ?LoyaltyProgram
    {
        return self::find()
            ->where(['is_active' => true])
            ->andWhere(['<=', 'min_points', $points])
            ->orderBy(['min_points' => SORT_DESC])
            ->one();
    }

    /**
     * Получить все уровни
     * 
     * @return LoyaltyProgram[]
     */
    public static function getAllLevels(): array
    {
        return self::find()
            ->where(['is_active' => true])
            ->orderBy(['min_points' => SORT_ASC])
            ->all();
    }

    /**
     * Получить преимущества
     * 
     * @return array
     */
    public function getBenefitsList(): array
    {
        if (empty($this->benefits)) {
            return [];
        }
        return json_decode($this->benefits, true) ?: [];
    }

    /**
     * Получить следующий уровень
     * 
     * @return LoyaltyProgram|null
     */
    public function getNextLevel(): ?LoyaltyProgram
    {
        return self::find()
            ->where(['is_active' => true])
            ->andWhere(['>', 'min_points', $this->min_points])
            ->orderBy(['min_points' => SORT_ASC])
            ->one();
    }

    /**
     * Рассчитать баллы до следующего уровня
     * 
     * @param int $currentPoints
     * @return int
     */
    public function getPointsToNextLevel(int $currentPoints): int
    {
        $nextLevel = $this->getNextLevel();
        if (!$nextLevel) {
            return 0;
        }
        return max(0, $nextLevel->min_points - $currentPoints);
    }

    /**
     * Список уровней
     */
    public static function getLevelList(): array
    {
        return [
            self::LEVEL_BRONZE => 'Бронза',
            self::LEVEL_SILVER => 'Серебро',
            self::LEVEL_GOLD => 'Золото',
            self::LEVEL_PLATINUM => 'Платина',
        ];
    }

    /**
     * Название уровня
     */
    public function getLevelName(): string
    {
        return self::getLevelList()[$this->level] ?? $this->level;
    }
}
