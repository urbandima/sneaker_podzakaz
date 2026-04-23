<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . $model->order_number;
$user = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$canEdit = !$user->isLogist();
$isEditing = $canEdit && !empty($editing);
$inputDisabled = ($canEdit && $isEditing) ? '' : 'disabled';
$logists = $user->isAdmin()
    ? (function() {
        try {
            return \app\backend\modules\admin\models\User::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all();
        } catch (\Exception $e) {
            return [];
        }
    })()
    : [];
$itemCount = count($model->orderItems);
$amoDealId = ($model->source === 'amocrm' && $model->source_id) ? $model->source_id : null;
$amoBase = Yii::$app->params['amocrmDealBaseUrl'] ?? 'https://www.amocrm.ru/leads/detail';
$amoDealUrl = $amoDealId ? rtrim($amoBase, '/') . '/' . $amoDealId : null;
?>


<div class="order-view">
    <header class="view-header">
        <div class="view-title-section">
            <h1 class="view-title"><?= Html::encode($this->title) ?></h1>
            <div class="view-meta">
                <span>📅 Создан: <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?></span>
                <span>🔄 Обновлён: <?= Yii::$app->formatter->asDatetime($model->updated_at, 'short') ?></span>
                <span>👤 <?= Html::encode($model->client_name ?: 'Клиент не указан') ?></span>
            </div>
        </div>
        <div class="view-actions">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="btn btn--secondary">
                ← Список заказов
            </a>
            <?php if ($canEdit): ?>
                <?php if ($isEditing): ?>
                    <button type="submit" form="orderUpdateForm" class="btn btn--success">
                        💾 Сохранить изменения
                    </button>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $model->id]) ?>" class="btn btn--secondary">
                        ❌ Отменить
                    </a>
                <?php else: ?>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $model->id, 'editing' => 1]) ?>" class="btn btn--primary">
                        ✏️ Редактировать
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>

    <section class="metrics-grid">
        <div class="metric-card metric-card--accent">
            <div class="metric-label">💰 Сумма заказа</div>
            <div class="metric-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</div>
            <div class="metric-note">Доставка: <?= Yii::$app->formatter->asDecimal((float)$model->delivery_cost, 2) ?> BYN</div>
        </div>
        <div class="metric-card metric-card--success">
            <div class="metric-label">📦 Позиции</div>
            <div class="metric-value"><?= $itemCount ?></div>
            <div class="metric-note"><?= Html::encode($model->source ?? 'Источник не указан') ?></div>
        </div>
        <div class="metric-card metric-card--warning">
            <div class="metric-label">🎯 Статус</div>
            <div class="metric-value">
                <span class="status-badge"><?= Html::encode($model->getStatusLabel()) ?></span>
            </div>
            <div class="metric-note"><?= Html::encode($model->comment ?: 'Комментариев нет') ?></div>
        </div>
        <?php if ($amoDealId): ?>
        <div class="metric-card amo-card">
            <div class="metric-label">🔗 amoCRM</div>
            <div class="metric-value">#<?= Html::encode($amoDealId) ?></div>
            <div class="metric-note">
                <?php if ($amoDealUrl): ?>
                    <a href="<?= Html::encode($amoDealUrl) ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 0.75rem;">
                        Открыть сделку →
                    </a>
                <?php else: ?>
                    Сделка привязана
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <div class="view-container">
        <main class="view-main">
            <?php if ($canEdit && $isEditing): ?>
                <div class="edit-mode-indicator">
                    <span>⚠️</span>
                    <span>Режим редактирования - внесите изменения и нажмите "Сохранить изменения"</span>
                </div>
                <form id="orderUpdateForm" method="post" action="<?= Url::to(['/admin/order/update', 'id' => $model->id]) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            📞 Клиент и доставка
                        </h2>
                        <div class="panel-subtitle">Контактные данные и параметры получения</div>
                    </div>
                </div>
                <div class="info-grid info-grid--3">
                    <div class="info-item">
                        <div class="info-label">ФИО клиента</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[client_name]" value="<?= Html::encode($model->client_name) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_name ?: 'Не указано') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[client_phone]" value="<?= Html::encode($model->client_phone) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_phone ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="email" name="Order[client_email]" value="<?= Html::encode($model->client_email) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_email ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Способ доставки</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[delivery_method]" value="<?= Html::encode($model->delivery_method) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_method ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Дата доставки</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="date" name="Order[delivery_date]" value="<?= Html::encode($model->delivery_date) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_date ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Страна</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[delivery_country]" value="<?= Html::encode($model->delivery_country) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_country ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Город</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[city]" value="<?= Html::encode($model->city) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->city ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Область</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[region]" value="<?= Html::encode($model->region) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->region ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Почтовый индекс</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[postal_code]" value="<?= Html::encode($model->postal_code) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->postal_code ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-grid info-grid--2 mt-20px">
                    <div class="info-item">
                        <div class="info-label">Полный адрес</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <textarea name="Order[full_address]" rows="3"><?= Html::encode($model->full_address) ?></textarea>
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->full_address ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Комментарий менеджера</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <textarea name="Order[comment]" rows="3"><?= Html::encode($model->comment) ?></textarea>
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->comment ?: 'Нет комментариев') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🛍️ Состав заказа (<?= $itemCount ?>)
                        </h2>
                        <div class="panel-subtitle">Позиции и стоимости</div>
                    </div>
                </div>
                <?php if ($isEditing): ?>
                    <?= $this->render('_order_items', [
                        'orderItems' => $model->orderItems,
                    ]) ?>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Название товара</th>
                                <th>Кол-во</th>
                                <th>Цена, BYN</th>
                                <th>Сумма, BYN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $item): ?>
                                <tr>
                                    <td><?= Html::encode($item->product_name) ?></td>
                                    <td><?= $item->quantity ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->total, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Итого</td>
                                <td><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </section>

            <div class="collapsible-section">
                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                    <div class="collapsible-title">📋 Паспортные данные и логистика</div>
                    <div class="collapsible-icon">▼</div>
                </div>
                <div class="collapsible-content">
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">
                                    📋 Получатель и паспорт
                                </h2>
                                <div class="panel-subtitle">Данные для таможенного оформления</div>
                            </div>
                        </div>
                        <div class="info-grid info-grid--3">
                            <?php foreach ([
                                'recipient_last_name' => 'Фамилия',
                                'recipient_first_name' => 'Имя',
                                'recipient_middle_name' => 'Отчество',
                                'passport_series' => 'Серия паспорта',
                                'passport_number' => 'Номер паспорта',
                                'passport_issue_date' => 'Дата выдачи',
                                'birth_date' => 'Дата рождения',
                                'inn' => 'ИНН',
                            ] as $field => $label): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= Html::encode($label) ?></div>
                                    <?php if ($isEditing): ?>
                                        <div class="form-field">
                                            <input type="<?= $field === 'passport_issue_date' || $field === 'birth_date' ? 'date' : 'text' ?>" 
                                                   name="Order[<?= $field ?>]" 
                                                   value="<?= Html::encode($model->$field) ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="info-value"><?= Html::encode($model->$field ?: 'Не указано') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">
                                    🚚 Логистика и ссылки
                                </h2>
                                <div class="panel-subtitle">Трек-номера и стоимость доставки</div>
                            </div>
                        </div>
                        <div class="info-grid info-grid--3">
                            <?php foreach ([
                                'china_track_number' => 'Китайский трек',
                                'ms_number' => '№ МС',
                                'dobropost_tariff' => 'DobroПост тариф',
                                'shipment_value_cny' => 'Ценность (¥)',
                                'item_quantity' => 'Кол-во товаров',
                                'item_price_cny' => 'Цена за ед. (¥)',
                            ] as $field => $label): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= Html::encode($label) ?></div>
                                    <?php if ($isEditing): ?>
                                        <div class="form-field">
                                            <input type="<?= in_array($field, ['shipment_value_cny', 'item_price_cny'], true) ? 'number' : 'text' ?>" 
                                                   name="Order[<?= $field ?>]" 
                                                   value="<?= Html::encode($model->$field) ?>"
                                                   step="<?= in_array($field, ['shipment_value_cny', 'item_price_cny'], true) ? '0.01' : '' ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="info-value"><?= Html::encode($model->$field ?: 'Не указано') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="info-grid info-grid--2 mt-20px">
                            <div class="info-item">
                                <div class="info-label">Ссылка на товар</div>
                                <?php if ($isEditing): ?>
                                    <div class="form-field">
                                        <input type="url" name="Order[product_link]" value="<?= Html::encode($model->product_link) ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="info-value">
                                        <?php if ($model->product_link): ?>
                                            <a href="<?= Html::encode($model->product_link) ?>" target="_blank" class="info-value--link">
                                                <?= Html::encode($model->product_link) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="info-value--empty">Не указана</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Ссылка Sneakerhead</div>
                                <?php if ($isEditing): ?>
                                    <div class="form-field">
                                        <input type="url" name="Order[sneakerhead_order_link]" value="<?= Html::encode($model->sneakerhead_order_link) ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="info-value">
                                        <?php if ($model->sneakerhead_order_link): ?>
                                            <a href="<?= Html::encode($model->sneakerhead_order_link) ?>" target="_blank" class="info-value--link">
                                                <?= Html::encode($model->sneakerhead_order_link) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="info-value--empty">Не указана</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item mt-20px">
                            <div class="info-label">Описание для таможни</div>
                            <?php if ($isEditing): ?>
                                <div class="form-field">
                                    <textarea name="Order[customs_description]" rows="3"><?= Html::encode($model->customs_description) ?></textarea>
                                </div>
                            <?php else: ?>
                                <div class="info-value"><?= Html::encode($model->customs_description ?: 'Не указано') ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <?php if ($canEdit && $isEditing): ?>
                </form>
            <?php endif; ?>
        </main>

        <aside class="view-sidebar">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🎯 Статус заказа
                        </h2>
                        <div class="panel-subtitle"><?= Html::encode($model->getStatusLabel()) ?></div>
                    </div>
                </div>
                <?php if ($canEdit): ?>
                    <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        <div class="form-field">
                            <label>Изменить статус</label>
                            <select name="status">
                                <?php
                                $statusList = $user->isLogist()
                                    ? Yii::$app->settings->getLogistStatuses()
                                    : $statuses;
                                foreach ($statusList as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                        <?= Html::encode($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Комментарий к изменению</label>
                            <textarea name="comment" rows="3" placeholder="Опционально..."></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 12px;">
                            🔄 Сохранить статус
                        </button>
                    </form>
                <?php else: ?>
                    <div class="info-value" style="color: var(--muted); font-size: 0.875rem;">
                        У вас нет прав для изменения статуса.
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            💰 Финансы
                        </h2>
                        <div class="panel-subtitle">Основные суммы заказа</div>
                    </div>
                </div>
                <div class="finance-list">
                    <div class="finance-item">
                        <span class="finance-label">Товары</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->product_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Логистика</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->logistics_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Комиссия</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->commission_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Доставка</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->delivery_cost, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Итого к оплате</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</span>
                    </div>
                </div>
            </section>

            <?php if ($user->isAdmin()): ?>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            👤 Ответственный логист
                        </h2>
                        <div class="panel-subtitle"><?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></div>
                    </div>
                </div>
                <form method="post" action="<?= Url::to(['/admin/order/assign-logist', 'id' => $model->id]) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="form-field">
                        <label>Выберите логиста</label>
                        <select name="logist_id">
                            <option value="">—</option>
                            <?php foreach ($logists as $logist): ?>
                                <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                    <?= Html::encode($logist->username) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--secondary" style="width: 100%; margin-top: 12px;">
                        💾 Сохранить
                    </button>
                </form>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🔗 Публичная ссылка
                        </h2>
                        <div class="panel-subtitle">Для клиента</div>
                    </div>
                </div>
                <div class="public-link">
                    <input type="text" value="<?= Html::encode($model->getPublicUrl()) ?>" id="publicLink" readonly>
                    <button type="button" class="copy-btn" onclick="copyLink()">📋 Копировать</button>
                </div>
            </section>

            <?php if ($model->history): ?>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            📜 История изменений
                        </h2>
                        <div class="panel-subtitle"><?= count($model->history) ?> записей</div>
                    </div>
                </div>
                <div class="timeline">
                    <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-title"><?= Html::encode($history->getNewStatusLabel()) ?></div>
                                <div class="timeline-meta">
                                    <?= Yii::$app->formatter->asDatetime($history->created_at, 'short') ?>
                                    <?php if ($history->changer): ?> • <?= Html::encode($history->changer->username) ?><?php endif; ?>
                                </div>
                                <?php if ($history->comment): ?>
                                    <div class="timeline-comment"><?= Html::encode($history->comment) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</div>

<div class="save-indicator" id="saveIndicator">
    <span>✅</span>
    <span>Сохранено</span>
</div>

<?php
// PHP-dependent JS: .js-flag handler (uses CSRF and model URL)
$_csrfParam = Yii::$app->request->csrfParam;
$_csrfToken = Yii::$app->request->csrfToken;
$_updateFieldUrl = \yii\helpers\Url::to(['/admin/order/update-field', 'id' => $model->id]);
$this->registerJs(
    'document.querySelectorAll(".js-flag").forEach(function(flag) {'
    . '    flag.addEventListener("change", function() {'
    . '        var field = this.dataset.field; var value = this.checked ? 1 : 0;'
    . '        var fd = new FormData();'
    . '        fd.append("field", field); fd.append("value", value);'
    . '        fd.append("' . $_csrfParam . '", "' . $_csrfToken . '");'
    . '        fetch("' . $_updateFieldUrl . '", {method:"POST",body:fd})'
    . '            .then(function(r){return r.json();})'
    . '            .then(function(data){if(data.success && window.showSaveIndicator){showSaveIndicator("Флаг сохранён");}});'
    . '    });'
    . '});',
    \yii\web\View::POS_END
);
?>
