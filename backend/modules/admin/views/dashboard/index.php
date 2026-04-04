<?php
/**
 * Dashboard — Главная панель администратора
 * Виджеты с реальными данными из БД, графики, топ-товары, складские предупреждения
 * 
 * @var yii\web\View $this
 * @var object $user
 * @var array $orderStats
 * @var array $productStats
 * @var array $userStats
 * @var array $topProducts
 * @var array $activeLogists
 * @var array $chartData
 * @var array|object $companySettings
 * @var bool $demoMode
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Панель управления';

$statusMap = [
    'new' => ['label' => 'Новый', 'class' => 'info'],
    'pending' => ['label' => 'Ожидает', 'class' => 'warning'],
    'processing' => ['label' => 'В обработке', 'class' => 'warning'],
    'paid' => ['label' => 'Оплачен', 'class' => 'success'],
    'shipped' => ['label' => 'Отправлен', 'class' => 'primary'],
    'delivered' => ['label' => 'Доставлен', 'class' => 'success'],
    'completed' => ['label' => 'Завершён', 'class' => 'success'],
    'cancelled' => ['label' => 'Отменён', 'class' => 'danger'],
    'refunded' => ['label' => 'Возврат', 'class' => 'danger'],
];

$totalAmount = $orderStats['totalAmount'] ?? 0;
$amountFormatted = $totalAmount >= 1000 ? number_format($totalAmount / 1000, 1, '.', '') . 'K' : number_format($totalAmount, 0, '.', ' ');
?>

<!-- Header -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
        <p class="dash-subtitle">
            <?= Html::encode($user->username ?? 'Admin') ?> · <?= date('d.m.Y, H:i') ?>
            <?php if ($demoMode): ?><span class="admin-badge admin-badge-warning" style="margin-left:0.5rem">Demo</span><?php endif ?>
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i> Новый заказ
        </a>
        <button class="admin-btn admin-btn-secondary" id="theme-toggle" title="Ctrl+D — тема">
            <i class="bi bi-moon-fill" id="theme-icon"></i>
        </button>
        <button class="admin-btn admin-btn-secondary" onclick="location.reload()" title="Ctrl+R">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-bag-check-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($orderStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Всего заказов</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-info"><?= $orderStats['today'] ?? 0 ?> сегодня</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-currency-exchange"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $amountFormatted ?> <small>BYN</small></div>
            <div class="admin-stat-label">Выручка</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-success"><?= $orderStats['thisMonth'] ?? 0 ?> за месяц</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-collection"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($productStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Товары</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-success"><?= $productStats['active'] ?? 0 ?> активных</span>
                <?php if (($productStats['outOfStock'] ?? 0) > 0): ?>
                    <span class="admin-badge admin-badge-danger"><?= $productStats['outOfStock'] ?> нет</span>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="admin-stat-content">
            <?php
            $overdue = \app\backend\modules\checkout\models\Order::find()
                ->where(['<', 'created_at', time() - 259200])
                ->andWhere(['status' => ['created', 'confirmed']])
                ->count();
            ?>
            <div class="admin-stat-value"><?= $overdue ?></div>
            <div class="admin-stat-label">Просроченные</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-danger">&gt; 3 дней</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-people-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($userStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Персонал</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-info"><?= $userStats['admins'] ?? 0 ?> адм</span>
                <span class="admin-badge admin-badge-warning"><?= $userStats['managers'] ?? 0 ?> мен</span>
            </div>
        </div>
    </div>
</div>

<!-- Order Pipeline - Enhanced KPI Block -->
<div class="admin-card" style="margin-bottom:1.5rem; border-left: 4px solid var(--admin-accent);">
    <div class="admin-card-header" style="border-bottom: none; padding-bottom: 0.5rem;">
        <h2 class="admin-card-title"><i class="bi bi-kanban-fill"></i> Пайплайн заказов</h2>
        <span class="admin-badge admin-badge-info">В реальном времени</span>
    </div>
    <div class="admin-card-body">
        <div class="dash-pipeline-enhanced">
            <a href="<?= Url::to(['/admin/order', 'status' => 'created']) ?>" class="dash-pipe-step dash-pipe-pending">
                <div class="dash-pipe-icon"><i class="bi bi-plus-circle-fill"></i></div>
                <div class="dash-pipe-content">
                    <div class="dash-pipe-value"><?= $orderStats['created'] ?? 0 ?></div>
                    <div class="dash-pipe-title">Созданы</div>
                    <div class="dash-pipe-bar">
                        <div class="dash-pipe-progress" style="width: <?= min(100, (($orderStats['created'] ?? 0) / max(1, $orderStats['total'] ?? 1)) * 100) ?>%"></div>
                    </div>
                </div>
            </a>
            
            <div class="dash-pipe-connector">
                <i class="bi bi-chevron-right"></i>
            </div>
            
            <a href="<?= Url::to(['/admin/order', 'status' => 'paid']) ?>" class="dash-pipe-step dash-pipe-processing">
                <div class="dash-pipe-icon"><i class="bi bi-credit-card-fill"></i></div>
                <div class="dash-pipe-content">
                    <div class="dash-pipe-value"><?= $orderStats['paid'] ?? 0 ?></div>
                    <div class="dash-pipe-title">Оплачены</div>
                    <div class="dash-pipe-bar">
                        <div class="dash-pipe-progress" style="width: <?= min(100, (($orderStats['paid'] ?? 0) / max(1, $orderStats['total'] ?? 1)) * 100) ?>%"></div>
                    </div>
                </div>
            </a>
            
            <div class="dash-pipe-connector">
                <i class="bi bi-chevron-right"></i>
            </div>
            
            <a href="<?= Url::to(['/admin/order', 'status' => 'delivered']) ?>" class="dash-pipe-step dash-pipe-completed">
                <div class="dash-pipe-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="dash-pipe-content">
                    <div class="dash-pipe-value"><?= $orderStats['delivered'] ?? 0 ?></div>
                    <div class="dash-pipe-title">Доставлены</div>
                    <div class="dash-pipe-bar">
                        <div class="dash-pipe-progress" style="width: <?= min(100, (($orderStats['delivered'] ?? 0) / max(1, $orderStats['total'] ?? 1)) * 100) ?>%"></div>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Total summary -->
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <span style="color: var(--admin-text-secondary); font-size: 14px;">Всего активных заказов:</span>
            <strong style="font-size: 24px; font-weight: 800; color: var(--admin-text);">
                <?= ($orderStats['created'] ?? 0) + ($orderStats['paid'] ?? 0) + ($orderStats['delivered'] ?? 0) ?>
            </strong>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-clock-history"></i> Последние заказы</h2>
        <a href="<?= Url::to(['/admin/order']) ?>" class="admin-btn admin-btn-sm admin-btn-secondary">Все заказы</a>
    </div>
    <?php if (!empty($recentOrders)): ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Клиент</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr onclick="location.href='<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>'" style="cursor:pointer">
                    <td style="font-weight:600">#<?= $order->id ?></td>
                    <td><?= Html::encode($order->client_name) ?></td>
                    <td><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></td>
                    <td>
                        <?php
                        $statusClass = [
                            'new' => 'info',
                            'paid' => 'success',
                            'processing' => 'warning',
                            'shipped' => 'primary',
                            'delivered' => 'success',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ][$order->status] ?? 'secondary';
                        ?>
                        <span class="admin-badge admin-badge-<?= $statusClass ?>">
                            <?= $order->getStatusLabel() ?>
                        </span>
                    </td>
                    <td><?= Yii::$app->formatter->asDatetime($order->created_at, 'short') ?></td>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="event.stopPropagation()">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="dash-empty-state">
        <div class="dash-empty-icon">
            <i class="bi bi-inbox"></i>
        </div>
        <div class="dash-empty-content">
            <h3>Заказов пока нет</h3>
            <p>Когда появятся заказы, они будут отображаться здесь</p>
            <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
                <i class="bi bi-plus-circle"></i> Создать первый заказ
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Sales Chart & Top Products -->
<div class="dash-grid-2">
    <!-- Sales Chart -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-bar-chart-line-fill"></i> Продажи за 7 дней</h2>
            <span class="admin-badge admin-badge-info" id="chart-status">Загрузка...</span>
        </div>
        <div class="dash-chart-container" style="max-width: 100%; overflow: hidden; position: relative; min-height: 350px;">
            <canvas id="salesChart" height="350" style="max-width: 100%;"></canvas>
            <div class="dash-chart-overlay" id="chart-overlay" style="display: none;">
                <div class="dash-chart-empty">
                    <i class="bi bi-bar-chart"></i>
                    <h3>Нет данных о продажах</h3>
                    <p>Начните продавать товары, чтобы увидеть статистику</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-trophy-fill"></i> Топ товары</h2>
            <span class="admin-badge admin-badge-info">30 дн</span>
        </div>
        <?php if (!empty($topProducts)): ?>
        <div class="dash-top-list">
            <?php foreach ($topProducts as $i => $p): ?>
            <div class="dash-top-item">
                <span class="dash-top-rank"><?= $i + 1 ?></span>
                <div class="dash-top-info">
                    <span class="dash-top-name"><?= Html::encode($p['product_name'] ?? '—') ?></span>
                    <span class="dash-top-meta"><?= (int)($p['order_count'] ?? 0) ?> заказов · <?= (int)($p['total_quantity'] ?? 0) ?> шт</span>
                </div>
                <span class="dash-top-price"><?= number_format($p['avg_price'] ?? 0, 0, '.', ' ') ?> BYN</span>
            </div>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div class="dash-empty-state">
            <div class="dash-empty-icon">
                <i class="bi bi-trophy"></i>
            </div>
            <div class="dash-empty-content">
                <h3>Топ товары отсутствуют</h3>
                <p>Проданные товары появятся в этом рейтинге</p>
                <a href="<?= Url::to(['/admin/catalog']) ?>" class="admin-btn admin-btn-secondary">
                    <i class="bi bi-collection"></i> Перейти к каталогу
                </a>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Quick Actions - только уникальные действия, не дублирующие навигацию -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-lightning-fill"></i> Быстрые действия</h2>
        <kbd class="dash-kbd">Ctrl+K</kbd>
    </div>
    <div class="dash-actions-grid">
        <a href="<?= Url::to(['/admin/import']) ?>" class="dash-action-card">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <span>Импорт товаров</span>
        </a>
        <a href="<?= Url::to(['/admin/export']) ?>" class="dash-action-card">
            <i class="bi bi-download"></i>
            <span>Экспорт заказов</span>
        </a>
        <a href="<?= Url::to(['/admin/coupon/create']) ?>" class="dash-action-card">
            <i class="bi bi-ticket-detailed-fill"></i>
            <span>Создать купон</span>
        </a>
        <a href="<?= Url::to(['/admin/settings']) ?>" class="dash-action-card">
            <i class="bi bi-gear-fill"></i>
            <span>Настройки</span>
        </a>
    </div>
</div>

<!-- Недавние действия -->
<?= \app\backend\modules\admin\widgets\RecentActionsWidget::widget(['limit' => 10]) ?>

<!-- System Info Bar - скрыта, доступна только админам через tooltip или отдельную страницу -->
<?php if ($user->isAdmin()): ?>
<div class="dash-sys-bar" style="opacity: 0.6; font-size: 0.7rem;">
    <span><b>Yii</b> <?= Yii::getVersion() ?></span>
    <span><b>PHP</b> <?= PHP_VERSION ?></span>
    <span><b>БД</b> sneakerhead</span>
    <span><b>ENV</b> <?= YII_ENV ?></span>
</div>
<?php endif; ?>

<!-- Chart.js CDN с fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Fallback если CDN не загрузился
if (typeof Chart === 'undefined') {
    document.write('<script src="/js/chart.min.js"><\/script>');
}
</script>

<!-- Передача данных для chart -->
<script>
window.chartData = <?= json_encode($chartData ?? []) ?>;

// Инициализация графика с обработкой пустых данных
document.addEventListener('DOMContentLoaded', function() {
    const chartStatus = document.getElementById('chart-status');
    const chartOverlay = document.getElementById('chart-overlay');
    const canvas = document.getElementById('salesChart');
    
    if (chartStatus) chartStatus.textContent = 'Инициализация...';
    
    // Проверяем, есть ли данные для графика
    if (!window.chartData || window.chartData.length === 0) {
        // Показываем empty state для графика
        if (chartStatus) chartStatus.textContent = 'Нет данных';
        if (chartOverlay) chartOverlay.style.display = 'flex';
        if (canvas) canvas.style.display = 'none';
        return;
    }
    
    // Есть данные - скрываем overlay
    if (chartOverlay) chartOverlay.style.display = 'none';
    if (canvas) canvas.style.display = 'block';
    
    // Инициализируем Chart.js
    if (typeof Chart !== 'undefined') {
        try {
            const ctx = canvas.getContext('2d');
            
            // Подготавливаем данные
            const labels = window.chartData.map(item => item.day || '');
            const data = window.chartData.map(item => item.amount || 0);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Продажи (BYN)',
                        data: data,
                        borderColor: '#008060',
                        backgroundColor: 'rgba(0, 128, 96, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#008060',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(32, 34, 35, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Продажи: ' + context.parsed.y.toLocaleString('ru-RU') + ' BYN';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6d7175',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e1e3e5',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6d7175',
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return value.toLocaleString('ru-RU') + ' BYN';
                                }
                            }
                        }
                    }
                }
            });
            
            if (chartStatus) chartStatus.textContent = 'Обновлено';
            
        } catch (error) {
            console.error('Error initializing chart:', error);
            if (chartStatus) chartStatus.textContent = 'Ошибка';
            if (chartOverlay) chartOverlay.style.display = 'flex';
            if (canvas) canvas.style.display = 'none';
        }
    } else {
        console.error('Chart.js not loaded');
        if (chartStatus) chartStatus.textContent = 'Ошибка загрузки';
        if (chartOverlay) chartOverlay.style.display = 'flex';
        if (canvas) canvas.style.display = 'none';
    }
});
</script>


<style>
.dash-subtitle { margin: 0.25rem 0 0; color: var(--admin-text-secondary); font-size: 0.875rem; }

/* Pipeline */
.dash-pipeline { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius-lg); }
.dash-pipe-item { text-align: center; }
.dash-pipe-num { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.dash-pipe-label { font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem; }
.dash-pipe-arrow { font-size: 1.25rem; color: var(--admin-text-secondary); }
.dash-pipe-info { color: var(--admin-info); }
.dash-pipe-warning { color: var(--admin-warning); }
.dash-pipe-success { color: var(--admin-success); }

/* 2-col grid */
.dash-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; max-width: 100%; }

