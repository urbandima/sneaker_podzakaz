<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */
$this->title = 'Поставщики';

$filterSearch = Yii::$app->request->get('search', '');
$filterActive = Yii::$app->request->get('active', '');
$filterCountry = Yii::$app->request->get('country', '');

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$columnDefs = [
    'contact' => 'Контакт',
    'phone'   => 'Телефон',
    'email'   => 'Email',
    'country' => 'Страна',
    'terms'   => 'Условия оплаты',
];
$storageKey = 'suppliersColumns';
?>

<!-- Filter bar -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <input type="text" name="search" class="compact-filter-input"
               placeholder="🔍 Название, контакт…" value="<?= htmlspecialchars($filterSearch) ?>">
        <select name="active" class="compact-filter-select" style="min-width:130px">
            <option value="">Все статусы</option>
            <option value="1" <?= $filterActive === '1' ? 'selected' : '' ?>>Активные</option>
            <option value="0" <?= $filterActive === '0' ? 'selected' : '' ?>>Неактивные</option>
        </select>
        <input type="text" name="country" class="compact-filter-input" style="min-width:80px;max-width:100px"
               placeholder="Страна" value="<?= htmlspecialchars($filterCountry) ?>">
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
        <a href="?" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
        <div class="col-selector-wrap" style="margin-left:auto">
            <button type="button" class="compact-filter-btn" onclick="AdminTable.toggleColSelector(event)">
                <i class="bi bi-layout-three-columns"></i> Столбцы
            </button>
            <div id="colSelector" class="col-selector-dropdown" style="display:none">
                <div style="font-weight:700;margin-bottom:8px;font-size:.8125rem">Показать столбцы:</div>
                <?php foreach ($columnDefs as $colKey => $colLabel): ?>
                <label class="col-selector-item">
                    <input type="checkbox" data-col="<?= $colKey ?>"
                           onchange="AdminTable.toggleColumn('<?= $colKey ?>', this.checked, '<?= $storageKey ?>')" checked>
                    <?= htmlspecialchars($colLabel) ?>
                </label>
                <?php endforeach; ?>
                <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e1e3e5);padding-top:8px;display:flex;gap:6px">
                    <button type="button" onclick="AdminTable.selectAllCols(true,'<?= $storageKey ?>')"  class="compact-filter-btn compact-filter-btn--apply"  style="flex:1;height:26px;font-size:11px;padding:0 8px">Все</button>
                    <button type="button" onclick="AdminTable.selectAllCols(false,'<?= $storageKey ?>')" class="compact-filter-btn compact-filter-btn--reset" style="flex:1;height:26px;font-size:11px;padding:0 8px">Скрыть</button>
                </div>
            </div>
        </div>
        <button type="button" class="compact-filter-btn compact-filter-btn--apply" onclick="openSupplierModal()" style="margin-left:4px">
            <i class="bi bi-plus-lg"></i> Добавить поставщика
        </button>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-building"></i> Поставщики
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($suppliers) ?></span>
        </h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th style="width:44px">ID</th>
                    <th data-sort="name" onclick="AdminTable.sortBy('name')">Название <?= $sortIcon('name') ?></th>
                    <th data-col="contact">Контакт</th>
                    <th data-col="phone">Телефон</th>
                    <th data-col="email">Email</th>
                    <th data-col="country">Страна</th>
                    <th data-col="terms">Условия оплаты</th>
                    <th style="text-align:center">Статус</th>
                    <th style="text-align:right;width:80px">—</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$suppliers): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Поставщиков нет</td></tr>
                <?php endif; ?>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td style="color:var(--admin-text-secondary,#6d7175);font-size:11px"><?= $s->id ?></td>
                    <td><strong><?= htmlspecialchars($s->name) ?></strong></td>
                    <td data-col="contact" style="color:var(--admin-text-secondary,#6d7175)"><?= htmlspecialchars($s->contact_person ?? '—') ?></td>
                    <td data-col="phone" style="white-space:nowrap">
                        <?= $s->phone ? '<a href="tel:' . htmlspecialchars($s->phone) . '" style="color:inherit;text-decoration:none">' . htmlspecialchars($s->phone) . '</a>' : '—' ?>
                    </td>
                    <td data-col="email" style="color:var(--admin-text-secondary,#6d7175);font-size:.75rem">
                        <?= htmlspecialchars($s->email ?? '—') ?>
                    </td>
                    <td data-col="country" style="white-space:nowrap">
                        <?= htmlspecialchars($s->country ?? '—') ?>
                    </td>
                    <td data-col="terms" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= htmlspecialchars($s->payment_terms ?? '—') ?>
                    </td>
                    <td style="text-align:center">
                        <?php if ($s->is_active): ?>
                            <span class="status-pill" style="background:#d1f7e5;color:#008060">Активен</span>
                        <?php else: ?>
                            <span class="status-pill" style="background:#f3f4f6;color:#6d7175">Неактивен</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;padding:4px 8px">
                        <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Изменить"
                                onclick='editSupplier(<?= json_encode(["id"=>$s->id,"name"=>$s->name,"contact_person"=>$s->contact_person,"phone"=>$s->phone,"email"=>$s->email,"address"=>$s->address,"country"=>$s->country,"payment_terms"=>$s->payment_terms,"notes"=>$s->notes,"is_active"=>$s->is_active]) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="admin-btn admin-btn-sm admin-btn-danger" title="Удалить"
                                onclick="deleteSupplier(<?= $s->id ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Supplier Modal -->
