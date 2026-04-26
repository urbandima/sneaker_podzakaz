<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Supplier $supplier */
/** @var app\backend\modules\catalog\models\Brand[] $allBrands */

use app\backend\modules\procurement\models\Supplier;

$this->title = 'Поставщик: ' . $supplier->name;

$stats         = $supplier->getPurchaseStats();
$brandsList    = $supplier->getBrandsList();
$brandMap      = [];
foreach ($allBrands as $b) $brandMap[$b->id] = $b->name;
$supplierBrands= $supplier->getBrandIds();
$contractColor = $supplier->getContractTypeBadgeColor();
$countryMap    = Supplier::getCountryMap();
$contractTypes = Supplier::getContractTypeOptions();
$flagEmoji     = $supplier->country ? Supplier::getCountryFlagEmoji($supplier->country) : '';
?>

<style>
/* ── Supplier CRM card ────────────────────────────────────────── */
.sv-wrap { padding: 0 0 40px; }

.sv-hero {
    background: linear-gradient(135deg, #1a2236 0%, #202223 55%, #00614a 100%);
    border-radius: var(--admin-radius-lg, 12px);
    padding: 28px 32px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.sv-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 80% 50%, rgba(0,176,118,.12) 0%, transparent 70%);
    pointer-events: none;
}
.sv-avatar {
    flex-shrink: 0;
    width: 72px; height: 72px;
    border-radius: 14px;
    background: linear-gradient(135deg, #00b37e 0%, #00614a 100%);
    border: 2px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 700; color: #fff;
    box-shadow: 0 6px 18px rgba(0,0,0,.25);
    z-index: 1;
}
.sv-hero-info { flex: 1; min-width: 0; z-index: 1; }
.sv-hero-name {
    font-size: 22px; font-weight: 700; color: #fff;
    line-height: 1.2; margin-bottom: 6px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sv-hero-meta { font-size: 13px; color: rgba(255,255,255,.65); display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.sv-hero-meta a { color: rgba(255,255,255,.8); text-decoration: none; }
.sv-hero-meta a:hover { color: #fff; }
.sv-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.sv-badge {
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; letter-spacing: .3px;
}
.sv-badge-active   { background: rgba(0,176,118,.22); color: #6effc9; border: 1px solid rgba(0,176,118,.4); }
.sv-badge-inactive { background: rgba(255,100,100,.18); color: #ffaaaa; border: 1px solid rgba(255,100,100,.3); }
.sv-hero-actions { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; z-index: 1; }
.sv-btn {
    padding: 7px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 500; cursor: pointer; border: none;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: all .15s; white-space: nowrap;
}
.sv-btn.primary { background: var(--admin-accent, #008060); color: #fff; }
.sv-btn.primary:hover { filter: brightness(1.1); }
.sv-btn.ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); }
.sv-btn.ghost:hover { background: rgba(255,255,255,.22); }
.sv-btn.back { position: absolute; top: 16px; left: 16px; width: 30px; height: 30px; padding: 0;
    border-radius: 50%; justify-content: center; font-size: 14px; }

/* Stats row */
.sv-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .sv-stats { grid-template-columns: repeat(2, 1fr); } }
.sv-stat {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e1e3e5);
    border-radius: var(--admin-radius, 8px);
    padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: var(--admin-shadow-sm, 0 1px 3px rgba(0,0,0,.07));
}
.sv-stat-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.sv-stat-icon.orders  { background: #eff6ff; color: #3b82f6; }
.sv-stat-icon.money   { background: #f0fdf4; color: #16a34a; }
.sv-stat-icon.avg     { background: #fefce8; color: #ca8a04; }
.sv-stat-icon.date    { background: #fdf4ff; color: #a855f7; }
.sv-stat-val  { font-size: 20px; font-weight: 700; color: var(--admin-text, #202223); line-height: 1.2; }
.sv-stat-lbl  { font-size: 11px; color: var(--admin-text-secondary, #6d7175); }

/* Two-column body */
.sv-body {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
    align-items: start;
}
@media (max-width: 900px) { .sv-body { grid-template-columns: 1fr; } }

.sv-card {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e1e3e5);
    border-radius: var(--admin-radius, 8px);
    overflow: hidden;
    margin-bottom: 14px;
}
.sv-card-title {
    font-size: 13px; font-weight: 700;
    color: var(--admin-text-secondary, #6d7175);
    text-transform: uppercase; letter-spacing: .05em;
    padding: 12px 16px 10px;
    border-bottom: 1px solid var(--admin-border, #e1e3e5);
    display: flex; align-items: center; gap: 8px;
}
.sv-card-body { padding: 16px; }

/* Inline-editable field */
.sv-field { margin-bottom: 14px; }
.sv-field:last-child { margin-bottom: 0; }
.sv-field-label { font-size: 11px; font-weight: 600; color: var(--admin-text-secondary, #6d7175);
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.sv-field-val {
    font-size: 13px; color: var(--admin-text, #202223);
    padding: 6px 8px; border-radius: 6px;
    border: 1.5px solid transparent;
    cursor: text;
    transition: border-color .15s;
    min-height: 30px;
    position: relative;
}
.sv-field-val:hover { border-color: var(--admin-border, #e1e3e5); background: var(--admin-bg, #f6f6f7); }
.sv-field-val:hover::after {
    content: '\F4CA'; /* bi-pencil */
    font-family: 'bootstrap-icons';
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    font-size: 11px; color: #9ca3af;
}
.sv-field-val.editing { border-color: var(--admin-accent, #008060); background: #fff; cursor: default; }
.sv-field-val.editing::after { display: none; }

/* Brand tags */
.sv-brand-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 14px; margin: 2px 3px;
    font-size: 12px; font-weight: 600;
    background: #eff6ff; color: #2563eb;
    border: 1px solid #bfdbfe;
}
.sv-brand-tag .remove-btn {
    width: 14px; height: 14px; border-radius: 50%;
    background: #bfdbfe; color: #2563eb;
    border: none; cursor: pointer; font-size: 10px; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    padding: 0;
}

/* Contract badge */
.sv-contract-badge {
    display: inline-block; padding: 4px 12px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
}

/* History table */
.sv-history-table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
.sv-history-table th { font-size: 11px; font-weight: 600; color: #6d7175;
    text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid var(--admin-border, #e1e3e5); }
.sv-history-table td { padding: 8px 10px; border-bottom: 1px solid var(--admin-border, #e1e3e5); }
.sv-history-table tr:last-child td { border-bottom: none; }
</style>

<div class="sv-wrap">

<!-- Back button -->
<a href="/admin/procurement/suppliers" class="sv-btn ghost sv-btn back" style="margin-bottom:14px;display:inline-flex;width:auto;padding:6px 14px;position:relative;top:auto;left:auto;">
    <i class="bi bi-arrow-left"></i> Поставщики
</a>

<!-- ── Hero ─────────────────────────────────────────────────────────────── -->
<div class="sv-hero">
    <div class="sv-avatar"><?= mb_strtoupper(mb_substr($supplier->name, 0, 1)) ?></div>
    <div class="sv-hero-info">
        <div class="sv-hero-name" id="svHeroName"><?= htmlspecialchars($supplier->name) ?></div>
        <div class="sv-hero-meta">
            <?php if ($supplier->contact_person): ?>
            <span><i class="bi bi-person"></i> <?= htmlspecialchars($supplier->contact_person) ?></span>
            <?php endif; ?>
            <?php if ($supplier->phone): ?>
            <a href="tel:<?= htmlspecialchars($supplier->phone) ?>"><i class="bi bi-telephone"></i> <?= htmlspecialchars($supplier->phone) ?></a>
            <?php endif; ?>
            <?php if ($supplier->email): ?>
            <a href="mailto:<?= htmlspecialchars($supplier->email) ?>"><i class="bi bi-envelope"></i> <?= htmlspecialchars($supplier->email) ?></a>
            <?php endif; ?>
            <?php if ($supplier->country): ?>
            <span><?= $flagEmoji ?> <?= htmlspecialchars($supplier->getCountryName()) ?><?= $supplier->region ? ', ' . htmlspecialchars($supplier->region) : '' ?></span>
            <?php endif; ?>
        </div>
        <div class="sv-hero-badges">
            <span class="sv-badge <?= $supplier->is_active ? 'sv-badge-active' : 'sv-badge-inactive' ?>">
                <?= $supplier->is_active ? 'Активен' : 'Неактивен' ?>
            </span>
            <?php if ($supplier->contract_type): ?>
            <span class="sv-badge" style="background:<?= $contractColor ?>22;color:<?= $contractColor ?>;border:1px solid <?= $contractColor ?>44">
                <?= htmlspecialchars($supplier->getContractTypeLabel()) ?>
            </span>
            <?php endif; ?>
            <?php foreach ($brandsList as $b): ?>
            <span class="sv-brand-tag"><?= htmlspecialchars($b->name) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="sv-hero-actions">
        <button class="sv-btn primary" onclick="openEditModal()"><i class="bi bi-pencil"></i> Редактировать</button>
        <button class="sv-btn ghost" onclick="deleteSupplier(<?= $supplier->id ?>)"><i class="bi bi-trash"></i> Удалить</button>
    </div>
</div>

<!-- ── Stats ─────────────────────────────────────────────────────────────── -->
<div class="sv-stats">
    <div class="sv-stat">
        <div class="sv-stat-icon orders"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="sv-stat-val"><?= number_format($stats['count']) ?></div>
            <div class="sv-stat-lbl">Закупок</div>
        </div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon money"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="sv-stat-val"><?= number_format($stats['total'], 2, '.', ' ') ?> BYN</div>
            <div class="sv-stat-lbl">Сумма закупок</div>
        </div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon avg"><i class="bi bi-graph-up"></i></div>
        <div>
            <div class="sv-stat-val"><?= number_format($stats['avg'], 2, '.', ' ') ?> BYN</div>
            <div class="sv-stat-lbl">Средняя закупка</div>
        </div>
    </div>
    <div class="sv-stat">
        <div class="sv-stat-icon date"><i class="bi bi-calendar3"></i></div>
        <div>
            <div class="sv-stat-val"><?= $stats['last_date'] ? date('d.m.Y', strtotime($stats['last_date'])) : '—' ?></div>
            <div class="sv-stat-lbl">Последняя закупка</div>
        </div>
    </div>
</div>

<!-- ── Two-column body ───────────────────────────────────────────────────── -->
<div class="sv-body">

    <!-- LEFT column -->
    <div class="sv-left">

        <!-- Contact info card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-person-lines-fill"></i> Контактная информация</div>
            <div class="sv-card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="sv-field">
                            <div class="sv-field-label">Название</div>
                            <div class="sv-field-val" data-field="name" data-id="<?= $supplier->id ?>"
                                 onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->name) ?></div>
                        </div>
                        <div class="sv-field">
                            <div class="sv-field-label">Контактное лицо</div>
                            <div class="sv-field-val" data-field="contact_person" data-id="<?= $supplier->id ?>"
                                 onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->contact_person ?? '') ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="sv-field">
                            <div class="sv-field-label">Телефон</div>
                            <div class="sv-field-val" data-field="phone" data-id="<?= $supplier->id ?>"
                                 onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->phone ?? '') ?></div>
                        </div>
                        <div class="sv-field">
                            <div class="sv-field-label">Email</div>
                            <div class="sv-field-val" data-field="email" data-id="<?= $supplier->id ?>"
                                 onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->email ?? '') ?></div>
                        </div>
                    </div>
                </div>
                <div class="sv-field">
                    <div class="sv-field-label">Адрес</div>
                    <div class="sv-field-val" data-field="address" data-id="<?= $supplier->id ?>" data-type="textarea"
                         onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->address ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Brands card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-bookmark-star"></i> Бренды</div>
            <div class="sv-card-body">
                <div id="svBrandTags" style="margin-bottom:10px">
                    <?php foreach ($brandsList as $b): ?>
                    <span class="sv-brand-tag">
                        <?= htmlspecialchars($b->name) ?>
                        <button class="remove-btn" onclick="SvBrands.remove(<?= $b->id ?>)" title="Убрать">×</button>
                    </span>
                    <?php endforeach; ?>
                    <?php if (!$brandsList): ?>
                    <span style="color:#9ca3af;font-size:12px;font-style:italic">Бренды не указаны</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <select id="svBrandAdd" class="compact-filter-select" style="flex:1">
                        <option value="">+ Добавить бренд</option>
                        <?php foreach ($allBrands as $b): ?>
                        <option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="compact-filter-btn compact-filter-btn--apply" onclick="SvBrands.add()">Добавить</button>
                </div>
            </div>
        </div>

        <!-- Contract terms card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-file-text"></i> Условия договора</div>
            <div class="sv-card-body">
                <div class="sv-field">
                    <div class="sv-field-label">Условия оплаты</div>
                    <div class="sv-field-val" data-field="payment_terms" data-id="<?= $supplier->id ?>" data-type="textarea"
                         onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->payment_terms ?? '') ?></div>
                </div>
                <div class="sv-field">
                    <div class="sv-field-label">Условия договора</div>
                    <div class="sv-field-val" data-field="contract_terms" data-id="<?= $supplier->id ?>" data-type="textarea"
                         onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->contract_terms ?? '') ?></div>
                </div>
                <div class="sv-field">
                    <div class="sv-field-label">Примечания</div>
                    <div class="sv-field-val" data-field="notes" data-id="<?= $supplier->id ?>" data-type="textarea"
                         onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->notes ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Purchase history -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-clock-history"></i> История закупок</div>
            <?php
            use app\backend\modules\procurement\models\PurchaseOrder;
            $orders = PurchaseOrder::find()
                ->where(['supplier_id' => $supplier->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(20)
                ->all();
            ?>
            <?php if (!$orders): ?>
            <div class="sv-card-body" style="color:#9ca3af;font-style:italic;font-size:13px">Закупок нет</div>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table class="sv-history-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Тип</th>
                            <th>Статус</th>
                            <th>Сумма BYN</th>
                            <th>Дата заказа</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a href="/admin/procurement/view?id=<?= $o->id ?>" style="color:var(--admin-accent,#008060)">
                                <?= htmlspecialchars($o->purchase_number) ?>
                            </a></td>
                            <td style="color:#6d7175"><?= htmlspecialchars($o->order_type ?? '—') ?></td>
                            <td><?= htmlspecialchars($o->status ?? '—') ?></td>
                            <td><?= $o->total_byn ? number_format($o->total_byn, 2, '.', ' ') : '—' ?></td>
                            <td style="color:#6d7175;font-size:12px"><?= $o->ordered_at ? date('d.m.Y', strtotime($o->ordered_at)) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- .sv-left -->

    <!-- RIGHT sidebar -->
    <div class="sv-right">

        <!-- Geo card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-geo-alt"></i> ГЕО</div>
            <div class="sv-card-body">
                <div class="sv-field">
                    <div class="sv-field-label">Страна</div>
                    <div id="svCountryDisplay" style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:20px"><?= $flagEmoji ?></span>
                        <span style="font-size:13px"><?= htmlspecialchars($supplier->getCountryName()) ?></span>
                        <button class="admin-btn admin-btn-sm admin-btn-secondary" style="margin-left:auto"
                                onclick="SvInline.startSelectEdit(this.previousElementSibling.previousElementSibling.previousElementSibling, 'country')">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <select id="svCountrySelect" class="form-control" style="display:none;margin-top:6px"
                            onchange="SvInline.saveField(<?= $supplier->id ?>, 'country', this.value).then(()=>location.reload())">
                        <option value="">—</option>
                        <?php foreach ($countryMap as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $supplier->country === $code ? 'selected' : '' ?>>
                            <?= $code ?> — <?= htmlspecialchars($name) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sv-field" style="margin-top:10px">
                    <div class="sv-field-label">Регион</div>
                    <div class="sv-field-val" data-field="region" data-id="<?= $supplier->id ?>"
                         onclick="SvInline.startEdit(this)"><?= htmlspecialchars($supplier->region ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Contract type card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-file-earmark-check"></i> Тип договора</div>
            <div class="sv-card-body">
                <?php if ($supplier->contract_type): ?>
                <span class="sv-contract-badge" style="background:<?= $contractColor ?>22;color:<?= $contractColor ?>;border:1px solid <?= $contractColor ?>44">
                    <?= htmlspecialchars($supplier->getContractTypeLabel()) ?>
                </span>
                <?php else: ?>
                <span style="color:#9ca3af;font-size:12px;font-style:italic">Не указан</span>
                <?php endif; ?>
                <div style="margin-top:10px">
                    <select class="form-control" id="svContractSelect"
                            onchange="SvInline.saveField(<?= $supplier->id ?>, 'contract_type', this.value).then(()=>location.reload())">
                        <option value="">— Изменить —</option>
                        <?php foreach ($contractTypes as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $supplier->contract_type === $k ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Quick info card -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-info-circle"></i> Сведения</div>
            <div class="sv-card-body">
                <div class="sv-field">
                    <div class="sv-field-label">Статус</div>
                    <select class="form-control" onchange="SvInline.saveField(<?= $supplier->id ?>, 'is_active', this.value).then(()=>location.reload())">
                        <option value="1" <?= $supplier->is_active ? 'selected' : '' ?>>Активен</option>
                        <option value="0" <?= !$supplier->is_active ? 'selected' : '' ?>>Неактивен</option>
                    </select>
                </div>
                <div class="sv-field">
                    <div class="sv-field-label">Дата создания</div>
                    <div style="font-size:13px;color:#6d7175">
                        <?= $supplier->created_at ? date('d.m.Y', strtotime($supplier->created_at)) : '—' ?>
                    </div>
                </div>
                <div class="sv-field">
                    <div class="sv-field-label">ID</div>
                    <div style="font-size:12px;color:#9ca3af">#<?= $supplier->id ?></div>
                </div>
            </div>
        </div>

        <!-- Documents placeholder -->
        <div class="sv-card">
            <div class="sv-card-title"><i class="bi bi-paperclip"></i> Документы</div>
            <div class="sv-card-body" style="color:#9ca3af;font-style:italic;font-size:12px;text-align:center;padding:20px 16px">
                <i class="bi bi-folder2-open" style="font-size:28px;display:block;margin-bottom:8px;color:#d1d5db"></i>
                Документы не прикреплены
            </div>
        </div>

    </div><!-- .sv-right -->
</div><!-- .sv-body -->

</div><!-- .sv-wrap -->

<!-- Edit modal (full form) -->
<div class="modal" id="svEditModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:600px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Редактировать поставщика</h5>
        <button type="button" class="btn-close" onclick="closeModal('svEditModal')"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="em_id" value="<?= $supplier->id ?>">
        <div class="mb-3"><label class="form-label">Название *</label><input type="text" id="em_name" class="form-control" value="<?= htmlspecialchars($supplier->name) ?>"></div>
        <div class="mb-3"><label class="form-label">Контактное лицо</label><input type="text" id="em_contact" class="form-control" value="<?= htmlspecialchars($supplier->contact_person ?? '') ?>"></div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">Телефон</label><input type="text" id="em_phone" class="form-control" value="<?= htmlspecialchars($supplier->phone ?? '') ?>"></div>
          <div class="col"><label class="form-label">Email</label><input type="email" id="em_email" class="form-control" value="<?= htmlspecialchars($supplier->email ?? '') ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Адрес</label><textarea id="em_address" class="form-control" rows="2"><?= htmlspecialchars($supplier->address ?? '') ?></textarea></div>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="form-label">Страна</label>
            <select id="em_country" class="form-control">
              <option value="">—</option>
              <?php foreach ($countryMap as $code => $name): ?>
              <option value="<?= $code ?>" <?= $supplier->country === $code ? 'selected' : '' ?>><?= $code ?> — <?= htmlspecialchars($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4"><label class="form-label">Регион</label><input type="text" id="em_region" class="form-control" value="<?= htmlspecialchars($supplier->region ?? '') ?>"></div>
          <div class="col-4">
            <label class="form-label">Активен</label>
            <select id="em_active" class="form-control">
              <option value="1" <?= $supplier->is_active ? 'selected' : '' ?>>Да</option>
              <option value="0" <?= !$supplier->is_active ? 'selected' : '' ?>>Нет</option>
            </select>
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col">
            <label class="form-label">Тип договора</label>
            <select id="em_contract_type" class="form-control">
              <option value="">—</option>
              <?php foreach ($contractTypes as $k => $v): ?>
              <option value="<?= $k ?>" <?= $supplier->contract_type === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-3"><label class="form-label">Условия оплаты</label><input type="text" id="em_terms" class="form-control" value="<?= htmlspecialchars($supplier->payment_terms ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Условия договора</label><textarea id="em_contract_terms" class="form-control" rows="2"><?= htmlspecialchars($supplier->contract_terms ?? '') ?></textarea></div>
        <div class="mb-3"><label class="form-label">Примечания</label><textarea id="em_notes" class="form-control" rows="2"><?= htmlspecialchars($supplier->notes ?? '') ?></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="admin-btn admin-btn-secondary" onclick="closeModal('svEditModal')">Отмена</button>
        <button class="admin-btn admin-btn-primary" onclick="saveEditModal()">Сохранить</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="svEditModal-backdrop" style="display:none"></div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;
let svCurrentBrands = <?= json_encode($supplier->getBrandIds()) ?>;
const svBrandMap    = <?= json_encode($brandMap) ?>;

// ── Inline editing ─────────────────────────────────────────────────────────
const SvInline = {
    startEdit(el) {
        if (el.classList.contains('editing')) return;
        const type  = el.dataset.type || 'text';
        const val   = el.textContent.trim();
        el.classList.add('editing');
        el.setAttribute('data-orig', val);

        if (type === 'textarea') {
            const ta = document.createElement('textarea');
            ta.className = 'form-control'; ta.rows = 3; ta.value = val;
            ta.style.fontSize = '13px';
            ta.addEventListener('blur', () => this.commit(el, ta.value));
            ta.addEventListener('keydown', e => { if (e.key === 'Escape') this.cancel(el); });
            el.innerHTML = ''; el.appendChild(ta);
            ta.focus();
        } else {
            const inp = document.createElement('input');
            inp.type = 'text'; inp.className = 'form-control'; inp.value = val;
            inp.style.fontSize = '13px';
            inp.addEventListener('blur',   () => this.commit(el, inp.value));
            inp.addEventListener('keydown', e => {
                if (e.key === 'Enter')  { inp.blur(); }
                if (e.key === 'Escape') { this.cancel(el); }
            });
            el.innerHTML = ''; el.appendChild(inp);
            inp.focus(); inp.select();
        }
    },

    cancel(el) {
        el.classList.remove('editing');
        el.textContent = el.getAttribute('data-orig');
    },

    commit(el, newVal) {
        el.classList.remove('editing');
        const field = el.dataset.field;
        const id    = el.dataset.id;
        if (newVal === el.getAttribute('data-orig')) { el.textContent = newVal; return; }
        this.saveField(id, field, newVal).then(() => {
            el.textContent = newVal;
        });
    },

    saveField(id, field, value) {
        return fetch('/admin/procurement/supplier-save', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
            body: JSON.stringify({ id: parseInt(id), [field]: value })
        }).then(r => r.json()).then(d => {
            if (!d.success) alert('Ошибка: ' + JSON.stringify(d.errors || d.message));
        });
    },

    startSelectEdit(dummyEl, field) {
        // toggle country select
        const sel = document.getElementById('svCountrySelect');
        const disp = document.getElementById('svCountryDisplay');
        sel.style.display = '';
        disp.style.display = 'none';
    },
};

// ── Brands ──────────────────────────────────────────────────────────────────
const SvBrands = {
    saveBrands(ids) {
        return SvInline.saveField(<?= $supplier->id ?>, 'brands', JSON.stringify(ids));
    },
    remove(id) {
        svCurrentBrands = svCurrentBrands.filter(b => b !== id);
        this.saveBrands(svCurrentBrands).then(() => this.renderTags());
    },
    add() {
        const sel = document.getElementById('svBrandAdd');
        const id  = parseInt(sel.value);
        if (!id || svCurrentBrands.includes(id)) { sel.value = ''; return; }
        svCurrentBrands.push(id);
        sel.value = '';
        this.saveBrands(svCurrentBrands).then(() => this.renderTags());
    },
    renderTags() {
        const container = document.getElementById('svBrandTags');
        if (!svCurrentBrands.length) {
            container.innerHTML = '<span style="color:#9ca3af;font-size:12px;font-style:italic">Бренды не указаны</span>';
            return;
        }
        container.innerHTML = svCurrentBrands.map(id =>
            `<span class="sv-brand-tag">${svBrandMap[id] || 'Бренд #' + id}
                <button class="remove-btn" onclick="SvBrands.remove(${id})" title="Убрать">×</button>
             </span>`
        ).join('');
    }
};

// ── Edit modal ──────────────────────────────────────────────────────────────
function openEditModal() {
    showModal('svEditModal');
}

function saveEditModal() {
    const body = {
        id:             parseInt(document.getElementById('em_id').value),
        name:           document.getElementById('em_name').value,
        contact_person: document.getElementById('em_contact').value,
        phone:          document.getElementById('em_phone').value,
        email:          document.getElementById('em_email').value,
        address:        document.getElementById('em_address').value,
        country:        document.getElementById('em_country').value,
        region:         document.getElementById('em_region').value,
        payment_terms:  document.getElementById('em_terms').value,
        notes:          document.getElementById('em_notes').value,
        contract_type:  document.getElementById('em_contract_type').value || null,
        contract_terms: document.getElementById('em_contract_terms').value,
        is_active:      document.getElementById('em_active').value,
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
        if (d.success) window.location.href = '/admin/procurement/suppliers';
        else alert('Ошибка: ' + d.message);
    });
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
</script>
