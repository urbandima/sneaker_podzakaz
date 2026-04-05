<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */
/** @var array $methods */

$this->title = 'Управление доставкой';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];

$internationalCount = count(array_filter($methods, fn($m) => $m['type'] === 'international'));
$localCount = count(array_filter($methods, fn($m) => $m['type'] === 'local'));
$activeCount = count(array_filter($methods, fn($m) => $m['status'] === 'active'));
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
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.shipping-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 1rem;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.stat-label {
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    color: #9ca3af;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
}

.shipping-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.category-card {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    background: #ffffff;
    transition: all 0.2s ease;
}

.category-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.category-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.category-icon.international {
    background: linear-gradient(135deg, #3b82f6, #06b6d4);
    color: white;
}

.category-icon.local {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: white;
}

.category-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.category-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.category-description {
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.category-actions {
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

.methods-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.methods-table th {
    text-align: left;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
    border-bottom: 2px solid #e5e7eb;
    color: #111827;
    background: #f9fafb;
}

.methods-table td {
    padding: 0.65rem 0.5rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
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

.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 6px;
    font-size: 0.75rem;
    padding: 0.25rem 0.6rem;
    font-weight: 500;
}

.type-international { background: #e0e7ff; color: #312e81; }
.type-local { background: #ecfdf5; color: #047857; }
</style>

<div class="shipping-page">
    <div class="shipping-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p style="margin: 0.25rem 0 0; color: #6b7280; font-size: 0.9rem;">
                Методов доставки: <?= count($methods) ?>
            </p>
        </div>
        <a href="<?= Url::to(['/admin/shipping/settings']) ?>" class="btn-action btn-primary-action">
            <i class="bi bi-gear"></i>
            Настройки
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Всего методов</div>
            <div class="stat-value"><?= count($methods) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Активных</div>
            <div class="stat-value"><?= $activeCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Международных</div>
            <div class="stat-value"><?= $internationalCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Внутри страны</div>
            <div class="stat-value"><?= $localCount ?></div>
        </div>
    </div>

    <div class="shipping-categories">
        <!-- Международная доставка -->
        <div class="category-card">
            <div class="category-header">
                <div class="category-icon international">
                    <i class="bi bi-globe"></i>
                </div>
                <div>
                    <h3 class="category-title">Международная доставка</h3>
                    <div class="category-count"><?= $internationalCount ?> методов</div>
                </div>
            </div>
            <p class="category-description">
                Доставка товаров из-за рубежа. Экспресс и стандартные методы с разными сроками и стоимостью.
            </p>
            <div class="category-actions">
                <a href="<?= Url::to(['/admin/shipping/international']) ?>" class="btn-action btn-primary-action">
                    <i class="bi bi-arrow-right"></i>
                    Управление
                </a>
            </div>
        </div>

        <!-- Доставка внутри страны -->
        <div class="category-card">
            <div class="category-header">
                <div class="category-icon local">
                    <i class="bi bi-geo-alt"></i>
                </div>
                <div>
                    <h3 class="category-title">Доставка внутри страны</h3>
                    <div class="category-count"><?= $localCount ?> методов</div>
                </div>
            </div>
            <p class="category-description">
                Локальная доставка: курьер, самовывоз, почта. Доступные и быстрые способы получения заказа.
            </p>
            <div class="category-actions">
                <a href="<?= Url::to(['/admin/shipping/local']) ?>" class="btn-action btn-primary-action">
                    <i class="bi bi-arrow-right"></i>
                    Управление
                </a>
            </div>
        </div>
    </div>

    <!-- Таблица всех методов -->
    <h2 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">Все методы доставки</h2>
    <div style="border: 1px solid #e5e7eb; border-radius: 16px; padding: 1rem; overflow-x: auto;">
        <table class="methods-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Перевозчик</th>
                    <th>Срок доставки</th>
                    <th>Стоимость</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $method): ?>
                <tr>
                    <td>
                        <strong><?= Html::encode($method['name']) ?></strong>
                        <div style="font-size: 0.75rem; color: #6b7280;">
                            <?= Html::encode($method['description']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge type-<?= $method['type'] ?>">
                            <?= $method['type_label'] ?>
                        </span>
                    </td>
                    <td><?= Html::encode($method['carrier']) ?></td>
                    <td><?= Html::encode($method['delivery_time']) ?></td>
                    <td>
                        <?php if ($method['base_cost'] > 0): ?>
                            <?= number_format($method['base_cost'], 2) ?> <?= $method['currency'] ?>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: 500;">Бесплатно</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $method['status'] ?>">
                            <?= $method['status'] === 'active' ? 'Активен' : 'Неактивен' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
