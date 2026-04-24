<?php
/**
 * RFM аналитика - сегментация клиентов
 * @var yii\web\View $this
 * @var array $rfmSegments
 * @var array $ltvSegments
 * @var array $atRiskCustomers
 * @var array $allCustomers
 * @var int   $totalCustomers
 * @var float $totalRevenue
 */

use yii\helpers\Html;

$this->title = 'RFM Аналитика';
$this->params['headerActions'] = [];

if (empty($allCustomers)) {
    echo '<div class="admin-card"><div class="admin-card-body" style="text-align:center;padding:48px 24px">'
       . '<i class="bi bi-bar-chart" style="font-size:3rem;color:var(--admin-text-muted)"></i>'
       . '<p style="margin:16px 0 0;font-size:1.125rem;color:var(--admin-text-secondary)">Нет данных для RFM-анализа. Данные появятся после первых заказов.</p>'
       . '</div></div>';
    return;
}

// VIP KPI: champions + loyal
$vipCount = 0;
foreach ($rfmSegments as $s) {
    if (in_array($s['key'] ?? '', ['champions', 'loyal'])) {
        $vipCount += $s['count'];
    }
}
// At-risk KPI: at_risk + lost
$atRiskCount = 0;
foreach ($rfmSegments as $s) {
    if (in_array($s['key'] ?? '', ['at_risk', 'lost'])) {
        $atRiskCount += $s['count'];
    }
}
?>

<!-- KPI -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-people-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($totalCustomers) ?></div>
            <div class="admin-stat-label">Всего клиентов</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-currency-exchange"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($totalRevenue / 1000, 1) ?>K</div>
            <div class="admin-stat-label">Общая выручка</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-star-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $vipCount ?></div>
            <div class="admin-stat-label">VIP клиенты</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $atRiskCount ?></div>
            <div class="admin-stat-label">Требуют внимания</div>
        </div>
    </div>
</div>

