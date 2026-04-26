<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Новый заказ';
$user     = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$logists  = $user->isAdmin()
    ? (function () {
        try {
            return \app\backend\modules\admin\models\User::find()
                ->where(['role' => 'logist'])
                ->orderBy(['username' => SORT_ASC])
                ->all();
        } catch (\Exception $e) { return []; }
    })()
    : [];

$sources = [];
try {
    $rawSrc = Yii::$app->settings->get('order', 'sources', '');
    $sources = $rawSrc ? json_decode($rawSrc, true) : [];
} catch (\Exception $e) {}
if (empty($sources)) {
    $sources = ['Сайт', 'Telegram', 'Instagram', 'ВКонтакте', 'Звонок', 'WhatsApp', 'Viber', 'Рекомендация', 'Другое'];
}

$orderItems = Yii::$app->request->post('OrderItem', [
    ['product_name' => '', 'quantity' => 1, 'price' => '', 'link' => ''],
]);

if (empty($model->dobropost_tariff)) { $model->dobropost_tariff = 26; }
if (empty($model->status))           { $model->status = 'new'; }
?>
<style>
/* ═══ ORDER CRM CREATE ═══ */
.crm-wrap { display: flex; flex-direction: column; gap: 0; width: 100%; min-width: 0; }

.crm-topbar {
    display: flex; align-items: center; gap: 12px; padding: 12px 20px;
    background: var(--admin-surface, #fff); border-bottom: 1px solid var(--admin-border, #e5e7eb);
    flex-wrap: wrap; position: sticky; top: 0; z-index: 30; overflow-x: auto;
}
.crm-topbar-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.crm-order-num { font-size: 1.125rem; font-weight: 800; color: var(--admin-text-primary, #111); white-space: nowrap; }
.crm-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
    background: #f3f4f6; color: #6b7280;
    border: 1px solid color-mix(in srgb, #6b7280 20%, transparent);
    white-space: nowrap;
}
.crm-topbar-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; flex-shrink: 0; }

.crm-body { display: grid; grid-template-columns: 1fr 380px; gap: 20px; flex: 1; align-items: start; width: 100%; min-width: 0; }
.crm-main { padding: 16px; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.crm-sidebar { padding: 10px 12px; display: flex; flex-direction: column; gap: 8px; }
.crm-sidebar .crm-card-head { padding: 7px 12px; }
.crm-sidebar .crm-card-head h3 { font-size: 0.75rem; }
.crm-sidebar .crm-card-body { padding: 10px 12px; }

.crm-card {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 12px; overflow: hidden;
}
.crm-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; border-bottom: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface-hover, #f9fafb);
}
.crm-card-head h3 {
    font-size: 0.8125rem; font-weight: 700; color: var(--admin-text-primary, #111);
    display: flex; align-items: center; gap: 6px; margin: 0;
}
.crm-card-head h3 i { color: var(--admin-text-secondary, #6b7280); }
.crm-card-body { padding: 14px 16px; }

.crm-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.crm-info-grid.cols3 { grid-template-columns: 1fr 1fr 1fr; }
.crm-field { display: flex; flex-direction: column; gap: 3px; }
.crm-field-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-text-secondary, #9ca3af); }

.crm-input {
    width: 100%; padding: 5px 8px; border: 1px solid var(--admin-border, #e5e7eb); border-radius: 6px;
    font-size: 0.875rem; font-weight: 500; background: var(--admin-surface, #fff);
    color: var(--admin-text-primary, #111); outline: none; box-sizing: border-box;
    transition: border-color .15s;
}
.crm-input:focus { border-color: var(--admin-accent, #111); }
.crm-input[type=number]::-webkit-inner-spin-button { opacity: 0.5; }
select.crm-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 8px center; padding-right: 24px; }
textarea.crm-input { resize: vertical; min-height: 60px; }

.crm-items-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.crm-items-table th {
    text-align: left; padding: 6px 10px; font-size: 0.7rem; font-weight: 600;
    color: var(--admin-text-secondary, #6b7280); text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
}
.crm-items-table td { padding: 6px 8px; border-bottom: 1px solid var(--admin-border, #f3f4f6); vertical-align: middle; }
.crm-items-table tr:last-child td { border-bottom: none; }
.crm-total-row {
    display: flex; justify-content: flex-end; align-items: center; gap: 8px;
    padding: 10px 16px; border-top: 1px solid var(--admin-border, #e5e7eb);
    font-weight: 700; font-size: 0.9375rem;
}
.crm-total-row .label { color: var(--admin-text-secondary, #6b7280); font-weight: 500; font-size: 0.8125rem; }

.crm-save-bar {
    position: sticky; bottom: 0; z-index: 20;
    background: var(--admin-surface, #fff); border-top: 1px solid var(--admin-border, #e5e7eb);
    padding: 10px 20px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;
}

/* Customer dropdown */
.crm-customer-drop {
    position: absolute; top: 100%; left: 0; right: 0; background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e5e7eb); border-radius: 8px; z-index: 100;
    max-height: 220px; overflow-y: auto; box-shadow: 0 4px 16px rgba(0,0,0,.1); display: none;
}
.crm-customer-opt { padding: 9px 14px; cursor: pointer; font-size: 0.8125rem; border-bottom: 1px solid var(--admin-border, #f3f4f6); }
.crm-customer-opt:last-child { border-bottom: none; }
.crm-customer-opt:hover { background: var(--admin-surface-hover, #f9fafb); }
.crm-customer-opt strong { display: block; font-weight: 600; }
.crm-customer-opt span { color: var(--admin-text-secondary, #6b7280); }

@media (max-width: 1023px) { .crm-body { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .crm-info-grid { grid-template-columns: 1fr; } .crm-info-grid.cols3 { grid-template-columns: 1fr 1fr; } }
</style>

<?php $form = ActiveForm::begin([
    'id'                   => 'orderCreateForm',
    'action'               => Url::to(['/admin/order/create']),
    'options'              => ['style' => 'display:contents'],
    'validateOnLoad'       => false,
    'validateOnChange'     => true,
    'validateOnBlur'       => true,
    'validateOnSubmit'     => true,
]); ?>

<div class="crm-wrap">

    <!-- ═══ TOP BAR ═══ -->
    <div class="crm-topbar">
        <div class="crm-topbar-left">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding:4px 8px">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="crm-order-num">Новый заказ</span>
            <span class="crm-status-pill">
                <i class="bi bi-circle-fill" style="font-size:6px"></i>
                Черновик
            </span>
        </div>
        <div class="crm-topbar-actions">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                Отмена
            </a>
            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">
                <i class="bi bi-check-lg"></i> Создать заказ
            </button>
        </div>
    </div>

    <!-- ═══ BODY ═══ -->
    <div class="crm-body">

        <!-- ── LEFT MAIN ── -->
        <div class="crm-main">

            <!-- Состав заказа -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-bag"></i> Состав заказа</h3>
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" id="addItemBtn">
                        <i class="bi bi-plus"></i> Добавить
                    </button>
                </div>
                <div style="padding: 0;">
                    <table class="crm-items-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th style="width:60px">Кол-во</th>
                                <th style="width:100px">Цена BYN</th>
                                <th style="width:160px">Ссылка</th>
                                <th style="width:32px"></th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBuilder">
                            <?php foreach ($orderItems as $index => $item): ?>
                            <tr class="crm-item-row" data-index="<?= $index ?>">
                                <td><input type="text" name="OrderItem[<?= $index ?>][product_name]"
                                    class="crm-input" style="min-width:160px"
                                    value="<?= Html::encode($item['product_name']) ?>" placeholder="Название товара"></td>
                                <td><input type="number" name="OrderItem[<?= $index ?>][quantity]"
                                    class="crm-input" value="<?= (int)($item['quantity'] ?: 1) ?>" min="1"></td>
                                <td><input type="number" step="0.01" name="OrderItem[<?= $index ?>][price]"
                                    class="crm-input crm-item-price"
                                    value="<?= Html::encode($item['price']) ?>" min="0" placeholder="0.00"></td>
                                <td><input type="text" name="OrderItem[<?= $index ?>][link]"
                                    class="crm-input" value="<?= Html::encode($item['link'] ?? '') ?>" placeholder="https://…"></td>
                                <td>
                                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm crm-remove-item"
                                        <?= $index === 0 ? 'disabled' : '' ?> title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="crm-total-row">
                        <span class="label">Итого товаров:</span>
                        <span id="orderTotalDisplay">0 BYN</span>
                    </div>
                </div>
            </div>

            <!-- Доставка по РБ -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-truck"></i> Доставка по РБ</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid cols3">
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('delivery_method') ?></label>
                            <?= Html::activeTextInput($model, 'delivery_method', ['class' => 'crm-input', 'placeholder' => 'Европочта / Белпочта…']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('delivery_date') ?></label>
                            <?= Html::activeTextInput($model, 'delivery_date', ['class' => 'crm-input', 'type' => 'date']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('delivery_country') ?></label>
                            <?= Html::activeTextInput($model, 'delivery_country', ['class' => 'crm-input', 'placeholder' => 'BY']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('city') ?></label>
                            <?= Html::activeTextInput($model, 'city', ['class' => 'crm-input', 'id' => 'cityInput']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('region') ?></label>
                            <?= Html::activeTextInput($model, 'region', ['class' => 'crm-input', 'id' => 'regionInput']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('postal_code') ?></label>
                            <?= Html::activeTextInput($model, 'postal_code', ['class' => 'crm-input', 'inputmode' => 'numeric', 'id' => 'postalCodeInput']) ?>
                        </div>
                    </div>
                    <div class="crm-field" style="margin-top:10px">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('full_address') ?></label>
                        <?= Html::activeTextInput($model, 'full_address', ['class' => 'crm-input', 'id' => 'fullAddressInput', 'placeholder' => 'ул. Ленина, д. 1, кв. 5']) ?>
                    </div>
                    <div class="crm-field" style="margin-top:10px">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('comment') ?></label>
                        <?= Html::activeTextarea($model, 'comment', ['class' => 'crm-input', 'rows' => 2, 'placeholder' => 'Комментарий менеджера']) ?>
                    </div>
                </div>
            </div>

            <!-- Данные для ДоброПост -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-airplane"></i> Данные для ДоброПост</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid cols3">
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('china_track_number') ?></label>
                            <?= Html::activeTextInput($model, 'china_track_number', ['class' => 'crm-input', 'style' => 'font-family:monospace']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('ms_number') ?></label>
                            <?= Html::activeTextInput($model, 'ms_number', ['class' => 'crm-input']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('dobropost_tariff') ?> <small>(26=Мск Экспресс)</small></label>
                            <?= Html::activeTextInput($model, 'dobropost_tariff', ['class' => 'crm-input', 'inputmode' => 'numeric']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('shipment_value_cny') ?> (¥)</label>
                            <?= Html::activeTextInput($model, 'shipment_value_cny', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('item_quantity') ?></label>
                            <?= Html::activeTextInput($model, 'item_quantity', ['class' => 'crm-input', 'type' => 'number', 'min' => 1]) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('item_price_cny') ?> (¥)</label>
                            <?= Html::activeTextInput($model, 'item_price_cny', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01']) ?>
                        </div>
                    </div>
                    <div class="crm-info-grid" style="margin-top:10px">
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('product_link') ?></label>
                            <?= Html::activeTextInput($model, 'product_link', ['class' => 'crm-input', 'type' => 'url', 'placeholder' => 'https://poizon.com/…']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('sneakerhead_order_link') ?></label>
                            <?= Html::activeTextInput($model, 'sneakerhead_order_link', ['class' => 'crm-input', 'type' => 'url']) ?>
                        </div>
                    </div>
                    <div class="crm-field" style="margin-top:10px">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('customs_description') ?> <small>(макс. 60 симв.)</small></label>
                        <?= Html::activeTextInput($model, 'customs_description', ['class' => 'crm-input', 'maxlength' => 60, 'placeholder' => 'Одежда и обувь']) ?>
                    </div>
                </div>
            </div>

            <!-- Паспортные данные -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-person-vcard"></i> Паспортные данные получателя</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid cols3">
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('recipient_last_name') ?></label>
                            <?= Html::activeTextInput($model, 'recipient_last_name', ['class' => 'crm-input', 'placeholder' => 'ИВАНОВ']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('recipient_first_name') ?></label>
                            <?= Html::activeTextInput($model, 'recipient_first_name', ['class' => 'crm-input', 'placeholder' => 'ИВАН']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('recipient_middle_name') ?></label>
                            <?= Html::activeTextInput($model, 'recipient_middle_name', ['class' => 'crm-input', 'placeholder' => 'ИВАНОВИЧ']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('passport_series') ?></label>
                            <?= Html::activeTextInput($model, 'passport_series', ['class' => 'crm-input', 'maxlength' => 4, 'placeholder' => 'AB12', 'style' => 'font-family:monospace']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('passport_number') ?></label>
                            <?= Html::activeTextInput($model, 'passport_number', ['class' => 'crm-input', 'maxlength' => 7, 'inputmode' => 'numeric', 'placeholder' => '1234567', 'style' => 'font-family:monospace']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('passport_issue_date') ?></label>
                            <?= Html::activeTextInput($model, 'passport_issue_date', ['class' => 'crm-input', 'type' => 'date']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('birth_date') ?></label>
                            <?= Html::activeTextInput($model, 'birth_date', ['class' => 'crm-input', 'type' => 'date']) ?>
                        </div>
                        <div class="crm-field">
                            <label class="crm-field-label"><?= $model->getAttributeLabel('inn') ?> <small>(12 цифр)</small></label>
                            <?= Html::activeTextInput($model, 'inn', ['class' => 'crm-input', 'maxlength' => 12, 'inputmode' => 'numeric', 'placeholder' => '123456789012']) ?>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /crm-main -->

        <!-- ── RIGHT SIDEBAR ── -->
        <div class="crm-sidebar">

            <!-- Источник -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-share"></i> Источник</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('source') ?></label>
                        <select name="Order[source]" class="crm-input">
                            <option value="">— выберите —</option>
                            <?php foreach ($sources as $src): ?>
                                <option value="<?= Html::encode($src) ?>" <?= $model->source === $src ? 'selected' : '' ?>>
                                    <?= Html::encode($src) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Клиент -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-person-circle"></i> Покупатель</h3>
                </div>
                <div class="crm-card-body" style="display:flex;flex-direction:column;gap:8px">
                    <div class="crm-field" style="position:relative">
                        <label class="crm-field-label">Поиск клиента</label>
                        <input type="text" id="customerSearch" class="crm-input"
                               placeholder="Имя, телефон или email…" autocomplete="off">
                        <div id="customerDropdown" class="crm-customer-drop"></div>
                        <input type="hidden" id="customerIdInput" name="Order[customer_id]" value="<?= Html::encode($model->customer_id ?? '') ?>">
                        <div id="customerSelectedInfo" style="display:none;margin-top:6px;font-size:0.75rem;color:var(--admin-text-secondary)"></div>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('client_name') ?></label>
                        <?= Html::activeTextInput($model, 'client_name', ['class' => 'crm-input', 'id' => 'clientNameInput']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('client_phone') ?></label>
                        <?= Html::activeTextInput($model, 'client_phone', ['class' => 'crm-input', 'id' => 'clientPhoneInput', 'type' => 'tel']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('client_email') ?></label>
                        <?= Html::activeTextInput($model, 'client_email', ['class' => 'crm-input', 'id' => 'clientEmailInput', 'type' => 'email']) ?>
                    </div>
                </div>
            </div>

            <!-- Статус -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-sliders"></i> Статус</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('status') ?></label>
                        <select name="Order[status]" class="crm-input">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= Html::encode($key) ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px">
                        <?php foreach ([
                            'offer_accepted'  => 'Оферта принята',
                            'is_processed'    => 'Обработано',
                            'is_shipped'      => 'Отправлено',
                            'customs_cleared' => 'Таможня пройдена',
                        ] as $field => $flagLabel): ?>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.75rem">
                                <?= Html::activeCheckbox($model, $field, ['label' => false]) ?>
                                <?= $flagLabel ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Итого / Оплата -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-receipt"></i> Оплата и итог</h3>
                </div>
                <div class="crm-card-body" style="display:flex;flex-direction:column;gap:8px">
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('payment_method') ?></label>
                        <?= Html::activeTextInput($model, 'payment_method', ['class' => 'crm-input', 'placeholder' => 'Перевод / ЕРИП']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('product_price') ?></label>
                        <?= Html::activeTextInput($model, 'product_price', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01', 'id' => 'productPriceInput']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('logistics_price') ?></label>
                        <?= Html::activeTextInput($model, 'logistics_price', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('commission_price') ?></label>
                        <?= Html::activeTextInput($model, 'commission_price', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01']) ?>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label"><?= $model->getAttributeLabel('delivery_cost') ?></label>
                        <?= Html::activeTextInput($model, 'delivery_cost', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01']) ?>
                    </div>
                    <div class="crm-field" style="padding-top:8px;border-top:1px solid var(--admin-border,#e5e7eb)">
                        <label class="crm-field-label" style="font-weight:700"><?= $model->getAttributeLabel('total_amount') ?></label>
                        <?= Html::activeTextInput($model, 'total_amount', ['class' => 'crm-input', 'type' => 'number', 'step' => '0.01', 'id' => 'totalAmountInput', 'style' => 'font-weight:700;font-size:1rem']) ?>
                    </div>
                </div>
            </div>

            <!-- Логист (admin only) -->
            <?php if ($user->isAdmin() && !empty($logists)): ?>
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-person-badge"></i> Логист</h3>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field">
                        <select name="Order[assigned_logist]" class="crm-input">
                            <option value="">— не назначен —</option>
                            <?php foreach ($logists as $logist): ?>
                                <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                    <?= Html::encode($logist->username) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /crm-sidebar -->

    </div><!-- /crm-body -->

    <!-- ═══ SAVE BAR ═══ -->
    <div class="crm-save-bar">
        <a href="<?= Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary">
            Отмена
        </a>
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-check-lg"></i> Создать заказ
        </button>
    </div>

</div><!-- /crm-wrap -->

<?php ActiveForm::end(); ?>

<?php
$customerSearchUrl = Url::to(['/admin/customer/search']);
$js = <<<JS
(function() {
    // Customer autocomplete
    var csrInput = document.getElementById('customerSearch');
    var csrDrop  = document.getElementById('customerDropdown');
    var csrId    = document.getElementById('customerIdInput');
    var csrInfo  = document.getElementById('customerSelectedInfo');
    var timer    = null;

    if (csrInput) {
        csrInput.addEventListener('input', function() {
            clearTimeout(timer);
            var q = this.value.trim();
            if (q.length < 2) { csrDrop.style.display = 'none'; return; }
            timer = setTimeout(function() {
                fetch('{$customerSearchUrl}?q=' + encodeURIComponent(q), {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                }).then(function(r) { return r.json(); }).then(function(list) {
                    csrDrop.innerHTML = '';
                    if (!list.length) {
                        csrDrop.innerHTML = '<div class="crm-customer-opt" style="color:var(--admin-text-secondary)">Клиент не найден</div>';
                    } else {
                        list.forEach(function(c) {
                            var el = document.createElement('div');
                            el.className = 'crm-customer-opt';
                            el.innerHTML = '<strong>' + (c.name || 'Клиент #' + c.id) + '</strong>' +
                                '<span>' + [c.phone, c.email].filter(Boolean).join(' · ') + '</span>';
                            el.addEventListener('click', function() {
                                csrId.value  = c.id;
                                csrInput.value = (c.name || 'Клиент #' + c.id) + ' — ' + (c.phone || c.email || '');
                                csrDrop.style.display = 'none';
                                var fill = function(id, val) { var el = document.getElementById(id); if (el && !el.value) el.value = val || ''; };
                                fill('clientPhoneInput', c.phone);
                                fill('clientEmailInput', c.email);
                                fill('cityInput', c.city);
                                fill('regionInput', c.region);
                                fill('postalCodeInput', c.postal_code);
                                fill('fullAddressInput', c.address);
                                fill('clientNameInput', c.name);
                                csrInfo.style.display = 'block';
                                csrInfo.innerHTML = '<i class="bi bi-check-circle-fill" style="color:var(--admin-success)"></i> Клиент привязан: <strong>' + (c.name || '#' + c.id) + '</strong>';
                            });
                            csrDrop.appendChild(el);
                        });
                    }
                    csrDrop.style.display = 'block';
                }).catch(function() { csrDrop.style.display = 'none'; });
            }, 280);
        });
        document.addEventListener('click', function(e) {
            if (!csrInput.contains(e.target) && !csrDrop.contains(e.target)) csrDrop.style.display = 'none';
        });
    }

    // Item builder
    var builder = document.getElementById('orderItemsBuilder');
    var addBtn  = document.getElementById('addItemBtn');
    var idx     = builder ? builder.querySelectorAll('.crm-item-row').length : 0;

    function calcTotal() {
        var total = 0;
        document.querySelectorAll('.crm-item-price').forEach(function(el) { total += parseFloat(el.value) || 0; });
        var disp = document.getElementById('orderTotalDisplay');
        if (disp) disp.textContent = total.toFixed(2) + ' BYN';
        var ta = document.getElementById('totalAmountInput');
        if (ta && !ta.dataset.edited) ta.value = total.toFixed(2);
    }

    if (builder) {
        builder.addEventListener('input', function(e) { if (e.target.classList.contains('crm-item-price')) calcTotal(); });
        builder.addEventListener('click', function(e) {
            var btn = e.target.closest('.crm-remove-item');
            if (btn && !btn.disabled) { btn.closest('.crm-item-row').remove(); calcTotal(); }
        });
        calcTotal();
    }

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var tr = document.createElement('tr');
            tr.className = 'crm-item-row';
            tr.dataset.index = idx;
            tr.innerHTML =
                '<td><input type="text" name="OrderItem[' + idx + '][product_name]" class="crm-input" style="min-width:160px" placeholder="Название товара"></td>' +
                '<td><input type="number" name="OrderItem[' + idx + '][quantity]" class="crm-input" value="1" min="1"></td>' +
                '<td><input type="number" step="0.01" name="OrderItem[' + idx + '][price]" class="crm-input crm-item-price" min="0" placeholder="0.00"></td>' +
                '<td><input type="text" name="OrderItem[' + idx + '][link]" class="crm-input" placeholder="https://…"></td>' +
                '<td><button type="button" class="admin-btn admin-btn-secondary admin-btn-sm crm-remove-item" title="Удалить"><i class="bi bi-trash"></i></button></td>';
            builder.appendChild(tr);
            idx++;
            calcTotal();
        });
    }

    var taInput = document.getElementById('totalAmountInput');
    if (taInput) taInput.addEventListener('change', function() { this.dataset.edited = '1'; });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
