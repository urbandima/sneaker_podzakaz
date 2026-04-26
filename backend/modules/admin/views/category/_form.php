<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Category $model */
/** @var array $parentCategories */

$this->title = $model->isNewRecord ? 'Создать категорию' : 'Редактировать: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Категории', 'url' => ['/admin/category']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/category'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Основное</div>
            <div class="card-body">
                <?= $form->field($model, 'name')->textInput(['placeholder' => 'Обувь, Одежда, Аксессуары...']) ?>
                <?= $form->field($model, 'slug')->textInput(['placeholder' => 'auto-generated']) ?>
                <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
                <?= $form->field($model, 'parent_id')->dropDownList(
                    ArrayHelper::map($parentCategories, 'id', 'name'),
                    ['prompt' => '— Нет родителя —']
                ) ?>
                <?= $form->field($model, 'sort_order')->textInput(['type' => 'number', 'min' => 0]) ?>
                <?= $form->field($model, 'is_active')->checkbox() ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">SEO</div>
            <div class="card-body">
                <?= $form->field($model, 'meta_title')->textInput(['placeholder' => 'SEO заголовок (60 символов)']) ?>
                <?= $form->field($model, 'meta_description')->textarea(['rows' => 2, 'placeholder' => 'SEO описание (160 символов)']) ?>
                <?= $form->field($model, 'meta_keywords')->textInput(['placeholder' => 'ключевое слово 1, ключевое слово 2']) ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">Изображение категории</div>
            <div class="card-body">
                <?php if (!$model->isNewRecord && $model->image): ?>
                    <div class="mb-3 text-center">
                        <img src="<?= Html::encode($model->image) ?>"
                             alt="Текущее фото"
                             style="max-width:100%; max-height:200px; border-radius:8px; object-fit:cover;">
                        <div class="mt-1 text-muted" style="font-size:.75rem">Текущее изображение</div>
                    </div>
                <?php endif; ?>

                <div class="drop-zone-wrap" onclick="document.getElementById('imgFileInput').click()"
                     style="border:2px dashed #d1d5db; border-radius:10px; padding:24px; text-align:center; cursor:pointer; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='#2563eb'"
                     onmouseout="this.style.borderColor='#d1d5db'">
                    <i class="bi bi-image" style="font-size:1.8rem; color:#9ca3af;"></i>
                    <p class="mt-2 mb-0 text-muted" style="font-size:.85rem">
                        Нажмите для выбора<br>
                        <small>JPG, PNG, WebP — 600×400 рекомендуется</small>
                    </p>
                    <div id="imgPreviewWrap" class="mt-2" style="display:none">
                        <img id="imgPreview" src="" style="max-width:100%; max-height:150px; border-radius:6px;">
                        <div id="imgPreviewName" class="text-muted mt-1" style="font-size:.75rem"></div>
                    </div>
                </div>

                <?php
                // Attach imageFile as virtual field
                $model->addRule ? null : null; // no-op
                ?>
                <input type="file" id="imgFileInput" name="Category[imageFile]"
                       accept="image/*" style="display:none"
                       onchange="previewImage(this)">

                <?php if ($model->image): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="Category[image]" value="" id="clearImage">
                        <label class="form-check-label text-muted" for="clearImage" style="font-size:.85rem">
                            Удалить текущее изображение
                        </label>
                    </div>
                <?php endif; ?>

                <div class="mt-2 text-muted" style="font-size:.75rem">
                    Рекомендуемый размер: 600×400 px, JPG или WebP
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <?= Html::submitButton(
        $model->isNewRecord ? '<i class="bi bi-plus-lg"></i> Создать' : '<i class="bi bi-check-lg"></i> Сохранить',
        ['class' => 'btn btn-primary btn-lg']
    ) ?>
    <?= Html::a('Отмена', ['/admin/category'], ['class' => 'btn btn-secondary btn-lg ms-2']) ?>
</div>

<?php ActiveForm::end(); ?>

<script>
function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('imgPreview').src = e.target.result;
        document.getElementById('imgPreviewName').textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
        document.getElementById('imgPreviewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
}
</script>
