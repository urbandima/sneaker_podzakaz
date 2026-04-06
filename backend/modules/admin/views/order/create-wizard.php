<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Новый заказ';
$user = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$logists = $user->isAdmin()
    ? (function() {
        try {
            return \app\backend\modules\admin\models\User::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all();
        } catch (\Exception $e) {
            return [];
        }
    })()
    : [];
?>



<div class="order-wizard">
    <div class="wizard-header">
        <div>
            <h1 class="wizard-title"><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="wizard-actions">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="btn btn--secondary">
                ← Список заказов
            </a>
        </div>
    </div>

    <div class="wizard-container">
        <aside class="wizard-sidebar">
            <div class="wizard-steps">
                <div class="step-item step-item--active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Клиент и доставка</div>
                        <div class="step-description">Основные данные</div>
                        <div class="step-validation-summary step-validation-summary--hidden" id="step1-errors"></div>
                    </div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Товары</div>
                        <div class="step-description">Позиции заказа</div>
                        <div class="step-validation-summary step-validation-summary--hidden" id="step2-errors"></div>
                    </div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Паспорт и логистика</div>
                        <div class="step-description">Доп. информация</div>
                        <div class="step-validation-summary step-validation-summary--hidden" id="step3-errors"></div>
                    </div>
                </div>
                <div class="step-item" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <div class="step-title">Финансы и статус</div>
                        <div class="step-description">Итоговые данные</div>
                        <div class="step-validation-summary step-validation-summary--hidden" id="step4-errors"></div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="wizard-main">
            <?php $form = ActiveForm::begin([
                'id' => 'orderWizardForm',
                'action' => Url::to(['/admin/order/create']),
                'options' => ['data-wizard' => 'true']
            ]); ?>

            <!-- Шаг 1: Клиент и доставка -->
            <div class="wizard-step-content wizard-step-content--active" data-step="1">
                <div class="step-header">
                    <h2 class="step-title-main">Клиент и доставка</h2>
                    <p class="step-description-main">Введите основную информацию о клиенте и параметрах доставки</p>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        📞 Контактные данные клиента
                    </h3>
                    <div class="form-grid form-grid--3">
                        <div class="form-field">
                            <label>
                                ФИО клиента
                                <span class="required">*</span>
                                <span class="tooltip-icon" data-tooltip="Введите полное имя клиента как в паспорте">?</span>
                            </label>
                            <?= Html::activeTextInput($model, 'client_name', [
                                'data-validation' => 'required',
                                'data-field' => 'client_name'
                            ]) ?>
                            <div class="field-hint">Например: Иванов Иван Иванович</div>
                        </div>
                        <div class="form-field">
                            <label>
                                Телефон
                                <span class="required">*</span>
                                <span class="tooltip-icon" data-tooltip="В формате +37529XXXXXXX">?</span>
                            </label>
                            <?= Html::activeTextInput($model, 'client_phone', [
                                'data-validation' => 'required phone',
                                'data-field' => 'client_phone',
                                'placeholder' => '+37529XXXXXXX'
                            ]) ?>
                            <div class="field-hint">Для связи и SMS уведомлений</div>
                        </div>
                        <div class="form-field">
                            <label>
                                Email
                                <span class="tooltip-icon" data-tooltip="Для отправки чеков и уведомлений">?</span>
                            </label>
                            <?= Html::activeTextInput($model, 'client_email', [
                                'type' => 'email',
                                'data-validation' => 'email',
                                'data-field' => 'client_email'
                            ]) ?>
                            <div class="field-hint">Опционально</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        📍 Параметры доставки
                    </h3>
                    <div class="form-grid form-grid--3">
                        <div class="form-field">
                            <label>
                                Способ доставки
                                <span class="required">*</span>
                            </label>
                            <?= Html::activeTextInput($model, 'delivery_method', [
                                'data-validation' => 'required',
                                'data-field' => 'delivery_method'
                            ]) ?>
                            <div class="field-hint">Например: Dobropost, EMS, самовывоз</div>
                        </div>
                        <div class="form-field">
                            <label>
                                Дата доставки
                                <span class="required">*</span>
                            </label>
                            <?= Html::activeTextInput($model, 'delivery_date', [
                                'data-validation' => 'required',
                                'data-field' => 'delivery_date',
                                'type' => 'date'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>
                                Страна
                                <span class="required">*</span>
                            </label>
                            <?= Html::activeTextInput($model, 'delivery_country', [
                                'data-validation' => 'required',
                                'data-field' => 'delivery_country'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>
                                Город
                                <span class="required">*</span>
                            </label>
                            <?= Html::activeTextInput($model, 'city', [
                                'data-validation' => 'required',
                                'data-field' => 'city'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>Область</label>
                            <?= Html::activeTextInput($model, 'region', [
                                'data-field' => 'region'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>Почтовый индекс</label>
                            <?= Html::activeTextInput($model, 'postal_code', [
                                'data-field' => 'postal_code'
                            ]) ?>
                        </div>
                    </div>
                    <div class="form-grid form-grid--2" style="margin-top: 16px;">
                        <div class="form-field">
                            <label>
                                Полный адрес
                                <span class="required">*</span>
                            </label>
                            <?= Html::activeTextarea($model, 'full_address', [
                                'data-validation' => 'required',
                                'data-field' => 'full_address',
                                'rows' => 3
                            ]) ?>
                            <div class="field-hint">Улица, дом, квартира</div>
                        </div>
                        <div class="form-field">
                            <label>Комментарий менеджера</label>
                            <?= Html::activeTextarea($model, 'comment', [
                                'data-field' => 'comment',
                                'rows' => 3,
                                'placeholder' => 'Особые пожелания клиента...'
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Шаг 2: Товары -->
            <div class="wizard-step-content" data-step="2">
                <div class="step-header">
                    <h2 class="step-title-main">Товары</h2>
                    <p class="step-description-main">Добавьте позиции заказа</p>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        🛍️ Позиции заказа
                        <span style="color: var(--danger); font-size: 0.875rem;">(минимум 1 позиция)</span>
                    </h3>
                    
                    <table class="products-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>Название товара</th>
                                <th>Количество</th>
                                <th>Цена, BYN</th>
                                <th>Сумма, BYN</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr data-index="0">
                                <td>
                                    <input type="text" name="OrderItem[0][product_name]" 
                                           placeholder="Введите название товара" 
                                           data-validation="required"
                                           data-field="product_name_0">
                                </td>
                                <td>
                                    <input type="number" name="OrderItem[0][quantity]" 
                                           value="1" min="1" 
                                           data-field="quantity_0"
                                           data-calc="quantity">
                                </td>
                                <td>
                                    <input type="number" name="OrderItem[0][price]" 
                                           step="0.01" placeholder="0.00" 
                                           data-validation="required positive"
                                           data-field="price_0"
                                           data-calc="price">
                                </td>
                                <td class="total-cell" data-total="0">0.00</td>
                                <td class="product-actions">
                                    <button type="button" class="icon-btn icon-btn--danger" onclick="removeProduct(0)" title="Удалить">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: 600;">Итого:</td>
                                <td id="productsTotal" style="font-weight: 600; font-size: 1.125rem;">0.00 BYN</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <button type="button" class="add-product-btn" onclick="addProduct()">
                        <span>➕</span>
                        <span>Добавить товар</span>
                    </button>
                </div>
            </div>

            <!-- Шаг 3: Паспорт и логистика -->
            <div class="wizard-step-content" data-step="3">
                <div class="step-header">
                    <h2 class="step-title-main">Паспорт и логистика</h2>
                    <p class="step-description-main">Дополнительная информация для доставки и таможни</p>
                </div>

                <div class="collapsible-section">
                    <div class="collapsible-header" onclick="toggleCollapsible(this)">
                        <div class="collapsible-title">📋 Паспортные данные получателя</div>
                        <div class="collapsible-icon">▼</div>
                    </div>
                    <div class="collapsible-content">
                        <div class="form-grid form-grid--3">
                            <div class="form-field">
                                <label>Фамилия получателя</label>
                                <?= Html::activeTextInput($model, 'recipient_last_name', [
                                    'data-field' => 'recipient_last_name'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Имя получателя</label>
                                <?= Html::activeTextInput($model, 'recipient_first_name', [
                                    'data-field' => 'recipient_first_name'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Отчество получателя</label>
                                <?= Html::activeTextInput($model, 'recipient_middle_name', [
                                    'data-field' => 'recipient_middle_name'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Серия паспорта</label>
                                <?= Html::activeTextInput($model, 'passport_series', [
                                    'data-field' => 'passport_series'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Номер паспорта</label>
                                <?= Html::activeTextInput($model, 'passport_number', [
                                    'data-field' => 'passport_number'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Дата выдачи паспорта</label>
                                <?= Html::activeTextInput($model, 'passport_issue_date', [
                                    'type' => 'date',
                                    'data-field' => 'passport_issue_date'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Дата рождения</label>
                                <?= Html::activeTextInput($model, 'birth_date', [
                                    'type' => 'date',
                                    'data-field' => 'birth_date'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>ИНН (для РФ)</label>
                                <?= Html::activeTextInput($model, 'inn', [
                                    'data-field' => 'inn'
                                ]) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="collapsible-section">
                    <div class="collapsible-header" onclick="toggleCollapsible(this)">
                        <div class="collapsible-title">🚚 Логистика и ссылки</div>
                        <div class="collapsible-icon">▼</div>
                    </div>
                    <div class="collapsible-content">
                        <div class="form-grid form-grid--3">
                            <div class="form-field">
                                <label>Китайский трек-номер</label>
                                <?= Html::activeTextInput($model, 'china_track_number', [
                                    'data-field' => 'china_track_number'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>№ МС</label>
                                <?= Html::activeTextInput($model, 'ms_number', [
                                    'data-field' => 'ms_number'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>DobroПост тариф</label>
                                <?= Html::activeTextInput($model, 'dobropost_tariff', [
                                    'data-field' => 'dobropost_tariff'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Ценность товара (¥)</label>
                                <?= Html::activeTextInput($model, 'shipment_value_cny', [
                                    'type' => 'number',
                                    'step' => '0.01',
                                    'data-field' => 'shipment_value_cny',
                                    'data-calc' => 'logistics'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Количество товаров</label>
                                <?= Html::activeTextInput($model, 'item_quantity', [
                                    'type' => 'number',
                                    'data-field' => 'item_quantity'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Цена за единицу (¥)</label>
                                <?= Html::activeTextInput($model, 'item_price_cny', [
                                    'type' => 'number',
                                    'step' => '0.01',
                                    'data-field' => 'item_price_cny'
                                ]) ?>
                            </div>
                        </div>
                        <div class="form-grid form-grid--2" style="margin-top: 16px;">
                            <div class="form-field">
                                <label>Ссылка на товар</label>
                                <?= Html::activeTextInput($model, 'product_link', [
                                    'type' => 'url',
                                    'data-field' => 'product_link'
                                ]) ?>
                            </div>
                            <div class="form-field">
                                <label>Ссылка Sneakerhead</label>
                                <?= Html::activeTextInput($model, 'sneakerhead_order_link', [
                                    'type' => 'url',
                                    'data-field' => 'sneakerhead_order_link'
                                ]) ?>
                            </div>
                        </div>
                        <div class="form-field" style="margin-top: 16px;">
                            <label>Описание для таможни</label>
                            <?= Html::activeTextarea($model, 'customs_description', [
                                'data-field' => 'customs_description',
                                'rows' => 3,
                                'placeholder' => 'Описание содержимого для таможенной декларации...'
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Шаг 4: Финансы и статус -->
            <div class="wizard-step-content" data-step="4">
                <div class="step-header">
                    <h2 class="step-title-main">Финансы и статус</h2>
                    <p class="step-description-main">Итоговые данные и создание заказа</p>
                </div>

                <div class="summary-card">
                    <h3 class="summary-title">📊 Сводка по заказу</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-value" id="summaryItems">0</div>
                            <div class="summary-label">Позиций</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="summaryTotal">0.00 BYN</div>
                            <div class="summary-label">Сумма товаров</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="summaryLogistics">0.00 BYN</div>
                            <div class="summary-label">Логистика</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="summaryFinal">0.00 BYN</div>
                            <div class="summary-label">Итого к оплате</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        💰 Финансовые параметры
                    </h3>
                    <div class="form-grid form-grid--2">
                        <div class="form-field">
                            <label>Стоимость логистики, BYN</label>
                            <?= Html::activeTextInput($model, 'logistics_price', [
                                'type' => 'number',
                                'step' => '0.01',
                                'data-field' => 'logistics_price',
                                'data-calc' => 'logistics',
                                'readonly' => true
                            ]) ?>
                            <div class="field-hint">Рассчитывается автоматически</div>
                        </div>
                        <div class="form-field">
                            <label>Комиссия, BYN</label>
                            <?= Html::activeTextInput($model, 'commission_price', [
                                'type' => 'number',
                                'step' => '0.01',
                                'data-field' => 'commission_price',
                                'data-calc' => 'commission'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>Стоимость доставки, BYN</label>
                            <?= Html::activeTextInput($model, 'delivery_cost', [
                                'type' => 'number',
                                'step' => '0.01',
                                'data-field' => 'delivery_cost',
                                'data-calc' => 'delivery'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>Общая сумма заказа, BYN</label>
                            <?= Html::activeTextInput($model, 'total_amount', [
                                'type' => 'number',
                                'step' => '0.01',
                                'data-field' => 'total_amount',
                                'readonly' => true
                            ]) ?>
                            <div class="field-hint">Рассчитывается автоматически</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        🎯 Статус и источник
                    </h3>
                    <div class="form-grid form-grid--2">
                        <div class="form-field">
                            <label>
                                Статус заказа
                                <span class="required">*</span>
                            </label>
                            <select name="Order[status]" data-validation="required" data-field="status">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= Html::encode($key) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Источник заказа</label>
                            <?= Html::activeTextInput($model, 'source', [
                                'data-field' => 'source',
                                'placeholder' => 'Например: сайт, телефон, amoCRM'
                            ]) ?>
                        </div>
                        <div class="form-field">
                            <label>ID источника</label>
                            <?= Html::activeTextInput($model, 'source_id', [
                                'data-field' => 'source_id',
                                'placeholder' => 'ID сделки в CRM и т.д.'
                            ]) ?>
                        </div>
                        <?php if ($user->isAdmin()): ?>
                        <div class="form-field">
                            <label>Ответственный логист</label>
                            <select name="Order[assigned_logist]" data-field="assigned_logist">
                                <option value="">—</option>
                                <?php foreach ($logists as $logist): ?>
                                    <option value="<?= $logist->id ?>"><?= Html::encode($logist->username) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        🏁 Флаги процесса
                    </h3>
                    <div class="form-grid form-grid--2">
                        <?php foreach ([
                            'offer_accepted' => 'Оферта принята',
                            'is_processed' => 'Обработано',
                            'is_shipped' => 'Отправлено',
                            'customs_cleared' => 'Таможня пройдена',
                        ] as $field => $label): ?>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <?= Html::activeCheckbox($model, $field, [
                                    'label' => false,
                                    'data-field' => $field
                                ]) ?>
                                <span><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="wizard-footer">
                <div class="wizard-progress">
                    <span>Шаг <span id="currentStep">1</span> из 4</span>
                </div>
                <div class="wizard-actions-footer">
                    <button type="button" class="btn btn--secondary" id="prevBtn" onclick="changeStep(-1)" disabled>
                        ← Назад
                    </button>
                    <button type="button" class="btn btn--primary" id="nextBtn" onclick="changeStep(1)">
                        Далее →
                    </button>
                    <button type="submit" class="btn btn--success" id="submitBtn" style="display: none;">
                        ✅ Создать заказ
                    </button>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </main>
    </div>
</div>

<div class="autosave-indicator" id="autosaveIndicator">
    <span>💾</span>
    <span>Черновик сохранён</span>
</div>


