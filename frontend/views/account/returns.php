<?php

/**
 * Returns Section - Секция возвратов в личном кабинете
 */

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $returns */
/** @var app\backend\modules\return\models\ReturnPolicy $policy */
?>

<div class="returns-section">
    <div class="section-header">
        <h2><i class="bi bi-arrow-repeat"></i> Возвраты</h2>
        <a href="/account/return/create" class="btn-create-return">
            <i class="bi bi-plus"></i> Оформить возврат
        </a>
    </div>
    
    <!-- Return Policy Info -->
    <div class="return-policy-info">
        <div class="policy-icon">📋</div>
        <div class="policy-content">
            <h4>Условия возврата</h4>
            <ul>
                <li>Срок возврата: <strong><?= $policy->return_period_days ?> дней</strong></li>
                <?php if ($policy->requires_original_packaging): ?>
                <li>Требуется оригинальная упаковка</li>
                <?php endif; ?>
                <?php if ($policy->requires_tags): ?>
                <li>Требуются ярлыки и бирки</li>
                <?php endif; ?>
                <?php if ($policy->restocking_fee > 0): ?>
                <li>Комиссия за возврат: <strong><?= $policy->restocking_fee ?>%</strong></li>
                <?php endif; ?>
                <?php if ($policy->free_return_shipping): ?>
                <li>Бесплатная обратная доставка</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <?php if (empty($returns)): ?>
    <!-- Empty State -->
    <div class="empty-returns">
        <div class="empty-icon">📦</div>
        <h3>Нет активных возвратов</h3>
        <p>Вы можете оформить возврат товара в течение <?= $policy->return_period_days ?> дней после доставки</p>
        <a href="/account/orders" class="btn-view-orders">Посмотреть заказы</a>
    </div>
    
    <?php else: ?>
    <!-- Returns List -->
    <div class="returns-list">
        <?php foreach ($returns as $return): ?>
        <div class="return-item">
            <div class="return-header">
                <div class="return-number">
                    <span class="label">Заявка</span>
                    <strong><?= $return->return_number ?></strong>
                </div>
                <div class="return-status status-<?= $return->status ?>">
                    <?= $return->getStatusName() ?>
                </div>
            </div>
            
            <div class="return-body">
                <div class="return-info">
                    <div class="info-row">
                        <span class="info-label">Заказ:</span>
                        <a href="/account/orders/<?= $return->order_id ?>" class="info-value">
                            #<?= $return->order->order_number ?? $return->order_id ?>
                        </a>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Причина:</span>
                        <span class="info-value"><?= $return->getReasonName() ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Сумма возврата:</span>
                        <span class="info-value return-amount"><?= Yii::$app->formatter->asCurrency($return->refund_amount, 'BYN') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Дата:</span>
                        <span class="info-value"><?= Yii::$app->formatter->asDate($return->created_at, 'dd.MM.yyyy') ?></span>
                    </div>
                </div>
                
                <div class="return-items">
                    <h4>Возвращаемые товары:</h4>
                    <div class="items-list">
                        <?php foreach ($return->getItems() as $item): ?>
                        <div class="return-item-product">
                            <span class="item-name"><?= Html::encode($item['name'] ?? 'Товар') ?></span>
                            <span class="item-quantity">× <?= $item['quantity'] ?? 1 ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($return->status === 'approved' && $return->tracking_number): ?>
            <div class="return-tracking">
                <i class="bi bi-truck"></i>
                <span>Трек-номер для отправки: <strong><?= $return->tracking_number ?></strong></span>
            </div>
            <?php endif; ?>
            
            <?php if ($return->admin_comment): ?>
            <div class="return-comment">
                <strong>Комментарий:</strong> <?= Html::encode($return->admin_comment) ?>
            </div>
            <?php endif; ?>
            
            <div class="return-actions">
                <a href="/account/returns/<?= $return->id ?>" class="btn-details">
                    <i class="bi bi-eye"></i> Подробнее
                </a>
                <?php if ($return->status === 'pending'): ?>
                <button class="btn-cancel" onclick="cancelReturn(<?= $return->id ?>)">
                    <i class="bi bi-x"></i> Отменить
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.returns-section {
    padding: 2rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header h2 i {
    color: #6366f1;
}

.btn-create-return {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #6366f1;
    color: white;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-create-return:hover {
    background: #4f46e5;
    transform: translateY(-2px);
}

/* Return Policy Info */
.return-policy-info {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.policy-icon {
    font-size: 2rem;
}

.policy-content h4 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.policy-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.policy-content li {
    padding: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
}

.policy-content li::before {
    content: '•';
    margin-right: 0.5rem;
    color: #6366f1;
}

/* Empty State */
.empty-returns {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border-radius: 16px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-returns h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.empty-returns p {
    color: #64748b;
    margin-bottom: 1.5rem;
}

.btn-view-orders {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #0f172a;
    color: white;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
}

/* Returns List */
.returns-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.return-item {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: box-shadow 0.3s;
}

.return-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.return-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.return-number .label {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
}

.return-number strong {
    font-size: 1.125rem;
    color: #0f172a;
}

.return-status {
    padding: 0.375rem 1rem;
    border-radius: 100px;
    font-size: 0.8125rem;
    font-weight: 600;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-processing { background: #dbeafe; color: #1e40af; }
.status-completed { background: #e0e7ff; color: #3730a3; }

.return-body {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.info-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.info-label {
    color: #64748b;
    font-size: 0.875rem;
}

.info-value {
    font-weight: 500;
    color: #0f172a;
}

.info-value a {
    color: #6366f1;
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}

.return-amount {
    color: #10b981;
    font-weight: 700;
}

.return-items h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.return-item-product {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
    font-size: 0.875rem;
}

.return-tracking {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.875rem;
}

.return-comment {
    padding: 1rem 1.5rem;
    background: #fef3c7;
    color: #92400e;
    font-size: 0.875rem;
}

.return-actions {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.btn-details, .btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-details {
    background: #f1f5f9;
    color: #0f172a;
    border: none;
}

.btn-details:hover {
    background: #e2e8f0;
}

.btn-cancel {
    background: transparent;
    color: #ef4444;
    border: 1px solid #fecaca;
}

.btn-cancel:hover {
    background: #fef2f2;
}

/* Responsive */
@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .return-body {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .return-actions {
        flex-direction: column;
    }
}
</style>

<script>
function cancelReturn(returnId) {
    if (!confirm('Вы уверены, что хотите отменить заявку на возврат?')) return;
    
    fetch(`/api/v1/returns/${returnId}/cancel`, {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка при отмене');
        }
    });
}
</script>
