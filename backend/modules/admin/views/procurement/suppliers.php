<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */
/** @var app\backend\modules\catalog\models\Brand[] $allBrands */

use app\backend\modules\procurement\models\Supplier;

$this->title = 'Поставщики';

$filterSearch       = Yii::$app->request->get('search', '');
$filterBrands       = (array)Yii::$app->request->get('brands', []);
$filterCountries    = (array)Yii::$app->request->get('countries', []);
$filterContracts    = (array)Yii::$app->request->get('contracts', []);

$countryMap    = Supplier::getCountryMap();
$contractTypes = Supplier::getContractTypeOptions();

// ── Build brand map from all brands passed by controller ───────────────────
$brandMap = []; // id => name
foreach ($allBrands as $b) {
    $brandMap[$b->id] = $b->name;
}

// ── Helper: brand chips HTML for a supplier ────────────────────────────────
$brandChips = function(Supplier $s) use ($brandMap): string {
    $ids = $s->getBrandIds();
    if (!$ids) return '<span style="color:#9ca3af;font-size:11px;font-style:italic">Нет</span>';
    $html = '';
    foreach ($ids as $id) {
        $name = $brandMap[$id] ?? "Бренд #$id";
        $html .= '<span class="sp-brand-chip">' . htmlspecialchars($name) . '</span>';
    }
    return $html;
};

// ── Helper: contract badge ─────────────────────────────────────────────────
$contractBadge = function(Supplier $s): string {
    if (!$s->contract_type) {
        return '<span class="sp-contract-badge" style="background:#f3f4f6;color:#9ca3af">Нет</span>';
    }
    $color = $s->getContractTypeBadgeColor();
    // derive light bg from hex
    return '<span class="sp-contract-badge" style="background:' . $color . '22;color:' . $color . ';border:1px solid ' . $color . '44">'
        . htmlspecialchars($s->getContractTypeLabel())
        . '</span>';
};

// ── Helper: geo cell ───────────────────────────────────────────────────────
$geoCell = function(Supplier $s): string {
    if (!$s->country) return '<span style="color:#9ca3af">—</span>';
    $flag = Supplier::getCountryFlagEmoji($s->country);
    $name = $s->getCountryName();
    return $flag . ' ' . htmlspecialchars($name) . ($s->region ? '<span style="color:#9ca3af;font-size:11px;display:block">' . htmlspecialchars($s->region) . '</span>' : '');
};

// ── Supplier row HTML ──────────────────────────────────────────────────────
$supplierRow = function(Supplier $s) use ($brandChips, $contractBadge, $geoCell): string {
    $isActive = $s->is_active;
    return '<tr data-supplier-id="' . $s->id . '">
        <td style="width:22px;padding-left:12px"><input type="checkbox" class="sp-row-check" value="' . $s->id . '"></td>
        <td>
            <a href="/admin/procurement/supplier/' . $s->id . '" class="sp-name-link">
                <strong>' . htmlspecialchars($s->name) . '</strong>
            </a>
            ' . ($s->contact_person ? '<span style="font-size:11px;color:#6d7175;display:block">' . htmlspecialchars($s->contact_person) . '</span>' : '') . '
        </td>
        <td>' . $brandChips($s) . '</td>
        <td>' . $geoCell($s) . '</td>
        <td>' . $contractBadge($s) . '</td>
        <td style="text-align:center">
            ' . ($isActive
                ? '<span class="status-pill" style="background:#d1f7e5;color:#008060">Активен</span>'
                : '<span class="status-pill" style="background:#f3f4f6;color:#6d7175">Неактивен</span>') . '
        </td>
        <td style="text-align:right;white-space:nowrap;padding:4px 8px">
            <a href="/admin/procurement/supplier/' . $s->id . '" class="admin-btn admin-btn-sm admin-btn-secondary" title="Карточка"><i class="bi bi-eye"></i></a>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Изменить"
                    onclick=\'editSupplier(' . json_encode([
                        'id'            => $s->id,
                        'name'          => $s->name,
                        'contact_person'=> $s->contact_person,
                        'phone'         => $s->phone,
                        'email'         => $s->email,
                        'address'       => $s->address,
                        'country'       => $s->country,
                        'region'        => $s->region,
                        'payment_terms' => $s->payment_terms,
                        'notes'         => $s->notes,
                        'brands'        => $s->brands,
                        'contract_type' => $s->contract_type,
                        'contract_terms'=> $s->contract_terms,
                        'is_active'     => $s->is_active,
                    ]) . ')\'>
                <i class="bi bi-pencil"></i>
            </button>
            <button class="admin-btn admin-btn-sm admin-btn-danger" title="Удалить"
                    onclick="deleteSupplier(' . $s->id . ')">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>';
};

