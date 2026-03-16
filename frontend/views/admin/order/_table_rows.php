<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\backend\modules\catalog\models\Order[] $orders */
/** @var array $statuses */
/** @var yii\i18n\Formatter $formatter */
/** @var array $statusDescriptions */
/** @var array $logistMap */

?>
<?php foreach ($orders as $order): ?>
    <?php
        $statusKey = $order->status;
        $statusLabel = Html::encode($statuses[$statusKey] ?? $statusKey);
        $statusDescription = Html::encode($statusDescriptions[$statusKey] ?? 'Статус обновляется.');
        $logistName = $order->assigned_logist ? ($logistMap[$order->assigned_logist] ?? ('ID ' . $order->assigned_logist)) : 'Не назначен';
        $clientName = $order->client_name ?: trim($order->recipient_last_name . ' ' . $order->recipient_first_name);
    ?>
    <tr data-order-id="<?= $order->id ?>">
        <td class="order-cell selection-cell" data-column="selection">
            <label class="checkbox-pill">
                <input type="checkbox" class="order-checkbox" value="<?= $order->id ?>">
                <span></span>
            </label>
        </td>
        <td class="order-cell order-cell--primary" data-column="order">
            <div class="order-id-line">
                <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>">№ <?= Html::encode($order->order_number) ?></a>
                <?php if ($order->assigned_logist): ?>
                    <span class="meta-pill">
                        <i class="bi bi-person-badge"></i>
                        <?= Html::encode($logistName) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="order-date"><?= $formatter->asDatetime($order->created_at, 'php:d M, H:i') ?></div>
            <div class="order-meta-line">
                <?php if ($order->china_track_number): ?>
                    <span class="meta-pill">CN: <?= Html::encode($order->china_track_number) ?></span>
                <?php endif; ?>
                <?php if ($order->ms_number): ?>
                    <span class="meta-pill">МС: <?= Html::encode($order->ms_number) ?></span>
                <?php endif; ?>
            </div>
        </td>
        <td class="order-cell" data-column="client">
            <div class="client-name"><?= Html::encode($clientName ?: '—') ?></div>
            <div class="client-contact">
                <?php if ($order->client_phone): ?>
                    <a href="tel:<?= Html::encode($order->client_phone) ?>"><?= Html::encode($order->client_phone) ?></a>
                <?php endif; ?>
                <?php if ($order->client_email): ?>
                    · <a href="mailto:<?= Html::encode($order->client_email) ?>"><?= Html::encode($order->client_email) ?></a>
                <?php endif; ?>
            </div>
            <div class="client-contact">
                <?= Html::encode($order->city ?: $order->delivery_country ?: '—') ?>
            </div>
        </td>
        <td class="order-cell" data-column="finance">
            <div class="finance-value"><?= $formatter->asDecimal($order->total_amount, 2) ?> BYN</div>
            <ul class="finance-breakdown">
                <li>Товар: <?= $order->product_price ? $formatter->asDecimal($order->product_price, 2) : '—' ?> BYN</li>
                <li>Логистика: <?= $order->logistics_price ? $formatter->asDecimal($order->logistics_price, 2) : '—' ?> BYN</li>
                <li>Комиссия: <?= $order->commission_price ? $formatter->asDecimal($order->commission_price, 2) : '—' ?> BYN</li>
            </ul>
        </td>
        <td class="order-cell" data-column="logistics">
            <div class="logistics-line">
                <?= Html::encode($order->delivery_method ?: 'Метод не задан') ?>
            </div>
            <div class="logistics-tags">
                <span class="logistics-tag <?= $order->is_processed ? 'success' : '' ?>">Обработан</span>
                <span class="logistics-tag <?= $order->is_shipped ? 'success' : '' ?>">Отправлен</span>
                <span class="logistics-tag <?= $order->customs_cleared ? 'success' : '' ?>">Таможня</span>
            </div>
            <div class="editable-field-group">
                <label>Трек CN</label>
                <div class="editable-cell" data-field="china_track_number" data-order-id="<?= $order->id ?>">
                    <span class="cell-value"><?= Html::encode($order->china_track_number ?: '—') ?></span>
                    <i class="bi bi-pencil edit-icon"></i>
                    <span class="inline-cell-indicator" aria-live="polite"></span>
                </div>
            </div>
            <div class="editable-field-group">
                <label>№ МС</label>
                <div class="editable-cell" data-field="ms_number" data-order-id="<?= $order->id ?>">
                    <span class="cell-value"><?= Html::encode($order->ms_number ?: '—') ?></span>
                    <i class="bi bi-pencil edit-icon"></i>
                    <span class="inline-cell-indicator" aria-live="polite"></span>
                </div>
            </div>
        </td>
        <td class="order-cell status-cell" data-column="status">
            <div class="status-detail">
                <div class="status-badge">
                    <span><?= $statusLabel ?></span>
                </div>
                <p class="status-description"><?= $statusDescription ?></p>
                <select class="status-select" data-field="status" data-order-id="<?= $order->id ?>">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= Html::encode($key) ?>" <?= $key === $order->status ? 'selected' : '' ?>>
                            <?= Html::encode($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="row-actions">
                    <a class="action-btn action-btn-view" title="Просмотр" href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if (!$user->isLogist()): ?>
                        <a class="action-btn action-btn-edit" title="Редактировать" href="<?= Url::to(['/admin/order/update', 'id' => $order->id]) ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
