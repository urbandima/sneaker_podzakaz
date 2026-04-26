<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM — интеграция';

$tab     = Yii::$app->request->get('tab', 'dashboard');
$apiKey  = Yii::$app->settings->get('amocrm', 'api_key', '');
$domain  = Yii::$app->settings->get('amocrm', 'domain', '');
$siteUrl = Yii::$app->request->hostInfo;

$tabs = [
    'dashboard' => ['label' => 'Dashboard',  'icon' => 'bi-speedometer2'],
    'settings'  => ['label' => 'Settings',   'icon' => 'bi-gear'],
    'webhooks'  => ['label' => 'Webhooks',   'icon' => 'bi-link-45deg'],
    'fields'    => ['label' => 'Поля',       'icon' => 'bi-table'],
    'logs'      => ['label' => 'Logs',       'icon' => 'bi-journal-text'],
    'stats'     => ['label' => 'Stats',      'icon' => 'bi-bar-chart-line'],
];
?>

<?php $this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($apiKey ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="amocrm-status-badge">' . ($apiKey ? 'Активно' : 'Не настроено') . '</span>',
];
?>

<!-- Tab nav -->
<nav class="amo-tabs-nav" style="margin-bottom:20px">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="?tab=<?= $key ?>"
       class="amo-tab-btn <?= $tab === $key ? 'active' : '' ?>">
        <i class="bi <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === 'dashboard'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: DASHBOARD
     ════════════════════════════════════════════════════════════════════ -->

<?php
$recentOrders = \app\backend\modules\checkout\models\Order::find()
    ->where(['source' => 'amoCRM'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(25)
    ->all();
?>

<div class="amo-stats-grid" style="margin-bottom:20px">
    <div class="amo-stat-card">
        <div class="amo-stat-val"><?= count($recentOrders) ?></div>
        <div class="amo-stat-label">Заказов из AmoCRM</div>
    </div>
    <div class="amo-stat-card <?= $apiKey ? 'text-success' : '' ?>">
        <div class="amo-stat-val"><?= $apiKey ? 'Активно' : 'Не настроено' ?></div>
        <div class="amo-stat-label">Статус интеграции</div>
    </div>
    <div class="amo-stat-card">
        <div class="amo-stat-val"><?= $domain ?: '—' ?></div>
        <div class="amo-stat-label">Домен AmoCRM</div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-list-ul"></i> Последние заказы из AmoCRM</h2>
        <span class="admin-badge admin-badge-primary"><?= count($recentOrders) ?></span>
    </div>
    <div class="admin-card-body" style="padding:0">
        <?php if (empty($recentOrders)): ?>
            <div style="padding:32px;text-align:center;color:var(--admin-text-secondary)">
                <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5"></i>
                <p style="margin:0">Заказов из AmoCRM пока нет</p>
            </div>
        <?php else: ?>
            <table class="admin-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Заказ</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?= Html::encode($order->order_number) ?></strong></td>
                            <td><?= Html::encode($order->client_name) ?></td>
                            <td><?= Html::encode($order->client_phone) ?></td>
                            <td><?= number_format($order->total_amount, 2) ?> BYN</td>
                            <td><span class="admin-badge admin-badge-secondary"><?= Html::encode($order->getStatusLabel()) ?></span></td>
                            <td style="font-size:12px;color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asRelativeTime($order->created_at) ?></td>
                            <td>
                                <a href="<?= Url::to(['/admin/order/' . $order->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'settings'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: SETTINGS
     ════════════════════════════════════════════════════════════════════ -->

<!-- API Key -->
<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-key"></i> API-ключ для виджета</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 12px">
            Ключ используется виджетом AmoCRM для авторизации запросов к вашему магазину. Передаётся в заголовке <code>X-Api-Key</code>.
        </p>
        <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px">
            <div style="flex:1">
                <label class="admin-form-label">API Key</label>
                <input type="text" class="admin-form-input" id="amo-api-key"
                       value="<?= Html::encode($apiKey) ?>"
                       placeholder="Введите API-ключ"
                       style="text-overflow:ellipsis" readonly>
            </div>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="generateApiKey()">
                <i class="bi bi-arrow-repeat"></i> Сгенерировать
            </button>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyToClipboard('amo-api-key')">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
        <button class="admin-btn admin-btn-primary" onclick="saveApiKey()">
            <i class="bi bi-save"></i> Сохранить ключ
        </button>
        <span id="amo-key-msg" style="margin-left:10px;font-size:13px"></span>
    </div>
</div>

<!-- OAuth Settings -->
<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-shield-lock"></i> OAuth / Подключение</h2>
    </div>
    <div class="admin-card-body">
        <div class="amo-settings-grid">
            <div>
                <div style="margin-bottom:10px">
                    <label class="admin-form-label">Домен AmoCRM</label>
                    <input type="text" class="admin-form-input" id="amocrm-domain" placeholder="example.amocrm.ru"
                           value="<?= Html::encode($domain) ?>">
                </div>
                <div style="margin-bottom:10px">
                    <label class="admin-form-label">Client ID</label>
                    <input type="text" class="admin-form-input" id="amocrm-client-id" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                           value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'client_id', '')) ?>">
                </div>
                <div style="margin-bottom:10px">
                    <label class="admin-form-label">Client Secret</label>
                    <input type="password" class="admin-form-input" id="amocrm-secret" placeholder="••••••••••••••••">
                </div>
                <div style="margin-bottom:12px">
                    <label class="admin-form-label">Access Token</label>
                    <textarea class="admin-form-input" rows="2" id="amocrm-token" placeholder="Получите токен после авторизации через OAuth"></textarea>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="admin-btn admin-btn-secondary" onclick="testAmoCRMConn()">
                        <i class="bi bi-check-circle"></i> Проверить
                    </button>
                    <button class="admin-btn admin-btn-primary" onclick="saveAmoCRMConn()">
                        <i class="bi bi-save"></i> Сохранить
                    </button>
                </div>
                <div id="amo-oauth-msg" style="margin-top:10px;font-size:13px"></div>
            </div>
            <div>
                <label class="admin-form-label">Код виджета для AmoCRM</label>
                <p style="font-size:12px;color:var(--admin-text-secondary);margin:0 0 8px">
                    Вставьте этот код в настройках виджета AmoCRM (карточка сделки → виджет → HTML-код).
                </p>
                <div style="position:relative">
