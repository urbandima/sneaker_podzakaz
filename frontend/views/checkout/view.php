<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\CheckoutAsset;

$this->title = 'Заказ №' . $model->order_number;
$company = Yii::$app->settings->getCompany();

// Подключаем AssetBundle для checkout (все стили автоматически с версионированием)
CheckoutAsset::register($this);
?>

<div class="product-page">
    <div class="container-fluid px-2 px-md-4">
        <div class="order-container">
            <!-- Шапка с номером заказа и статусом -->
            <div class="order-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <h1 class="order-title mb-1">Заказ №<?= Html::encode($model->order_number) ?></h1>
                            <div class="text-muted small"><?= Html::encode($model->client_name) ?> • <?= Yii::$app->formatter->asDate($model->created_at) ?></div>
                        </div>
                        <?php if (!empty($model->history)): ?>
                        <button class="btn-history-compact" onclick="showHistoryModal()" title="История изменений">
                            <i class="bi bi-clock-history"></i>
                            <span class="d-none d-md-inline">История</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="status-badge"><?= $model->getStatusLabel() ?></span>
                        <div class="delivery-badge">
                            <i class="bi bi-truck"></i> <?= Html::encode($model->delivery_date) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основной контент: одна колонка -->
            <div class="content-single-column">
                <!-- Товары -->
                <div class="product-card">
                    <h5 class="section-title"><i class="bi bi-cart3"></i> Состав заказа</h5>
                    <div class="products-list">
                        <?php foreach ($model->orderItems as $index => $item): ?>
                        <div class="product-item">
                            <div class="product-number"><?= $index + 1 ?></div>
                            <div class="product-info flex-grow-1">
                                <div class="product-name"><?= Html::encode($item->product_name) ?></div>
                                <div class="product-meta"><?= $item->quantity ?> шт. × <?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</div>
                            </div>
                            <div class="product-total"><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> BYN</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="total-section">
                        <span>Итого к оплате:</span>
                        <strong class="total-amount"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</strong>
                    </div>
                </div>

                <!-- Реквизиты для оплаты -->
                <div class="payment-card-clean">
                    <div class="payment-header-with-btn">
                        <h3 class="payment-title-clean">Реквизиты для оплаты</h3>
                        <button type="button" class="btn-instruction" onclick="openPaymentModal()">
                            <i class="bi bi-book"></i> Инструкция
                        </button>
                    </div>
                    <div class="payment-list-clean">
                        <div class="payment-item-clean">
                            <div class="payment-label-clean">Организация</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean"><?= Html::encode($company['name']) ?></div>
                                <button class="copy-btn-clean" data-copy="<?= Html::encode($company['name']) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-item-clean">
                            <div class="payment-label-clean">УНП</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean"><?= Html::encode($company['unp']) ?></div>
                                <button class="copy-btn-clean" data-copy="<?= Html::encode($company['unp']) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-item-clean">
                            <div class="payment-label-clean">Банк</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean"><?= Html::encode($company['bank']) ?></div>
                                <button class="copy-btn-clean" data-copy="<?= Html::encode($company['bank']) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-item-clean">
                            <div class="payment-label-clean">БИК</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean"><?= Html::encode($company['bic']) ?></div>
                                <button class="copy-btn-clean" data-copy="<?= Html::encode($company['bic']) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-item-clean">
                            <div class="payment-label-clean">Расчетный счет</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean payment-account-clean"><?= Html::encode($company['account']) ?></div>
                                <button class="copy-btn-clean" data-copy="<?= Html::encode($company['account']) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-item-clean">
                            <div class="payment-label-clean">Код назначения платежа</div>
                            <div class="payment-value-wrap">
                                <div class="payment-value-clean">90401</div>
                                <button class="copy-btn-clean" data-copy="90401">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-purpose-clean payment-purpose-highlighted">
                            <div class="payment-label-clean">
                                <i class="bi bi-exclamation-circle-fill text-warning me-2"></i>
                                Назначение платежа
                                <i class="bi bi-exclamation-circle-fill text-warning ms-2"></i>
                            </div>
                            <div class="alert alert-warning mb-2 py-2">
                                <small><i class="bi bi-info-circle me-1"></i>Обязательно укажите это назначение платежа при переводе!</small>
                            </div>
                            <div class="payment-value-wrap">
                                <div class="payment-purpose-value payment-purpose-important">Оплата по договору оферты №<?= Html::encode($model->order_number) ?></div>
                                <button class="copy-btn-clean copy-btn-highlighted" data-copy="Оплата по договору оферты №<?= Html::encode($model->order_number) ?>">
                                    <i class="bi bi-files"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Подтверждение оплаты -->
            <?php if (!$model->payment_proof): ?>
            <section class="confirmation-section">
                <div class="confirmation-card">
                    <div class="confirmation-header">
                        <div>
                            <p class="confirmation-eyebrow">Последний шаг</p>
                            <h3>Загрузите чек об оплате</h3>
                            <p class="confirmation-subtitle">Это поможет нам быстрее проверить заказ и передать его менеджеру.</p>
                        </div>
                        <div class="confirmation-meta">
                            <div>
                                <span class="meta-label">Статус</span>
                                <span class="meta-value warning"><i class="bi bi-hourglass-split"></i> Ожидает подтверждения</span>
                            </div>
                            <div>
                                <span class="meta-label">Форматы</span>
                                <span class="meta-value">JPG · PNG · PDF · до 10 МБ</span>
                            </div>
                        </div>
                    </div>

                    <div class="confirmation-grid">
                        <div class="confirmation-panel info-panel">
                            <div class="chip-row">
                                <span class="chip warning"><i class="bi bi-hourglass-split"></i> Ожидает подтверждения</span>
                                <span class="chip neutral"><i class="bi bi-filetype-pdf"></i> JPG · PNG · PDF · до 10 МБ</span>
                            </div>
                            <ul class="progress-list">
                                <li class="progress-item">
                                    <div class="progress-index">1</div>
                                    <div>
                                        <strong>Сделайте платёж</strong>
                                        <p>Используя реквизиты выше</p>
                                    </div>
                                </li>
                                <li class="progress-item">
                                    <div class="progress-index">2</div>
                                    <div>
                                        <strong>Сохраните чек</strong>
                                        <p>Сфотографируйте или экспортируйте PDF</p>
                                    </div>
                                </li>
                                <li class="progress-item">
                                    <div class="progress-index">3</div>
                                    <div>
                                        <strong>Прикрепите файл</strong>
                                        <p>Мы уведомим менеджера автоматически</p>
                                    </div>
                                </li>
                            </ul>
                            <div class="tip-card">
                                <i class="bi bi-lightbulb"></i>
                                <p>Лучше всего загружаются файлы до 5 МБ. Можно сфотографировать чек прямо с телефона.</p>
                            </div>
                        </div>
                        <div class="confirmation-panel form-panel">
                            <form method="post" action="<?= Url::to(['upload-payment', 'token' => $model->token]) ?>" enctype="multipart/form-data" id="payment-form" class="confirmation-form">
                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                                <div class="upload-box-bottom" id="upload-box">
                                    <label for="file-input" class="upload-label-bottom" id="upload-label">
                                        <div class="upload-icon">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div>
                                            <span>Перетащите чек сюда или выберите файл</span>
                                            <small>Мы поддерживаем чёткие фото, сканы и PDF</small>
                                        </div>
                                    </label>
                                    <input type="file" class="file-input" name="payment_proof" accept="image/*,application/pdf" required id="file-input">
                                    <div class="file-preview-bottom" id="file-preview" style="display: none;">
                                        <div class="preview-content">
                                            <img id="preview-image" class="preview-img-bottom">
                                            <div class="file-info" id="file-info">
                                                <div class="file-name" id="file-name"></div>
                                                <div class="file-size" id="file-size"></div>
                                                <button type="button" class="btn-change-file" id="change-file">
                                                    <i class="bi bi-arrow-repeat"></i> Выбрать другой файл
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <label class="offer-checkbox-bottom">
                                    <input type="checkbox" name="offer_accepted" value="1" id="offer_accepted" required>
                                    <span>Подтверждаю, что согласен с <a href="<?= \yii\helpers\Url::to(['/site/offer-agreement']) ?>" target="_blank">договором оферты</a> и <a href="https://sneaker-head.by/policy" target="_blank">политикой обработки данных</a></span>
                                </label>

                                <div class="confirmation-actions">
                                    <button type="submit" class="btn-submit-bottom">
                                        <i class="bi bi-shield-check"></i> Отправить подтверждение
                                    </button>
                                    <span class="submit-hint"><i class="bi bi-lightning-charge"></i> Обычно проверка занимает до 30 минут</span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            <?php else: ?>
            <section class="confirmation-section">
                <div class="confirmation-card success">
                    <div class="confirmation-header">
                        <div>
                            <p class="confirmation-eyebrow">Спасибо!</p>
                            <h3>Оплата подтверждена</h3>
                            <p class="confirmation-subtitle">Мы получили чек и уже передали информацию менеджеру.</p>
                        </div>
                        <div class="confirmation-meta">
                            <div>
                                <span class="meta-label">Загружено</span>
                                <span class="meta-value"><?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?></span>
                            </div>
                            <div>
                                <span class="meta-label">Статус</span>
                                <span class="meta-value success"><i class="bi bi-check-circle"></i> На проверке</span>
                            </div>
                        </div>
                    </div>

                    <div class="confirmation-grid">
                        <div class="confirmation-panel success-panel">
                            <div class="success-icon-bottom">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <h4>Файл прикреплён</h4>
                            <p class="text-muted">Если потребуется дополнительная информация, менеджер свяжется с вами по контактам из заказа.</p>
                            <div class="status-pill positive">
                                <i class="bi bi-info-circle"></i>
                                Проверяем платёж, это займёт не более нескольких часов
                            </div>
                        </div>
                        <div class="confirmation-panel details-panel">
                            <ul class="meta-list">
                                <li>
                                    <span class="meta-label">Загружено</span>
                                    <span class="meta-value neutral"><?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?></span>
                                </li>
                                <li>
                                    <span class="meta-label">Статус</span>
                                    <span class="meta-value success"><i class="bi bi-check-circle"></i> На проверке</span>
                                </li>
                            </ul>
                            <?php if ($model->payment_proof): ?>
                                <a class="ghost-button" href="<?= Url::to(['download-payment', 'token' => $model->token]) ?>" target="_blank">
                                    <i class="bi bi-download"></i> Скачать загруженный файл
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cookie баннер -->
<div id="cookie-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-content">
        <div class="cookie-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="cookie-text">
            <h6>Использование файлов cookie</h6>
            <p>Мы используем файлы cookie для улучшения работы сайта, анализа трафика и персонализации контента. Продолжая использовать сайт, вы соглашаетесь с использованием cookie.</p>
        </div>
        <div class="cookie-actions">
            <a href="https://sneaker-head.by/page/politika-konfidencialnosti" target="_blank" class="btn-cookie-learn">Подробнее</a>
            <button onclick="acceptCookies()" class="btn-cookie-accept">Принять</button>
        </div>
    </div>
