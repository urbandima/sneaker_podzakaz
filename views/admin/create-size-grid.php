<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

$this->title = 'Создать размерную сетку';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = ['label' => 'Размерные сетки', 'url' => ['size-grids']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="size-grid-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> После создания сетки вы сможете добавить к ней размеры на странице редактирования.
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Например: Стандартная сетка, Nike маломерит'
                    ])->label('Название сетки <span class="text-danger">*</span>', ['encode' => false]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'brand_id')->dropDownList(
                        ArrayHelper::map($brands, 'id', 'name'),
                        ['prompt' => '-- Универсальная (для всех) --']
                    )->label('Бренд')->hint('Оставьте пустым для универсальной сетки') ?>
                </div>
            </div>

            <?= $form->field($model, 'gender')->dropDownList(
                \app\models\SizeGrid::getGenderOptions(),
                ['prompt' => '-- Выберите пол --']
            )->label('Пол <span class="text-danger">*</span>', ['encode' => false]) ?>

            <?= $form->field($model, 'description')->textarea([
                'rows' => 3,
                'placeholder' => 'Опишите особенности этой сетки, например "Маломерит на 0.5 размера"'
            ])->label('Описание') ?>

            <?= $form->field($model, 'is_active')->checkbox(['checked' => true])->label('Сетка активна') ?>

            <div class="form-group">
                <?= Html::submitButton('<i class="bi bi-check-circle"></i> Создать', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Отмена', ['size-grids'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
