<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Вход';
?>
<div class="site-login">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4"><?= Html::encode($this->title) ?></h1>

                    <p class="text-center text-muted mb-4">Введите логин и пароль для входа в систему</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label'],
                        ],
                    ]); ?>

                        <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'password')->passwordInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'rememberMe')->checkbox([
                            'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                            'labelOptions' => ['class' => 'form-check-label'],
                            'inputOptions' => ['class' => 'form-check-input'],
                        ]) ?>

                        <div class="form-group mt-4">
                            <?= Html::submitButton('Войти', ['class' => 'btn btn-primary btn-lg w-100', 'name' => 'login-button']) ?>
                        </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>
        </div>
    </div>
</div>
