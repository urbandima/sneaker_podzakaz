<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Мои заказы - СНИКЕРХЭД';
?>

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