<textarea class="admin-form-input" rows="7" id="amo-embed-code" readonly style="font-family:monospace;font-size:12px;background:var(--admin-bg);resize:none">
<div id="sneakerhead-widget-container"></div>
<script>
window.SNEAKERHEAD_API_URL = '<?= $siteUrl ?>';
window.SNEAKERHEAD_API_KEY = '<?= Html::encode($apiKey) ?>';
</script>
<script src="<?= $siteUrl ?>/amocrm-widget/widget.js"></script>
</textarea>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" style="position:absolute;top:8px;right:8px" onclick="copyToClipboard('amo-embed-code')">
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'webhooks'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: WEBHOOKS
     ════════════════════════════════════════════════════════════════════ -->

<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-link-45deg"></i> Webhook URL для AmoCRM</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 12px">
            Укажите этот URL в настройках AmoCRM <strong>Настройки → Интеграции → Webhook</strong> для получения уведомлений о событиях.
        </p>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
            <input type="text" class="admin-form-input" id="amo-webhook-url"
                   value="<?= Html::encode($siteUrl . '/api/amocrm/create-order') ?>"
                   readonly style="font-family:monospace;font-size:12px">
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyToClipboard('amo-webhook-url')">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
        <div style="margin-bottom:12px">
            <label class="admin-form-label" style="margin-bottom:8px;display:block">Зарегистрированные события</label>
            <table class="amo-wh-table">
                <thead>
                    <tr>
                        <th>Событие</th>
                        <th>Описание</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>add_lead</code></td>
                        <td>Новая сделка — создаёт заказ в магазине</td>
                        <td><span class="admin-badge admin-badge-success">Активно</span></td>
                    </tr>
                    <tr>
                        <td><code>update_lead</code></td>
                        <td>Обновление сделки — синхронизация статуса заказа</td>
                        <td><span class="admin-badge admin-badge-success">Активно</span></td>
                    </tr>
                    <tr>
                        <td><code>delete_lead</code></td>
                        <td>Удаление сделки — помечает заказ как отменённый</td>
                        <td><span class="admin-badge admin-badge-secondary">Опционально</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button class="admin-btn admin-btn-secondary" onclick="amoWHTest()">
            <i class="bi bi-play-circle"></i> Тест webhook
        </button>
        <span id="amo-wh-test-result" style="margin-left:10px;font-size:13px"></span>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-arrow-repeat"></i> Пакетная синхронизация заказов</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 12px">
            Отправить существующие заказы в AmoCRM как сделки.
        </p>
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px">
            <div>
                <label class="admin-form-label">Статус заказов</label>
                <select class="admin-form-input" id="amo-sync-status">
                    <option value="">Все</option>
                    <option value="new">Новые</option>
                    <option value="paid">Оплаченные</option>
                    <option value="delivered">Доставленные</option>
                </select>
            </div>
            <div>
                <label class="admin-form-label">Лимит</label>
                <input type="number" class="admin-form-input" id="amo-sync-limit" value="50" min="1" max="500" style="width:80px">
            </div>
        </div>
        <button class="admin-btn admin-btn-primary" id="amo-sync-btn" onclick="amoSyncBatch()">
            <i class="bi bi-arrow-repeat"></i> Синхронизировать
        </button>
        <span id="amo-sync-result" style="margin-left:10px;font-size:13px"></span>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--admin-border)">
        <label class="admin-form-label">Синхронизировать один заказ (ID)</label>
        <div style="display:flex;gap:8px;align-items:center;margin-top:6px">
            <input type="number" class="admin-form-input" id="amo-single-id" placeholder="ID заказа" style="width:160px">
            <button class="admin-btn admin-btn-secondary" onclick="amoSyncSingle()">
                <i class="bi bi-arrow-right-circle"></i> Отправить
            </button>
            <span id="amo-single-result" style="font-size:13px"></span>
        </div>
    </div>
