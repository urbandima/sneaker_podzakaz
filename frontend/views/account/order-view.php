<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var app\backend\modules\catalog\models\Order $order */

$this->title = 'Заказ #' . ($order->order_number ?: $order->id) . ' - СНИКЕРХЭД';
?>

<style>
.account-page {
    min-height: 100vh;
    background: #f3f4f6;
    padding: 2rem 0;
}

.account-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #fff;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.back-btn:hover {
    background: #f3f4f6;
    color: #000;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    flex: 1;
}

.order-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.order-status-badge.created { background: #dbeafe; color: #1d4ed8; }
.order-status-badge.processing { background: #fef3c7; color: #d97706; }
.order-status-badge.shipped { background: #e0e7ff; color: #4338ca; }
.order-status-badge.delivered { background: #d1fae5; color: #059669; }
.order-status-badge.completed { background: #d1fae5; color: #059669; }
.order-status-badge.cancelled { background: #fee2e2; color: #dc2626; }

.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 991px) {
    .order-grid {
        grid-template-columns: 1fr;
    }
}

.content-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.content-card h2 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 1.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
    background: #e5e7eb;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.8125rem;
    color: #6b7280;
}

.item-price {
    text-align: right;
}

.item-price .price {
    font-weight: 700;
    font-size: 1rem;
}

.item-price .qty {
    font-size: 0.8125rem;
    color: #6b7280;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}

.info-item {
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 10px;
}

.info-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: #1f2937;
}

.order-summary {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
}

.summary-row.total {
    font-size: 1.125rem;
    font-weight: 700;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 2px solid #e5e7eb;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #e5e7eb;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-item.active .timeline-dot {
    background: #3b82f6;
    box-shadow: 0 0 0 2px #3b82f6;
}

.timeline-item.completed .timeline-dot {
    background: #10b981;
    box-shadow: 0 0 0 2px #10b981;
}

.timeline-content h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.timeline-content p {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
}

.track-number {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    margin-top: 1rem;
}

.track-number i {
    font-size: 1.5rem;
    color: #0284c7;
}

.track-number .track-info {
    flex: 1;
}

.track-number .track-label {
    font-size: 0.75rem;
    color: #0369a1;
}

.track-number .track-value {
    font-weight: 700;
    color: #0c4a6e;
}

.help-card {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 12px;
    padding: 1rem 1.25rem;
}

.help-card h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #92400e;
    margin: 0 0 0.5rem 0;
}

.help-card p {
    font-size: 0.8125rem;
    color: #a16207;
    margin: 0 0 0.75rem 0;
}

.help-card a {
    color: #92400e;
    font-weight: 600;
    text-decoration: none;
}

.help-card a:hover {
    text-decoration: underline;
}
</style>

<div class="account-page">
    <div class="account-container">
        <div class="page-header">
            <a href="<?= Url::to(['/account/orders']) ?>" class="back-btn">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Заказ #<?= Html::encode($order->order_number ?: $order->id) ?></h1>
            <span class="order-status-badge <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
        </div>

        <div class="order-grid">
            <div class="order-main">
                <div class="content-card">
                    <h2><i class="bi bi-box-seam"></i> Состав заказа</h2>
                    
                    <?php if (!empty($order->orderItems)): ?>
                        <div class="order-items">
                            <?php foreach ($order->orderItems as $item): ?>
                                <div class="order-item">
                                    <?php if ($item->product && $item->product->image): ?>
                                        <img src="<?= Html::encode($item->product->image) ?>" alt="" class="item-image">
                                    <?php else: ?>
                                        <div class="item-image" style="display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-image" style="font-size:2rem;color:#9ca3af;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-info">
                                        <div class="item-name"><?= Html::encode($item->product_name ?: 'Товар') ?></div>
                                        <div class="item-details">
                                            <?php if ($item->size): ?>Размер: <?= Html::encode($item->size) ?><?php endif; ?>
                                            <?php if ($item->color): ?> • Цвет: <?= Html::encode($item->color) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        <div class="price"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?></div>
                                        <div class="qty">× <?= $item->quantity ?> шт.</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #6b7280;">Информация о товарах недоступна</p>
                    <?php endif; ?>
                    
                    <div class="order-summary">
                        <?php if ($order->product_price): ?>
                            <div class="summary-row">
                                <span>Товары</span>
                                <span><?= Yii::$app->formatter->asCurrency($order->product_price, 'BYN') ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order->logistics_price): ?>
                            <div class="summary-row">
                                <span>Доставка</span>
                                <span><?= Yii::$app->formatter->asCurrency($order->logistics_price, 'BYN') ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order->commission_price): ?>
                            <div class="summary-row">
                                <span>Комиссия</span>
                                <span><?= Yii::$app->formatter->asCurrency($order->commission_price, 'BYN') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Итого</span>
                            <span><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></span>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-geo-alt"></i> Информация о доставке</h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Получатель</div>
                            <div class="info-value"><?= Html::encode($order->client_name ?: '-') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Телефон</div>
                            <div class="info-value"><?= Html::encode($order->client_phone ?: '-') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= Html::encode($order->client_email ?: '-') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Способ доставки</div>
                            <div class="info-value"><?= Html::encode($order->delivery_method ?: '-') ?></div>
                        </div>
                    </div>
                    
                    <?php if ($order->delivery_address || $order->full_address): ?>
                        <div class="info-item" style="margin-top: 1rem;">
                            <div class="info-label">Адрес доставки</div>
                            <div class="info-value"><?= Html::encode($order->full_address ?: $order->delivery_address) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order->china_track_number): ?>
                        <div class="track-number">
                            <i class="bi bi-truck"></i>
                            <div class="track-info">
                                <div class="track-label">Трек-номер для отслеживания</div>
                                <div class="track-value"><?= Html::encode($order->china_track_number) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-sidebar">
                <div class="content-card">
                    <h2><i class="bi bi-clock-history"></i> Статус заказа</h2>
                    
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Заказ создан</h4>
                                <p><?= Yii::$app->formatter->asDatetime($order->created_at, 'short') ?></p>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?= in_array($order->status, ['processing', 'shipped', 'delivered', 'completed']) ? 'completed' : '' ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>В обработке</h4>
                                <p>Заказ принят в работу</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?= in_array($order->status, ['shipped', 'delivered', 'completed']) ? 'completed' : ($order->status === 'processing' ? 'active' : '') ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Отправлен</h4>
                                <p>Передан в службу доставки</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item <?= in_array($order->status, ['delivered', 'completed']) ? 'completed' : ($order->status === 'shipped' ? 'active' : '') ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Доставлен</h4>
                                <p>Заказ получен</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-receipt"></i> Детали заказа</h2>
                    
                    <div class="info-item" style="margin-bottom: 0.75rem;">
                        <div class="info-label">Номер заказа</div>
                        <div class="info-value"><?= Html::encode($order->order_number ?: $order->id) ?></div>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 0.75rem;">
                        <div class="info-label">Дата оформления</div>
                        <div class="info-value"><?= Yii::$app->formatter->asDatetime($order->created_at, 'medium') ?></div>
                    </div>
                    
                    <?php if ($order->delivery_date): ?>
                        <div class="info-item">
                            <div class="info-label">Ожидаемая доставка</div>
                            <div class="info-value"><?= Html::encode($order->delivery_date) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="help-card">
                    <h4><i class="bi bi-question-circle"></i> Нужна помощь?</h4>
                    <p>Если у вас есть вопросы по заказу, свяжитесь с нами:</p>
                    <a href="https://t.me/sneakerheadbyweb_bot" target="_blank">
                        <i class="bi bi-telegram"></i> Написать в Telegram
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
