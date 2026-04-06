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


<div class="order-console">
    <header class="order-console__header">
        <div>
            <h1 class="order-console__title"><?= Html::encode($this->title) ?></h1>
            <div class="order-console__meta">
                Создан: <?= Yii::$app->formatter->asDatetime($model->created_at) ?> •
                Обновлён: <?= Yii::$app->formatter->asDatetime($model->updated_at) ?>
            </div>
        </div>
        <div class="order-console__actions">
            <a class="order-btn" href="<?= Url::to(['/admin/order/index']) ?>">
                ← Список заказов
            </a>
            <?php if ($canEdit): ?>
                <button type="submit" form="orderUpdateForm" class="order-btn order-btn--primary">
                    Сохранить карточку
                </button>
            <?php endif; ?>
        </div>
    </header>

    <section class="order-metrics">
        <div class="order-metric">
            <div class="order-metric__label">Сумма заказа</div>
            <div class="order-metric__value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</div>
            <div class="order-metric__note">Доставка: <?= Yii::$app->formatter->asDecimal((float)$model->delivery_cost, 2) ?> BYN</div>
        </div>
        <div class="order-metric">
            <div class="order-metric__label">Позиции</div>
            <div class="order-metric__value"><?= $itemCount ?></div>
            <div class="order-metric__note"><?= Html::encode($model->source ?? 'Источник не указан') ?></div>
        </div>
        <div class="order-metric">
            <div class="order-metric__label">Статус</div>
            <div class="order-metric__value">
                <span class="status-badge"><?= Html::encode($model->getStatusLabel()) ?></span>
            </div>
            <div class="order-metric__note"><?= Html::encode($model->comment ?: 'Комментариев нет') ?></div>
        </div>
        <?php if ($amoDealId): ?>
        <div class="order-metric">
            <div class="order-metric__label">amoCRM</div>
            <div class="order-metric__value">#<?= Html::encode($amoDealId) ?></div>
            <?php if ($amoDealUrl): ?>
                <div class="order-metric__note">
                    <a href="<?= Html::encode($amoDealUrl) ?>" target="_blank" class="order-btn" style="padding:4px 10px;font-weight:500;">
                        Открыть сделку
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <div class="order-body">
        <div class="order-primary">
            <?php if ($canEdit && $isEditing): ?>
            <form id="orderUpdateForm" method="post" action="<?= Url::to(['/admin/order/update', 'id' => $model->id]) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <?php endif; ?>

                <section class="panel">
                    <div class="panel__header">
                        <div class="panel__title">Клиент и доставка</div>
                        <div class="panel__hint">Контактные данные + параметры получения</div>
                    </div>
                    <div class="form-grid form-grid--3">
                        <div class="form-field">
                            <label>ФИО клиента</label>
                            <input type="text" name="Order[client_name]" value="<?= Html::encode($model->client_name) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Телефон</label>
                            <input type="text" name="Order[client_phone]" value="<?= Html::encode($model->client_phone) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Email</label>
                            <input type="email" name="Order[client_email]" value="<?= Html::encode($model->client_email) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Способ доставки</label>
                            <input type="text" name="Order[delivery_method]" value="<?= Html::encode($model->delivery_method) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Срок / дата доставки</label>
                            <input type="text" name="Order[delivery_date]" value="<?= Html::encode($model->delivery_date) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Страна</label>
                            <input type="text" name="Order[delivery_country]" value="<?= Html::encode($model->delivery_country) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Город</label>
                            <input type="text" name="Order[city]" value="<?= Html::encode($model->city) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Область</label>
                            <input type="text" name="Order[region]" value="<?= Html::encode($model->region) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Почтовый индекс</label>
                            <input type="text" name="Order[postal_code]" value="<?= Html::encode($model->postal_code) ?>" <?= $inputDisabled ?>>
                        </div>
                    </div>
                    <div class="form-grid form-grid--2" style="margin-top:12px;">
                        <div class="form-field">
                            <label>Полный адрес</label>
                            <textarea name="Order[full_address]" <?= $inputDisabled ?>><?= Html::encode($model->full_address) ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Комментарий менеджера</label>
                            <textarea name="Order[comment]" <?= $inputDisabled ?>><?= Html::encode($model->comment) ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__header">
                        <div class="panel__title">Получатель и паспорт</div>
                        <div class="panel__hint">Для таможенного оформления</div>
                    </div>
                    <div class="form-grid form-grid--3">
                        <div class="form-field">
                            <label>Фамилия</label>
                            <input type="text" name="Order[recipient_last_name]" value="<?= Html::encode($model->recipient_last_name) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Имя</label>
                            <input type="text" name="Order[recipient_first_name]" value="<?= Html::encode($model->recipient_first_name) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Отчество</label>
                            <input type="text" name="Order[recipient_middle_name]" value="<?= Html::encode($model->recipient_middle_name) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Серия паспорта</label>
                            <input type="text" name="Order[passport_series]" value="<?= Html::encode($model->passport_series) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Номер паспорта</label>
                            <input type="text" name="Order[passport_number]" value="<?= Html::encode($model->passport_number) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Дата выдачи</label>
                            <input type="date" name="Order[passport_issue_date]" value="<?= Html::encode($model->passport_issue_date) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Дата рождения</label>
                            <input type="date" name="Order[birth_date]" value="<?= Html::encode($model->birth_date) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>ИНН</label>
                            <input type="text" name="Order[inn]" value="<?= Html::encode($model->inn) ?>" <?= $inputDisabled ?>>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__header">
                        <div class="panel__title">Поставка и товар</div>
                        <div class="panel__hint">Логистика, себестоимость и ссылки</div>
                    </div>
                    <div class="form-grid form-grid--3">
                        <div class="form-field">
                            <label>Китайский трек</label>
                            <input type="text" name="Order[china_track_number]" value="<?= Html::encode($model->china_track_number) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>№ МС</label>
                            <input type="text" name="Order[ms_number]" value="<?= Html::encode($model->ms_number) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>DobroПост тариф</label>
                            <input type="text" name="Order[dobropost_tariff]" value="<?= Html::encode($model->dobropost_tariff) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Ценность (¥)</label>
                            <input type="number" step="0.01" name="Order[shipment_value_cny]" value="<?= Html::encode($model->shipment_value_cny) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Кол-во товаров</label>
                            <input type="number" name="Order[item_quantity]" value="<?= Html::encode($model->item_quantity) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Цена за ед. (¥)</label>
                            <input type="number" step="0.01" name="Order[item_price_cny]" value="<?= Html::encode($model->item_price_cny) ?>" <?= $inputDisabled ?>>
                        </div>
                    </div>
                    <div class="form-grid form-grid--2" style="margin-top:10px;">
                        <div class="form-field">
                            <label>Ссылка на товар</label>
                            <input type="url" name="Order[product_link]" value="<?= Html::encode($model->product_link) ?>" <?= $inputDisabled ?>>
                        </div>
                        <div class="form-field">
                            <label>Ссылка Sneakerhead</label>
                            <input type="url" name="Order[sneakerhead_order_link]" value="<?= Html::encode($model->sneakerhead_order_link) ?>" <?= $inputDisabled ?>>
                        </div>
                    </div>
                    <div class="form-field" style="margin-top:10px;">
                        <label>Описание для таможни</label>
                        <textarea name="Order[customs_description]" <?= $inputDisabled ?>><?= Html::encode($model->customs_description) ?></textarea>
                    </div>
                </section>

            <?php if ($canEdit && $isEditing): ?>
                <section class="panel">
                    <div class="panel__header">
                        <div class="panel__title">Позиции заказа</div>
                        <div class="panel__hint">Редактируйте состав и стоимость</div>
                    </div>
                    <?= $this->render('_order_items', [
                        'orderItems' => $model->orderItems,
                    ]) ?>
                </section>
                <div class="form-actions mt-3">
                    <button type="submit" class="order-btn order-btn--primary">Сохранить изменения</button>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $model->id]) ?>" class="order-btn">Отменить</a>
                </div>
            </form>
            <?php else: ?>
                <section class="panel">
                    <div class="panel__header">
                        <div class="panel__title">Состав заказа (<?= $itemCount ?>)</div>
                        <div class="panel__hint">Финальный подсчёт по позициям</div>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Кол-во</th>
                                    <th>Цена, BYN</th>
                                    <th>Сумма</th>
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
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="order-side">
            <section class="panel panel--side status-panel">
                <div class="panel__header">
                    <div class="panel__title">Статус заказа</div>
                    <div class="panel__hint"><?= Html::encode($model->getStatusLabel()) ?></div>
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
                    <div class="form-field" style="margin-top:10px;">
                        <label>Комментарий</label>
                        <textarea rows="3" name="comment" placeholder="Опционально..."></textarea>
                    </div>
                    <button type="submit" class="order-btn order-btn--primary" style="width:100%; margin-top:12px;">Сохранить статус</button>
                </form>
                <?php else: ?>
                <div class="status-description" style="font-size:.9rem;color:var(--muted);">
                    У вас нет прав для изменения статуса.
                </div>
                <?php endif; ?>
            </section>

            <section class="panel panel--side">
                <div class="panel__header">
                    <div class="panel__title">Финансы</div>
                    <div class="panel__hint">Основные суммы заказа</div>
                </div>
                <div class="finance-list">
                    <div class="finance-line"><span>Товар</span><strong><?= Yii::$app->formatter->asDecimal($model->product_price, 2) ?> BYN</strong></div>
                    <div class="finance-line"><span>Логистика</span><strong><?= Yii::$app->formatter->asDecimal($model->logistics_price, 2) ?> BYN</strong></div>
                    <div class="finance-line"><span>Комиссия</span><strong><?= Yii::$app->formatter->asDecimal($model->commission_price, 2) ?> BYN</strong></div>
                    <div class="finance-line"><span>Доставка</span><strong><?= Yii::$app->formatter->asDecimal($model->delivery_cost, 2) ?> BYN</strong></div>
                    <div class="finance-line" style="border-top:1px solid #eef0f3; padding-top:6px;">
                        <span>Итого</span><strong><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</strong>
                    </div>
                </div>
            </section>

            <?php if ($user->isAdmin()): ?>
            <section class="panel panel--side">
                <div class="panel__header">
                    <div class="panel__title">Ответственный логист</div>
                    <div class="panel__hint"><?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></div>
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
                    <button type="submit" class="order-btn" style="width:100%; margin-top:12px;">Сохранить</button>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($amoDealId): ?>
            <section class="panel panel--side" style="background:linear-gradient(135deg,#1f3d78,#102044);color:#fff;">
                <div class="panel__header">
                    <div class="panel__title" style="color:#fff;">amoCRM</div>
                    <div class="panel__hint" style="color:rgba(255,255,255,.7);">Сделка привязана</div>
                </div>
                <div style="font-size:1.4rem;font-weight:600;">#<?= Html::encode($amoDealId) ?></div>
                <?php if ($amoDealUrl): ?>
                    <a href="<?= Html::encode($amoDealUrl) ?>" target="_blank" class="order-btn" style="margin-top:14px;">
                        Перейти в amoCRM →
                    </a>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <section class="panel panel--side">
                <div class="panel__header">
                    <div class="panel__title">Публичная ссылка</div>
                    <div class="panel__hint">Для клиента</div>
                </div>
                <div class="public-link">
                    <input type="text" value="<?= Html::encode($model->getPublicUrl()) ?>" id="publicLink" readonly>
                    <button type="button" class="copy-btn" onclick="copyLink()">Копировать</button>
                </div>
            </section>

            <?php if ($model->history): ?>
            <section class="panel panel--side">
                <div class="panel__header">
                    <div class="panel__title">История изменений</div>
                    <div class="panel__hint"><?= count($model->history) ?> записей</div>
                </div>
                <div class="timeline">
                    <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item">
                            <div class="timeline-status"><?= Html::encode($history->getNewStatusLabel()) ?></div>
                            <div class="timeline-meta">
                                <?= Yii::$app->formatter->asDatetime($history->created_at, 'short') ?>
                                <?php if ($history->changer): ?> • <?= Html::encode($history->changer->username) ?><?php endif; ?>
                                <?php if ($history->comment): ?>
                                    <div><?= Html::encode($history->comment) ?></div>
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
    <i class="bi bi-check-circle"></i> Сохранено
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
