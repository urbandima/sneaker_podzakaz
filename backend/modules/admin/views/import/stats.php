<?php

use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var array $overallStats */
/** @var array $sourceStats */
/** @var array $dailyStats */
/** @var app\backend\modules\admin\models\import\ImportProductPrice[] $bestPrices */

$this->title = 'Статистика импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-stats">
    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Всего задач</h6>
                    <h2 class="mb-0"><?= number_format($overallStats['total_tasks'], 0, '', ' ') ?></h2>
                    <small><?= $overallStats['running_tasks'] ?> выполняется</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Завершено успешно</h6>
                    <h2 class="mb-0"><?= number_format($overallStats['completed_tasks'], 0, '', ' ') ?></h2>
                    <small><?= $overallStats['total_products_imported'] ?> товаров импортировано</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Ошибок</h6>
                    <h2 class="mb-0"><?= number_format($overallStats['failed_tasks'], 0, '', ' ') ?></h2>
                    <small><?= $overallStats['total_errors'] ?> ошибок импорта</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Обновлено товаров</h6>
                    <h2 class="mb-0"><?= number_format($overallStats['total_products_updated'], 0, '', ' ') ?></h2>
                    <small>из всех источников</small>
                </div>
            </div>
        </div>
    </div>

    <!-- График по дням -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Статистика по дням (последние 30 дней)</h5>
        </div>
        <div class="card-body">
            <canvas id="dailyChart" height="100"></canvas>
        </div>
    </div>

    <div class="row">
        <!-- Статистика по источникам -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Статистика по источникам</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Источник</th>
                                <th>Задач</th>
                                <th>Успешно</th>
                                <th>Ошибок</th>
                                <th>Товаров</th>
                                <th>Последний запуск</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sourceStats as $stat): ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($stat['source']->name) ?></strong>
                                </td>
                                <td><?= $stat['total_tasks'] ?></td>
                                <td class="text-success"><?= $stat['successful_runs'] ?></td>
                                <td class="text-danger"><?= $stat['failed_runs'] ?></td>
                                <td><?= number_format($stat['total_products'], 0, '', ' ') ?></td>
                                <td>
                                    <?= $stat['last_run'] 
                                        ? Yii::$app->formatter->asRelativeTime($stat['last_run']) 
                                        : '<span class="text-muted">—</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Лучшие цены -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Лучшие цены (топ 20)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Источник</th>
                                <th>Цена</th>
                                <th>Размер</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestPrices as $price): ?>
                            <tr>
                                <td>
                                    <?php if ($price->product): ?>
                                        <?= Html::a(
                                            Html::encode($price->product->name),
                                            ['/catalog/product/view', 'id' => $price->product->id],
                                            ['target' => '_blank']
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">SKU: <?= Html::encode($price->external_sku) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= Html::encode($price->source->name) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= number_format($price->price_byn, 2) ?> BYN</strong>
                                    <?php if ($price->currency_code !== 'BYN'): ?>
                                    <br><small class="text-muted">
                                        <?= number_format($price->price_original, 2) ?> <?= $price->currency_code ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode($price->size) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица по дням -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Детализация по дням</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Задач</th>
                        <th>Импортировано</th>
                        <th>Обновлено</th>
                        <th>Ошибок</th>
                        <th>Дубликатов</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyStats as $day): ?>
                    <tr>
                        <td><?= Yii::$app->formatter->asDate($day['date'], 'php:d.m.Y') ?></td>
                        <td><?= $day['tasks'] ?></td>
                        <td class="text-success"><?= $day['imported'] ?></td>
                        <td class="text-info"><?= $day['updated'] ?></td>
                        <td class="text-danger"><?= $day['errors'] ?></td>
                        <td class="text-warning"><?= $day['duplicates'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// График Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['position' => \yii\web\View::POS_HEAD]);

$labels = array_map(fn($d) => date('d.m', strtotime($d['date'])), array_reverse($dailyStats));
$importedData = array_map(fn($d) => (int)$d['imported'], array_reverse($dailyStats));
$updatedData = array_map(fn($d) => (int)$d['updated'], array_reverse($dailyStats));
$errorData = array_map(fn($d) => (int)$d['errors'], array_reverse($dailyStats));

$this->registerJs(<<<JS
var ctx = document.getElementById('dailyChart').getContext('2d');
var chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {$this->renderPhpValue($labels)},
        datasets: [
            {
                label: 'Импортировано',
                data: {$this->renderPhpValue($importedData)},
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
            },
            {
                label: 'Обновлено',
                data: {$this->renderPhpValue($updatedData)},
                backgroundColor: 'rgba(13, 202, 240, 0.7)',
            },
            {
                label: 'Ошибок',
                data: {$this->renderPhpValue($errorData)},
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});
JS
);
?>

<?php
// Вспомогательный метод для рендеринга PHP значений в JS
$this->registerJs("
    // Helper для рендеринга PHP массивов в JS
    (function() {
        var renderPhpValue = function(value) {
            return JSON.stringify(value);
        };
        window.renderPhpValue = renderPhpValue;
    })();
", \yii\web\View::POS_BEGIN);
?>
