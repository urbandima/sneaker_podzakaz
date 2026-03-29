<?php

/**
 * SeoMetaBulkForm — Форма массового редактирования meta-тегов
 */
namespace app\backend\modules\seo\models;

use Yii;
use yii\base\Model;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Category;

class SeoMetaBulkForm extends Model
{
    public $entity_type; // 'product' или 'category'
    public $ids = []; // массив ID
    public $meta_title_pattern;
    public $meta_description_pattern;
    public $meta_keywords_pattern;
    public $append_to_existing = false;

    public function rules()
    {
        return [
            [['entity_type'], 'required'],
            [['entity_type'], 'in', 'range' => ['product', 'category']],
            [['ids'], 'each', 'rule' => ['integer']],
            [['meta_title_pattern', 'meta_description_pattern', 'meta_keywords_pattern'], 'string'],
            [['append_to_existing'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'entity_type' => 'Тип сущности',
            'ids' => 'ID (пусто = все)',
            'meta_title_pattern' => 'Шаблон Title',
            'meta_description_pattern' => 'Шаблон Description',
            'meta_keywords_pattern' => 'Шаблон Keywords',
            'append_to_existing' => 'Добавлять к существующему',
        ];
    }

    /**
     * Применить изменения
     * @return array Результаты
     */
    public function apply()
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if ($this->entity_type === 'product') {
            $query = Product::find()->where(['is_active' => true]);
            if (!empty($this->ids)) {
                $query->andWhere(['id' => $this->ids]);
            }
            $items = $query->all();

            foreach ($items as $product) {
                if ($this->updateEntity($product, $product->name, $product->brand_name ?? '')) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Товар #{$product->id}: " . json_encode($product->errors);
                }
            }
        } else {
            $query = Category::find()->where(['is_active' => true]);
            if (!empty($this->ids)) {
                $query->andWhere(['id' => $this->ids]);
            }
            $items = $query->all();

            foreach ($items as $category) {
                if ($this->updateEntity($category, $category->name, '')) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Обновить сущность
     */
    private function updateEntity($entity, $name, $brand)
    {
        $updated = false;

        if (!empty($this->meta_title_pattern)) {
            $title = $this->processPattern($this->meta_title_pattern, $name, $brand);
            if ($this->append_to_existing && $entity->meta_title) {
                $entity->meta_title = $entity->meta_title . ' ' . $title;
            } else {
                $entity->meta_title = $title;
            }
            $updated = true;
        }

        if (!empty($this->meta_description_pattern)) {
            $desc = $this->processPattern($this->meta_description_pattern, $name, $brand);
            if ($this->append_to_existing && $entity->meta_description) {
                $entity->meta_description = $entity->meta_description . ' ' . $desc;
            } else {
                $entity->meta_description = $desc;
            }
            $updated = true;
        }

        if (!empty($this->meta_keywords_pattern)) {
            $keywords = $this->processPattern($this->meta_keywords_pattern, $name, $brand);
            if ($this->append_to_existing && $entity->meta_keywords) {
                $entity->meta_keywords = $entity->meta_keywords . ', ' . $keywords;
            } else {
                $entity->meta_keywords = $keywords;
            }
            $updated = true;
        }

        if ($updated) {
            return $entity->save(false, ['meta_title', 'meta_description', 'meta_keywords']);
        }

        return true;
    }

    /**
     * Обработать шаблон
     */
    private function processPattern($pattern, $name, $brand)
    {
        $replacements = [
            '{name}' => $name,
            '{brand}' => $brand,
            '{site}' => 'СНИКЕРХЭД',
            '{price}' => '', // можно расширить
        ];

        return strtr($pattern, $replacements);
    }
}
