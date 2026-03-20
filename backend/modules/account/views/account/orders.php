<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\backend\modules\checkout\models\Order;

$this->title = 'Мои заказы';
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-orders">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковое меню личного кабинета -->
            <div class="account-sidebar">
                <div class="list-group">
                    <?= Html::a('Профиль', ['account/profile'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Мои заказы', ['account/orders'], ['class' => 'list-group-item active']) ?>
                    <?= Html::a('Избранное', ['account/wishlist'], ['class' => 'list-group-item']) ?>
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
                
                <?php if (empty($orders)): ?>
                    <div class="empty-orders text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-box-seam" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h3>У вас пока нет заказов</h3>
                        <p class="text-muted">Сделайте первый заказ, чтобы увидеть его здесь</p>
                        <?= Html::a('Перейти в каталог', ['/catalog'], ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="card-title">
                                                Заказ #<?= $order->id ?>
                                                <span class="badge bg-<?= $order->getStatusClass() ?> ms-2">
                                                    <?= $order->getStatusLabel() ?>
                                                </span>
                                            </h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    от <?= Yii::$app->formatter->asDate($order->created_at) ?>
                                                    • Сумма: <?= Yii::$app->formatter->asCurrency($order->total_amount) ?>
                                                </small>
                                            </p>
                                            <?php if ($order->delivery_address): ?>
                                                <p class="card-text">
                                                    <small><strong>Адрес доставки:</strong> <?= Html::encode($order->delivery_address) ?></small>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?= Html::a('Подробнее', ['account/order-view', 'id' => $order->id], ['class' => 'btn btn-outline-primary btn-sm']) ?>
                                            <?php if ($order->canBePaid()): ?>
                                                <?= Html::a('Оплатить', ['order/view', 'token' => $order->token], ['class' => 'btn btn-success btn-sm ms-2']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (isset($pagination)): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <?= LinkPager::widget([
                                'pagination' => $pagination,
                                'options' => ['class' => 'pagination'],
                            ]) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
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

.empty-orders {
    background: #f8f9fa;
    border-radius: 8px;
}

.orders-list .card {
    transition: box-shadow 0.2s ease;
}

.orders-list .card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
