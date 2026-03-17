<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$this->title = 'Заказ #' . $model->order_number;
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);
?>

<div class="order-view-page">
    <div class="container">
        <div class="order-view-header">
            <h1><?= Html::encode($this->title) ?></h1>
            <div class="order-status">
                <span class="order-status-badge order-status-<?= $model->status ?>">
                    <?= Html::encode($model->getStatusLabel()) ?>
                </span>
            </div>
        </div>

        <div class="order-view-content">
            <div class="order-view-grid">
                <!-- Информация о заказе -->
                <div class="order-view-section">
                    <h2>Информация о заказе</h2>
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <span class="order-info-label">Номер заказа:</span>
                            <span class="order-info-value"><?= Html::encode($model->order_number) ?></span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Дата создания:</span>
                            <span class="order-info-value"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Сумма:</span>
                            <span class="order-info-value order-info-value--highlight">
                                <?= Yii::$app->formatter->asCurrency($model->total_amount, 'BYN') ?>
                            </span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Статус:</span>
                            <span class="order-info-value"><?= Html::encode($model->getStatusLabel()) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="order-view-section">
                    <h2>Информация о клиенте</h2>
                    <div class="order-client-info">
                        <div class="order-client-item">
                            <span class="order-client-label">Имя:</span>
                            <span class="order-client-value"><?= Html::encode($model->client_name) ?></span>
                        </div>
                        <div class="order-client-item">
                            <span class="order-client-label">Телефон:</span>
                            <span class="order-client-value"><?= Html::encode($model->client_phone) ?></span>
                        </div>
                        <div class="order-client-item">
                            <span class="order-client-label">Email:</span>
                            <span class="order-client-value"><?= Html::encode($model->client_email) ?></span>
                        </div>
                        <?php if ($model->client_address): ?>
                        <div class="order-client-item">
                            <span class="order-client-label">Адрес:</span>
                            <span class="order-client-value"><?= Html::encode($model->client_address) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Товары в заказе -->
                <div class="order-view-section order-view-section--full">
                    <h2>Товары в заказе</h2>
                    <div class="order-items">
                        <?php foreach ($model->orderItems as $item): ?>
                        <div class="order-item">
                            <div class="order-item-info">
                                <div class="order-item-name">
                                    <?= Html::encode($item->product_name) ?>
                                </div>
                                <div class="order-item-details">
                                    <?php if ($item->size): ?>
                                    <span class="order-item-size">Размер: <?= Html::encode($item->size) ?></span>
                                    <?php endif; ?>
                                    <span class="order-item-quantity">Кол-во: <?= Html::encode($item->quantity) ?></span>
                                </div>
                            </div>
                            <div class="order-item-price">
                                <?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">
                        <span class="order-total-label">Итого:</span>
                        <span class="order-total-value">
                            <?= Yii::$app->formatter->asCurrency($model->total_amount, 'BYN') ?>
                        </span>
                    </div>
                </div>

                <!-- Подтверждение оплаты -->
                <?php if ($model->payment_proof): ?>
                <div class="order-view-section order-view-section--full">
                    <h2>Подтверждение оплаты</h2>
                    <div class="order-payment-proof">
                        <a href="<?= Html::encode($model->payment_proof) ?>" target="_blank" class="btn btn-primary">
                            <i class="bi bi-download"></i>
                            Скачать подтверждение
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Загрузка подтверждения оплаты -->
                <?php if (!$model->payment_proof && in_array($model->status, ['created', 'confirmed'])): ?>
                <div class="order-view-section order-view-section--full">
                    <h2>Загрузить подтверждение оплаты</h2>
                    <div class="order-upload-payment">
                        <form action="<?= Url::to(['order/upload-payment', 'token' => $model->token]) ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
                            <div class="form-group">
                                <label for="payment_proof">Файл подтверждения оплаты</label>
                                <input type="file" name="payment_proof" id="payment_proof" accept="image/*,.pdf" required>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="offer_accepted" required>
                                    Я подтверждаю, что ознакомился с условиями оферты
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i>
                                Загрузить
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.order-view-page {
    padding: 40px 0;
    min-height: calc(100vh - 200px);
}

.order-view-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.order-view-header h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: #1a1a2e;
}

.order-status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.order-status-created {
    background: #e9ecef;
    color: #6c757d;
}

.order-status-confirmed {
    background: #d1ecf1;
    color: #0c5460;
}

.order-status-paid {
    background: #d4edda;
    color: #155724;
}

.order-status-ordered {
    background: #fff3cd;
    color: #856404;
}

.order-status-shipped {
    background: #cce5ff;
    color: #004085;
}

.order-status-delivered {
    background: #d4edda;
    color: #155724;
}

.order-status-canceled {
    background: #f8d7da;
    color: #721c24;
}

.order-view-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.order-view-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.order-view-section--full {
    grid-column: 1 / -1;
}

.order-view-section h2 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 20px;
}

.order-info-grid,
.order-client-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.order-info-item,
.order-client-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #dee2e6;
}

.order-info-item:last-child,
.order-client-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.order-info-label,
.order-client-label {
    font-size: 14px;
    color: #6c757d;
}

.order-info-value,
.order-client-value {
    font-size: 16px;
    font-weight: 500;
    color: #1a1a2e;
}

.order-info-value--highlight {
    color: #4472C4;
    font-size: 20px;
    font-weight: 700;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.order-item-info {
    flex: 1;
}

.order-item-name {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.order-item-details {
    display: flex;
    gap: 16px;
    font-size: 14px;
    color: #6c757d;
}

.order-item-price {
    font-size: 18px;
    font-weight: 600;
    color: #4472C4;
}

.order-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
}

.order-total-label {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
}

.order-total-value {
    font-size: 24px;
    font-weight: 700;
    color: #4472C4;
}

.order-payment-proof {
    display: flex;
    gap: 16px;
}

.order-upload-payment {
    max-width: 500px;
}

.order-upload-payment .form-group {
    margin-bottom: 16px;
}

.order-upload-payment label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #1a1a2e;
}

.order-upload-payment input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.order-upload-payment input[type="checkbox"] {
    margin-right: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
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

@media (max-width: 768px) {
    .order-view-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .order-view-grid {
        grid-template-columns: 1fr;
    }

    .order-view-header h1 {
        font-size: 24px;
    }
}
</style>
