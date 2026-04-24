<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\backend\modules\account\models\Customer;

$this->title = 'Настройки';
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-settings">
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
                    <?= Html::a('Настройки', ['account/settings'], ['class' => 'list-group-item active']) ?>
                    <?= Html::a('Выход', ['account/logout'], ['class' => 'list-group-item text-danger']) ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="account-content">
                <h1><?= Html::encode($this->title) ?></h1>
                
                <div class="row">
                    <div class="col-md-6">
                        <!-- Изменение личных данных -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Личные данные</h5>
                            </div>
                            <div class="card-body">
                                <?php $form = ActiveForm::begin(['id' => 'profile-form']); ?>
                                
                                <?= $form->field($customer, 'first_name')->textInput(['maxlength' => true]) ?>
                                
                                <?= $form->field($customer, 'last_name')->textInput(['maxlength' => true]) ?>
                                
                                <?= $form->field($customer, 'phone')->textInput(['maxlength' => true]) ?>
                                
                                <div class="form-group">
                                    <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-primary']) ?>
                                </div>
                                
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Изменение пароля -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Изменение пароля</h5>
                            </div>
                            <div class="card-body">
                                <form id="password-form" method="post" action="/account/settings">
                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                    <div class="form-group mb-3">
                                        <label for="current_password">Текущий пароль</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="new_password">Новый пароль</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="confirm_password">Подтвердите пароль</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-warning">Изменить пароль</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Настройки уведомлений -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Настройки уведомлений</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_orders" checked>
                                    <label class="form-check-label" for="email_orders">
                                        Уведомления о статусе заказа по email
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_promo" checked>
                                    <label class="form-check-label" for="email_promo">
                                        Информация об акциях и скидках
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_news">
                                    <label class="form-check-label" for="email_news">
                                        Новости и новинки
                                    </label>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <?= Html::submitButton('Сохранить настройки', ['class' => 'btn btn-outline-primary']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Удаление аккаунта -->
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">⚠️ Опасные действия</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Удаление аккаунта приведёт к потере всей истории заказов и персональных данных.</p>
                        <?= Html::a('Удалить аккаунт', '#', [
                            'class' => 'btn btn-outline-danger',
                            'data-bs-toggle' => 'modal',
                            'data-bs-target' => '#deleteAccountModal'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления аккаунта -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить свой аккаунт?</p>
                <p class="text-danger">Это действие необратимо!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger">Удалить аккаунт</button>
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