</div>

<!-- Модальное окно истории заказа -->
<?php if (!empty($model->history)): ?>
<div id="history-modal" class="history-modal" onclick="closeHistoryModal(event)">
    <div class="history-modal-content" onclick="event.stopPropagation()">
        <div class="history-modal-header">
            <h5><i class="bi bi-clock-history"></i> История заказа</h5>
            <button class="modal-close" onclick="closeHistoryModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="history-modal-body">
            <div class="history-timeline-modal">
                <?php foreach (array_reverse($model->history) as $index => $history): ?>
                <div class="history-item-modal">
                    <div class="history-dot-modal"></div>
                    <?php if ($index < count($model->history) - 1): ?>
                    <div class="history-line-modal"></div>
                    <?php endif; ?>
                    <div class="history-content-modal">
                        <div class="history-status-modal"><?= $history->getNewStatusLabel() ?></div>
                        <div class="history-date-modal"><?= Yii::$app->formatter->asDatetime($history->created_at, 'medium') ?></div>
                        <?php if ($history->comment): ?>
                            <div class="history-comment-modal"><?= Html::encode($history->comment) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Брендированная шапка */
.brand-header {
    background: #ffffff;
    color: #1a1a1a;
    border-bottom: 2px solid #e5e7eb;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo-img {
    height: 40px;
    width: auto;
}

