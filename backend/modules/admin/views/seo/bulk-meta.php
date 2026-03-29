<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Массовое редактирование meta-тегов';
?>

<div class="seo-bulk-meta">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <?php if (!empty($results)): ?>
        <div class="alert alert-success">
            Обновлено: <?= $results['success'] ?>, Ошибок: <?= $results['failed'] ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">📋 Готовые шаблоны</div>
                <div class="card-body">
                    <p class="text-muted small">Нажмите чтобы применить:</p>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-2 w-100" onclick="applyTemplate('title', '{brand} {name} - купить в Минске | СНИКЕРХЭД')">
                        Title: {brand} {name} - купить в Минске...
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-2 w-100" onclick="applyTemplate('desc', 'Оригинальные {brand} {name} по лучшей цене. Доставка по Минску и Беларуси. Гарантия подлинности. Закажите сейчас!')">
                        Description: Оригинальные {brand} {name}...
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="applyTemplate('keywords', '{brand}, {name}, купить, оригинал, Минск, доставка')">
                        Keywords: {brand}, {name}, купить...
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">⚙️ Шаблоны для генерации</div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>
                    
                    <?= $form->field($form, 'entity_type')->dropDownList([
                        'product' => 'Товары',
                        'category' => 'Категории',
                    ]) ?>
                    
                    <?= $form->field($form, 'meta_title_pattern')->textInput(['id' => 'meta_title_pattern'])->hint('Шаблоны: {name}, {brand}, {site}') ?>
                    <?= $form->field($form, 'meta_description_pattern')->textarea(['id' => 'meta_description_pattern', 'rows' => 3])->hint('Шаблоны: {name}, {brand}, {site}') ?>
                    <?= $form->field($form, 'meta_keywords_pattern')->textInput(['id' => 'meta_keywords_pattern'])->hint('Шаблоны: {name}, {brand}, {site}') ?>
                    
                    <?= $form->field($form, 'append_to_existing')->checkbox() ?>
                    
                    <div class="form-group">
                        <?= Html::submitButton('Применить шаблон', ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                    
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Товары</div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Title</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product->id ?></td>
                                <td><?= Html::encode($product->name) ?></td>
                                <td>
                                    <?= Html::textInput("product_{$product->id}_title", $product->meta_title, [
                                        'class' => 'form-control form-control-sm',
                                        'onchange' => "updateProductMeta({$product->id}, 'meta_title', this.value)",
                                    ]) ?>
                                </td>
                                <td>
                                    <?= Html::textarea("product_{$product->id}_desc", $product->meta_description, [
                                        'class' => 'form-control form-control-sm',
                                        'rows' => 2,
                                        'onchange' => "updateProductMeta({$product->id}, 'meta_description', this.value)",
                                    ]) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateProductMeta(id, field, value) {
    fetch('/admin/seo/update-product-meta', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': yii.getCsrfToken(),
        },
        body: 'id=' + id + '&field=' + field + '&value=' + encodeURIComponent(value)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Показать уведомление об успехе
            showNotification('Сохранено!', 'success');
        } else {
            showNotification('Ошибка сохранения', 'error');
        }
    });
}

function applyTemplate(type, template) {
    if (type === 'title') {
        document.getElementById('meta_title_pattern').value = template;
    } else if (type === 'desc') {
        document.getElementById('meta_description_pattern').value = template;
    } else if (type === 'keywords') {
        document.getElementById('meta_keywords_pattern').value = template;
    }
    showNotification('Шаблон применён!', 'success');
}

function showNotification(message, type) {
    // Простое уведомление
    const div = document.createElement('div');
    div.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
    div.textContent = message;
    div.style.position = 'fixed';
    div.style.top = '20px';
    div.style.right = '20px';
    div.style.zIndex = '9999';
    div.style.padding = '10px 20px';
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}
</script>
