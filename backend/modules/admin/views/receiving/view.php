<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Receiving $receiving */
/** @var app\backend\modules\procurement\models\ReceivingItem[] $items */
/** @var app\backend\modules\procurement\models\ReceivingExpense[] $expenses */
/** @var app\backend\modules\procurement\models\ReceivingDocument[] $documents */
/** @var app\backend\modules\procurement\models\ReceivingHistory[] $history */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\procurement\models\Receiving;
use app\backend\modules\procurement\models\ReceivingExpense;
use app\backend\modules\procurement\models\ReceivingDocument;

$this->title = $receiving->number;
$canEdit   = !$receiving->isFinal();
$canAccept = $receiving->canTransitionTo(Receiving::STATUS_ACCEPTED)
          || $receiving->canTransitionTo(Receiving::STATUS_PARTIAL);
?>
<style>
/* Sticky header */
.rcv-sticky-header{position:sticky;top:0;z-index:100;background:#fff;border-bottom:1.5px solid var(--admin-border,#e5e7eb);display:flex;align-items:center;gap:12px;padding:10px 20px;flex-wrap:wrap;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.rcv-sticky-header .rcv-number{font-size:1rem;font-weight:800;color:var(--admin-text-primary,#111)}
.rcv-sticky-header .status-pill{font-size:.75rem;padding:3px 10px;border-radius:20px;font-weight:700;color:#fff}
.rcv-sticky-header .header-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s}
.btn-success{background:#059669;color:#fff}.btn-success:hover{background:#047857;color:#fff}
.btn-primary{background:var(--admin-primary,#2563eb);color:#fff}.btn-primary:hover{background:#1d4ed8;color:#fff}
.btn-outline{background:#fff;color:#374151;border:1.5px solid #d1d5db}.btn-outline:hover{background:#f3f4f6}
.btn-danger{background:#dc2626;color:#fff}.btn-danger:hover{background:#b91c1c;color:#fff}
/* Layout */
.rcv-layout{display:grid;grid-template-columns:1fr 320px;gap:16px;padding:16px 20px;max-width:1400px}
@media(max-width:900px){.rcv-layout{grid-template-columns:1fr}}
/* Cards */
.rcv-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:14px;overflow:hidden}
.rcv-card-title{font-size:.8125rem;font-weight:700;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.05em;padding:12px 16px 8px;border-bottom:1px solid var(--admin-border,#e5e7eb)}
.rcv-card-body{padding:12px 16px}
/* Items table */
.ri-table{width:100%;border-collapse:collapse;font-size:.8125rem}
.ri-table th{padding:7px 10px;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;text-align:left;border-bottom:1.5px solid #e5e7eb;background:#fafafa}
.ri-table td{padding:7px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
.ri-table tr:hover td{background:#fafafa}
.ri-input{height:28px;width:60px;border:1.5px solid #e5e7eb;border-radius:6px;padding:0 6px;font-size:.8125rem;text-align:right;outline:none}
.ri-input:focus{border-color:var(--admin-primary,#2563eb)}
.ri-input:disabled{background:#f9fafb;color:#9ca3af}
/* Expense table */
.re-table{width:100%;border-collapse:collapse;font-size:.8125rem}
.re-table th{padding:6px 8px;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid #e5e7eb;background:#fafafa}
.re-table td{padding:7px 8px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
/* Status toggles */
.status-btn{padding:5px 12px;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid transparent;background:#f3f4f6;color:#374151;margin:2px}
.status-btn.active{color:#fff}
.status-btn:disabled{opacity:.5;cursor:default}
/* Totals */
.totals-row{display:flex;justify-content:space-between;padding:6px 0;font-size:.8125rem;border-bottom:1px solid #f0f0f0}
.totals-row:last-child{border-bottom:none;font-weight:800;font-size:.9rem}
/* Documents */
.doc-item{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f5f5f5;font-size:.8125rem}
.doc-item:last-child{border-bottom:none}
.doc-icon{font-size:1.1rem;color:#2563eb;width:20px;text-align:center}
/* Timeline */
.timeline-item{display:flex;gap:10px;padding:8px 0;font-size:.8125rem}
.timeline-dot{width:8px;height:8px;border-radius:50%;background:var(--admin-primary,#2563eb);margin-top:5px;flex-shrink:0}
.timeline-time{color:#9ca3af;font-size:.75rem;min-width:90px}
/* Drop zone */
.drop-zone{border:2px dashed #d1d5db;border-radius:10px;padding:20px;text-align:center;color:#9ca3af;font-size:.8125rem;cursor:pointer;transition:all .2s;margin-top:8px}
.drop-zone:hover,.drop-zone.dragover{border-color:var(--admin-primary,#2563eb);background:#eff6ff;color:#2563eb}
/* Inline editable */
[contenteditable]{outline:none;border-bottom:1.5px dashed transparent;transition:border-color .15s;cursor:text}
[contenteditable]:hover{border-bottom-color:#d1d5db}
[contenteditable]:focus{border-bottom-color:var(--admin-primary,#2563eb)}
</style>

<!-- Sticky Header -->
<div class="rcv-sticky-header" id="rcvStickyHeader">
    <a href="<?= Url::to(['/admin/receiving']) ?>" class="btn btn-outline" style="padding:5px 10px;font-size:.78rem">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
    <span class="rcv-number"><?= Html::encode($receiving->number) ?></span>
    <span class="status-pill" id="statusPill" style="background:<?= $receiving->getStatusColor() ?>">
        <?= $receiving->getStatusLabel() ?>
    </span>
    <div class="header-actions">
        <?php if ($canAccept): ?>
        <button class="btn btn-success" onclick="rcvAccept(<?= $receiving->id ?>)">
            <i class="bi bi-check-lg"></i> Принять на склад
        </button>
        <?php endif; ?>
        <?php if ($canEdit && !in_array($receiving->status, [Receiving::STATUS_ACCEPTED, Receiving::STATUS_PARTIAL])): ?>
        <button class="btn btn-danger" onclick="rcvCancel(<?= $receiving->id ?>)" title="Отменить">
            <i class="bi bi-x-circle"></i> Отменить
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="rcv-layout">
    <!-- Left column -->
    <div>
        <!-- Items card -->
        <div class="rcv-card" id="itemsCard">
            <div class="rcv-card-title" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="bi bi-boxes"></i> Товары
                    <span style="font-size:.78rem;font-weight:400;color:#9ca3af" id="itemsCount">
                        (<?= count($items) ?> поз., <?= $receiving->total_qty_expected ?> ед.)
                    </span>
                </span>
                <?php if ($canEdit): ?>
                <button class="btn btn-outline" style="padding:3px 10px;font-size:.78rem" onclick="rcvShowAddItem()">
                    <i class="bi bi-plus"></i> Добавить
                </button>
                <?php endif; ?>
            </div>
            <div style="overflow-x:auto">
            <table class="ri-table">
                <thead>
                    <tr>
                        <th>Товар / Размер</th>
                        <th style="text-align:right">Ожид.</th>
                        <th style="text-align:right">Прибыло</th>
                        <th style="text-align:right">Дефект</th>
                        <th style="text-align:right">Цена BYN</th>
                        <th style="text-align:right">Расходы</th>
                        <th style="text-align:right">Итого</th>
                        <?php if ($canEdit): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="itemsTbody">
                <?php foreach ($items as $item): ?>
                <tr id="item-row-<?= $item->id ?>">
                    <td>
                        <div style="font-weight:600;font-size:.8125rem">
                            <?= Html::encode($item->product ? $item->product->name : "Товар #{$item->product_id}") ?>
                        </div>
                        <?php if ($item->size): ?>
                            <div style="font-size:.75rem;color:#6b7280"><?= Html::encode($item->size->size) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right"><?= $item->qty_expected ?></td>
                    <td style="text-align:right">
                        <?php if ($canEdit): ?>
                        <input type="number" class="ri-input" min="0" value="<?= $item->qty_arrived ?>"
                               onchange="rcvUpdateItem(<?= $item->id ?>, 'qty_arrived', this.value)"
                               id="qa-<?= $item->id ?>">
                        <?php else: ?>
                            <?= $item->qty_arrived ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <?php if ($canEdit): ?>
                        <input type="number" class="ri-input" min="0" value="<?= $item->qty_defected ?>"
                               onchange="rcvUpdateItem(<?= $item->id ?>, 'qty_defected', this.value)"
                               id="qd-<?= $item->id ?>">
                        <?php else: ?>
                            <?= $item->qty_defected ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right"><?= number_format($item->unit_cost_byn, 2) ?></td>
                    <td style="text-align:right;color:#059669" id="alloc-<?= $item->id ?>"><?= number_format($item->allocated_expenses_byn, 2) ?></td>
                    <td style="text-align:right;font-weight:700" id="final-<?= $item->id ?>"><?= number_format($item->final_cost_byn, 2) ?></td>
                    <?php if ($canEdit): ?>
                    <td>
                        <button class="btn btn-outline" style="padding:2px 7px;font-size:.72rem;color:#dc2626;border-color:#dc2626"
                                onclick="rcvRemoveItem(<?= $item->id ?>)" title="Удалить позицию">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                <tr id="noItemsRow">
                    <td colspan="8" style="text-align:center;padding:20px;color:#9ca3af">
                        Нет позиций. <a href="#" onclick="rcvShowAddItem();return false">Добавить товар</a>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Add Item modal (inline) -->
        <div id="addItemPanel" style="display:none" class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-plus-circle"></i> Добавить позицию</div>
            <div class="rcv-card-body">
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                    <div style="flex:2;min-width:200px">
                        <label style="font-size:.75rem;color:#6b7280;display:block;margin-bottom:4px">Товар</label>
                        <input type="text" id="itemSearch" class="compact-filter-input" style="width:100%"
                               placeholder="Поиск по названию или SKU…" oninput="rcvSearchProduct(this.value)">
                        <div id="itemSearchResults" style="display:none;position:absolute;z-index:200;background:#fff;border:1.5px solid #e5e7eb;border-radius:8px;max-height:200px;overflow-y:auto;min-width:280px;box-shadow:0 4px 16px rgba(0,0,0,.1)"></div>
                        <input type="hidden" id="itemProductId">
                        <select id="itemSizeId" class="compact-filter-select" style="width:100%;margin-top:6px;display:none">
                            <option value="">— Размер (если есть) —</option>
                        </select>
                    </div>
                    <div style="flex:0 0 80px">
                        <label style="font-size:.75rem;color:#6b7280;display:block;margin-bottom:4px">Кол-во</label>
                        <input type="number" id="itemQtyExpected" class="ri-input" value="1" min="1" style="width:100%">
                    </div>
                    <div style="flex:0 0 100px">
                        <label style="font-size:.75rem;color:#6b7280;display:block;margin-bottom:4px">Цена</label>
                        <input type="number" id="itemUnitCost" class="ri-input" value="0" step="0.01" style="width:100%">
                    </div>
                    <div style="flex:0 0 70px">
                        <label style="font-size:.75rem;color:#6b7280;display:block;margin-bottom:4px">Валюта</label>
                        <select id="itemCurrency" class="compact-filter-select" style="width:100%">
                            <option>BYN</option><option>USD</option><option>EUR</option><option>CNY</option>
                        </select>
                    </div>
                    <div style="flex:0 0 80px">
                        <label style="font-size:.75rem;color:#6b7280;display:block;margin-bottom:4px">Курс</label>
                        <input type="number" id="itemRate" class="ri-input" value="1" step="0.0001" style="width:100%">
                    </div>
                    <button class="btn btn-primary" onclick="rcvAddItem(<?= $receiving->id ?>)">
                        <i class="bi bi-plus"></i> Добавить
                    </button>
                    <button class="btn btn-outline" onclick="document.getElementById('addItemPanel').style.display='none'">
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Expenses card -->
        <div class="rcv-card">
            <div class="rcv-card-title" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="bi bi-receipt"></i> Расходы на поставку</span>
                <?php if ($canEdit): ?>
                <button class="btn btn-outline" style="padding:3px 10px;font-size:.78rem" onclick="rcvShowAddExpense()">
                    <i class="bi bi-plus"></i> Добавить расход
                </button>
                <?php endif; ?>
            </div>
            <div style="overflow-x:auto">
            <table class="re-table">
                <thead>
                    <tr>
                        <th>Тип</th>
                        <th style="text-align:right">Сумма</th>
                        <th>Валюта</th>
                        <th style="text-align:right">BYN</th>
                        <th>Распределение</th>
                        <th>Примечания</th>
                        <?php if ($canEdit): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="expensesTbody">
                <?php foreach ($expenses as $exp): ?>
                <tr id="exp-row-<?= $exp->id ?>">
                    <td><?= $exp->getTypeLabel() ?></td>
                    <td style="text-align:right"><?= number_format($exp->amount, 2) ?></td>
                    <td><?= Html::encode($exp->currency) ?></td>
                    <td style="text-align:right;font-weight:600"><?= number_format($exp->amount_byn, 2) ?></td>
                    <td><span style="font-size:.75rem;color:#059669"><?= $exp->getDistributionLabel() ?></span></td>
                    <td style="color:#6b7280;font-size:.75rem"><?= Html::encode($exp->notes ?? '') ?></td>
                    <?php if ($canEdit): ?>
                    <td>
                        <button class="btn btn-outline" style="padding:2px 7px;font-size:.72rem;color:#dc2626;border-color:#dc2626"
                                onclick="rcvRemoveExpense(<?= $exp->id ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$expenses): ?>
                <tr id="noExpensesRow">
                    <td colspan="7" style="text-align:center;padding:16px;color:#9ca3af">Расходов нет</td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>

            <!-- Add expense inline -->
            <div id="addExpensePanel" style="display:none" class="rcv-card-body" style="border-top:1px solid #f0f0f0">
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;padding-top:10px">
                    <select id="expType" class="compact-filter-select">
                        <?php foreach (ReceivingExpense::getTypes() as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="expAmount" class="ri-input" placeholder="Сумма" step="0.01" style="width:90px">
                    <select id="expCurrency" class="compact-filter-select" style="width:70px">
                        <option>BYN</option><option>USD</option><option>EUR</option><option>CNY</option>
                    </select>
                    <input type="number" id="expRate" class="ri-input" placeholder="Курс" value="1" step="0.0001" style="width:70px">
                    <select id="expDist" class="compact-filter-select">
                        <?php foreach (ReceivingExpense::getDistributionMethods() as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="expNotes" class="compact-filter-input" placeholder="Примечание" style="min-width:140px">
                    <button class="btn btn-primary" onclick="rcvAddExpense(<?= $receiving->id ?>)">Добавить</button>
                    <button class="btn btn-outline" onclick="document.getElementById('addExpensePanel').style.display='none'">Отмена</button>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-clock-history"></i> История</div>
            <div class="rcv-card-body" id="timelineBlock">
                <?php foreach ($history as $h): ?>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background:<?= Receiving::getStatusColors()[$h->to_status] ?? '#6b7280' ?>"></div>
                    <div class="timeline-time"><?= date('d.m.Y H:i', $h->changed_at) ?></div>
                    <div>
                        <strong><?= $h->getStatusLabel() ?></strong>
                        <?php if ($h->comment): ?>
                            <div style="color:#6b7280;font-size:.75rem"><?= Html::encode($h->comment) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$history): ?>
                    <div style="color:#9ca3af;font-size:.8125rem">Нет истории</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <!-- Supply info -->
        <div class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-building"></i> Поставка</div>
            <div class="rcv-card-body" style="font-size:.8125rem">
                <div style="margin-bottom:8px">
                    <div style="color:#6b7280;font-size:.72rem;margin-bottom:2px">Поставщик</div>
                    <?php if ($receiving->supplier): ?>
                        <div style="font-weight:600"><?= Html::encode($receiving->supplier->name) ?></div>
                    <?php elseif ($receiving->buyout_id): ?>
                        <div>
                            <a href="<?= Url::to(['/admin/buyout/view', 'id' => $receiving->buyout_id]) ?>">
                                Выкуп #<?= $receiving->buyout_id ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="color:#9ca3af">—</div>
                    <?php endif; ?>
                </div>
                <div style="margin-bottom:8px">
                    <div style="color:#6b7280;font-size:.72rem;margin-bottom:2px">Ожидалась</div>
                    <div><?= $receiving->expected_date ? date('d.m.Y', strtotime($receiving->expected_date)) : '—' ?></div>
                </div>
                <div style="margin-bottom:8px">
                    <div style="color:#6b7280;font-size:.72rem;margin-bottom:2px">Прибыла</div>
                    <div><?= $receiving->arrived_date ? date('d.m.Y', strtotime($receiving->arrived_date)) : '—' ?></div>
                </div>
                <?php if ($receiving->accepted_date): ?>
                <div style="margin-bottom:8px">
                    <div style="color:#6b7280;font-size:.72rem;margin-bottom:2px">Принята</div>
                    <div><?= date('d.m.Y H:i', strtotime($receiving->accepted_date)) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($receiving->notes): ?>
                <div style="margin-top:8px;padding:8px;background:#f9fafb;border-radius:8px;font-size:.78rem;color:#374151">
                    <?= Html::encode($receiving->notes) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status transitions -->
        <?php if ($canEdit && !$receiving->isFinal()): ?>
        <div class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-arrow-right-circle"></i> Сменить статус</div>
            <div class="rcv-card-body" style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach (Receiving::ALLOWED_TRANSITIONS[$receiving->status] as $ts): ?>
                <?php if (in_array($ts, [Receiving::STATUS_ACCEPTED, Receiving::STATUS_PARTIAL])) continue; ?>
                <button class="status-btn" style="background:<?= Receiving::getStatusColors()[$ts] ?>;color:#fff"
                        onclick="rcvSetStatus(<?= $receiving->id ?>, '<?= $ts ?>')">
                    <?= Receiving::getStatuses()[$ts] ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Totals -->
        <div class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-calculator"></i> Итоги</div>
            <div class="rcv-card-body" id="totalsBlock">
                <div class="totals-row">
                    <span>Стоимость товаров</span>
                    <span id="totalSubtotal"><?= number_format($receiving->subtotal_byn, 2) ?> BYN</span>
                </div>
                <div class="totals-row">
                    <span>Расходы</span>
                    <span id="totalExpenses"><?= number_format($receiving->expenses_total_byn, 2) ?> BYN</span>
                </div>
                <div class="totals-row" style="margin-top:6px;padding-top:6px;border-top:2px solid #e5e7eb">
                    <span>Итого с расходами</span>
                    <span id="totalFinal"><?= number_format($receiving->total_with_expenses_byn, 2) ?> BYN</span>
                </div>
                <div style="margin-top:10px;font-size:.75rem;color:#6b7280">
                    <div>Позиций: <strong id="totalItems"><?= $receiving->total_items ?></strong></div>
                    <div>Прибыло: <strong id="totalArrived"><?= $receiving->total_qty_arrived ?></strong> / <span id="totalExpected"><?= $receiving->total_qty_expected ?></span> ожидалось</div>
                    <?php if ($receiving->total_qty_defected > 0): ?>
                    <div style="color:#dc2626">Дефект: <strong><?= $receiving->total_qty_defected ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="rcv-card">
            <div class="rcv-card-title"><i class="bi bi-paperclip"></i> Документы</div>
            <div class="rcv-card-body">
                <div id="docsList">
                    <?php foreach ($documents as $doc): ?>
                    <div class="doc-item" id="doc-<?= $doc->id ?>">
                        <span class="doc-icon">
                            <?php if (str_contains($doc->mime_type ?? '', 'pdf')): ?>
                                <i class="bi bi-file-earmark-pdf"></i>
                            <?php elseif (str_contains($doc->mime_type ?? '', 'image')): ?>
                                <i class="bi bi-file-earmark-image"></i>
                            <?php else: ?>
                                <i class="bi bi-file-earmark"></i>
                            <?php endif; ?>
                        </span>
                        <div style="flex:1;min-width:0">
                            <a href="<?= $doc->getPublicUrl() ?>" target="_blank" style="font-size:.8rem;font-weight:600;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= Html::encode($doc->original_name) ?>
                            </a>
                            <div style="font-size:.72rem;color:#9ca3af"><?= $doc->getTypeLabel() ?> · <?= $doc->getFormattedSize() ?></div>
                        </div>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-outline" style="padding:2px 7px;font-size:.72rem;color:#dc2626;border-color:#dc2626"
                                onclick="rcvDeleteDocument(<?= $doc->id ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$documents): ?>
                        <div id="noDocsMsg" style="color:#9ca3af;font-size:.8rem">Документов нет</div>
                    <?php endif; ?>
                </div>
                <?php if ($canEdit): ?>
                <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                    <i class="bi bi-cloud-upload" style="font-size:1.4rem"></i>
                    <div>Перетащите файл или нажмите</div>
                    <div style="font-size:.72rem;margin-top:3px">PDF, JPG, PNG, DOC, XLS</div>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px">
                    <select id="uploadDocType" class="compact-filter-select" style="flex:1">
                        <?php foreach (ReceivingDocument::getTypes() as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="file" id="fileInput" style="display:none" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                       onchange="rcvUploadDocument(<?= $receiving->id ?>, this.files[0])">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const RCV_ID = <?= $receiving->id ?>;
const API    = {
    updateItem:    '<?= Url::to(['/admin/receiving/update-item']) ?>',
    removeItem:    '<?= Url::to(['/admin/receiving/remove-item']) ?>',
    addItem:       '<?= Url::to(['/admin/receiving/add-item']) ?>',
    addExpense:    '<?= Url::to(['/admin/receiving/add-expense']) ?>',
    removeExpense: '<?= Url::to(['/admin/receiving/remove-expense']) ?>',
    setStatus:     '<?= Url::to(['/admin/receiving/set-status']) ?>',
    accept:        '<?= Url::to(['/admin/receiving/accept']) ?>',
    cancel:        '<?= Url::to(['/admin/receiving/cancel']) ?>',
    products:      '<?= Url::to(['/admin/receiving/products']) ?>',
    uploadDoc:     '<?= Url::to(['/admin/receiving/upload-document']) ?>',
    deleteDoc:     '<?= Url::to(['/admin/receiving/delete-document']) ?>',
};

function rcvPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'},
        body: JSON.stringify(data)
    }).then(r => r.json());
}

function rcvUpdateTotals(t) {
    if (!t) return;
    document.getElementById('totalSubtotal').textContent  = Number(t.subtotal_byn).toFixed(2) + ' BYN';
    document.getElementById('totalExpenses').textContent  = Number(t.expenses_total_byn).toFixed(2) + ' BYN';
    document.getElementById('totalFinal').textContent     = Number(t.total_with_expenses_byn).toFixed(2) + ' BYN';
    document.getElementById('totalArrived').textContent   = t.total_qty_arrived;
    document.getElementById('totalExpected').textContent  = t.total_qty_expected;
    if (document.getElementById('totalItems')) {
        document.getElementById('totalItems').textContent = t.total_items || '';
    }
}

function rcvUpdateItem(itemId, field, value) {
    rcvPost(API.updateItem, {id: itemId, [field]: parseInt(value) || 0})
        .then(r => {
            if (r.success) {
                rcvUpdateTotals(r.totals);
                // refresh allocated/final for this row
                if (r.item) {
                    const allocEl = document.getElementById('alloc-' + itemId);
                    const finalEl = document.getElementById('final-' + itemId);
                    if (allocEl) allocEl.textContent = Number(r.item.allocated_expenses_byn).toFixed(2);
                    if (finalEl) finalEl.textContent = Number(r.item.final_cost_byn).toFixed(2);
                }
            }
        });
}

function rcvRemoveItem(itemId) {
    if (!confirm('Удалить позицию?')) return;
    rcvPost(API.removeItem, {id: itemId}).then(r => {
        if (r.success) {
            const row = document.getElementById('item-row-' + itemId);
            if (row) row.remove();
            rcvUpdateTotals(r.totals);
        }
    });
}

function rcvShowAddItem() {
    const p = document.getElementById('addItemPanel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
    document.getElementById('itemSearch').focus();
}

let _productTimeout = null;
function rcvSearchProduct(q) {
    clearTimeout(_productTimeout);
    if (!q) { document.getElementById('itemSearchResults').style.display = 'none'; return; }
    _productTimeout = setTimeout(() => {
        fetch(API.products + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(list => {
                const box = document.getElementById('itemSearchResults');
                if (!list.length) { box.style.display = 'none'; return; }
                box.innerHTML = list.map(p =>
                    `<div style="padding:8px 12px;cursor:pointer;font-size:.8125rem;border-bottom:1px solid #f0f0f0"
                         onmousedown="rcvSelectProduct(${p.id},'${p.name.replace(/'/g,"\\'")}',${JSON.stringify(p.sizes)})"
                         onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                        <strong>${p.name}</strong> ${p.sku ? '<span style=\'color:#9ca3af\'>' + p.sku + '</span>' : ''}
                     </div>`
                ).join('');
                box.style.display = 'block';
            });
    }, 250);
}

function rcvSelectProduct(id, name, sizes) {
    document.getElementById('itemProductId').value = id;
    document.getElementById('itemSearch').value = name;
    document.getElementById('itemSearchResults').style.display = 'none';
    const sizeEl = document.getElementById('itemSizeId');
    sizeEl.innerHTML = '<option value="">— Размер —</option>';
    if (sizes && sizes.length) {
        sizes.forEach(s => {
            sizeEl.innerHTML += `<option value="${s.id}">${s.size} (${s.price} BYN)</option>`;
        });
        sizeEl.style.display = 'block';
        document.getElementById('itemUnitCost').value = sizes[0].price || 0;
    } else {
        sizeEl.style.display = 'none';
    }
}

function rcvAddItem(rcvId) {
    const productId = document.getElementById('itemProductId').value;
    if (!productId) { alert('Выберите товар'); return; }
    const data = {
        receiving_id: rcvId,
        product_id: productId,
        size_id: document.getElementById('itemSizeId').value || null,
        qty_expected: parseInt(document.getElementById('itemQtyExpected').value) || 1,
        unit_cost_source: parseFloat(document.getElementById('itemUnitCost').value) || 0,
        source_currency: document.getElementById('itemCurrency').value,
        exchange_rate: parseFloat(document.getElementById('itemRate').value) || 1,
    };
    rcvPost(API.addItem, data).then(r => {
        if (r.success) {
            location.reload(); // reload to show new item row properly
        } else {
            alert('Ошибка: ' + (r.message || 'неизвестная ошибка'));
        }
    });
}

function rcvShowAddExpense() {
    const p = document.getElementById('addExpensePanel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

function rcvAddExpense(rcvId) {
    const amount = parseFloat(document.getElementById('expAmount').value);
    if (!amount) { alert('Введите сумму'); return; }
    const data = {
        receiving_id: rcvId,
        type: document.getElementById('expType').value,
        amount: amount,
        currency: document.getElementById('expCurrency').value,
        exchange_rate: parseFloat(document.getElementById('expRate').value) || 1,
        distribution_method: document.getElementById('expDist').value,
        notes: document.getElementById('expNotes').value,
    };
    rcvPost(API.addExpense, data).then(r => {
        if (r.success) {
            rcvUpdateTotals(r.totals);
            location.reload();
        } else {
            alert('Ошибка: ' + (r.message || ''));
        }
    });
}

function rcvRemoveExpense(expId) {
    if (!confirm('Удалить расход?')) return;
    rcvPost(API.removeExpense, {id: expId}).then(r => {
        if (r.success) {
            document.getElementById('exp-row-' + expId)?.remove();
            rcvUpdateTotals(r.totals);
        }
    });
}

function rcvSetStatus(id, status) {
    if (!confirm('Изменить статус на «' + status + '»?')) return;
    rcvPost(API.setStatus, {id, status}).then(r => {
        if (r.success) {
            document.getElementById('statusPill').textContent = r.status_label;
            document.getElementById('statusPill').style.background = r.status_color;
            location.reload();
        } else {
            alert('Ошибка: ' + r.message);
        }
    });
}

function rcvAccept(id) {
    if (!confirm('Принять товары на склад? Остатки будут обновлены.')) return;
    rcvPost(API.accept, {id}).then(r => {
        if (r.success) {
            alert('Приёмка завершена. Статус: ' + r.status_label);
            location.reload();
        } else {
            alert('Ошибка: ' + r.message);
        }
    });
}

function rcvCancel(id) {
    const comment = prompt('Причина отмены (опционально):');
    if (comment === null) return;
    rcvPost(API.cancel, {id, comment}).then(r => {
        if (r.success) location.reload();
        else alert('Ошибка: ' + r.message);
    });
}

function rcvUploadDocument(rcvId, file) {
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('receiving_id', rcvId);
    fd.append('type', document.getElementById('uploadDocType').value);
    fd.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch(API.uploadDoc, {method: 'POST', body: fd})
        .then(r => r.json())
        .then(r => {
            if (r.success) {
                document.getElementById('noDocsMsg')?.remove();
                const icon = r.original_name.match(/\.pdf$/i) ? 'bi-file-earmark-pdf'
                           : r.original_name.match(/\.(jpg|jpeg|png|gif)$/i) ? 'bi-file-earmark-image'
                           : 'bi-file-earmark';
                const html = `<div class="doc-item" id="doc-${r.document_id}">
                    <span class="doc-icon"><i class="bi ${icon}"></i></span>
                    <div style="flex:1;min-width:0">
                        <a href="${r.url}" target="_blank" style="font-size:.8rem;font-weight:600;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.original_name}</a>
                        <div style="font-size:.72rem;color:#9ca3af">${r.type_label} · ${r.size}</div>
                    </div>
                    <button class="btn btn-outline" style="padding:2px 7px;font-size:.72rem;color:#dc2626;border-color:#dc2626"
                            onclick="rcvDeleteDocument(${r.document_id})"><i class="bi bi-trash"></i></button>
                </div>`;
                document.getElementById('docsList').insertAdjacentHTML('beforeend', html);
            } else {
                alert('Ошибка загрузки: ' + r.message);
            }
        });
}

function rcvDeleteDocument(docId) {
    if (!confirm('Удалить документ?')) return;
    rcvPost(API.deleteDoc, {id: docId}).then(r => {
        if (r.success) document.getElementById('doc-' + docId)?.remove();
    });
}

// Drag-drop
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) rcvUploadDocument(RCV_ID, file);
    });
}
</script>
