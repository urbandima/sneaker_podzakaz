<?php
/** @var yii\web\View $this */
/** @var string $tab, $from, $to */
/** @var array $data */
$this->title = 'Маржинальность';

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};
?>

<!-- Filter bar with tab pills -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="atu-tab-pills">
            <a href="?tab=product&from=<?= $from ?>&to=<?= $to ?>"
               class="atu-tab-pill <?= $tab === 'product' ? 'atu-tab-pill--active' : '' ?>">
                <i class="bi bi-box"></i> По товарам
            </a>
            <a href="?tab=manager&from=<?= $from ?>&to=<?= $to ?>"
               class="atu-tab-pill <?= $tab === 'manager' ? 'atu-tab-pill--active' : '' ?>">
                <i class="bi bi-person"></i> По менеджерам
            </a>
        </div>
        <input type="date" name="from" class="compact-filter-input" value="<?= htmlspecialchars($from) ?>" title="Дата от">
        <input type="date" name="to"   class="compact-filter-input" value="<?= htmlspecialchars($to) ?>"   title="Дата до">
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Применить</button>
        <a href="?tab=<?= htmlspecialchars($tab) ?>" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0">
            <i class="bi bi-<?= $tab === 'product' ? 'box' : 'person' ?>"></i>
            <?= $tab === 'product' ? 'Маржа по товарам' : 'Маржа по менеджерам' ?>
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($data) ?></span>
        </h2>
    </div>

    <?php if ($tab === 'product'): ?>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th data-sort="order_count" onclick="AdminTable.sortBy('order_count')" style="text-align:right">Заказов <?= $sortIcon('order_count') ?></th>
                    <th data-sort="qty"         onclick="AdminTable.sortBy('qty')"         style="text-align:right">Кол-во <?= $sortIcon('qty') ?></th>
                    <th data-sort="revenue"     onclick="AdminTable.sortBy('revenue')"     style="text-align:right">Выручка BYN <?= $sortIcon('revenue') ?></th>
                    <th style="text-align:right">Себест-ть</th>
                    <th data-sort="margin"      onclick="AdminTable.sortBy('margin')"      style="text-align:right">Маржа BYN <?= $sortIcon('margin') ?></th>
                    <th data-sort="margin_pct"  onclick="AdminTable.sortBy('margin_pct')"  style="text-align:right">Маржа % <?= $sortIcon('margin_pct') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?>
                    <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Нет данных за период</td></tr>
                <?php endif; ?>
                <?php foreach ($data as $row):
                    $pct  = (float)$row['margin_pct'];
                    $cogs = (float)$row['cogs'];
                    if ($cogs == 0)        { $pctBg = '#f3f4f6'; $pctColor = '#6d7175'; }
                    elseif ($pct >= 20)    { $pctBg = '#d1f7e5'; $pctColor = '#008060'; }
                    elseif ($pct >= 0)     { $pctBg = '#fff4e5'; $pctColor = '#ffa500'; }
                    else                   { $pctBg = '#fbeae5'; $pctColor = '#d72c0d'; }
                ?>
                <tr>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($row['product_name'] ?? '—') ?>
                    </td>
                    <td style="text-align:right"><?= (int)$row['order_count'] ?></td>
                    <td style="text-align:right"><?= (int)$row['qty'] ?></td>
                    <td style="text-align:right;font-weight:700"><?= number_format($row['revenue'], 2) ?></td>
                    <td style="text-align:right;color:var(--admin-text-secondary,#6d7175)"><?= $cogs > 0 ? number_format($cogs, 2) : '—' ?></td>
                    <td style="text-align:right;color:<?= $row['margin'] >= 0 ? 'var(--admin-success,#008060)' : 'var(--admin-danger,#d72c0d)' ?>;font-weight:700">
                        <?= $cogs > 0 ? number_format($row['margin'], 2) : '—' ?>
                    </td>
                    <td style="text-align:right">
                        <?php if ($cogs == 0): ?>
                            <span class="status-pill" style="background:#f3f4f6;color:#6d7175" title="Себестоимость не указана">Нет себестоимости</span>
                        <?php else: ?>
                            <span class="status-pill" style="background:<?= $pctBg ?>;color:<?= $pctColor ?>"><?= $pct ?>%</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th>Менеджер</th>
                    <th data-sort="orders"        onclick="AdminTable.sortBy('orders')"        style="text-align:right">Заказов <?= $sortIcon('orders') ?></th>
                    <th data-sort="revenue"       onclick="AdminTable.sortBy('revenue')"       style="text-align:right">Выручка BYN <?= $sortIcon('revenue') ?></th>
                    <th style="text-align:right">Доставка</th>
                    <th data-sort="margin"        onclick="AdminTable.sortBy('margin')"        style="text-align:right">Маржа BYN <?= $sortIcon('margin') ?></th>
                    <th data-sort="margin_pct"    onclick="AdminTable.sortBy('margin_pct')"    style="text-align:right">Маржа % <?= $sortIcon('margin_pct') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?>
                    <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Нет данных за период</td></tr>
                <?php endif; ?>
                <?php foreach ($data as $row):
                    $pct = (float)$row['margin_pct'];
                    if ($pct >= 20)        { $pctBg = '#d1f7e5'; $pctColor = '#008060'; }
                    elseif ($pct >= 0)     { $pctBg = '#fff4e5'; $pctColor = '#ffa500'; }
                    else                   { $pctBg = '#fbeae5'; $pctColor = '#d72c0d'; }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['manager']) ?></strong></td>
                    <td style="text-align:right"><?= (int)$row['orders'] ?></td>
                    <td style="text-align:right"><?= number_format($row['revenue'], 2) ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['delivery_cost'] > 0 ? '−'.number_format($row['delivery_cost'], 2) : '—' ?></td>
                    <td style="text-align:right;font-weight:700;color:<?= $row['margin'] >= 0 ? 'var(--admin-success,#008060)' : 'var(--admin-danger,#d72c0d)' ?>">
                        <?= number_format($row['margin'], 2) ?>
                    </td>
                    <td style="text-align:right">
                        <span class="status-pill" style="background:<?= $pctBg ?>;color:<?= $pctColor ?>"><?= $pct ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <p style="color:var(--admin-text-secondary,#6d7175);font-size:.75rem;margin-top:.75rem">
        * Себестоимость по товарам будет точной после привязки расходов-закупок к позициям заказа.
    </p>
</div>
