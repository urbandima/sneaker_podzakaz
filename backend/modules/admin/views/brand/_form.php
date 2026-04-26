<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Brand $model */

$this->title = $model->isNewRecord ? 'Создать бренд' : 'Редактировать: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Бренды', 'url' => ['/admin/brand']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/brand'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Основное</div>
            <div class="card-body">
                <?= $form->field($model, 'name')->textInput(['placeholder' => 'Nike, Adidas, Puma...']) ?>
                <?= $form->field($model, 'slug')->textInput(['placeholder' => 'auto-generated']) ?>
                <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
                <?= $form->field($model, 'sort_order')->textInput(['type' => 'number', 'min' => 0]) ?>
                <?= $form->field($model, 'is_active')->checkbox() ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">SEO</div>
            <div class="card-body">
                <?= $form->field($model, 'meta_title')->textInput(['placeholder' => 'SEO заголовок']) ?>
                <?= $form->field($model, 'meta_description')->textarea(['rows' => 2]) ?>
                <?= $form->field($model, 'meta_keywords')->textInput(['placeholder' => 'ключевое слово 1, ключевое слово 2']) ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Logo -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Логотип</div>
            <div class="card-body">
                <?php
                $currentLogo = $model->logo ?: $model->logo_url;
                ?>
                <?php if (!$model->isNewRecord && $currentLogo): ?>
                    <div class="mb-3 text-center">
                        <img src="<?= Html::encode($currentLogo) ?>"
                             alt="Логотип"
                             style="max-width:100%; max-height:120px; object-fit:contain; background:#f8fafc; padding:8px; border-radius:8px; border:1px solid #e5e7eb;">
                        <div class="mt-1 text-muted" style="font-size:.75rem">Текущий логотип</div>
                    </div>
                <?php endif; ?>

                <div onclick="document.getElementById('logoFileInput').click()"
                     style="border:2px dashed #d1d5db; border-radius:10px; padding:20px; text-align:center; cursor:pointer; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='#2563eb'"
                     onmouseout="this.style.borderColor='#d1d5db'">
                    <i class="bi bi-image" style="font-size:1.5rem; color:#9ca3af;"></i>
                    <p class="mt-2 mb-0 text-muted" style="font-size:.8rem">
                        PNG с прозрачностью<br><small>~300×150 px рекомендуется</small>
                    </p>
                    <div id="logoPreviewWrap" class="mt-2" style="display:none">
                        <img id="logoPreview" src="" style="max-width:100%; max-height:80px; object-fit:contain;">
                    </div>
                </div>
                <input type="file" id="logoFileInput" name="Brand[logoFile]"
                       accept="image/*" style="display:none"
                       onchange="previewFile(this,'logoPreview','logoPreviewWrap')">

                <?= $form->field($model, 'logo_url')->textInput(['placeholder' => 'https://... (или загрузите файл выше)']) ?>
            </div>
        </div>

        <!-- Cover Image -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">Обложка (cover_image)</div>
            <div class="card-body">
                <?php if (!$model->isNewRecord && !empty($model->cover_image)): ?>
                    <div class="mb-3 text-center">
                        <img src="<?= Html::encode($model->cover_image) ?>"
                             alt="Обложка"
                             style="max-width:100%; max-height:120px; border-radius:8px; object-fit:cover;">
                        <div class="mt-1 text-muted" style="font-size:.75rem">Текущая обложка</div>
                    </div>
                <?php endif; ?>

                <div onclick="document.getElementById('coverFileInput').click()"
                     style="border:2px dashed #d1d5db; border-radius:10px; padding:20px; text-align:center; cursor:pointer; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='#2563eb'"
                     onmouseout="this.style.borderColor='#d1d5db'">
                    <i class="bi bi-card-image" style="font-size:1.5rem; color:#9ca3af;"></i>
                    <p class="mt-2 mb-0 text-muted" style="font-size:.8rem">
                        Баннер бренда<br><small>1200×400 px рекомендуется</small>
                    </p>
                    <div id="coverPreviewWrap" class="mt-2" style="display:none">
                        <img id="coverPreview" src="" style="max-width:100%; max-height:80px; object-fit:cover; border-radius:6px;">
                    </div>
                </div>
                <input type="file" id="coverFileInput" name="Brand[coverFile]"
                       accept="image/*" style="display:none"
                       onchange="previewFile(this,'coverPreview','coverPreviewWrap')">

                <div class="mt-2 text-muted" style="font-size:.75rem">
                    Обложка показывается на странице бренда. Рекомендуется 1200×400 px.
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
    <?= Html::a('Отмена', ['/admin/brand'], ['class' => 'btn btn-secondary btn-lg ms-2']) ?>
</div>

<?php ActiveForm::end(); ?>

<script>
function previewFile(input, imgId, wrapId) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById(imgId).src = e.target.result;
        document.getElementById(wrapId).style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
