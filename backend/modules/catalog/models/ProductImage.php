<?php

/**
 * ProductImage — Модель изображения товара
 *
 * НАЗНАЧЕНИЕ:
 * Изображения товара: главное фото, галерея, сортировка.
 * Поддержка нескольких изображений для одного товара.
 *
 * ОСНОВНЫЕ СВОЙСТВА:
 * - product_id: ID товара
 * - image: путь к изображению
 * - sort_order: порядок сортировки
 * - is_main: главное изображение (для карточки и каталога)
 *
 * СВЯЗИ:
 * - Product (принадлежит товару)
 *
 * ИСПОЛЬЗОВАНИЕ:
 * - CatalogController (отображение в карточке товара)
 * - ProductController/admin (управление изображениями)
 * - SEO (Open Graph, Schema.org)
 *
 * ОСОБЕННОСТИ:
 * - Автоматическое создание миниатюр
 * - Водяные знаки (опционально)
 * - Оптимизация размера
 */

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель ProductImage (Изображение товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $image Путь к изображению
 * @property int $sort_order Порядок сортировки
 * @property int $is_main Главное изображение
 * @property int $created_at
 *
 * @property Product $product
 */
class ProductImage extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_image';
    }

    /**
     * {@inheritdoc}
     *
     * CMP-417: `product_image`.`created_at` — `int NOT NULL` без дефолта (см.
     * infrastructure/migrations/m250101_000000_create_base_tables.php). Модель
     * никогда не заполняла это поле, поэтому КАЖДЫЙ save() (actionAddImage)
     * падал под strict mode с "SQLSTATE[HY000]: 1364 Field 'created_at'
     * doesn't have a default value" — тот же класс багов, что и в CMP-410.
     * Таблица не имеет колонки updated_at, поэтому она отключена явно.
     */
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
            [['product_id', 'image'], 'required'],
            [['product_id', 'sort_order'], 'integer'],
            [['is_main'], 'boolean'],
            [['is_main'], 'default', 'value' => 0],
            [['sort_order'], 'default', 'value' => 0],
            [['image'], 'string', 'max' => 255],
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
            'product_id' => 'Товар',
            'image' => 'Путь к изображению',
            'sort_order' => 'Порядок',
            'is_main' => 'Главное изображение',
            'created_at' => 'Создано',
        ];
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Получить URL изображения
     */
    public function getUrl()
    {
        if (empty($this->image)) {
            return null;
        }

        // Если это полный URL (начинается с http)
        if (strpos($this->image, 'http') === 0) {
            return $this->image;
        }

        // Если это относительный путь - добавляем baseUrl
        return Yii::$app->request->baseUrl . '/' . ltrim($this->image, '/');
    }

    /**
     * Получить полный URL изображения
     */
    public function getImageUrl()
    {
        if (empty($this->image)) {
            return null;
        }

        // Если это полный URL (начинается с http)
        if (strpos($this->image, 'http') === 0) {
            return $this->image;
        }

        // Если это относительный путь
        return Yii::getAlias('@web') . '/' . ltrim($this->image, '/');
    }

    /**
     * Установить как главное изображение
     */
    public function setAsMain()
    {
        // Снять флаг "главное" у других изображений этого товара
        static::updateAll(
            ['is_main' => 0],
            ['product_id' => $this->product_id]
        );

        // Установить флаг для текущего
        $this->is_main = 1;
        return $this->save(false);
    }
}
