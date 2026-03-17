<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

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

<style>
.admin-import-page {
    padding: 2rem 0;
}

.admin-import-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.admin-import-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.import-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.import-stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.import-stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.import-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a2e;
}

.import-stat-value--success {
    color: #28a745;
}

.import-batches-section h2 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.import-batches-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.import-batch-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
}

.import-batch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.import-batch-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.import-batch-id {
    font-weight: 700;
    font-size: 1.125rem;
}

.import-batch-status {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.import-batch-status--pending {
    background: #fff3cd;
    color: #856404;
}

.import-batch-status--running {
    background: #d1ecf1;
    color: #0c5460;
}

.import-batch-status--completed {
    background: #d4edda;
    color: #155724;
}

.import-batch-status--failed {
    background: #f8d7da;
    color: #721c24;
}

.import-batch-date {
    color: #6c757d;
    font-size: 0.875rem;
}

.import-batch-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.import-batch-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.import-batch-stat-label {
    font-size: 0.75rem;
    color: #6c757d;
}

.import-batch-stat-value {
    font-size: 1.125rem;
    font-weight: 600;
}

.import-batch-stat-value--error {
    color: #dc3545;
}

.import-batch-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #4472C4;
    color: white;
}

.btn-primary:hover {
    background: #3a5cb8;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-outline-primary {
    background: transparent;
    border: 1px solid #4472C4;
    color: #4472C4;
}

.btn-outline-primary:hover {
    background: #4472C4;
    color: white;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    margin-top: 2rem;
    justify-content: center;
}

.page-link {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #4472C4;
    text-decoration: none;
    border-radius: 6px;
}

.page-link:hover {
    background: #f8f9fa;
}

.pagination .active .page-link {
    background: #4472C4;
    color: white;
    border-color: #4472C4;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>
