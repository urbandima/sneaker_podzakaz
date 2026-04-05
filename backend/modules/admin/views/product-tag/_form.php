<?php
/**
 * Форма создания/редактирования тега
 * 
 * @var ProductTag $model
 * @var string $title
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$isCreate = $model->isNewRecord;
$this->title = $isCreate ? 'Создать тег' : 'Редактировать тег: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Теги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $isCreate ? 'Создание' : 'Редактирование';
?>

<div class="product-tag-form">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('← Назад к списку', ['index'], ['class' => 'btn btn-link']) ?>
    </div>

    <div class="form-container">
        <?php $form = ActiveForm::begin([
            'id' => 'tag-form',
            'options' => ['class' => 'tag-form'],
        ]); ?>

        <div class="form-row">
            <div class="form-col form-col-main">
                <?= $form->field($model, 'name')->textInput([
                    'maxlength' => 100,
                    'placeholder' => 'Например: Новинка, Хит продаж, Скидка',
                ])->hint('Название тега отображается на карточке товара') ?>

                <?= $form->field($model, 'slug')->textInput([
                    'maxlength' => 100,
                    'placeholder' => 'URL-friendly название',
                ])->hint('Генерируется автоматически, можно изменить') ?>

                <?= $form->field($model, 'description')->textarea([
                    'rows' => 3,
                    'placeholder' => 'Описание тега (опционально)',
                ])->hint('Описание отображается на странице тега') ?>
            </div>

            <div class="form-col form-col-side">
                <?= $form->field($model, 'color', [
                    'template' => "{label}\n<div class=\"color-picker-wrapper\">{input}</div>\n{hint}\n{error}",
                ])->input('color', [
                    'class' => 'form-control color-picker',
                ])->hint('Цвет тега для визуального выделения') ?>

                <?= $form->field($model, 'is_active')->checkbox([
                    'class' => 'checkbox-toggle',
                ])->hint('Неактивные теги не отображаются на сайте') ?>

                <?= $form->field($model, 'sort_order')->input('number', [
                    'min' => 0,
                    'class' => 'form-control',
                ])->hint('Порядок отображения (меньше = выше)') ?>

                <?php if (!$isCreate): ?>
                    <div class="tag-stats">
                        <h4>Статистика</h4>
                        <p><strong>Товаров с тегом:</strong> <?= $model->getProductsCount() ?></p>
                        <p><strong>Создан:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
                        <p><strong>Обновлен:</strong> <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <?= Html::submitButton($isCreate ? 'Создать тег' : 'Сохранить изменения', [
                'class' => 'btn btn-primary',
            ]) ?>
            
            <?php if (!$isCreate): ?>
                <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data-confirm' => 'Удалить этот тег?',
                    'data-method' => 'post',
                ]) ?>
            <?php endif; ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$this->registerCss("
.product-tag-form {
    padding: 20px;
    max-width: 900px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
}

.form-container {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-col-main .form-group {
    margin-bottom: 20px;
}

.form-col-main label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-col-main .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-col-main .form-control:focus {
    outline: none;
    border-color: #000;
}

.form-col-main textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-col-main .hint-block {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.form-col-main .help-block-error {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
}

.color-picker-wrapper {
    position: relative;
}

.color-picker {
    width: 60px !important;
    height: 40px;
    padding: 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.checkbox-toggle {
    width: 44px;
    height: 24px;
    cursor: pointer;
}

.form-col-side .form-group {
    margin-bottom: 16px;
}

.form-col-side label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 14px;
}

.tag-stats {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    margin-top: 20px;
}

.tag-stats h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
}

.tag-stats p {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: #374151;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary {
    background: #000;
    color: #fff;
}

.btn-primary:hover {
    background: #374151;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-link {
    background: transparent;
    color: #374151;
}

.btn-link:hover {
    color: #000;
}
");

// JS для автогенерации slug
$this->registerJs("
$('#tag-form input[name=\"ProductTag[name]\"]').on('blur', function() {
    var slugField = $('#tag-form input[name=\"ProductTag[slug]\"]');
    if (!slugField.val()) {
        var name = $(this).val();
        var slug = name.toLowerCase()
            .replace(/[^\\w\\s-]/g, '')
            .replace(/[\\s]+/g, '-')
            .substring(0, 100);
        slugField.val(slug);
    }
});
");
?>
