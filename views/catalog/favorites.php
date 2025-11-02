<?php

/** @var yii\web\View $this */
/** @var app\models\ProductFavorite[] $favorites */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Избранное - СНИКЕРХЭД';
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="<?= Url::to(['/site/index']) ?>">Главная</a>
            <span>/</span>
            <a href="<?= Url::to(['/catalog/index']) ?>">Каталог</a>
            <span>/</span>
            <span>Избранное</span>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Избранное</h1>
            <p class="page-subtitle">Товары, которые вам понравились</p>
        </div>

        <!-- Favorites Content -->
        <div class="favorites-content">
            <?php if (empty($favorites)): ?>
                <div class="empty-favorites">
                    <i class="bi bi-heart"></i>
                    <h2>Избранное пустое</h2>
                    <p>Вы еще не добавили ни одного товара в избранное</p>
                    <a href="<?= Url::to(['/catalog/index']) ?>" class="btn-primary">
                        Перейти в каталог
                    </a>
                </div>
            <?php else: ?>
                <div class="favorites-stats">
                    <span>Всего товаров: <strong><?= count($favorites) ?></strong></span>
                    <div class="stats-actions">
                        <button class="btn-share-wishlist" onclick="wishlistShare.share()">
                            <i class="bi bi-share-fill"></i>
                            Поделиться списком
                        </button>
                        <button class="btn-clear-favorites" onclick="clearAllFavorites()">
                            <i class="bi bi-trash"></i>
                            Очистить избранное
                        </button>
                    </div>
                </div>

                <div class="products-grid" id="favorites-container">
                    <?php foreach ($favorites as $favorite): ?>
                        <?php if ($favorite->product): ?>
                            <div class="favorite-item favorite-product" data-favorite-id="<?= $favorite->id ?>" data-id="<?= $favorite->product->id ?>" data-product-id="<?= $favorite->product->id ?>">
                                <?= $this->render('_product_card', ['product' => $favorite->product]) ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="catalog-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.</p>
        </div>
    </footer>
</div>

<style>
.breadcrumbs {
    padding: 1rem 0;
    font-size: 0.875rem;
    color: #666666;
}

.breadcrumbs a {
    color: #666666;
    text-decoration: none;
}

.breadcrumbs a:hover {
    color: #000000;
}

.breadcrumbs span {
    margin: 0 0.5rem;
}

.page-header {
    padding: 2rem 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 0.5rem;
    letter-spacing: -1px;
}

.page-subtitle {
    font-size: 1.125rem;
    color: #666666;
}

.favorites-content {
    padding: 2rem 0;
    min-height: 60vh;
}

.empty-favorites {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-favorites i {
    font-size: 6rem;
    color: #e5e7eb;
    margin-bottom: 1.5rem;
}

.empty-favorites h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: #000000;
}

.empty-favorites p {
    font-size: 1.125rem;
    color: #666666;
    margin-bottom: 2rem;
}

.btn-primary {
    display: inline-block;
    padding: 1rem 2rem;
    background: #000000;
    color: #ffffff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #333333;
    transform: translateY(-2px);
}

.favorites-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.favorites-stats span {
    font-size: 1rem;
    color: #666666;
}

.favorites-stats strong {
    color: #000000;
}

.stats-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-share-wishlist {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 600;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-share-wishlist:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-clear-favorites {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #ef4444;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-clear-favorites:hover {
    background: #fef2f2;
    border-color: #ef4444;
}

/* Share Modal */
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.3s;
}

.share-modal-content {
    background: #fff;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    padding: 2rem;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.share-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #f3f4f6;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.2s;
}

.share-close:hover {
    background: #000;
    color: #fff;
    transform: rotate(90deg);
}

.share-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.share-header i {
    font-size: 3rem;
    color: #3b82f6;
    margin-bottom: 0.5rem;
}

.share-header h3 {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.share-header p {
    color: #666;
    font-size: 0.9375rem;
}

.share-link-box {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.share-link-box input {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    background: #f9fafb;
}

.btn-copy {
    padding: 0.75rem 1.25rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-copy:hover {
    background: #333;
}

.share-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.share-btn {
    padding: 0.75rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
    font-size: 0.9375rem;
}

.share-whatsapp {
    background: #25D366;
    color: #fff;
}

.share-whatsapp:hover {
    background: #128C7E;
}

.share-telegram {
    background: #0088cc;
    color: #fff;
}

.share-telegram:hover {
    background: #006699;
}

.share-vk {
    background: #0077FF;
    color: #fff;
}

.share-vk:hover {
    background: #0055CC;
}

.share-email {
    background: #f3f4f6;
    color: #000;
}

.share-email:hover {
    background: #e5e7eb;
}

.share-qr {
    text-align: center;
}

.btn-generate-qr {
    padding: 0.75rem 1.25rem;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-generate-qr:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

#qrCodeContainer {
    margin-top: 1rem;
}

.products-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.favorite-item {
    position: relative;
}

@media (min-width: 576px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

<script>
function clearAllFavorites() {
    if (!confirm('Вы уверены, что хотите очистить все избранное?')) {
        return;
    }
    
    // TODO: Реализовать через AJAX
    const favoriteItems = document.querySelectorAll('.favorite-item');
    favoriteItems.forEach(item => {
        const productId = item.dataset.productId;
        removeFavorite(productId);
    });
    
    setTimeout(() => {
        location.reload();
    }, 500);
}

function removeFavorite(productId) {
    // TODO: AJAX запрос на удаление
    console.log('Remove from favorites:', productId);
}
</script>

<?php
$this->registerJsFile('@web/js/catalog.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/wishlist-share.js', ['position' => \yii\web\View::POS_END]);
?>