.brand-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
}

.header-contact {
    color: #1a1a1a;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-contact i {
    color: #ff6b35;
}

/* Кнопка поддержки в хедере */
.btn-support {
    background: #111827;
    color: white;
    border: none;
    padding: 0.5rem 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-support:hover {
    background: #1f2937;
    color: white;
    transform: translateY(-1px);
}

.btn-support i {
    font-size: 1.125rem;
}

/* Компактная кнопка истории в шапке */
.btn-history-compact {
    background: #f9fafb;
    color: #374151;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-history-compact:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #111827;
}

.btn-history-compact i {
    font-size: 0.9375rem;
}

/* Форма оплаты внизу - минималистичный стиль */
.payment-upload-section {
    margin-top: 3rem;
}

.payment-upload-section h4 {
    color: #111827;
    font-weight: 600;
    font-size: 1.5rem;
}

.content-single-column,
.payment-upload-section,
.payment-success-section {
    background: #f5f7fb;
    border-radius: 24px;
    padding: clamp(16px, 4vw, 32px);
    border: 1px solid #e4e7ef;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
}

.order-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: clamp(12px, 3vw, 36px) 0 2rem;
}

.order-header,
.product-card,
.payment-card-clean,
.upload-section-bottom,
.payment-success-bottom,
.info-card,
.history-card {
    background: #fff;
    border: 1px solid #e6e8f0;
    border-radius: 20px;
    padding: clamp(18px, 2vw, 28px);
    box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
}

