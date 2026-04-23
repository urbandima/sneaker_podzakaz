<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\admin\models\User as AdminUser;

$this->title = 'Заказ №' . ($model->order_number ?: $model->id);

// Подключаем стили для admin-order
$this->registerCssFile('@web/css/pages/admin-order.css');
$user = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$canEdit = $canEdit ?? (method_exists($user, 'isLogist') ? !$user->isLogist() : true);
$isEditing = $canEdit && !empty($editing);
$inputDisabled = ''; // Разрешаем inline-редактирование

$itemCount = count($model->orderItems ?? []);

$amoDealUrl = null;
if (!empty($model->amo_deal_id)) {
    $amoDealUrl = 'https://sneakerhead.amocrm.ru/leads/detail/' . (int)$model->amo_deal_id;
}

try {
    $logists = AdminUser::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all();
} catch (\Exception $e) {
    $logists = [];
}
?>

<div class="order-view">
    <!-- Хедер заказа -->
    <header class="order-header">
        <div class="order-header-top">
            <h1 class="order-number">Заказ №<?= Html::encode($model->order_number) ?></h1>
            <div class="order-actions-header">
                <?= Html::a('← Список заказов', ['/admin/order/index'], ['class' => 'btn']) ?>
                <?php if ($editing): ?>
                    <button type="submit" form="order-edit-form" class="btn btn--success">💾 Сохранить</button>
                    <?= Html::a('✕ Отмена', ['/admin/order/view', 'id' => $model->id], ['class' => 'btn btn--secondary']) ?>
                <?php elseif ($canEdit): ?>
                    <?= Html::a('✏️ Редактировать', ['/admin/order/update', 'id' => $model->id], ['class' => 'btn btn--primary']) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-header-middle">
            <div class="order-meta">
                <?php if ($editing): ?>
                    <select class="control-select" name="status" style="width: auto; margin-right: 8px;">
                        <?php foreach ($statuses as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                <?= Html::encode($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="status-badge status-badge--<?= $model->status ?>">
                        <?= Html::encode($statuses[$model->status] ?? $model->status) ?>
                    </div>
                <?php endif; ?>
                <div class="client-id">Клиент: #<?= $model->id ?></div>
                <?php if ($model->assigned_logist): ?>
                    <div style="font-size: 0.75rem; color: var(--muted);">
                        Менеджер: <?= Html::encode($model->assignedLogist->username ?? 'Неизвестно') ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="public-link-mini">
                <span>🔗</span>
                <input type="text" readonly value="<?= Url::to(['/order/view', 'token' => $model->token], true) ?>">
                <button onclick="copyPublicLink()">📋</button>
            </div>
        </div>
        
        <div class="order-header-bottom">
            <div class="order-dates">
                Создан: <?= Yii::$app->formatter->asDatetime($model->created_at, 'd MMM H:mm') ?>
                <?php if ($model->updated_at > $model->created_at): ?>
                    | Обновлён: <?= Yii::$app->formatter->asDatetime($model->updated_at, 'd MMM H:mm') ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Метрики -->
    <div class="metrics-cards">
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-value"><?= number_format($model->total_amount ?? 0, 2, ',', ' ') ?></div>
            <div class="metric-label">Сумма заказа</div>
            <?php if ($model->delivery_cost > 0): ?>
                <div class="metric-subtitle">+<?= number_format($model->delivery_cost ?? 0, 2, ',', ' ') ?> доставка</div>
            <?php endif; ?>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">📦</div>
            <div class="metric-value"><?= $itemCount ?></div>
            <div class="metric-label">Позиций</div>
            <div class="metric-subtitle">товаров</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">🎯</div>
            <div class="metric-value"><?= Html::encode($statuses[$model->status] ?? $model->status) ?></div>
            <div class="metric-label">Статус</div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="order-content">
        <!-- Левая колонка - основная информация -->
        <div class="content-main">
            <?php if ($editing): ?>
            <?= Html::beginForm(['/admin/order/update', 'id' => $model->id], 'post', ['class' => 'order-edit-form', 'id' => 'order-edit-form']) ?>
            <input type="hidden" name="editing" value="1">
            <?php endif; ?>
            <!-- Клиент и доставка -->
            <section class="section">
                <div class="section-header">
                    <span style="font-size: 1.5rem;">📞</span>
                    <h2 class="section-title">Клиент и доставка</h2>
                </div>
                <div class="section-content">
                    <div class="contact-row">
                        <div class="contact-item">
                            <span class="contact-label">ФИО:</span>
                            <?php if ($editing): ?>
                                <input type="text" class="control-input" name="client_name" value="<?= Html::encode($model->client_name) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="contact-value"><?= Html::encode($model->client_name) ?></span>
                            <?php endif; ?>
                            <span class="contact-status contact-status--filled">✓</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-label">Телефон:</span>
                            <?php if ($editing): ?>
                                <input type="tel" class="control-input" name="client_phone" value="<?= Html::encode($model->client_phone) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="contact-value"><?= Html::encode($model->client_phone) ?></span>
                            <?php endif; ?>
                            <span class="contact-status contact-status--filled">✓</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-label">Email:</span>
                            <?php if ($editing): ?>
                                <input type="email" class="control-input" name="client_email" value="<?= Html::encode($model->client_email) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="contact-value"><?= Html::encode($model->client_email ?: 'не указан') ?></span>
                            <?php endif; ?>
                            <span class="contact-status <?= $model->client_email ? 'contact-status--filled' : 'contact-status--empty' ?>">
                                <?= $model->client_email ? '✓' : '✕' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="delivery-info">
                        <div class="delivery-item">
                            <span class="delivery-label">Способ доставки:</span>
                            <?php if ($editing): ?>
                                <input type="text" class="control-input" name="delivery_method" value="<?= Html::encode($model->delivery_method) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->delivery_method ?: 'не указан') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="delivery-item">
                            <span class="delivery-label">Дата доставки:</span>
                            <?php if ($editing): ?>
                                <input type="date" class="control-input" name="delivery_date" value="<?= $model->delivery_date ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= $model->delivery_date ? Yii::$app->formatter->asDate($model->delivery_date, 'd MMM yyyy') : 'не указана' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="delivery-item">
                            <span class="delivery-label">Адрес:</span>
                            <?php if ($editing): ?>
                                <input type="text" class="control-input" name="full_address" value="<?= Html::encode($model->full_address) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->full_address ?: 'не указан') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($model->postal_code || $editing): ?>
                        <div class="delivery-item">
                            <span class="delivery-label">Индекс:</span>
                            <?php if ($editing): ?>
                                <input type="text" class="control-input" name="postal_code" value="<?= Html::encode($model->postal_code) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->postal_code) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($model->comment || $editing): ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--bg); border-radius: 8px;">
                        <strong>Комментарий:</strong> 
                        <?php if ($editing): ?>
                            <textarea class="control-textarea" name="comment" style="width: 100%; margin-top: 8px;" <?= $inputDisabled ?> placeholder="Добавьте комментарий к заказу..."><?= Html::encode($model->comment) ?></textarea>
                        <?php else: ?>
                            <?= Html::encode($model->comment ?: 'нет комментария') ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Товары -->
            <section class="section">
                <div class="section-header">
                    <span style="font-size: 1.5rem;">🛍️</span>
                    <h2 class="section-title">Состав заказа (<?= $itemCount ?> товар<?= $itemCount > 1 ? 'ов' : '' ?>, <?= number_format($model->total_amount ?? 0, 2, ',', ' ') ?> BYN)</h2>
                </div>
                <div class="section-content">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th style="width: 80px; text-align: center;">Кол.</th>
                                <th style="width: 100px; text-align: right;">Цена</th>
                                <th style="width: 100px; text-align: right;">Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $item): ?>
                            <tr>
                                <td><?= Html::encode($item->product_name) ?></td>
                                <td class="text-center"><?= $item->quantity ?></td>
                                <td class="text-right"><?= number_format($item->price ?? 0, 2, ',', ' ') ?></td>
                                <td class="text-right"><?= number_format($item->total ?? 0, 2, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if ($model->delivery_cost > 0): ?>
                            <tr class="products-summary">
                                <td colspan="3">Доставка</td>
                                <td class="text-right">+<?= number_format($model->delivery_cost ?? 0, 2, ',', ' ') ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="products-summary">
                                <td colspan="3" class="fw-600">ВСЕГО К ОПЛАТЕ</td>
                                <td class="products-total"><?= number_format(($model->total_amount ?? 0) + ($model->delivery_cost ?? 0), 2, ',', ' ') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Паспорт и логистика - аккордеон -->
            <div class="accordion-item accordion-item--open" id="passport-accordion">
                <div class="accordion-header" onclick="toggleAccordion('passport-accordion')">
                    <div class="accordion-title">
                        <span>📋</span>
                        <span>Получатель и паспорт</span>
                    </div>
                    <div class="accordion-icon">▼</div>
                </div>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <strong>ФИО получателя:</strong>
                                <?php if ($isEditing): ?>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px;">
                                        <input type="text" name="recipient_last_name" value="<?= Html::encode($model->recipient_last_name) ?>" placeholder="Фамилия" <?= $inputDisabled ?>>
                                        <input type="text" name="recipient_first_name" value="<?= Html::encode($model->recipient_first_name) ?>" placeholder="Имя" <?= $inputDisabled ?>>
                                        <input type="text" name="recipient_middle_name" value="<?= Html::encode($model->recipient_middle_name) ?>" placeholder="Отчество" <?= $inputDisabled ?>>
                                    </div>
                                <?php else: ?>
                                    <?= Html::encode($model->recipient_first_name . ' ' . $model->recipient_last_name . ' ' . $model->recipient_middle_name ?: 'не указано') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Паспорт:</strong>
                                <?php if ($isEditing): ?>
                                    <div style="display: grid; grid-template-columns: 80px 100px 1fr; gap: 8px; margin-top: 8px;">
                                        <input type="text" name="passport_series" value="<?= Html::encode($model->passport_series) ?>" placeholder="Серия" <?= $inputDisabled ?>>
                                        <input type="text" name="passport_number" value="<?= Html::encode($model->passport_number) ?>" placeholder="Номер" <?= $inputDisabled ?>>
                                        <input type="date" name="passport_issue_date" value="<?= $model->passport_issue_date ?>" placeholder="Дата выдачи" <?= $inputDisabled ?>>
                                    </div>
                                <?php else: ?>
                                    <?php if ($model->passport_series || $model->passport_number): ?>
                                        <?= Html::encode($model->passport_series . ' ' . $model->passport_number) ?>
                                        <?php if ($model->passport_issue_date): ?>
                                            выдан <?= Yii::$app->formatter->asDate($model->passport_issue_date, 'd MMM yyyy') ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        не указано
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Дата рождения:</strong>
                                <?php if ($isEditing): ?>
                                    <input type="date" name="birth_date" value="<?= $model->birth_date ?>" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= $model->birth_date ? Yii::$app->formatter->asDate($model->birth_date, 'd MMM yyyy') : 'не указано' ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>ИНН (для РФ):</strong>
                                <?php if ($isEditing): ?>
                                    <input type="text" name="inn" value="<?= Html::encode($model->inn) ?>" placeholder="ИНН" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= Html::encode($model->inn ?: 'не указано') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item" id="logistics-accordion">
                <div class="accordion-header" onclick="toggleAccordion('logistics-accordion')">
                    <div class="accordion-title">
                        <span>🚚</span>
                        <span>Логистика и ссылки</span>
                    </div>
                    <div class="accordion-icon">▼</div>
                </div>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <strong>Трек-номер (Китай):</strong>
                                <?php if ($isEditing): ?>
                                    <input type="text" name="china_track_number" value="<?= Html::encode($model->china_track_number) ?>" placeholder="Трек-номер" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= Html::encode($model->china_track_number ?: 'не указано') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Номер МС:</strong>
                                <?php if ($isEditing): ?>
                                    <input type="text" name="ms_number" value="<?= Html::encode($model->ms_number) ?>" placeholder="Номер МС" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= Html::encode($model->ms_number ?: 'не указано') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Стоимость поставки (CNY):</strong>
                                <?php if ($isEditing): ?>
                                    <input type="number" name="shipment_value_cny" value="<?= $model->shipment_value_cny ?>" placeholder="0.00" step="0.01" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= $model->shipment_value_cny ? number_format($model->shipment_value_cny, 2, ',', ' ') : 'не указано' ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Описание для таможни:</strong>
                                <?php if ($isEditing): ?>
                                    <textarea name="customs_description" placeholder="Описание товара для таможни" style="margin-top: 8px; min-height: 60px;" <?= $inputDisabled ?>><?= Html::encode($model->customs_description) ?></textarea>
                                <?php else: ?>
                                    <?= Html::encode($model->customs_description ?: 'не указано') ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Количество (таможня):</strong>
                                <?php if ($isEditing): ?>
                                    <input type="number" name="item_quantity" value="<?= $model->item_quantity ?>" placeholder="0" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= $model->item_quantity ?: 'не указано' ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Цена (таможня, CNY):</strong>
                                <?php if ($isEditing): ?>
                                    <input type="number" name="item_price_cny" value="<?= $model->item_price_cny ?>" placeholder="0.00" step="0.01" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?= $model->item_price_cny ? number_format($model->item_price_cny, 2, ',', ' ') : 'не указано' ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Ссылка на товар:</strong>
                                <?php if ($isEditing): ?>
                                    <input type="url" name="product_link" value="<?= Html::encode($model->product_link) ?>" placeholder="https://..." class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?php if ($model->product_link): ?>
                                        <?= Html::a(Html::encode($model->product_link), $model->product_link, ['target' => '_blank']) ?>
                                    <?php else: ?>
                                        не указана
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Заказ Sneakerhead:</strong>
                                <?php if ($isEditing): ?>
                                    <input type="text" name="sneakerhead_order_link" value="<?= Html::encode($model->sneakerhead_order_link) ?>" placeholder="Ссылка на заказ" class="mt-2" <?= $inputDisabled ?>>
                                <?php else: ?>
                                    <?php if ($model->sneakerhead_order_link): ?>
                                        <?= Html::a(Html::encode($model->sneakerhead_order_link), $model->sneakerhead_order_link, ['target' => '_blank']) ?>
                                    <?php else: ?>
                                        не указан
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Сделка AmoCRM:</strong>
                                <?php if ($amoDealUrl): ?>
                                    <?= Html::a('Открыть в AmoCRM', $amoDealUrl, ['target' => '_blank']) ?>
                                <?php else: ?>
                                    не указана
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Правая колонка - управление -->
        <div class="content-sidebar">
            <!-- Управление заказом -->
            <div class="control-section">
                <div class="control-header">🎯 УПРАВЛЕНИЕ ЗАКАЗОМ</div>
                <div class="control-content">
                    <div class="control-group">
                        <label class="control-label">Статус:</label>
                        <select class="control-select" <?= $inputDisabled ?>>
                            <?php foreach ($statuses as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                    <?= Html::encode($value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">Комментарий:</label>
                        <textarea class="control-textarea" <?= $inputDisabled ?> placeholder="Добавьте комментарий к заказу..."><?= Html::encode($model->comment) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Логист -->
            <?php if ($user->isAdmin()): ?>
            <div class="control-section">
                <div class="control-header">👤 Логист</div>
                <div class="control-content">
                    <div class="control-group">
                        <label class="control-label">Назначить логиста:</label>
                        <select class="control-select" <?= $inputDisabled ?>>
                            <option value="">Не назначен</option>
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

            <!-- Таможня:ДП -->
            <div class="control-section">
                <div class="control-header flex-between">
                    <span>📦 Таможня:ДП</span>
                    <?php
                    $dpShipmentId = $model->dp_shipment_id ?? null;
                    $dpStatus     = $model->dp_status ?? null;
                    $dpPassportOk = $model->isPassportComplete();
                    if ($dpShipmentId) {
                        $badgeStyle = 'background:#008060;color:#fff;font-size:11px;padding:2px 8px;border-radius:10px';
                        $badgeText  = 'Отправлен';
                        if (in_array($dpStatus, ['problem','customs_hold','returned'])) { $badgeStyle = 'background:#dc3545;color:#fff;font-size:11px;padding:2px 8px;border-radius:10px'; $badgeText = 'Ошибка'; }
                        elseif ($dpStatus === 'delivered') { $badgeText = 'Доставлен'; }
                    } else { $badgeStyle = 'background:#6b7280;color:#fff;font-size:11px;padding:2px 8px;border-radius:10px'; $badgeText = 'Не отправлен'; }
                    ?>
                    <span style="<?= $badgeStyle ?>"><?= $badgeText ?></span>
                </div>
                <div class="control-content">
                    <div style="font-size:0.8125rem;margin-bottom:0.75rem">
                        <div><strong>Паспорт:</strong>
                            <?php if ($dpPassportOk): ?>
                                <span style="color:#008060">✓ Заполнен<?= !empty($model->passport_validated) ? ' <span style="background:#008060;color:#fff;font-size:10px;padding:1px 6px;border-radius:8px">Подтверждён ДП</span>' : '' ?></span>
                            <?php else: ?>
                                <span style="color:#dc3545">✗ Не заполнен</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($dpShipmentId): ?>
                        <div><strong>ID ДП:</strong> <code><?= Html::encode($dpShipmentId) ?></code></div>
                        <?php endif; ?>
                        <?php if (!empty($model->dp_track_number)): ?>
                        <div><strong>Трек:</strong> <code><?= Html::encode($model->dp_track_number) ?></code></div>
                        <?php endif; ?>
                        <?php if ($dpStatus): ?>
                        <?php $dpLabels = ['created'=>'Создан','accepted'=>'Принят','in_transit'=>'В пути','customs_clearance'=>'Таможня','delivered'=>'Доставлен','returned'=>'Возврат','problem'=>'Проблема']; ?>
                        <div><strong>Статус:</strong> <?= Html::encode($dpLabels[$dpStatus] ?? $dpStatus) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($model->estimated_delivery_date)): ?>
                        <div><strong>Ожид. доставка:</strong> <?= Html::encode(date('d.m.Y', strtotime($model->estimated_delivery_date))) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:0.5rem">
                        <?php if (!$dpShipmentId): ?>
                        <button class="btn btn-primary btn-sm" onclick="sendToDP(<?= $model->id ?>)"
                                <?= !$dpPassportOk ? 'disabled title="Заполните паспортные данные"' : '' ?>>
                            <i class="bi bi-send"></i> Отправить в Таможня:ДП
                        </button>
                        <?php else: ?>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshDPStatus(<?= $model->id ?>)">
                            <i class="bi bi-arrow-clockwise"></i> Обновить статус
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="retryDP(<?= $model->id ?>)">
                            <i class="bi bi-arrow-repeat"></i> Повторить
                        </button>
                        <?php endif; ?>
                    </div>
                    <span id="dp-action-result" style="font-size:0.8125rem;display:block"></span>
                </div>
            </div>

            <!-- Финансы -->
            <div class="control-section">
                <div class="control-header">💰 ФИНАНСЫ</div>
                <div class="control-content">
                    <table class="finance-table">
                        <tr>
                            <td>Товары:</td>
                            <td><?= number_format($model->product_price ?? 0, 2, ',', ' ') ?> BYN</td>
                        </tr>
                        <tr>
                            <td>Логистика:</td>
                            <td><?= number_format($model->logistics_price ?? 0, 2, ',', ' ') ?> BYN</td>
                        </tr>
                        <tr>
                            <td>Комиссия:</td>
                            <td><?= number_format($model->commission_price ?? 0, 2, ',', ' ') ?> BYN</td>
                        </tr>
                        <?php if ($model->delivery_cost > 0): ?>
                        <tr>
                            <td>Доставка:</td>
                            <td><?= number_format($model->delivery_cost, 2, ',', ' ') ?> BYN</td>
                        </tr>
                        <?php endif; ?>
                        <tr class="finance-total">
                            <td>ИТОГО:</td>
                            <td><?= number_format(($model->total_amount ?? 0) + ($model->delivery_cost ?? 0), 2, ',', ' ') ?> BYN</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($editing): ?>
<?= Html::endForm() ?>
<?php endif; ?>

<?php
$_dpSendUrl   = \yii\helpers\Url::to(['/admin/order/send-to-dp', 'id' => $model->id]);
$_dpStatusUrl = \yii\helpers\Url::to(['/admin/order/dp-status',  'id' => $model->id]);
$_dpRetryUrl  = \yii\helpers\Url::to(['/admin/order/retry-dp',   'id' => $model->id]);
$_csrf        = Yii::$app->request->csrfToken;
$this->registerJs(
    'function sendToDP(id){'
    . 'var btn=event.target.closest("button");btn.disabled=true;btn.innerHTML="<i class=\'bi bi-hourglass-split\'></i> Отправка...";'
    . 'var res=document.getElementById("dp-action-result");'
    . 'fetch("' . $_dpSendUrl . '",{method:"POST",headers:{"X-CSRF-Token":"' . $_csrf . '","Content-Type":"application/json"}})'
    . '.then(function(r){return r.json();})'
    . '.then(function(d){if(d.success){res.style.color="#008060";res.textContent="\u2713 "+d.message;setTimeout(function(){location.reload();},1500);}else{res.style.color="#dc3545";res.textContent="\u2717 "+d.message;btn.disabled=false;btn.innerHTML="<i class=\'bi bi-send\'></i> Отправить в Таможня:ДП";}})'
    . '.catch(function(){res.style.color="#dc3545";res.textContent="\u2717 Ошибка сети";btn.disabled=false;});}'
    . 'function refreshDPStatus(id){'
    . 'var res=document.getElementById("dp-action-result");res.style.color="#6b7280";res.textContent="Обновляем...";'
    . 'fetch("' . $_dpStatusUrl . '",{method:"POST",headers:{"X-CSRF-Token":"' . $_csrf . '"}})'
    . '.then(function(r){return r.json();})'
    . '.then(function(d){if(d.success){res.style.color="#008060";res.textContent="\u2713 "+d.message;setTimeout(function(){location.reload();},1000);}else{res.style.color="#dc3545";res.textContent="\u2717 "+d.message;}})'
    . '.catch(function(){res.style.color="#dc3545";res.textContent="\u2717 Ошибка сети";});}'
    . 'function retryDP(id){'
    . 'var res=document.getElementById("dp-action-result");res.style.color="#6b7280";res.textContent="Повторная отправка...";'
    . 'fetch("' . $_dpRetryUrl . '",{method:"POST",headers:{"X-CSRF-Token":"' . $_csrf . '"}})'
    . '.then(function(r){return r.json();})'
    . '.then(function(d){if(d.success){res.style.color="#008060";res.textContent="\u2713 "+d.message;setTimeout(function(){location.reload();},1500);}else{res.style.color="#dc3545";res.textContent="\u2717 "+d.message;}})'
    . '.catch(function(){res.style.color="#dc3545";res.textContent="\u2717 Ошибка сети";});}'
);
?>

