<?php

namespace app\backend\modules\catalog\controllers;

use yii\web\Controller;
use yii\data\Pagination;

class CatalogController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', [
            'products' => [],
            'pagination' => new Pagination(['totalCount' => 0]),
            'filters' => [],
            'h1' => 'Каталог товаров'
        ]);
    }
    
    public function actionBrands()
    {
        return $this->render('brands', ['brands' => []]);
    }
    
    public function actionFavorites()
    {
        return $this->render('favorites', ['favorites' => []]);
    }
}
