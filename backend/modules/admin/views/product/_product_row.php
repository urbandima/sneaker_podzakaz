<?php
/**
 * Partial: single product table row (used by index + infinite scroll)
 * @var app\backend\modules\catalog\models\Product $product
 */
use yii\helpers\Html;
use yii\helpers\Url;

$imageUrl = $product->getMainImageUrl();
$sizesCount = count($product->sizes ?? []);
$stockTotal = 0;
foreach ($product->sizes ?? [] as $sz) {
    $stockTotal += (int)($sz->stock ?? 0);
}

// Status
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
<tr data-href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>">
    <td style="padding:6px 8px">
        <div class="product-thumb-cell">
            <?php if ($imageUrl): ?>
                <img src="<?= Html::encode($imageUrl) ?>" alt="" class="product-thumb" loading="lazy">
            <?php else: ?>
                <div class="product-thumb product-thumb--empty"><i class="bi bi-image"></i></div>
            <?php endif; ?>
        </div>
    </td>
    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <div style="font-weight:600;line-height:1.3"><?= Html::encode($product->name) ?></div>
    </td>
    <td data-col="article" style="font-family:monospace;font-size:.75rem;color:var(--admin-text-secondary,#6b7280)">
        <?= Html::encode($product->vendor_code ?: $product->sku ?: '—') ?>
    </td>
    <td data-col="brand" style="white-space:nowrap">
        <?= Html::encode($product->brand->name ?? '—') ?>
    </td>
    <td data-col="price" style="font-weight:700;white-space:nowrap">
        <?= number_format($product->price ?? 0, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);font-weight:400">Br</span>
    </td>
    <td data-col="sizes" style="text-align:center;font-weight:600">
        <?= $sizesCount ?>
    </td>
    <td data-col="stock_total" style="text-align:center;font-weight:600">
        <?= $stockTotal ?>
    </td>
    <td data-col="status" style="padding:6px 8px">
        <span class="status-pill" style="background:<?= $statusPill['bg'] ?>;color:<?= $statusPill['color'] ?>">
            <?= $statusPill['label'] ?>
        </span>
    </td>
    <td style="padding:4px 6px">
        <a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>"
           class="admin-btn admin-btn-secondary"
           style="padding:.2rem .45rem;font-size:.875rem;">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
