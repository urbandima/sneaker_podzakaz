<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\backend\modules\catalog\models\SizeGrid;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\SizeGrid $model */
/** @var app\backend\modules\catalog\models\Brand[] $brands */

$this->title = 'Новая размерная сетка';
$this->params['breadcrumbs'][] = ['label' => 'Управление', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Характеристики и размеры', 'url' => ['/admin/characteristic/index', 'tab' => 'sizes']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="size-grid-create">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/characteristic/index', 'tab' => 'sizes'], ['class' => 'btn btn-secondary']) ?>
    </div>

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
                        'placeholder' => 'Например: Стандартная EU, Nike маломерит'
                    ])->label('Название сетки <span class="text-danger">*</span>', ['encode' => false]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'brand_id')->dropDownList(
                        ArrayHelper::map($brands, 'id', 'name'),
                        ['prompt' => '-- Универсальная (для всех) --']
                    )->label('Бренд')->hint('Оставьте пустым для универсальной сетки') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'gender')->dropDownList(
                        SizeGrid::getGenderOptions(),
                        ['prompt' => '-- Выберите пол --']
                    )->label('Пол <span class="text-danger">*</span>', ['encode' => false]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'is_active')->checkbox()->label('Сетка активна') ?>
                </div>
            </div>

            <?= $form->field($model, 'description')->textarea([
                'rows' => 3,
                'placeholder' => 'Опишите особенности (например, "маломерит на 0.5")'
            ])->label('Описание') ?>

            <div class="form-group">
                <?= Html::submitButton('<i class="bi bi-check-circle"></i> Создать', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Отмена', ['/admin/characteristic/index', 'tab' => 'sizes'], ['class' => 'btn btn-outline-secondary ms-2']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
