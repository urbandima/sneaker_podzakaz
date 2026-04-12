<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Инструкция по оплате на юридическое лицо';
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.instruction-page {
    background: #ffffff;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    padding: 3rem 0;
}

.instruction-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.instruction-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #e5e7eb;
}

.instruction-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
}

.instruction-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
}

.bank-section {
    margin-bottom: 3rem;
    padding: 2rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.bank-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.bank-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.bank-name {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
}

.steps-list {
    list-style: none;
    counter-reset: step-counter;
}

.step-item {
    counter-increment: step-counter;
    margin-bottom: 1.5rem;
    padding-left: 3rem;
    position: relative;
}

.step-item::before {
    content: counter(step-counter);
    position: absolute;
    left: 0;
    top: 0;
    width: 32px;
    height: 32px;
    background: #111827;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
}

.step-description {
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
}

.step-note strong {
    color: #d97706;
}

.warning-box {
    background: #fef2f2;
    border: 2px solid #fca5a5;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.warning-box h4 {
    color: #dc2626;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.warning-box ul {
    margin-left: 1.5rem;
    color: #991b1b;
}

.warning-box li {
    margin-bottom: 0.5rem;
}

.help-section {
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    margin-top: 3rem;
}

.help-section h3 {
    color: #15803d;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.help-section p {
    color: #166534;
    margin-bottom: 1.5rem;
}

.btn-telegram {
    background: #111827;
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-telegram:hover {
    background: #1f2937;
    transform: translateY(-2px);
    color: white;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    margin-bottom: 2rem;
    font-weight: 500;
}

.back-link:hover {
    color: #111827;
}

@media (max-width: 768px) {
    .instruction-title {
        font-size: 1.875rem;
    }
    
    .bank-section {
        padding: 1.5rem;
    }
    
    .step-item {
        padding-left: 2.5rem;
    }
}
</style>

<div class="instruction-page">
    <div class="instruction-container">
        <?= Html::a('<i class="bi bi-arrow-left"></i> Вернуться назад', 'javascript:history.back()', ['class' => 'back-link']) ?>
        
        <div class="instruction-header">
            <h1 class="instruction-title">Как оплатить на юридическое лицо</h1>
            <p class="instruction-subtitle">Подробная инструкция для всех банков Беларуси</p>
        </div>

        <!-- Беларусбанк -->
        <div class="bank-section">
            <div class="bank-logo">
                <div class="bank-icon">🏦</div>
                <h2 class="bank-name">Беларусбанк</h2>
            </div>
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-title">Войдите в приложение или интернет-банк</div>
                    <div class="step-description">Откройте мобильное приложение "Беларусбанк" или зайдите в личный кабинет на сайте</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Выберите "Платежи" → "Оплата услуг"</div>
                    <div class="step-description">В главном меню найдите раздел платежей</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Нажмите "Перевод по реквизитам"</div>
                    <div class="step-description">Выберите опцию перевода на расчётный счёт организации</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Заполните реквизиты получателя</div>
                    <div class="step-description">Введите все данные организации из вашего счета</div>
                    <div class="step-note">
                        <strong>Важно!</strong> Обязательно укажите назначение платежа точно как в счете (например: "Оплата по договору оферты №2025-00001")
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-title">Подтвердите платёж</div>
                    <div class="step-description">Проверьте данные и подтвердите операцию кодом из SMS</div>
                </li>
            </ol>
        </div>

        <!-- Приорбанк -->
        <div class="bank-section">
            <div class="bank-logo">
                <div class="bank-icon">💳</div>
                <h2 class="bank-name">Приорбанк</h2>
            </div>
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-title">Откройте приложение "Приорбанк Онлайн"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Перейдите в "Платежи и переводы"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Выберите "На расчётный счёт"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Введите реквизиты организации</div>
                    <div class="step-note">
                        <strong>Совет:</strong> Можно сохранить реквизиты как шаблон для будущих платежей
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-title">Укажите сумму и назначение платежа</div>
                    <div class="step-description">Обязательно укажите номер договора оферты в назначении платежа</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Подтвердите операцию</div>
                </li>
            </ol>
        </div>

        <!-- Альфа-Банк -->
        <div class="bank-section">
            <div class="bank-logo">
                <div class="bank-icon">🅰️</div>
                <h2 class="bank-name">Альфа-Банк</h2>
            </div>
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-title">Войдите в "Альфа-Мобайл"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Нажмите "Платежи" в нижнем меню</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Выберите "Перевод на счёт в банке"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Заполните форму перевода</div>
                    <div class="step-description">Укажите УНП организации, расчётный счёт, БИК банка и назначение платежа</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Подтвердите платёж с помощью кода из SMS или Face ID</div>
                </li>
            </ol>
        </div>

        <!-- БелВЭБ -->
        <div class="bank-section">
            <div class="bank-logo">
                <div class="bank-icon">🏛️</div>
                <h2 class="bank-name">БелВЭБ</h2>
            </div>
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-title">Откройте "БелВЭБ 24/7"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Перейдите в раздел "Платежи"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Выберите "Платёж по реквизитам"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Введите данные организации</div>
                    <div class="step-description">Все реквизиты указаны в вашем счёте на оплату</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Укажите назначение платежа и сумму</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Подтвердите операцию</div>
                </li>
            </ol>
        </div>

        <!-- MTБанк -->
        <div class="bank-section">
            <div class="bank-logo">
                <div class="bank-icon">📱</div>
                <h2 class="bank-name">МТБанк</h2>
            </div>
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-title">Зайдите в приложение "МТБанк Online"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Выберите "Платежи" → "Переводы и платежи"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Нажмите "Перевод на счёт в другом банке"</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Заполните реквизиты получателя</div>
                    <div class="step-description">УНП, расчётный счёт, название организации, БИК банка</div>
                </li>
                <li class="step-item">
                    <div class="step-title">Укажите назначение платежа</div>
                    <div class="step-note">
                        <strong>Обязательно!</strong> Скопируйте назначение платежа из вашего счёта без изменений
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-title">Подтвердите перевод кодом</div>
                </li>
            </ol>
        </div>

        <div class="warning-box">
            <h4><i class="bi bi-exclamation-triangle-fill"></i> Важная информация</h4>
            <ul>
                <li>Обязательно указывайте <strong>точное назначение платежа</strong>, как в счёте</li>
                <li>Проверяйте все реквизиты перед подтверждением операции</li>
                <li>Сохраните чек об оплате — его нужно загрузить в личном кабинете</li>
                <li>Обработка платежа может занять 1-2 рабочих дня</li>
            </ul>
        </div>

        <div class="help-section">
            <h3><i class="bi bi-headset"></i> Нужна помощь?</h3>
            <p>Если у вас возникли трудности с оплатой, мы всегда готовы помочь</p>
            <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-telegram">
                <i class="bi bi-telegram"></i> Написать в Telegram
            </a>
        </div>
    </div>
</div>
