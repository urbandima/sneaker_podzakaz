<?php

namespace app\backend\modules\compare\components;

use Yii;
use yii\base\Component;
use app\backend\modules\catalog\models\Product;

/**
 * CompareComponent — Компонент управления списком сравнения товаров
 *
 * Хранит список сравниваемых товаров в сессии.
 * Максимум 4 товара одновременно.
 *
 * Использование:
 *   Yii::$app->compare->add($productId);
 *   Yii::$app->compare->remove($productId);
 *   Yii::$app->compare->getCount();
 *   Yii::$app->compare->getIds();
 *   Yii::$app->compare->getProducts();
 *   Yii::$app->compare->clear();
 *   Yii::$app->compare->has($productId);
 */
class CompareComponent extends Component
{
    /** Максимальное количество товаров для сравнения */
    const MAX_ITEMS = 4;

    /** Ключ сессии для хранения списка */
    const SESSION_KEY = 'compare';

    /**
     * Добавить товар в список сравнения.
     *
     * @param int $productId
     * @return bool true — добавлен, false — уже есть или достигнут лимит
     */
    public function add(int $productId): bool
    {
        $ids = $this->getIds();

        if (in_array($productId, $ids)) {
            return false; // уже есть
        }

        if (count($ids) >= self::MAX_ITEMS) {
            return false; // достигнут лимит
        }

        $ids[] = $productId;
        Yii::$app->session->set(self::SESSION_KEY, $ids);

        return true;
    }

    /**
     * Удалить товар из списка сравнения.
     *
     * @param int $productId
     * @return bool
     */
    public function remove(int $productId): bool
    {
        $ids = $this->getIds();
        $ids = array_values(array_diff($ids, [$productId]));
        Yii::$app->session->set(self::SESSION_KEY, $ids);

        return true;
    }

    /**
     * Очистить список сравнения.
     */
    public function clear(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY);
    }

    /**
     * Проверить, есть ли товар в списке сравнения.
     *
     * @param int $productId
     * @return bool
     */
    public function has(int $productId): bool
    {
        return in_array($productId, $this->getIds());
    }

    /**
     * Получить список ID товаров в сравнении.
     *
     * @return int[]
     */
    public function getIds(): array
    {
        return (array) Yii::$app->session->get(self::SESSION_KEY, []);
    }

    /**
     * Получить количество товаров в сравнении.
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->getIds());
    }

    /**
     * Получить модели товаров из сравнения.
     *
     * @return Product[]
     */
    public function getProducts(): array
    {
        $ids = $this->getIds();
        if (empty($ids)) {
            return [];
        }

        return Product::find()
            ->where(['id' => $ids, 'is_active' => 1])
            ->with(['brand', 'category', 'images', 'characteristicValues'])
            ->all();
    }

    /**
     * Достигнут ли максимальный лимит товаров.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->getCount() >= self::MAX_ITEMS;
    }
}
