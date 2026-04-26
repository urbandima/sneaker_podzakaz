<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;
use app\backend\modules\admin\models\CompanySettings;

AppAsset::register($this);

$this->title = 'Заказ #' . $model->order_number;
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);

$companySettings = CompanySettings::getSettings();
$_doneStatuses   = ['delivered','canceled','cancelled','returned','refunded'];
$awaitingPayment = !$model->payment_proof && !in_array($model->status, $_doneStatuses);
// Если заказ оплачен (или импортирован с заполненным чеком) и нужен паспорт — показываем форму
$passportNeeded  = !in_array($model->status, $_doneStatuses)
    && ($model->payment_proof || in_array($model->status, ['paid','imported','ordered','awaiting_warehouse','at_warehouse','processing']))
    && $model->hasMethod('isPassportComplete') && !$model->isPassportComplete();
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
                            <span class="order-info-value"><?= date('d.m.Y H:i', $model->created_at) ?></span>
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
                        <?php if ($model->delivery_address): ?>
                        <div class="order-client-item">
                            <span class="order-client-label">Адрес:</span>
                            <span class="order-client-value"><?= Html::encode($model->delivery_address) ?></span>
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

                <!-- ═══ ШАГ 1: Реквизиты для оплаты ═══ -->
                <?php if ($awaitingPayment): ?>
                <div class="order-view-section order-view-section--full order-payment-step" data-step="1">
                    <div class="order-step-head">
                        <span class="order-step-num">1</span>
                        <h2>Реквизиты для оплаты</h2>
                        <button type="button" class="btn-instruction" onclick="openPaymentModal()">
                            <i class="bi bi-book"></i> Инструкция к оплате
                        </button>
                    </div>
                    <p class="order-step-sub">Переведите указанную сумму по реквизитам ниже и приложите квитанцию на шаге 2.</p>
                    <div class="order-requisites">
                        <div class="order-req-row">
                            <span class="order-req-label">Получатель:</span>
                            <span class="order-req-value">
                                ИП Коляда С.С.
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'ИП Коляда С.С.')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php if (!empty($companySettings['unp'])): ?>
                        <div class="order-req-row">
                            <span class="order-req-label">УНП:</span>
                            <span class="order-req-value">
                                <?= Html::encode($companySettings['unp']) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'<?= Html::encode($companySettings['unp']) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($companySettings['address'])): ?>
                        <div class="order-req-row">
                            <span class="order-req-label">Адрес:</span>
                            <span class="order-req-value">
                                <?= Html::encode($companySettings['address']) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'<?= Html::encode($companySettings['address']) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($companySettings['bank'])): ?>
                        <div class="order-req-row">
                            <span class="order-req-label">Банк:</span>
                            <span class="order-req-value">
                                <?= Html::encode($companySettings['bank']) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'<?= Html::encode($companySettings['bank']) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($companySettings['bic'])): ?>
                        <div class="order-req-row">
                            <span class="order-req-label">БИК:</span>
                            <span class="order-req-value order-req-mono">
                                <?= Html::encode($companySettings['bic']) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'<?= Html::encode($companySettings['bic']) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($companySettings['account'])): ?>
                        <div class="order-req-row">
                            <span class="order-req-label">Расчётный счёт:</span>
                            <span class="order-req-value order-req-mono">
                                <?= Html::encode($companySettings['account']) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'<?= Html::encode($companySettings['account']) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="order-req-row">
                            <span class="order-req-label">Код назначения платежа:</span>
                            <span class="order-req-value order-req-mono">
                                90401
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'90401')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <div class="order-req-row order-req-row--highlight">
                            <span class="order-req-label">Сумма к оплате:</span>
                            <span class="order-req-value"><?= Yii::$app->formatter->asCurrency($model->total_amount, 'BYN') ?></span>
                        </div>
                        <div class="order-req-row order-req-row--purpose">
                            <span class="order-req-label">
                                <i class="bi bi-exclamation-circle-fill" style="color:#d97706"></i>
                                Назначение платежа:
                            </span>
                            <span class="order-req-value order-req-purpose-value">
                                Оплата по договору оферты №<?= Html::encode($model->order_number) ?>
                                <button type="button" class="order-req-copy" onclick="orderCopyReq(this,'Оплата по договору оферты №<?= Html::encode($model->order_number) ?>')" title="Скопировать"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══ ШАГ 2: Загрузка чека оплаты ═══ -->
                <?php if ($awaitingPayment): ?>
                <div class="order-view-section order-view-section--full order-payment-step" data-step="2">
                    <div class="order-step-head">
                        <span class="order-step-num">2</span>
                        <h2>Загрузить чек оплаты</h2>
                    </div>
                    <p class="order-step-sub">Приложите квитанцию или платёжное поручение (JPG, PNG или PDF, до 10 МБ).</p>
                    <div class="order-upload-payment">
                        <form action="<?= Url::to(['order/upload-payment', 'token' => $model->token]) ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
                            <div class="form-group">
                                <label for="payment_proof">Файл чека / квитанции</label>
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
                                Загрузить чек
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══ Подтверждение уже загруженной оплаты ═══ -->
                <?php if ($model->payment_proof): ?>
                <div class="order-view-section order-view-section--full order-payment-confirmed">
                    <h2><i class="bi bi-check-circle-fill" style="color:#16a34a"></i> Чек оплаты получен</h2>
                    <div class="order-payment-proof">
                        <a href="<?= Html::encode(Url::to(['/order/' . $model->token . '/download-payment'])) ?>" target="_blank" class="btn btn-secondary">
                            <i class="bi bi-download"></i>
                            Скачать загруженный чек
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ═══ ШАГ 3: Паспортные данные для ДоброПост ═══ -->
                <?php if ($passportNeeded): ?>
                <div class="order-view-section order-view-section--full order-payment-step" data-step="3">
                    <div class="order-step-head">
                        <span class="order-step-num">3</span>
                        <h2>Паспортные данные для доставки (Таможня:ДП)</h2>
                    </div>
                    <p class="order-step-sub">После получения оплаты необходимо заполнить паспортные данные для прохождения таможенного оформления.</p>
                    <?= $this->render('_passport_form', ['model' => $model]) ?>
                </div>
                <?php elseif ($model->hasMethod('isPassportComplete') && $model->isPassportComplete() && $model->payment_proof): ?>
                <div class="order-view-section order-view-section--full order-payment-confirmed">
                    <h2><i class="bi bi-check-circle-fill" style="color:#16a34a"></i> Паспортные данные заполнены</h2>
                    <p style="margin:0;color:#6b7280">Спасибо! Мы получили все необходимые данные для оформления доставки.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function orderCopyReq(btn, text){
    try { navigator.clipboard.writeText(text); } catch(e){}
    var i = btn.querySelector('i');
    if (i) { i.className = 'bi bi-check2'; setTimeout(function(){ i.className = 'bi bi-clipboard'; }, 1400); }
}