// ── Group table wrapper ────────────────────────────────────────────────────
$groupTable = function(string $title, array $rows, int $count) use ($supplierRow): string {
    if (!$rows) return '';
    $rowsHtml = implode('', array_map($supplierRow, $rows));
    $safeId   = preg_replace('/[^a-z0-9]/i', '_', $title);
    return '<details class="sp-group" open>
        <summary class="sp-group-summary">
            <span class="sp-group-title">' . htmlspecialchars($title) . '</span>
            <span class="sp-group-count">' . $count . '</span>
        </summary>
        <div class="sp-group-body" style="overflow-x:auto">
            <table class="admin-table sp-table" style="font-size:.8125rem">
                <thead>
                    <tr>
                        <th style="width:22px;padding-left:12px"><input type="checkbox" class="sp-check-all" data-group="' . $safeId . '"></th>
                        <th>Поставщик</th>
                        <th>Бренды</th>
                        <th>ГЕО</th>
                        <th>Договор</th>
                        <th style="text-align:center">Статус</th>
                        <th style="text-align:right;width:100px">Действия</th>
                    </tr>
                </thead>
                <tbody>' . $rowsHtml . '</tbody>
            </table>
        </div>
    </details>';
};
?>

<style>
/* ── Supplier grouping page ───────────────────────────────────────────── */
.sp-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding: 12px 16px;
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e1e3e5);
    border-radius: var(--admin-radius, 8px);
    margin-bottom: 16px;
}
.sp-filter-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--admin-text-secondary, #6d7175);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-right: 2px;
}
.sp-chip-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
}
.sp-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border: 1.5px solid var(--admin-border, #e1e3e5);
    background: var(--admin-surface, #fff);
    color: var(--admin-text, #202223);
    transition: all .15s;
    user-select: none;
    white-space: nowrap;
}
.sp-chip:hover   { border-color: var(--admin-accent, #008060); color: var(--admin-accent, #008060); }
.sp-chip.active  { background: var(--admin-accent, #008060); color: #fff; border-color: var(--admin-accent, #008060); }
.sp-search-input {
    height: 32px;
    border: 1.5px solid var(--admin-border, #e1e3e5);
    border-radius: 6px;
    padding: 0 10px;
    font-size: 13px;
    background: var(--admin-surface, #fff);
    color: var(--admin-text, #202223);
    min-width: 180px;
}
.sp-search-input:focus { border-color: var(--admin-accent, #008060); outline: none; }
.sp-divider { width: 1px; height: 28px; background: var(--admin-border, #e1e3e5); flex-shrink: 0; }

/* Grouping switcher */
.sp-group-switcher {
    display: flex;
    gap: 0;
    border: 1.5px solid var(--admin-border, #e1e3e5);
    border-radius: 8px;
    overflow: hidden;
    margin-left: auto;
    flex-shrink: 0;
}
.sp-group-btn {
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--admin-text-secondary, #6d7175);
    border-right: 1.5px solid var(--admin-border, #e1e3e5);
    transition: all .15s;
}
.sp-group-btn:last-child { border-right: none; }
.sp-group-btn:hover   { background: var(--admin-bg, #f6f6f7); color: var(--admin-text, #202223); }
.sp-group-btn.active  { background: var(--admin-accent, #008060); color: #fff; }

/* Group blocks */
.sp-group {
    margin-bottom: 12px;
    border: 1px solid var(--admin-border, #e1e3e5);
    border-radius: var(--admin-radius, 8px);
    overflow: hidden;
}
.sp-group-summary {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: var(--admin-bg, #f6f6f7);
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid transparent;
}
.sp-group[open] .sp-group-summary { border-bottom-color: var(--admin-border, #e1e3e5); }
.sp-group-summary::-webkit-details-marker { display: none; }
.sp-group-summary::before {
    content: '▶';
    font-size: 9px;
    color: var(--admin-text-secondary, #6d7175);
    transition: transform .15s;
}
.sp-group[open] .sp-group-summary::before { transform: rotate(90deg); }
.sp-group-title { font-weight: 700; font-size: .875rem; color: var(--admin-text, #202223); }
.sp-group-count {
    font-size: 11px;
    font-weight: 600;
    background: var(--admin-border, #e1e3e5);
    color: var(--admin-text-secondary, #6d7175);
    border-radius: 20px;
    padding: 2px 8px;
}
.sp-group-body { background: var(--admin-surface, #fff); }
.sp-table td, .sp-table th { padding: 8px 10px; }
.sp-brand-chip {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    background: #eff6ff;
    color: #2563eb;
    margin: 1px 2px;
    border: 1px solid #bfdbfe;
}
.sp-contract-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.sp-name-link { color: var(--admin-text, #202223); text-decoration: none; }
.sp-name-link:hover { color: var(--admin-accent, #008060); }

/* Bulk bar */
.sp-bulk-bar {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    margin-bottom: 12px;
    font-size: 13px;
}
.sp-bulk-bar.visible { display: flex; }

/* Header row */
.sp-header-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
</style>

<?php
// ── Collect unique countries ────────────────────────────────────────────
$usedCountries = [];
foreach ($suppliers as $s) {
    if ($s->country) $usedCountries[$s->country] = true;
}
ksort($usedCountries);

// ── Filter suppliers in PHP (search / brands / countries / contracts) ──
$visible = array_filter($suppliers, function(Supplier $s) use ($filterSearch, $filterBrands, $filterCountries, $filterContracts) {
    if ($filterSearch) {
        $hay = mb_strtolower($s->name . ' ' . $s->contact_person . ' ' . $s->email . ' ' . $s->phone);
        if (strpos($hay, mb_strtolower($filterSearch)) === false) return false;
    }
    if ($filterBrands) {
        $ids = $s->getBrandIds();
        $match = false;
        foreach ($filterBrands as $bid) {
            if (in_array((int)$bid, $ids)) { $match = true; break; }
        }
        if (!$match) return false;
    }
    if ($filterCountries) {
        $country = $s->country ?: '__other__';
        if (in_array('other', $filterCountries)) {
            $mainCountries = ['BY','RU','CN','IT'];
            if (in_array($country, $mainCountries) && !in_array($country, $filterCountries)) return false;
        } else {
            if (!in_array($country, $filterCountries)) return false;
        }
    }
    if ($filterContracts) {
        $ct = $s->contract_type ?: 'none';
        if (!in_array($ct, $filterContracts)) return false;
    }
    return true;
});
$visible = array_values($visible);
?>

<!-- Header -->
<div class="sp-header-row">
    <h2 class="admin-card-title" style="margin:0"><i class="bi bi-building"></i> Поставщики
        <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($visible) ?></span>
    </h2>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <div class="sp-group-switcher" id="groupSwitcher">
            <button class="sp-group-btn" data-group="brand"    onclick="SpPage.switchGroup('brand')">По брендам</button>
            <button class="sp-group-btn" data-group="geo"      onclick="SpPage.switchGroup('geo')">По ГЕО</button>
            <button class="sp-group-btn" data-group="contract" onclick="SpPage.switchGroup('contract')">По договору</button>
        </div>
        <button type="button" class="compact-filter-btn compact-filter-btn--apply" onclick="openSupplierModal()">
            <i class="bi bi-plus-lg"></i> Добавить
        </button>
    </div>
</div>

<!-- Filter bar -->
<form method="get" id="spFilterForm" class="sp-filters">
    <input type="text" name="search" class="sp-search-input"
           placeholder="Поиск по названию, контакту…"
           value="<?= htmlspecialchars($filterSearch) ?>"
           oninput="SpPage.debouncedSubmit()">

    <?php if ($allBrands): ?>
    <div class="sp-divider"></div>
    <span class="sp-filter-label">Бренды:</span>
    <div class="sp-chip-wrap" id="filterBrands">
        <?php foreach ($allBrands as $b): ?>
        <label class="sp-chip <?= in_array($b->id, $filterBrands) ? 'active' : '' ?>">
            <input type="checkbox" name="brands[]" value="<?= $b->id ?>"
                   <?= in_array($b->id, $filterBrands) ? 'checked' : '' ?>
                   onchange="this.closest('label').classList.toggle('active',this.checked);SpPage.submit()">
            <?= htmlspecialchars($b->name) ?>
        </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sp-divider"></div>
    <span class="sp-filter-label">ГЕО:</span>
    <div class="sp-chip-wrap">
        <?php
        $geoChips = ['BY'=>'BY','RU'=>'RU','CN'=>'CN','IT'=>'IT'];
        foreach ($geoChips as $code => $label):
        ?>
        <label class="sp-chip <?= in_array($code, $filterCountries) ? 'active' : '' ?>">
            <input type="checkbox" name="countries[]" value="<?= $code ?>"
                   <?= in_array($code, $filterCountries) ? 'checked' : '' ?>
                   onchange="this.closest('label').classList.toggle('active',this.checked);SpPage.submit()">
            <?= Supplier::getCountryFlagEmoji($code) ?> <?= $label ?>
        </label>
        <?php endforeach; ?>
        <label class="sp-chip <?= in_array('other', $filterCountries) ? 'active' : '' ?>">
            <input type="checkbox" name="countries[]" value="other"
                   <?= in_array('other', $filterCountries) ? 'checked' : '' ?>
                   onchange="this.closest('label').classList.toggle('active',this.checked);SpPage.submit()">
            Прочие
        </label>
    </div>

    <div class="sp-divider"></div>
    <span class="sp-filter-label">Договор:</span>
    <div class="sp-chip-wrap">
        <?php foreach ($contractTypes as $k => $v): ?>
        <label class="sp-chip <?= in_array($k, $filterContracts) ? 'active' : '' ?>">
            <input type="checkbox" name="contracts[]" value="<?= $k ?>"
                   <?= in_array($k, $filterContracts) ? 'checked' : '' ?>
                   onchange="this.closest('label').classList.toggle('active',this.checked);SpPage.submit()">
            <?= htmlspecialchars($v) ?>
        </label>
        <?php endforeach; ?>
    </div>

    <a href="?" class="compact-filter-btn compact-filter-btn--reset" style="margin-left:auto;flex-shrink:0">
        <i class="bi bi-x-lg"></i> Сброс
    </a>
</form>

<!-- Bulk action bar -->
<div class="sp-bulk-bar" id="spBulkBar">
    <i class="bi bi-check2-square"></i>
    <span id="spBulkCount">0</span> выбрано
    <select id="spBulkAction" class="compact-filter-select" style="min-width:200px">
        <option value="">— Действие —</option>
        <option value="contract">Сменить тип договора</option>
        <option value="activate">Активировать</option>
        <option value="deactivate">Деактивировать</option>
    </select>
    <select id="spBulkContract" class="compact-filter-select" style="min-width:160px;display:none">
        <option value="">Выберите тип…</option>
        <?php foreach ($contractTypes as $k => $v): ?>
        <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="compact-filter-btn compact-filter-btn--apply" onclick="SpPage.applyBulk()">Применить</button>
    <button class="compact-filter-btn compact-filter-btn--reset" onclick="SpPage.clearBulk()">Отмена</button>
</div>

<!-- Grouped content -->
<div id="spGroupedContent">

<?php
// ════════════════════════════════════════════════════════
// GROUP BY BRAND
// ════════════════════════════════════════════════════════
?>
<div id="spViewBrand" class="sp-view-panel">
<?php
$byBrand = []; // brand_id => [brand_name, suppliers[]]
$noBrand = [];

foreach ($visible as $s) {
    $ids = $s->getBrandIds();
    if (!$ids) {
        $noBrand[] = $s;
    } else {
        foreach ($ids as $bid) {
            $bname = $brandMap[$bid] ?? "Бренд #$bid";
            $byBrand[$bid]['name']       = $bname;
            $byBrand[$bid]['suppliers'][] = $s;
        }
    }
}
// Sort by brand name
uasort($byBrand, fn($a,$b) => strcmp($a['name'], $b['name']));

foreach ($byBrand as $bid => $data):
    echo $groupTable($data['name'], $data['suppliers'], count($data['suppliers']));
endforeach;

if ($noBrand):
    echo $groupTable('Без бренда', $noBrand, count($noBrand));
endif;

if (!$visible): ?>
    <div class="admin-card" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">
        Поставщики не найдены
    </div>
<?php endif; ?>
</div>

<?php
// ════════════════════════════════════════════════════════
// GROUP BY GEO
// ════════════════════════════════════════════════════════
?>
<div id="spViewGeo" class="sp-view-panel" style="display:none">
<?php
$geoGroups  = ['BY'=>[],'RU'=>[],'CN'=>[],'IT'=>[],'__other__'=>[]];
foreach ($visible as $s) {
    $c = $s->country ?: '__other__';
    if (!array_key_exists($c, $geoGroups)) $c = '__other__';
    $geoGroups[$c][] = $s;
}
$geoLabels = array_merge($countryMap, ['__other__' => 'Прочие']);
foreach ($geoGroups as $code => $rows):
    if (!$rows) continue;
    $flag  = ($code !== '__other__') ? Supplier::getCountryFlagEmoji($code) . ' ' : '';
    $label = $geoLabels[$code] ?? $code;
    echo $groupTable($flag . $label, $rows, count($rows));
endforeach;
if (!$visible): ?>
    <div class="admin-card" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Поставщики не найдены</div>
<?php endif; ?>
</div>

<?php
// ════════════════════════════════════════════════════════
// GROUP BY CONTRACT
// ════════════════════════════════════════════════════════
?>
<div id="spViewContract" class="sp-view-panel" style="display:none">
<?php
$ctGroups = ['commission'=>[],'stock'=>[],'to_order'=>[],'mixed'=>[],'__none__'=>[]];
foreach ($visible as $s) {
    $ct = $s->contract_type ?: '__none__';
    $ctGroups[$ct][] = $s;
}
$ctLabels = array_merge($contractTypes, ['__none__' => 'Без договора']);
foreach ($ctGroups as $ct => $rows):
    if (!$rows) continue;
    echo $groupTable($ctLabels[$ct] ?? $ct, $rows, count($rows));
endforeach;
if (!$visible): ?>
    <div class="admin-card" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Поставщики не найдены</div>
<?php endif; ?>
</div>

</div><!-- #spGroupedContent -->

<!-- ══ Supplier Modal ══════════════════════════════════════════════════════ -->
<div class="modal" id="supplierModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:600px">
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
          <div class="col-3">
            <label class="form-label">Страна (ISO-2)</label>
            <select id="s_country" class="form-control">
              <option value="">—</option>
              <?php foreach ($countryMap as $code => $name): ?>
              <option value="<?= $code ?>"><?= $code ?> — <?= htmlspecialchars($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-5">
            <label class="form-label">Регион</label>
            <input type="text" id="s_region" class="form-control" placeholder="Напр. Шанхай">
          </div>
          <div class="col-4">
            <label class="form-label">Активен</label>
            <select id="s_active" class="form-control"><option value="1">Да</option><option value="0">Нет</option></select>
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col">
            <label class="form-label">Тип договора</label>
            <select id="s_contract_type" class="form-control">
              <option value="">—</option>
              <?php foreach ($contractTypes as $k => $v): ?>
              <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col">
            <label class="form-label">Бренды (JSON: [1,2,3])</label>
            <input type="text" id="s_brands" class="form-control" placeholder='[1,2,3]'>
          </div>
        </div>
        <div class="mb-3"><label class="form-label">Условия оплаты</label><input type="text" id="s_terms" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Условия договора</label><textarea id="s_contract_terms" class="form-control" rows="2"></textarea></div>
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

<!-- ══ Bulk contract modal ══════════════════════════════════════════════════ -->
<div class="modal" id="bulkContractModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:380px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Сменить тип договора</h5>
        <button type="button" class="btn-close" onclick="closeModal('bulkContractModal')"></button>
      </div>
      <div class="modal-body">
        <select id="bulkNewContract" class="form-control">
          <option value="">—</option>
          <?php foreach ($contractTypes as $k => $v): ?>
          <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="admin-btn admin-btn-secondary" onclick="closeModal('bulkContractModal')">Отмена</button>
        <button class="admin-btn admin-btn-primary" onclick="SpPage.confirmBulkContract()">Применить</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="bulkContractModal-backdrop" style="display:none"></div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

// ── Grouping switcher ──────────────────────────────────────────────────────
const SpPage = {
    currentGroup: sessionStorage.getItem('suppliersGroupBy') || 'brand',
    _submitTimer: null,

    init() {
        this.switchGroup(this.currentGroup, false);
        this._initCheckboxes();
    },

    switchGroup(key, save = true) {
        this.currentGroup = key;
        if (save) sessionStorage.setItem('suppliersGroupBy', key);

        document.querySelectorAll('.sp-view-panel').forEach(el => el.style.display = 'none');
        const panel = document.getElementById('spView' + key.charAt(0).toUpperCase() + key.slice(1));
        if (panel) panel.style.display = '';

        document.querySelectorAll('.sp-group-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.group === key);
        });
    },

    submit() {
        document.getElementById('spFilterForm').submit();
    },

    debouncedSubmit() {
        clearTimeout(this._submitTimer);
        this._submitTimer = setTimeout(() => this.submit(), 450);
    },

    // ── Checkboxes & bulk ──────────────────────────────────────────────────
    _initCheckboxes() {
        document.querySelectorAll('.sp-check-all').forEach(cb => {
            cb.addEventListener('change', function() {
                const group = this.dataset.group;
                document.querySelectorAll('.sp-row-check').forEach(r => {
                    // only in same group table
                    const tbl = r.closest('table');
                    const headerCb = tbl ? tbl.querySelector('.sp-check-all') : null;
                    if (headerCb && headerCb.dataset.group === group) {
                        r.checked = cb.checked;
                    }
                });
                SpPage._updateBulkBar();
            });
        });

        document.querySelectorAll('.sp-row-check').forEach(cb => {
            cb.addEventListener('change', () => this._updateBulkBar());
        });

        document.getElementById('spBulkAction').addEventListener('change', function() {
            document.getElementById('spBulkContract').style.display =
                this.value === 'contract' ? '' : 'none';
        });
    },

    _getSelected() {
        return [...document.querySelectorAll('.sp-row-check:checked')].map(cb => parseInt(cb.value));
    },

    _updateBulkBar() {
        const ids  = this._getSelected();
        const bar  = document.getElementById('spBulkBar');
        const cnt  = document.getElementById('spBulkCount');
        cnt.textContent = ids.length;
        bar.classList.toggle('visible', ids.length > 0);
    },

    clearBulk() {
        document.querySelectorAll('.sp-row-check, .sp-check-all').forEach(cb => cb.checked = false);
        this._updateBulkBar();
    },

    applyBulk() {
        const ids    = this._getSelected();
        const action = document.getElementById('spBulkAction').value;
        if (!ids.length) { alert('Не выбрано ни одного поставщика'); return; }
        if (!action)     { alert('Выберите действие'); return; }

        if (action === 'contract') {
            showModal('bulkContractModal');
            return;
        }

        const isActive = action === 'activate' ? 1 : 0;
        if (!confirm(`${action === 'activate' ? 'Активировать' : 'Деактивировать'} ${ids.length} поставщиков?`)) return;

        Promise.all(ids.map(id =>
            fetch('/admin/procurement/supplier-save', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
                body: JSON.stringify({ id, is_active: isActive })
            }).then(r => r.json())
        )).then(() => window.location.reload());
    },

    confirmBulkContract() {
        const ct  = document.getElementById('bulkNewContract').value;
        const ids = this._getSelected();
        if (!ct)       { alert('Выберите тип договора'); return; }
        if (!ids.length) return;

        closeModal('bulkContractModal');
        Promise.all(ids.map(id =>
            fetch('/admin/procurement/supplier-save', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
                body: JSON.stringify({ id, contract_type: ct })
            }).then(r => r.json())
        )).then(() => window.location.reload());
    },
};

// ── Supplier modal helpers ────────────────────────────────────────────────
function openSupplierModal() {
    document.getElementById('supplierModalTitle').textContent = 'Новый поставщик';
    document.getElementById('s_id').value = '0';
    ['s_name','s_contact','s_phone','s_email','s_address','s_region','s_terms','s_notes','s_contract_terms','s_brands'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('s_country').value       = '';
    document.getElementById('s_active').value        = '1';
    document.getElementById('s_contract_type').value = '';
    showModal('supplierModal');
}

function editSupplier(data) {
    document.getElementById('supplierModalTitle').textContent = 'Изменить поставщика';
    document.getElementById('s_id').value              = data.id;
    document.getElementById('s_name').value            = data.name             || '';
    document.getElementById('s_contact').value         = data.contact_person   || '';
    document.getElementById('s_phone').value           = data.phone            || '';
    document.getElementById('s_email').value           = data.email            || '';
    document.getElementById('s_address').value         = data.address          || '';
    document.getElementById('s_country').value         = data.country          || '';
    document.getElementById('s_region').value          = data.region           || '';
    document.getElementById('s_terms').value           = data.payment_terms    || '';
    document.getElementById('s_notes').value           = data.notes            || '';
    document.getElementById('s_brands').value          = data.brands           || '';
    document.getElementById('s_contract_type').value   = data.contract_type    || '';
    document.getElementById('s_contract_terms').value  = data.contract_terms   || '';
    document.getElementById('s_active').value          = data.is_active ? '1' : '0';
    showModal('supplierModal');
}

function showModal(id) {
    document.getElementById(id).style.display = 'block';
    document.getElementById(id).classList.add('show');
    const bd = document.getElementById(id + '-backdrop');
    if (bd) { bd.style.display = 'block'; bd.classList.add('show'); }
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.getElementById(id).classList.remove('show');
    const bd = document.getElementById(id + '-backdrop');
    if (bd) { bd.style.display = 'none'; bd.classList.remove('show'); }
}

function saveSupplier() {
    const brandsRaw = document.getElementById('s_brands').value.trim();
    let brandsVal = null;
    if (brandsRaw) {
        try { brandsVal = JSON.stringify(JSON.parse(brandsRaw)); }
        catch(e) { alert('Бренды: неверный JSON. Используйте формат [1,2,3]'); return; }
    }
    const body = {
        id:             parseInt(document.getElementById('s_id').value),
        name:           document.getElementById('s_name').value,
        contact_person: document.getElementById('s_contact').value,
        phone:          document.getElementById('s_phone').value,
        email:          document.getElementById('s_email').value,
        address:        document.getElementById('s_address').value,
        country:        document.getElementById('s_country').value,
        region:         document.getElementById('s_region').value,
        payment_terms:  document.getElementById('s_terms').value,
        notes:          document.getElementById('s_notes').value,
        brands:         brandsVal,
        contract_type:  document.getElementById('s_contract_type').value || null,
        contract_terms: document.getElementById('s_contract_terms').value,
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

// Init on load
document.addEventListener('DOMContentLoaded', () => SpPage.init());
</script>
