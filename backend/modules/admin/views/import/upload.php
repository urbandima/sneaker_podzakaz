<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var object $model */
/** @var app\backend\modules\admin\models\import\ImportSource[] $sources */

$this->title = 'Ручной импорт товаров';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['index']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Назад к импорту
        </a>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-file-upload"></i>
        Загрузка файла для импорта
    </h2>
    
    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'form-error']
        ]
    ]); ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div class="form-group">
            <label>Источник импорта *</label>
            <select name="Import[source_id]" class="form-control">
                <option value="">Выберите источник импорта</option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?= $source->id ?>"><?= Html::encode($source->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Формат файла *</label>
            <select name="Import[format]" class="form-control">
                <option value="">Выберите формат</option>
                <option value="json">JSON файл</option>
                <option value="csv">CSV файл (разделитель ;)</option>
                <option value="xlsx">Excel файл (.xlsx)</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Выберите файл *</label>
        <input type="file" name="Import[file]" class="form-control" accept=".json,.csv,.xlsx">
        <div class="form-hint">
            <i class="bi bi-info-circle"></i>
            Поддерживаемые форматы: JSON, CSV, Excel (макс. 10MB)
        </div>
    </div>

    <div class="form-actions">
        <?= Html::submitButton('<i class="bi bi-upload"></i> Загрузить и импортировать', [
            'class' => 'admin-btn admin-btn-primary'
        ]) ?>
        <?= Html::a('<i class="bi bi-x-circle"></i> Отмена', ['index'], [
            'class' => 'admin-btn admin-btn-secondary'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Инструкция и шаблоны -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
    <!-- Инструкция -->
    <div class="admin-card">
        <h3 class="admin-card-title">
            <i class="bi bi-question-circle"></i>
            Инструкция по импорту
        </h3>
        
        <div style="margin-top: 1rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">Обязательные поля:</h4>
            <ul style="font-size: 0.875rem; color: var(--admin-text-secondary); padding-left: 1.5rem;">
                <li><code>name</code> - Название товара</li>
                <li><code>sku</code> - Артикул (уникальный)</li>
            </ul>
        </div>
        
        <div style="margin-top: 1rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">Опциональные поля:</h4>
            <ul style="font-size: 0.875rem; color: var(--admin-text-secondary); padding-left: 1.5rem;">
                <li><code>description</code> - Описание</li>
                <li><code>price</code> - Цена</li>
                <li><code>brand_id</code> - ID бренда</li>
                <li><code>brand_name</code> - Название бренда</li>
                <li><code>is_active</code> - Активность (true/false)</li>
            </ul>
        </div>
    </div>

    <!-- Примеры -->
    <div class="admin-card">
        <h3 class="admin-card-title">
            <i class="bi bi-code"></i>
            Примеры форматов
        </h3>
        
        <div style="margin-top: 1rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--admin-primary);">JSON:</h4>
            <pre style="background: var(--admin-bg); padding: 1rem; border-radius: 0.5rem; font-size: 0.75rem; overflow-x: auto;"><code>[
  {
    "name": "Nike Air Max 90",
    "sku": "NM-90-001",
    "description": "Классические кроссовки",
    "price": 299.99,
    "brand_name": "Nike",
    "is_active": true
  }
]</code></pre>
        </div>
        
        <div style="margin-top: 1rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--admin-success);">CSV:</h4>
            <pre style="background: var(--admin-bg); padding: 1rem; border-radius: 0.5rem; font-size: 0.75rem; overflow-x: auto;"><code>name;sku;description;price;brand_name;is_active
"Nike Air Max 90";"NM-90-001";"Классические кроссовки";299.99;"Nike";1</code></pre>
        </div>
    </div>

    <!-- Шаблоны -->
    <div class="admin-card">
        <h3 class="admin-card-title">
            <i class="bi bi-download"></i>
            Шаблоны для скачивания
        </h3>
        
        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="downloadTemplate('json')">
                <i class="bi bi-download"></i>
                Скачать JSON шаблон
            </button>
            <button type="button" class="admin-btn admin-btn-secondary" onclick="downloadTemplate('csv')">
                <i class="bi bi-download"></i>
                Скачать CSV шаблон
            </button>
        </div>
    </div>
</div>

<style>
.admin-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    font-size: 0.875rem;
}

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

.form-error {
    color: var(--admin-danger);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.form-hint {
    font-size: 0.75rem;
    color: var(--admin-text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--admin-border);
}

code {
    background: var(--admin-primary-soft);
    color: var(--admin-primary);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

pre {
    margin: 0;
}
</style>

<script>
function downloadTemplate(format) {
    let content, filename, mimeType;
    
    if (format === 'json') {
        content = JSON.stringify([
            {
                "name": "Nike Air Max 90",
                "sku": "NM-90-001",
                "description": "Классические кроссовки Nike",
                "price": 299.99,
                "brand_name": "Nike",
                "is_active": true
            },
            {
                "name": "Adidas Ultraboost",
                "sku": "AD-UB-001",
                "description": "Беговые кроссовки Adidas",
                "price": 349.99,
                "brand_name": "Adidas",
                "is_active": true
            }
        ], null, 2);
        filename = 'import-template.json';
        mimeType = 'application/json';
    } else if (format === 'csv') {
        content = 'name;sku;description;price;brand_name;is_active\n' +
                  '"Nike Air Max 90";"NM-90-001";"Классические кроссовки Nike";299.99;"Nike";1\n' +
                  '"Adidas Ultraboost";"AD-UB-001";"Беговые кроссовки Adidas";349.99;"Adidas";1';
        filename = 'import-template.csv';
        mimeType = 'text/csv';
    }
    
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>
