<?php
/**
 * Настройка цепочки статусов заказов
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Статусы заказов';

$saveUrl = Url::to(['/admin/settings/save-statuses']);

$this->params['headerActions'] = [
    '<button class="admin-btn admin-btn-primary" onclick="saveStatuses()">
        <i class="bi bi-save"></i> Сохранить изменения
    </button>'
];

?>

<!-- Визуализация цепочки -->
<div class="admin-card mb-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-arrow-right-circle"></i> Цепочка статусов</h2>
    </div>
    <div class="admin-card-body">
        <div id="status-chain" style="display:flex;align-items:flex-start;gap:8px;overflow-x:auto;padding:16px;flex-wrap:wrap">
            <?php foreach ($statuses as $i => $status): ?>
                <?php if ($status['is_active'] && $status['key'] !== 'canceled'): ?>
                    <div class="status-chain-item" style="flex-shrink:0">
                        <div class="status-badge-large admin-badge-<?= $status['color'] ?? 'secondary' ?>" style="white-space:normal;word-break:break-word;min-height:2.5em;display:flex;align-items:center;text-align:center">
                            <?= $i + 1 ?>. <?= Html::encode($status['label']) ?>
                        </div>
                    </div>
                    <?php if ($i < count($statuses) - 2): ?>
                        <i class="bi bi-arrow-right" style="font-size:20px;color:var(--admin-text-secondary)"></i>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Настройка статусов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-gear"></i> Настройка статусов</h2>
    </div>
    <div class="admin-card-body">
        <table class="admin-table" id="statuses-table">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th style="width:180px">Ключ</th>
                    <th style="width:220px">Название</th>
                    <th style="width:140px">Цвет</th>
                    <th style="width:100px">Активен</th>
                    <th style="width:120px">Логист</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody id="statuses-list">
                <?php foreach ($statuses as $i => $status): ?>
                <?php $isSystem = !empty($status['is_system']); ?>
                <tr class="status-row" data-key="<?= Html::encode($status['key']) ?>">
                    <td style="cursor:move;color:var(--admin-text-secondary)"><i class="bi bi-grip-vertical"></i></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                        <input type="text" class="admin-form-input status-key" value="<?= Html::encode($status['key']) ?>" style="width:100%" placeholder="status_key" <?= $isSystem ? 'readonly style="width:100%;background:#f1f5f9"' : '' ?>>
                        <?php if ($isSystem): ?><span class="admin-badge admin-badge-secondary" style="font-size:10px;white-space:nowrap;padding:2px 6px" title="Системный статус — нельзя удалить">Системный</span><?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="admin-form-input status-label w-100" value="<?= Html::encode($status['label']) ?>" placeholder="Название">
                    </td>
                    <td>
                        <select class="admin-form-select status-color w-100">
                            <option value="info" <?= ($status['color'] ?? '') === 'info' ? 'selected' : '' ?>>Синий</option>
                            <option value="success" <?= ($status['color'] ?? '') === 'success' ? 'selected' : '' ?>>Зеленый</option>
                            <option value="warning" <?= ($status['color'] ?? '') === 'warning' ? 'selected' : '' ?>>Желтый</option>
                            <option value="danger" <?= ($status['color'] ?? '') === 'danger' ? 'selected' : '' ?>>Красный</option>
                            <option value="primary" <?= ($status['color'] ?? '') === 'primary' ? 'selected' : '' ?>>Фиолетовый</option>
                            <option value="secondary" <?= ($status['color'] ?? 'secondary') === 'secondary' ? 'selected' : '' ?>>Серый</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="status-active" <?= $status['is_active'] ? 'checked' : '' ?>>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="status-logist" <?= !empty($status['logist_available']) ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <?php if (!$isSystem): ?>
                        <button class="admin-btn admin-btn-danger" style="padding:4px 8px;font-size:12px" onclick="removeStatus(this)" title="Удалить">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <button class="admin-btn admin-btn-secondary" style="margin-top:16px" onclick="addStatus()">
            <i class="bi bi-plus-circle"></i> Добавить статус
        </button>
    </div>
</div>

<div id="save-result" style="display:none;margin-top:16px;padding:12px 16px;border-radius:8px;font-size:14px"></div>

<script>
function addStatus() {
    var tbody = document.getElementById('statuses-list');
    var row = document.createElement('tr');
    row.className = 'status-row';
    row.dataset.key = '';
    row.innerHTML = 
        '<td style="cursor:move;color:var(--admin-text-secondary)"><i class="bi bi-grip-vertical"></i></td>' +
        '<td><input type="text" class="admin-form-input status-key w-100" value="" placeholder="new_status_key"></td>' +
        '<td><input type="text" class="admin-form-input status-label w-100" value="" placeholder="Название"></td>' +
        '<td><select class="admin-form-select status-color w-100">' +
            '<option value="info">Синий</option><option value="success">Зеленый</option>' +
            '<option value="warning">Желтый</option><option value="danger">Красный</option>' +
            '<option value="primary">Фиолетовый</option><option value="secondary">Серый</option>' +
        '</select></td>' +
        '<td><input type="checkbox" class="status-active text-center" checked></td>' +
        '<td><input type="checkbox" class="status-logist text-center"></td>' +
        '<td><button class="admin-btn admin-btn-danger" style="padding:4px 8px;font-size:12px" onclick="removeStatus(this)" title="Удалить"><i class="bi bi-trash"></i></button></td>';
    tbody.appendChild(row);
}

function removeStatus(btn) {
    if (confirm('Удалить этот статус?')) {
        btn.closest('tr').remove();
    }
}

function saveStatuses() {
    var rows = document.querySelectorAll('#statuses-list .status-row');
    var statuses = [];
    
    rows.forEach(function(row) {
        var key = row.querySelector('.status-key').value.trim();
        var label = row.querySelector('.status-label').value.trim();
        var color = row.querySelector('.status-color').value;
        var isActive = row.querySelector('.status-active').checked;
        var logistAvailable = row.querySelector('.status-logist').checked;
        
        if (key && label) {
            statuses.push({
                key: key,
                label: label,
                color: color,
                is_active: isActive,
                logist_available: logistAvailable
            });
        }
    });
    
    var resultDiv = document.getElementById('save-result');
    
    fetch('<?= $saveUrl ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''},
        body: JSON.stringify({_csrf: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '', statuses: statuses})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.style.background = '#ecfdf5';
            resultDiv.style.color = '#065f46';
            resultDiv.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.message;
            setTimeout(function(){ location.reload(); }, 1000);
        } else {
            resultDiv.style.background = '#fef2f2';
            resultDiv.style.color = '#991b1b';
            resultDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + data.message;
        }
    })
    .catch(function(err) {
        resultDiv.style.display = 'block';
        resultDiv.style.background = '#fef2f2';
        resultDiv.style.color = '#991b1b';
        resultDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка сети';
    });
}

// Drag-and-drop для сортировки
(function() {
    var tbody = document.getElementById('statuses-list');
    var dragRow = null;
    
    tbody.addEventListener('dragstart', function(e) {
        dragRow = e.target.closest('tr');
        if (dragRow) e.dataTransfer.effectAllowed = 'move';
    });
    
    tbody.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('tr');
        if (target && target !== dragRow) {
            var rect = target.getBoundingClientRect();
            var mid = rect.top + rect.height / 2;
            if (e.clientY < mid) {
                tbody.insertBefore(dragRow, target);
            } else {
                tbody.insertBefore(dragRow, target.nextSibling);
            }
        }
    });
    
    tbody.addEventListener('dragend', function() { dragRow = null; });
    
    // Сделать строки перетаскиваемыми
    document.querySelectorAll('#statuses-list .status-row').forEach(function(row) {
        row.draggable = true;
    });
})();
</script>
