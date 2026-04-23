<?php
/**
 * RFM аналитика - сегментация клиентов
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use app\backend\modules\checkout\models\Order;

$this->title = 'RFM Аналитика';

// Расчет RFM метрик для демонстрации
$rfmSegments = [
    ['segment' => 'Чемпионы', 'rfm' => '555', 'count' => 12, 'revenue' => 45600, 'color' => '#10b981', 'desc' => 'Покупают часто, недавно и много'],
    ['segment' => 'Лояльные', 'rfm' => '544', 'count' => 28, 'revenue' => 78400, 'color' => '#3b82f6', 'desc' => 'Регулярные покупатели с высоким чеком'],
    ['segment' => 'Потенциальные', 'rfm' => '455', 'count' => 45, 'revenue' => 56700, 'color' => '#8b5cf6', 'desc' => 'Недавние клиенты с потенциалом роста'],
    ['segment' => 'Новички', 'rfm' => '511', 'count' => 67, 'revenue' => 34200, 'color' => '#06b6d4', 'desc' => 'Новые клиенты, требуют внимания'],
    ['segment' => 'Обещающие', 'rfm' => '415', 'count' => 34, 'revenue' => 28900, 'color' => '#f59e0b', 'desc' => 'Средняя активность, можно развивать'],
    ['segment' => 'Нуждаются в внимании', 'rfm' => '344', 'count' => 56, 'revenue' => 23400, 'color' => '#f97316', 'desc' => 'Снижение активности'],
    ['segment' => 'Засыпающие', 'rfm' => '233', 'count' => 89, 'revenue' => 12300, 'color' => '#ef4444', 'desc' => 'Давно не покупали'],
    ['segment' => 'Потерянные', 'rfm' => '111', 'count' => 123, 'revenue' => 5600, 'color' => '#991b1b', 'desc' => 'Неактивны более 6 месяцев'],
];

$totalCustomers = array_sum(array_column($rfmSegments, 'count'));
$totalRevenue = array_sum(array_column($rfmSegments, 'revenue'));
?>

<?php
$this->params['headerActions'] = [];
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
            <div class="admin-stat-value"><?= $rfmSegments[0]['count'] + $rfmSegments[1]['count'] ?></div>
            <div class="admin-stat-label">VIP клиенты</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $rfmSegments[6]['count'] + $rfmSegments[7]['count'] ?></div>
            <div class="admin-stat-label">Требуют внимания</div>
        </div>
    </div>
</div>

<!-- Что такое RFM -->
<div class="admin-card mb-5">
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
                                RFM: <?= $segment['rfm'] ?>
                            </span>
                        </div>
                    </div>
                    <div style="flex:1">
                        <p style="margin:0 0 8px;font-size:13px;color:var(--admin-text-secondary)"><?= $segment['desc'] ?></p>
                        <div style="display:flex;gap:16px;font-size:14px">
                            <span><strong><?= $segment['count'] ?></strong> клиентов (<?= $percent ?>%)</span>
                            <span><strong><?= number_format($segment['revenue'], 0, '.', ' ') ?></strong> BYN</span>
                            <span><strong><?= number_format($segment['revenue'] / $segment['count'], 0, '.', ' ') ?></strong> BYN средний чек</span>
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
// LTV сегменты
$ltvSegments = [
    ['name' => 'VIP', 'min' => 5000, 'max' => null, 'count' => 24, 'color' => '#7c3aed', 'desc' => 'Высокая ценность, требуют особого внимания'],
    ['name' => 'Высокий', 'min' => 2000, 'max' => 4999, 'count' => 89, 'color' => '#10b981', 'desc' => 'Активные покупатели с хорошим LTV'],
    ['name' => 'Средний', 'min' => 500, 'max' => 1999, 'count' => 156, 'color' => '#3b82f6', 'desc' => 'Стабильные клиенты с потенциалом роста'],
    ['name' => 'Низкий', 'min' => 0, 'max' => 499, 'count' => 234, 'color' => '#6b7280', 'desc' => 'Требуют развития и удержания'],
];

// Покупатели в статусе риска с LTV
$atRiskCustomers = [
    ['name' => 'Иванов Иван', 'email' => 'ivan@example.com', 'ltv' => 4500, 'last_order' => '2026-01-15', 'risk' => 'Высокий', 'risk_days' => 75, 'color' => '#dc2626'],
    ['name' => 'Петрова Мария', 'email' => 'maria@example.com', 'ltv' => 3200, 'last_order' => '2026-02-01', 'risk' => 'Высокий', 'risk_days' => 58, 'color' => '#dc2626'],
    ['name' => 'Сидоров Алексей', 'email' => 'alex@example.com', 'ltv' => 1800, 'last_order' => '2026-02-20', 'risk' => 'Средний', 'risk_days' => 39, 'color' => '#f59e0b'],
    ['name' => 'Козлова Елена', 'email' => 'elena@example.com', 'ltv' => 8900, 'last_order' => '2026-01-05', 'risk' => 'Критический', 'risk_days' => 85, 'color' => '#991b1b'],
    ['name' => 'Морозов Дмитрий', 'email' => 'dmitry@example.com', 'ltv' => 1200, 'last_order' => '2026-02-25', 'risk' => 'Средний', 'risk_days' => 34, 'color' => '#f59e0b'],
];

$totalAtRisk = count($atRiskCustomers);
$totalAtRiskLTV = array_sum(array_column($atRiskCustomers, 'ltv'));
?>

<div class="admin-card mt-5">
    <div class="admin-card-header flex-between">
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
<div class="admin-card mt-5">
    <div class="admin-card-header flex-between">
        <h2 class="admin-card-title"><i class="bi bi-exclamation-triangle-fill"></i> Покупатели в статусе риска</h2>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="admin-badge admin-badge-danger"><?= $totalAtRisk ?> клиентов</span>
            <span class="admin-badge admin-badge-warning"><?= number_format($totalAtRiskLTV, 0) ?> BYN LTV</span>
            <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="exportAtRisk()">
                <i class="bi bi-download"></i> Экспорт
            </button>
        </div>
    </div>
    <div class="admin-card-body p-0">
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
                    $ltvClass = $customer['ltv'] >= 5000 ? 'VIP' : ($customer['ltv'] >= 2000 ? 'Высокий' : ($customer['ltv'] >= 500 ? 'Средний' : 'Низкий'));
                    $ltvColor = $customer['ltv'] >= 5000 ? '#7c3aed' : ($customer['ltv'] >= 2000 ? '#10b981' : ($customer['ltv'] >= 500 ? '#3b82f6' : '#6b7280'));
                ?>
                <tr style="border-bottom:1px solid var(--admin-border-light)">
                    <td style="padding:12px 16px">
                        <div style="font-weight:600;color:var(--admin-text)"><?= $customer['name'] ?></div>
                        <div style="font-size:0.75rem;color:var(--admin-text-secondary)"><?= $customer['email'] ?></div>
                    </td>
                    <td style="padding:12px 16px;text-align:center;font-weight:700;color:var(--admin-text)">
                        <?= number_format($customer['ltv'], 0) ?> BYN
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:<?= $ltvColor ?>20;color:<?= $ltvColor ?>;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $ltvClass ?></span>
                    </td>
                    <td style="padding:12px 16px;text-align:center;color:var(--admin-text-secondary);font-size:0.875rem">
                        <?= $customer['last_order'] ?>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:#fee2e2;color:#dc2626;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $customer['risk_days'] ?> дн</span>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <span style="padding:2px 8px;background:<?= $customer['color'] ?>20;color:<?= $customer['color'] ?>;border-radius:4px;font-size:0.75rem;font-weight:600"><?= $customer['risk'] ?></span>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center">
                            <button class="admin-btn admin-btn-xs admin-btn-secondary" onclick="sendEmail('<?= $customer['email'] ?>')" title="Отправить email">
                                <i class="bi bi-envelope"></i>
                            </button>
                            <button class="admin-btn admin-btn-xs admin-btn-secondary" onclick="sendSms('<?= $customer['email'] ?>')" title="Отправить SMS">
                                <i class="bi bi-chat-dots"></i>
                            </button>
                            <button class="admin-btn admin-btn-xs admin-btn-primary" onclick="createOffer('<?= $customer['email'] ?>')" title="Создать персональное предложение">
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
<div class="admin-card mt-5">
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
