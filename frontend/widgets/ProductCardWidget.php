<?php

namespace app\frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\catalog\models\Product;

/**
 * ProductCardWidget - Виджет для рендеринга карточки товара
 * 
 * Заменяет partial _product_card.php
 */
class ProductCardWidget extends Widget
{
    public $product;
    public $isCriticalCard = false;
    public $selectedSizesParam = null;
    public $selectedSizesArray = [];
    public $currentSizeSystem = 'eu';
    public $sizeField = 'size_eu';
    public $lazyPlaceholder = null;
    
    public function run()
    {
        if (!$this->product) {
            return '';
        }
        
        return $this->render('product-card', [
            'product' => $this->product,
            'isCriticalCard' => $this->isCriticalCard,
            'selectedSizesParam' => $this->selectedSizesParam,
            'selectedSizesArray' => $this->selectedSizesArray,
            'currentSizeSystem' => $this->currentSizeSystem,
            'sizeField' => $this->sizeField,
            'lazyPlaceholder' => $this->lazyPlaceholder,
        ]);
    }
}