// Payment instruction modal
function openPaymentModal() {
    var modal = document.getElementById('paymentModal');
    modal.style.display = 'flex';
    setTimeout(function(){ modal.classList.add('show'); }, 10);
}

function closePaymentModal() {
    var modal = document.getElementById('paymentModal');
    modal.classList.remove('show');
    setTimeout(function(){ modal.style.display = 'none'; }, 300);
}

function showBankInstructions(bank) {
    document.getElementById('bankSelection').style.display = 'none';
    var instruction = document.getElementById('bankInstruction');
    instruction.style.display = 'block';
    var content = getBankInstructionContent(bank);
    document.getElementById('bankInstructionContent').innerHTML = content;
}

function backToBankSelection() {
    document.getElementById('bankInstruction').style.display = 'none';
    document.getElementById('bankSelection').style.display = 'block';
}

function getBankInstructionContent(bank) {
    var instructions = {
        belarusbank: {
            name: 'Беларусбанк',
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

    var bankData = instructions[bank];
    var html = '<div class="bank-header"><h3>' + bankData.name + '</h3></div><ol class="instruction-steps">';

    bankData.steps.forEach(function(step, index) {
        html += '<li class="instruction-step"><div class="step-number">' + (index + 1) + '</div><div class="step-content"><div class="step-title">' + step.title + '</div>';
        if (step.desc) html += '<div class="step-desc">' + step.desc + '</div>';
        if (step.note) html += '<div class="step-note"><i class="bi bi-exclamation-circle"></i> ' + step.note + '</div>';
        html += '</div></li>';
    });

    html += '</ol><div class="instruction-footer"><div class="alert alert-warning"><strong><i class="bi bi-exclamation-triangle-fill"></i> Важно!</strong><ul><li>Указывайте точное назначение платежа</li><li>Проверяйте все реквизиты перед подтверждением</li><li>Сохраните чек — его нужно загрузить на этой странице</li></ul></div></div>';
    return html;
}

document.addEventListener('click', function(e) {
    var modal = document.getElementById('paymentModal');
    if (e.target === modal) closePaymentModal();
});
</script>

<!-- Modal для инструкции по оплате -->
<div id="paymentModal" class="payment-modal">
    <div class="payment-modal-content">
        <button class="modal-close" onclick="closePaymentModal()">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="bankSelection">
            <h2 class="modal-title">
                <i class="bi bi-bank"></i>
                Выберите ваш банк
            </h2>
            <p class="modal-subtitle">Мы покажем пошаговую инструкцию для вашего банка</p>
            <div class="banks-list">
                <button class="bank-item" onclick="showBankInstructions('alfabank')">Альфа-Банк</button>
                <button class="bank-item" onclick="showBankInstructions('belarusbank')">Беларусбанк</button>
                <button class="bank-item" onclick="showBankInstructions('belgazprombank')">Белгазпромбанк</button>
                <button class="bank-item" onclick="showBankInstructions('belveb')">БелВЭБ</button>
                <button class="bank-item" onclick="showBankInstructions('bpsbank')">БПС-Сбербанк</button>
                <button class="bank-item" onclick="showBankInstructions('dabrabyt')">Дабрабыт</button>
                <button class="bank-item" onclick="showBankInstructions('idea')">Идея Банк</button>
                <button class="bank-item" onclick="showBankInstructions('mtbank')">МТБанк</button>
                <button class="bank-item" onclick="showBankInstructions('priorbank')">Приорбанк</button>
                <button class="bank-item" onclick="showBankInstructions('technobank')">Технобанк</button>
            </div>
        </div>
        <div id="bankInstruction" style="display: none;">
            <button class="back-btn" onclick="backToBankSelection()">
                <i class="bi bi-arrow-left"></i> Выбрать другой банк
            </button>
            <div id="bankInstructionContent"></div>
        </div>
    </div>
</div>

<style>
.order-view-page {
    padding: var(--space-10) 0;
    min-height: calc(100vh - 200px);
}

.order-view-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-10);
    flex-wrap: wrap;
    gap: var(--space-5);
}

