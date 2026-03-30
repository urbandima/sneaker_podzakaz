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

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="bi bi-diagram-3-fill"></i> <?= Html::encode($this->title) ?>
    </h1>
</div>

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

<script>
function showSegmentDetails(segment) {
    alert('Детали сегмента "' + segment + '"\n\nЗдесь будет список клиентов этого сегмента с возможностью экспорта и массовых действий.');
}
</script>
