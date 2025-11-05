<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var string $content */
/** @var string $fileName */
/** @var int $fileSize */
/** @var int $lastModified */
/** @var array $logsList */

$this->title = 'Просмотр лога импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт Poizon', 'url' => ['poizon-import']];
$this->params['breadcrumbs'][] = $this->title;

// Форматирование размера
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log(1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>

<div class="poizon-view-log">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['poizon-import'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="row">
        <!-- Список логов -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-list"></i> Все логи</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($logsList as $log): ?>
                        <a href="<?= Url::to(['poizon-view-log', 'file' => $log['name']]) ?>" 
                           class="list-group-item list-group-item-action <?= $log['name'] === $fileName ? 'active' : '' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-truncate"><?= Html::encode($log['name']) ?></small>
                            </div>
                            <small class="text-muted">
                                <?= formatSize($log['size']) ?> • 
                                <?= date('d.m.Y H:i', $log['time']) ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Содержимое лога -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-file-text"></i> <?= Html::encode($fileName) ?>
                        </h6>
                        <div>
                            <span class="badge bg-secondary"><?= formatSize($fileSize) ?></span>
                            <span class="badge bg-info"><?= date('d.m.Y H:i:s', $lastModified) ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; max-height: 600px; overflow-y: auto; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.6;">
                        <?php
                        // Подсветка важных строк
                        $lines = explode("\n", $content);
                        foreach ($lines as $line) {
                            $line = Html::encode($line);
                            
                            // Подсветка ошибок
                            if (strpos($line, 'ОШИБКА') !== false || strpos($line, '❌') !== false) {
                                echo '<div style="color: #f48771; background: rgba(244, 135, 113, 0.1); padding: 2px 5px; margin: 2px 0;">' . $line . '</div>';
                            }
                            // Подсветка успехов
                            elseif (strpos($line, '✅') !== false || strpos($line, 'Успешно') !== false) {
                                echo '<div style="color: #89d185;">' . $line . '</div>';
                            }
                            // Подсветка предупреждений
                            elseif (strpos($line, '⚠️') !== false || strpos($line, 'ВНИМАНИЕ') !== false) {
                                echo '<div style="color: #e5c07b;">' . $line . '</div>';
                            }
                            // Подсветка заголовков
                            elseif (strpos($line, '═══') !== false || strpos($line, '╔══') !== false || strpos($line, '║') !== false) {
                                echo '<div style="color: #61afef; font-weight: bold;">' . $line . '</div>';
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
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyLog()">
                            <i class="bi bi-clipboard"></i> Копировать
                        </button>
                        <a href="<?= Url::to(['poizon-view-log', 'file' => $fileName]) ?>" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Обновить
                        </a>
                        <button class="btn btn-sm btn-outline-success" onclick="scrollToBottom()">
                            <i class="bi bi-arrow-down"></i> В конец
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="searchErrors()">
                            <i class="bi bi-search"></i> Найти ошибки
                        </button>
                    </div>
                </div>
            </div>

            <!-- Быстрая статистика из лога -->
            <?php
            $errors = substr_count($content, 'ОШИБКА');
            $success = substr_count($content, '✅');
            $warnings = substr_count($content, '⚠️');
            
            if ($errors > 0 || $success > 0 || $warnings > 0):
            ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Статистика лога</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h3 class="text-success mb-0"><?= $success ?></h3>
                                <small class="text-muted">Успешно</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h3 class="text-danger mb-0"><?= $errors ?></h3>
                                <small class="text-muted">Ошибок</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <h3 class="text-warning mb-0"><?= $warnings ?></h3>
                                <small class="text-muted">Предупреждений</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyLog() {
    const content = <?= json_encode($content) ?>;
    navigator.clipboard.writeText(content).then(() => {
        alert('Лог скопирован в буфер обмена');
    });
}

function scrollToBottom() {
    const logContainer = document.querySelector('.card-body > div');
    logContainer.scrollTop = logContainer.scrollHeight;
}

function searchErrors() {
    const logContainer = document.querySelector('.card-body > div');
    const errorElement = logContainer.querySelector('[style*="color: #f48771"]');
    if (errorElement) {
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        errorElement.style.animation = 'blink 1s ease-in-out 3';
    } else {
        alert('Ошибок в логе не найдено');
    }
}

// Автообновление каждые 5 секунд (если лог свежий)
const lastModified = <?= $lastModified ?>;
const now = Math.floor(Date.now() / 1000);
if (now - lastModified < 300) { // Если лог моложе 5 минут
    setInterval(() => {
        location.reload();
    }, 5000);
}
</script>

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