/* Chart container fix */
.dash-grid-2 .admin-card canvas { 
    max-width: 100% !important; 
    height: auto !important;
}

/* Top Products */
.dash-top-list { display: flex; flex-direction: column; gap: 0.5rem; }
.dash-top-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border-light); }
.dash-top-item:last-child { border-bottom: none; }
.dash-top-rank { width: 28px; height: 28px; border-radius: 50%; background: var(--admin-border-light); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: var(--admin-text-secondary); flex-shrink: 0; }
.dash-top-item:nth-child(1) .dash-top-rank { background: #fef3c7; color: #92400e; }
.dash-top-item:nth-child(2) .dash-top-rank { background: #e2e8f0; color: #475569; }
.dash-top-item:nth-child(3) .dash-top-rank { background: #fed7aa; color: #9a3412; }
.dash-top-info { flex: 1; min-width: 0; }
.dash-top-name { display: block; font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-top-meta { font-size: 0.75rem; color: var(--admin-text-secondary); }
.dash-top-price { font-weight: 700; font-size: 0.875rem; white-space: nowrap; }
.dash-empty { text-align: center; color: var(--admin-text-secondary); padding: 2rem 0; }

/* Actions Grid */
.dash-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
.dash-action-card { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.25rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-lg); text-decoration: none; color: var(--admin-text); transition: all 0.15s; background: var(--admin-surface); }
.dash-action-card:hover { border-color: var(--admin-accent); transform: translateY(-2px); box-shadow: var(--admin-shadow-md); color: var(--admin-accent); }
.dash-action-card i { font-size: 1.5rem; }
.dash-action-card span { font-size: 0.8rem; font-weight: 600; }

/* Keyboard hint */
.dash-kbd { background: var(--admin-border-light); border: 1px solid var(--admin-border); border-radius: 4px; padding: 0.15rem 0.4rem; font-size: 0.7rem; font-family: monospace; color: var(--admin-text-secondary); }

/* Stat sub badges */
.dash-stat-sub { margin-top: 0.25rem; display: flex; gap: 0.25rem; flex-wrap: wrap; }
.dash-stat-sub .admin-badge { font-size: 0.65rem; padding: 0.15rem 0.5rem; }

/* System bar */
.dash-sys-bar { display: flex; flex-wrap: wrap; gap: 1.5rem; padding: 0.75rem 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.5rem; }
.dash-sys-bar b { color: var(--admin-text); }

/* Badge additions */
.admin-badge-info { background: #dbeafe; color: #1e40af; }
.admin-badge-primary { background: #dbeafe; color: #1e40af; }

/* Enhanced Pipeline KPI Block */
.dash-pipeline-enhanced {
    display: flex;
    align-items: stretch;
    justify-content: center;
    gap: 1rem;
    padding: 0.5rem 0;
}

.dash-pipe-step {
    flex: 1;
    max-width: 200px;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1.25rem;
    background: var(--admin-bg);
    border-radius: var(--admin-radius);
    text-decoration: none;
    color: var(--admin-text);
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.dash-pipe-step:hover {
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-md);
}

.dash-pipe-step.dash-pipe-pending:hover { border-color: var(--admin-info); }
.dash-pipe-step.dash-pipe-processing:hover { border-color: var(--admin-warning); }
.dash-pipe-step.dash-pipe-completed:hover { border-color: var(--admin-success); }

.dash-pipe-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--admin-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.dash-pipe-pending .dash-pipe-icon { background: var(--admin-info-bg); color: var(--admin-info); }
.dash-pipe-processing .dash-pipe-icon { background: var(--admin-warning-bg); color: var(--admin-warning); }
.dash-pipe-completed .dash-pipe-icon { background: var(--admin-success-bg); color: var(--admin-success); }

.dash-pipe-content { flex: 1; }
.dash-pipe-value { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 0.25rem; color: var(--admin-text); }
.dash-pipe-title { font-size: 14px; font-weight: 500; color: var(--admin-text-secondary); margin-bottom: 0.5rem; }

.dash-pipe-bar {
    height: 6px;
    background: var(--admin-border);
    border-radius: 3px;
    overflow: hidden;
}

.dash-pipe-progress {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.dash-pipe-pending .dash-pipe-progress { background: var(--admin-info); }
.dash-pipe-processing .dash-pipe-progress { background: var(--admin-warning); }
.dash-pipe-completed .dash-pipe-progress { background: var(--admin-success); }

.dash-pipe-connector {
    display: flex;
    align-items: center;
    font-size: 24px;
    color: var(--admin-border);
    padding-top: 0.5rem;
}

@media (max-width: 768px) {
    .dash-pipeline-enhanced { flex-direction: column; align-items: center; }
    .dash-pipe-step { max-width: 100%; width: 100%; }
    .dash-pipe-connector { transform: rotate(90deg); padding: 0; }
}

/* Empty States */
.dash-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    background: var(--admin-surface);
    border-radius: var(--admin-radius);
    border: 1px solid var(--admin-border);
}

.dash-empty-icon {
    font-size: 3rem;
    color: var(--admin-text-subdued);
    margin-bottom: 1rem;
    opacity: 0.5;
}

.dash-empty-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--admin-text);
}

.dash-empty-content p {
    color: var(--admin-text-secondary);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

/* Chart Container */
.dash-chart-container {
    position: relative;
}

.dash-chart-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--admin-surface);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--admin-radius);
}

.dash-chart-empty {
    text-align: center;
    padding: 2rem;
}

.dash-chart-empty i {
    font-size: 3rem;
    color: var(--admin-text-subdued);
    margin-bottom: 1rem;
    opacity: 0.5;
}

.dash-chart-empty h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--admin-text);
}

