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
                <div class="form-grid form-grid--2 mt-3">
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
                <div class="form-grid form-grid--2 mt-10px">
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
                <div class="form-field mt-10px">
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
                <div class="order-items-builder" id="orderItemsBuilder" data-item-count="<?= count($orderItems) ?>" data-initial-index="<?= count($orderItems) ?>">
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
                <div class="form-field mt-10px">
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
