<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

// Подключаем стили для admin-import
$this->registerCssFile('@web/css/pages/admin-import.css');

$this->title = 'Импорт товаров из Poizon';
?>

<div class="admin-import-page">
    <div class="container">
        <div class="admin-import-header">
            <h1><?= Html::encode($this->title) ?></h1>
            <div class="header-actions">
                <?= Html::a('<i class="bi bi-play-circle"></i> Запустить импорт', ['admin-import/run'], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="bi bi-exclamation-triangle"></i> Ошибки', ['admin-import/errors'], ['class' => 'btn btn-warning']) ?>
            </div>
        </div>

        <!-- Статистика -->
        <div class="import-stats-grid">
            <div class="import-stat-card">
                <div class="import-stat-label">Всего импортов</div>
                <div class="import-stat-value"><?= $stats['total_batches'] ?></div>
            </div>
            <div class="import-stat-card">
                <div class="import-stat-label">Успешных</div>
                <div class="import-stat-value import-stat-value--success"><?= $stats['successful_batches'] ?></div>
            </div>
            <div class="import-stat-card">
                <div class="import-stat-label">Товаров импортировано</div>
                <div class="import-stat-value"><?= number_format($stats['total_products_imported']) ?></div>
            </div>
            <div class="import-stat-card">
                <div class="import-stat-label">Успешность</div>
                <div class="import-stat-value"><?= $stats['success_rate'] ?>%</div>
            </div>
        </div>

        <!-- Последние импорты -->
        <div class="import-batches-section">
            <h2>Последние импорты</h2>
            
            <?php if ($dataProvider->count > 0): ?>
                <div class="import-batches-list">
                    <?php foreach ($dataProvider->getModels() as $batch): ?>
                        <div class="import-batch-card">
                            <div class="import-batch-header">
                                <div class="import-batch-title">
                                    <span class="import-batch-id">#<?= $batch->id ?></span>
                                    <span class="import-batch-status import-batch-status--<?= $batch->status ?>">
                                        <?= Html::encode($batch->getStatusLabel()) ?>
                                    </span>
                                </div>
                                <div class="import-batch-date">
                                    <?= Yii::$app->formatter->asDatetime($batch->created_at) ?>
                                </div>
                            </div>
                            <div class="import-batch-stats">
                                <div class="import-batch-stat">
                                    <span class="import-batch-stat-label">Создано:</span>
                                    <span class="import-batch-stat-value"><?= $batch->created_count ?></span>
                                </div>
                                <div class="import-batch-stat">
                                    <span class="import-batch-stat-label">Обновлено:</span>
                                    <span class="import-batch-stat-value"><?= $batch->updated_count ?></span>
                                </div>
                                <div class="import-batch-stat">
                                    <span class="import-batch-stat-label">Ошибок:</span>
                                    <span class="import-batch-stat-value import-batch-stat-value--error"><?= $batch->error_count ?></span>
                                </div>
                                <div class="import-batch-stat">
                                    <span class="import-batch-stat-label">Успешность:</span>
                                    <span class="import-batch-stat-value"><?= $batch->getSuccessRate() ?>%</span>
                                </div>
                            </div>
                            <div class="import-batch-actions">
                                <?= Html::a('<i class="bi bi-eye"></i> Детали', ['admin-import/view', 'id' => $batch->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?= yii\widgets\LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'pagination'],
                    'linkOptions' => ['class' => 'page-link'],
                    'activePageCssClass' => 'active',
                ]) ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Импорты еще не запускались. Нажмите "Запустить импорт" для начала.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
