<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order $order */

$this->title = 'Заказ #' . ($order->order_number ?: $order->id) . ' - СНИКЕРХЭД';
AppAsset::register($this);
?>

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
                                        <div class="item-image item-image--placeholder">
                                            <i class="bi bi-image placeholder-icon"></i>
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
                        <p class="text-muted">Информация о товарах недоступна</p>
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
                        <div class="info-item info-item-spacing-top">
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
                    
                    <div class="info-item info-item-spacing-bottom">
                        <div class="info-label">Номер заказа</div>
                        <div class="info-value"><?= Html::encode($order->order_number ?: $order->id) ?></div>
                    </div>
                    
                    <div class="info-item info-item-spacing-bottom">
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
