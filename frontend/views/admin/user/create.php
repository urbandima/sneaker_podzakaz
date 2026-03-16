<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\User $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Создать пользователя';
?>

<div class="admin-create-user">
    <div class="admin-create-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад к списку', ['/admin/user/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="admin-create-content">
        <div class="user-form-card">
            <div class="form-card-header">
                <h2><i class="bi bi-person-plus"></i> Данные пользователя</h2>
            </div>
            <div class="form-card-body">
                <?php $form = ActiveForm::begin([
                    'id' => 'create-user-form',
                    'options' => ['class' => 'needs-validation'],
                    'fieldConfig' => [
                        'options' => ['class' => 'form-group'],
                        'labelOptions' => ['class' => 'form-label'],
                        'inputOptions' => ['class' => 'form-control'],
                        'errorOptions' => ['class' => 'invalid-feedback'],
                    ],
                ]); ?>

                <div class="form-grid">
                    <div class="form-col">
                        <?= $form->field($model, 'username')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Введите имя пользователя',
                        ]) ?>
                    </div>

                    <div class="form-col">
                        <?= $form->field($model, 'email')->textInput([
                            'maxlength' => true,
                            'type' => 'email',
                            'placeholder' => 'example@mail.com',
                        ]) ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-col">
                        <?= $form->field($model, 'password')->passwordInput([
                            'maxlength' => true,
                            'placeholder' => 'Минимум 6 символов',
                        ]) ?>
                    </div>

                    <div class="form-col">
                        <?= $form->field($model, 'role')->dropDownList([
                            'manager' => 'Менеджер',
                            'logist' => 'Логист',
                            'admin' => 'Администратор',
                        ], ['prompt' => 'Выберите роль']) ?>
                    </div>
                </div>

                <div class="roles-info">
                    <h3><i class="bi bi-info-circle"></i> Информация о ролях</h3>
                    <div class="roles-list">
                        <div class="role-item">
                            <span class="role-badge role-badge--admin">Администратор</span>
                            <span class="role-description">Полный доступ ко всем функциям системы</span>
                        </div>
                        <div class="role-item">
                            <span class="role-badge role-badge--manager">Менеджер</span>
                            <span class="role-description">Создание и управление заказами</span>
                        </div>
                        <div class="role-item">
                            <span class="role-badge role-badge--logist">Логист</span>
                            <span class="role-description">Просмотр назначенных заказов и управление доставкой</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <?= Html::submitButton('<i class="bi bi-check-circle"></i> Создать пользователя', [
                        'class' => 'btn btn-success'
                    ]) ?>
                    <?= Html::a('Отмена', ['/admin/user/index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<style>
.admin-create-user {
    padding: 2rem 0;
}

.admin-create-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-create-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a2e;
}

.admin-create-content {
    max-width: 800px;
}

.user-form-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.form-card-header {
    background: linear-gradient(135deg, #4472C4 0%, #3a5cb8 100%);
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-card-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.form-card-body {
    padding: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1a1a2e;
    font-size: 0.875rem;
}

.form-control,
.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #4472C4;
    box-shadow: 0 0 0 3px rgba(68, 114, 196, 0.1);
}

.form-control::placeholder {
    color: #9ca3af;
}

.roles-info {
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.roles-info h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a2e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.roles-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.role-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.role-badge--admin {
    background: #dc3545;
    color: white;
}

.role-badge--manager {
    background: #4472C4;
    color: white;
}

.role-badge--logist {
    background: #ffc107;
    color: #212529;
}

.role-description {
    font-size: 0.875rem;
    color: #6c757d;
}

.form-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-outline-secondary {
    background: transparent;
    border: 1px solid #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .admin-create-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-card-body {
        padding: 1.5rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
