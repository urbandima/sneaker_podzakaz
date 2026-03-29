<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Новый заказ';
$user = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$logists = $user->isAdmin()
    ? \app\models\User::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all()
    : [];
$orderItems = Yii::$app->request->post('OrderItem', [
    ['product_name' => '', 'quantity' => 1, 'price' => '', 'link' => ''],
]);
?>

<style>
:root {
    --bg: #f0f2f5;
    --panel: #ffffff;
    --border: #d9dde3;
    --text: #1b1f25;
    --muted: #6c7482;
    --accent: #2065d1;
    --accent-soft: #e4edff;
    --success: #1b9c69;
    --danger: #dc2626;
}

.order-console {
    background: var(--bg);
    min-height: 100vh;
    padding: 28px clamp(16px, 3vw, 48px) 60px;
    font-family: "Segoe UI", "PT Sans", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--text);
}

.order-console__header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.order-console__title {
    font-size: clamp(1.4rem, 2vw, 2rem);
    font-weight: 600;
    margin: 0;
}

.order-console__actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.order-btn {
    border-radius: 6px;
    padding: 9px 16px;
    border: 1px solid var(--border);
    font-weight: 600;
    font-size: .92rem;
    background: var(--panel);
    color: var(--text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .18s ease;
}

.order-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.order-btn--primary {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
}

.order-btn--danger {
    border-color: var(--danger);
    color: var(--danger);
}

.order-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 22px;
}

.order-metric {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
}

.order-metric__label {
    font-size: .78rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
}

.order-metric__value {
    font-size: 1.35rem;
    font-weight: 600;
    margin-top: 6px;
}

.order-body {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 16px;
}

@media (max-width: 1180px) {
    .order-body {
        grid-template-columns: 1fr;
    }
}

.order-primary,
.order-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.panel {
    background: var(--panel);
    border-radius: 12px;
    border: 1px solid var(--border);
    padding: 18px 20px;
}

.panel__header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 12px;
}

.panel__title {
    font-weight: 600;
    font-size: 1rem;
}

.panel__hint {
    font-size: .85rem;
    color: var(--muted);
}

.form-grid {
    display: grid;
    gap: 10px 14px;
}

.form-grid--2 {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.form-grid--3 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form-field label {
    font-size: .75rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--muted);
}

.form-field input,
.form-field textarea,
.form-field select {
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .95rem;
    color: var(--text);
    background: #fdfefe;
    transition: border .15s ease, box-shadow .15s ease;
    width: 100%;
}

.form-field textarea {
    min-height: 68px;
    resize: vertical;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-soft);
    outline: none;
}

.field-error {
    color: var(--danger);
    font-size: .8rem;
}

.order-items-builder {
    border: 1px dashed var(--border);
    border-radius: 12px;
    background: #fbfcff;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-item-row {
    display: grid;
    grid-template-columns: minmax(0, 3fr) minmax(0, 1fr) minmax(0, 1fr) 40px;
    gap: 10px;
    align-items: end;
}

.order-item-row .remove-item {
    border: 1px solid var(--danger);
    background: #fff;
    color: var(--danger);
    border-radius: 8px;
    height: 40px;
    width: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
}

