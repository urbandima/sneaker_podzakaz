<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Customer $customer
 */

$this->title = 'Редактирование покупателя #' . $customer->id;
$this->params['breadcrumbs'][] = ['label' => 'Покупатели', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $customer->getFullName(), 'url' => ['view', 'id' => $customer->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>

<div class="admin-page customer-update-page">
    <div class="page-header">
        <div>
            <p class="eyebrow">Покупатели</p>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="subtitle">Обновите информацию о покупателе, контактные данные и адрес доставки</p>
        </div>
        <div class="header-actions">
            <?= Html::a('<i class="bi bi-eye"></i> Просмотр', ['view', 'id' => $customer->id], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <div class="customer-form-shell">
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'customer-form'],
        ]); ?>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-person-badge"></i> Персональные данные</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <?= $form->field($customer, 'first_name')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($customer, 'last_name')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($customer, 'middle_name')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($customer, 'birth_date')->input('date') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($customer, 'gender')->dropDownList([
                            '' => 'Не выбран',
                            'male' => 'Мужской',
                            'female' => 'Женский',
                        ]) ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-envelope"></i> Контакты</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <?= $form->field($customer, 'email')->textInput(['maxlength' => true, 'disabled' => true]) ?>
                        <p class="form-hint">Email нельзя изменять из админки</p>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($customer, 'phone')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-geo-alt"></i> Адрес по умолчанию</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-4">
                        <?= $form->field($customer, 'default_country')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($customer, 'default_city')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($customer, 'default_postal_code')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-12">
                        <?= $form->field($customer, 'default_address')->textarea(['rows' => 2]) ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-card-text"></i> Документы</h3>
                </div>
                <div class="card-body row">
                    <div class="col-md-4">
                        <?= $form->field($customer, 'passport_series')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($customer, 'passport_number')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($customer, 'passport_issue_date')->input('date') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($customer, 'inn')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-bell"></i> Коммуникации</h3>
            </div>
            <div class="card-body row">
                <div class="col-md-6">
                    <?= $form->field($customer, 'subscribe_news')->checkbox(['label' => 'Подписка на новости']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($customer, 'subscribe_promo')->checkbox(['label' => 'Подписка на акции и промокоды']) ?>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <?= Html::submitButton('<i class="bi bi-save"></i> Сохранить изменения', ['class' => 'btn btn-primary btn-lg']) ?>
            <?= Html::a('Отмена', ['view', 'id' => $customer->id], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$css = <<<CSS
.customer-update-page .grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.customer-form-shell .card {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
.customer-form-shell .card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    background: #f9fafb;
}
.customer-form-shell .card-header h3 {
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.customer-form-shell .card-body {
    padding: 1.25rem;
}
.customer-form-shell .form-footer {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}
.customer-form .form-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: -0.5rem;
}
CSS;

$this->registerCss($css);
