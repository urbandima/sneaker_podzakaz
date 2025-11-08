<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . $model->order_number;
$company = Yii::$app->settings->getCompany();
?>

<!-- Брендированная шапка -->
<header class="brand-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-3">
            <div class="brand-logo">
                <img src="https://sneaker-head.by/images/logo.png" 
                     alt="СНИКЕРХЭД" 
                     class="logo-img"
                     onerror="this.style.display='none'; this.nextElementSibling.style.fontSize='1.8rem';">
                <span class="brand-name">СНИКЕРХЭД</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="header-contact">
                    <i class="bi bi-telephone-fill"></i>
                    <span class="d-none d-sm-inline">+375 (44) 700-90-01</span>
                </div>
                <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-support" title="Написать в поддержку">
                    <i class="bi bi-telegram"></i>
                    <span class="d-none d-md-inline">Поддержка</span>
                </a>
            </div>
        </div>
    </div>
</header>

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
                                <div class="payment-value-clean">22501</div>
                                <button class="copy-btn-clean" data-copy="22501">
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

            <!-- ФОРМА ОПЛАТЫ - В САМОМ НИЗУ -->
            <?php if (!$model->payment_proof): ?>
            <div class="payment-upload-section mt-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="upload-section-bottom">
                                <h4 class="text-center mb-4"><i class="bi bi-upload"></i> Подтверждение оплаты</h4>
                                <form method="post" action="<?= Url::to(['upload-payment', 'token' => $model->token]) ?>" enctype="multipart/form-data" id="payment-form">
                                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                                    
                                    <div class="upload-box-bottom" id="upload-box">
                                        <label for="file-input" class="upload-label-bottom" id="upload-label">
                                            <i class="bi bi-cloud-upload"></i>
                                            <span>Прикрепить чек об оплате</span>
                                            <small>JPG, PNG, PDF (до 10 МБ)</small>
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

                                    <div class="offer-checkbox-bottom">
                                        <input type="checkbox" name="offer_accepted" value="1" id="offer_accepted" required>
                                        <label for="offer_accepted">
                                            Согласен с <a href="<?= \yii\helpers\Url::to(['/site/offer-agreement']) ?>" target="_blank">условиями договора оферты</a> и <a href="https://sneaker-head.by/policy" target="_blank">политикой обработки персональных данных</a>
                                        </label>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn-submit-bottom">
                                            <i class="bi bi-check-circle"></i> Подтвердить оплату
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="payment-success-section mt-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="payment-success-bottom text-center">
                                <div class="success-icon-bottom">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h4>Оплата подтверждена</h4>
                                <p class="text-muted">Загружено: <?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?></p>
                                <div class="alert alert-success">
                                    <i class="bi bi-info-circle"></i> Заказ на проверке. Менеджер свяжется с вами.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Брендированный футер -->
<footer class="brand-footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="footer-section">
                    <h6>Контакты</h6>
                    <p class="mb-1">г. Минск, пр-т Победителей 5</p>
                    <p class="mb-1">БЦ «Александровский», офис 9</p>
                    <p class="mb-1">Время работы: 09:00 - 21:00</p>
                    <p class="mb-1">Телефон: +375 (44) 700-90-01</p>
                    <p>Email: sneakerkultura@gmail.com</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="footer-section">
                    <h6>Юридическая информация</h6>
                    <ul class="footer-links">
                        <li><a href="https://sneaker-head.by/page/politika-konfidencialnosti" target="_blank">Обработка персональных данных</a></li>
                        <li><a href="<?= \yii\helpers\Url::to(['/site/offer-agreement']) ?>" target="_blank">Договор оферты</a></li>
                        <li><a href="https://sneaker-head.by/page/dostavka-i-oplata" target="_blank">Доставка и оплата</a></li>
                        <li><a href="https://sneaker-head.by/page/obmen-i-vozvrat" target="_blank">Обмен и возврат</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="footer-section">
                    <h6>Принимаем к оплате</h6>
                    <div class="payment-methods">
                        <span class="payment-badge">VISA</span>
                        <span class="payment-badge">MasterCard</span>
                        <span class="payment-badge">Белкарт</span>
                        <span class="payment-badge">МИР</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="text-center">
                <p>&copy; 2024 СНИКЕРХЭД. Интернет-магазин оригинальных кроссовок в Беларуси.</p>
            </div>
        </div>
    </div>
</footer>

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
    background: #f9fafb;
    padding: 3rem 0;
    margin-top: 3rem;
    border-top: 1px solid #e5e7eb;
}

