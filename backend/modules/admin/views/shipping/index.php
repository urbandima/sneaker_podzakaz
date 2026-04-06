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
