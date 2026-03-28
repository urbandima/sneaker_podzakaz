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

<style>
:root {
    --bg: #f8fafc;
    --panel: #ffffff;
    --border: #e2e8f0;
    --text: #1e293b;
    --muted: #64748b;
    --accent: #3b82f6;
    --accent-soft: #dbeafe;
    --success: #10b981;
    --success-soft: #d1fae5;
    --danger: #ef4444;
    --danger-soft: #fee2e2;
    --warning: #f59e0b;
    --warning-soft: #fef3c7;
}

.order-wizard {
    background: var(--bg);
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--text);
}

.wizard-header {
    max-width: 1200px;
    margin: 0 auto 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.wizard-title {
    font-size: 1.875rem;
    font-weight: 700;
    margin: 0;
    color: var(--text);
}

.wizard-actions {
    display: flex;
    gap: 12px;
}

.btn {
    border-radius: 8px;
    padding: 10px 20px;
    border: 1px solid var(--border);
    font-weight: 600;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn--primary {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

.btn--primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    color: white;
}

.btn--success {
    background: var(--success);
    border-color: var(--success);
    color: white;
}

.btn--success:hover {
    background: #059669;
    border-color: #059669;
    color: white;
}

.btn--secondary {
    background: var(--panel);
    color: var(--muted);
    border-color: var(--border);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.wizard-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
}

.wizard-sidebar {
    background: var(--panel);
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--border);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.wizard-steps {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.step-item:hover {
    background: var(--accent-soft);
}

.step-item--active {
    background: var(--accent);
    color: white;
}

.step-item--completed {
    color: var(--success);
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    background: var(--border);
    color: var(--muted);
}

.step-item--active .step-number {
    background: white;
    color: var(--accent);
}

.step-item--completed .step-number {
    background: var(--success);
    color: white;
}

.step-item--has-errors {
    background: var(--danger-soft);
    color: var(--danger);
}

.step-item--has-errors .step-number {
    background: var(--danger);
    color: white;
}

.step-validation-summary {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--danger);
}

.step-validation-summary--hidden {
    display: none;
}

.step-content {
    flex: 1;
}

.step-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 2px;
}

.step-description {
    font-size: 0.75rem;
    opacity: 0.7;
}

.wizard-main {
    background: var(--panel);
    border-radius: 12px;
    padding: 32px;
    border: 1px solid var(--border);
}

.wizard-step-content {
    display: none;
}

.wizard-step-content--active {
    display: block;
}

.step-header {
    margin-bottom: 32px;
}

.step-title-main {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.step-description-main {
    color: var(--muted);
    font-size: 1rem;
}

.form-section {
    margin-bottom: 32px;
}

.form-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-grid {
    display: grid;
    gap: 16px;
}

.form-grid--2 {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.form-grid--3 {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 6px;
}

.required {
    color: var(--danger);
}

.field-hint {
    font-size: 0.75rem;
    color: var(--muted);
    margin-top: 4px;
}

.form-field input,
.form-field textarea,
.form-field select {
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 0.875rem;
    color: var(--text);
    background: var(--panel);
    transition: all 0.2s ease;
    width: 100%;
}

.form-field textarea {
    min-height: 80px;
    resize: vertical;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
}

.form-field input.error,
.form-field textarea.error,
.form-field select.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px var(--danger-soft);
}

.form-field .success {
    border-color: var(--success);
    box-shadow: 0 0 0 3px var(--success-soft);
}

.field-error {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.field-success {
    color: var(--success);
    font-size: 0.75rem;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.tooltip-icon {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--border);
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: help;
    position: relative;
}

.tooltip-icon:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--text);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 4px;
    max-width: 200px;
    white-space: normal;
    text-align: left;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.products-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--muted);
    border-bottom: 2px solid var(--border);
}

.products-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
}

