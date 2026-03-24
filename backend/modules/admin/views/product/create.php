<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Новый товар';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
</div>

<div class="admin-card">
    <?php $form = ActiveForm::begin(); ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div class="form-group">
            <label>Название товара</label>
            <?= Html::activeTextInput($model, 'name', ['class' => 'form-control']) ?>
        </div>
        
        <div class="form-group">
            <label>Бренд</label>
            <?= Html::activeDropDownList($model, 'brand_id', 
                \yii\helpers\ArrayHelper::map($brands, 'id', 'name'),
                ['class' => 'form-control', 'prompt' => 'Выберите бренд']) ?>
        </div>
        
        <div class="form-group">
            <label>Категория</label>
            <?= Html::activeDropDownList($model, 'category_id',
                \yii\helpers\ArrayHelper::map($categories, 'id', 'name'),
                ['class' => 'form-control', 'prompt' => 'Выберите категорию']) ?>
        </div>
        
        <div class="form-group">
            <label>Артикул (SKU)</label>
            <?= Html::activeTextInput($model, 'sku', ['class' => 'form-control']) ?>
        </div>
        
        <div class="form-group">
            <label>Цена, BYN</label>
            <?= Html::activeTextInput($model, 'price', ['type' => 'number', 'step' => '0.01', 'class' => 'form-control']) ?>
        </div>
        
        <div class="form-group">
            <label>Цена в юанях</label>
            <?= Html::activeTextInput($model, 'price_cny', ['type' => 'number', 'step' => '0.01', 'class' => 'form-control']) ?>
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <?= Html::activeTextarea($model, 'description', ['class' => 'form-control', 'rows' => 5]) ?>
        </div>
        
        <div class="form-group">
            <label>Статус активности</label>
            <?= Html::activeCheckbox($model, 'is_active', ['label' => false]) ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
        <?= Html::submitButton('Сохранить', ['class' => 'admin-btn admin-btn-primary']) ?>
        <?= Html::a('Отмена', ['/admin/product'], ['class' => 'admin-btn admin-btn-secondary']) ?>
    </div>
    
    <?php ActiveForm::end(); ?>
</div>

<style>
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
}
</style>
