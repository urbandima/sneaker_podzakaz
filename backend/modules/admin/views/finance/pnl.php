<?php
/** @var yii\web\View $this */
/** @var array $months */
/** @var int $year */
/** @var int[] $years */
$this->title = 'P&L — ' . $year;

$monthNames = ['','Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'];

$totalRevenue = array_sum(array_column($months, 'revenue'));
$totalCogs    = array_sum(array_column($months, 'cogs'));
$totalGross   = array_sum(array_column($months, 'gross'));
$totalNet     = array_sum(array_column($months, 'net'));
$totalMargin  = $totalRevenue > 0 ? round($totalNet / $totalRevenue * 100, 1) : 0;
?>

<!-- KPI bar -->
<div class="atu-kpi-bar">
    <div class="atu-kpi-card atu-kpi-card--blue">
        <div class="atu-kpi-label">Выручка за год</div>
        <div class="atu-kpi-value"><?= number_format($totalRevenue, 0) ?> <span style="font-size:.875rem;font-weight:400">BYN</span></div>
    </div>
    <div class="atu-kpi-card">
        <div class="atu-kpi-label">Валовая прибыль</div>
        <div class="atu-kpi-value"><?= number_format($totalGross, 0) ?> <span style="font-size:.875rem;font-weight:400">BYN</span></div>
    </div>
    <div class="atu-kpi-card <?= $totalNet >= 0 ? 'atu-kpi-card--green' : 'atu-kpi-card--red' ?>">
        <div class="atu-kpi-label">Чистая прибыль</div>
        <div class="atu-kpi-value"><?= number_format($totalNet, 0) ?> <span style="font-size:.875rem;font-weight:400">BYN</span></div>
    </div>
    <div class="atu-kpi-card">
        <div class="atu-kpi-label">Маржа</div>
        <div class="atu-kpi-value" style="color:<?= $totalMargin >= 0 ? 'var(--admin-success,#008060)' : 'var(--admin-danger,#d72c0d)' ?>"><?= $totalMargin ?>%</div>
    </div>
</div>

<!-- Filter: year selector -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <span style="font-size:.8125rem;font-weight:600;color:var(--admin-text-secondary,#6d7175)">Год:</span>
        <select name="year" class="compact-filter-select" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <span style="margin-left:auto;font-size:.8125rem;color:var(--admin-text-secondary,#6d7175)">P&amp;L <?= $year ?></span>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-graph-up-arrow"></i> P&amp;L <?= $year ?></h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th>Месяц</th>
                    <th style="text-align:right">Выручка</th>
                    <th style="text-align:right">Закупка</th>
                    <th style="text-align:right">Вал. прибыль</th>
                    <th style="text-align:right">Дост. Китай</th>
                    <th style="text-align:right">Таможня</th>
                    <th style="text-align:right">Дост. местн.</th>
                    <th style="text-align:right">Аренда</th>
                    <th style="text-align:right">Зарплата</th>
                    <th style="text-align:right">Прочее</th>
                    <th style="text-align:right">Чист. прибыль</th>
                    <th style="text-align:right">Маржа</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($months as $m => $row): ?>
                <tr style="<?= $row['revenue'] == 0 ? 'opacity:.45' : '' ?>">
                    <td><strong><?= $monthNames[$m] ?></strong></td>
                    <td style="text-align:right"><?= number_format($row['revenue'], 0) ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['cogs'] > 0 ? '−'.number_format($row['cogs'], 0) : '—' ?></td>
                    <td style="text-align:right"><strong><?= number_format($row['gross'], 0) ?></strong></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['delChina'] > 0 ? '−'.number_format($row['delChina'], 0) : '—' ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['customs'] > 0 ? '−'.number_format($row['customs'], 0) : '—' ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['delLocal'] > 0 ? '−'.number_format($row['delLocal'], 0) : '—' ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['rent'] > 0 ? '−'.number_format($row['rent'], 0) : '—' ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['salary'] > 0 ? '−'.number_format($row['salary'], 0) : '—' ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $row['other'] > 0 ? '−'.number_format($row['other'], 0) : '—' ?></td>
                    <td style="text-align:right">
                        <strong style="color:<?= $row['net'] >= 0 ? 'var(--admin-success,#008060)' : 'var(--admin-danger,#d72c0d)' ?>">
                            <?= number_format($row['net'], 0) ?>
                        </strong>
                    </td>
                    <td style="text-align:right">
                        <span class="status-pill" style="background:<?= $row['revenue'] > 0 && $row['margin'] >= 15 ? '#d1f7e5;color:#008060' : ($row['revenue'] > 0 && $row['margin'] >= 0 ? '#fff4e5;color:#ffa500' : '#f3f4f6;color:#6d7175') ?>">
                            <?= $row['revenue'] > 0 ? $row['margin'].'%' : '—' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:var(--admin-surface-hover,#fafbfc)">
                    <td>ИТОГО</td>
                    <td style="text-align:right"><?= number_format($totalRevenue, 0) ?></td>
                    <td style="text-align:right;color:var(--admin-danger,#d72c0d)"><?= $totalCogs > 0 ? '−'.number_format($totalCogs, 0) : '—' ?></td>
                    <td style="text-align:right"><?= number_format($totalGross, 0) ?></td>
                    <td colspan="6"></td>
                    <td style="text-align:right;color:<?= $totalNet >= 0 ? 'var(--admin-success,#008060)' : 'var(--admin-danger,#d72c0d)' ?>">
                        <?= number_format($totalNet, 0) ?>
                    </td>
                    <td style="text-align:right">
                        <span class="status-pill" style="background:<?= $totalMargin >= 15 ? '#d1f7e5;color:#008060' : ($totalMargin >= 0 ? '#fff4e5;color:#ffa500' : '#fbeae5;color:#d72c0d') ?>">
                            <?= $totalMargin ?>%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
