<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $methods */

$this->title = 'Международная доставка';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.shipping-page {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
}

.shipping-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.shipping-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.method-card {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    background: #ffffff;
    transition: all 0.2s ease;
}

.method-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.method-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.method-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    font-size: 0.75rem;
    padding: 0.2rem 0.8rem;
    font-weight: 600;
}

.status-active { background: #d1fae5; color: #065f46; }
.status-inactive { background: #fee2e2; color: #991b1b; }

.method-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: #111827;
}

.method-description {
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
}

.method-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border-radius: 8px;
    border: 1px solid transparent;
    padding: 0.6rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-primary-action {
    background: #111827;
    color: #ffffff;
}

.btn-primary-action:hover {
    background: #000000;
}

.btn-secondary-action {
    background: #f3f4f6;
    color: #111827;
    border-color: #e5e7eb;
}

.btn-secondary-action:hover {
    background: #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #d1d5db;
}
</style>

<div class="shipping-page">
    <div class="shipping-header">
        <div>
            <h1><i class="bi bi-globe" style="color: #3b82f6;"></i> <?= Html::encode($this->title) ?></h1>
            <p style="margin: 0.25rem 0 0; color: #6b7280; font-size: 0.9rem;">
                Доставка товаров из-за рубежа
            </p>
        </div>
        <a href="<?= Url::to(['/admin/shipping']) ?>" class="btn-action btn-secondary-action">
            <i class="bi bi-arrow-left"></i>
            Назад
        </a>
    </div>

    <?php if (!empty($methods)): ?>
    <div class="methods-grid">
        <?php foreach ($methods as $method): ?>
        <div class="method-card">
            <div class="method-header">
                <h3 class="method-title"><?= Html::encode($method['name']) ?></h3>
                <span class="status-badge status-<?= $method['status'] ?>">
                    <?= $method['status'] === 'active' ? 'Активен' : 'Неактивен' ?>
                </span>
            </div>
            
            <div class="method-description">
                <?= Html::encode($method['description']) ?>
            </div>
            
            <div class="method-details">
                <div class="detail-item">
                    <span class="detail-label">Перевозчик</span>
                    <span class="detail-value"><?= Html::encode($method['carrier']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Срок доставки</span>
                    <span class="detail-value"><?= Html::encode($method['delivery_time']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Стоимость</span>
                    <span class="detail-value">
                        <?php if ($method['base_cost'] > 0): ?>
                            <?= number_format($method['base_cost'], 2) ?> <?= $method['currency'] ?>
                        <?php else: ?>
                            <span style="color: #10b981;">Бесплатно</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">ID метода</span>
                    <span class="detail-value" style="font-family: monospace; font-size: 0.8rem;">
                        <?= Html::encode($method['id']) ?>
                    </span>
                </div>
            </div>
            
            <div class="method-actions">
                <button class="btn-action btn-primary-action">
                    <i class="bi bi-pencil"></i>
                    Редактировать
                </button>
                <button class="btn-action btn-secondary-action">
                    <i class="bi bi-pause"></i>
                    <?= $method['status'] === 'active' ? 'Отключить' : 'Включить' ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-globe"></i>
        <h3>Нет методов международной доставки</h3>
        <p>Добавьте метод доставки для начала работы</p>
    </div>
    <?php endif; ?>
</div>
