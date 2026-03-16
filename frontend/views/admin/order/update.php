<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Редактировать заказ №' . $model->order_number;
?>

<div class="admin-update-order">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('← Назад к заказу', ['/admin/order/view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'order-form']); ?>

            <h5 class="mb-3">Информация о клиенте</h5>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_name')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'client_phone')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_email')->textInput(['maxlength' => true, 'type' => 'email']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'delivery_date')->textInput() ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'status')->dropDownList(
                        Yii::$app->settings->getStatuses(),
                        ['prompt' => 'Выберите статус']
                    ) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'comment')->textarea(['rows' => 3, 'placeholder' => 'Примечания к заказу...']) ?>
                </div>
            </div>

            <hr class="my-4">

            <?= $this->render('_order_items', [
                'orderItems' => $model->orderItems,
            ]) ?>

            <div class="form-group">
                <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-success btn-lg']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