.order-item-row .remove-item:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.add-item-btn {
    border: 1px dashed var(--accent);
    color: var(--accent);
    border-radius: 10px;
    padding: 9px 14px;
    background: rgba(32, 101, 209, .04);
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.save-bar {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
}
</style>

<div class="order-console">
    <div class="order-console__header">
        <div>
            <h1 class="order-console__title"><?= Html::encode($this->title) ?></h1>
            <div class="panel__hint">Создайте заказ с теми же блоками, что и карточка просмотра</div>
        </div>
        <div class="order-console__actions">
            <a class="order-btn" href="<?= Url::to(['/admin/order/index']) ?>">← Список заказов</a>
            <button type="submit" form="orderCreateForm" class="order-btn order-btn--primary">Сохранить заказ</button>
        </div>
    </div>

    <section class="order-metrics">
        <div class="order-metric">
            <div class="order-metric__label">Сумма заказа</div>
            <div class="order-metric__value" id="orderTotalDisplay">—</div>
            <div class="panel__hint">Обновится после ввода позиций</div>
        </div>
        <div class="order-metric">
            <div class="order-metric__label">Позиции</div>
            <div class="order-metric__value" id="itemCountDisplay"><?= count($orderItems) ?></div>
            <div class="panel__hint">Минимум одна позиция</div>
        </div>
        <div class="order-metric">
            <div class="order-metric__label">Статус</div>
            <div class="order-metric__value">Черновик</div>
            <div class="panel__hint">Можно выбрать на панели справа</div>
        </div>
    </section>

    <?php $form = ActiveForm::begin([
        'id' => 'orderCreateForm',
        'action' => Url::to(['/admin/order/create']),
    ]); ?>

    <div class="order-body">
        <div class="order-primary">
            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Клиент и доставка</div>
                    <div class="panel__hint">Обязательные поля для связи и логистики</div>
                </div>
                <div class="form-grid form-grid--3">
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('client_name') ?></label>
                        <?= Html::activeTextInput($model, 'client_name') ?>
                        <?= Html::error($model, 'client_name', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('client_phone') ?></label>
                        <?= Html::activeTextInput($model, 'client_phone') ?>
                        <?= Html::error($model, 'client_phone', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('client_email') ?></label>
                        <?= Html::activeTextInput($model, 'client_email', ['type' => 'email']) ?>
                        <?= Html::error($model, 'client_email', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('delivery_method') ?></label>
                        <?= Html::activeTextInput($model, 'delivery_method') ?>
                        <?= Html::error($model, 'delivery_method', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('delivery_date') ?></label>
                        <?= Html::activeTextInput($model, 'delivery_date') ?>
                        <?= Html::error($model, 'delivery_date', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('delivery_country') ?></label>
                        <?= Html::activeTextInput($model, 'delivery_country') ?>
                        <?= Html::error($model, 'delivery_country', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('city') ?></label>
                        <?= Html::activeTextInput($model, 'city') ?>
                        <?= Html::error($model, 'city', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('region') ?></label>
                        <?= Html::activeTextInput($model, 'region') ?>
                        <?= Html::error($model, 'region', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('postal_code') ?></label>
                        <?= Html::activeTextInput($model, 'postal_code') ?>
                        <?= Html::error($model, 'postal_code', ['class' => 'field-error']) ?>
                    </div>
                </div>
                <div class="form-grid form-grid--2" style="margin-top:12px;">
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('full_address') ?></label>
                        <?= Html::activeTextarea($model, 'full_address') ?>
                        <?= Html::error($model, 'full_address', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('comment') ?></label>
                        <?= Html::activeTextarea($model, 'comment', ['placeholder' => 'Комментарий менеджера']) ?>
                        <?= Html::error($model, 'comment', ['class' => 'field-error']) ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Получатель и паспорт</div>
                    <div class="panel__hint">Используется для таможенных деклараций</div>
                </div>
                <div class="form-grid form-grid--3">
                    <?php foreach ([
                        'recipient_last_name',
                        'recipient_first_name',
                        'recipient_middle_name',
                        'passport_series',
                        'passport_number',
                        'passport_issue_date',
                        'birth_date',
                        'inn',
                    ] as $attribute): ?>
                        <div class="form-field">
                            <label><?= $model->getAttributeLabel($attribute) ?></label>
                            <?= Html::activeInput($attribute === 'passport_issue_date' || $attribute === 'birth_date' ? 'date' : 'text', $model, $attribute) ?>
                            <?= Html::error($model, $attribute, ['class' => 'field-error']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Логистика, ссылки и описание</div>
                    <div class="panel__hint">Укажите всё, что поможет доставке</div>
                </div>
                <div class="form-grid form-grid--3">
                    <?php foreach ([
                        'china_track_number',
                        'ms_number',
                        'dobropost_tariff',
                        'shipment_value_cny',
                        'item_quantity',
                        'item_price_cny',
                    ] as $attribute): ?>
                        <div class="form-field">
                            <label><?= $model->getAttributeLabel($attribute) ?></label>
                            <?= Html::activeTextInput($model, $attribute, [
                                'type' => in_array($attribute, ['shipment_value_cny', 'item_price_cny'], true) ? 'number' : 'text',
                                'step' => in_array($attribute, ['shipment_value_cny', 'item_price_cny'], true) ? '0.01' : null,
                            ]) ?>
                            <?= Html::error($model, $attribute, ['class' => 'field-error']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-grid form-grid--2" style="margin-top:10px;">
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('product_link') ?></label>
                        <?= Html::activeTextInput($model, 'product_link', ['type' => 'url']) ?>
                        <?= Html::error($model, 'product_link', ['class' => 'field-error']) ?>
                    </div>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel('sneakerhead_order_link') ?></label>
                        <?= Html::activeTextInput($model, 'sneakerhead_order_link', ['type' => 'url']) ?>
                        <?= Html::error($model, 'sneakerhead_order_link', ['class' => 'field-error']) ?>
                    </div>
                </div>
                <div class="form-field" style="margin-top:10px;">
                    <label><?= $model->getAttributeLabel('customs_description') ?></label>
                    <?= Html::activeTextarea($model, 'customs_description') ?>
                    <?= Html::error($model, 'customs_description', ['class' => 'field-error']) ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Позиции заказа</div>
                    <div class="panel__hint">Добавьте хотя бы один товар</div>
                </div>
                <div class="order-items-builder" id="orderItemsBuilder">
                    <?php foreach ($orderItems as $index => $item): ?>
                        <div class="order-item-row" data-index="<?= $index ?>">
                            <div class="form-field">
                                <label>Название</label>
                                <input type="text" name="OrderItem[<?= $index ?>][product_name]" value="<?= Html::encode($item['product_name']) ?>">
                            </div>
                            <div class="form-field">
                                <label>Кол-во</label>
                                <input type="number" name="OrderItem[<?= $index ?>][quantity]" value="<?= (int)($item['quantity'] ?: 1) ?>" min="1">
                            </div>
                            <div class="form-field">
                                <label>Цена, BYN</label>
                                <input type="number" step="0.01" name="OrderItem[<?= $index ?>][price]" value="<?= Html::encode($item['price']) ?>">
                            </div>
                            <button type="button" class="remove-item" <?= $index === 0 ? 'disabled' : '' ?>>×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-item-btn" id="addItemBtn">+ Добавить товар</button>
            </section>
        </div>

        <aside class="order-side">
            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Статус и источник</div>
                    <div class="panel__hint">Выберите стартовый статус</div>
                </div>
                <div class="form-field">
                    <label><?= $model->getAttributeLabel('status') ?></label>
                    <select name="Order[status]">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= Html::encode($key) ?>" <?= $model->status === $key ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= Html::error($model, 'status', ['class' => 'field-error']) ?>
                </div>
                <div class="form-field" style="margin-top:10px;">
                    <label><?= $model->getAttributeLabel('source') ?></label>
                    <?= Html::activeTextInput($model, 'source') ?>
                    <?= Html::error($model, 'source', ['class' => 'field-error']) ?>
                </div>
                <div class="form-field">
                    <label><?= $model->getAttributeLabel('source_id') ?></label>
                    <?= Html::activeTextInput($model, 'source_id') ?>
                    <?= Html::error($model, 'source_id', ['class' => 'field-error']) ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Финансовые поля</div>
                    <div class="panel__hint">Суммы используются в отчётах</div>
                </div>
                <?php foreach (['product_price', 'logistics_price', 'commission_price', 'delivery_cost', 'total_amount'] as $attribute): ?>
                    <div class="form-field">
                        <label><?= $model->getAttributeLabel($attribute) ?></label>
                        <?= Html::activeTextInput($model, $attribute, ['type' => 'number', 'step' => '0.01']) ?>
                        <?= Html::error($model, $attribute, ['class' => 'field-error']) ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Флаги процесса</div>
                    <div class="panel__hint">Проверки логиста</div>
                </div>
                <?php foreach ([
                    'offer_accepted' => 'Оферта принята',
                    'is_processed' => 'Обработано',
                    'is_shipped' => 'Отправлено',
                    'customs_cleared' => 'Таможня пройдена',
                ] as $field => $label): ?>
                    <label style="display:flex;align-items:center;gap:.5rem;">
                        <?= Html::activeCheckbox($model, $field, ['label' => false]) ?>
                        <span><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </section>

            <?php if ($user->isAdmin()): ?>
            <section class="panel">
                <div class="panel__header">
                    <div class="panel__title">Ответственный логист</div>
                    <div class="panel__hint">Можно назначить сразу</div>
                </div>
                <div class="form-field">
                    <label><?= $model->getAttributeLabel('assigned_logist') ?></label>
                    <select name="Order[assigned_logist]">
                        <option value="">—</option>
                        <?php foreach ($logists as $logist): ?>
                            <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                <?= Html::encode($logist->username) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>
            <?php endif; ?>
        </aside>
    </div>

    <div class="save-bar">
        <a class="order-btn" href="<?= Url::to(['/admin/order/index']) ?>">Отмена</a>
        <button type="submit" class="order-btn order-btn--primary">Создать заказ</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
const orderItemsBuilder = document.getElementById('orderItemsBuilder');
const addItemBtn = document.getElementById('addItemBtn');
let orderItemIndex = <?= count($orderItems) ?>;

function updateRemoveButtons() {
    const rows = orderItemsBuilder.querySelectorAll('.order-item-row');
    rows.forEach((row, idx) => {
        const btn = row.querySelector('.remove-item');
        btn.disabled = rows.length === 1;
    });
    document.getElementById('itemCountDisplay').textContent = rows.length;
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    orderItemsBuilder.querySelectorAll('.order-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
        total += qty * price;
    });
    document.getElementById('orderTotalDisplay').textContent = total ? total.toFixed(2) + ' BYN' : '—';
}

orderItemsBuilder.addEventListener('input', (e) => {
    if (e.target.matches('input[type="number"]')) {
        recalcTotal();
    }
});

orderItemsBuilder.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-item')) {
        e.target.closest('.order-item-row').remove();
        updateRemoveButtons();
    }
});

addItemBtn.addEventListener('click', () => {
    const tpl = document.createElement('div');
    tpl.className = 'order-item-row';
    tpl.dataset.index = orderItemIndex;
    tpl.innerHTML = `
        <div class="form-field">
            <label>Название</label>
            <input type="text" name="OrderItem[${orderItemIndex}][product_name]" placeholder="Название товара">
        </div>
        <div class="form-field">
            <label>Кол-во</label>
            <input type="number" name="OrderItem[${orderItemIndex}][quantity]" value="1" min="1">
        </div>
        <div class="form-field">
            <label>Цена, BYN</label>
            <input type="number" step="0.01" name="OrderItem[${orderItemIndex}][price]" placeholder="0.00">
        </div>
        <button type="button" class="remove-item">×</button>
    `;
    orderItemsBuilder.appendChild(tpl);
    orderItemIndex++;
    updateRemoveButtons();
});

updateRemoveButtons();
recalcTotal();
</script>
