<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Избранное';
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-wishlist">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковое меню личного кабинета -->
            <div class="account-sidebar">
                <div class="list-group">
                    <?= Html::a('Профиль', ['account/profile'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Мои заказы', ['account/orders'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Избранное', ['account/wishlist'], ['class' => 'list-group-item active']) ?>
                    <?= Html::a('Баллы лояльности', ['loyalty/index'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Возвраты', ['return/index'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Настройки', ['account/settings'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Выход', ['account/logout'], ['class' => 'list-group-item text-danger']) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="account-content">
                <h1><?= Html::encode($this->title) ?></h1>
                
                <?php if (empty($favorites)): ?>
                    <div class="empty-wishlist text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-heart" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h3>У вас пока нет избранных товаров</h3>
                        <p class="text-muted">Добавляйте товары в избранное, чтобы не потерять их</p>
                        <?= Html::a('Перейти в каталог', ['/catalog'], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <div class="wishlist-grid">
                        <!-- TODO: Здесь будет отображение избранных товаров -->
                        <div class="alert alert-info">
                            <strong>В разработке:</strong> Функционал избранного скоро будет доступен.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.account-wishlist {
    margin-top: 2rem;
}

.account-sidebar .list-group-item {
    border: none;
    border-radius: 0;
    border-left: 3px solid transparent;
}

.account-sidebar .list-group-item.active {
    background-color: #f8f9fa;
    border-left-color: #007bff;
    color: #007bff;
}

.account-sidebar .list-group-item:hover {
    background-color: #f8f9fa;
}

.empty-wishlist {
    background: #f8f9fa;
    border-radius: 8px;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}
</style>
