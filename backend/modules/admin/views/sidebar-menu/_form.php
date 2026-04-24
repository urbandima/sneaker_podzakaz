<?php

use app\backend\modules\admin\models\SidebarMenuItem;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var SidebarMenuItem $model */
/** @var array $types */
/** @var array $parents */

$this->title = 'Создать пункт меню';
$this->params['breadcrumbs'][] = ['label' => 'Боковое меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div id="sidebar-form-config"
    data-type-divider="<?= SidebarMenuItem::TYPE_DIVIDER ?>"
    data-type-header="<?= SidebarMenuItem::TYPE_HEADER ?>"
    data-type-banner="<?= SidebarMenuItem::TYPE_BANNER ?>"
></div>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> К списку', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<div class="admin-card">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "<div class=\"admin-form-group\">{label}<div class=\"admin-form-input-wrap\">{input}</div>{error}</div>",
            'labelOptions' => ['class' => 'admin-form-label'],
            'inputOptions' => ['class' => 'admin-form-input'],
            'errorOptions' => ['class' => 'admin-form-error'],
        ],
    ]); ?>

    <div class="form-row">
        <div class="form-col-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Например: Каталог']) ?>
        </div>
        <div class="form-col-6">
            <?= $form->field($model, 'type')->dropDownList($types, [
                'prompt' => 'Выберите тип',
                'class' => 'admin-form-input',
                'onchange' => 'toggleTypeFields(this.value)'
            ]) ?>
        </div>
    </div>

    <div class="form-row" id="url-fields">
        <div class="form-col-6">
            <?= $form->field($model, 'url')->textInput(['maxlength' => true, 'placeholder' => '/catalog или https://example.com']) ?>
        </div>
        <div class="form-col-6">
            <?= $form->field($model, 'route')->textInput(['maxlength' => true, 'placeholder' => 'catalog/index']) ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-col-6">
            <?= $form->field($model, 'parent_id')->dropDownList($parents, ['prompt' => 'Без родителя', 'class' => 'admin-form-input']) ?>
        </div>
        <div class="form-col-6">
            <?= $form->field($model, 'icon')->textInput(['maxlength' => true, 'placeholder' => 'bi bi-house']) ?>
            <small class="form-hint">Иконки Bootstrap: <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a></small>
        </div>
    </div>

    <div class="form-row" id="image-field" style="display: none;">
        <div class="form-col-12">
            <?= $form->field($model, 'image')->textInput(['maxlength' => true, 'placeholder' => '/images/banner.jpg']) ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-col-3">
            <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
        </div>
        <div class="form-col-3">
            <?= $form->field($model, 'css_class')->textInput(['maxlength' => true, 'placeholder' => 'highlight']) ?>
        </div>
        <div class="form-col-2">
            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
        <div class="form-col-2">
            <?= $form->field($model, 'is_visible')->checkbox() ?>
        </div>
        <div class="form-col-2">
            <?= $form->field($model, 'target_blank')->checkbox() ?>
        </div>
    </div>

    <div class="admin-form-group" style="display:flex;gap:0.75rem;margin-top:1.5rem">
        <?= Html::submitButton('<i class="bi bi-check-lg"></i> Сохранить', ['class' => 'admin-btn admin-btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'admin-btn admin-btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

