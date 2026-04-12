<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Мои заказы - СНИКЕРХЭД';
AppAsset::register($this);
$this->registerCss('
.order-card-badges { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.dp-badge { display:inline-block; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.dp-badge-green  { background:#d1fae5; color:#065f46; }
.dp-badge-blue   { background:#dbeafe; color:#1e40af; }
.dp-badge-yellow { background:#fef3c7; color:#92400e; }
.dp-badge-red    { background:#fee2e2; color:#991b1b; }
.order-info-eta  { background:#f0f9ff; border-radius:6px; padding:4px 8px; }
.order-info-value-eta { color:#1d4ed8; font-weight:700; }
');
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
                                    <div class="order-card-badges">
                                        <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                        <?php if (!empty($order->dp_status)): ?>
                                            <?php
                                            $dpColors = [
                                                'delivered' => 'dp-badge-green',
                                                'in_transit' => 'dp-badge-blue',
                                                'out_for_delivery' => 'dp-badge-blue',
                                                'arrived_in_country' => 'dp-badge-blue',
                                                'customs_clearance' => 'dp-badge-yellow',
                                                'customs_hold' => 'dp-badge-red',
                                                'problem' => 'dp-badge-red',
                                                'returned' => 'dp-badge-red',
                                            ];
                                            $dpLabels = [
                                                'created' => 'ДП: Создан',
                                                'accepted' => 'ДП: Принят',
                                                'in_transit' => 'ДП: В пути',
                                                'customs_clearance' => 'ДП: Таможня',
                                                'customs_hold' => 'ДП: Задержан',
                                                'arrived_in_country' => 'ДП: В РБ',
                                                'out_for_delivery' => 'ДП: Доставка',
                                                'delivered' => 'ДП: Доставлен',
                                                'returned' => 'ДП: Возврат',
                                                'problem' => 'ДП: Проблема',
                                            ];
                                            $dpColor = $dpColors[$order->dp_status] ?? 'dp-badge-blue';
                                            $dpLabel = $dpLabels[$order->dp_status] ?? ('ДП: ' . $order->dp_status);
                                            ?>
                                            <span class="dp-badge <?= $dpColor ?>"><?= Html::encode($dpLabel) ?></span>
                                        <?php endif; ?>
                                    </div>
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
                                    <?php if (!empty($order->estimated_delivery_date)): ?>
                                    <div class="order-info order-info-eta">
                                        <span class="order-info-label"><i class="bi bi-calendar-check"></i> Ожидается</span>
                                        <span class="order-info-value order-info-value-eta">
                                            <?= date('d.m.Y', strtotime($order->estimated_delivery_date)) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