.dash-chart-empty p {
    color: var(--admin-text-secondary);
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 900px) {
    .dash-grid-2 { grid-template-columns: 1fr; }
    .dash-pipeline { flex-wrap: wrap; }
    .admin-stats { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .admin-header { flex-direction: column; gap: 1rem; text-align: center; }
    .admin-header-actions { justify-content: center; }
    .admin-stats { grid-template-columns: 1fr 1fr; }
    .dash-actions-grid { grid-template-columns: repeat(3, 1fr); }
    .dash-empty-state { padding: 2rem 1rem; }
    .admin-main { padding: var(--spacing-lg); }
}
@media (max-width: 480px) {
    .admin-stats { grid-template-columns: 1fr; }
    .dash-actions-grid { grid-template-columns: repeat(2, 1fr); }
    .admin-main { padding: var(--spacing-md); }
}

/* Improved spacing for better space utilization */
.admin-card {
    margin-bottom: 1.5rem;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    box-shadow: var(--admin-shadow-sm);
    transition: box-shadow 0.15s ease;
}

.admin-card:hover {
    box-shadow: var(--admin-shadow);
}

.admin-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.admin-card-body {
    padding: 1.5rem;
}

/* Better stats grid utilization */
.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* Compact pipeline for better space usage */
.dash-pipeline-enhanced {
    gap: 0.75rem;
    padding: 0;
}

.dash-pipe-step {
    padding: 1rem;
    min-width: 160px;
}

.dash-pipe-value {
    font-size: 28px;
}

/* Optimized chart container */
.dash-chart-container {
    min-height: 300px;
}

.dash-chart-container canvas {
    max-height: 300px;
}
</style>
