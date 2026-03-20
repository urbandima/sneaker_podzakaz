<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\backend\modules\checkout\models\Order;

$this->title = 'Заказ #' . $order->id;
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/index']];
$this->params['breadcrumbs'][] = ['label' => 'Мои заказы', 'url' => ['account/orders']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-order-view">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковое меню личного кабинета -->
            <div class="account-sidebar">
                <div class="list-group">
                    <?= Html::a('Профиль', ['account/profile'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Мои заказы', ['account/orders'], ['class' => 'list-group-item']) ?>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><?= Html::encode($this->title) ?></h1>
                    <div>
                        <span class="badge bg-<?= $order->getStatusClass() ?> fs-6">
                            <?= $order->getStatusLabel() ?>
                        </span>
                    </div>
                </div>
                
                <!-- Информация о заказе -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Информация о заказе</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Номер заказа:</strong> #<?= $order->id ?></p>
                                <p><strong>Дата создания:</strong> <?= Yii::$app->formatter->asDateTime($order->created_at) ?></p>
                                <p><strong>Статус:</strong> 
                                    <span class="badge bg-<?= $order->getStatusClass() ?>">
                                        <?= $order->getStatusLabel() ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Сумма заказа:</strong> <?= Yii::$app->formatter->asCurrency($order->total_amount) ?></p>
                                <p><strong>Доставка:</strong> <?= Yii::$app->formatter->asCurrency($order->delivery_cost) ?></p>
                                <p><strong>Итого:</strong> <?= Yii::$app->formatter->asCurrency($order->total_amount + $order->delivery_cost) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Информация о доставке -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Информация о доставке</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Способ доставки:</strong> <?= $order->delivery_method ?></p>
                                <p><strong>Адрес доставки:</strong> <?= Html::encode($order->delivery_address) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Имя получателя:</strong> <?= Html::encode($order->customer_name) ?></p>
                                <p><strong>Телефон:</strong> <?= Html::encode($order->customer_phone) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Товары в заказе -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Товары в заказе</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th>Цена</th>
                                        <th>Количество</th>
                                        <th>Сумма</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order->orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= $item->product->image ?? '/images/placeholder.png' ?>" 
                                                         alt="<?= Html::encode($item->product->name) ?>" 
                                                         class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <strong><?= Html::encode($item->product->name) ?></strong>
                                                        <?php if ($item->size): ?>
                                                            <br><small class="text-muted">Размер: <?= Html::encode($item->size) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= Yii::$app->formatter->asCurrency($item->price) ?></td>
                                            <td><?= $item->quantity ?></td>
                                            <td><?= Yii::$app->formatter->asCurrency($item->price * $item->quantity) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Итого:</th>
                                        <th><?= Yii::$app->formatter->asCurrency($order->product_price) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Действия -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <?= Html::a('← Вернуться к заказам', ['account/orders'], ['class' => 'btn btn-outline-secondary']) ?>
                            <?php if ($order->canBePaid()): ?>
                                <?= Html::a('Оплатить заказ', ['order/view', 'token' => $order->token], ['class' => 'btn btn-success']) ?>
                            <?php endif; ?>
                            <?php if ($order->canBeReturned()): ?>
                                <?= Html::a('Оформить возврат', ['return/create', 'order_id' => $order->id], ['class' => 'btn btn-warning']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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

.table img {
    border-radius: 4px;
}
</style>
