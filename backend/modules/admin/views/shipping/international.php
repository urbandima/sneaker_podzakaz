<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $methods */

$this->title = 'Международная доставка';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="shipping-page">
    <div class="shipping-header">
        <div>
            <h1><i class="bi bi-globe" style="color: #3b82f6;"></i> <?= Html::encode($this->title) ?></h1>
            <p style="margin: 0.25rem 0 0; color: #6b7280; font-size: 0.9rem;">
                Доставка товаров из-за рубежа
            </p>
        </div>
        <a href="<?= Url::to(['/admin/shipping']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
            <i class="bi bi-arrow-left"></i> Назад
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
                    <i class="badmin-i b -dmni-"></i> dm-btn-sm
                    Редактировать tton>
                <button class="btn-action btn-secondary-action">
                    <i class="badmin-i b -dmue-></i> adminb-sm
                    <?= $method['status'] === 'utton>
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