</div>

<?php elseif ($tab === 'logs'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: LOGS
     ════════════════════════════════════════════════════════════════════ -->

<div class="amo-tab-pane">
    <div class="amo-logs-toolbar" style="margin-bottom:12px">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="admin-form-input" id="amo-log-status" style="width:auto" onchange="amoLoadLogs(1)">
                <option value="">Все статусы</option>
                <option value="ok">ok</option>
                <option value="fail">fail</option>
            </select>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="amoLoadLogs(1)">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
        </div>
        <span style="font-size:12px;color:var(--admin-text-muted)" id="amo-log-total"></span>
    </div>

    <div class="admin-card">
        <div class="admin-card-body" style="padding:0">
            <table class="amo-log-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Направление</th>
                        <th>Событие</th>
                        <th>Статус</th>
                        <th>Время (мс)</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody id="amo-log-tbody">
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--admin-text-muted)">Загрузка...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:12px" id="amo-log-pager"></div>
</div>

<?php elseif ($tab === 'fields'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: FIELDS — Field mapping AmoCRM ↔ our order fields
     ════════════════════════════════════════════════════════════════════ -->

<div class="amo-tab-pane">
    <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h2 class="admin-card-title"><i class="bi bi-table"></i> Маппинг полей AmoCRM → наши поля</h2>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="amoLoadFields()">
                <i class="bi bi-arrow-clockwise"></i> Обновить поля
            </button>
        </div>
        <div class="admin-card-body">
            <p style="color:var(--admin-text-secondary);font-size:13px;margin-bottom:16px">
                Укажите какие кастомные поля AmoCRM соответствуют полям заказа.
                При создании заказа из сделки значения будут скопированы автоматически.
            </p>

            <div id="amo-fields-loading" style="text-align:center;padding:32px;color:var(--admin-text-muted)">
                <i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px"></i>
                Загрузка полей из AmoCRM...
            </div>

            <table class="admin-table" id="amo-fields-table" style="display:none;margin:0">
                <thead>
                    <tr>
                        <th>Поле AmoCRM</th>
                        <th>ID поля</th>
                        <th>Тип</th>
                        <th>Наше поле заказа</th>
                    </tr>
                </thead>
                <tbody id="amo-fields-tbody"></tbody>
            </table>

            <div id="amo-fields-empty" style="display:none;padding:24px;text-align:center;color:var(--admin-text-muted)">
                <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;opacity:.5"></i>
                Кастомных полей в AmoCRM не найдено. Проверьте настройки токена.
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h2 class="admin-card-title"><i class="bi bi-floppy"></i> Сохранённые маппинги</h2>
            <div id="amo-fields-save-wrap" style="display:none;gap:8px">
                <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="amoSaveFields()">
                    <i class="bi bi-floppy"></i> Сохранить маппинг
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <div id="amo-save-msg"></div>
            <table class="admin-table" id="amo-saved-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Наше поле</th>
                        <th>AmoCRM field_id</th>
                        <th>Название в AMO</th>
                        <th>Направление</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="amo-saved-tbody">
                    <?php
                    try {
                        $mappings = Yii::$app->db->createCommand('SELECT * FROM {{%amocrm_field_mapping}} WHERE entity_type="lead" ORDER BY local_field')->queryAll();
                        foreach ($mappings as $m): ?>
                        <tr id="amo-mapping-row-<?= $m['id'] ?>">
                            <td><code><?= Html::encode($m['local_field']) ?></code></td>
                            <td><?= (int)$m['amocrm_field_id'] ?></td>
                            <td><?= Html::encode($m['amocrm_field_name'] ?? '—') ?></td>
                            <td><?= Html::encode($m['direction']) ?></td>
                            <td>
                                <button class="admin-btn admin-btn-sm admin-btn-danger"
                                    onclick="amoDeleteMapping(<?= $m['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mappings)): ?>
                        <tr id="amo-saved-empty"><td colspan="5" style="text-align:center;padding:20px;color:var(--admin-text-muted)">Нет сохранённых маппингов</td></tr>
                        <?php endif; ?>
                    <?php } catch (\Exception $e) { ?>
                        <tr><td colspan="5" style="color:var(--admin-danger)">Таблица маппинга не найдена. Запустите миграции.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($tab === 'stats'): ?>
<!-- ══════════════════════════════════════════════════════════════════════
     TAB: STATS
     ════════════════════════════════════════════════════════════════════ -->

<div class="amo-tab-pane">
    <div class="amo-stats-grid" id="amo-stats-grid" style="margin-bottom:20px">
        <div class="amo-stat-card"><div class="amo-stat-val" id="amo-s-total">—</div><div class="amo-stat-label">Всего запросов</div></div>
        <div class="amo-stat-card" style="color:var(--admin-success,#065f46)"><div class="amo-stat-val" id="amo-s-ok">—</div><div class="amo-stat-label">Успешных</div></div>
        <div class="amo-stat-card" style="color:var(--admin-danger,#991b1b)"><div class="amo-stat-val" id="amo-s-fail">—</div><div class="amo-stat-label">Ошибок</div></div>
        <div class="amo-stat-card"><div class="amo-stat-val" id="amo-s-ms">—</div><div class="amo-stat-label">Среднее время (мс)</div></div>
        <div class="amo-stat-card" style="color:var(--admin-accent,#008060)"><div class="amo-stat-val" id="amo-s-synced">—</div><div class="amo-stat-label">Заказов в AmoCRM</div></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Активность за 14 дней</h2>
        </div>
        <div class="admin-card-body">
            <div id="amo-chart-wrap" style="height:160px;display:flex;align-items:flex-end;gap:4px">
                <span style="color:var(--admin-text-muted);font-size:13px">Загрузка...</span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════════════════
     Styles & Scripts (shared across all tabs)
     ════════════════════════════════════════════════════════════════════ -->
<style>
.amo-tabs-nav{display:flex;gap:4px;border-bottom:2px solid var(--admin-border);padding-bottom:2px;margin-bottom:20px;flex-wrap:wrap}
.amo-tab-btn{padding:8px 16px;border-radius:6px 6px 0 0;border:none;background:none;color:var(--admin-text-muted);cursor:pointer;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:color .15s,background .15s}
.amo-tab-btn:hover{color:var(--admin-text);background:var(--admin-hover,rgba(0,0,0,.04))}
.amo-tab-btn.active{color:var(--admin-accent,#008060);background:rgba(0,128,96,.08);border-bottom:2px solid var(--admin-accent,#008060);margin-bottom:-2px}
.amo-settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:900px){.amo-settings-grid{grid-template-columns:1fr}}
.amo-wh-table{width:100%;border-collapse:collapse;font-size:13px}
.amo-wh-table th,.amo-wh-table td{padding:7px 10px;border-bottom:1px solid var(--admin-border);text-align:left}
.amo-wh-table th{font-weight:600;color:var(--admin-text-muted)}
.amo-logs-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.amo-log-table{width:100%;border-collapse:collapse;font-size:12px}
.amo-log-table th,.amo-log-table td{padding:7px 12px;border-bottom:1px solid var(--admin-border);white-space:nowrap}
.amo-log-table th{font-weight:600;color:var(--admin-text-muted);background:var(--admin-surface-2,#f8f9fa)}
.amo-log-table .status-ok{color:#065f46;font-weight:600}
.amo-log-table .status-fail{color:#991b1b;font-weight:600}
.amo-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px}
.amo-stat-card{background:var(--admin-card-bg,#fff);border:1px solid var(--admin-border);border-radius:8px;padding:16px;text-align:center}
.amo-stat-val{font-size:28px;font-weight:700;line-height:1.1;margin-bottom:4px}
.amo-stat-label{font-size:11px;color:var(--admin-text-muted);text-transform:uppercase;letter-spacing:.04em}
.amo-bar{background:var(--admin-accent,#008060);border-radius:3px 3px 0 0;min-width:14px;position:relative;transition:height .3s}
.amo-bar:hover::after{content:attr(data-cnt);position:absolute;top:-22px;left:50%;transform:translateX(-50%);background:#333;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;white-space:nowrap}
</style>

<script>
(function() {
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function msg(id, text, color) {
    var el = document.getElementById(id);
    if (el) { el.textContent = text; el.style.color = color || 'inherit'; }
}

function showResult(elId, d) {
    msg(elId, (d.success ? '✓ ' : '✗ ') + (d.message || ''), d.success ? '#065f46' : '#991b1b');
}

function post(url, data, cb) {
    fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body:JSON.stringify(data)})
        .then(r => r.json()).then(cb).catch(() => cb({success:false, message:'Ошибка сети'}));
}

function get(url, cb) {
    fetch(url).then(r => r.json()).then(cb).catch(() => cb({success:false}));
}

window.copyToClipboard = function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    navigator.clipboard.writeText(el.value || el.textContent).then(() => {
        var old = el.style.borderColor;
        el.style.borderColor = '#22c55e';
        setTimeout(() => { el.style.borderColor = old; }, 800);
    });
};

window.generateApiKey = function() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var key = Array.from({length: 48}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
    var el = document.getElementById('amo-api-key');
    if (el) { el.value = key; el.removeAttribute('readonly'); }
};

window.saveApiKey = function() {
    var key = (document.getElementById('amo-api-key') || {}).value || '';
    if (!key.trim()) { msg('amo-key-msg', 'Введите или сгенерируйте ключ', '#b45309'); return; }
    post('<?= Url::to(['/admin/settings/save']) ?>', {amocrm: {api_key: key}}, function(d) {
        msg('amo-key-msg', d.success ? '✓ Ключ сохранён' : '✗ ' + d.message, d.success ? '#065f46' : '#991b1b');
    });
};

window.testAmoCRMConn = function() {
    var domain = (document.getElementById('amocrm-domain') || {}).value || '';
    var token  = (document.getElementById('amocrm-token')  || {}).value || '';
    if (!domain || !token) { msg('amo-oauth-msg', '⚠ Заполните домен и токен', '#b45309'); return; }
    msg('amo-oauth-msg', 'Проверяем...', '#6b7280');
    post('<?= Url::to(['/admin/settings/test-amocrm']) ?>', {domain, api_token: token}, function(d) {
        msg('amo-oauth-msg', (d.success ? '✓ ' : '✗ ') + (d.message || (d.success ? 'Успешно' : 'Ошибка')), d.success ? '#065f46' : '#991b1b');
        var badge = document.getElementById('amocrm-status-badge');
        if (badge) { badge.textContent = d.success ? 'Подключено' : 'Не подключено'; badge.className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary'); }
    });
};

window.saveAmoCRMConn = function() {
    post('<?= Url::to(['/admin/settings/save']) ?>', {amocrm: {
        domain:    (document.getElementById('amocrm-domain')    || {}).value,
        client_id: (document.getElementById('amocrm-client-id') || {}).value,
        api_token: (document.getElementById('amocrm-token')     || {}).value,
    }}, function(d) { msg('amo-oauth-msg', d.success ? '✓ Сохранено' : '✗ ' + d.message, d.success ? '#065f46' : '#991b1b'); });
};

window.amoWHTest = function() {
    msg('amo-wh-test-result', 'Отправляем...', '#6b7280');
    post('/webhook/amocrm/event', {_test: 1}, function(d) {
        msg('amo-wh-test-result', d.success !== false ? '✓ Webhook доступен' : '✗ Ошибка', d.success !== false ? '#065f46' : '#991b1b');
    });
};

window.amoSyncBatch = function() {
    var btn = document.getElementById('amo-sync-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Синхронизация...'; }
    msg('amo-sync-result', 'Отправляем...', '#6b7280');
    post('/admin/plugin/amocrm/sync', {
        status: (document.getElementById('amo-sync-status') || {}).value,
        limit:  parseInt((document.getElementById('amo-sync-limit') || {}).value) || 50,
    }, function(d) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Синхронизировать'; }
        showResult('amo-sync-result', d);
    });
};

window.amoSyncSingle = function() {
    var id = parseInt((document.getElementById('amo-single-id') || {}).value);
    if (!id) return;
    msg('amo-single-result', 'Отправляем...', '#6b7280');
    post('/admin/plugin/amocrm/sync', {order_id: id}, function(d) { showResult('amo-single-result', d); });
};

/* ── Logs tab ── */
var _logPage = 1;
window.amoLoadLogs = function(page) {
    _logPage = page || _logPage;
    var st = (document.getElementById('amo-log-status') || {}).value || '';
    var tbody = document.getElementById('amo-log-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:16px;color:var(--admin-text-muted)">Загрузка...</td></tr>';
    get('/admin/plugin/amocrm/logs?page=' + _logPage + '&status=' + encodeURIComponent(st), function(d) {
        if (!tbody) return;
        var totalEl = document.getElementById('amo-log-total');
        if (!d.rows || !d.rows.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--admin-text-muted)">Нет записей</td></tr>'; return; }
        if (totalEl) totalEl.textContent = 'Всего: ' + (d.total || 0);
        tbody.innerHTML = d.rows.map(function(r) {
            var dt = r.created_at ? new Date(r.created_at * 1000).toLocaleString('ru') : '';
            return '<tr><td>' + r.id + '</td><td>' + (r.direction||'') + '</td>'
                + '<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis">' + (r.event||'') + '</td>'
                + '<td class="status-' + r.status + '">' + r.status + '</td>'
                + '<td>' + (r.response_ms||'—') + '</td><td>' + dt + '</td></tr>';
        }).join('');
        var pager = document.getElementById('amo-log-pager');
        if (pager) {
            var pages = Math.ceil((d.total||0) / 50);
            pager.innerHTML = pages > 1 ? Array.from({length:pages}, function(_,i) {
                return '<button class="admin-btn admin-btn-sm ' + (i+1===_logPage?'admin-btn-primary':'admin-btn-secondary') + '" onclick="amoLoadLogs(' + (i+1) + ')">' + (i+1) + '</button>';
            }).join('') : '';
        }
    });
};

/* ── Stats tab ── */
function loadStats() {
    get('/admin/plugin/amocrm/stats', function(d) {
        if (!d.success) return;
        ['total','ok','fail','ms','synced'].forEach(function(k) {
            var el = document.getElementById('amo-s-' + k);
            if (el) el.textContent = d[k === 'ms' ? 'avg_ms' : k === 'synced' ? 'synced_orders' : k] ?? '—';
        });
        var wrap = document.getElementById('amo-chart-wrap');
        var byDay = d.by_day || [];
        if (!wrap || !byDay.length) { if (wrap) wrap.innerHTML = '<span style="color:var(--admin-text-muted);font-size:13px">Нет данных</span>'; return; }
        var max = Math.max.apply(null, byDay.map(function(x){return parseInt(x.cnt)||0;})) || 1;
        wrap.innerHTML = byDay.map(function(x) {
            var pct = Math.round(((parseInt(x.cnt)||0) / max) * 150);
            return '<div style="display:flex;flex-direction:column;align-items:center;gap:2px" title="' + x.d + ': ' + x.cnt + '">'
                + '<div class="amo-bar" data-cnt="' + x.cnt + '" style="height:' + pct + 'px"></div>'
                + '<div style="font-size:9px;color:var(--admin-text-muted)">' + (x.d||'').slice(5) + '</div>'
                + '</div>';
        }).join('');
    });
}

/* ── Fields tab ── */
var _amoFields = [];
var LOCAL_FIELDS = [
    {value: '', label: '— не маппить —'},
    {value: 'order.client_name',    label: 'Имя клиента'},
    {value: 'order.client_phone',   label: 'Телефон'},
    {value: 'order.client_email',   label: 'Email'},
    {value: 'order.total_amount',   label: 'Сумма заказа'},
    {value: 'order.comment',        label: 'Комментарий'},
    {value: 'order.product_name',   label: 'Название товара'},
    {value: 'order.size',           label: 'Размер'},
    {value: 'order.source',         label: 'Источник'},
    {value: 'order.delivery_address', label: 'Адрес доставки'},
];

window.amoLoadFields = function() {
    var loading = document.getElementById('amo-fields-loading');
    var table   = document.getElementById('amo-fields-table');
    var empty   = document.getElementById('amo-fields-empty');
    var saveWrap = document.getElementById('amo-fields-save-wrap');
    if (loading) loading.style.display = '';
    if (table)   table.style.display   = 'none';
    if (empty)   empty.style.display   = 'none';

    get('/admin/plugin/amocrm/fields', function(d) {
        if (loading) loading.style.display = 'none';
        if (!d.success || !d.fields || !d.fields.length) {
            if (empty) empty.style.display = '';
            return;
        }
        _amoFields = d.fields;
        var tbody = document.getElementById('amo-fields-tbody');
        if (!tbody) return;

        tbody.innerHTML = _amoFields.map(function(f) {
            var sel = '<select class="admin-form-control" data-amo-id="' + f.id + '" data-amo-name="' + (f.name||'').replace(/"/g,'&quot;') + '">'
                + LOCAL_FIELDS.map(function(lf) { return '<option value="' + lf.value + '">' + lf.label + '</option>'; }).join('')
                + '</select>';
            return '<tr><td>' + (f.name||'') + '</td><td><code>' + f.id + '</code></td><td>' + (f.field_type||'') + '</td><td>' + sel + '</td></tr>';
        }).join('');

        // Pre-fill existing mappings
        if (d.mappings) {
            d.mappings.forEach(function(m) {
                var sel = tbody.querySelector('[data-amo-id="' + m.amocrm_field_id + '"]');
                if (sel) sel.value = m.local_field;
            });
        }

        if (table)    table.style.display    = '';
        if (saveWrap) saveWrap.style.display  = 'flex';
    });
};

window.amoSaveFields = function() {
    var tbody = document.getElementById('amo-fields-tbody');
    if (!tbody) return;
    var rows = [];
    tbody.querySelectorAll('select[data-amo-id]').forEach(function(sel) {
        if (sel.value) rows.push({
            amocrm_field_id:   parseInt(sel.getAttribute('data-amo-id')),
            amocrm_field_name: sel.getAttribute('data-amo-name'),
            local_field:       sel.value,
        });
    });
    msg('amo-save-msg', 'Сохраняем...', '#6b7280');
    post('/admin/plugin/amocrm/fields-save', {mappings: rows}, function(d) {
        msg('amo-save-msg', d.success ? '✓ Маппинг сохранён' : '✗ ' + d.message, d.success ? '#065f46' : '#991b1b');
        if (d.success) {
            // Refresh saved list
            var emptyRow = document.getElementById('amo-saved-empty');
            if (emptyRow) emptyRow.remove();
            // Simple reload of saved section
            setTimeout(function() { location.reload(); }, 800);
        }
    });
};

window.amoDeleteMapping = function(id) {
    if (!confirm('Удалить маппинг?')) return;
    post('/admin/plugin/amocrm/fields-delete', {id: id}, function(d) {
        if (d.success) {
            var row = document.getElementById('amo-mapping-row-' + id);
            if (row) row.remove();
        }
    });
};

/* Auto-init current tab */
var curTab = '<?= $tab ?>';
if (curTab === 'logs')   amoLoadLogs(1);
if (curTab === 'stats')  loadStats();
if (curTab === 'fields') amoLoadFields();

})();
</script>