.products-table input {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 0.875rem;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: var(--bg);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.icon-btn:hover {
    background: var(--accent-soft);
    color: var(--accent);
}

.icon-btn--danger:hover {
    background: var(--danger-soft);
    color: var(--danger);
}

.add-product-btn {
    margin-top: 16px;
    padding: 12px;
    border: 2px dashed var(--accent);
    background: var(--accent-soft);
    border-radius: 8px;
    color: var(--accent);
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.add-product-btn:hover {
    background: var(--accent);
    color: white;
}

.wizard-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
}

.wizard-progress {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 0.875rem;
}

.wizard-actions-footer {
    display: flex;
    gap: 12px;
}

.summary-card {
    background: linear-gradient(135deg, var(--accent), #2563eb);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.summary-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 16px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.summary-item {
    text-align: center;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.summary-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.collapsible-section {
    margin-top: 24px;
}

.collapsible-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--bg);
    border-radius: 8px;
    cursor: pointer;
    user-select: none;
    margin-bottom: 12px;
}

.collapsible-title {
    font-weight: 600;
    font-size: 0.875rem;
}

.collapsible-icon {
    transition: transform 0.2s ease;
}

.collapsible-section--collapsed .collapsible-icon {
    transform: rotate(-90deg);
}

.collapsible-content {
    display: grid;
    gap: 16px;
}

.collapsible-section--collapsed .collapsible-content {
    display: none;
}

@media (max-width: 768px) {
    .wizard-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .wizard-sidebar {
        position: static;
    }
    
    .wizard-steps {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 8px;
    }
    
    .step-item {
        min-width: 120px;
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .wizard-main {
        padding: 20px;
    }
    
    .form-grid--2,
    .form-grid--3 {
        grid-template-columns: 1fr;
    }
    
    .wizard-footer {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .wizard-actions-footer {
        justify-content: stretch;
    }
    
    .btn {
        flex: 1;
        justify-content: center;
    }
}

.autosave-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--success);
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    align-items: center;
    gap: 8px;
    z-index: 1000;
}

.autosave-indicator--show {
    display: flex;
}

.warning-message {
    background: var(--warning-soft);
    border: 1px solid var(--warning);
    color: #92400e;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}

.error-message {
    background: var(--danger-soft);
    border: 1px solid var(--danger);
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}
</style>

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

<script>
let currentStep = 1;
let productIndex = 1;
let autosaveTimer = null;
let formData = {};
let stepErrors = {}; // Хранилище ошибок по шагам

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadFromLocalStorage();
    initValidation();
    initCalculation();
    initAutosave();
    updateSummary();
    updateStepValidation();
});

// Навигация по шагам
function changeStep(direction) {
    const newStep = currentStep + direction;
    if (newStep < 1 || newStep > 4) return;
    
    // Сохраняем текущий шаг
    saveToLocalStorage();
    
    // Обновляем UI
    document.querySelectorAll('.wizard-step-content').forEach(el => {
        el.classList.remove('wizard-step-content--active');
    });
    document.querySelector(`[data-step="${newStep}"].wizard-step-content`).classList.add('wizard-step-content--active');
    
    document.querySelectorAll('.step-item').forEach(el => {
        el.classList.remove('step-item--active');
    });
    const newStepItem = document.querySelector(`.step-item[data-step="${newStep}"]`);
    newStepItem.classList.add('step-item--active');
    
    // Обновляем завершенные шаги
    for (let i = 1; i < newStep; i++) {
        const stepItem = document.querySelector(`.step-item[data-step="${i}"]`);
        stepItem.classList.add('step-item--completed');
        
        // Показываем ошибки если они есть
        if (stepErrors[i] && stepErrors[i].length > 0) {
            stepItem.classList.add('step-item--has-errors');
            const errorSummary = document.getElementById(`step${i}-errors`);
            errorSummary.textContent = `${stepErrors[i].length} ошибок`;
            errorSummary.classList.remove('step-validation-summary--hidden');
        }
    }
    
    for (let i = newStep; i <= 4; i++) {
        const stepItem = document.querySelector(`.step-item[data-step="${i}"]`);
        stepItem.classList.remove('step-item--completed');
        stepItem.classList.remove('step-item--has-errors');
        const errorSummary = document.getElementById(`step${i}-errors`);
        errorSummary.classList.add('step-validation-summary--hidden');
    }
    
    currentStep = newStep;
    updateStepButtons();
    updateSummary();
    updateStepValidation();
}

function updateStepButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.disabled = currentStep === 1;
    
    if (currentStep === 4) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-flex';
    } else {
        nextBtn.style.display = 'inline-flex';
        submitBtn.style.display = 'none';
    }
    
    document.getElementById('currentStep').textContent = currentStep;
}

