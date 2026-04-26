<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Условия оплаты — СНИКЕРХЭД';
$this->params['breadcrumbs'][] = $this->title;

$css = <<<CSS
/* ===== PAYMENT TERMS PAGE ===== */
.pt-page { padding: 3rem 0 5rem; }

/* Hero */
.pt-hero { margin-bottom: 3rem; }
.pt-hero h1 {
    font-size: clamp(1.875rem, 5vw, 2.75rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1.05;
    margin-bottom: 0.5rem;
}
.pt-hero p {
    font-size: 1.0625rem;
    color: var(--color-text-secondary, #666);
    max-width: 540px;
}

/* Quick-nav pills */
.pt-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 2.5rem;
}
.pt-nav a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 999px;
    border: 1px solid var(--color-border, #e5e5e5);
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-text-primary, #111);
    text-decoration: none;
    transition: background 0.15s, border-color 0.15s;
    background: var(--color-bg-primary, #fff);
}
.pt-nav a:hover { background: #f5f5f5; border-color: #ccc; }

/* Section cards */
.pt-section {
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.25rem;
    transition: box-shadow 0.2s;
}
.pt-section:hover { box-shadow: 0 4px 24px rgba(0,0,0,0.06); }

.pt-section-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.pt-section-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #111;
}
.pt-section-icon.green  { background: #f0fdf4; color: #16a34a; }
.pt-section-icon.blue   { background: #eff6ff; color: #2563eb; }
.pt-section-icon.amber  { background: #fffbeb; color: #d97706; }
.pt-section-icon.purple { background: #faf5ff; color: #7c3aed; }

.pt-section-title h2 {
    font-size: 1.1875rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0 0 3px;
}
.pt-section-title p {
    font-size: 0.8125rem;
    color: var(--color-text-secondary, #666);
    margin: 0;
}

/* ЕРИП steps */
.erip-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 1.25rem;
}
.erip-step {
    display: flex;
    gap: 1rem;
    position: relative;
}
.erip-step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 19px;
    top: 40px;
    bottom: -4px;
    width: 2px;
    background: #e5e5e5;
}
.erip-step-num {
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #111;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 800;
    position: relative;
    z-index: 1;
}
.erip-step-body {
    padding: 8px 0 20px;
}
.erip-step-body strong {
    display: block;
    font-size: 0.9375rem;
    font-weight: 700;
    margin-bottom: 3px;
    color: var(--color-text-primary, #111);
}
.erip-step-body span {
    font-size: 0.8125rem;
    color: var(--color-text-secondary, #666);
    line-height: 1.5;
}

.erip-code-box {
    background: #f5f5f5;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.erip-code-box i { font-size: 1.4rem; color: #111; }
.erip-code-label { font-size: 0.75rem; color: #666; margin-bottom: 2px; }
.erip-code-value { font-size: 1.5rem; font-weight: 900; letter-spacing: 0.05em; color: #111; }

/* Card logos */
.card-logos {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 1.25rem;
}
.card-logo-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 14px;
    border: 1.5px solid var(--color-border, #e5e5e5);
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--color-text-primary, #111);
    background: var(--color-bg-primary, #fff);
}
.card-logo-badge.visa      { border-color: #1a1f71; color: #1a1f71; }
.card-logo-badge.mc        { border-color: #eb001b; }
.card-logo-badge.belkart   { border-color: #007a3d; color: #007a3d; }
.card-logo-badge.mir       { border-color: #3a3f8f; color: #3a3f8f; }

/* Security row */
.security-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.security-badge {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    background: #f0fdf4;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #166534;
}
.security-badge i { font-size: 1rem; }

/* Address / schedule table */
.pt-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}
.pt-info-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9rem;
}
.pt-info-list li i {
    flex-shrink: 0;
    width: 20px;
    text-align: center;
    font-size: 1rem;
    margin-top: 1px;
    color: #666;
}
.pt-info-list li strong { color: var(--color-text-primary, #111); }

/* Реквизиты */
.pt-requisites {
    display: grid;
    gap: 0;
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: 14px;
    overflow: hidden;
}
.pt-req-row {
    display: grid;
    grid-template-columns: 180px 1fr;
    padding: 11px 16px;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--color-border, #f0f0f0);
    gap: 1rem;
}
.pt-req-row:last-child { border-bottom: none; }
.pt-req-row:nth-child(even) { background: #fafafa; }
.pt-req-key {
    color: var(--color-text-secondary, #666);
    font-weight: 500;
}
.pt-req-val {
    color: var(--color-text-primary, #111);
    font-weight: 600;
}

/* Two-col grid for last two sections */
.pt-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

/* Admin edit button */
.page-edit-admin-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #0f0f0f;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    opacity: .7;
    transition: opacity .15s;
}
.page-edit-admin-btn:hover { opacity: 1; color: #fff; }

@media (max-width: 700px) {
    .pt-two-col { grid-template-columns: 1fr; }
    .pt-req-row { grid-template-columns: 1fr; gap: 2px; }
    .pt-req-key { font-size: 0.75rem; }
    .pt-section { padding: 1.25rem; }
    .erip-step:not(:last-child)::before { left: 18px; }
}
CSS;

$this->registerCss($css);
?>

<div class="container pt-page">
    <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity && Yii::$app->user->identity->isAdmin()): ?>
    <div style="margin-bottom:1.25rem;text-align:right">
        <a href="/admin/page/edit?slug=payment-terms" class="page-edit-admin-btn" target="_blank">
            <i class="bi bi-pencil-square"></i> Редактировать страницу
        </a>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="pt-hero">
        <h1>Условия оплаты</h1>
        <p>Выберите удобный способ — принимаем онлайн-платежи, банковские карты и наличные при получении.</p>
    </div>

    <!-- Quick nav -->
    <nav class="pt-nav">
        <a href="#erip"><i class="bi bi-grid-3x3-gap"></i> ЕРИП</a>
        <a href="#card"><i class="bi bi-credit-card"></i> Банковская карта</a>
        <a href="#cash"><i class="bi bi-cash-stack"></i> Наличные</a>
        <a href="#requisites"><i class="bi bi-building"></i> Реквизиты</a>
    </nav>

    <!-- ===== ЕРИП ===== -->
    <div class="pt-section" id="erip">
        <div class="pt-section-header">
            <div class="pt-section-icon blue">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </div>
            <div class="pt-section-title">
                <h2>Оплата через ЕРИП</h2>
                <p>Единое расчётное и информационное пространство — работает через любой банк Беларуси</p>
            </div>
        </div>

        <div class="erip-steps">
            <div class="erip-step">
                <div class="erip-step-num">1</div>
                <div class="erip-step-body">
                    <strong>Откройте ЕРИП в вашем банке</strong>
                    <span>Интернет-банкинг, мобильное приложение или терминал — любой удобный канал</span>
                </div>
            </div>
            <div class="erip-step">
                <div class="erip-step-num">2</div>
                <div class="erip-step-body">
                    <strong>Перейдите: Интернет-магазины → СНИКЕРХЭД</strong>
                    <span>Или воспользуйтесь поиском по коду плательщика</span>
                </div>
            </div>
            <div class="erip-step">
                <div class="erip-step-num">3</div>
                <div class="erip-step-body">
                    <strong>Введите номер заказа</strong>
                    <span>Номер находится в подтверждении заказа, которое пришло на вашу электронную почту</span>
                </div>
            </div>
            <div class="erip-step">
                <div class="erip-step-num">4</div>
                <div class="erip-step-body">
                    <strong>Проверьте сумму и подтвердите оплату</strong>
                    <span>Платёж зачисляется автоматически — статус заказа обновится в течение 5–15 минут</span>
                </div>
            </div>
        </div>

        <div class="erip-code-box">
            <i class="bi bi-upc-scan"></i>
            <div>
                <div class="erip-code-label">Код плательщика в ЕРИП</div>
                <div class="erip-code-value">000 · 000</div>
            </div>
        </div>
    </div>

    <!-- ===== Банковская карта ===== -->
    <div class="pt-section" id="card">
        <div class="pt-section-header">
            <div class="pt-section-icon green">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div class="pt-section-title">
                <h2>Банковская карта онлайн</h2>
                <p>Мгновенная оплата — деньги зачисляются сразу, заказ уходит в сборку</p>
            </div>
        </div>

        <div class="card-logos">
            <div class="card-logo-badge visa">
                <i class="bi bi-credit-card"></i> Visa
            </div>
            <div class="card-logo-badge mc">
                <i class="bi bi-credit-card"></i>
                <span style="color:#eb001b">Master</span><span style="color:#f79e1b">Card</span>
            </div>
            <div class="card-logo-badge belkart">
                <i class="bi bi-credit-card"></i> Белкарт
            </div>
            <div class="card-logo-badge mir">
                <i class="bi bi-credit-card"></i> МИР
            </div>
        </div>

        <div class="security-row">
            <div class="security-badge">
                <i class="bi bi-shield-lock-fill"></i> SSL-шифрование
            </div>
            <div class="security-badge">
                <i class="bi bi-phone-fill"></i> 3D Secure / SMS-код
            </div>
            <div class="security-badge">
                <i class="bi bi-patch-check-fill"></i> PCI DSS
            </div>
            <div class="security-badge">
                <i class="bi bi-eye-slash-fill"></i> Данные карты не хранятся
            </div>
        </div>
    </div>

    <!-- ===== Наличные + Мобильный банкинг (two-col) ===== -->
    <div class="pt-two-col">

        <!-- Наличные -->
        <div class="pt-section" id="cash" style="margin-bottom:0">
            <div class="pt-section-header">
                <div class="pt-section-icon amber">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="pt-section-title">
                    <h2>Наличные</h2>
                    <p>При получении заказа</p>
                </div>
            </div>

            <ul class="pt-info-list">
                <li>
                    <i class="bi bi-bag-check"></i>
                    <div><strong>Самовывоз со склада</strong><br>Оплата при выдаче</div>
                </li>
                <li>
                    <i class="bi bi-bicycle"></i>
                    <div><strong>Курьер по городу</strong><br>Оплата при доставке</div>
                </li>
                <li>
                    <i class="bi bi-geo-alt"></i>
                    <div>г. Минск<br>Пн–Пт: 10:00–19:00<br>Сб: 10:00–17:00</div>
                </li>
                <li>
                    <i class="bi bi-receipt"></i>
                    <div>Выдаём кассовый чек в соответствии с законодательством РБ</div>
                </li>
            </ul>
        </div>

        <!-- Мобильный банкинг -->
        <div class="pt-section" style="margin-bottom:0">
            <div class="pt-section-header">
                <div class="pt-section-icon purple">
                    <i class="bi bi-phone"></i>
                </div>
                <div class="pt-section-title">
                    <h2>Мобильный банкинг</h2>
                    <p>Оплата через приложение вашего банка</p>
                </div>
            </div>

            <ul class="pt-info-list">
                <li>
                    <i class="bi bi-check-circle"></i>
                    <div><strong>A1</strong><br>Мобильный платёж</div>
                </li>
                <li>
                    <i class="bi bi-check-circle"></i>
                    <div><strong>МТС Банк</strong><br>Приложение МТС Банк</div>
                </li>
                <li>
                    <i class="bi bi-check-circle"></i>
                    <div><strong>life:)</strong><br>Мобильный банкинг</div>
                </li>
                <li>
                    <i class="bi bi-check-circle"></i>
                    <div><strong>EasyPay / WebPay</strong><br>Электронные кошельки</div>
                </li>
            </ul>
        </div>

    </div>

    <!-- ===== Сроки зачисления ===== -->
    <div class="pt-section" style="margin-top:1.25rem">
        <div class="pt-section-header">
            <div class="pt-section-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="pt-section-title">
                <h2>Сроки зачисления</h2>
                <p>Когда мы получаем ваш платёж</p>
            </div>
        </div>

        <div class="pt-requisites">
            <div class="pt-req-row">
                <span class="pt-req-key"><i class="bi bi-credit-card me-1"></i> Банковская карта</span>
                <span class="pt-req-val">Мгновенно &nbsp;<span style="font-weight:400;color:#666;font-size:.8em">комиссия 0%</span></span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key"><i class="bi bi-grid-3x3-gap me-1"></i> ЕРИП</span>
                <span class="pt-req-val">5–15 минут &nbsp;<span style="font-weight:400;color:#666;font-size:.8em">комиссия 0%</span></span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key"><i class="bi bi-wallet2 me-1"></i> EasyPay / WebPay</span>
                <span class="pt-req-val">Мгновенно &nbsp;<span style="font-weight:400;color:#666;font-size:.8em">комиссия 0%</span></span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key"><i class="bi bi-cash me-1"></i> Наличные</span>
                <span class="pt-req-val">Немедленно</span>
            </div>
        </div>
    </div>

    <!-- ===== Реквизиты ===== -->
    <div class="pt-section" id="requisites">
        <div class="pt-section-header">
            <div class="pt-section-icon">
                <i class="bi bi-building"></i>
            </div>
            <div class="pt-section-title">
                <h2>Реквизиты компании</h2>
                <p>Для юридических лиц и безналичного расчёта</p>
            </div>
        </div>

        <div class="pt-requisites">
            <div class="pt-req-row">
                <span class="pt-req-key">Наименование</span>
                <span class="pt-req-val">ООО «СникерКультура»</span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key">УНП</span>
                <span class="pt-req-val">000 000 000</span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key">Р/счёт</span>
                <span class="pt-req-val">BY00 XXXX 0000 0000 0000 0000 0000</span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key">Банк</span>
                <span class="pt-req-val">ОАО «Беларусбанк»</span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key">БИК</span>
                <span class="pt-req-val">AKBBBY2X</span>
            </div>
            <div class="pt-req-row">
                <span class="pt-req-key">Юридический адрес</span>
                <span class="pt-req-val">220000, г. Минск, ул. Примерная, д. 1</span>
            </div>
        </div>
    </div>

    <!-- ===== Support note ===== -->
    <div style="display:flex;align-items:center;gap:1rem;padding:1.25rem 1.5rem;background:#f5f5f5;border-radius:16px;margin-top:1.25rem">
        <i class="bi bi-headset" style="font-size:1.75rem;color:#111;flex-shrink:0"></i>
        <div>
            <div style="font-weight:700;font-size:0.9375rem;margin-bottom:3px">Проблемы с оплатой?</div>
            <div style="font-size:0.8125rem;color:#666">
                Напишите нам на <a href="mailto:payment@snikered.by" style="color:#111;font-weight:600">payment@snikered.by</a>
                или позвоните <a href="tel:+375291234567" style="color:#111;font-weight:600">+375 (29) 123-45-67</a> — поможем разобраться.
            </div>
        </div>
    </div>

</div>