<!-- Что такое RFM -->
<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Что такое RFM-анализ?</h2>
    </div>
    <div class="admin-card-body">
        <p style="color:var(--admin-text-secondary);margin-bottom:16px">
            RFM-анализ — это метод сегментации клиентов на основе трех показателей:
        </p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px">
                <h4 style="margin:0 0 8px;color:var(--admin-accent)">
                    <i class="bi bi-clock-history"></i> Recency (Давность)
                </h4>
                <p style="margin:0;font-size:14px;color:var(--admin-text-secondary)">
                    Как давно клиент совершил последнюю покупку
                </p>
            </div>
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px">
                <h4 style="margin:0 0 8px;color:var(--admin-accent)">
                    <i class="bi bi-arrow-repeat"></i> Frequency (Частота)
                </h4>
                <p style="margin:0;font-size:14px;color:var(--admin-text-secondary)">
                    Как часто клиент совершает покупки
                </p>
            </div>
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px">
                <h4 style="margin:0 0 8px;color:var(--admin-accent)">
                    <i class="bi bi-currency-dollar"></i> Monetary (Деньги)
                </h4>
                <p style="margin:0;font-size:14px;color:var(--admin-text-secondary)">
                    Сколько денег клиент потратил
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Матрица сегментов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Сегменты клиентов</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;gap:16px">
            <?php foreach ($rfmSegments as $segment): ?>
                <?php $percent = round(($segment['count'] / $totalCustomers) * 100, 1); ?>
                <div style="display:flex;align-items:center;gap:16px;padding:16px;background:var(--admin-bg);border-radius:12px;border-left:4px solid <?= $segment['color'] ?>">
                    <div style="flex:0 0 200px">
                        <h3 style="margin:0 0 4px;font-size:16px;font-weight:700"><?= $segment['segment'] ?></h3>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:12px;padding:2px 8px;background:<?= $segment['color'] ?>;color:white;border-radius:4px;font-weight:600">
                                <?= Html::encode($segment['key'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                    <div style="flex:1">
                        <p style="margin:0 0 8px;font-size:13px;color:var(--admin-text-secondary)"><?= $segment['desc'] ?></p>
                        <div style="display:flex;gap:16px;font-size:14px">
                            <span><strong><?= $segment['count'] ?></strong> клиентов (<?= $percent ?>%)</span>
                            <span><strong><?= number_format($segment['revenue'], 0, '.', ' ') ?></strong> BYN</span>
                            <span><strong><?= number_format($segment['avg_check'] ?? 0, 0, '.', ' ') ?></strong> BYN средний чек</span>
                        </div>
                    </div>
                    <div style="flex:0 0 auto">
                        <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="showSegmentDetails('<?= $segment['segment'] ?>')">
                            <i class="bi bi-eye"></i> Подробнее
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- LTV Сегментация -->
<?php
$totalAtRisk    = count($atRiskCustomers);
$totalAtRiskLTV = array_sum(array_column($atRiskCustomers, 'monetary'));
?>

<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-cash-stack"></i> LTV Сегментация</h2>
        <span class="admin-badge admin-badge-info"><?= number_format($totalRevenue / $totalCustomers, 0) ?> BYN средний LTV</span>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
            <?php foreach ($ltvSegments as $ltv): ?>
            <div style="padding:16px;background:var(--admin-bg);border-radius:12px;border-left:4px solid <?= $ltv['color'] ?>;text-align:center">
                <div style="font-size:2rem;font-weight:700;color:<?= $ltv['color'] ?>"><?= $ltv['count'] ?></div>
                <div style="font-size:0.875rem;font-weight:600;color:var(--admin-text)"><?= $ltv['name'] ?> LTV</div>
                <div style="font-size:0.75rem;color:var(--admin-text-secondary);margin-top:4px">
                    <?= $ltv['min'] ?><?= $ltv['max'] ? '-' . $ltv['max'] : '+' ?> BYN
                </div>
                <div style="font-size:0.75rem;color:var(--admin-text-secondary);margin-top:4px"><?= $ltv['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Покупатели в статусе риска -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-exclamation-triangle-fill"></i> Покупатели в статусе риска</h2>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="admin-badge admin-badge-danger"><?= $totalAtRisk ?> клиентов</span>
            <span class="admin-badge admin-badge-warning"><?= number_format($totalAtRiskLTV, 0) ?> BYN LTV</span>
            <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="exportAtRisk()">
                <i class="bi bi-download"></i> Экспорт
            </button>
        </div>
    </div>
    <div class="admin-card-body" style="padding:0">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--admin-bg)">
                    <th style="padding:12px 16px;text-align:left;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Покупатель</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">LTV</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Класс</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Последний заказ</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Дней без заказа</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Уровень риска</th>
                    <th style="padding:12px 16px;text-align:center;font-size:0.75rem;text-transform:uppercase;color:var(--admin-text-secondary);border-bottom:1px solid var(--admin-border)">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atRiskCustomers as $customer):
                    $ltv      = (float)($customer['monetary'] ?? 0);
                    $days     = (int)($customer['days'] ?? 0);
                    $ltvClass = $ltv >= 5000 ? 'VIP' : ($ltv >= 2000 ? 'Высокий' : ($ltv >= 500 ? 'Средний' : 'Низкий'));
                    $ltvColor = $ltv >= 5000 ? '#7c3aed' : ($ltv >= 2000 ? '#10b981' : ($ltv >= 500 ? '#3b82f6' : '#6b7280'));
                    $riskLabel = $days >= 90 ? 'Критический' : ($days >= 60 ? 'Высокий' : 'Средний');
                    $riskColor = $days >= 90 ? '#991b1b'    : ($days >= 60 ? '#dc2626'  : '#f59e0b');
                ?>
                <tr style="border-bottom:1px solid var(--admin-border-light)">
                    <td style="padding:12px 16px">
                        <div style="font-weight:600;color:var(--admin-text)"><?= Html::encode($customer['name'] ?? '') ?></div>
                        <div style="font-size:0.75rem;color:var(--admin-text-secondary)"><?= Html::encode($customer['email'] ?? '') ?></div>
                    </td>
                    <td style="padding:12px 16px;text-align:center;font-weight:700;color:var(--admin-text)">
                        <?= number_format($ltv, 0) ?> BYN
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:<?= $ltvColor ?>20;color:<?= $ltvColor ?>;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $ltvClass ?></span>
                    </td>
                    <td style="padding:12px 16px;text-align:center;color:var(--admin-text-secondary);font-size:0.875rem">
                        <?= Html::encode($customer['last_order_date'] ?? '—') ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:#fee2e2;color:#dc2626;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $days ?> дн</span>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:<?= $riskColor ?>20;color:<?= $riskColor ?>;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $riskLabel ?></span>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center">
                            <button class="admin-btn admin-btn-xs admin-btn-secondary" onclick="sendEmail('<?= Html::encode($customer['email'] ?? '') ?>')" title="Отправить email">
                                <i class="bi bi-envelope"></i>
                            </button>
                            <button class="admin-btn admin-btn-xs admin-btn-secondary" onclick="sendSms('<?= Html::encode($customer['email'] ?? '') ?>')" title="Отправить SMS">
                                <i class="bi bi-chat-dots"></i>
                            </button>
                            <button class="admin-btn admin-btn-xs admin-btn-primary" onclick="createOffer('<?= Html::encode($customer['email'] ?? '') ?>')" title="Создать персональное предложение">
                                <i class="bi bi-gift"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Рекомендации -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Рекомендации по работе с сегментами</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px;border-left:4px solid #10b981">
                <h4 style="margin:0 0 8px;color:#10b981">
                    <i class="bi bi-trophy-fill"></i> Чемпионы и Лояльные
                </h4>
                <ul style="margin:0;padding-left:20px;font-size:14px;color:var(--admin-text-secondary)">
                    <li>Эксклюзивные предложения</li>
                    <li>Программа лояльности VIP</li>
                    <li>Ранний доступ к новинкам</li>
                </ul>
            </div>
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px;border-left:4px solid #3b82f6">
                <h4 style="margin:0 0 8px;color:#3b82f6">
                    <i class="bi bi-star-fill"></i> Потенциальные и Новички
                </h4>
                <ul style="margin:0;padding-left:20px;font-size:14px;color:var(--admin-text-secondary)">
                    <li>Приветственные бонусы</li>
                    <li>Персональные рекомендации</li>
                    <li>Обучающие материалы</li>
                </ul>
            </div>
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px;border-left:4px solid #f59e0b">
                <h4 style="margin:0 0 8px;color:#f59e0b">
                    <i class="bi bi-bell-fill"></i> Нуждаются в внимании
                </h4>
                <ul style="margin:0;padding-left:20px;font-size:14px;color:var(--admin-text-secondary)">
                    <li>Персональные скидки</li>
                    <li>Напоминания о брошенных корзинах</li>
                    <li>Email-рассылки с новинками</li>
                </ul>
            </div>
            <div style="padding:16px;background:var(--admin-bg);border-radius:8px;border-left:4px solid #ef4444">
                <h4 style="margin:0 0 8px;color:#ef4444">
                    <i class="bi bi-arrow-clockwise"></i> Засыпающие и Потерянные
                </h4>
                <ul style="margin:0;padding-left:20px;font-size:14px;color:var(--admin-text-secondary)">
                    <li>Реактивационные кампании</li>
                    <li>Специальные возвратные предложения</li>
                    <li>Опросы для выяснения причин</li>
                </ul>
            </div>
        </div>
    </div>
</div>