.content-single-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-title {
    font-size: clamp(1.5rem, 2.4vw, 2.1rem);
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.status-badge {
    background: rgba(34,197,94,0.12);
    color: #15803d;
    padding: 0.45rem 0.95rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: .03em;
}

.delivery-badge {
    background: rgba(14,165,233,0.12);
    color: #0f172a;
    padding: 0.45rem 0.95rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(125,211,252,0.6);
}

.product-card,
.payment-card-clean,
.info-card,
.history-card {
    margin-bottom: 0;
}

/* Чистая минималистичная карточка реквизитов */
.payment-card-clean {
    margin-bottom: 1rem;
}

.payment-header-with-btn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.payment-title-clean {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.btn-instruction {
    background: #111827;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-instruction:hover {
    background: #1f2937;
    transform: translateY(-1px);
}

.payment-list-clean {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.payment-item-clean {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}


.payment-label-clean {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
}

.payment-value-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.payment-value-clean {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #111827;
    flex-grow: 1;
    word-break: break-word;
}

.payment-account-clean {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.copy-btn-clean {
    background: transparent;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.4rem 0.6rem;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.copy-btn-clean:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #111827;
}

.copy-btn-clean:active {
    background: #f3f4f6;
}

.copy-btn-clean.copied {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.payment-purpose-clean {
    margin-top: 0.5rem;
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.payment-purpose-value {
    font-size: 0.9375rem;
    color: #111827;
    font-weight: 500;
    line-height: 1.5;
    flex-grow: 1;
}

.payment-purpose-highlighted {
    background: #fffbeb !important;
    border: 2px solid #fbbf24 !important;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.payment-purpose-highlighted .payment-label-clean {
    color: #d97706;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-purpose-important {
    font-size: 1.0625rem !important;
    font-weight: 600 !important;
    color: #d97706 !important;
    background: white;
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid #fbbf24;
}

.copy-btn-highlighted {
    background: #fbbf24 !important;
    color: white !important;
    border-color: #fbbf24 !important;
}

.copy-btn-highlighted:hover {
    background: #d97706 !important;
    border-color: #d97706 !important;
}

.payment-instruction-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #d97706;
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.payment-instruction-link:hover {
    color: #b45309;
    background: rgba(251, 191, 36, 0.1);
    text-decoration: underline;
}

.section-title {
    font-size: 1.0625rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.products-list {
    margin-bottom: 1rem;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}

.product-item:last-child {
    border-bottom: none;
}

.product-number {
    width: 30px;
    height: 30px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: #6b7280;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
}

.product-name {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #111827;
    margin-bottom: 0.25rem;
}

.product-meta {
    font-size: 0.85rem;
    color: #6b7280;
}

.product-total {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
}

.total-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 2px solid #e5e7eb;
    font-size: 1.125rem;
}

.total-amount {
    font-size: 1.375rem;
    color: #111827;
}

.upload-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.upload-section .section-title {
    color: white;
}

.upload-box {
    background: rgba(255,255,255,0.15);
    border: 2px dashed rgba(255,255,255,0.4);
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-box:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.6);
}

.upload-label {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: white;
}

.upload-label i {
    font-size: 2.5rem;
}

.upload-label span {
    font-weight: 600;
    font-size: 1rem;
}

.upload-label small {
    opacity: 0.8;
}

.file-input {
    display: none;
}

.file-preview {
    margin-top: 1rem;
    text-align: center;
}

.preview-img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 6px;
    border: 2px solid rgba(255,255,255,0.3);
}

.offer-checkbox-bottom {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: rgba(255,255,255,0.15);
    border-radius: 6px;
}

.offer-checkbox-bottom input[type="checkbox"] {
    margin-top: 0.25rem;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.offer-checkbox-bottom label,
.offer-checkbox-bottom span,
.offer-checkbox-bottom a {
    font-size: 0.9rem;
    cursor: pointer;
    margin: 0;
}

.offer-checkbox-bottom a {
    color: #2563eb;
    text-decoration: underline;
}

.btn-submit-bottom {
    width: 100%;
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 0.95rem 1.25rem;
    border-radius: 14px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-submit-bottom:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 25px rgba(37, 99, 235, 0.2);
}

.btn-submit-bottom:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.offer-checkbox-bottom input[type="checkbox"]:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.payment-success {
    background: #f0fdf4;
    color: #111827;
    border: 1px solid #86efac;
}

.payment-success i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #10b981;
}

.payment-success h5 {
    color: #111827;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.payment-success .alert {
    background: white;
    border: 1px solid #d1fae5;
    color: #065f46;
}

.info-card, .history-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.info-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: #4b5563;
}

.info-item i {
    color: #9ca3af;
}

.history-timeline {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.history-item {
    display: flex;
    align-items: start;
    gap: 0.75rem;
}

.history-dot {
    width: 12px;
    height: 12px;
    background: #667eea;
    border-radius: 50%;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.history-content {
    flex-grow: 1;
}

.history-status {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1a1a1a;
}

.history-date {
    font-size: 0.8rem;
    color: #6b7280;
}

.company-contacts {
    background: #1f2937;
    color: white;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.contact-item i {
    color: #10b981;
}

.upload-box-bottom {
    border: 1px solid #cfd6e6;
    border-radius: 18px;
    padding: clamp(24px, 4vw, 40px);
    text-align: center;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: all 0.25s ease;
    background: #f8faff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
}

.upload-box-bottom:hover {
    border-color: #9aa7c4;
    background: #f1f5ff;
    transform: translateY(-2px);
}

.upload-label-bottom {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
    margin-bottom: 1.5rem;
    transition: all 0.2s;
}

.payment-success-section {
    margin-top: 3rem;
}

.confirmation-section {
    margin-top: clamp(24px, 4vw, 48px);
}

.confirmation-card {
    background: #ffffff;
    border-radius: 28px;
    border: 1px solid rgba(15,23,42,0.08);
    box-shadow: 0 30px 80px rgba(15,23,42,0.1);
    padding: clamp(24px, 4vw, 40px);
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.confirmation-card.success {
    border: 1px solid rgba(16, 185, 129, 0.35);
    box-shadow: 0 30px 80px rgba(16, 185, 129, 0.15);
}

.confirmation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: clamp(16px, 3vw, 32px);
}

.confirmation-panel {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(15,23,42,0.08);
    padding: clamp(20px, 3vw, 32px);
    box-shadow: 0 20px 60px rgba(15,23,42,0.08);
}

.confirmation-panel.info-panel {
    background: #f8fbff;
}

.confirmation-panel.form-panel {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
}

.chip.warning {
    background: rgba(245, 158, 11, 0.15);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.chip.neutral {
    background: rgba(148,163,184,0.25);
    color: #475569;
}

.progress-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.progress-item {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.progress-index {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid rgba(148,163,184,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #2563eb;
}

.tip-card {
    margin-top: 1.25rem;
    padding: 1rem 1.25rem;
    border-radius: 16px;
    background: rgba(37,99,235,0.08);
    color: #1d4ed8;
    font-size: 0.9rem;
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.confirmation-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.confirmation-panel.success-panel {
    text-align: center;
    background: #ecfdf5;
}

.confirmation-panel.details-panel {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.meta-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.meta-value.neutral {
    background: rgba(148,163,184,0.2);
    color: #1f2937;
}

.ghost-button {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.65rem 1.1rem;
    border-radius: 12px;
    border: 1px dashed rgba(37,99,235,0.4);
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.ghost-button:hover {
    border-color: #1d4ed8;
    background: rgba(37,99,235,0.08);
}

.confirmation-header {
    display: flex;
    justify-content: space-between;
    gap: clamp(20px, 3vw, 40px);
    flex-wrap: wrap;
    align-items: flex-start;
}

.confirmation-eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.4rem;
    font-weight: 600;
}

.confirmation-header h3 {
    margin: 0;
    font-size: clamp(1.5rem, 2.8vw, 2.25rem);
    color: #0f172a;
}

.confirmation-subtitle {
    margin: 0.5rem 0 0;
    color: #475569;
    font-size: 1rem;
}

.confirmation-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
}

.meta-label {
    display: block;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.meta-value {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    background: rgba(15,23,42,0.05);
    font-weight: 600;
    color: #0f172a;
}

.meta-value.warning {
    background: rgba(251, 191, 36, 0.15);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.meta-value.success {
    background: rgba(16, 185, 129, 0.12);
    color: #0f9d58;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.confirmation-body {
    background: #f5f7fb;
    border-radius: 22px;
    padding: clamp(20px, 3vw, 32px);
    border: 1px solid rgba(148,163,184,0.25);
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
}

.confirmation-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.step-pill {
    flex: 1;
    min-width: 220px;
    background: rgba(255,255,255,0.9);
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    gap: 0.75rem;
    align-items: center;
    border: 1px solid rgba(148,163,184,0.3);
}

.step-pill span {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #2563eb;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.step-pill strong {
    color: #0f172a;
    font-size: 0.95rem;
}

.step-pill p {
    margin: 0.2rem 0 0;
    color: #475569;
    font-size: 0.85rem;
}

.upload-label-bottom {
    margin-bottom: 0 !important;
}

.upload-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    background: rgba(37,99,235,0.12);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
    margin-inline: auto;
}

.confirmation-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    text-align: center;
}

.submit-hint {
    font-size: 0.95rem;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-pill.positive {
    background: rgba(37,99,235,0.1);
    color: #1d4ed8;
}

.success-icon-bottom {
    font-size: 3.5rem;
    color: #10b981;
    margin-bottom: 1.25rem;
    color: #10b981;
}

.payment-success h5 {
    color: #111827;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

@media (max-width: 991px) {
    .order-container {
        max-width: 100%;
    }
    
    .order-header {
        padding: 0.75rem;
    }
    
    .order-title {
        font-size: 1.25rem;
    }
    
    .product-card, .payment-card-clean, .payment-details-card, .upload-section, .info-card, .history-card {
        padding: 1rem;
    }
}

@media (max-width: 768px) {
    .cookie-content {
        flex-direction: column;
        text-align: center;
    }
    
    .cookie-icon {
        font-size: 2rem;
    }
    
    .cookie-text {
        min-width: 100%;
    }
    
    .cookie-actions {
        width: 100%;
        justify-content: center;
    }
    
    .history-modal-content {
        width: 95%;
        max-height: 90vh;
    }
    
    .history-modal-body {
        padding: 1.5rem;
        max-height: calc(90vh - 80px);
    }
    
    .btn-history-compact {
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
    }
    
    .btn-support {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .payment-header {
        padding: 1.25rem;
    }
    
    .payment-header-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .payment-header-title {
        font-size: 1.1rem;
    }
    
    .payment-grid-modern {
        padding: 1rem;
    }
    
    .payment-item-modern {
        padding: 0.85rem;
    }
    
    .copy-btn-modern span {
        display: none;
    }
    
    .copy-btn-primary {
        padding: 0.65rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .order-container {
        padding: 0.5rem 0;
    }
    
    .content-single-column {
        gap: 1rem;
    }
    
    .product-item {
        flex-wrap: wrap;
        padding: 0.5rem;
    }
    
    .product-total {
        width: 100%;
        text-align: right;
        margin-top: 0.5rem;
    }
    
    .payment-value-wrap {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .copy-btn-clean {
        align-self: flex-end;
    }
    
    .cookie-banner {
        padding: 1rem;
    }
    
    .cookie-text h6 {
        font-size: 1rem;
    }
    
    .cookie-text p {
        font-size: 0.85rem;
    }
    
    .btn-cookie-accept {
        padding: 0.65rem 1.5rem;
        font-size: 0.9rem;
    }
}

.history-modal {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(6px);
    z-index: 10000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.history-modal.active {
    opacity: 1;
    pointer-events: auto;
}

.history-modal-content {
    background: #f8faff;
    border-radius: 28px 28px 0 0;
    max-width: 720px;
    width: min(720px, 100%);
    max-height: 90vh;
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 0 -25px 80px rgba(15,23,42,0.35);
    transform: translateY(40px);
    transition: transform 0.35s ease;
    overflow: hidden;
}

.history-modal.active .history-modal-content {
    transform: translateY(0);
}

.history-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fff;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.history-modal-header h5 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: inherit;
}

.modal-close {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.7);
}

.history-modal-body {
    padding: 2rem;
    overflow-y: auto;
    max-height: calc(90vh - 96px);
}

.history-timeline-modal {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.history-item-modal {
    position: relative;
    display: flex;
    gap: 1.5rem;
    padding-bottom: 2rem;
}

.history-item-modal:last-child {
    padding-bottom: 0;
}

.history-dot-modal {
    width: 14px;
    height: 14px;
    background: #2563eb;
    border: 3px solid #fff;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
    z-index: 2;
    margin-top: 4px;
}

.history-line-modal {
    position: absolute;
    left: 6px;
    top: 16px;
    width: 2px;
    height: calc(100% - 10px);
    background: linear-gradient(180deg, rgba(37,99,235,0.2), rgba(37,99,235,0));
    z-index: 1;
}

.history-content-modal {
    flex: 1;
    padding-top: 0;
}

.history-status-modal {
    font-weight: 600;
    font-size: 1rem;
    color: #0f172a;
    margin-bottom: 0.375rem;
}

.history-date-modal {
    font-size: 0.85rem;
    color: #475569;
    margin-bottom: 0.75rem;
}

.history-comment-modal {
    background: rgba(15,23,42,0.03);
    padding: 0.95rem;
    border-radius: 12px;
    border-left: 4px solid rgba(37,99,235,0.35);
    font-size: 0.9rem;
    color: #1f2937;
    line-height: 1.55;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Универсальное копирование в буфер обмена
    document.querySelectorAll('.copy-btn-clean, .copy-btn-full').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.getAttribute('data-copy');
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess(this);
                }).catch(err => {
                    fallbackCopy(text, this);
                });
            } else {
                fallbackCopy(text, this);
            }
        });
    });
    
    // Превью файла
    const fileInput = document.getElementById('file-input');
    const uploadBox = document.getElementById('upload-box');
    const uploadLabel = document.getElementById('upload-label');
    const filePreview = document.getElementById('file-preview');
    const previewImage = document.getElementById('preview-image');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const changeFileBtn = document.getElementById('change-file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Скрываем label загрузки
                uploadLabel.style.display = 'none';
                uploadBox.style.border = '2px solid #10b981';
                uploadBox.style.background = '#f0fdf4';
                
                // Показываем информацию о файле
                fileName.textContent = file.name;
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                fileSize.textContent = `Размер: ${sizeMB} МБ`;
                filePreview.style.display = 'block';
                
                // Показываем превью для изображений
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Для PDF показываем иконку
                    previewImage.style.display = 'none';
                }
            }
        });
        
        // Кнопка смены файла
        if (changeFileBtn) {
            changeFileBtn.addEventListener('click', function() {
                fileInput.click();
            });
        }
    }
    
    // Валидация формы
    const form = document.getElementById('payment-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkbox = document.getElementById('offer_accepted');
            if (!checkbox.checked) {
                e.preventDefault();
                alert('Необходимо согласиться с условиями оферты');
                return false;
            }
            
            const submitBtn = this.querySelector('.btn-submit-bottom');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Загрузка...';
                submitBtn.disabled = true;
            }
        });
    }
});

