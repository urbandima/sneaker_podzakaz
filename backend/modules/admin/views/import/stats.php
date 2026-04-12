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

<div class="admin-stats" style="margin-bottom: 24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-list-task"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($overallStats['total_tasks'], 0, '', ' ') ?></div>
            <div class="admin-stat-label">Всего задач</div>
            <div style="margin-top: 0.5rem;">
                <span class="admin-badge admin-badge-warning"><?= $overallStats['running_tasks'] ?> выполняется</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($overallStats['completed_tasks'], 0, '', ' ') ?></div>
            <div class="admin-stat-label">Завершено успешно</div>
            <div style="margin-top: 0.5rem;">
                <span class="admin-badge admin-badge-success"><?= number_format($overallStats['total_products_imported'], 0, '', ' ') ?> товаров</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger"><i class="bi bi-x-circle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($overallStats['failed_tasks'], 0, '', ' ') ?></div>
            <div class="admin-stat-label">Ошибок</div>
            <div style="margin-top: 0.5rem;">
                <span class="admin-badge admin-badge-danger"><?= number_format($overallStats['total_errors'], 0, '', ' ') ?> ошибок импорта</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-arrow-clockwise"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($overallStats['total_products_updated'], 0, '', ' ') ?></div>
            <div class="admin-stat-label">Обновлено товаров</div>
            <div style="margin-top: 0.5rem;">
                <span class="admin-badge admin-badge-secondary">из всех источников</span>
            </div>
        </div>
    </div>
</div>

<!-- График по дням -->
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-graph-up"></i> Статистика по дням (последние 30 дней)</h2>
    </div>
    <div class="admin-card-body">
        <?php
        $chartLabels = array_map(fn($d) => date('d.m', strtotime($d['date'])), array_reverse($dailyStats));
        $chartImported = array_map(fn($d) => (int)$d['imported'], array_reverse($dailyStats));
        $chartUpdated = array_map(fn($d) => (int)$d['updated'], array_reverse($dailyStats));
        $chartErrors = array_map(fn($d) => (int)$d['errors'], array_reverse($dailyStats));
        ?>
        <canvas id="dailyChart" height="100"
            data-labels="<?= Html::encode(json_encode($chartLabels)) ?>"
            data-imported="<?= Html::encode(json_encode($chartImported)) ?>"
            data-updated="<?= Html::encode(json_encode($chartUpdated)) ?>"
            data-errors="<?= Html::encode(json_encode($chartErrors)) ?>"></canvas>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 24px;">
    <!-- Статистика по источникам -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-server"></i> Статистика по источникам</h2>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <table class="admin-table">
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
                        <td style="color: var(--admin-success);"><?= $stat['successful_runs'] ?></td>
                        <td style="color: var(--admin-danger);"><?= $stat['failed_runs'] ?></td>
                        <td><?= number_format($stat['total_products'], 0, '', ' ') ?></td>
                        <td>
                            <?= $stat['last_run'] 
                                ? Yii::$app->formatter->asRelativeTime($stat['last_run']) 
                                : '<span style="color: var(--admin-text-secondary);">—</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Лучшие цены -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-tag"></i> Лучшие цены (топ 20)</h2>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <table class="admin-table">
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
                                <span style="color: var(--admin-text-secondary);">SKU: <?= Html::encode($price->external_sku) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-secondary">
                                <?= Html::encode($price->source->name) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= number_format($price->price_byn, 2) ?> BYN</strong>
                            <?php if ($price->currency_code !== 'BYN'): ?>
                            <br><small style="color: var(--admin-text-secondary);">
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

<!-- Таблица по дням -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-calendar-week"></i> Детализация по дням</h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
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
                    <td style="color: var(--admin-success);"><?= $day['imported'] ?></td>
                    <td style="color: var(--admin-info);"><?= $day['updated'] ?></td>
                    <td style="color: var(--admin-danger);"><?= $day['errors'] ?></td>
                    <td style="color: var(--admin-warning);"><?= $day['duplicates'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Подключаем Chart.js для графика
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['position' => \yii\web\View::POS_HEAD]);
?>
