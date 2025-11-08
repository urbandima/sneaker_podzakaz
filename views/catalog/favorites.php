<?php

/** @var yii\web\View $this */
/** @var app\models\ProductFavorite[] $favorites */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Избранное - СНИКЕРХЭД';

// Подключаем только стили каталога (как в каталоге 1в1)
$this->registerCssFile('@web/css/catalog-clean.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/catalog-card.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="catalog-page">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs-nav">
        <div class="container">
            <ol class="breadcrumbs">
                <li><a href="<?= Url::to(['/site/index']) ?>"><i class="bi bi-house"></i> Главная</a></li>
                <li><i class="bi bi-chevron-right"></i></li>
                <li><a href="<?= Url::to(['/catalog/index']) ?>">Каталог</a></li>
                <li><i class="bi bi-chevron-right"></i></li>
                <li class="active">Избранное</li>
            </ol>
        </div>
    </nav>

    <div class="container">
        <div class="catalog-layout">
            <div class="catalog-main">
                <?php if (empty($favorites)): ?>
                    <div class="empty">
                        <i class="bi bi-heart"></i>
                        <h3>Избранное пустое</h3>
                        <p>Вы еще не добавили ни одного товара в избранное</p>
                        <a href="<?= Url::to(['/catalog/index']) ?>" style="display:inline-block;padding:0.75rem 1.5rem;background:#000;color:#fff;text-decoration:none;border-radius:8px;margin-top:1rem;">
                            Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Заголовок и счетчик (как в каталоге) -->
                    <div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 0.5rem 0;">Избранное</h1>
                            <p style="color:#666;margin:0;">Найдено товаров: <?= count($favorites) ?></p>
                        </div>
                    </div>

                    <!-- Сетка товаров (точно как в каталоге) -->
                    <div class="products-grid" id="favorites-container">
                        <?php foreach ($favorites as $favorite): ?>
                            <?php if ($favorite->product): ?>
                                <div class="favorite-product" data-favorite-id="<?= $favorite->id ?>" data-id="<?= $favorite->product->id ?>" data-product-id="<?= $favorite->product->id ?>">
                                    <?= $this->render('_product_card', ['product' => $favorite->product]) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php
// Регистрация JS (как в каталоге)
$this->registerJsFile('@web/js/favorites.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/catalog.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
