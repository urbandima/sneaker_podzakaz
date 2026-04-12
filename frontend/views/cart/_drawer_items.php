<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $items app\backend\modules\cart\models\Cart[] */
?>

<?php if (empty($items)): ?>
    <div class="cart-empty">
        <i class="bi bi-bag"></i>
        <p>Ваша корзина пуста</p>
        <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-primary" style="margin-top: 16px;" onclick="closeCartDrawer()">Перейти в каталог</a>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <?php $product = $item->product; ?>
        <?php if ($product): ?>
            <div class="cart-item" data-cart-id="<?= $item->id ?>">
                <img src="<?= Html::encode($product->getMainImageUrl()) ?>" alt="<?= Html::encode($product->name) ?>" class="cart-item-image">
                
                <div class="cart-item-info">
                    <div class="cart-item-brand"><?= Html::encode($product->brand->name ?? '') ?></div>
                    <a href="<?= Url::to(['/catalog/product', 'slug' => $product->slug]) ?>" class="cart-item-title" onclick="closeCartDrawer()">
                        <?= Html::encode($product->name) ?>
                    </a>
                    
                    <?php if ($item->size): ?>
                        <div class="cart-item-size">Размер: <?= Html::encode($item->size) ?></div>
                    <?php endif; ?>
                    
                    <div class="cart-item-actions">
                        <div class="quantity-control item-quantity">
                            <button class="qty-btn" onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity - 1 ?>)" <?= $item->quantity <= 1 ? 'disabled' : '' ?>>
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="qty-input" value="<?= $item->quantity ?>" readonly>
                            <button class="qty-btn" onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity + 1 ?>)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        
                        <div class="cart-item-price subtotal">
                            <?= Yii::$app->formatter->asCurrency($item->getSubtotal(), 'BYN') ?>
                        </div>
                        
                        <button class="cart-item-remove" onclick="removeCartItem(<?= $item->id ?>)" aria-label="Удалить">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