function showCopySuccess(btn) {
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2"></i>';
    btn.classList.add('copied');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('copied');
    }, 1500);
}

function fallbackCopy(text, btn) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(btn);
    } catch (err) {
        showCopyError(btn);
    }
    
    document.body.removeChild(textarea);
}

function showCopyError(btn) {
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-x-circle"></i>';
    btn.style.background = '#ef4444';
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.style.background = '';
    }, 1500);
}

// Функции для модального окна истории
function showHistoryModal() {
    const modal = document.getElementById('history-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeHistoryModal(event) {
    const modal = document.getElementById('history-modal');
    if (modal && (!event || event.target === modal)) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Закрытие модального окна по Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHistoryModal();
    }
});

// Обработка Cookie баннера
function checkCookieConsent() {
    const cookieConsent = localStorage.getItem('cookieConsent');
    if (!cookieConsent) {
        setTimeout(() => {
            const banner = document.getElementById('cookie-banner');
            if (banner) {
                banner.style.display = 'block';
            }
        }, 1000);
    }
}

function acceptCookies() {
    localStorage.setItem('cookieConsent', 'accepted');
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.style.animation = 'slideDown 0.4s ease-out';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 400);
    }
}

// Проверка cookie при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    checkCookieConsent();
});

// Анимация для скрытия баннера
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            transform: translateY(0);
        }
        to {
            transform: translateY(100%);
        }
    }
