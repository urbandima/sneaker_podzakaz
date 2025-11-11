<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\ImportLog;

/** @var yii\web\View $this */
/** @var app\models\ImportBatch $batch */
/** @var yii\data\ActiveDataProvider $logsProvider */
/** @var string $logContent */

$this->title = 'Импорт #' . $batch->id;
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['/admin/poizon/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/poizon/index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <!-- Информация о батче -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Статус:</th>
                            <td>
                                <span class="badge <?= $batch->getStatusBadgeClass() ?>"><?= $batch->getStatusLabel() ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Источник:</th>
                            <td><?= $batch->getSourceLabel() ?></td>
                        </tr>
                        <tr>
                            <th>Тип:</th>
                            <td><?= $batch->getTypeLabel() ?></td>
                        </tr>
                        <tr>
                            <th>Начало:</th>
                            <td><?= $batch->started_at ?></td>
                        </tr>
                        <tr>
                            <th>Завершение:</th>
                            <td><?= $batch->finished_at ?: '-' ?></td>
                        </tr>
                        <tr>
                            <th>Длительность:</th>
                            <td><?= $batch->getFormattedDuration() ?></td>
                        </tr>
                        <tr>
                            <th>Запустил:</th>
                            <td><?= $batch->creator ? $batch->creator->username : 'Cron' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Статистика</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h3 class="text-success"><?= $batch->created_count ?></h3>
                            <small class="text-muted">Создано</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="text-info"><?= $batch->updated_count ?></h3>
                            <small class="text-muted">Обновлено</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-warning"><?= $batch->skipped_count ?></h3>
                            <small class="text-muted">Пропущено</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-danger"><?= $batch->error_count ?></h3>
                            <small class="text-muted">Ошибок</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4><?= $batch->getSuccessRate() ?>%</h4>
                        <small class="text-muted">Успешность</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Конфигурация -->
    <?php if ($batch->config): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Конфигурация</h5>
        </div>
        <div class="card-body">
            <pre class="mb-0"><?= json_encode($batch->getConfigArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <!-- Полный лог с подсветкой -->
    <?php if (!empty($logContent)): ?>
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-terminal"></i> Полный лог импорта</h5>
            <div>
                <button class="btn btn-sm btn-outline-light" onclick="copyFullLog()">
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="toggleLogExpand()">
                    <i class="bi bi-arrows-fullscreen"></i> <span id="expandText">Развернуть</span>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="fullLogContainer" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; max-height: 500px; overflow-y: auto; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.6;">
                <?php
                // Подсветка важных строк (та же логика что и в poizon-view-log.php)
                $lines = explode("\n", $logContent);
                $errorCount = 0;
                $successCount = 0;
                
                foreach ($lines as $line) {
                    $originalLine = $line;
                    $line = Html::encode($line);
                    
                    // Считаем ошибки и успехи
                    if (strpos($originalLine, 'ОШИБКА') !== false || strpos($originalLine, '❌') !== false) {
                        $errorCount++;
                    }
                    if (strpos($originalLine, '✅') !== false || strpos($originalLine, 'Успешно') !== false) {
                        $successCount++;
                    }
                    
                    // Подсветка ошибок
                    if (strpos($line, 'ОШИБКА') !== false || strpos($line, '❌') !== false || strpos($line, 'ERROR') !== false) {
                        echo '<div style="color: #f48771; background: rgba(244, 135, 113, 0.1); padding: 2px 5px; margin: 2px 0; border-left: 3px solid #f48771;">' . $line . '</div>';
                    }
                    // Подсветка успехов
                    elseif (strpos($line, '✅') !== false || strpos($line, 'Успешно') !== false || strpos($line, 'SUCCESS') !== false) {
                        echo '<div style="color: #89d185;">' . $line . '</div>';
                    }
                    // Подсветка предупреждений
                    elseif (strpos($line, '⚠️') !== false || strpos($line, 'ВНИМАНИЕ') !== false || strpos($line, 'WARNING') !== false) {
                        echo '<div style="color: #e5c07b; background: rgba(229, 192, 123, 0.1); padding: 2px 5px; margin: 2px 0;">' . $line . '</div>';
                    }
                    // Подсветка заголовков
                    elseif (strpos($line, '═══') !== false || strpos($line, '╔══') !== false || strpos($line, '║') !== false) {
                        echo '<div style="color: #61afef; font-weight: bold;">' . $line . '</div>';
                    }
                    // Подсветка статистики
                    elseif (strpos($line, 'Создано:') !== false || strpos($line, 'Обновлено:') !== false || 
                            strpos($line, 'Пропущено:') !== false || strpos($line, 'Ошибок:') !== false) {
                        echo '<div style="color: #c678dd; font-weight: bold;">' . $line . '</div>';
                    }
                    // Подсветка timestamp
                    elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                        $line = preg_replace('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', '<span style="color: #98c379;">[$1]</span>', $line);
                        echo '<div>' . $line . '</div>';
                    }
                    // Обычная строка
                    else {
                        echo '<div>' . $line . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        <div class="card-footer bg-dark text-white">
            <div class="row">
                <div class="col-md-4">
                    <small><i class="bi bi-file-earmark-text"></i> Всего строк: <?= count($lines) ?></small>
                </div>
                <div class="col-md-4">
                    <small><i class="bi bi-check-circle text-success"></i> Успехов: <?= $successCount ?></small>
                </div>
                <div class="col-md-4">
                    <small><i class="bi bi-x-circle text-danger"></i> Ошибок: <?= $errorCount ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function copyFullLog() {
        const logContainer = document.getElementById('fullLogContainer');
        const text = logContainer.innerText;
        navigator.clipboard.writeText(text).then(function() {
            alert('Лог скопирован в буфер обмена!');
        });
    }
    
    function toggleLogExpand() {
        const logContainer = document.getElementById('fullLogContainer');
        const expandText = document.getElementById('expandText');
        
        if (logContainer.style.maxHeight === '500px') {
            logContainer.style.maxHeight = 'none';
            expandText.textContent = 'Свернуть';
        } else {
            logContainer.style.maxHeight = '500px';
            expandText.textContent = 'Развернуть';
        }
    }
    </script>
    <?php endif; ?>

    <!-- Логи (таблица) -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-table"></i> Детальная таблица операций</h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $logsProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['width' => '180'],
                    ],
                    [
                        'attribute' => 'action',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::tag('span', $model->getActionLabel(), [
                                'class' => 'badge ' . $model->getActionBadgeClass(),
                            ]);
                        },
                        'headerOptions' => ['width' => '100'],
                    ],
                    [
                        'attribute' => 'sku',
                        'headerOptions' => ['width' => '150'],
                    ],
                    [
                        'attribute' => 'product_name',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->product_id) {
                                return Html::a($model->product_name, ['/catalog/product', 'id' => $model->product_id], [
                                    'target' => '_blank',
                                ]);
                            }
                            return $model->product_name;
                        },
                    ],
                    [
                        'attribute' => 'message',
                        'format' => 'ntext',
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>

<?php
// Auto-refresh если импорт в процессе
if ($batch->status === \app\models\ImportBatch::STATUS_PROCESSING) {
    $this->registerJs("
        setTimeout(function() {
            location.reload();
        }, 3000);
    ");
}
?>
