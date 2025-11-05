<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */

$this->title = 'Запуск импорта товаров';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['poizon-import']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-run">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mt-4">
        <div class="col-md-12">
            <!-- Импорт из URL -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Импорт из URL (JSON)</h5>
                </div>
                <div class="card-body">
                    <?php $formUrl = ActiveForm::begin([
                        'method' => 'post',
                        'id' => 'url-import-form'
                    ]); ?>

                    <div class="mb-3">
                        <label class="form-label">URL JSON файла</label>
                        <?= Html::textInput('import_url', '', [
                            'class' => 'form-control',
                            'id' => 'import-url-input',
                            'placeholder' => 'https://storage.yandexcloud.net/.../export.json'
                        ]) ?>
                        <small class="form-text text-muted">
                            Вставьте прямую ссылку на JSON файл экспорта Poizon
                        </small>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton('<i class="bi bi-download"></i> Импортировать из URL', [
                            'class' => 'btn btn-warning btn-lg',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <!-- Загрузка файла -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Импорт из файла</h5>
                </div>
                <div class="card-body">
                    <?php $formFile = ActiveForm::begin([
                        'method' => 'post',
                        'options' => ['enctype' => 'multipart/form-data'],
                        'id' => 'file-upload-form'
                    ]); ?>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Поддерживаемые форматы:</strong> JSON, CSV, Excel (.xlsx, .xls)
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Загрузить файл</label>
                        <div class="upload-area" id="upload-area">
                            <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                            <p class="mt-3">Перетащите файл сюда или нажмите для выбора</p>
                            <small class="text-muted">Максимальный размер: 10 МБ</small>
                            <?= Html::fileInput('import_file', null, [
                                'class' => 'form-control d-none',
                                'id' => 'file-input',
                                'accept' => '.json,.csv,.xlsx,.xls'
                            ]) ?>
                        </div>
                        <div id="file-info" class="mt-2 d-none">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                <strong>Выбран файл:</strong> <span id="file-name"></span>
                                <button type="button" class="btn-close float-end" onclick="clearFile()"></button>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton('<i class="bi bi-upload"></i> Загрузить и импортировать', [
                            'class' => 'btn btn-success btn-lg',
                            'id' => 'submit-file-btn',
                            'disabled' => true
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <!-- API импорт -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cloud-download"></i> Импорт через API</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['method' => 'post']); ?>

                    <div class="mb-3">
                        <label class="form-label">Лимит товаров</label>
                        <?= Html::input('number', 'limit', 100, [
                            'class' => 'form-control',
                            'min' => 1,
                            'max' => 10000,
                        ]) ?>
                        <small class="form-text text-muted">
                            Максимальное количество товаров для импорта (рекомендуется 100-500 для первого запуска)
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Информация об импорте:</h6>
                        <ul class="mb-0">
                            <li>Импорт запускается в фоновом режиме</li>
                            <li>Процесс может занять несколько минут</li>
                            <li>Все товары публикуются со статусом "Под заказ"</li>
                            <li>Срок доставки: 14-30 дней</li>
                            <li>Формула цены: CNY × курс × 1.5 + 40 BYN</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2">
                        <?= Html::submitButton('<i class="bi bi-play-circle-fill"></i> Запустить импорт', [
                            'class' => 'btn btn-primary btn-lg',
                            'onclick' => 'return confirm("Вы уверены, что хотите запустить импорт?")',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Справка</h5>
                </div>
                <div class="card-body">
                    <h6>Что делает импорт?</h6>
                    <ul class="small">
                        <li>Загружает товары из Poizon API</li>
                        <li>Создает новые товары</li>
                        <li>Обновляет существующие</li>
                        <li>Импортирует размеры (US/EU/UK/CM)</li>
                        <li>Устанавливает характеристики обуви</li>
                        <li>Логирует все действия</li>
                    </ul>

                    <hr>

                    <h6>Консольные команды:</h6>
                    <pre class="small bg-dark text-light p-2 rounded"><code>php yii poizon-import/run --limit=100
php yii poizon-import/test
php yii poizon-import/logs</code></pre>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Быстрые ссылки</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?= Html::a('История импортов', ['poizon-import'], ['class' => 'list-group-item list-group-item-action']) ?>
                    <?= Html::a('Логи ошибок', ['poizon-errors'], ['class' => 'list-group-item list-group-item-action']) ?>
                    <?= Html::a('Управление товарами', ['/catalog/index'], ['class' => 'list-group-item list-group-item-action']) ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .upload-area {
        border: 3px dashed #ddd;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .upload-area:hover {
        border-color: #28a745;
        background: #e8f5e9;
    }
    
    .upload-area.dragover {
        border-color: #28a745;
        background: #d4edda;
        transform: scale(1.02);
    }
</style>

<script>
    // Drag and Drop
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('submit-file-btn');

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            showFileInfo(e.target.files[0]);
        }
    });

    function showFileInfo(file) {
        fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
        fileInfo.classList.remove('d-none');
        uploadArea.style.display = 'none';
        submitBtn.disabled = false;
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        uploadArea.style.display = 'block';
        submitBtn.disabled = true;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>
