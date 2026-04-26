<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $methods */

$this->title = 'Настройки доставки';
$csrfToken = Yii::$app->request->csrfToken;
?>

<?php
// Group methods by region
$regionGroups = [
    'international' => ['label' => 'Международная доставка', 'icon' => 'bi-airplane', 'methods' => []],
    'belarus'       => ['label' => 'Беларусь', 'icon' => 'bi-house-fill', 'methods' => []],
    'russia'        => ['label' => 'Россия', 'icon' => 'bi-truck', 'methods' => []],
];
foreach ($methods as $i => $method) {
    $region = $method['region'] ?? ($method['type'] === 'international' ? 'international' : 'belarus');
    if (!isset($regionGroups[$region])) $region = 'belarus';
    $regionGroups[$region]['methods'][] = ['_idx' => $i, 'data' => $method];
}
?>

<!-- Region Tabs -->
<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <div style="display:flex;gap:0">
            <?php foreach ($regionGroups as $regionKey => $group): ?>
            <button class="shipping-tab-btn" data-region="<?= $regionKey ?>" onclick="switchRegionTab('<?= $regionKey ?>')" style="<?= $regionKey === 'international' ? 'border-bottom-color:var(--admin-accent);color:var(--admin-accent);' : '' ?>">
                <i class="bi <?= $group['icon'] ?>"></i> <?= $group['label'] ?>
                <span class="admin-badge admin-badge-secondary" style="font-size:10px;margin-left:4px"><?= count($group['methods']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="addDeliveryType()">
            <i class="bi bi-plus-circle"></i> Добавить
        </button>
    </div>

    <?php foreach ($regionGroups as $regionKey => $group): ?>
    <div class="shipping-region-panel" id="region-panel-<?= $regionKey ?>" style="<?= $regionKey !== 'international' ? 'display:none' : '' ?>;padding:0">
        <div id="delivery-types-list-<?= $regionKey ?>">
        <?php if (empty($group['methods'])): ?>
        <div style="padding:2rem;text-align:center;color:var(--admin-text-secondary);font-size:0.875rem">
            <i class="bi <?= $group['icon'] ?>" style="font-size:1.5rem;display:block;margin-bottom:0.5rem"></i>
            Нет методов доставки для этого региона
        </div>
        <?php endif; ?>
        <?php foreach ($group['methods'] as $entry): $i = $entry['_idx']; $method = $entry['data']; ?>
        <div class="delivery-type-row" id="delivery-row-<?= $i ?>" data-id="<?= htmlspecialchars($method['id']) ?>">
            <div class="delivery-type-info">
                <div class="delivery-type-drag"><i class="bi bi-grip-vertical"></i></div>
                <div class="delivery-type-icon">
                    <i class="bi <?= $regionKey === 'international' ? 'bi-airplane' : ($method['id'] === 'local_pickup' ? 'bi-shop' : 'bi-truck') ?>"></i>
                </div>
                <div class="delivery-type-fields">
                    <div class="delivery-field-row">
                        <input type="text" class="admin-form-input delivery-name"
                               value="<?= htmlspecialchars($method['name']) ?>"
                               placeholder="Название"
                               data-field="name" data-idx="<?= $i ?>">
                        <input type="text" class="admin-form-input delivery-time"
                               value="<?= htmlspecialchars($method['delivery_time']) ?>"
                               placeholder="Срок (напр: 1-3 дня)"
                               data-field="delivery_time" data-idx="<?= $i ?>">
                        <div class="delivery-price-wrap">
                            <input type="number" class="admin-form-input delivery-price"
                                   value="<?= $method['base_cost'] ?>"
                                   placeholder="0.00"
                                   step="0.5" min="0"
                                   data-field="base_cost" data-idx="<?= $i ?>">
                            <span class="delivery-currency">BYN</span>
                        </div>
                    </div>
                    <div class="delivery-field-row delivery-field-meta">
                        <input type="text" class="admin-form-input delivery-carrier"
                               value="<?= htmlspecialchars($method['carrier']) ?>"
                               placeholder="Перевозчик"
                               data-field="carrier" data-idx="<?= $i ?>">
                        <input type="text" class="admin-form-input delivery-plugin"
                               value="<?= htmlspecialchars($method['plugin'] ?? '') ?>"
                               placeholder="Плагин (europochta, belpochta...)"
                               data-field="plugin" data-idx="<?= $i ?>"
                               style="max-width:180px;font-size:12px">
                        <?php if (empty($method['plugin']) && ($method['status'] ?? '') === 'active'): ?>
                        <span class="admin-badge admin-badge-warning" style="font-size:11px;white-space:nowrap" title="Провайдер не выбран — метод деактивирован при сохранении">
                            <i class="bi bi-exclamation-triangle"></i> Провайдер не выбран — метод деактивирован
                        </span>
                        <?php endif; ?>
                        <input type="text" class="admin-form-input delivery-desc"
                               value="<?= htmlspecialchars($method['description']) ?>"
                               placeholder="Описание"
                               data-field="description" data-idx="<?= $i ?>">
                        <select class="admin-form-input delivery-region" data-field="region" data-idx="<?= $i ?>" style="max-width:130px;font-size:12px">
                            <option value="international" <?= ($method['region'] ?? ($method['type'] === 'international' ? 'international' : 'belarus')) === 'international' ? 'selected' : '' ?>>Международная</option>
                            <option value="belarus" <?= ($method['region'] ?? ($method['type'] !== 'international' ? 'belarus' : '')) === 'belarus' ? 'selected' : '' ?>>Беларусь</option>
                            <option value="russia" <?= ($method['region'] ?? '') === 'russia' ? 'selected' : '' ?>>Россия</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="delivery-type-actions">
                <label class="delivery-toggle" title="Активен / Отключён">
                    <input type="checkbox" class="delivery-active-chk"
                           <?= $method['status'] === 'active' ? 'checked' : '' ?>
                           data-idx="<?= $i ?>">
                    <span class="delivery-toggle-track"></span>
                </label>
                <button class="action-btn" title="Удалить" style="color:var(--admin-danger)" onclick="removeDeliveryType(<?= $i ?>)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <div class="admin-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <button class="admin-btn admin-btn-primary" onclick="saveShippingSettings()">
            <i class="bi bi-save"></i> Сохранить настройки доставки
        </button>
        <div id="shipping-save-result" style="font-size:13px"></div>
    </div>
</div>

<style>
.delivery-type-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--admin-border);
    gap: 16px;
    transition: background 0.15s;
}
.delivery-type-row:last-child { border-bottom: none; }
.delivery-type-row:hover { background: var(--admin-surface-hover); }
.delivery-type-info { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
.delivery-type-drag { color: var(--admin-text-secondary); cursor: grab; padding: 4px; }
.delivery-type-icon {
    width: 36px; height: 36px;
    background: var(--admin-surface-hover);
    border-radius: var(--admin-radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: var(--admin-accent); flex-shrink: 0;
}
.delivery-type-fields { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
.delivery-field-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.delivery-field-row .admin-form-input { padding: 6px 10px; font-size: 13px; }
.delivery-name { flex: 2; min-width: 140px; }
.delivery-time { flex: 1; min-width: 100px; }
.delivery-price-wrap { display: flex; align-items: center; gap: 4px; }
.delivery-price { width: 80px; text-align: right; }
.delivery-currency { font-size: 12px; color: var(--admin-text-secondary); white-space: nowrap; }
.delivery-carrier, .delivery-desc { flex: 1; min-width: 120px; }
.delivery-field-meta .admin-form-input { font-size: 12px; color: var(--admin-text-secondary); }
.delivery-type-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.delivery-toggle { position: relative; cursor: pointer; }
.delivery-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.delivery-toggle-track {
    display: block; width: 36px; height: 20px;
    background: var(--admin-border); border-radius: 20px; transition: background 0.2s;
    position: relative;
}
.delivery-toggle-track::after {
    content: ''; position: absolute;
    top: 3px; left: 3px;
    width: 14px; height: 14px;
    background: white; border-radius: 50%;
    transition: transform 0.2s;
}
.delivery-toggle input:checked + .delivery-toggle-track { background: var(--admin-accent); }
.delivery-toggle input:checked + .delivery-toggle-track::after { transform: translateX(16px); }
</style>

<style>
.shipping-tab-btn {
    padding: 10px 16px;
    border: none;
    background: transparent;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--admin-text-secondary);
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.15s, border-color 0.15s;
}
.shipping-tab-btn:hover { color: var(--admin-text); }
.shipping-tab-btn.active { border-bottom-color: var(--admin-accent); color: var(--admin-accent); }
</style>

<script>
var activeRegionTab = 'international';

function switchRegionTab(region) {
    document.querySelectorAll('.shipping-region-panel').forEach(function(p) { p.style.display = 'none'; });
    document.querySelectorAll('.shipping-tab-btn').forEach(function(b) { b.classList.remove('active'); b.style.borderBottomColor = 'transparent'; b.style.color = ''; });
    document.getElementById('region-panel-' + region).style.display = '';
    var btn = document.querySelector('[data-region="' + region + '"]');
    if (btn) { btn.style.borderBottomColor = 'var(--admin-accent)'; btn.style.color = 'var(--admin-accent)'; }
    activeRegionTab = region;
}

var shippingData = <?= json_encode(array_values($methods)) ?>;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function saveShippingSettings() {
    var result = document.getElementById('shipping-save-result');
    result.textContent = 'Сохраняем...'; result.style.color = '#6b7280';

    // Collect current form state from ALL regions
    var rows = document.querySelectorAll('.delivery-type-row');
    var updated = [];
    rows.forEach(function(row) {
        var idx = parseInt(row.querySelector('[data-idx]')?.dataset.idx ?? '0');
        var entry = Object.assign({}, shippingData[idx] || {});
        row.querySelectorAll('[data-field]').forEach(function(inp) {
            entry[inp.dataset.field] = inp.value;
        });
        var chk = row.querySelector('.delivery-active-chk');
        if (chk) entry.status = chk.checked ? 'active' : 'inactive';
        updated.push(entry);
    });

    fetch('<?= \yii\helpers\Url::to(['/admin/settings/save-shipping']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({shipping: {methods: updated}})
    })
    .then(r => r.json())
    .then(d => {
        result.textContent = d.success ? '✓ Настройки доставки сохранены' : ('✗ ' + (d.message || 'Ошибка'));
        result.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { result.textContent = '✗ Ошибка сети'; result.style.color = '#991b1b'; });
}

function removeDeliveryType(idx) {
    var row = document.getElementById('delivery-row-' + idx);
    if (row && confirm('Удалить тип доставки?')) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.2s';
        setTimeout(function() { row.remove(); }, 200);
    }
}

function addDeliveryType() {
    var list = document.getElementById('delivery-types-list-' + activeRegionTab);
    if (!list) list = document.querySelector('[id^="delivery-types-list-"]');
    var newIdx = document.querySelectorAll('.delivery-type-row').length;
    var html = '<div class="delivery-type-row" id="delivery-row-' + newIdx + '" data-id="new_' + newIdx + '">'
        + '<div class="delivery-type-info">'
        + '<div class="delivery-type-drag"><i class="bi bi-grip-vertical"></i></div>'
        + '<div class="delivery-type-icon"><i class="bi bi-truck"></i></div>'
        + '<div class="delivery-type-fields">'
        + '<div class="delivery-field-row">'
        + '<input type="text" class="admin-form-input delivery-name" placeholder="Название" data-field="name" data-idx="' + newIdx + '">'
        + '<input type="text" class="admin-form-input delivery-time" placeholder="Срок (напр: 1-3 дня)" data-field="delivery_time" data-idx="' + newIdx + '">'
        + '<div class="delivery-price-wrap"><input type="number" class="admin-form-input delivery-price" placeholder="0.00" step="0.5" min="0" data-field="base_cost" data-idx="' + newIdx + '"><span class="delivery-currency">BYN</span></div>'
        + '</div>'
        + '<div class="delivery-field-row delivery-field-meta">'
        + '<input type="text" class="admin-form-input delivery-carrier" placeholder="Перевозчик" data-field="carrier" data-idx="' + newIdx + '">'
        + '<input type="text" class="admin-form-input delivery-plugin" placeholder="Плагин (europochta, belpochta...)" data-field="plugin" data-idx="' + newIdx + '" style="max-width:180px;font-size:12px">'
        + '<input type="text" class="admin-form-input delivery-desc" placeholder="Описание" data-field="description" data-idx="' + newIdx + '">'
        + '</div>'
        + '</div>'
        + '</div>'
        + '<div class="delivery-type-actions">'
        + '<label class="delivery-toggle"><input type="checkbox" class="delivery-active-chk" checked data-idx="' + newIdx + '"><span class="delivery-toggle-track"></span></label>'
        + '<span class="admin-badge admin-badge-secondary" style="font-size:11px">Внутри страны</span>'
        + '<button class="action-btn" style="color:var(--admin-danger)" onclick="removeDeliveryType(' + newIdx + ')"><i class="bi bi-trash"></i></button>'
        + '</div>'
        + '</div>';
    list.insertAdjacentHTML('beforeend', html);
    shippingData.push({id: 'new_' + newIdx, name: '', type: activeRegionTab === 'international' ? 'international' : 'local', region: activeRegionTab, type_label: activeRegionTab === 'international' ? 'Международная' : (activeRegionTab === 'russia' ? 'Россия' : 'Беларусь'), carrier: '', plugin: '', delivery_time: '', base_cost: 0, currency: 'BYN', status: 'active', description: ''});
    // Remove the "no methods" placeholder if present
    var emptyMsg = list.querySelector('div[style*="text-align:center"]');
    if (emptyMsg) emptyMsg.remove();
    list.lastElementChild.querySelector('.delivery-name').focus();
}
</script>