`;
document.head.appendChild(style);

// Popup модальное окно с инструкцией по оплате
function openPaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function showBankInstructions(bank) {
    // Скрываем выбор банков
    document.getElementById('bankSelection').style.display = 'none';
    // Показываем инструкцию
    const instruction = document.getElementById('bankInstruction');
    instruction.style.display = 'block';
    
    // Обновляем контент
    const content = getBankInstructionContent(bank);
    document.getElementById('bankInstructionContent').innerHTML = content;
}

function backToBankSelection() {
    document.getElementById('bankInstruction').style.display = 'none';
    document.getElementById('bankSelection').style.display = 'block';
}

function getBankInstructionContent(bank) {
    const instructions = {
        belarusbank: {
            name: 'Беларусбанк',
            logo: 'https://belarusbank.by/favicon.ico',
            steps: [
                { title: 'Войдите в приложение или интернет-банк', desc: 'Откройте мобильное приложение "Беларусбанк" или зайдите в личный кабинет на сайте' },
                { title: 'Выберите "Платежи" → "Оплата услуг"', desc: 'В главном меню найдите раздел платежей' },
                { title: 'Нажмите "Перевод по реквизитам"', desc: 'Выберите опцию перевода на расчётный счёт организации' },
                { title: 'Заполните реквизиты получателя', desc: 'Введите все данные организации из вашего счета', note: 'Обязательно укажите назначение платежа точно как в счете' },
                { title: 'Подтвердите платёж', desc: 'Проверьте данные и подтвердите операцию кодом из SMS' }
            ]
        },
        priorbank: {
            name: 'Приорбанк',
            logo: 'https://www.priorbank.by/favicon.ico',
            steps: [
                { title: 'Откройте приложение "Приорбанк Онлайн"', desc: '' },
                { title: 'Перейдите в "Платежи и переводы"', desc: '' },
                { title: 'Нажмите "На расчётный счёт"', desc: '' },
                { title: 'Введите реквизиты организации', desc: '', note: 'Можно сохранить реквизиты как шаблон для будущих платежей' },
                { title: 'Укажите сумму и назначение платежа', desc: 'Обязательно укажите номер договора оферты в назначении платежа' },
                { title: 'Подтвердите операцию', desc: '' }
            ]
        },
        alfabank: {
            name: 'Альфа-Банк',
            logo: 'https://alfabank.by/favicon.ico',
            steps: [
                { title: 'Войдите в "Альфа-Мобайл"', desc: '' },
                { title: 'Нажмите "Платежи" в нижнем меню', desc: '' },
                { title: 'Выберите "Перевод на счёт в банке"', desc: '' },
                { title: 'Заполните форму перевода', desc: 'Укажите УНП организации, расчётный счёт, БИК банка и назначение платежа' },
                { title: 'Подтвердите платёж с помощью кода из SMS или Face ID', desc: '' }
            ]
        },
        belveb: {
            name: 'БелВЭБ',
            logo: 'https://belveb.by/favicon.ico',
            steps: [
                { title: 'Откройте "БелВЭБ 24/7"', desc: '' },
                { title: 'Перейдите в раздел "Платежи"', desc: '' },
                { title: 'Выберите "Платёж по реквизитам"', desc: '' },
                { title: 'Введите данные организации', desc: 'Все реквизиты указаны в вашем счёте на оплату' },
                { title: 'Укажите назначение платежа и сумму', desc: '' },
                { title: 'Подтвердите операцию', desc: '' }
            ]
        },
        mtbank: {
            name: 'МТБанк',
            logo: 'https://www.mtbank.by/favicon.ico',
            steps: [
                { title: 'Зайдите в приложение "МТБанк Online"', desc: '' },
                { title: 'Выберите "Платежи" → "Переводы и платежи"', desc: '' },
                { title: 'Нажмите "Перевод на счёт в другом банке"', desc: '' },
                { title: 'Заполните реквизиты получателя', desc: 'УНП, расчётный счёт, название организации, БИК банка' },
                { title: 'Укажите назначение платежа', desc: '', note: 'Скопируйте назначение платежа из вашего счёта без изменений' },
                { title: 'Подтвердите перевод кодом', desc: '' }
            ]
        },
        bpsbank: {
            name: 'БПС-Сбербанк',
            logo: 'https://www.bps-sberbank.by/favicon.ico',
            steps: [
                { title: 'Откройте "БПС-Онлайн"', desc: 'Войдите в мобильное приложение или интернет-банк' },
                { title: 'Выберите "Платежи и переводы"', desc: '' },
                { title: 'Нажмите "Перевод организации"', desc: '' },
                { title: 'Заполните реквизиты', desc: 'Введите все данные получателя из вашего счета' },
                { title: 'Укажите назначение платежа', desc: '', note: 'Точно скопируйте назначение из счета' },
                { title: 'Подтвердите платёж', desc: 'Проверьте данные и подтвердите кодом' }
            ]
        },
        belgazprombank: {
            name: 'Белгазпромбанк',
            logo: 'https://www.bgpb.by/favicon.ico',
            steps: [
                { title: 'Зайдите в "БГПБанк Онлайн"', desc: '' },
                { title: 'Перейдите в раздел "Переводы"', desc: '' },
                { title: 'Выберите "Перевод по реквизитам"', desc: '' },
                { title: 'Введите данные организации', desc: 'Все реквизиты из вашего счета на оплату' },
                { title: 'Заполните назначение платежа', desc: '', note: 'Обязательно укажите номер договора' },
                { title: 'Подтвердите операцию', desc: '' }
            ]
        },
        dabrabyt: {
            name: 'Банк Дабрабыт',
            logo: 'https://www.dabrabyt.by/favicon.ico',
            steps: [
                { title: 'Откройте приложение "Дабрабыт"', desc: '' },
                { title: 'Перейдите в "Платежи"', desc: '' },
                { title: 'Выберите "Перевод организации"', desc: '' },
                { title: 'Введите реквизиты', desc: 'Все данные из вашего счета' },
                { title: 'Укажите назначение', desc: '', note: 'Точно как в счете' },
                { title: 'Подтвердите', desc: '' }
            ]
        },
        technobank: {
            name: 'Технобанк',
            logo: 'https://www.technobank.by/favicon.ico',
            steps: [
                { title: 'Зайдите в интернет-банк', desc: '' },
                { title: 'Выберите "Платежи и переводы"', desc: '' },
                { title: 'Нажмите "Платёж юр. лицу"', desc: '' },
                { title: 'Заполните реквизиты', desc: '' },
                { title: 'Укажите назначение платежа', desc: '' },
                { title: 'Подтвердите', desc: '' }
            ]
        },
        idea: {
            name: 'Идея Банк',
            logo: 'https://ideabank.by/favicon.ico',
            steps: [
                { title: 'Откройте "Идея Онлайн"', desc: '' },
                { title: 'Выберите "Платежи"', desc: '' },
                { title: 'Нажмите "Перевод по реквизитам"', desc: '' },
                { title: 'Введите данные организации', desc: '' },
                { title: 'Укажите назначение', desc: '', note: 'Обязательно укажите номер договора' },
                { title: 'Подтвердите', desc: '' }
            ]
        }
    };
    
    const bankData = instructions[bank];
    let html = `
        <div class="bank-header">
            <h3>${bankData.name}</h3>
        </div>
        <ol class="instruction-steps">
    `;
    
    bankData.steps.forEach((step, index) => {
        html += `
            <li class="instruction-step">
                <div class="step-number">${index + 1}</div>
                <div class="step-content">
                    <div class="step-title">${step.title}</div>
                    ${step.desc ? `<div class="step-desc">${step.desc}</div>` : ''}
                    ${step.note ? `<div class="step-note"><i class="bi bi-exclamation-circle"></i> ${step.note}</div>` : ''}
                </div>
            </li>
        `;
    });
    
    html += `
        </ol>
        <div class="instruction-footer">
            <div class="alert alert-warning">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Важно!</strong>
                <ul>
                    <li>Указывайте точное назначение платежа</li>
                    <li>Проверяйте все реквизиты перед подтверждением</li>
                    <li>Сохраните чек — его нужно загрузить в личном кабинете</li>
                </ul>
            </div>
        </div>
    `;
    
    return html;
}

// Закрытие по клику вне модального окна
document.addEventListener('click', function(e) {
    const modal = document.getElementById('paymentModal');
    if (e.target === modal) {
        closePaymentModal();
    }
});
</script>

<!-- Modal для инструкции по оплате -->
<div id="paymentModal" class="payment-modal">
    <div class="payment-modal-content">
        <button class="modal-close" onclick="closePaymentModal()">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <!-- Выбор банка -->
        <div id="bankSelection">
            <h2 class="modal-title">
                <i class="bi bi-bank"></i>
                Выберите ваш банк
            </h2>
            <p class="modal-subtitle">Мы покажем пошаговую инструкцию для вашего банка</p>
            
            <div class="banks-list">
                <button class="bank-item" onclick="showBankInstructions('alfabank')">
                    Альфа-Банк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('belarusbank')">
                    Беларусбанк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('belgazprombank')">
                    Белгазпромбанк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('belveb')">
                    БелВЭБ
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('bpsbank')">
                    БПС-Сбербанк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('dabrabyt')">
                    Дабрабыт
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('idea')">
                    Идея Банк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('mtbank')">
                    МТБанк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('priorbank')">
                    Приорбанк
                </button>
                
                <button class="bank-item" onclick="showBankInstructions('technobank')">
                    Технобанк
                </button>
            </div>
        </div>
        
        <!-- Инструкция для выбранного банка -->
        <div id="bankInstruction" style="display: none;">
            <button class="back-btn" onclick="backToBankSelection()">
                <i class="bi bi-arrow-left"></i> Выбрать другой банк
            </button>
            <div id="bankInstructionContent"></div>
        </div>
    </div>
</div>

<style>
.payment-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.payment-modal.show {
    opacity: 1;
}

.payment-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    padding: 2.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.payment-modal.show .payment-modal-content {
    transform: scale(1);
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #f3f4f6;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1.25rem;
    color: #6b7280;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #111827;
    transform: rotate(90deg);
}

.modal-title {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin-bottom: 2rem;
}

.banks-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.bank-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
    font-weight: 500;
    color: #111827;
    width: 100%;
}

.bank-item:hover {
    background: #f9fafb;
    border-color: #111827;
    transform: translateX(4px);
}

.back-btn {
    background: #f3f4f6;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
    color: #374151;
    margin-bottom: 1.5rem;
    transition: all 0.2s;
}

.back-btn:hover {
    background: #e5e7eb;
    color: #111827;
}

.bank-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.bank-header h3 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #111827;
}

.instruction-steps {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.instruction-step {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.step-number {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: #111827;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.125rem;
}

.step-content {
    flex: 1;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
}

.step-desc {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 0.5rem;
}

.step-note {
    background: #fffbeb;
    border-left: 4px solid #fbbf24;
    padding: 0.75rem 1rem;
    margin-top: 0.75rem;
    border-radius: 4px;
    font-size: 0.9375rem;
    color: #78350f;
}

.instruction-footer .alert {
    background: #fef2f2;
    border: 2px solid #fca5a5;
    border-radius: 8px;
    padding: 1.25rem;
}

.instruction-footer .alert strong {
    display: block;
    color: #dc2626;
    margin-bottom: 0.75rem;
    font-size: 1.0625rem;
}

.instruction-footer .alert ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #991b1b;
}

.instruction-footer .alert li {
    margin-bottom: 0.5rem;
}

.payment-instruction-link {
    background: none;
    border: none;
}

@media (max-width: 768px) {
    .payment-modal {
        padding: 0.5rem;
    }
    
    .payment-modal-content {
        padding: 1.25rem;
        max-height: 95vh;
        border-radius: 12px;
    }
    
    .modal-title {
        font-size: 1.375rem;
    }
    
    .modal-subtitle {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .modal-close {
        width: 36px;
        height: 36px;
        top: 0.75rem;
        right: 0.75rem;
    }
    
    .instruction-step {
        gap: 1rem;
    }
    
    .step-number {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .step-title {
        font-size: 1rem;
    }
    
    .bank-header h3 {
        font-size: 1.5rem;
    }
}
</style>
