<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . ($model->order_number ?: $model->id);
$user = Yii::$app->user->identity;
$statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();

// CSS moved to frontend/web/css/admin-pages.css (/* -- order/view.php -- */)

<div class="order-page">
    <div class="order-shell">
        <!-- Верхняя панель со статусом -->
        <div class="order-top-bar">
            <div class="order-top-left">
                <h1><i class="bi bi-box-seam"></i> <?= Html::encode($this->title) ?></h1>
                <div class="order-top-meta">
                    <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?>
                    <?php if ($model->creator): ?>
                        • <?= Html::encode($model->creator->username) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="order-top-right">
                <!-- Быстрая смена статуса -->
                <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>" id="quickStatusForm">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="status-select-wrap">
                        <label>Статус:</label>
                        <select name="status" class="status-select status-<?= $model->status ?>" onchange="this.form.submit()">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="public-link-inline">
                    <label>Публичная ссылка</label>
                    <div class="public-link-inline-fields">
                        <input type="text" value="<?= $model->getPublicUrl() ?>" id="public-link" readonly>
                        <button class="btn-action btn-secondary-light" type="button" onclick="copyLink('public-link', event)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <div class="top-action-buttons">
                    <button type="button" class="btn-action btn-secondary-light" onclick="openHistoryModal()">
                        <i class="bi bi-clock-history"></i> История
                    </button>
                    <?php if (!$user->isLogist()): ?>
                        <button type="button" class="btn-action btn-action-primary" id="toggleEditMode">
                            <i class="bi bi-pencil"></i> <span id="editModeText">Редактировать</span>
                        </button>
                    <?php endif; ?>
                    <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/order/index'], ['class' => 'btn-action']) ?>
                </div>
            </div>
        </div>

        <div class="order-grid">
            <div class="order-main">
                <!-- СОСТАВ ЗАКАЗА -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-bag-check"></i> Состав заказа</h3>
                        <span style="font-size:0.8125rem;color:#6b7280;"><?= count($model->orderItems) ?> позиций</span>
                    </div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Наименование</th>
                                <th style="width:80px;">Кол-во</th>
                                <th style="width:100px;">Цена</th>
                                <th style="width:100px;">Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $index => $item): ?>
                            <tr>
                                <td style="color:#6b7280;"><?= $index + 1 ?></td>
                                <td class="item-name"><?= Html::encode($item->product_name) ?></td>
                                <td><?= $item->quantity ?> шт.</td>
                                <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> Br</td>
                                <td class="item-price"><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> Br</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="items-total">
                        <span class="items-total-label">Итого к оплате:</span>
                        <span class="items-total-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> Br</span>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-person"></i> Клиент</h3>
                    </div>
                    <div class="card-body">
                        <div id="viewMode">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">ФИО</div>
                                    <div class="info-value editable-field" data-field="client_name" data-id="<?= $model->id ?>"><?= Html::encode($model->client_name ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Телефон</div>
                                    <div class="info-value editable-field" data-field="client_phone" data-id="<?= $model->id ?>"><?= Html::encode($model->client_phone ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value editable-field" data-field="client_email" data-id="<?= $model->id ?>"><?= Html::encode($model->client_email ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Срок доставки</div>
                                    <div class="info-value editable-field" data-field="delivery_date" data-id="<?= $model->id ?>"><?= Html::encode($model->delivery_date ?: '-') ?></div>
                                </div>
                            </div>

                            <?php if ($model->comment): ?>
                            <div style="margin-top:1rem;padding:0.875rem;background:#fef3c7;border-radius:8px;">
                                <div style="font-weight:600;font-size:0.75rem;color:#92400e;margin-bottom:0.25rem;">КОММЕНТАРИЙ</div>
                                <div class="editable-field" data-field="comment" data-id="<?= $model->id ?>" style="color:#78350f;cursor:pointer;" title="Кликните для редактирования"><?= nl2br(Html::encode($model->comment)) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                    <!-- Режим редактирования -->
                    <div id="editMode" style="display: none;">
                        <form method="post" action="<?= Url::to(['/admin/order/update', 'id' => $model->id]) ?>" id="orderEditForm">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">ФИО клиента</label>
                                        <input type="text" class="form-control" name="Order[client_name]" value="<?= Html::encode($model->client_name) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Телефон</label>
                                        <input type="text" class="form-control" name="Order[client_phone]" value="<?= Html::encode($model->client_phone) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" class="form-control" name="Order[client_email]" value="<?= Html::encode($model->client_email) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Срок доставки</label>
                                        <input type="text" class="form-control" name="Order[delivery_date]" value="<?= Html::encode($model->delivery_date) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Статус</label>
                                        <select class="form-select" name="Order[status]">
                                            <?php foreach (Yii::$app->settings->getStatuses() as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Комментарий</label>
                                <textarea class="form-control" name="Order[comment]" rows="3" placeholder="Примечания к заказу..."><?= Html::encode($model->comment) ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Сохранить изменения
                                </button>
                                <button type="button" class="btn btn-secondary" id="cancelEdit">
                                    <i class="bi bi-x-circle"></i> Отмена
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-3">
                        <p><strong>Публичная ссылка:</strong></p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= $model->getPublicUrl() ?>" id="public-link-alt" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyLink('public-link-alt', event)">
                                <i class="bi bi-clipboard"></i> Копировать
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Товары</h5>
                </div>
                <div class="card-body">
                    <!-- Режим просмотра товаров -->
                    <div id="viewModeItems">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Наименование</th>
                                    <th>Количество</th>
                                    <th>Цена</th>
                                    <th>Итого</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->orderItems as $index => $item): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= Html::encode($item->product_name) ?></td>
                                    <td><?= $item->quantity ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> BYN</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="4" class="text-end">ИТОГО:</th>
                                    <th><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Режим редактирования товаров -->
                    <div id="editModeItems" style="display: none;">
                        <div id="order-items-edit">
                            <?php foreach ($model->orderItems as $index => $item): ?>
                            <div class="order-item row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Название товара</label>
                                    <input type="text" class="form-control" name="OrderItem[<?= $index ?>][product_name]" value="<?= Html::encode($item->product_name) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Количество</label>
                                    <input type="number" class="form-control" name="OrderItem[<?= $index ?>][quantity]" value="<?= $item->quantity ?>" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Цена (BYN)</label>
                                    <input type="number" step="0.01" class="form-control" name="OrderItem[<?= $index ?>][price]" value="<?= $item->price ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger remove-item">Удалить</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="add-item-edit">+ Добавить товар</button>
                    </div>
                </div>
            </div>

                <!-- ДОСТАВКА 1 — Международная (Poizon → РБ) -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-airplane"></i> Доставка из Китая (Poizon → РБ)</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Трек Poizon</div>
                                <div style="display:flex;gap:0.5rem;align-items:center">
                                    <input type="text" class="form-control" id="china-track-input" value="<?= Html::encode($model->china_track_number ?? '') ?>" placeholder="Введите трек-номер">
                                    <button class="btn btn-outline-primary" onclick="saveField('china_track_number', document.getElementById('china-track-input').value)"><i class="bi bi-save"></i></button>
                                    <button class="btn btn-outline-secondary" onclick="checkTrack(document.getElementById('china-track-input').value, this)"><i class="bi bi-search"></i></button>
                                </div>
                                <div id="china-track-result" style="margin-top:0.5rem;font-size:0.8125rem;color:#6b7280;"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Статус этапа</div>
                                <select class="form-select" onchange="saveField('china_delivery_status', this.value)">
                                    <?php foreach(['ordered_poizon'=>'Заказано на Poizon','in_transit_china'=>'В пути из Китая','customs'=>'Таможня','arrived_warehouse'=>'Прибыл на склад'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= ($model->china_delivery_status ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Дата прибытия на склад</div>
                                <input type="date" class="form-control" value="<?= Html::encode($model->warehouse_arrival_date ?? '') ?>" onchange="saveField('warehouse_arrival_date', this.value)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ДОСТАВКА 2 — Местная (РБ → Клиент) -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-truck"></i> Доставка по РБ</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Служба доставки</div>
                                <select class="form-select" onchange="saveField('delivery_method', this.value)">
                                    <?php foreach(['europochta'=>'Европочта','belpochta'=>'Белпочта','cdek'=>'СДЭК','courier_minsk'=>'Курьер Минск','pickup'=>'Самовывоз'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= ($model->delivery_method ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Адрес доставки</div>
                                <input type="text" class="form-control" value="<?= Html::encode($model->delivery_address ?? '') ?>" onchange="saveField('delivery_address', this.value)" placeholder="Город, улица, дом">
                            </div>
                            <div class="info-item">
                                <div class="info-label">Трек РБ</div>
                                <div style="display:flex;gap:0.5rem;align-items:center">
                                    <input type="text" class="form-control" id="local-track-input" value="<?= Html::encode($model->local_track_number ?? '') ?>" placeholder="Локальный трек">
                                    <button class="btn btn-outline-primary" onclick="saveField('local_track_number', document.getElementById('local-track-input').value)"><i class="bi bi-save"></i></button>
                                    <button class="btn btn-outline-secondary" onclick="checkTrack(document.getElementById('local-track-input').value, this)"><i class="bi bi-search"></i></button>
                                </div>
                                <div id="local-track-result" style="margin-top:0.5rem;font-size:0.8125rem;color:#6b7280;"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Распечатать бланк</div>
                                <a href="<?= Url::to(['/admin/order/pdf', 'id' => $model->id]) ?>" target="_blank" class="btn btn-outline-secondary">
                                    <i class="bi bi-printer"></i> Печать бланка
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ЗАМЕТКИ КОМАНДЫ -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-chat-square-text"></i> Заметки команды</h3>
                    </div>
                    <div class="card-body">
                        <div id="order-notes-list" style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1rem;">
                            <?php foreach (($model->notes ?? []) as $note): ?>
                            <div class="order-note-item" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:0.75rem">
                                <div style="display:flex;justify-content:space-between;margin-bottom:0.3rem">
                                    <strong style="font-size:0.8125rem"><?= Html::encode($note->author->username ?? 'Система') ?></strong>
                                    <span style="font-size:0.75rem;color:#9ca3af"><?= Yii::$app->formatter->asDatetime($note->created_at) ?></span>
                                </div>
                                <p style="margin:0;font-size:0.875rem"><?= Html::encode($note->text) ?></p>
                            </div>
                            <?php endforeach ?>
                            <?php if (empty($model->notes)): ?>
                            <p style="color:#9ca3af;font-size:0.875rem;margin:0">Заметок пока нет.</p>
                            <?php endif ?>
                        </div>
                        <div style="display:flex;gap:0.5rem">
                            <textarea id="new-note-text" class="form-control" rows="2" placeholder="Добавить заметку..." style="resize:vertical"></textarea>
                            <button class="btn btn-primary" onclick="addOrderNote(<?= $model->id ?>)" style="white-space:nowrap">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- МОИСКЛАД -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-cloud-check"></i> МойСклад</h3>
                    </div>
                    <div class="card-body">
                        <?php $msStatus = $model->moysklad_id ?? null; ?>
                        <?php if ($msStatus): ?>
                        <p style="color:#008060;font-size:0.875rem"><i class="bi bi-check-circle"></i> Передан в МойСклад</p>
                        <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right"></i> Открыть документ</a>
                        <?php else: ?>
                        <p style="color:#9ca3af;font-size:0.875rem"><i class="bi bi-circle"></i> Не передан</p>
                        <?php endif ?>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="syncMoysklad(<?= $model->id ?>)">
                            <i class="bi bi-arrow-repeat"></i> Синхронизировать
                        </button>
                        <span id="ms-sync-result" style="margin-left:0.5rem;font-size:0.8125rem;"></span>
                    </div>
                </div>

        </div>

        <div class="order-sidebar">
            <!-- Статус -->
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-toggles"></i> Управление статусом
                </div>
                <div class="sidebar-body">
                    <div class="mb-3">
                        <div class="info-label">Текущий статус</div>
                        <div class="badge bg-primary" style="font-size:0.8rem;"><?= $model->getStatusLabel() ?></div>
                    </div>

                    <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Новый статус</label>
                            <select name="status" class="form-select">
                                <?php
                                $statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();
                                foreach ($statuses as $key => $label):
                                ?>
                                    <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Опционально..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Сохранить</button>
                    </form>
                </div>
            </div>

            <!-- Назначение логиста (только для админа) -->
            <?php if ($user->isAdmin()): ?>
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-truck"></i> Логистика
                </div>
                <div class="sidebar-body">
                    <p><strong>Текущий логист:</strong><br>
                    <?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></p>

                    <form method="post" action="<?= Url::to(['/admin/order/assign-logist', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Назначить логиста</label>
                            <select name="logist_id" class="form-select">
                                <option value="">Не назначен</option>
                                <?php
                                try {
                                    $logists = \app\backend\modules\admin\models\User::find()->where(['role' => 'logist'])->all();
                                } catch (\Exception $e) {
                                    $logists = [];
                                }
                                foreach ($logists as $logist):
                                ?>
                                    <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                        <?= Html::encode($logist->username) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">Назначить</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Подтверждение оплаты -->
            <?php if ($model->payment_proof): ?>
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-credit-card"></i> Подтверждение оплаты
                </div>
                <div class="sidebar-body">
                    <p class="text-muted small mb-2">
                        Загружено: <?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?>
                    </p>
                    <a href="<?= $model->payment_proof ?>" target="_blank" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-image"></i> Просмотреть файл
                    </a>

                    <?php if ($model->offer_accepted): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="bi bi-check-circle"></i> Оферта принята
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модалка истории -->
<div class="history-modal" id="historyModal">
    <div class="history-modal-content">
        <div class="history-modal-header">
            <h3><i class="bi bi-clock-history"></i> История изменений</h3>
            <button class="history-close-btn" type="button" onclick="closeHistoryModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="history-modal-body">
            <?php if (!empty($model->history)): ?>
                <div class="timeline">
                    <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item mb-4 <?= $history->new_status === $model->status ? 'active' : '' ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-title"><?= $history->getNewStatusLabel() ?></div>
                                <div class="timeline-meta">
                                    <?= Yii::$app->formatter->asDatetime($history->created_at) ?>
                                    <?php if ($history->changer): ?>
                                        • <?= Html::encode($history->changer->username) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($history->comment): ?>
                                    <div class="mt-1"><?= Html::encode($history->comment) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">История изменений пока пуста.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// PHP-dependent JS: saveField, js-flag handler, add-item-edit
$_csrfParam = Yii::$app->request->csrfParam;
$_csrfToken = Yii::$app->request->csrfToken;
$_updateFieldUrl = \yii\helpers\Url::to(['/admin/order/update-field', 'id' => $model->id]);
$_itemCount = count($model->orderItems);
$_modelId = $model->id;
$this->registerJs(
    'window.saveField = function(field, value) {'
    . '    fetch("/admin/order/save-field", {'
    . '        method: "POST",'
    . '        headers: {"Content-Type":"application/json","X-CSRF-Token": document.querySelector("meta[name=csrf-token]") ? document.querySelector("meta[name=csrf-token]").content : ""},'
    . '        body: JSON.stringify({id: ' . $_modelId . ', field: field, value: value})'
    . '    }).then(function(r){ return r.json(); }).then(function(d){'
    . '        if(d.success) { var el=document.createElement("span"); el.textContent="\u2713"; el.style.cssText="color:var(--admin-accent,#008060);font-size:0.75rem;margin-left:0.25rem"; setTimeout(function(){el.remove();},2000); }'
    . '    });'
    . '};'
    . 'document.querySelectorAll(".js-flag").forEach(function(flag) {'
    . '    flag.addEventListener("change", function() {'
    . '        var field = this.dataset.field;'
    . '        var value = this.checked ? 1 : 0;'
    . '        var fd = new FormData();'
    . '        fd.append("field", field); fd.append("value", value);'
    . '        fd.append("' . $_csrfParam . '", "' . $_csrfToken . '");'
    . '        fetch("' . $_updateFieldUrl . '", {method:"POST",body:fd})'
    . '            .then(function(r){return r.json();})'
    . '            .then(function(data){if(data.success && window.showSaveIndicator){showSaveIndicator("Флаг сохранён");}});'
    . '    });'
    . '});'
    . '(function(){'
    . '    var addItemBtn = document.getElementById("add-item-edit");'
    . '    var itemIndex = ' . $_itemCount . ';'
    . '    if (addItemBtn) {'
    . '        addItemBtn.addEventListener("click", function() {'
    . '            var newItem = '<div class="order-item row mb-3">''
    . '                + '<div class="col-md-5"><label class="form-label">Название товара</label>''
    . '                + '<input type="text" class="form-control" name="OrderItem[' + itemIndex + '][product_name]"></div>''
    . '                + '<div class="col-md-2"><label class="form-label">Количество</label>''
    . '                + '<input type="number" class="form-control" name="OrderItem[' + itemIndex + '][quantity]" value="1" min="1"></div>''
    . '                + '<div class="col-md-3"><label class="form-label">Цена (BYN)</label>''
    . '                + '<input type="number" step="0.01" class="form-control" name="OrderItem[' + itemIndex + '][price]"></div>''
    . '                + '<div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger remove-item">Удалить</button></div>''
    . '                + "</div>";'
    . '            var container = document.getElementById("order-items-edit");'
    . '            if (container) { container.insertAdjacentHTML("beforeend", newItem); itemIndex++; }'
    . '        });'
    . '    }'
    . '})();',
    \yii\web\View::POS_END
);
?>