// Обновление валидации текущего шага
function updateStepValidation() {
    const currentStepElement = document.querySelector(`.wizard-step-content--active`);
    const requiredFields = currentStepElement.querySelectorAll('[data-validation*="required"]');
    const errors = [];
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        const validation = field.dataset.validation;
        
        if (validation.includes('required') && !value) {
            errors.push('Обязательное поле не заполнено');
        } else if (validation.includes('email') && value && !isValidEmail(value)) {
            errors.push('Некорректный email');
        } else if (validation.includes('phone') && value && !isValidPhone(value)) {
            errors.push('Некорректный телефон');
        } else if (validation.includes('positive') && value && (parseFloat(value) <= 0)) {
            errors.push('Значение должно быть положительным');
        }
    });
    
    // Дополнительная проверка для шага 2 (товары)
    if (currentStep === 2) {
        const products = document.querySelectorAll('#productsTableBody tr');
        if (products.length === 0 || !hasValidProducts()) {
            errors.push('Добавьте хотя бы один товар с корректными данными');
        }
    }
    
    // Сохраняем ошибки шага
    stepErrors[currentStep] = errors;
    
    // Обновляем UI текущего шага
    const stepItem = document.querySelector(`.step-item[data-step="${currentStep}"]`);
    const errorSummary = document.getElementById(`step${currentStep}-errors`);
    
    if (errors.length > 0) {
        stepItem.classList.add('step-item--has-errors');
        errorSummary.textContent = `${errors.length} ошибок`;
        errorSummary.classList.remove('step-validation-summary--hidden');
    } else {
        stepItem.classList.remove('step-item--has-errors');
        errorSummary.classList.add('step-validation-summary--hidden');
    }
}

// Валидация
function initValidation() {
    document.querySelectorAll('[data-validation]').forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
            updateStepValidation();
        });
        
        field.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
                updateStepValidation();
            }
        });
    });
}

function validateField(field) {
    const validation = field.dataset.validation;
    const value = field.value.trim();
    const fieldName = field.dataset.field;
    let isValid = true;
    let errorMessage = '';
    
    // Удаляем предыдущие ошибки
    field.classList.remove('error', 'success');
    const existingError = field.parentNode.querySelector('.field-error');
    const existingSuccess = field.parentNode.querySelector('.field-success');
    if (existingError) existingError.remove();
    if (existingSuccess) existingSuccess.remove();
    
    if (validation.includes('required') && !value) {
        isValid = false;
        errorMessage = 'Это поле обязательно для заполнения';
    } else if (validation.includes('email') && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Введите корректный email';
    } else if (validation.includes('phone') && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Введите телефон в формате +37529XXXXXXX';
    } else if (validation.includes('positive') && value && (parseFloat(value) <= 0)) {
        isValid = false;
        errorMessage = 'Значение должно быть положительным';
    }
    
    if (!isValid) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = `❌ ${errorMessage}`;
        field.parentNode.appendChild(errorDiv);
    } else if (value) {
        field.classList.add('success');
        const successDiv = document.createElement('div');
        successDiv.className = 'field-success';
        successDiv.innerHTML = '✅';
        field.parentNode.appendChild(successDiv);
    }
    
    return isValid;
}

// Валидация только перед отправкой формы
function validateCurrentStep() {
    const errors = stepErrors[currentStep] || [];
    
    if (errors.length > 0) {
        showMessage('Есть ошибки в текущем шаге. Пожалуйста, исправьте их перед отправкой формы.', 'error');
        return false;
    }
    
    return true;
}

function hasValidProducts() {
    const products = document.querySelectorAll('#productsTableBody tr');
    for (let product of products) {
        const name = product.querySelector('input[name*="[product_name]"]').value.trim();
        const price = parseFloat(product.querySelector('input[name*="[price]"]').value);
        const quantity = parseInt(product.querySelector('input[name*="[quantity]"]').value);
        
        if (name && price > 0 && quantity > 0) {
            return true;
        }
    }
    return false;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^\+375\d{9}$/.test(phone);
}

