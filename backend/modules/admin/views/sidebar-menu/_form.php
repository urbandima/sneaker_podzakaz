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

<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="header-actions">
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['index'], ['class' => 'btn btn-outline']) ?>
    </div>
</div>

<div class="admin-card">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "<div class=\"form-group\">{label}{input}{error}</div>",
        ],
    ]); ?>

    <div class="form-row">
        <div class="form-col-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Например: Каталог']) ?>
        </div>
        <div class="form-col-6">
            <?= $form->field($model, 'type')->dropDownList($types, [
                'prompt' => 'Выберите тип',
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
            <?= $form->field($model, 'parent_id')->dropDownList($parents, ['prompt' => 'Без родителя']) ?>
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

    <div class="form-actions">
        <?= Html::submitButton('<i class="bi bi-check-lg"></i> Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-outline']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
function toggleTypeFields(type) {
    const urlFields = document.getElementById('url-fields');
    const imageField = document.getElementById('image-field');

    if (type === '<?= SidebarMenuItem::TYPE_DIVIDER ?>' || type === '<?= SidebarMenuItem::TYPE_HEADER ?>') {
        urlFields.style.display = 'none';
    } else {
        urlFields.style.display = 'flex';
    }

    if (type === '<?= SidebarMenuItem::TYPE_BANNER ?>') {
        imageField.style.display = 'flex';
    } else {
        imageField.style.display = 'none';
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('sidebarmenuitem-type');
    if (typeSelect) {
        toggleTypeFields(typeSelect.value);
    }
});
</script>

<style>
.admin-form .form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.admin-form .form-col-6 { flex: 0 0 calc(50% - 10px); }
.admin-form .form-col-3 { flex: 0 0 calc(25% - 15px); }
.admin-form .form-col-2 { flex: 0 0 calc(16.666% - 16.666px); }
.admin-form .form-col-12 { flex: 0 0 100%; }

.admin-form label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
}

.admin-form input[type="text"],
.admin-form input[type="number"],
.admin-form select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.admin-form input:focus,
.admin-form select:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.admin-form .form-hint {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .admin-form .form-row {
        flex-direction: column;
        gap: 0;
    }
    .admin-form .form-col-6,
    .admin-form .form-col-3,
    .admin-form .form-col-2 {
        flex: 0 0 100%;
    }
}
</style>
