<?php

/**
 * ProductTagsWidget — Виджет отображения тегов товара
 * 
 * НАЗНАЧЕНИЕ:
 * Отображение тегов на карточке товара или на странице товара.
 * 
 * ПАРАМЕТРЫ:
 * - product: Product — товар для отображения тегов
 * - style: string — стиль отображения (badges|chips|pills)
 * - limit: int — максимальное количество тегов (0 = все)
 * - showLinks: bool — делать ли теги ссылками
 */

namespace app\frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use app\backend\modules\catalog\models\Product;

class ProductTagsWidget extends Widget
{
    /** @var Product */
    public $product;

    /** @var string Стиль отображения */
    public $style = 'badges';

    /** @var int Лимит тегов (0 = без лимита) */
    public $limit = 0;

    /** @var bool Показывать ли теги как ссылки */
    public $showLinks = true;

    /** @var string Дополнительный CSS класс */
    public $containerClass = '';

    /** @var string Заголовок блока */
    public $title = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        if ($this->product === null) {
            throw new \InvalidArgumentException('Property "product" is required');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $tags = $this->getTags();
        
        if (empty($tags)) {
            return '';
        }

        return $this->renderTags($tags);
    }

    /**
     * Получить теги товара
     * @return array
     */
    protected function getTags()
    {
        $tags = $this->product->tags;
        
        if ($this->limit > 0 && count($tags) > $this->limit) {
            return array_slice($tags, 0, $this->limit);
        }
        
        return $tags;
    }

    /**
     * Отрендерить теги
     * @param array $tags
     * @return string
     */
    protected function renderTags($tags)
    {
        $items = [];
        
        foreach ($tags as $tag) {
            $items[] = $this->renderTag($tag);
        }

        $containerClass = 'product-tags product-tags--' . $this->style;
        if ($this->containerClass) {
            $containerClass .= ' ' . $this->containerClass;
        }

        $html = '<div class="' . $containerClass . '">';
        
        if ($this->title) {
            $html .= '<h4 class="product-tags__title">' . Html::encode($this->title) . '</h4>';
        }
        
        $html .= '<div class="product-tags__list">';
        $html .= implode('', $items);
        $html .= '</div></div>';

        $this->registerAssets();

        return $html;
    }

    /**
     * Отрендерить один тег
     */
    protected function renderTag($tag)
    {
        $content = Html::encode($tag->name);
        
        $inlineStyle = '';
        if ($tag->color) {
            $inlineStyle = 'background-color: ' . $tag->color . '; color: #fff;';
        }

        $class = 'product-tag product-tag--' . $this->style;
        
        if ($this->showLinks) {
            return Html::a($content, ['/catalog/search/tag', 'slug' => $tag->slug], [
                'class' => $class,
                'style' => $inlineStyle ?: null,
            ]);
        }

        return Html::tag('span', $content, [
            'class' => $class,
            'style' => $inlineStyle ?: null,
        ]);
    }

    /**
     * Регистрация CSS
     */
    protected function registerAssets()
    {
        $css = ".product-tags { margin: 12px 0; }
.product-tags__title { font-size: 13px; font-weight: 600; color: #6b7280; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px; }
.product-tags__list { display: flex; flex-wrap: wrap; gap: 6px; }
.product-tag--badges { display: inline-block; padding: 4px 10px; background: #f3f4f6; color: #374151; border-radius: 20px; font-size: 12px; text-decoration: none; transition: all 0.2s; font-weight: 500; }
.product-tag--badges:hover { background: #000; color: #fff; }
.product-tag--chips { display: inline-flex; align-items: center; padding: 6px 12px; background: #f3f4f6; border-radius: 16px; font-size: 12px; text-decoration: none; transition: all 0.2s; }
.product-tag--chips:hover { opacity: 0.8; transform: translateY(-1px); }
.product-tag--pills { display: inline-block; padding: 3px 10px; border: 1px solid #e5e7eb; background: #fff; color: #374151; border-radius: 4px; font-size: 11px; text-decoration: none; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.3px; }
.product-tag--pills:hover { border-color: #000; color: #000; }
.product-tags--compact .product-tags__list { gap: 4px; }
.product-tags--compact .product-tag { padding: 2px 8px; font-size: 11px; }";

        $this->view->registerCss($css);
    }
}
