<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product|stdClass $product */
/** @var app\backend\modules\catalog\models\Product[]|stdClass[] $similarProducts */
/** @var bool $isFavorite */

use yii\helpers\Html;
use yii\helpers\Url;

// Функция для склонения русских слов
function pluralizeRu($number, $titles) {
    $cases = [2, 0, 1, 1, 1];
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

$title = $product->name ?? 'Товар';
$this->title = $title . ' — СНИКЕРХЭД';

// Сброс кэша браузера
$this->registerMetaTag(['http-equiv' => 'Cache-Control', 'content' => 'no-cache, no-store, must-revalidate']);
$this->registerMetaTag(['pragma' => 'no-cache']);
$this->registerMetaTag(['expires' => '0']);

$this->registerMetaTag(['name' => 'description', 'content' => 'Описание товара']);

// Получаем базовые данные товара
$productName = $product->name ?? 'Без названия';
$productPrice = $product->price ?? 0;
$productOldPrice = $product->old_price ?? null;
$productDescription = $product->description ?? '';
$brandName = $product->brand_name ?? '';

?>

<div class="container product-page">
    <!-- Хлебные крошки -->
    <nav class="breadcrumb">
        <a href="<?= Url::to(['/']) ?>">Главная</a>
        <span> / </span>
        <a href="<?= Url::to(['/catalog']) ?>">Каталог</a>
        <span> / </span>
        <span class="active"><?= Html::encode($productName) ?></span>
    </nav>

    <!-- Основная информация о товаре -->
    <div class="product-main">
        <div class="product-images">
            <div class="main-image">
                <?php if (!empty($product->image_url)): ?>
                    <img src="<?= Html::encode($product->image_url) ?>" alt="<?= Html::encode($productName) ?>">
                <?php else: ?>
                    <div class="no-image">Нет изображения</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-info">
            <h1 class="product-title"><?= Html::encode($productName) ?></h1>
            
            <?php if ($brandName): ?>
                <div class="product-brand">Бренд: <?= Html::encode($brandName) ?></div>
            <?php endif; ?>

            <div class="product-price">
                <span class="current-price"><?= number_format($productPrice, 0, '.', ' ') ?> BYN</span>
                <?php if ($productOldPrice && $productOldPrice > $productPrice): ?>
                    <span class="old-price"><?= number_format($productOldPrice, 0, '.', ' ') ?> BYN</span>
                    <span class="discount-percent">
                        -<?= round((($productOldPrice - $productPrice) / $productOldPrice) * 100) ?>%
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($productDescription): ?>
                <div class="product-description">
                    <h3>Описание</h3>
                    <p><?= Html::encode($productDescription) ?></p>
                </div>
            <?php endif; ?>

            <div class="product-actions">
                <button class="btn btn-primary add-to-cart">
                    В корзину
                </button>
                <button class="btn btn-secondary add-to-favorites">
                    В избранное
                </button>
            </div>
        </div>
    </div>

    <!-- Описание товара -->
    <?php if ($productDescription): ?>
    <div class="product-details">
        <h2>Подробное описание</h2>
        <div class="description-content">
            <?= $productDescription ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Похожие товары -->
    <?php if (!empty($similarProducts)): ?>
    <div class="similar-products">
        <h2>Похожие товары</h2>
        <div class="products-grid">
            <?php foreach ($similarProducts as $similar): ?>
                <div class="product-card">
                    <h3><?= Html::encode($similar->name ?? 'Товар') ?></h3>
                    <div class="price"><?= number_format($similar->price ?? 0, 0, '.', ' ') ?> BYN</div>
                    <a href="<?= Url::to(['/catalog/product', 'slug' => $similar->slug]) ?>" class="btn">Подробнее</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.product-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.breadcrumb {
    margin-bottom: 20px;
    font-size: 14px;
}

.breadcrumb a {
    color: #007bff;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.product-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.product-images img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.no-image {
    width: 100%;
    height: 400px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #6c757d;
}

.product-title {
    font-size: 28px;
    margin-bottom: 10px;
}

.product-brand {
    color: #6c757d;
    margin-bottom: 20px;
}

.product-price {
    margin-bottom: 20px;
}

.current-price {
    font-size: 24px;
    font-weight: bold;
    color: #28a745;
}

.old-price {
    text-decoration: line-through;
    color: #6c757d;
    margin-left: 10px;
}

.discount-percent {
    background: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 10px;
    font-size: 14px;
}

.product-actions {
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 10px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.product-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.product-card h3 {
    margin-bottom: 10px;
    font-size: 16px;
}

.product-card .price {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 15px;
}
</style>
