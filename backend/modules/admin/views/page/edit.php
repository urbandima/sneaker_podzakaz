<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Редактировать: ' . $model->title;

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-box-arrow-up-right"></i> Открыть', '/' . $model->slug, ['target' => '_blank', 'class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/page'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-file-earmark-text"></i> <?= Html::encode($model->title) ?></h2>
        <code style="font-size:13px;color:var(--admin-text-secondary)">/<?= Html::encode($model->slug) ?></code>
    </div>
    <div class="admin-card-body">
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'admin-form'],
        ]); ?>

        <div style="margin-bottom:1rem">
            <label class="admin-form-label">Заголовок страницы</label>
            <?= $form->field($model, 'title', [
                'template' => '{input}{error}',
                'inputOptions' => ['class' => 'form-control'],
            ])->textInput(['placeholder' => 'Заголовок страницы']) ?>
        </div>

        <div style="margin-bottom:1rem">
            <label class="admin-form-label">Meta-описание</label>
            <?= $form->field($model, 'meta_desc', [
                'template' => '{input}{error}',
                'inputOptions' => ['class' => 'form-control'],
            ])->textInput(['placeholder' => 'Краткое описание для поисковиков (до 160 символов)']) ?>
        </div>

        <div style="margin-bottom:1.25rem">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem">
                <label class="admin-form-label" style="margin:0">Контент страницы (HTML)</label>
                <div style="display:flex;gap:6px">
                    <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="togglePreview()">
                        <i class="bi bi-eye" id="preview-icon"></i> Предпросмотр
                    </button>
                </div>
            </div>
            <?= $form->field($model, 'content', [
                'template' => '{input}{error}',
                'inputOptions' => ['class' => 'form-control'],
            ])->textarea([
                'id'          => 'page-content-editor',
                'rows'        => 20,
                'style'       => 'font-family:monospace;font-size:13px;line-height:1.5;resize:vertical;min-height:400px',
                'placeholder' => '<h1>Заголовок</h1>\n<p>Текст страницы...</p>',
            ]) ?>
            <small style="color:var(--admin-text-secondary);font-size:12px">
                Поддерживается HTML. Используйте теги &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;table&gt; и т.д.
            </small>
        </div>

        <!-- Preview panel -->
        <div id="preview-panel" style="display:none;margin-bottom:1.25rem;border:1px solid var(--admin-border);border-radius:10px;overflow:hidden">
            <div style="padding:8px 14px;background:var(--admin-surface-2,#f8f9fa);border-bottom:1px solid var(--admin-border);font-size:12px;color:var(--admin-text-secondary);display:flex;justify-content:space-between">
                <span><i class="bi bi-eye"></i> Предпросмотр контента</span>
                <button type="button" onclick="togglePreview()" style="background:none;border:none;cursor:pointer;color:inherit;font-size:12px"><i class="bi bi-x"></i></button>
            </div>
            <div id="preview-content" style="padding:1.5rem;max-height:500px;overflow-y:auto;background:#fff;color:#111"></div>
        </div>

        <div style="display:flex;gap:0.75rem">
            <?= Html::submitButton('<i class="bi bi-check-lg"></i> Сохранить', ['class' => 'admin-btn admin-btn-primary']) ?>
            <?= Html::a('Отмена', ['/admin/page'], ['class' => 'admin-btn admin-btn-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<script>
var _previewOpen = false;
function togglePreview() {
    _previewOpen = !_previewOpen;
    var panel = document.getElementById('preview-panel');
    var icon  = document.getElementById('preview-icon');
    var editor = document.getElementById('page-content-editor');
    if (panel) panel.style.display = _previewOpen ? 'block' : 'none';
    if (icon)  icon.className = _previewOpen ? 'bi bi-eye-slash' : 'bi bi-eye';
    if (_previewOpen && editor) {
        var pContent = document.getElementById('preview-content');
        if (pContent) pContent.innerHTML = editor.value;
    }
}
/* Live preview update on typing */
var _editor = document.getElementById('page-content-editor');
if (_editor) {
    _editor.addEventListener('input', function() {
        if (_previewOpen) {
            var pContent = document.getElementById('preview-content');
            if (pContent) pContent.innerHTML = this.value;
        }
    });
}
</script>