.order-view-header h1 {
    margin: 0;
    font-size: var(--text-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--c-black);
}

.order-status-badge {
    display: inline-block;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: var(--font-weight-semibold);
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.order-status-new,
.order-status-created {
    background: var(--c-gray-100);
    color: var(--c-gray-600);
}

.order-status-confirmed {
    background: var(--c-gray-200);
    color: var(--c-gray-800);
}

.order-status-paid {
    background: var(--c-black);
    color: var(--c-white);
}

.order-status-ordered {
    background: var(--c-gray-800);
    color: var(--c-white);
}

.order-status-shipped {
    background: var(--c-gray-700);
    color: var(--c-white);
}

.order-status-delivered {
    background: var(--c-black);
    color: var(--c-white);
}

.order-status-canceled {
    background: var(--c-gray-100);
    color: var(--c-gray-500);
    text-decoration: line-through;
}

.order-view-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-6);
}

.order-view-section {
    background: var(--c-white);
    border: 1px solid var(--c-gray-200);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
}

.order-view-section--full {
    grid-column: 1 / -1;
}

.order-view-section h2 {
    font-size: var(--text-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--c-black);
    margin: 0 0 var(--space-5);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--c-gray-200);
}

.order-info-grid,
.order-client-info {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.order-info-item,
.order-client-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: var(--space-3);
    border-bottom: 1px solid var(--c-gray-100);
}

.order-info-item:last-child,
.order-client-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.order-info-label,
.order-client-label {
    font-size: var(--text-sm);
    color: var(--c-gray-500);
}

.order-info-value,
.order-client-value {
    font-size: var(--text-base);
    font-weight: var(--font-weight-medium);
    color: var(--c-black);
}

