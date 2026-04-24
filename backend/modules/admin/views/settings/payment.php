<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $methods */

$this->title = 'Способы оплаты';
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-credit-card"></i> Способы оплаты</h2>
        <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="addPaymentMethod()">
            <i class="bi bi-plus-circle"></i> Добавить способ
        </button>
    </div>
    <div class="admin-card-body" style="padding:0">
        <div id="payment-methods-list">
        <?php foreach ($methods as $i => $method): ?>
        <div class="payment-method-row" id="pm-row-<?= $i ?>" data-idx="<?= $i ?>">
            <div class="pm-drag"><i class="bi bi-grip-vertical"></i></div>
            <div class="pm-icon-preview">
                <i class="bi bi-<?= Html::encode($method['icon'] ?? 'credit-card') ?>" id="pm-icon-preview-<?= $i ?>"></i>
            </div>
            <div class="pm-fields">
                <div class="pm-field-row">
                    <input type="text" class="admin-form-input pm-name"
                           value="<?= Html::encode($method['name']) ?>"
                           placeholder="Название" data-field="name" data-idx="<?= $i ?>">
                    <input type="text" class="admin-form-input pm-icon"
                           value="<?= Html::encode($method['icon'] ?? 'credit-card') ?>"
                           placeholder="Bootstrap Icon (напр: bank)"
                           data-field="icon" data-idx="<?= $i ?>"
                           oninput="updateIconPreview(<?= $i ?>, this.value)">
                    <input type="text" class="admin-form-input pm-id"
                           value="<?= Html::encode($method['id']) ?>"
                           placeholder="ID (латиница)" data-field="id" data-idx="<?= $i ?>">
                    <input type="number" class="admin-form-input pm-sort" style="width:70px"
                           value="<?= (int)($method['sort_order'] ?? $i+1) ?>"
                           placeholder="Порядок" data-field="sort_order" data-idx="<?= $i ?>">
                </div>
                <div class="pm-field-row">
                    <input type="text" class="admin-form-input pm-desc"
                           value="<?= Html::encode($method['description'] ?? '') ?>"
                           placeholder="Описание для покупателя"
                           data-field="description" data-idx="<?= $i ?>">
                </div>
            </div>
            <div class="pm-actions">
                <label class="pm-toggle" title="Активен / Отключён">
                    <input type="checkbox" class="pm-active-chk"
                           <?= ($method['status'] ?? 'active') === 'active' ? 'checked' : '' ?>
                           data-idx="<?= $i ?>">
                    <span class="pm-toggle-track"></span>
                </label>
                <button class="action-btn" title="Удалить" style="color:var(--admin-danger)"
                        onclick="removePaymentMethod(<?= $i ?>)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <button class="admin-btn admin-btn-primary" onclick="savePaymentMethods()">
            <i class="bi bi-save"></i> Сохранить
        </button>
        <div id="pm-save-result" style="font-size:13px"></div>
    </div>
</div>

<div class="admin-card" style="margin-top:16px">
    <div class="admin-card-header">
        <h2 class="admin-card-title" style="font-size:13px"><i class="bi bi-info-circle"></i> Подсказка по иконкам</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0">
            Используйте названия иконок Bootstrap Icons без префикса <code>bi-</code>.
            Примеры: <code>bank</code>, <code>credit-card-2-front</code>, <code>cash-coin</code>,
            <code>wallet2</code>, <code>qr-code</code>, <code>phone</code>.
            Полный список: <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a>
        </p>
    </div>
</div>