<div class="modal" id="supplierModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="supplierModalTitle">Поставщик</h5>
        <button type="button" class="btn-close" onclick="closeModal('supplierModal')"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="s_id" value="0">
        <div class="mb-3"><label class="form-label">Название *</label><input type="text" id="s_name" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Контактное лицо</label><input type="text" id="s_contact" class="form-control"></div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">Телефон</label><input type="text" id="s_phone" class="form-control"></div>
          <div class="col"><label class="form-label">Email</label><input type="email" id="s_email" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Адрес</label><textarea id="s_address" class="form-control" rows="2"></textarea></div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">Страна</label><input type="text" id="s_country" class="form-control" value="CN"></div>
          <div class="col">
            <label class="form-label">Активен</label>
            <select id="s_active" class="form-control"><option value="1">Да</option><option value="0">Нет</option></select>
          </div>
        </div>
        <div class="mb-3"><label class="form-label">Условия оплаты</label><input type="text" id="s_terms" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Примечания</label><textarea id="s_notes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="admin-btn admin-btn-secondary" onclick="closeModal('supplierModal')">Отмена</button>
        <button class="admin-btn admin-btn-primary" onclick="saveSupplier()">Сохранить</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="supplierModal-backdrop" style="display:none"></div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

AdminTable.initColSelector('<?= $storageKey ?>');

function openSupplierModal() {
    document.getElementById('supplierModalTitle').textContent = 'Новый поставщик';
    document.getElementById('s_id').value = '0';
    ['s_name','s_contact','s_phone','s_email','s_address','s_terms','s_notes'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('s_country').value = 'CN';
    document.getElementById('s_active').value = '1';
    showModal('supplierModal');
}

function editSupplier(data) {
    document.getElementById('supplierModalTitle').textContent = 'Изменить поставщика';
    document.getElementById('s_id').value          = data.id;
    document.getElementById('s_name').value        = data.name || '';
    document.getElementById('s_contact').value     = data.contact_person || '';
    document.getElementById('s_phone').value       = data.phone || '';
    document.getElementById('s_email').value       = data.email || '';
    document.getElementById('s_address').value     = data.address || '';
    document.getElementById('s_country').value     = data.country || 'CN';
    document.getElementById('s_terms').value       = data.payment_terms || '';
    document.getElementById('s_notes').value       = data.notes || '';
    document.getElementById('s_active').value      = data.is_active ? '1' : '0';
    showModal('supplierModal');
}

function showModal(id) {
    document.getElementById(id).style.display = 'block';
    document.getElementById(id).classList.add('show');
    document.getElementById(id + '-backdrop').style.display = 'block';
    document.getElementById(id + '-backdrop').classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.getElementById(id).classList.remove('show');
    const bd = document.getElementById(id + '-backdrop');
    if (bd) { bd.style.display = 'none'; bd.classList.remove('show'); }
}

function saveSupplier() {
    const body = {
        id:             parseInt(document.getElementById('s_id').value),
        name:           document.getElementById('s_name').value,
        contact_person: document.getElementById('s_contact').value,
        phone:          document.getElementById('s_phone').value,
        email:          document.getElementById('s_email').value,
        address:        document.getElementById('s_address').value,
        country:        document.getElementById('s_country').value,
        payment_terms:  document.getElementById('s_terms').value,
        notes:          document.getElementById('s_notes').value,
        is_active:      document.getElementById('s_active').value,
    };
    fetch('/admin/procurement/supplier-save', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify(body)
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка: ' + JSON.stringify(d.errors));
    });
}

function deleteSupplier(id) {
    if (!confirm('Удалить поставщика #' + id + '?')) return;
    fetch('/admin/procurement/supplier-delete', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка: ' + d.message);
    });
}
</script>
