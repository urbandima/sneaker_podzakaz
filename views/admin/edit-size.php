<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Product $product */
/** @var app\models\ProductSize $size */

$this->title = 'Редактировать размер: ' . $product->name;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = ['label' => $product->name, 'url' => ['view-product', 'id' => $product->id]];
$this->params['breadcrumbs'][] = 'Редактировать размер';
?>

<div class="size-edit">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($size, 'us_size')->textInput([
                        'placeholder' => '9.5'
                    ])->label('US размер') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($size, 'eu_size')->textInput([
                        'placeholder' => '43'
                    ])->label('EU размер') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($size, 'uk_size')->textInput([
                        'placeholder' => '8.5'
                    ])->label('UK размер') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($size, 'cm_size')->textInput([
                        'type' => 'number',
                        'step' => '0.1',
                        'placeholder' => '27.5'
                    ])->label('CM (длина стопы)') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($size, 'size')->textInput([
                        'placeholder' => 'M, L, XL или 42'
                    ])->label('Размер (общее обозначение)') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($size, 'stock')->textInput([
                        'type' => 'number',
                        'min' => '0'
                    ])->label('Остаток на складе') ?>
                </div>
            </div>

            <?= $form->field($size, 'is_available')->checkbox()->label('Размер доступен для заказа') ?>

            <div class="form-group">
                <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Отмена', ['view-product', 'id' => $product->id], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
