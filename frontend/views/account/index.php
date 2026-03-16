<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order[] $orders */
/** @var string|null $email */

$this->title = 'Личный кабинет - СНИКЕРХЭД';
?>

<style>
.account-page {
    min-height: 100vh;
    background: #f3f4f6;
    padding: 2rem 0;
}

.account-page .container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 0 1rem;
}

.account-header {
    margin-bottom: 2rem;
}

.account-header h1 {
    font-size: 2rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    height: fit-content;
}

.account-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-menu li {
    margin-bottom: 0.5rem;
}

.account-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.account-menu a:hover,
.account-menu a.active {
    background: #f3f4f6;
    color: #000;
}

.account-menu a.active {
    background: #000;
    color: #fff;
}

.account-menu i {
    font-size: 1.25rem;
}

.account-content {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.welcome-section {
    text-align: center;
    padding: 3rem 1rem;
}

.welcome-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.welcome-icon i {
    font-size: 2.5rem;
    color: #fff;
}

.welcome-section h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-section p {
    color: #6b7280;
    margin-bottom: 2rem;
}

.find-orders-form {
    max-width: 400px;
    margin: 0 auto;
}

.find-orders-form .form-group {
    margin-bottom: 1rem;
}

.find-orders-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.find-orders-form input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.find-orders-form input:focus {
    outline: none;
    border-color: #3b82f6;
}

.find-orders-form .btn-find {
    width: 100%;
    padding: 1rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.find-orders-form .btn-find:hover {
    background: #333;
}

.orders-list {
    margin-top: 2rem;
}

.order-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
}

.order-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59,130,246,0.1);
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.order-number {
    font-weight: 700;
    font-size: 1.125rem;
}

.order-status {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.order-status.new { background: #dbeafe; color: #1d4ed8; }
.order-status.processing { background: #fef3c7; color: #d97706; }
.order-status.completed { background: #d1fae5; color: #059669; }
.order-status.cancelled { background: #fee2e2; color: #dc2626; }

.order-card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-date {
    color: #6b7280;
    font-size: 0.875rem;
}

.order-total {
    font-weight: 700;
    font-size: 1.125rem;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #f9fafb;
    border-radius: 12px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
}

.quick-link:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}

.quick-link i {
    font-size: 1.5rem;
    color: #3b82f6;
}

.quick-link span {
    font-weight: 600;
}
</style>

<div class="account-page">
    <div class="container">
        <div class="account-header">
            <h1><i class="bi bi-person-circle"></i> Личный кабинет</h1>
        </div>

        <div class="account-grid">
            <aside class="account-sidebar">
                <ul class="account-menu">
                    <li><a href="<?= Url::to(['/account/index']) ?>" class="active"><i class="bi bi-house"></i> Главная</a></li>
                    <li><a href="<?= Url::to(['/account/orders']) ?>"><i class="bi bi-bag"></i> Мои заказы</a></li>
                    <li><a href="<?= Url::to(['/catalog/history']) ?>"><i class="bi bi-clock-history"></i> История просмотров</a></li>
                    <li><a href="<?= Url::to(['/favorite/index']) ?>"><i class="bi bi-heart"></i> Избранное</a></li>
                    <li><a href="<?= Url::to(['/cart/index']) ?>"><i class="bi bi-cart3"></i> Корзина</a></li>
                </ul>
            </aside>

            <main class="account-content">
                <?php if (empty($email)): ?>
                    <div class="welcome-section">
                        <div class="welcome-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h2>Найдите свои заказы</h2>
                        <p>Введите email или телефон, указанный при оформлении заказа</p>
                        
                        <form class="find-orders-form" id="findOrdersForm">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" placeholder="example@mail.com">
                            </div>
                            <div class="form-group">
                                <label for="phone">или Телефон</label>
                                <input type="tel" id="phone" name="phone" placeholder="+375 29 123-45-67">
                            </div>
                            <button type="submit" class="btn-find">
                                <i class="bi bi-search"></i> Найти заказы
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <h2>Добро пожаловать!</h2>
                    <p>Ваш email: <strong><?= Html::encode($email) ?></strong></p>
                    
                    <?php if (!empty($orders)): ?>
                        <h3 style="margin-top: 2rem;">Последние заказы</h3>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <a href="<?= Url::to(['/order/view', 'token' => $order->token]) ?>" class="order-card" style="display: block; text-decoration: none; color: inherit;">
                                    <div class="order-card-header">
                                        <span class="order-number">Заказ #<?= $order->id ?></span>
                                        <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                    </div>
                                    <div class="order-card-body">
                                        <span class="order-date"><?= Yii::$app->formatter->asDate($order->created_at, 'long') ?></span>
                                        <span class="order-total"><?= Yii::$app->formatter->asCurrency($order->total, 'BYN') ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= Url::to(['/account/orders']) ?>" style="display: inline-block; margin-top: 1rem; color: #3b82f6; font-weight: 600;">
                            Все заказы →
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="quick-links">
                    <a href="<?= Url::to(['/catalog']) ?>" class="quick-link">
                        <i class="bi bi-grid"></i>
                        <span>Каталог товаров</span>
                    </a>
                    <a href="<?= Url::to(['/favorite/index']) ?>" class="quick-link">
                        <i class="bi bi-heart"></i>
                        <span>Избранное</span>
                    </a>
                    <a href="<?= Url::to(['/catalog/history']) ?>" class="quick-link">
                        <i class="bi bi-clock-history"></i>
                        <span>История просмотров</span>
                    </a>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
document.getElementById('findOrdersForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    
    if (!email && !phone) {
        if (window.NotificationManager) {
            NotificationManager.warning('Укажите email или телефон');
        }
        return;
    }
    
    const formData = new FormData();
    formData.append('email', email);
    formData.append('phone', phone);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        formData.append('_csrf', csrfToken.content);
    }
    
    fetch('/account/find-orders', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.count > 0) {
                window.location.reload();
            } else {
                if (window.NotificationManager) {
                    NotificationManager.info('Заказы не найдены');
                }
            }
        } else {
            if (window.NotificationManager) {
                NotificationManager.error(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>
