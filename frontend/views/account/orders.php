<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Мои заказы - СНИКЕРХЭД';
AppAsset::register($this);
?>

<div class="account-page">
    <div class="account-container">
        <div class="account-header">
            <h1><i class="bi bi-bag"></i> Мои заказы</h1>
        </div>

        <div class="account-grid">
            <?= $this->render('_sidebar', [
                'customer' => $customer,
                'activePage' => 'orders',
            ]) ?>

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
                        <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-primary">
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
