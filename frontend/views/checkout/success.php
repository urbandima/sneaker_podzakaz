<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */
/** @var app\backend\modules\catalog\models\Product[] $recommendedProducts */
/** @var array|null $autoAccount */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\CheckoutAsset;

$this->title = 'Заказ принят';
$this->registerMetaTag([
    'name' => 'robots',
    'content' => 'noindex, nofollow'
]);

CheckoutAsset::register($this);
?>

<div class="checkout-success-page">
    <div class="success-overlay"></div>
    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3">
                <circle cx="32" cy="32" r="30" stroke="currentColor"/>
                <path d="M18 32l10 10 18-18" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <h1 class="success-title">Заказ #<?= Html::encode($model->order_number) ?> принят</h1>
        
        <?php if ($model->payment_method === 'erip' || $model->payment_method === 'card_online'): ?>
            <div class="next-step-card">
                <h3>Следующий шаг: Оплата</h3>
                <p>Ваш заказ ожидает оплаты. Вы можете оплатить его через ЕРИП или картой онлайн.</p>
                <div class="payment-details">
                    <p>Сумма: <strong><?= number_format($model->total_amount, 2, '.', ' ') ?> BYN</strong></p>
                    <p>Номер заказа: <strong id="copyData"><?= Html::encode($model->order_number) ?></strong></p>
                </div>
                <button class="btn btn-secondary" onclick="copyToClipboard('<?= Html::encode($model->order_number) ?>')">
                    <i class="bi bi-copy"></i> Скопировать номер
                </button>
            </div>
        <?php else: ?>
            <div class="next-step-card">
                <h3>Следующий шаг: Подтверждение</h3>
                <p>Наш менеджер свяжется с вами в ближайшее время для подтверждения заказа.</p>
                <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn btn-primary" style="width:100%; justify-content:center;">
                    <i class="bi bi-telegram"></i> Написать менеджеру
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($autoAccount)): ?>
            <div class="auto-account-card">
                <h3>Ваш личный кабинет создан</h3>
                <p>Мы автоматически создали для вас аккаунт. Используйте эти данные для входа:</p>
                <div class="account-credentials">
                    <p>Логин: <strong><?= Html::encode($autoAccount['phone']) ?></strong></p>
                    <p>Пароль: <strong><?= Html::encode($autoAccount['password']) ?></strong></p>
                </div>
                <p class="account-hint">Данные для входа отправлены по SMS. Рекомендуем сменить пароль в личном кабинете.</p>
            </div>
        <?php endif; ?>

        <div class="success-actions">
            <a href="<?= Url::to(['/account/orders']) ?>" class="btn-track-order">
                Отследить заказ <i class="bi bi-arrow-right"></i>
            </a>
            <a href="<?= Url::to(['/catalog']) ?>" class="btn-continue-shopping">
                Продолжить покупки
            </a>
        </div>
    </div>
</div>

<style>
.checkout-success-page {
    padding: 64px 0;
    min-height: calc(100vh - var(--header-height));
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-surface);
    position: relative;
}

.success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(2px);
    z-index: 0;
}

.success-container {
    max-width: 480px;
    width: 100%;
    margin: 0 auto;
    padding: 48px 32px;
    background: var(--color-white);
    border-radius: 24px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--color-border);
    position: relative;
    z-index: 1;
}

.success-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 24px;
    color: var(--color-black);
}

.success-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 32px;
}

.next-step-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    text-align: left;
}

.next-step-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 12px;
}

.next-step-card p {
    color: var(--color-muted);
    font-size: 0.9375rem;
    margin-bottom: 16px;
}

.payment-details {
    background: var(--color-white);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    border: 1px solid var(--color-border);
}

.payment-details p {
    margin: 0 0 8px 0;
    color: var(--color-black);
    display: flex;
    justify-content: space-between;
}

.payment-details p:last-child {
    margin: 0;
}

.next-step-card .btn {
    width: 100%;
    justify-content: center;
}

.auto-account-card {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    text-align: left;
}

.auto-account-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 12px;
    color: #166534;
}

.auto-account-card p {
    color: var(--color-muted);
    font-size: 0.9375rem;
    margin-bottom: 12px;
}

.account-credentials {
    background: var(--color-white);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 12px;
    border: 1px solid #bbf7d0;
}

.account-credentials p {
    margin: 0 0 8px 0;
    color: var(--color-black);
    display: flex;
    justify-content: space-between;
    font-size: 0.9375rem;
}

.account-credentials p:last-child {
    margin: 0;
}

.account-hint {
    font-size: 0.8125rem !important;
    color: #166534 !important;
    margin-bottom: 0 !important;
}

.success-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.btn-track-order {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 600;
    color: var(--color-black);
    text-decoration: underline;
    padding: 12px;
}

.btn-track-order:hover {
    color: var(--color-muted);
}

.btn-continue-shopping {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: var(--color-black);
    color: var(--color-white);
    border-radius: 100px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s ease;
}

.btn-continue-shopping:hover {
    background: #333;
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Номер заказа скопирован!');
    }, function(err) {
        console.error('Ошибка копирования: ', err);
    });
}
</script>
