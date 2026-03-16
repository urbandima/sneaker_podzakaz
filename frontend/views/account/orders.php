<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var app\backend\modules\catalog\models\Order[] $orders */

$this->title = 'Мои заказы - СНИКЕРХЭД';
?>

<style>
.account-page {
    min-height: 100vh;
    background: #f3f4f6;
    padding: 2rem 0;
}

.account-container {
    width: 100%;
    max-width: none;
    margin: 0 auto;
    padding: 0 2rem;
}

@media (max-width: 768px) {
    .account-container {
        padding: 0 1rem;
    }
}

.account-header {
    margin-bottom: 2rem;
}

.account-header h1 {
    font-size: 1.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.account-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
}

@media (max-width: 991px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
}

.account-sidebar {
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.sidebar-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-info {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.user-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
    font-size: 1.5rem;
    color: #fff;
    font-weight: 700;
}

.user-name {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.125rem;
}

.user-email {
    color: #6b7280;
    font-size: 0.8125rem;
}

.account-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-menu li {
    margin-bottom: 0.375rem;
}

.account-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.account-menu a:hover {
    background: #f3f4f6;
    color: #000;
}

.account-menu a.active {
    background: #000;
    color: #fff;
}

.account-menu i {
    font-size: 1.125rem;
    width: 24px;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 1rem;
}

.logout-btn:hover {
    background: #fecaca;
}

.account-content {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.content-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.orders-count {
    color: #6b7280;
    font-size: 0.875rem;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-card {
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    padding: 1.5rem;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    display: block;
}

.order-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59,130,246,0.15);
    transform: translateY(-2px);
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.order-number {
    font-weight: 700;
    font-size: 1.125rem;
}

.order-status {
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.order-status.created { background: #dbeafe; color: #1d4ed8; }
.order-status.processing { background: #fef3c7; color: #d97706; }
.order-status.shipped { background: #e0e7ff; color: #4338ca; }
.order-status.delivered { background: #d1fae5; color: #059669; }
.order-status.completed { background: #d1fae5; color: #059669; }
.order-status.cancelled { background: #fee2e2; color: #dc2626; }

.order-card-body {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

@media (max-width: 768px) {
    .order-card-body {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .order-card-body {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
}

.order-info {
    display: flex;
    flex-direction: column;
}

.order-info-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.order-info-value {
    font-weight: 600;
}

.empty-orders {
    text-align: center;
    padding: 4rem 1rem;
}

.empty-orders i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
    display: block;
}

.empty-orders h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-orders p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.btn-catalog {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: #000;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-catalog:hover {
    background: #333;
    color: #fff;
}
</style>

<div class="account-page">
    <div class="account-container">
        <div class="account-header">
            <h1><i class="bi bi-bag"></i> Мои заказы</h1>
        </div>

        <div class="account-grid">
            <aside class="account-sidebar">
                <div class="sidebar-card">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                        </div>
                        <div class="user-name"><?= Html::encode($customer->getShortName()) ?></div>
                        <div class="user-email"><?= Html::encode($customer->email) ?></div>
                    </div>
                    
                    <ul class="account-menu">
                        <li><a href="<?= Url::to(['/account/profile']) ?>"><i class="bi bi-person"></i> Профиль</a></li>
                        <li><a href="<?= Url::to(['/account/orders']) ?>" class="active"><i class="bi bi-bag"></i> Мои заказы</a></li>
                        <li><a href="<?= Url::to(['/catalog/favorites']) ?>"><i class="bi bi-heart"></i> Избранное</a></li>
                        <li><a href="<?= Url::to(['/account/settings']) ?>"><i class="bi bi-gear"></i> Настройки</a></li>
                    </ul>
                    
                    <?= Html::beginForm(['/account/logout'], 'post') ?>
                        <button type="submit" class="logout-btn">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </button>
                    <?= Html::endForm() ?>
                </div>
            </aside>

            <main class="account-content">
                <div class="content-header">
                    <h2><i class="bi bi-bag-check"></i> История заказов</h2>
                    <?php if (!empty($orders)): ?>
                        <span class="orders-count"><?= count($orders) ?> <?= Yii::t('app', '{n, plural, =1{заказ} =2{заказа} =3{заказа} =4{заказа} other{заказов}}', ['n' => count($orders)]) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <i class="bi bi-bag-x"></i>
                        <h3>Заказов пока нет</h3>
                        <p>Перейдите в каталог и выберите понравившиеся товары</p>
                        <a href="<?= Url::to(['/catalog']) ?>" class="btn-catalog">
                            <i class="bi bi-grid"></i> Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <a href="<?= Url::to(['/account/order-view', 'id' => $order->id]) ?>" class="order-card">
                                <div class="order-card-header">
                                    <span class="order-number">Заказ #<?= $order->order_number ?: $order->id ?></span>
                                    <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                </div>
                                <div class="order-card-body">
                                    <div class="order-info">
                                        <span class="order-info-label">Дата заказа</span>
                                        <span class="order-info-value"><?= Yii::$app->formatter->asDate($order->created_at, 'long') ?></span>
                                    </div>
                                    <div class="order-info">
                                        <span class="order-info-label">Получатель</span>
                                        <span class="order-info-value"><?= Html::encode($order->client_name ?: '-') ?></span>
                                    </div>
                                    <div class="order-info">
                                        <span class="order-info-label">Доставка</span>
                                        <span class="order-info-value"><?= Html::encode($order->delivery_method ?: '-') ?></span>
                                    </div>
                                    <div class="order-info">
                                        <span class="order-info-label">Сумма</span>
                                        <span class="order-info-value"><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