.upload-section-bottom {
    background: white;
    border-radius: 8px;
    padding: 2.5rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.upload-section-bottom h4 {
    color: #111827;
    font-weight: 600;
    font-size: 1.5rem;
}

.upload-box-bottom {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    background: #f9fafb;
}

.upload-box-bottom:hover {
    border-color: #111827;
    background: #ffffff;
    transform: translateY(-1px);
}

.upload-label-bottom {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    color: #111827;
}

.upload-label-bottom i {
    font-size: 3rem;
    color: #6b7280;
}

.upload-label-bottom span {
    font-weight: 600;
    font-size: 1.125rem;
    color: #111827;
}

.upload-label-bottom small {
    color: #6b7280;
    font-size: 0.875rem;
}

.file-preview-bottom {
    margin-top: 1rem;
}

.preview-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.preview-img-bottom {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    border: 2px solid #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
}

.file-info {
    text-align: center;
    width: 100%;
}

.file-name {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
    word-break: break-word;
}

.file-size {
    font-size: 0.875rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.btn-change-file {
    background: white;
    color: #111827;
    border: 2px solid #e5e7eb;
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-change-file:hover {
    border-color: #111827;
    background: #f9fafb;
    transform: translateY(-1px);
}

.offer-checkbox-bottom {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.offer-checkbox-bottom input[type="checkbox"] {
    margin-top: 0.25rem;
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #111827;
}

.offer-checkbox-bottom label {
    font-size: 0.9375rem;
    cursor: pointer;
    margin: 0;
    line-height: 1.5;
    color: #374151;
}

.offer-checkbox-bottom a {
    color: #111827;
    text-decoration: underline;
    font-weight: 500;
}

.offer-checkbox-bottom a:hover {
    color: #000000;
}

.btn-submit-bottom {
    background: #111827;
    color: white;
    border: none;
    padding: 1rem 2.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.0625rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.btn-submit-bottom:hover {
    background: #1f2937;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-submit-bottom:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.payment-success-section {
    background: #f9fafb;
    padding: 3rem 0;
    margin-top: 3rem;
    border-top: 1px solid #e5e7eb;
}

.payment-success-bottom {
    background: white;
    border-radius: 8px;
    padding: 2.5rem;
    border: 1px solid #e5e7eb;
}

.success-icon-bottom {
    font-size: 3.5rem;
    color: #10b981;
    margin-bottom: 1.25rem;
}

.payment-success-bottom h4 {
    color: #111827;
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

/* Брендированный футер */
.brand-footer {
    background: #ffffff;
    color: #4b5563;
    padding: 2rem 0 1rem;
    margin-top: 3rem;
    border-top: 2px solid #e5e7eb;
}

.footer-section h6 {
    color: #1a1a1a;
    font-weight: 700;
    margin-bottom: 1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: #6b7280;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: #ff6b35;
    text-decoration: underline;
}

.payment-methods {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.payment-badge {
    background: #f3f4f6;
    color: #1a1a1a;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid #e5e7eb;
}

.footer-bottom {
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
    padding-top: 1rem;
}

.footer-bottom p {
    margin: 0;
    color: #9ca3af;
    font-size: 0.9rem;
}

.legal-address {
    color: #999;
    line-height: 1.6;
}


/* Cookie баннер */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: white;
    padding: 1.5rem;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.cookie-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.cookie-icon {
    font-size: 2.5rem;
    color: #ff6b35;
    flex-shrink: 0;
}

.cookie-text {
    flex: 1;
    min-width: 300px;
}

.cookie-text h6 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff6b35;
}

.cookie-text p {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #ccc;
}

.cookie-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-shrink: 0;
}

.btn-cookie-learn {
    color: #ff6b35;
    text-decoration: underline;
    background: transparent;
    border: none;
    padding: 0.5rem 1rem;
    cursor: pointer;
    font-size: 0.95rem;
    transition: color 0.3s;
}

.btn-cookie-learn:hover {
    color: #f7931e;
}

.btn-cookie-accept {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-cookie-accept:hover {
    background: #f7931e;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
}

/* Модальное окно истории */
.history-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease-out;
}

.history-modal.active {
    display: flex;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.history-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 700px;
    width: 90%;
    max-height: 80vh;
    border: 1px solid #e5e7eb;
    animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.history-modal-header {
    background: #ffffff;
    color: #111827;
    padding: 1.25rem 1.5rem;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
}

.history-modal-header h5 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #111827;
}

.modal-close {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    color: #6b7280;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #111827;
}

.history-modal-body {
    padding: 2rem;
    overflow-y: auto;
    max-height: calc(80vh - 80px);
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
    width: 12px;
    height: 12px;
    background: #111827;
    border: 2px solid #fff;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 1px #d1d5db;
    z-index: 2;
    margin-top: 4px;
}

.history-line-modal {
    position: absolute;
    left: 5px;
    top: 16px;
    width: 2px;
    height: calc(100% - 10px);
    background: #e5e7eb;
    z-index: 1;
}

.history-content-modal {
    flex: 1;
    padding-top: 0;
}

.history-status-modal {
    font-weight: 600;
    font-size: 0.9375rem;
    color: #111827;
    margin-bottom: 0.375rem;
}

.history-date-modal {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

.history-comment-modal {
    background: #f9fafb;
    padding: 0.875rem;
    border-radius: 6px;
    border-left: 3px solid #d1d5db;
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.5;
}

.product-page {
    background: #ffffff;
    min-height: 100vh;
}

.order-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem 0;
}

.content-single-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-header {
    background: #fff;
    padding: 1.25rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
}

.order-title {
    font-size: 1.375rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.status-badge {
    background: #10b981;
    color: white;
    padding: 0.4rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.delivery-badge {
    background: #f9fafb;
    color: #6b7280;
    padding: 0.4rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.product-card, .payment-details-card, .upload-section, .info-card, .history-card, .payment-success {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

/* Чистая минималистичная карточка реквизитов */
.payment-card-clean {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
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

.offer-checkbox {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: rgba(255,255,255,0.15);
    border-radius: 6px;
}

.offer-checkbox input[type="checkbox"] {
    margin-top: 0.25rem;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.offer-checkbox label {
    font-size: 0.9rem;
    cursor: pointer;
    margin: 0;
}

.offer-checkbox a {
    color: white;
    text-decoration: underline;
}

.btn-submit {
    width: 100%;
    background: white;
    color: #764ba2;
    border: none;
    padding: 0.9rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-submit:hover {
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
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Загрузка...';
            submitBtn.disabled = true;
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
                { title: 'Выберите "На расчётный счёт"', desc: '' },
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