.order-info-value--highlight {
    font-size: var(--text-xl);
    font-weight: var(--font-weight-bold);
    color: var(--c-black);
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    background: var(--c-gray-50);
    border-radius: var(--radius-lg);
    gap: var(--space-4);
}

.order-item-info {
    flex: 1;
    min-width: 0;
}

.order-item-name {
    font-size: var(--text-base);
    font-weight: var(--font-weight-semibold);
    color: var(--c-black);
    margin-bottom: var(--space-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.order-item-details {
    display: flex;
    gap: var(--space-4);
    font-size: var(--text-sm);
    color: var(--c-gray-500);
}

.order-item-price {
    font-size: var(--text-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--c-black);
    white-space: nowrap;
}

.order-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-5);
    background: var(--c-black);
    color: var(--c-white);
    border-radius: var(--radius-lg);
    margin-top: var(--space-5);
}

.order-total-label {
    font-size: var(--text-lg);
    font-weight: var(--font-weight-semibold);
}

.order-total-value {
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-bold);
}

.order-payment-proof {
    display: flex;
    gap: var(--space-4);
}

/* Upload section — prominent */
.order-upload-payment {
    max-width: 520px;
}

.order-upload-payment .form-group {
    margin-bottom: var(--space-4);
}

.order-upload-payment label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: var(--font-weight-medium);
    color: var(--c-black);
    font-size: var(--text-sm);
}

.order-upload-payment input[type="file"] {
    width: 100%;
    padding: var(--space-4);
    border: 2px dashed var(--c-gray-300);
    border-radius: var(--radius-lg);
    background: var(--c-gray-50);
    font-size: var(--text-sm);
    cursor: pointer;
    transition: border-color var(--duration-fast);
}

.order-upload-payment input[type="file"]:hover {
    border-color: var(--c-black);
}

.order-upload-payment input[type="checkbox"] {
    margin-right: var(--space-2);
}

.btn-instruction {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: var(--text-sm);
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: auto;
}

.btn-instruction:hover {
    background: #e5e7eb;
    color: #111;
}

.order-req-row--purpose {
    background: #fffbeb;
    border-radius: 8px;
    padding: var(--space-3) var(--space-4) !important;
    border-bottom: none !important;
    margin-top: var(--space-2);
}

.order-req-purpose-value {
    font-size: var(--text-base) !important;
    font-weight: 700 !important;
    color: #92400e !important;
}

/* Payment Modal */
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

/* Step-based payment flow */
.order-payment-step {
    border: 2px solid #e5e7eb;
    position: relative;
}

.order-step-head {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-2);
}

.order-step-head h2 {
    margin: 0;
    padding: 0;
    border: none;
}

.order-step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #111;
    color: #fff;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}

.order-step-sub {
    margin: 0 0 var(--space-5);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
    font-size: var(--text-sm);
}

.order-requisites {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    margin-bottom: var(--space-4);
}

.order-req-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-4);
    padding-bottom: var(--space-3);
    border-bottom: 1px dashed #e5e7eb;
    font-size: var(--text-sm);
}

.order-req-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.order-req-row--highlight {
    background: #fef3c7;
    border-radius: 8px;
    padding: var(--space-3) var(--space-4);
    border-bottom: none;
    margin: var(--space-2) 0;
}

.order-req-row--highlight .order-req-value {
    font-size: var(--text-xl);
    font-weight: 800;
    color: #111;
}

.order-req-label {
    color: #6b7280;
    font-weight: 500;
    flex-shrink: 0;
}

.order-req-value {
    font-weight: 600;
    color: #111;
    text-align: right;
    word-break: break-all;
}

.order-req-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    letter-spacing: 0.02em;
}

.order-req-copy {
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 2px 8px;
    margin-left: 6px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.15s;
}

.order-req-copy:hover {
    color: #111;
    border-color: #111;
}

.order-payment-confirmed {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.order-payment-confirmed h2 {
    border: none;
    padding: 0;
    margin-bottom: var(--space-3);
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
        font-size: var(--text-2xl);
    }

    .order-view-page {
        padding: var(--space-6) 0;
    }

    .order-step-head {
        flex-wrap: wrap;
    }

    .btn-instruction {
        width: 100%;
        justify-content: center;
        margin-left: 0;
    }

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
