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

// Функция для получения цвета статуса
function getStatusColor($status) {
    switch ($status) {
        case 'created': return 'info';
        case 'confirmed': return 'warning';
        case 'paid': return 'warning';
        case 'ordered': return 'warning';
        case 'shipped': return 'warning';
        case 'delivered': return 'success';
        case 'issued': return 'success';
        case 'canceled': return 'danger';
        default: return 'neutral';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'created': return 'bi-plus-circle';
        case 'confirmed': return 'bi-check-circle';
        case 'paid': return 'bi-currency-dollar';
        case 'ordered': return 'bi-bag';
        case 'shipped': return 'bi-truck';
        case 'delivered': return 'bi-box-seam';
        case 'issued': return 'bi-check2-all';
        case 'canceled': return 'bi-x-circle';
        default: return 'bi-question-circle';
    }
}

$statusColor = getStatusColor($model->status);
$statusIcon = getStatusIcon($model->status);
$statusName = $statuses[$model->status] ?? $model->status;
?>

<div class="admin-page-container">
    <div class="admin-page-shell">
        <!-- Хедер страницы -->
        <div class="admin-page-header">
            <div class="admin-page-header-content">
                <div class="admin-page-eyebrow">
                    <?= Html::a('<i class="bi bi-arrow-left"></i> К заказам', ['/admin/order/index'], ['class' => 'admin-link']) ?>
                </div>
                <h1 class="admin-page-title">
                    <i class="bi bi-receipt"></i>
                    Заказ №<?= Html::encode($model->order_number) ?>
                </h1>
                <p class="admin-page-subtitle">
                    Создан <?= Yii::$app->formatter->asDatetime($model->created_at, 'd MMMM yyyy, HH:mm') ?>
                    <?php if ($model->updated_at > $model->created_at): ?>
                        · Обновлён <?= Yii::$app->formatter->asDatetime($model->updated_at, 'd MMM HH:mm') ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-page-actions">
                <?php if ($isEditing): ?>
                    <button type="submit" form="order-edit-form" class="admin-btn admin-btn--success">
                        <i class="bi bi-check-lg"></i>
                        Сохранить
                    </button>
                    <?= Html::a('<i class="bi bi-x-lg"></i> Отмена', ['/admin/order/view', 'id' => $model->id], ['class' => 'admin-btn admin-btn--ghost']) ?>
                <?php elseif ($canEdit): ?>
                    <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['/admin/order/update', 'id' => $model->id], ['class' => 'admin-btn admin-btn--primary']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Метрики заказа -->
        <div class="admin-grid admin-grid--4 admin-mb-6">
            <div class="admin-metric-card">
                <span class="admin-metric-label">Статус заказа</span>
                <span class="admin-metric-value" style="font-size: 1.25rem;">
                    <span class="admin-badge admin-badge--<?= $statusColor ?>">
                        <i class="bi <?= $statusIcon ?>"></i>
                        <?= Html::encode($statusName) ?>
                    </span>
                </span>
                <span class="admin-metric-change">
                    ID: #<?= $model->id ?>
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Сумма заказа</span>
                <span class="admin-metric-value"><?= number_format($model->total_amount ?? 0, 0, ',', ' ') ?> <small>BYN</small></span>
                <span class="admin-metric-change <?= ($model->delivery_cost > 0) ? 'admin-metric-change--up' : '' ?>">
                    <?php if ($model->delivery_cost > 0): ?>
                        +<?= number_format($model->delivery_cost, 0, ',', ' ') ?> доставка
                    <?php else: ?>
                        Без доставки
                    <?php endif; ?>
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Позиций в заказе</span>
                <span class="admin-metric-value"><?= $itemCount ?></span>
                <span class="admin-metric-change">
                    <?= $model->delivery_method ?: 'Способ доставки не указан' ?>
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Клиент</span>
                <span class="admin-metric-value" style="font-size: 1.25rem;"><?= Html::encode($model->client_name) ?></span>
                <span class="admin-metric-change">
                    <?= Html::encode($model->client_phone) ?>
                </span>
            </div>
        </div>

        <!-- Основной контент - две колонки -->
        <div class="admin-grid admin-grid--3 admin-gap-6 admin-mb-6">
            <!-- Левая колонка (2/3) -->
            <div class="admin-grid-col-span-2">
                <?php if ($isEditing): ?>
                    <?= Html::beginForm(['/admin/order/update', 'id' => $model->id], 'post', ['class' => 'order-edit-form', 'id' => 'order-edit-form']) ?>
                    <input type="hidden" name="editing" value="1">
                <?php endif; ?>

                <!-- Клиент и доставка -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-person"></i>
                            Клиент и доставка
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid--2 admin-gap-4 admin-mb-4">
                            <div class="admin-form-group">
                                <label class="admin-form-label">ФИО клиента</label>
                                <?php if ($isEditing): ?>
                                    <input type="text" class="admin-form-input" name="client_name" value="<?= Html::encode($model->client_name) ?>">
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->client_name) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Телефон</label>
                                <?php if ($isEditing): ?>
                                    <input type="tel" class="admin-form-input" name="client_phone" value="<?= Html::encode($model->client_phone) ?>">
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->client_phone) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Email</label>
                                <?php if ($isEditing): ?>
                                    <input type="email" class="admin-form-input" name="client_email" value="<?= Html::encode($model->client_email) ?>">
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->client_email ?: '—') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Способ доставки</label>
                                <?php if ($isEditing): ?>
                                    <input type="text" class="admin-form-input" name="delivery_method" value="<?= Html::encode($model->delivery_method) ?>">
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->delivery_method ?: '—') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Адрес доставки</label>
                                <?php if ($isEditing): ?>
                                    <input type="text" class="admin-form-input" name="full_address" value="<?= Html::encode($model->full_address) ?>">
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->full_address ?: '—') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Дата доставки</label>
                                <?php if ($isEditing): ?>
                                    <input type="date" class="admin-form-input" name="delivery_date" value="<?= $model->delivery_date ?>">
                                <?php else: ?>
                                    <div class="admin-form-value">
                                        <?= $model->delivery_date ? Yii::$app->formatter->asDate($model->delivery_date, 'd MMMM yyyy') : '—' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($model->comment || $isEditing): ?>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Комментарий к заказу</label>
                                <?php if ($isEditing): ?>
                                    <textarea class="admin-form-textarea" name="comment" rows="3"><?= Html::encode($model->comment) ?></textarea>
                                <?php else: ?>
                                    <div class="admin-form-value"><?= Html::encode($model->comment) ?: '<span class="admin-text-muted">Нет комментария</span>' ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Товары в заказе -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                        <h2 class="admin-card-title">
                            <i class="bi bi-cart3"></i>
                            Состав заказа
                        </h2>
                        <span class="admin-badge admin-badge--neutral">
                            <?= $itemCount ?> <?= Yii::t('app', '{n, plural, one{товар} few{товара} many{товаров} other{товаров}}', ['n' => $itemCount]) ?>
                        </span>
                    </div>
                    <div class="admin-card-body admin-table-wrapper">
                        <table class="admin-table admin-table--compact">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th class="text-center">Кол-во</th>
                                    <th class="text-end">Цена</th>
                                    <th class="text-end">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->orderItems as $item): ?>
                                    <tr>
                                        <td><?= Html::encode($item->product_name) ?></td>
                                        <td class="text-center"><?= $item->quantity ?></td>
                                        <td class="text-end"><?= number_format($item->price ?? 0, 2, ',', ' ') ?> BYN</td>
                                        <td class="text-end fw-semibold"><?= number_format($item->total ?? 0, 2, ',', ' ') ?> BYN</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <?php if ($model->delivery_cost > 0): ?>
                                    <tr class="admin-table-footer">
                                        <td colspan="3" class="text-end">Доставка:</td>
                                        <td class="text-end">+<?= number_format($model->delivery_cost, 2, ',', ' ') ?> BYN</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="admin-table-footer admin-table-footer--total">
                                    <td colspan="3" class="text-end fw-bold">ИТОГО К ОПЛАТЕ:</td>
                                    <td class="text-end fw-bold" style="font-size: 1.125rem; color: var(--admin-success);">
                                        <?= number_format(($model->total_amount ?? 0) + ($model->delivery_cost ?? 0), 2, ',', ' ') ?> BYN
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Паспорт и получатель -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-person-badge"></i>
                            Данные получателя
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid--2 admin-gap-4">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Фамилия</label>
                                <div class="admin-form-value"><?= Html::encode($model->recipient_last_name ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Имя</label>
                                <div class="admin-form-value"><?= Html::encode($model->recipient_first_name ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Отчество</label>
                                <div class="admin-form-value"><?= Html::encode($model->recipient_middle_name ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Дата рождения</label>
                                <div class="admin-form-value">
                                    <?= $model->birth_date ? Yii::$app->formatter->asDate($model->birth_date, 'd MMMM yyyy') : '—' ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Паспорт серия/номер</label>
                                <div class="admin-form-value">
                                    <?= $model->passport_series || $model->passport_number
                                        ? Html::encode(trim($model->passport_series . ' ' . $model->passport_number))
                                        : '—' ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Дата выдачи паспорта</label>
                                <div class="admin-form-value">
                                    <?= $model->passport_issue_date ? Yii::$app->formatter->asDate($model->passport_issue_date, 'd MMMM yyyy') : '—' ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">ИНН (для РФ)</label>
                                <div class="admin-form-value"><?= Html::encode($model->inn ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Индекс</label>
                                <div class="admin-form-value"><?= Html::encode($model->postal_code ?: '—') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Логистика -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-truck"></i>
                            Логистика и внешние ссылки
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid--2 admin-gap-4">
                            <div class="admin-form-group">
                                <label class="admin-form-label">Трек-номер (Китай)</label>
                                <div class="admin-form-value"><?= Html::encode($model->china_track_number ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Номер МС</label>
                                <div class="admin-form-value"><?= Html::encode($model->ms_number ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Стоимость поставки (CNY)</label>
                                <div class="admin-form-value">
                                    <?= $model->shipment_value_cny ? number_format($model->shipment_value_cny, 2, ',', ' ') . ' CNY' : '—' ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Количество (таможня)</label>
                                <div class="admin-form-value"><?= $model->item_quantity ?: '—' ?></div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Цена (таможня, CNY)</label>
                                <div class="admin-form-value">
                                    <?= $model->item_price_cny ? number_format($model->item_price_cny, 2, ',', ' ') . ' CNY' : '—' ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Описание для таможни</label>
                                <div class="admin-form-value"><?= Html::encode($model->customs_description ?: '—') ?></div>
                            </div>
                            <div class="admin-form-group admin-grid-col-span-2">
                                <label class="admin-form-label">Ссылка на товар</label>
                                <div class="admin-form-value">
                                    <?php if ($model->product_link): ?>
                                        <?= Html::a(Html::encode($model->product_link), $model->product_link, ['target' => '_blank', 'class' => 'admin-link']) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="admin-form-group admin-grid-col-span-2">
                                <label class="admin-form-label">Заказ Sneakerhead</label>
                                <div class="admin-form-value">
                                    <?php if ($model->sneakerhead_order_link): ?>
                                        <?= Html::a(Html::encode($model->sneakerhead_order_link), $model->sneakerhead_order_link, ['target' => '_blank', 'class' => 'admin-link']) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($amoDealUrl): ?>
                                <div class="admin-form-group admin-grid-col-span-2">
                                    <label class="admin-form-label">AmoCRM сделка</label>
                                    <div class="admin-form-value">
                                        <?= Html::a('<i class="bi bi-box-arrow-up-right"></i> Открыть в AmoCRM', $amoDealUrl, ['target' => '_blank', 'class' => 'admin-link']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($isEditing): ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>

            <!-- Правая колонка (1/3) -->
            <div>
                <!-- Публичная ссылка -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-link-45deg"></i>
                            Публичная ссылка
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group admin-mb-3">
                            <input type="text" class="admin-form-input" readonly value="<?= Url::to(['/order/view', 'token' => $model->token], true) ?>" id="publicLinkInput">
                        </div>
                        <button class="admin-btn admin-btn--secondary admin-btn--sm" onclick="copyPublicLink()">
                            <i class="bi bi-copy"></i>
                            Копировать ссылку
                        </button>
                    </div>
                </div>

                <!-- Управление статусом -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-sliders"></i>
                            Управление
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-form-group admin-mb-4">
                            <label class="admin-form-label">Статус заказа</label>
                            <?php if ($isEditing): ?>
                                <select class="admin-form-select" name="status">
                                    <?php foreach ($statuses as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                            <?= Html::encode($value) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="admin-flex admin-gap-2 admin-flex--center">
                                    <span class="admin-badge admin-badge--<?= $statusColor ?>">
                                        <i class="bi <?= $statusIcon ?>"></i>
                                        <?= Html::encode($statusName) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($user->isAdmin()): ?>
                            <div class="admin-form-group">
                                <label class="admin-form-label">Назначенный логист</label>
                                <?php if ($isEditing): ?>
                                    <select class="admin-form-select" name="assigned_logist">
                                        <option value="">Не назначен</option>
                                        <?php foreach ($logists as $logist): ?>
                                            <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                                <?= Html::encode($logist->username) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <div class="admin-form-value">
                                        <?php if ($model->assignedLogist): ?>
                                            <span class="admin-badge admin-badge--success">
                                                <i class="bi bi-person-check"></i>
                                                <?= Html::encode($model->assignedLogist->username) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="admin-text-muted">Не назначен</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Финансы -->
                <div class="admin-card admin-mb-6">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-cash-stack"></i>
                            Финансы
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <table class="admin-table admin-table--plain">
                            <tbody>
                                <tr>
                                    <td class="admin-text-muted">Стоимость товаров</td>
                                    <td class="text-end"><?= number_format($model->product_price ?? 0, 2, ',', ' ') ?> BYN</td>
                                </tr>
                                <tr>
                                    <td class="admin-text-muted">Логистика</td>
                                    <td class="text-end"><?= number_format($model->logistics_price ?? 0, 2, ',', ' ') ?> BYN</td>
                                </tr>
                                <tr>
                                    <td class="admin-text-muted">Комиссия</td>
                                    <td class="text-end"><?= number_format($model->commission_price ?? 0, 2, ',', ' ') ?> BYN</td>
                                </tr>
                                <?php if ($model->delivery_cost > 0): ?>
                                    <tr>
                                        <td class="admin-text-muted">Доставка</td>
                                        <td class="text-end">+<?= number_format($model->delivery_cost, 2, ',', ' ') ?> BYN</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="admin-table-footer--total">
                                    <td class="fw-bold">ИТОГО</td>
                                    <td class="text-end fw-bold" style="color: var(--admin-success);">
                                        <?= number_format(($model->total_amount ?? 0) + ($model->delivery_cost ?? 0), 2, ',', ' ') ?> BYN
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Действия -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            <i class="bi bi-lightning-charge"></i>
                            Быстрые действия
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-flex admin-flex--column admin-gap-2">
                            <?= Html::a('<i class="bi bi-file-pdf"></i> Скачать PDF', ['#'], ['class' => 'admin-btn admin-btn--outline admin-btn--sm']) ?>
                            <?= Html::a('<i class="bi bi-printer"></i> Печать', ['#'], ['class' => 'admin-btn admin-btn--outline admin-btn--sm', 'onclick' => 'window.print(); return false;']) ?>
                            <?= Html::a('<i class="bi bi-send"></i> Отправить клиенту', ['#'], ['class' => 'admin-btn admin-btn--outline admin-btn--sm']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyPublicLink() {
    const input = document.getElementById('publicLinkInput');
    input.select();
    document.execCommand('copy');

    const button = document.querySelector('.admin-card-body button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check-lg"></i> Скопировано!';
    button.classList.add('admin-btn--success');
    button.classList.remove('admin-btn--secondary');

    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('admin-btn--success');
        button.classList.add('admin-btn--secondary');
    }, 2000);
}
</script>

<style>
/* Дополнительные стили для страницы заказа */
.admin-form-value {
    padding: 10px 0;
    font-weight: 500;
    color: var(--admin-text-primary);
}

.admin-table-footer {
    background: var(--admin-surface);
    font-weight: 600;
}

.admin-table-footer--total {
    background: var(--admin-surface-muted);
    font-size: 1rem;
}

.admin-table--plain td {
    padding: 8px 0;
    border: none;
}

.admin-table--plain tr:last-child td {
    padding-top: 12px;
    border-top: 2px solid var(--admin-border);
}

.admin-link {
    color: var(--admin-primary);
    text-decoration: none;
}

.admin-link:hover {
    text-decoration: underline;
}

/* Для печати */
@media print {
    .admin-page-actions,
    .admin-btn,
    .admin-card-header {
        display: none !important;
    }

    .admin-card {
        border: 1px solid #ddd;
        break-inside: avoid;
    }
}
</style>

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

.order-view {
    background: var(--bg);
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--text);
}

/* Хедер заказа */
.order-header {
    max-width: 1400px;
    margin: 0 auto 20px;
    background: var(--panel);
    border-radius: 8px;
    padding: 16px 20px;
    border: 1px solid var(--border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.order-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.order-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.order-actions-header {
    display: flex;
    gap: 8px;
}

.order-header-middle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 16px;
}

.order-header-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    color: var(--muted);
    flex-wrap: wrap;
    gap: 16px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 16px;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
}

.status-badge--created { background: #3b82f6; }
.status-badge--confirmed { background: #f59e0b; }
.status-badge--paid { background: #f59e0b; }
.status-badge--ordered { background: #f59e0b; }
.status-badge--shipped { background: #f59e0b; }
.status-badge--delivered { background: #10b981; }
.status-badge--canceled { background: #ef4444; }

.client-id {
    font-size: 0.875rem;
    color: var(--muted);
    font-weight: 500;
}

.order-dates {
    font-size: 0.875rem;
    color: var(--muted);
}

.order-meta {
    display: flex;
    gap: 16px;
    align-items: center;
}

.public-link-mini {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--muted);
}

.public-link-mini input {
    width: 120px;
    padding: 2px 6px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 0.75rem;
    background: var(--bg);
}

.public-link-mini button {
    padding: 2px 6px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: var(--panel);
    color: var(--muted);
    font-size: 0.75rem;
    cursor: pointer;
}

.public-link-mini button:hover {
    background: var(--bg);
}

/* Метрики */
.metrics-cards {
    max-width: 1400px;
    margin: 0 auto 30px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.metric-card {
    background: var(--panel);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--border);
    text-align: center;
}

.metric-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.metric-label {
    font-size: 0.875rem;
    color: var(--muted);
}

.metric-subtitle {
    font-size: 0.75rem;
    color: var(--muted);
    margin-top: 4px;
}

/* Основной контент - двухколонный лейаут */
.order-content {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.content-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.content-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Секции */
.section {
    background: var(--panel);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
}

.section-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text);
    margin: 0;
}

.section-content {
    padding: 24px;
}

/* Клиент и доставка */
.contact-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-item input.control-input {
    flex: 1;
    padding: 4px 8px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
}

.contact-item input.control-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-soft);
}

.contact-label {
    font-weight: 500;
    color: var(--muted);
    min-width: 80px;
}

.contact-value {
    color: var(--text);
}

.contact-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.contact-status--filled {
    background: var(--success-soft);
    color: var(--success);
}

.contact-status--empty {
    background: var(--danger-soft);
    color: var(--danger);
}

.delivery-info {
    background: var(--bg);
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.delivery-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.delivery-item input.control-input {
    flex: 1;
    padding: 4px 8px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
}

.delivery-item input.control-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-soft);
}

.delivery-label {
    color: var(--muted);
    font-weight: 500;
}

.delivery-value {
    color: var(--text);
}

/* Таблица товаров */
.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: var(--muted);
    font-size: 0.875rem;
    border-bottom: 2px solid var(--border);
}

.products-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
}

.products-table tr:hover {
    background: var(--bg);
}

.products-summary {
    background: var(--bg);
    font-weight: 600;
}

.products-summary td {
    padding: 16px 12px;
}

.products-total {
    font-size: 1.125rem;
    color: var(--success);
}

/* Аккордеон */
.accordion-item {
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}

.accordion-header {
    background: var(--bg);
    padding: 16px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.accordion-header:hover {
    background: var(--border);
}

.accordion-title {
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.accordion-icon {
    transition: transform 0.2s;
}

.accordion-item--open .accordion-icon {
    transform: rotate(180deg);
}

.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.2s ease-out;
}

.accordion-item--open .accordion-content {
    max-height: 1000px;
}

.accordion-body {
    padding: 20px;
}

/* Боковая панель управления */
.control-section {
    background: var(--panel);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
}

.control-header {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    color: var(--text);
}

.control-content {
    padding: 20px;
}

.control-group {
    margin-bottom: 20px;
}

.control-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--muted);
    font-size: 0.875rem;
}

.control-input,
.control-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
}

.control-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
    resize: vertical;
    min-height: 80px;
}

.control-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
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
    cursor: pointer;
    transition: all 0.2s;
}

.btn:hover {
    background: var(--bg);
}

.btn--primary {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.btn--primary:hover {
    background: #2563eb;
}

.btn--success {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

.btn--success:hover {
    background: #059669;
}

.btn--secondary {
    background: var(--muted);
    color: white;
    border-color: var(--muted);
}

.btn--secondary:hover {
    background: #475569;
}

.btn--sm {
    padding: 6px 12px;
    font-size: 0.75rem;
}

/* Финансы */
.finance-table {
    width: 100%;
    font-size: 0.875rem;
}

.finance-table td {
    padding: 8px 0;
}

.finance-table td:first-child {
    color: var(--muted);
    font-weight: 500;
}

.finance-table td:last-child {
    text-align: right;
    font-weight: 600;
    color: var(--text);
}

.finance-total {
    border-top: 2px solid var(--border);
    margin-top: 8px;
    padding-top: 8px;
    font-size: 1rem;
    color: var(--success);
}

/* Публичная ссылка */
.public-link {
    display: flex;
    gap: 8px;
}

.public-link input {
    flex: 1;
}

/* Мобильная адаптивность */
@media (max-width: 1024px) {
    .order-content {
        grid-template-columns: 1fr;
    }
    
    .contact-row {
        grid-template-columns: 1fr;
    }
    
    .order-header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .order-header-middle {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .order-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .public-link-mini {
        align-self: flex-end;
    }
}

@media (max-width: 768px) {
    .order-view {
        padding: 12px;
    }
    
    .order-header {
        padding: 12px 16px;
    }
    
    .order-number {
        font-size: 1.25rem;
    }
    
    .order-header-middle {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .public-link-mini {
        align-self: stretch;
        justify-content: space-between;
    }
    
    .public-link-mini input {
        flex: 1;
        width: auto;
    }
    
    .metrics-cards {
        grid-template-columns: 1fr;
    }
    
    .products-table {
        font-size: 0.875rem;
    }
    
    .products-table th,
    .products-table td {
        padding: 8px;
    }
    
    .control-actions {
        flex-direction: column;
    }
}
</style>

<div class="order-view">
    <!-- Хедер заказа -->
    <header class="order-header">
        <div class="order-header-top">
            <h1 class="order-number">Заказ №<?= Html::encode($model->order_number) ?></h1>
            <div class="order-actions-header">
                <?= Html::a('← Список заказов', ['/admin/order/index'], ['class' => 'btn']) ?>
                <?php if ($isEditing): ?>
                    <button type="submit" form="order-edit-form" class="btn btn--success">💾 Сохранить</button>
                    <?= Html::a('✕ Отмена', ['/admin/order/view', 'id' => $model->id], ['class' => 'btn btn--secondary']) ?>
                <?php elseif ($canEdit): ?>
                    <?= Html::a('✏️ Редактировать', ['/admin/order/update', 'id' => $model->id], ['class' => 'btn btn--primary']) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-header-middle">
            <div class="order-meta">
                <?php if ($isEditing): ?>
                    <select class="control-select" name="status" style="width: auto; margin-right: 8px;">
                        <?php foreach ($statuses as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                <?= Html::encode($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="status-badge status-badge--<?= $model->status ?>">
                        <?= getStatusIcon($model->status) ?>
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
            <div class="metric-value"><?= getStatusIcon($model->status) ?></div>
            <div class="metric-label">Статус</div>
            <div class="metric-subtitle"><?= Html::encode($statuses[$model->status] ?? $model->status) ?></div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="order-content">
        <!-- Левая колонка - основная информация -->
        <div class="content-main">
            <?php if ($isEditing): ?>
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
                            <?php if ($isEditing): ?>
                                <input type="text" class="control-input" name="client_name" value="<?= Html::encode($model->client_name) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="contact-value"><?= Html::encode($model->client_name) ?></span>
                            <?php endif; ?>
                            <span class="contact-status contact-status--filled">✓</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-label">Телефон:</span>
                            <?php if ($isEditing): ?>
                                <input type="tel" class="control-input" name="client_phone" value="<?= Html::encode($model->client_phone) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="contact-value"><?= Html::encode($model->client_phone) ?></span>
                            <?php endif; ?>
                            <span class="contact-status contact-status--filled">✓</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-label">Email:</span>
                            <?php if ($isEditing): ?>
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
                            <?php if ($isEditing): ?>
                                <input type="text" class="control-input" name="delivery_method" value="<?= Html::encode($model->delivery_method) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->delivery_method ?: 'не указан') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="delivery-item">
                            <span class="delivery-label">Дата доставки:</span>
                            <?php if ($isEditing): ?>
                                <input type="date" class="control-input" name="delivery_date" value="<?= $model->delivery_date ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= $model->delivery_date ? Yii::$app->formatter->asDate($model->delivery_date, 'd MMM yyyy') : 'не указана' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="delivery-item">
                            <span class="delivery-label">Адрес:</span>
                            <?php if ($isEditing): ?>
                                <input type="text" class="control-input" name="full_address" value="<?= Html::encode($model->full_address) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->full_address ?: 'не указан') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($model->postal_code || $isEditing): ?>
                        <div class="delivery-item">
                            <span class="delivery-label">Индекс:</span>
                            <?php if ($isEditing): ?>
                                <input type="text" class="control-input" name="postal_code" value="<?= Html::encode($model->postal_code) ?>" <?= $inputDisabled ?>>
                            <?php else: ?>
                                <span class="delivery-value"><?= Html::encode($model->postal_code) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($model->comment || $isEditing): ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--bg); border-radius: 8px;">
                        <strong>Комментарий:</strong> 
                        <?php if ($isEditing): ?>
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
                                <td style="text-align: center;"><?= $item->quantity ?></td>
                                <td style="text-align: right;"><?= number_format($item->price ?? 0, 2, ',', ' ') ?></td>
                                <td style="text-align: right;"><?= number_format($item->total ?? 0, 2, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if ($model->delivery_cost > 0): ?>
                            <tr class="products-summary">
                                <td colspan="3">Доставка</td>
                                <td style="text-align: right;">+<?= number_format($model->delivery_cost ?? 0, 2, ',', ' ') ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="products-summary">
                                <td colspan="3" style="font-weight: 600;">ВСЕГО К ОПЛАТЕ</td>
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
                                <strong>ФИО получателя:</strong> <?= Html::encode($model->recipient_first_name . ' ' . $model->recipient_last_name . ' ' . $model->recipient_middle_name ?: 'не указано') ?>
                            </div>
                            <div>
                                <strong>Паспорт:</strong> 
                                <?php if ($model->passport_series || $model->passport_number): ?>
                                    <?= Html::encode($model->passport_series . ' ' . $model->passport_number) ?>
                                    <?php if ($model->passport_issue_date): ?>
                                        выдан <?= Yii::$app->formatter->asDate($model->passport_issue_date, 'd MMM yyyy') ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    не указано
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Дата рождения:</strong> <?= $model->birth_date ? Yii::$app->formatter->asDate($model->birth_date, 'd MMM yyyy') : 'не указано' ?>
                            </div>
                            <div>
                                <strong>ИНН (для РФ):</strong> <?= Html::encode($model->inn ?: 'не указано') ?>
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
                                <strong>Трек-номер (Китай):</strong> <?= Html::encode($model->china_track_number ?: 'не указано') ?>
                            </div>
                            <div>
                                <strong>Номер МС:</strong> <?= Html::encode($model->ms_number ?: 'не указано') ?>
                            </div>
                            <div>
                                <strong>Стоимость поставки (CNY):</strong> <?= $model->shipment_value_cny ? number_format($model->shipment_value_cny, 2, ',', ' ') : 'не указано' ?>
                            </div>
                            <div>
                                <strong>Описание для таможни:</strong> <?= Html::encode($model->customs_description ?: 'не указано') ?>
                            </div>
                            <div>
                                <strong>Количество (таможня):</strong> <?= $model->item_quantity ?: 'не указано' ?>
                            </div>
                            <div>
                                <strong>Цена (таможня, CNY):</strong> <?= $model->item_price_cny ? number_format($model->item_price_cny, 2, ',', ' ') : 'не указано' ?>
                            </div>
                            <div>
                                <strong>Ссылка на товар:</strong> 
                                <?php if ($model->product_link): ?>
                                    <?= Html::a(Html::encode($model->product_link), $model->product_link, ['target' => '_blank']) ?>
                                <?php else: ?>
                                    не указана
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Заказ Sneakerhead:</strong> 
                                <?php if ($model->sneakerhead_order_link): ?>
                                    <?= Html::a(Html::encode($model->sneakerhead_order_link), $model->sneakerhead_order_link, ['target' => '_blank']) ?>
                                <?php else: ?>
                                    не указан
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
                        <?php if ($canEdit && $isEditing): ?>
                        <div class="control-actions">
                            <button class="btn btn--success btn--sm">🔄 Сохранить</button>
                            <button class="btn btn--secondary btn--sm">✕ Отмена</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">Комментарий:</label>
                        <textarea class="control-textarea" <?= $inputDisabled ?> placeholder="Добавьте комментарий к заказу..."><?= Html::encode($model->comment) ?></textarea>
                        <?php if ($canEdit && $isEditing): ?>
                        <div class="control-actions">
                            <button class="btn btn--success btn--sm">🔄 Сохранить</button>
                            <button class="btn btn--secondary btn--sm">✕ Отмена</button>
                        </div>
                        <?php endif; ?>
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
                        <?php if ($canEdit && $isEditing): ?>
                        <div class="control-actions">
                            <button class="btn btn--success btn--sm">💾 Сохранить</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

<?php if ($isEditing): ?>
<?= Html::endForm() ?>
<?php endif; ?>

<script>
function toggleAccordion(id) {
    const element = document.getElementById(id);
    element.classList.toggle('accordion-item--open');
}

function copyPublicLink() {
    const input = document.querySelector('.public-link input');
    input.select();
    document.execCommand('copy');
    
    // Показать уведомление
    const button = document.querySelector('.public-link button');
    const originalText = button.innerHTML;
    button.innerHTML = '✅ Скопировано';
    button.style.background = 'var(--success)';
    button.style.borderColor = 'var(--success)';
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.background = '';
        button.style.borderColor = '';
    }, 2000);
}
</script>
