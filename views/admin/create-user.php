<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Создать пользователя';
?>

<div class="admin-create-user">
    <div class="mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left me-2"></i>Назад к списку', ['users'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'create-user-form',
                'options' => ['class' => 'needs-validation'],
            ]); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'username')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Введите имя пользователя',
                        'class' => 'form-control'
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput([
                        'maxlength' => true,
                        'type' => 'email',
                        'placeholder' => 'example@mail.com',
                        'class' => 'form-control'
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'password')->passwordInput([
                        'maxlength' => true,
                        'placeholder' => 'Минимум 6 символов',
                        'class' => 'form-control'
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'role')->dropDownList([
                        'manager' => 'Менеджер',
                        'logist' => 'Логист',
                        'admin' => 'Администратор',
                    ], ['prompt' => 'Выберите роль', 'class' => 'form-select']) ?>
                </div>
            </div>

            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle me-2"></i>Информация о ролях:</h6>
                <ul class="mb-0 small">
                    <li><strong>Администратор</strong> - полный доступ ко всем функциям системы</li>
                    <li><strong>Менеджер</strong> - создание и управление заказами</li>
                    <li><strong>Логист</strong> - просмотр назначенных заказов и управление доставкой</li>
                </ul>
            </div>

            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="bi bi-check-circle me-2"></i>Создать пользователя', [
                    'class' => 'btn btn-success'
                ]) ?>
                <?= Html::a('Отмена', ['users'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