<style>
.payment-method-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--admin-border);
    transition: background 0.15s;
}
.payment-method-row:last-child { border-bottom: none; }
.payment-method-row:hover { background: var(--admin-surface-hover); }
.pm-drag { color: var(--admin-text-secondary); cursor: grab; padding: 4px; flex-shrink: 0; }
.pm-icon-preview {
    width: 38px; height: 38px;
    background: var(--admin-surface-hover);
    border-radius: var(--admin-radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: var(--admin-accent); flex-shrink: 0;
}
.pm-fields { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.pm-field-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.pm-field-row .admin-form-input { padding: 6px 10px; font-size: 13px; }
.pm-name { flex: 2; min-width: 160px; }
.pm-icon { flex: 1; min-width: 130px; }
.pm-id { flex: 1; min-width: 120px; }
.pm-desc { flex: 1; min-width: 200px; color: var(--admin-text-secondary); font-size: 12px; }
.pm-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pm-toggle { position: relative; cursor: pointer; }
.pm-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.pm-toggle-track {
    display: block; width: 36px; height: 20px;
    background: var(--admin-border); border-radius: 20px; transition: background 0.2s;
    position: relative;
}
.pm-toggle-track::after {
    content: ''; position: absolute;
    top: 3px; left: 3px;
    width: 14px; height: 14px;
    background: white; border-radius: 50%; transition: transform 0.2s;
}
.pm-toggle input:checked + .pm-toggle-track { background: var(--admin-accent); }
.pm-toggle input:checked + .pm-toggle-track::after { transform: translateX(16px); }
</style>

<script>
var pmData = <?= json_encode(array_values($methods), JSON_UNESCAPED_UNICODE) ?>;

function updateIconPreview(idx, iconName) {
    var el = document.getElementById('pm-icon-preview-' + idx);
    if (el) {
        el.className = 'bi bi-' + (iconName.trim() || 'credit-card');
    }
}

function removePaymentMethod(idx) {
    var row = document.getElementById('pm-row-' + idx);
    if (row && confirm('Удалить способ оплаты?')) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.2s';
        setTimeout(function() { row.remove(); }, 200);
    }
}

function addPaymentMethod() {
    var list = document.getElementById('payment-methods-list');
    var newIdx = list.querySelectorAll('.payment-method-row').length;
    var html = '<div class="payment-method-row" id="pm-row-' + newIdx + '" data-idx="' + newIdx + '">'
        + '<div class="pm-drag"><i class="bi bi-grip-vertical"></i></div>'
        + '<div class="pm-icon-preview"><i class="bi bi-credit-card" id="pm-icon-preview-' + newIdx + '"></i></div>'
        + '<div class="pm-fields">'
        + '<div class="pm-field-row">'
        + '<input type="text" class="admin-form-input pm-name" placeholder="Название" data-field="name" data-idx="' + newIdx + '">'
        + '<input type="text" class="admin-form-input pm-icon" placeholder="Иконка (напр: bank)" data-field="icon" data-idx="' + newIdx + '" oninput="updateIconPreview(' + newIdx + ', this.value)">'
        + '<input type="text" class="admin-form-input pm-id" placeholder="ID (латиница)" data-field="id" data-idx="' + newIdx + '">'
        + '<input type="number" class="admin-form-input pm-sort" style="width:70px" placeholder="Порядок" data-field="sort_order" data-idx="' + newIdx + '" value="' + (newIdx + 1) + '">'
        + '</div>'
        + '<div class="pm-field-row">'
        + '<input type="text" class="admin-form-input pm-desc" placeholder="Описание для покупателя" data-field="description" data-idx="' + newIdx + '">'
        + '</div>'
        + '</div>'
        + '<div class="pm-actions">'
        + '<label class="pm-toggle"><input type="checkbox" class="pm-active-chk" checked data-idx="' + newIdx + '"><span class="pm-toggle-track"></span></label>'
        + '<button class="action-btn" style="color:var(--admin-danger)" onclick="removePaymentMethod(' + newIdx + ')"><i class="bi bi-trash"></i></button>'
        + '</div>'
        + '</div>';
    list.insertAdjacentHTML('beforeend', html);
    list.lastElementChild.querySelector('.pm-name').focus();
}

function savePaymentMethods() {
    var result = document.getElementById('pm-save-result');
    result.textContent = 'Сохраняем...'; result.style.color = '#6b7280';

    var rows = document.querySelectorAll('.payment-method-row');
    var methods = [];
    rows.forEach(function(row) {
        var entry = {};
        row.querySelectorAll('[data-field]').forEach(function(inp) {
            entry[inp.dataset.field] = inp.value.trim();
        });
        var chk = row.querySelector('.pm-active-chk');
        entry.status = (chk && chk.checked) ? 'active' : 'inactive';
        if (entry.name || entry.id) methods.push(entry);
    });

    fetch('<?= Url::to(['/admin/settings/save-payment']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $csrfToken ?>'
        },
        body: JSON.stringify({methods: methods})
    })
    .then(r => r.json())
    .then(d => {
        result.textContent = d.success
            ? '✓ Сохранено ' + d.saved + ' способов оплаты'
            : ('✗ ' + (d.message || 'Ошибка'));
        result.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { result.textContent = '✗ Ошибка сети'; result.style.color = '#991b1b'; });
}
</script>
