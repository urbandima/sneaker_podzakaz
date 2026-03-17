<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var object $model */
/** @var app\backend\modules\admin\models\import\ImportSource[] $sources */

$this->title = 'Ручной импорт товаров';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-upload">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-upload"></i> Загрузка файла для импорта
                    </h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'options' => ['enctype' => 'multipart/form-data'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label'],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'text-danger']
                        ]
                    ]); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'source_id')->dropDownList(
                                \yii\helpers\ArrayHelper::map($sources, 'id', 'name'),
                                ['prompt' => 'Выберите источник импорта', 'class' => 'form-select']
                            )->label('Источник импорта') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'format')->dropDownList([
                                'json' => 'JSON файл',
                                'csv' => 'CSV файл (разделитель ;)',
                                'xlsx' => 'Excel файл (.xlsx)',
                            ], ['prompt' => 'Выберите формат', 'class' => 'form-select'])->label('Формат файла') ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'file')->fileInput([
                            'class' => 'form-control',
                            'accept' => '.json,.csv,.xlsx'
                        ])->label('Выберите файл') ?>
                        
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle"></i>
                            Поддерживаемые форматы: JSON, CSV, Excel (макс. 10MB)
                        </p>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?= Html::submitButton('<i class="fas fa-upload"></i> Загрузить и импортировать', [
                            'class' => 'btn btn-primary btn-lg'
                        ]) ?>
                        <?= Html::a('<i class="fas fa-arrow-left"></i> Назад', ['index'], [
                            'class' => 'btn btn-outline-secondary btn-lg'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Инструкция -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle"></i> Инструкция по импорту
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Обязательные поля:</h6>
                    <ul class="small">
                        <li><code>name</code> - Название товара</li>
                        <li><code>sku</code> - Артикул (уникальный)</li>
                    </ul>

                    <h6>Опциональные поля:</h6>
                    <ul class="small">
                        <li><code>description</code> - Описание</li>
                        <li><code>price</code> - Цена</li>
                        <li><code>brand_id</code> - ID бренда</li>
                        <li><code>brand_name</code> - Название бренда</li>
                        <li><code>is_active</code> - Активность (true/false)</li>
                    </ul>
                </div>
            </div>

            <!-- Примеры -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-code"></i> Примеры форматов
                    </h6>
                </div>
                <div class="card-body">
                    <!-- JSON пример -->
                    <div class="mb-3">
                        <h6 class="text-primary">JSON:</h6>
                        <pre class="small"><code>[
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

                    <!-- CSV пример -->
                    <div class="mb-3">
                        <h6 class="text-success">CSV:</h6>
                        <pre class="small"><code>name;sku;description;price;brand_name;is_active
"Nike Air Max 90";"NM-90-001";"Классические кроссовки";299.99;"Nike";1</code></pre>
                    </div>
                </div>
            </div>

            <!-- Шаблоны -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-download"></i> Шаблоны для скачивания
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::a('<i class="fas fa-download"></i> Скачать JSON шаблон', '#', [
                            'class' => 'btn btn-outline-primary btn-sm',
                            'onclick' => 'downloadTemplate("json")'
                        ]) ?>
                        <?= Html::a('<i class="fas fa-download"></i> Скачать CSV шаблон', '#', [
                            'class' => 'btn btn-outline-success btn-sm',
                            'onclick' => 'downloadTemplate("csv")'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.import-upload .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.import-upload .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.import-upload .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.import-upload .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.import-upload .btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
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
