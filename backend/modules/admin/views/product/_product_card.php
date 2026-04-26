<?php
/**
 * Partial: single product grid card (used by index + infinite scroll)
 * @var app\backend\modules\catalog\models\Product $product
 */
use yii\helpers\Html;
use yii\helpers\Url;

$imageUrl = $product->getMainImageUrl();
$sizesCount = count($product->sizes ?? []);

$isActive = (bool)$product->is_active;
$isOutOfStock = $product->stock_status === 'out_of_stock';
if ($isActive && !$isOutOfStock) {
    $statusPill = ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Активен'];
} elseif ($isOutOfStock) {
    $statusPill = ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'Нет в наличии'];
} else {
    $statusPill = ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Архив'];
}
?>
<a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>" class="product-card">
    <div class="product-card__img-wrap">
        <?php if ($imageUrl): ?>
            <img src="<?= Html::encode($imageUrl) ?>" alt="" class="product-card__img" loading="lazy">
        <?php else: ?>
            <div class="product-card__img product-card__img--empty"><i class="bi bi-image" style="font-size:2rem;opacity:.3"></i></div>
        <?php endif; ?>
        <span class="product-card__status" style="background:<?= $statusPill['bg'] ?>;color:<?= $statusPill['color'] ?>">
            <?= $statusPill['label'] ?>
        </span>
    </div>
    <div class="product-card__body">
        <div class="product-card__name"><?= Html::encode($product->name) ?></div>
        <div class="product-card__meta"><?= Html::encode($product->brand->name ?? '') ?></div>
        <div class="product-card__bottom">
            <span class="product-card__price"><?= number_format($product->price ?? 0, 2) ?> Br</span>
            <span class="product-card__sizes"><?= $sizesCount ?> разм.</span>
        </div>
    </div>
</a>
