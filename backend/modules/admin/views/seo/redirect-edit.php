<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? 'Добавить редирект' : 'Редактировать редирект';
?>

<div class="seo-redirect-edit">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <?php $form = ActiveForm::begin(); ?>
    
    <?= $form->field($model, 'from_url')->textInput(['maxlength' => 500]) ?>
    <?= $form->field($model, 'to_url')->textInput(['maxlength' => 500]) ?>
    <?= $form->field($model, 'type')->dropDownList([
        301 => '301 (Постоянный)',
        302 => '302 (Временный)',
        404 => '404 (Не найдено)',
    ]) ?>
    <?= $form->field($model, 'is_active')->checkbox() ?>
    
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['redirects'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
    
    <?php ActiveForm::end(); ?>
</div>
