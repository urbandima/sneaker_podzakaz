<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\backend\modules\account\models\Customer;

$this->title = 'Профиль';
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-profile">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковое меню личного кабинета -->
            <div class="account-sidebar">
                <div class="list-group">
                    <?= Html::a('Профиль', ['account/profile'], ['class' => 'list-group-item active']) ?>
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
                <h1><?= Html::encode($this->title) ?></h1>
                
                <?php if ($customer): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Личные данные</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Имя:</strong> <?= Html::encode($customer->first_name) ?></p>
                                    <p><strong>Фамилия:</strong> <?= Html::encode($customer->last_name) ?></p>
                                    <p><strong>Email:</strong> <?= Html::encode($customer->email) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Телефон:</strong> <?= Html::encode($customer->phone) ?></p>
                                    <p><strong>Дата регистрации:</strong> <?= Yii::$app->formatter->asDate($customer->created_at) ?></p>
                                    <p><strong>Статус:</strong> 
                                        <span class="badge bg-success">Активен</span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <?= Html::a('Редактировать профиль', ['account/settings'], ['class' => 'btn btn-primary']) ?>
                                <?= Html::a('История заказов', ['account/orders'], ['class' => 'btn btn-outline-primary']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary"><?= $customer->orders_count ?></h3>
                                    <p class="card-text">Всего заказов</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success"><?= count(array_filter($orders ?? [], fn($order) => in_array($order->status ?? '', ['delivered', 'completed']))) ?></h3>
                                    <p class="card-text">Завершено</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-info"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></h3>
                                    <p class="card-text">Сумма покупок</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h5>Профиль не найден</h5>
                        <p>Ваши данные не найдены в системе. Пожалуйста, войдите или зарегистрируйтесь.</p>
                        <div class="mt-3">
                            <?= Html::a('Войти', ['account/login'], ['class' => 'btn btn-primary me-2']) ?>
                            <?= Html::a('Регистрация', ['account/register'], ['class' => 'btn btn-outline-primary']) ?>
                        </div>
                    </div>
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
</style>