// Работа с товарами
function addProduct() {
    const tbody = document.getElementById('productsTableBody');
    const row = document.createElement('tr');
    row.dataset.index = productIndex;
    
    row.innerHTML = `
        <td>
            <input type="text" name="OrderItem[${productIndex}][product_name]" 
                   placeholder="Введите название товара" 
                   data-validation="required"
                   data-field="product_name_${productIndex}">
        </td>
        <td>
            <input type="number" name="OrderItem[${productIndex}][quantity]" 
                   value="1" min="1" 
                   data-field="quantity_${productIndex}"
                   data-calc="quantity">
        </td>
        <td>
            <input type="number" name="OrderItem[${productIndex}][price]" 
                   step="0.01" placeholder="0.00" 
                   data-validation="required positive"
                   data-field="price_${productIndex}"
                   data-calc="price">
        </td>
        <td class="total-cell" data-total="${productIndex}">0.00</td>
        <td class="product-actions">
            <button type="button" class="icon-btn icon-btn--danger" onclick="removeProduct(${productIndex})" title="Удалить">
                🗑️
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    
    // Добавляем обработчики для нового поля
    row.querySelectorAll('input').forEach(input => {
        if (input.dataset.validation) {
            input.addEventListener('blur', function() { validateField(this); });
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
                if (this.dataset.calc) {
                    calculateProductTotal(productIndex);
                }
            });
        }
    });
    
    productIndex++;
    updateRemoveButtons();
}

function removeProduct(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (row) {
        row.remove();
        calculateProductsTotal();
        updateRemoveButtons();
        updateSummary();
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('#productsTableBody tr');
    document.querySelectorAll('#productsTableBody .icon-btn--danger').forEach((btn, index) => {
        btn.disabled = rows.length === 1;
    });
}

function calculateProductTotal(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (!row) return;
    
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
    const total = quantity * price;
    
    row.querySelector('.total-cell').textContent = total.toFixed(2);
    calculateProductsTotal();
}

function calculateProductsTotal() {
    let total = 0;
    document.querySelectorAll('#productsTableBody tr').forEach(row => {
        const totalCell = row.querySelector('.total-cell');
        total += parseFloat(totalCell.textContent) || 0;
    });
    
    document.getElementById('productsTotal').textContent = total.toFixed(2) + ' BYN';
    
    // Обновляем поле в форме
    const productPriceField = document.querySelector('[data-field="product_price"]');
    if (productPriceField) {
        productPriceField.value = total.toFixed(2);
    }
    
    calculateFinalTotal();
    updateSummary();
}

function calculateFinalTotal() {
    const productsTotal = parseFloat(document.getElementById('productsTotal').textContent) || 0;
    const logisticsPrice = parseFloat(document.querySelector('[data-field="logistics_price"]')?.value) || 0;
    const commissionPrice = parseFloat(document.querySelector('[data-field="commission_price"]')?.value) || 0;
    const deliveryCost = parseFloat(document.querySelector('[data-field="delivery_cost"]')?.value) || 0;
    
    const finalTotal = productsTotal + logisticsPrice + commissionPrice + deliveryCost;
    
    const totalAmountField = document.querySelector('[data-field="total_amount"]');
    if (totalAmountField) {
        totalAmountField.value = finalTotal.toFixed(2);
    }
}

function initCalculation() {
    document.querySelectorAll('[data-calc]').forEach(field => {
        field.addEventListener('input', function() {
            if (this.dataset.calc === 'logistics') {
                calculateLogisticsPrice();
            }
            calculateFinalTotal();
            updateSummary();
        });
    });
    
    // Обработчики для товаров
    document.querySelectorAll('#productsTableBody input[data-calc]').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            const index = row.dataset.index;
            calculateProductTotal(index);
        });
    });
}

function calculateLogisticsPrice() {
    const shipmentValue = parseFloat(document.querySelector('[data-field="shipment_value_cny"]')?.value) || 0;
    // Примерный курс юаня
    const cnyToBynRate = 0.39;
    const logisticsPrice = shipmentValue * cnyToBynRate;
    
    const logisticsField = document.querySelector('[data-field="logistics_price"]');
    if (logisticsField) {
        logisticsField.value = logisticsPrice.toFixed(2);
    }
}

// Сводка
function updateSummary() {
    const productsCount = document.querySelectorAll('#productsTableBody tr').length;
    const productsTotal = parseFloat(document.getElementById('productsTotal').textContent) || 0;
    const logisticsPrice = parseFloat(document.querySelector('[data-field="logistics_price"]')?.value) || 0;
    const finalTotal = parseFloat(document.querySelector('[data-field="total_amount"]')?.value) || 0;
    
    document.getElementById('summaryItems').textContent = productsCount;
    document.getElementById('summaryTotal').textContent = productsTotal.toFixed(2) + ' BYN';
    document.getElementById('summaryLogistics').textContent = logisticsPrice.toFixed(2) + ' BYN';
    document.getElementById('summaryFinal').textContent = finalTotal.toFixed(2) + ' BYN';
}

// Collapsible sections
function toggleCollapsible(header) {
    const section = header.closest('.collapsible-section');
    section.classList.toggle('collapsible-section--collapsed');
}

// Автосохранение
function initAutosave() {
    document.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('input', function() {
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(saveToLocalStorage, 1000);
        });
    });
}

function saveToLocalStorage() {
    const form = document.getElementById('orderWizardForm');
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (!data[key]) {
            data[key] = [];
        }
        data[key].push(value);
    }
    
    const saveData = {
        step: currentStep,
        data: data,
        timestamp: Date.now()
    };
    
    localStorage.setItem('orderWizardDraft', JSON.stringify(saveData));
    showAutosaveIndicator();
}

function loadFromLocalStorage() {
    const saved = localStorage.getItem('orderWizardDraft');
    if (!saved) return;
    
    try {
        const saveData = JSON.parse(saved);
        
        // Проверяем, не устарели ли данные (24 часа)
        if (Date.now() - saveData.timestamp > 24 * 60 * 60 * 1000) {
            localStorage.removeItem('orderWizardDraft');
            return;
        }
        
        // Восстанавливаем данные
        const data = saveData.data;
        for (let [key, values] of Object.entries(data)) {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = values[0];
            }
        }
        
        // Восстанавливаем шаг
        if (saveData.step && saveData.step !== currentStep) {
            changeStep(saveData.step - currentStep);
        }
        
        showMessage('Черновик заказа восстановлен', 'success');
    } catch (e) {
        console.error('Error loading draft:', e);
    }
}

function showAutosaveIndicator() {
    const indicator = document.getElementById('autosaveIndicator');
    indicator.classList.add('autosave-indicator--show');
    setTimeout(() => {
        indicator.classList.remove('autosave-indicator--show');
    }, 2000);
}

// Сообщения
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `${type}-message`;
    messageDiv.innerHTML = `
        <span>${type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️'}</span>
        <span>${message}</span>
    `;
    
    const wizardMain = document.querySelector('.wizard-main');
    wizardMain.insertBefore(messageDiv, wizardMain.firstChild);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Предотвращение ухода со страницы с несохраненными данными
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = 'У вас есть несохранённые данные. Вы уверены, что хотите уйти?';
        return e.returnValue;
    }
});

function hasUnsavedChanges() {
    const saved = localStorage.getItem('orderWizardDraft');
    if (!saved) return false;
    
    try {
        const saveData = JSON.parse(saved);
        return Date.now() - saveData.timestamp < 5 * 60 * 1000; // Если данные свежие (5 минут)
    } catch (e) {
        return false;
    }
}

// Отправка формы
function submitForm() {
    // Проверяем все шаги перед отправкой
    let allValid = true;
    let allErrors = [];
    
    for (let step = 1; step <= 4; step++) {
        if (stepErrors[step] && stepErrors[step].length > 0) {
            allValid = false;
            allErrors.push(`Шаг ${step}: ${stepErrors[step].join(', ')}`);
        }
    }
    
    if (!allValid) {
        showMessage('Пожалуйста, исправьте все ошибки во всех шагах перед созданием заказа:\n\n' + allErrors.join('\n\n'), 'error');
        return;
    }
    
    // Финальная проверка товаров
    if (!hasValidProducts()) {
        showMessage('Добавьте хотя бы один товар с корректными данными', 'error');
        return;
    }
    
    const form = document.getElementById('orderWizardForm');
    form.submit();
}

// Клик по шагам в сайдбаре
document.querySelectorAll('.step-item').forEach(step => {
    step.addEventListener('click', function() {
        const targetStep = parseInt(this.dataset.step);
        if (targetStep < currentStep || validateCurrentStep()) {
            changeStep(targetStep - currentStep);
        }
    });
});

// Инициализация кнопок
updateStepButtons();
</script>
