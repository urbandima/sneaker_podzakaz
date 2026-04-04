<?php
/**
 * Настройка цепочки статусов заказов
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Статусы заказов';
?>

<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><i class="bi bi-diagram-3"></i> <?= Html::encode($this->title) ?></h1>
        <p class="dash-subtitle">Настройка цепочки статусов и их отображения</p>
    </div>
    <div class="admin-header-actions">
        <button class="admin-btn admin-btn-primary" onclick="saveStatuses()">
            <i class="bi bi-save"></i> Сохранить изменения
        </button>
    </div>
</div>

<!-- Визуализация цепочки -->
<?php
// Массив соответствия цветов для статусов по умолчанию
$statusColors = [
    'created' => 'info',
    'paid' => 'success',
    'accepted' => 'primary',
    'ordered' => 'warning',
    'received' => 'primary',
    'issued' => 'success',
    'confirmed' => 'primary',
    'shipped' => 'info',
    'delivered' => 'success'
];
?>
<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-arrow-right-circle"></i> Цепочка статусов</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:flex;align-items:center;gap:8px;overflow-x:auto;padding:16px">
            <?php foreach ($statuses as $i => $status): ?>
                <?php if ($status['is_active'] && $status['key'] !== 'cancelled'): ?>
                    <div class="status-chain-item">
                        <div class="status-badge-large admin-badge-<?= $status['color'] ?>">
                            <?= $i + 1 ?>. <?= $status['label'] ?>
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
        <div id="statuses-list">
            <?php foreach ($statuses as $i => $status): ?>
            <div class="status-config-item" data-status="<?= $status['key'] ?>">
                <div style="display:flex;align-items:center;gap:16px">
                    <div class="drag-handle" style="cursor:move;color:var(--admin-text-secondary)">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                            <input type="text" class="admin-form-input status-label" value="<?= Html::encode($status['label']) ?>" style="max-width:200px" placeholder="Название">
                            <select class="admin-form-select status-color" style="max-width:150px">
                                <option value="info" <?= $status['color'] === 'info' ? 'selected' : '' ?>>Синий</option>
                                <option value="success" <?= $status['color'] === 'success' ? 'selected' : '' ?>>Зеленый</option>
                                <option value="warning" <?= $status['color'] === 'warning' ? 'selected' : '' ?>>Желтый</option>
                                <option value="danger" <?= $status['color'] === 'danger' ? 'selected' : '' ?>>Красный</option>
                                <option value="primary" <?= $status['color'] === 'primary' ? 'selected' : '' ?>>Фиолетовый</option>
                                <option value="secondary" <?= $status['color'] === 'secondary' ? 'selected' : '' ?>>Серый</option>
                            </select>
                            <label style="display:flex;align-items:center;gap:8px;margin:0">
                                <input type="checkbox" class="status-active" <?= $status['is_active'] ? 'checked' : '' ?>>
                                <span style="font-size:14px">Активен</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button class="admin-btn admin-btn-secondary" style="margin-top:16px" onclick="addStatus()">
            <i class="bi bi-plus-circle"></i> Добавить статус
        </button>
    </div>
</div>

<!-- Настройка уведомлений -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-bell"></i> Уведомления при смене статуса</h2>
    </div>
    <div class="admin-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Статус</th>
                    <th>Email клиенту</th>
                    <th>Telegram команде</th>
                    <th>SMS клиенту</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statuses as $status): ?>
                    <?php if ($status['is_active'] && $status['key'] !== 'cancelled'): ?>
                    <tr>
                        <td><span class="admin-badge admin-badge-<?= $status['color'] ?>"><?= $status['label'] ?></span></td>
                        <td><input type="checkbox" checked></td>
                        <td><input type="checkbox" checked></td>
                        <td><input type="checkbox"></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.status-chain-item {
    flex-shrink: 0;
}

.status-badge-large {
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
}

.status-config-item {
    padding: 16px;
    background: var(--admin-bg);
    border-radius: 12px;
    margin-bottom: 12px;
    border: 1px solid var(--admin-border);
    transition: all 0.2s;
}

.status-config-item:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.drag-handle {
    font-size: 20px;
}
</style>

<script>
function saveStatuses() {
    const statuses = [];
    document.querySelectorAll('.status-config-item').forEach((item, index) => {
        statuses.push({
            key: item.dataset.status,
            label: item.querySelector('.status-label').value,
            color: item.querySelector('.status-color').value,
            active: item.querySelector('.status-active').checked
        });
    });
    
    fetch('/admin/settings/save-statuses', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({statuses})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Статусы сохранены');
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Ошибка сохранения'));
        }
    });
}

function addStatus() {
    const container = document.getElementById('statuses-list');
    const index = container.children.length;
    
    const newStatus = document.createElement('div');
    newStatus.className = 'status-config-item';
    newStatus.dataset.status = 'new_' + index;
    newStatus.innerHTML = `
        <div style="display:flex;align-items:center;gap:16px">
            <div class="drag-handle" style="cursor:move;color:var(--admin-text-secondary)">
                <i class="bi bi-grip-vertical"></i>
            </div>
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                    <input type="text" class="admin-form-input status-label" value="" placeholder="Название" style="max-width:200px">
                    <select class="admin-form-select status-color" style="max-width:150px">
                        <option value="info">Синий</option>
                        <option value="success">Зеленый</option>
                        <option value="warning">Желтый</option>
                        <option value="danger">Красный</option>
                        <option value="primary">Фиолетовый</option>
                        <option value="secondary">Серый</option>
                    </select>
                    <label style="display:flex;align-items:center;gap:8px;margin:0">
                        <input type="checkbox" class="status-active" checked>
                        <span style="font-size:14px">Активен</span>
                    </label>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(newStatus);
}

// Drag & Drop для сортировки
let draggedElement = null;

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('statuses-list');
    
    list.addEventListener('dragstart', (e) => {
        if (e.target.classList.contains('status-config-item')) {
            draggedElement = e.target;
            e.target.style.opacity = '0.5';
        }
    });
    
    list.addEventListener('dragend', (e) => {
        if (e.target.classList.contains('status-config-item')) {
            e.target.style.opacity = '1';
        }
    });
    
    list.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(list, e.clientY);
        if (afterElement == null) {
            list.appendChild(draggedElement);
        } else {
            list.insertBefore(draggedElement, afterElement);
        }
    });
    
    // Делаем элементы draggable
    document.querySelectorAll('.status-config-item').forEach(item => {
        item.draggable = true;
    });
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.status-config-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}
</script>
