<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'МойСклад — интеграция';

$csrfMeta = '<meta name="csrf-token" content="' . Yii::$app->request->csrfToken . '">';
$this->params['head'] = $csrfMeta;

// Operation → pill color mapping (used in overview + log tabs)
$opColors = ['push_all' => 'blue', 'pull' => 'green', 'periodic_sync' => 'yellow'];

// Type → pill color mapping (used in field mapping tabs)
$typeColors = ['string' => 'gray', 'money' => 'green', 'bool' => 'blue', 'json' => 'yellow', 'datetime' => 'blue', 'number' => 'gray', 'text' => 'gray'];
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge admin-badge-secondary" id="ms-conn-badge">Не проверено</span>',
];
?>

<style>
/* ─── Tab pills ─────────────────────────────────────────────── */
.ms-tabs {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: var(--admin-bg-secondary, #f3f4f6);
    border-radius: 10px;
    margin-bottom: 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.ms-tab {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    color: var(--admin-text-secondary, #6b7280);
    background: transparent;
    border: none;
    transition: all .15s;
}
.ms-tab:hover { background: var(--admin-surface-hover, rgba(0,0,0,.04)); }
.ms-tab.active {
    background: var(--admin-bg, #fff);
    color: var(--admin-text, #111);
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
[data-theme="dark"] .ms-tab.active { box-shadow: 0 1px 3px rgba(0,0,0,.3); }

.ms-panel { display: none; }
.ms-panel.active { display: block; }

/* ─── Status pills ──────────────────────────────────────────── */
.ms-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.5;
}
.ms-pill-green  { background: #d1fae5; color: #065f46; }
.ms-pill-gray   { background: #f3f4f6; color: #6b7280; }
.ms-pill-blue   { background: #dbeafe; color: #1e40af; }
.ms-pill-yellow { background: #fef3c7; color: #92400e; }
.ms-pill-red    { background: #fee2e2; color: #991b1b; }
[data-theme="dark"] .ms-pill-green  { background: rgba(16,185,129,.2); color: #6ee7b7; }
[data-theme="dark"] .ms-pill-gray   { background: rgba(107,114,128,.2); color: #9ca3af; }
[data-theme="dark"] .ms-pill-blue   { background: rgba(59,130,246,.2); color: #93c5fd; }
[data-theme="dark"] .ms-pill-yellow { background: rgba(245,158,11,.2); color: #fcd34d; }
[data-theme="dark"] .ms-pill-red    { background: rgba(239,68,68,.2); color: #fca5a5; }

/* ─── Field mapping indicator ───────────────────────────────── */
.ms-field-dot {
    width: 8px; height: 8px; border-radius: 50%; display: inline-block;
    margin-right: 6px; flex-shrink: 0;
}
.ms-field-dot.active   { background: #10b981; }
.ms-field-dot.inactive { background: #d1d5db; }
[data-theme="dark"] .ms-field-dot.inactive { background: #4b5563; }

/* ─── Progress bar ──────────────────────────────────────────── */
.ms-progress { height: 6px; background: var(--admin-bg-secondary, #e5e7eb); border-radius: 3px; overflow: hidden; }
.ms-progress-bar { height: 100%; background: var(--admin-primary, #3b82f6); border-radius: 3px; transition: width .5s; }

/* ─── Stats grid ────────────────────────────────────────────── */
.ms-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 20px; }
.ms-stat {
    text-align: center; padding: 16px 8px;
    background: var(--admin-bg-secondary, #f9fafb);
    border-radius: 10px;
}
.ms-stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.2; }
.ms-stat-label { font-size: 12px; color: var(--admin-text-secondary, #6b7280); margin-top: 4px; }

/* ─── Mapping grid ──────────────────────────────────────────── */
.ms-map-row {
    display: grid;
    grid-template-columns: 180px 24px 1fr;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
}
.ms-map-row:last-child { border-bottom: none; }
.ms-map-arrow { color: var(--admin-text-secondary); text-align: center; }

/* ─── Log filters ───────────────────────────────────────────── */
.ms-log-filters {
    display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;
}
.ms-log-filters select, .ms-log-filters input {
    font-size: 12px; padding: 4px 8px;
    border: 1px solid var(--admin-border, #d1d5db);
    border-radius: 6px; background: var(--admin-bg, #fff);
    color: var(--admin-text, #111);
}

/* ─── Field table ───────────────────────────────────────────── */
.ms-fields-table { width: 100%; font-size: 13px; }
.ms-fields-table th {
    text-align: left; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: .5px;
    color: var(--admin-text-secondary); padding: 6px 10px;
    border-bottom: 2px solid var(--admin-border, #e5e7eb);
}
.ms-fields-table td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--admin-border, #f3f4f6);
}
.ms-fields-table tr:hover td { background: var(--admin-surface-hover, rgba(0,0,0,.02)); }

/* ─── Mapping select ────────────────────────────────────────── */
.ms-map-select {
    font-size: 12px;
    padding: 4px 8px;
    border: 1px solid var(--admin-border, #d1d5db);
    border-radius: 6px;
    background: var(--admin-bg, #fff);
    color: var(--admin-text, #111);
    width: 100%;
    min-width: 120px;
    cursor: pointer;
}
.ms-map-select:focus {
    outline: none;
    border-color: var(--admin-primary, #3b82f6);
    box-shadow: 0 0 0 2px rgba(59,130,246,.15);
}
.ms-map-select.is-mapped { border-color: #10b981; background: rgba(16,185,129,.05); }
[data-theme="dark"] .ms-map-select { background: var(--admin-bg-secondary, #1f2937); }
[data-theme="dark"] .ms-map-select.is-mapped { background: rgba(16,185,129,.1); }
</style>

<!-- ═══════════════════════════════════════════════════════════════
     TAB NAVIGATION
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-tabs" id="ms-tabs">
    <button class="ms-tab active" data-tab="overview"><i class="bi bi-speedometer2"></i> Обзор</button>
    <button class="ms-tab" data-tab="status-map"><i class="bi bi-diagram-2"></i> Маппинг статусов</button>
    <button class="ms-tab" data-tab="order-fields"><i class="bi bi-cart3"></i> Поля заказов</button>
    <button class="ms-tab" data-tab="product-fields"><i class="bi bi-box-seam"></i> Поля товаров</button>
    <button class="ms-tab" data-tab="customer-fields"><i class="bi bi-people"></i> Поля клиентов</button>
    <button class="ms-tab" data-tab="settings"><i class="bi bi-gear"></i> Настройки</button>
    <button class="ms-tab" data-tab="log"><i class="bi bi-journal-text"></i> Лог</button>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 1 — ОБЗОР (Dashboard)
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel active" id="panel-overview">

    <!-- Stats -->
    <div class="ms-stats">
        <div class="ms-stat">
            <div class="ms-stat-value" style="color:var(--admin-primary)"><?= (int)$syncStats['synced'] ?></div>
            <div class="ms-stat-label">Синхронизировано</div>
        </div>
        <div class="ms-stat">
            <div class="ms-stat-value" style="color:#d97706"><?= (int)$syncStats['unsynced'] ?></div>
            <div class="ms-stat-label">Не синхронизировано</div>
        </div>
        <div class="ms-stat">
            <div class="ms-stat-value" style="color:var(--admin-text-secondary)"><?= (int)$syncStats['total'] ?></div>
            <div class="ms-stat-label">Всего заказов</div>
        </div>
        <div class="ms-stat">
            <div class="ms-stat-value" id="ms-sync-pct" style="color:#10b981">
                <?= $syncStats['total'] > 0 ? round($syncStats['synced'] / $syncStats['total'] * 100) : 0 ?>%
            </div>
            <div class="ms-stat-label">Покрытие</div>
        </div>
    </div>

    <!-- Sync progress bar -->
    <div style="margin-bottom:20px">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--admin-text-secondary);margin-bottom:4px">
            <span>Прогресс синхронизации</span>
            <span><?= (int)$syncStats['synced'] ?> / <?= (int)$syncStats['total'] ?></span>
        </div>
        <div class="ms-progress">
            <div class="ms-progress-bar" style="width:<?= $syncStats['total'] > 0 ? round($syncStats['synced'] / $syncStats['total'] * 100) : 0 ?>%"></div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-lightning"></i> Быстрые действия</h2>
            <span style="font-size:12px;color:var(--admin-text-secondary)">
                Последняя синхронизация: <span id="ms-last-sync"><?= $lastSyncAt ? Html::encode($lastSyncAt) : 'никогда' ?></span>
            </span>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                <button class="admin-btn admin-btn-primary" onclick="msPeriodicSync()">
                    <i class="bi bi-arrow-repeat"></i> Синхронизировать (60 мин)
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="msPushAll()">
                    <i class="bi bi-upload"></i> Push все → МС
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="msPull()">
                    <i class="bi bi-download"></i> Pull из МС
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="msTestConn()">
                    <i class="bi bi-plug"></i> Проверить соединение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="msSyncInfo()">
                    <i class="bi bi-info-circle"></i> Статусы МС
                </button>
            </div>
            <div id="ms-sync-result" style="font-size:13px"></div>
        </div>
    </div>

    <!-- Recent sync log (mini) -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-clock-history"></i> Последние операции</h2>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="switchTab('log')">Все записи →</button>
        </div>
        <div class="admin-card-body">
            <?php if (empty($syncLog)): ?>
            <p style="color:var(--admin-text-secondary);font-size:13px">Синхронизаций ещё не было</p>
            <?php else: ?>
            <table class="admin-table" style="font-size:12px">
                <thead><tr><th style="width:140px">Время</th><th style="width:110px">Операция</th><th>Результат</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($syncLog, 0, 5) as $entry): ?>
                <tr>
                    <td style="color:var(--admin-text-secondary)"><?= Html::encode($entry['at'] ?? '') ?></td>
                    <td>
                        <?php $op = $entry['operation'] ?? ''; $pillClass = 'ms-pill-' . ($opColors[$op] ?? 'gray'); ?>
                        <span class="ms-pill <?= $pillClass ?>"><?= Html::encode($op) ?></span>
                    </td>
                    <td>
                        <?php $r = $entry['result'] ?? []; ?>
                        <?php if (isset($r['pushed'])): ?>
                            Отправлено: <?= $r['pushed'] ?>, ошибок: <?= $r['errors'] ?? 0 ?>
                        <?php elseif (isset($r['synced'])): ?>
                            Синхронизировано: <?= $r['synced'] ?>, всего: <?= $r['ms_total'] ?? '?' ?>
                        <?php else: ?>
                            <?= Html::encode(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 2 — МАППИНГ СТАТУСОВ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-status-map">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-diagram-2"></i> Маппинг статусов: Сайт ↔ МойСклад</h2>
            <span style="font-size:12px;color:var(--admin-text-secondary)">Сопоставьте статусы заказов на сайте с названиями статусов в МойСклад</span>
        </div>
        <div class="admin-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:2px">
                <?php foreach ($siteStatuses as $key => $label): ?>
                <div class="ms-map-row">
                    <div>
                        <span class="ms-pill ms-pill-blue" style="font-size:12px"><?= Html::encode($key) ?></span>
                        <div style="font-size:11px;color:var(--admin-text-secondary);margin-top:2px"><?= Html::encode($label) ?></div>
                    </div>
                    <div class="ms-map-arrow">→</div>
                    <div>
                        <input type="text" class="admin-input ms-status-input" data-status="<?= $key ?>"
                               value="<?= Html::encode($statusMapping[$key] ?? '') ?>"
                               placeholder="Название статуса в МС"
                               style="font-size:13px;width:100%">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:8px;align-items:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--admin-border, #e5e7eb)">
                <button class="admin-btn admin-btn-primary" onclick="msSaveStatusMapping()">
                    <i class="bi bi-save"></i> Сохранить маппинг
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="msLoadMsStatuses()">
                    <i class="bi bi-cloud-download"></i> Загрузить статусы из МС
                </button>
                <span id="ms-status-map-result" style="font-size:13px"></span>
            </div>

            <div id="ms-states-list" style="margin-top:12px"></div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 3 — МАППИНГ ПОЛЕЙ ЗАКАЗОВ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-order-fields">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-cart3"></i> Поля заказов из МойСклад</h2>
            <span style="font-size:12px;color:var(--admin-text-secondary)">
                <?= count($orderMsFields) ?> полей · таблица <code>orders</code>
            </span>
        </div>
        <div class="admin-card-body" style="overflow-x:auto">
            <table class="ms-fields-table">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th style="width:220px">МС поле</th>
                        <th>Описание</th>
                        <th style="width:80px">Тип</th>
                        <th style="width:200px">Наше поле (orders)</th>
                    </tr>
                </thead>
                <tbody id="order-fields-tbody">
                <?php foreach ($orderMsFields as $col => $info): ?>
                <tr>
                    <td><span class="ms-field-dot <?= !empty($savedMappingOrders[$col]) ? 'active' : 'inactive' ?>" title="<?= !empty($savedMappingOrders[$col]) ? 'Смаппировано: ' . Html::encode($savedMappingOrders[$col]) : 'Не смаппировано' ?>"></span></td>
                    <td><code style="background:var(--admin-bg-secondary);padding:2px 6px;border-radius:4px;font-size:12px"><?= Html::encode($col) ?></code></td>
                    <td><?= Html::encode($info['label']) ?></td>
                    <td><span class="ms-pill ms-pill-<?= $typeColors[$info['type']] ?? 'gray' ?>"><?= $info['type'] ?></span></td>
                    <td>
                        <select class="ms-map-select <?= !empty($savedMappingOrders[$col]) ? 'is-mapped' : '' ?>"
                                data-ms-field="<?= Html::encode($col) ?>"
                                data-entity="orders"
                                data-saved="<?= Html::encode($savedMappingOrders[$col] ?? '') ?>">
                            <option value="">— не выбрано —</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid var(--admin-border,#e5e7eb)">
            <button class="admin-btn admin-btn-primary" onclick="msSaveFieldMapping('orders')">
                <i class="bi bi-save"></i> Сохранить маппинг заказов
            </button>
            <span id="ms-mapping-orders-result" style="font-size:13px"></span>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 4 — МАППИНГ ПОЛЕЙ ТОВАРОВ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-product-fields">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-box-seam"></i> Поля товаров из МойСклад</h2>
            <span style="font-size:12px;color:var(--admin-text-secondary)">
                <?= count($productMsFields) ?> полей · таблица <code>products</code>
            </span>
        </div>
        <div class="admin-card-body" style="overflow-x:auto">
            <table class="ms-fields-table">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th style="width:220px">МС поле</th>
                        <th>Описание</th>
                        <th style="width:80px">Тип</th>
                        <th style="width:200px">Наше поле (products)</th>
                    </tr>
                </thead>
                <tbody id="product-fields-tbody">
                <?php foreach ($productMsFields as $col => $info): ?>
                <tr>
                    <td><span class="ms-field-dot <?= !empty($savedMappingProducts[$col]) ? 'active' : 'inactive' ?>" title="<?= !empty($savedMappingProducts[$col]) ? 'Смаппировано: ' . Html::encode($savedMappingProducts[$col]) : 'Не смаппировано' ?>"></span></td>
                    <td><code style="background:var(--admin-bg-secondary);padding:2px 6px;border-radius:4px;font-size:12px"><?= Html::encode($col) ?></code></td>
                    <td><?= Html::encode($info['label']) ?></td>
                    <td><span class="ms-pill ms-pill-<?= $typeColors[$info['type']] ?? 'gray' ?>"><?= $info['type'] ?></span></td>
                    <td>
                        <select class="ms-map-select <?= !empty($savedMappingProducts[$col]) ? 'is-mapped' : '' ?>"
                                data-ms-field="<?= Html::encode($col) ?>"
                                data-entity="products"
                                data-saved="<?= Html::encode($savedMappingProducts[$col] ?? '') ?>">
                            <option value="">— не выбрано —</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid var(--admin-border,#e5e7eb)">
            <button class="admin-btn admin-btn-primary" onclick="msSaveFieldMapping('products')">
                <i class="bi bi-save"></i> Сохранить маппинг товаров
            </button>
            <span id="ms-mapping-products-result" style="font-size:13px"></span>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 5 — МАППИНГ ПОЛЕЙ КЛИЕНТОВ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-customer-fields">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-people"></i> Поля клиентов из МойСклад</h2>
            <span style="font-size:12px;color:var(--admin-text-secondary)">
                <?= count($customerMsFields) ?> полей · таблица <code>customers</code>
            </span>
        </div>
        <div class="admin-card-body" style="overflow-x:auto">
            <table class="ms-fields-table">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th style="width:220px">МС поле</th>
                        <th>Описание</th>
                        <th style="width:80px">Тип</th>
                        <th style="width:200px">Наше поле (customers)</th>
                    </tr>
                </thead>
                <tbody id="customer-fields-tbody">
                <?php foreach ($customerMsFields as $col => $info): ?>
                <tr>
                    <td><span class="ms-field-dot <?= !empty($savedMappingCustomers[$col]) ? 'active' : 'inactive' ?>" title="<?= !empty($savedMappingCustomers[$col]) ? 'Смаппировано: ' . Html::encode($savedMappingCustomers[$col]) : 'Не смаппировано' ?>"></span></td>
                    <td><code style="background:var(--admin-bg-secondary);padding:2px 6px;border-radius:4px;font-size:12px"><?= Html::encode($col) ?></code></td>
                    <td><?= Html::encode($info['label']) ?></td>
                    <td><span class="ms-pill ms-pill-<?= $typeColors[$info['type']] ?? 'gray' ?>"><?= $info['type'] ?></span></td>
                    <td>
                        <select class="ms-map-select <?= !empty($savedMappingCustomers[$col]) ? 'is-mapped' : '' ?>"
                                data-ms-field="<?= Html::encode($col) ?>"
                                data-entity="customers"
                                data-saved="<?= Html::encode($savedMappingCustomers[$col] ?? '') ?>">
                            <option value="">— не выбрано —</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid var(--admin-border,#e5e7eb)">
            <button class="admin-btn admin-btn-primary" onclick="msSaveFieldMapping('customers')">
                <i class="bi bi-save"></i> Сохранить маппинг клиентов
            </button>
            <span id="ms-mapping-customers-result" style="font-size:13px"></span>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 6 — НАСТРОЙКИ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-settings">

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px">

        <!-- Credentials card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-key"></i> Авторизация</h2>
            </div>
            <div class="admin-card-body">
                <div style="margin-bottom:12px">
                    <label class="admin-label">API-ключ (Bearer token)</label>
                    <input type="password" class="admin-input" id="ms-api-key"
                           value="<?= Html::encode(Yii::$app->settings->get('moysklad','api_key','')) ?>"
                           placeholder="Токен из МойСклад → Профиль → Безопасность">
                    <div style="font-size:11px;color:var(--admin-text-secondary);margin-top:4px">
                        Если указан — используется вместо логина/пароля
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                    <div>
                        <label class="admin-label">Логин</label>
                        <input type="text" class="admin-input" id="ms-login"
                               value="<?= Html::encode(Yii::$app->settings->get('moysklad','login','')) ?>">
                    </div>
                    <div>
                        <label class="admin-label">Пароль</label>
                        <input type="password" class="admin-input" id="ms-password"
                               value="<?= Html::encode(Yii::$app->settings->get('moysklad','password','')) ?>">
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="admin-btn admin-btn-secondary" onclick="msTestConn()">
                        <i class="bi bi-plug"></i> Проверить
                    </button>
                    <button class="admin-btn admin-btn-primary" onclick="msSaveCreds()">
                        <i class="bi bi-save"></i> Сохранить
                    </button>
                </div>
                <div id="ms-creds-result" style="margin-top:8px;font-size:13px"></div>
            </div>
        </div>

        <!-- Sync toggles card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-toggles"></i> Синхронизация</h2>
            </div>
            <div class="admin-card-body">
                <div style="display:grid;gap:10px;margin-bottom:16px">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-toggle-orders"
                            <?= Yii::$app->settings->get('moysklad','sync_orders','1') === '1' ? 'checked' : '' ?>>
                        <i class="bi bi-cart3"></i> Синхронизация заказов
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-toggle-products"
                            <?= Yii::$app->settings->get('moysklad','sync_products','1') === '1' ? 'checked' : '' ?>>
                        <i class="bi bi-box-seam"></i> Синхронизация товаров
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-toggle-customers"
                            <?= Yii::$app->settings->get('moysklad','sync_customers','1') === '1' ? 'checked' : '' ?>>
                        <i class="bi bi-people"></i> Синхронизация клиентов
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-toggle-stock"
                            <?= Yii::$app->settings->get('moysklad','sync_stock','1') === '1' ? 'checked' : '' ?>>
                        <i class="bi bi-box2"></i> Синхронизация остатков
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-toggle-prices"
                            <?= Yii::$app->settings->get('moysklad','sync_prices','1') === '1' ? 'checked' : '' ?>>
                        <i class="bi bi-currency-exchange"></i> Синхронизация цен
                    </label>
                </div>

                <div style="display:flex;gap:16px;margin-bottom:16px;padding:12px;background:var(--admin-bg-secondary);border-radius:8px">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-dir-ms-to-site"
                            <?= Yii::$app->settings->get('moysklad','sync_ms_to_site','1') ? 'checked' : '' ?>>
                        <i class="bi bi-cloud-download"></i> МС → Сайт
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="ms-dir-site-to-ms"
                            <?= Yii::$app->settings->get('moysklad','sync_site_to_ms','1') ? 'checked' : '' ?>>
                        <i class="bi bi-cloud-upload"></i> Сайт → МС
                    </label>
                </div>

                <div style="margin-bottom:16px">
                    <label class="admin-label">Интервал авто-синхронизации (минуты)</label>
                    <select class="admin-input" id="ms-auto-interval" style="width:auto">
                        <?php
                        $curInterval = Yii::$app->settings->get('moysklad','auto_sync_interval','0');
                        foreach ([0 => 'Выключена', 15 => '15 мин', 30 => '30 мин', 60 => '1 час', 120 => '2 часа', 360 => '6 часов', 720 => '12 часов', 1440 => '24 часа'] as $v => $l):
                        ?>
                        <option value="<?= $v ?>" <?= $curInterval == $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="admin-btn admin-btn-primary" onclick="msSaveSettings()">
                    <i class="bi bi-save"></i> Сохранить настройки
                </button>
                <span id="ms-settings-result" style="font-size:13px;margin-left:8px"></span>
            </div>
        </div>

        <!-- Webhooks card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-broadcast"></i> Вебхуки</h2>
                <span id="ms-wh-status-badge" class="ms-pill ms-pill-gray">Проверяем...</span>
            </div>
            <div class="admin-card-body">
                <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                    URL: <code style="background:var(--admin-bg-secondary);padding:2px 6px;border-radius:4px;font-size:12px"><?= Html::encode($webhookUrl) ?></code>
                </p>
                <div style="display:flex;gap:8px;margin-bottom:12px">
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="msListWebhooks()">
                        <i class="bi bi-list"></i> Список
                    </button>
                    <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="msRegisterWebhook()">
                        <i class="bi bi-plus-circle"></i> Зарегистрировать
                    </button>
                </div>
                <div id="ms-webhooks-list"></div>
                <div id="ms-webhook-result" style="margin-top:8px;font-size:13px"></div>
            </div>
        </div>

        <!-- Import card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-cloud-arrow-down"></i> Импорт из МойСклад</h2>
            </div>
            <div class="admin-card-body">
                <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                    Массовый импорт данных из МойСклад в базу сайта
                </p>
                <div style="display:grid;gap:8px">
                    <button class="admin-btn admin-btn-secondary" onclick="msPull()">
                        <i class="bi bi-download"></i> Импорт заказов из МС
                    </button>
                    <button class="admin-btn admin-btn-secondary" onclick="msPushAll()">
                        <i class="bi bi-upload"></i> Экспорт всех заказов в МС
                    </button>
                    <button class="admin-btn admin-btn-secondary" onclick="msPeriodicSync()">
                        <i class="bi bi-arrow-repeat"></i> Периодическая синхронизация (60 мин)
                    </button>
                </div>
                <div id="ms-import-result" style="margin-top:8px;font-size:13px"></div>
            </div>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     TAB 7 — ЛОГ
     ═══════════════════════════════════════════════════════════ -->
<div class="ms-panel" id="panel-log">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-journal-text"></i> Лог синхронизации</h2>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="msRefreshLog()">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
        </div>
        <div class="admin-card-body">
            <!-- Filters -->
            <div class="ms-log-filters">
                <select id="ms-log-filter-op">
                    <option value="">Все операции</option>
                    <option value="push_all">push_all</option>
                    <option value="pull">pull</option>
                    <option value="periodic_sync">periodic_sync</option>
                </select>
                <input type="text" id="ms-log-filter-search" placeholder="Поиск...">
            </div>

            <div id="ms-log-table-wrap">
                <?php if (empty($syncLog)): ?>
                <p style="color:var(--admin-text-secondary);font-size:13px">Записей нет</p>
                <?php else: ?>
                <table class="admin-table" style="font-size:12px" id="ms-log-table">
                    <thead>
                        <tr>
                            <th style="width:150px">Время</th>
                            <th style="width:120px">Операция</th>
                            <th>Результат</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($syncLog as $entry): ?>
                    <tr data-op="<?= Html::encode($entry['operation'] ?? '') ?>">
                        <td style="color:var(--admin-text-secondary)"><?= Html::encode($entry['at'] ?? '') ?></td>
                        <td>
                            <?php
                            $op = $entry['operation'] ?? '';
                            $pillClass = 'ms-pill-' . ($opColors[$op] ?? 'gray');
                            ?>
                            <span class="ms-pill <?= $pillClass ?>"><?= Html::encode($op) ?></span>
                        </td>
                        <td>
                            <?php $r = $entry['result'] ?? []; ?>
                            <?php if (isset($r['pushed'])): ?>
                                <span class="ms-pill ms-pill-green">Отправлено: <?= $r['pushed'] ?></span>
                                <?php if (($r['errors'] ?? 0) > 0): ?>
                                    <span class="ms-pill ms-pill-red">Ошибок: <?= $r['errors'] ?></span>
                                <?php endif; ?>
                                <span style="color:var(--admin-text-secondary);font-size:11px">из <?= $r['total'] ?? '?' ?></span>
                            <?php elseif (isset($r['synced'])): ?>
                                <span class="ms-pill ms-pill-green">Синхронизировано: <?= $r['synced'] ?></span>
                                <?php if (isset($r['new_links']) && $r['new_links'] > 0): ?>
                                    <span class="ms-pill ms-pill-blue">Новых связей: <?= $r['new_links'] ?></span>
                                <?php endif; ?>
                                <span style="color:var(--admin-text-secondary);font-size:11px">всего в МС: <?= $r['ms_total'] ?? '?' ?></span>
                            <?php else: ?>
                                <code style="font-size:11px;word-break:break-all"><?= Html::encode(json_encode($r, JSON_UNESCAPED_UNICODE)) ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════ -->
<script>
// ─── Tabs ────────────────────────────────────────────────────────
document.querySelectorAll('.ms-tab').forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
});

function switchTab(name) {
    document.querySelectorAll('.ms-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    document.querySelectorAll('.ms-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + name));
    // Save to URL hash
    history.replaceState(null, '', '#' + name);
}

// Restore tab from hash
(function() {
    const hash = location.hash.replace('#', '');
    if (hash && document.getElementById('panel-' + hash)) switchTab(hash);
})();


// ─── API helpers ─────────────────────────────────────────────────
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function apiPost(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf,'X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify(body)
    }).then(r => r.json());
}
function apiGet(url) {
    return fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r => r.json());
}
function showResult(elId, data) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = data.success ? ('✓ ' + (data.message || 'OK')) : ('✗ ' + (data.message || 'Ошибка'));
    el.style.color = data.success ? '#065f46' : '#991b1b';
}


// ─── Connection test ─────────────────────────────────────────────
function msTestConn() {
    showResult('ms-creds-result', {success: null, message: 'Проверяем...'});
    document.getElementById('ms-creds-result').style.color = '#6b7280';
    apiPost('<?= Url::to(['/admin/moysklad/test-connection']) ?>', {}).then(d => {
        showResult('ms-creds-result', d);
        const badge = document.getElementById('ms-conn-badge');
        badge.textContent = d.success ? 'Подключено' : 'Ошибка';
        badge.className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-danger');
    });
}


// ─── Save credentials ────────────────────────────────────────────
function msSaveCreds() {
    apiPost('<?= Url::to(['/admin/moysklad/save-credentials']) ?>', {
        api_key:  document.getElementById('ms-api-key').value,
        login:    document.getElementById('ms-login').value,
        password: document.getElementById('ms-password').value,
    }).then(d => showResult('ms-creds-result', d));
}


// ─── Sync operations ─────────────────────────────────────────────
function msPushAll() {
    const el = document.getElementById('ms-sync-result') || document.getElementById('ms-import-result');
    el.textContent = '⏳ Отправляем заказы в МойСклад...';
    el.style.color = '#6b7280';
    apiPost('<?= Url::to(['/admin/moysklad/push-all']) ?>', {}).then(d => {
        if (d.success) {
            el.innerHTML =
                `✓ Отправлено: <b>${d.pushed}</b>, ошибок: <b>${d.errors}</b> из ${d.total}<br>` +
                (d.log || []).slice(0,5).map(l =>
                    `<span style="font-size:11px;color:${l.status==='pushed'?'#065f46':'#991b1b'}">${l.number||l.id}: ${l.status}${l.message?' — '+l.message:''}</span>`
                ).join('<br>');
            el.style.color = '#065f46';
        } else {
            showResult(el.id, d);
        }
    });
}

function msPull() {
    const el = document.getElementById('ms-sync-result') || document.getElementById('ms-import-result');
    el.textContent = '⏳ Загружаем из МойСклад...';
    el.style.color = '#6b7280';
    apiPost('<?= Url::to(['/admin/moysklad/pull']) ?>', {limit: 100, offset: 0}).then(d => {
        if (d.success) {
            el.innerHTML =
                `✓ Обновлено: <b>${d.synced}</b>, новых связей: <b>${d.new_links}</b>, всего в МС: ${d.ms_total}`;
            el.style.color = '#065f46';
            if (d.synced > 0) document.getElementById('ms-last-sync').textContent = new Date().toLocaleString('ru');
        } else {
            showResult(el.id, d);
        }
    });
}

function msPeriodicSync() {
    const el = document.getElementById('ms-sync-result') || document.getElementById('ms-import-result');
    el.textContent = '⏳ Синхронизируем (60 мин)...';
    el.style.color = '#6b7280';
    apiPost('<?= Url::to(['/admin/moysklad/periodic-sync']) ?>', {since: 60}).then(d => {
        if (d.success) {
            el.innerHTML =
                `✓ Обновлено: <b>${d.synced}</b>, новых связей: <b>${d.new_links}</b>, обработано: ${d.processed}`;
            el.style.color = '#065f46';
            document.getElementById('ms-last-sync').textContent = new Date().toLocaleString('ru');
        } else {
            showResult(el.id, d);
        }
    });
}

function msSyncInfo() {
    apiGet('<?= Url::to(['/admin/moysklad/sync-info']) ?>').then(d => {
        const el = document.getElementById('ms-sync-result');
        if (d.success) {
            const states = (d.states || []).map(s => s.name).join(', ');
            el.innerHTML =
                `Организация: <b>${d.org_name}</b> · Заказов в МС: <b>${d.order_count}</b><br>` +
                `<span style="font-size:11px;color:var(--admin-text-secondary)">Статусы: ${states || 'нет'}</span>`;
            el.style.color = 'inherit';
        } else {
            showResult('ms-sync-result', d);
        }
    });
}


// ─── Status mapping ──────────────────────────────────────────────
function msSaveStatusMapping() {
    const map = {};
    document.querySelectorAll('.ms-status-input').forEach(el => { map[el.dataset.status] = el.value; });
    apiPost('<?= Url::to(['/admin/moysklad/save-status-mapping']) ?>', map)
        .then(d => showResult('ms-status-map-result', d));
}

function msLoadMsStatuses() {
    const el = document.getElementById('ms-states-list');
    el.innerHTML = '<span style="color:#6b7280;font-size:13px">⏳ Загружаем статусы из МС...</span>';
    apiGet('<?= Url::to(['/admin/moysklad/sync-info']) ?>').then(d => {
        if (d.success && d.states && d.states.length) {
            el.innerHTML = '<div style="font-size:12px;margin-top:8px"><b>Статусы в МойСклад:</b> ' +
                d.states.map(s => `<span class="ms-pill ms-pill-blue" style="margin:2px">${s.name}</span>`).join(' ') +
                '</div>';
        } else {
            el.innerHTML = '<span style="color:#991b1b;font-size:13px">✗ ' + (d.message || 'Не удалось загрузить') + '</span>';
        }
    });
}


// ─── Save settings ───────────────────────────────────────────────
function msSaveSettings() {
    apiPost('<?= Url::to(['/admin/moysklad/save-settings']) ?>', {
        sync_orders:         document.getElementById('ms-toggle-orders').checked ? '1' : '0',
        sync_products:       document.getElementById('ms-toggle-products').checked ? '1' : '0',
        sync_customers:      document.getElementById('ms-toggle-customers').checked ? '1' : '0',
        sync_stock:          document.getElementById('ms-toggle-stock').checked ? '1' : '0',
        sync_prices:         document.getElementById('ms-toggle-prices').checked ? '1' : '0',
        sync_ms_to_site:     document.getElementById('ms-dir-ms-to-site').checked ? '1' : '0',
        sync_site_to_ms:     document.getElementById('ms-dir-site-to-ms').checked ? '1' : '0',
        auto_sync_interval:  document.getElementById('ms-auto-interval').value,
    }).then(d => showResult('ms-settings-result', d));
}


// ─── Webhooks ────────────────────────────────────────────────────
function msCheckWebhookStatus() {
    apiGet('<?= Url::to(['/admin/moysklad/webhook-status']) ?>').then(d => {
        const badge = document.getElementById('ms-wh-status-badge');
        if (!badge) return;
        if (d.status === 'registered') {
            badge.textContent = '✓ Зарегистрирован (' + (d.count||1) + ')';
            badge.className = 'ms-pill ms-pill-green';
        } else if (d.status === 'not_registered') {
            badge.textContent = '✗ Не зарегистрирован';
            badge.className = 'ms-pill ms-pill-red';
        } else {
            badge.textContent = '? ' + (d.message || 'Ошибка');
            badge.className = 'ms-pill ms-pill-gray';
        }
    });
}

function msListWebhooks() {
    apiGet('<?= Url::to(['/admin/moysklad/webhooks']) ?>').then(d => {
        const el = document.getElementById('ms-webhooks-list');
        if (!d.success) { showResult('ms-webhook-result', d); return; }
        const hooks = d.webhooks || [];
        if (!hooks.length) { el.innerHTML = '<p style="font-size:13px;color:var(--admin-text-secondary)">Вебхуков нет</p>'; return; }
        el.innerHTML = '<table class="admin-table" style="font-size:12px"><thead><tr><th>URL</th><th>Событие</th><th>Тип</th><th></th></tr></thead><tbody>' +
            hooks.map(h => `<tr>
                <td style="word-break:break-all">${h.url}</td>
                <td>${h.action||''}</td>
                <td>${h.entityType||''}</td>
                <td><button class="admin-btn admin-btn-sm admin-btn-danger" onclick="msDeleteWebhook('${h.id}')">
                    <i class="bi bi-trash"></i>
                </button></td>
            </tr>`).join('') +
            '</tbody></table>';
    });
}

function msRegisterWebhook() {
    apiPost('<?= Url::to(['/admin/moysklad/register-webhook']) ?>', {}).then(d => {
        showResult('ms-webhook-result', d);
        if (d.success) { msListWebhooks(); msCheckWebhookStatus(); }
    });
}

function msDeleteWebhook(id) {
    if (!confirm('Удалить этот вебхук?')) return;
    apiPost('<?= Url::to(['/admin/moysklad/delete-webhook']) ?>', {id}).then(d => {
        showResult('ms-webhook-result', d);
        if (d.success) { msListWebhooks(); msCheckWebhookStatus(); }
    });
}


// ─── Log filters ─────────────────────────────────────────────────
function msFilterLog() {
    const op = document.getElementById('ms-log-filter-op').value;
    const search = document.getElementById('ms-log-filter-search').value.toLowerCase();
    document.querySelectorAll('#ms-log-table tbody tr').forEach(tr => {
        const matchOp = !op || tr.dataset.op === op;
        const matchSearch = !search || tr.textContent.toLowerCase().includes(search);
        tr.style.display = (matchOp && matchSearch) ? '' : 'none';
    });
}

document.getElementById('ms-log-filter-op')?.addEventListener('change', msFilterLog);
document.getElementById('ms-log-filter-search')?.addEventListener('input', msFilterLog);

function msRefreshLog() {
    apiGet('<?= Url::to(['/admin/moysklad/sync-log']) ?>').then(d => {
        if (!d.success || !d.logs.length) return;
        const opColors = {push_all:'ms-pill-blue', pull:'ms-pill-green', periodic_sync:'ms-pill-yellow'};
        const tbody = d.logs.map(e => {
            const r = e.result || {};
            let res = '';
            if (r.pushed !== undefined) {
                res = `<span class="ms-pill ms-pill-green">Отправлено: ${r.pushed}</span>` +
                    (r.errors > 0 ? ` <span class="ms-pill ms-pill-red">Ошибок: ${r.errors}</span>` : '') +
                    ` <span style="color:var(--admin-text-secondary);font-size:11px">из ${r.total||'?'}</span>`;
            } else if (r.synced !== undefined) {
                res = `<span class="ms-pill ms-pill-green">Синхронизировано: ${r.synced}</span>` +
                    (r.new_links > 0 ? ` <span class="ms-pill ms-pill-blue">Новых: ${r.new_links}</span>` : '') +
                    ` <span style="color:var(--admin-text-secondary);font-size:11px">всего: ${r.ms_total||'?'}</span>`;
            } else {
                res = `<code style="font-size:11px">${JSON.stringify(r)}</code>`;
            }
            return `<tr data-op="${e.operation||''}">
                <td style="color:var(--admin-text-secondary)">${e.at||''}</td>
                <td><span class="ms-pill ${opColors[e.operation]||'ms-pill-gray'}">${e.operation||''}</span></td>
                <td>${res}</td>
            </tr>`;
        }).join('');
        document.getElementById('ms-log-table-wrap').innerHTML =
            `<table class="admin-table" style="font-size:12px" id="ms-log-table">
                <thead><tr><th style="width:150px">Время</th><th style="width:120px">Операция</th><th>Результат</th></tr></thead>
                <tbody>${tbody}</tbody>
            </table>`;
    });
}


// ─── Field mapping — DB columns ──────────────────────────────────

// Cache of already-fetched columns per entity so we don't re-fetch on every tab click
const _dbColumnCache = {};

/**
 * Fetch DB columns for `entity` (orders|products|customers), populate all
 * .ms-map-select[data-entity=entity] dropdowns, then pre-select saved values.
 */
async function msLoadDbColumns(entity) {
    if (_dbColumnCache[entity]) {
        msPopulateSelects(entity, _dbColumnCache[entity]);
        return;
    }
    try {
        const d = await apiGet('<?= Url::to(['/admin/moysklad/get-db-columns']) ?>?table=' + entity);
        if (!d.success) { console.warn('getDbColumns failed:', d.message); return; }
        _dbColumnCache[entity] = d.columns || [];
        msPopulateSelects(entity, _dbColumnCache[entity]);
    } catch (e) {
        console.error('msLoadDbColumns:', e);
    }
}

function msPopulateSelects(entity, columns) {
    document.querySelectorAll(`.ms-map-select[data-entity="${entity}"]`).forEach(sel => {
        const saved = sel.dataset.saved || '';
        // Rebuild options (keep blank first)
        sel.innerHTML = '<option value="">— не выбрано —</option>';
        columns.forEach(col => {
            const opt = document.createElement('option');
            opt.value = col;
            opt.textContent = col;
            if (col === saved) opt.selected = true;
            sel.appendChild(opt);
        });
        // Visual state
        sel.classList.toggle('is-mapped', !!saved && columns.includes(saved));
        // On change — update the dot indicator
        sel.addEventListener('change', function() {
            const dot = this.closest('tr')?.querySelector('.ms-field-dot');
            if (dot) {
                dot.classList.toggle('active',   !!this.value);
                dot.classList.toggle('inactive', !this.value);
                dot.title = this.value ? 'Смаппировано: ' + this.value : 'Не смаппировано';
            }
            this.classList.toggle('is-mapped', !!this.value);
        });
    });
}

/**
 * Collect all selects for entity, build {ms_field: db_column} JSON, POST to save endpoint.
 */
function msSaveFieldMapping(entity) {
    const resultEl = document.getElementById('ms-mapping-' + entity + '-result');
    if (resultEl) { resultEl.textContent = '⏳ Сохраняем...'; resultEl.style.color = '#6b7280'; }

    const mapping = {};
    document.querySelectorAll(`.ms-map-select[data-entity="${entity}"]`).forEach(sel => {
        if (sel.value) mapping[sel.dataset.msField] = sel.value;
    });

    apiPost('<?= Url::to(['/admin/moysklad/save-moysklad-mapping']) ?>', { entity, mapping })
        .then(d => {
            if (resultEl) showResult('ms-mapping-' + entity + '-result', d);
            // Update saved data attributes so cache stays consistent
            if (d.success) {
                document.querySelectorAll(`.ms-map-select[data-entity="${entity}"]`).forEach(sel => {
                    sel.dataset.saved = sel.value;
                });
            }
        })
        .catch(e => {
            if (resultEl) { resultEl.textContent = '✗ ' + e.message; resultEl.style.color = '#991b1b'; }
        });
}

// Load columns when a mapping tab is first activated
(function() {
    const tabToEntity = {
        'order-fields':    'orders',
        'product-fields':  'products',
        'customer-fields': 'customers',
    };
    const origSwitch = window.switchTab;
    window.switchTab = function(name) {
        origSwitch(name);
        if (tabToEntity[name]) msLoadDbColumns(tabToEntity[name]);
    };
    // Also load for whichever tab is already active on page load
    const activeTab = document.querySelector('.ms-tab.active');
    if (activeTab && tabToEntity[activeTab.dataset.tab]) {
        msLoadDbColumns(tabToEntity[activeTab.dataset.tab]);
    }
    // If hash points to a mapping tab, load columns now
    const hash = location.hash.replace('#', '');
    if (hash && tabToEntity[hash]) msLoadDbColumns(tabToEntity[hash]);
})();


// ─── Init ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', msCheckWebhookStatus);
</script>
