<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\ChangePasswordForm $model */
/** @var app\models\User $user */

$this->title = 'Профиль пользователя';
?>

<div class="user-profile">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person-circle"></i> <?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <!-- Информация о пользователе -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th style="width: 40%">Логин:</th>
                            <td><strong><?= Html::encode($user->username) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Роль:</th>
                            <td>
                                <?php if ($user->role === 'admin'): ?>
                                    <span class="badge bg-danger">Администратор</span>
                                <?php elseif ($user->role === 'manager'): ?>
                                    <span class="badge bg-info">Менеджер</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Логист</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <?php if ($user->status == 10): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Аккаунт создан:</th>
                            <td><?= Yii::$app->formatter->asDatetime($user->created_at) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Смена пароля -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Смена пароля</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['id' => 'change-password-form']); ?>

                    <?= $form->field($model, 'old_password')->passwordInput([
                        'class' => 'form-control',
                        'placeholder' => 'Введите текущий пароль'
                    ]) ?>

                    <?= $form->field($model, 'new_password')->passwordInput([
                        'class' => 'form-control',
                        'placeholder' => 'Минимум 6 символов'
                    ]) ?>

                    <?= $form->field($model, 'new_password_repeat')->passwordInput([
                        'class' => 'form-control',
                        'placeholder' => 'Повторите новый пароль'
                    ]) ?>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        После смены пароля вы останетесь авторизованы в системе
                    </div>

                    <div class="form-group">
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сменить пароль', [
                            'class' => 'btn btn-warning w-100',
                            'name' => 'change-password-button'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-profile h1 {
    font-size: 1.875rem;
    font-weight: 600;
    color: #111827;
}

.card {
    border: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-bottom: none;
    padding: 1rem 1.25rem;
}

.card-header h5 {
    font-size: 1.125rem;
    font-weight: 600;
}

.table th {
    color: #6b7280;
    font-weight: 500;
}

.table td {
    color: #111827;
}

.form-control:focus {
    border-color: #fbbf24;
    box-shadow: 0 0 0 0.2rem rgba(251, 191, 36, 0.25);
}

.btn-warning {
    font-weight: 600;
}
</style>
