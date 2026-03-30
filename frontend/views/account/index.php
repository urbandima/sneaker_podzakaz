<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Order[] $orders */
/** @var string|null $email */

$this->title = 'Личный кабинет - СНИКЕРХЭД';
?>

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
                        <h3 class="account-index-title">Последние заказы</h3>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <a href="<?= Url::to(['/order/view', 'token' => $order->token]) ?>" class="order-card order-card-link">
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
                        <a href="<?= Url::to(['/account/orders']) ?>" class="account-all-orders-link">
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
