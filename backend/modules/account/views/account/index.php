<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Личный кабинет';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-index">
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
                <h1><?= Html::encode($this->title) ?></h1>
                
                <?php if (Yii::$app->user->isGuest): ?>
                    <!-- Для неавторизованных пользователей -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Вход в личный кабинет</h5>
                                    <p class="card-text">Войдите, чтобы управлять заказами и профилем</p>
                                    <?= Html::a('Войти', ['account/login'], ['class' => 'btn btn-primary']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Новый клиент?</h5>
                                    <p class="card-text">Зарегистрируйтесь для доступа ко всем функциям</p>
                                    <?= Html::a('Регистрация', ['account/register'], ['class' => 'btn btn-outline-primary']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Быстрый доступ к заказам -->
                    <div class="mt-4">
                        <h3>Быстрый доступ к заказам</h3>
                        <p>Если у вас есть заказ, но нет аккаунта, вы можете найти его по email или телефону</p>
                        <?= Html::a('Найти заказы', ['account/find-orders'], ['class' => 'btn btn-secondary']) ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Для авторизованных пользователей -->
                    <div class="welcome-section">
                        <h3>Добро пожаловать в личный кабинет!</h3>
                        <p>Здесь вы можете управлять своим профилем, отслеживать заказы и просматривать историю покупок</p>
                    </div>
                    
                    <!-- Быстрые действия -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-box-seam" style="font-size: 2rem; color: #007bff;"></i>
                                    <h5 class="card-title mt-2">Мои заказы</h5>
                                    <p class="card-text">Просмотр истории заказов</p>
                                    <?= Html::a('Перейти', ['account/orders'], ['class' => 'btn btn-primary']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-person" style="font-size: 2rem; color: #28a745;"></i>
                                    <h5 class="card-title mt-2">Профиль</h5>
                                    <p class="card-text">Редактирование личных данных</p>
                                    <?= Html::a('Перейти', ['account/profile'], ['class' => 'btn btn-success']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-heart" style="font-size: 2rem; color: #dc3545;"></i>
                                    <h5 class="card-title mt-2">Избранное</h5>
                                    <p class="card-text">Сохраненные товары</p>
                                    <?= Html::a('Перейти', ['account/wishlist'], ['class' => 'btn btn-danger']) ?>
                                </div>
                            </div>
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

.account-sidebar .list-group-item:hover {
    background-color: #f8f9fa;
}

.welcome-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}
</style>
