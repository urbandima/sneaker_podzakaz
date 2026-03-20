<?php

/** @var yii\web\View $this */
/** @var array $orderStats */
/** @var array $productStats */
/** @var array $userStats */
/** @var array $recentOrders */
/** @var array $chartData */
/** @var array $topProducts */
/** @var array $activeLogists */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Панель управления';
$user = Yii::$app->user->identity;
$formatter = Yii::$app->formatter;
?>

<div class="admin-page-container dashboard-admin">
    <div class="admin-page-shell">
        <div class="admin-page-header">
            <div class="admin-page-header-content">
                <div class="admin-page-eyebrow">Обзор</div>
                <h1 class="admin-page-title"><?= Html::encode($this->title) ?></h1>
                <p class="admin-page-subtitle">
                    <?php if ($user): ?>
                        Привет, <?= Html::encode($user->username) ?>. 
                    <?php endif; ?>
                    Отслеживайте заказы, выручку и нагрузку команды в едином дашборде.
                </p>
            </div>
            <div class="admin-page-actions">
                <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn--primary">
                    <i class="bi bi-plus-circle"></i>
                    Новый заказ
                </a>
                <button class="admin-btn admin-btn--ghost" onclick="location.reload()">
                    <i class="bi bi-arrow-repeat"></i>
                    Обновить данные
                </button>
                <a href="<?= Url::to(['/admin/dev-tools/index']) ?>" class="admin-btn admin-btn--outline">
                    <i class="bi bi-terminal"></i>
                    Dev Tools
                </a>
            </div>
        </div>

        <div class="dashboard-hero-grid admin-mb-6">
            <div class="dashboard-quick-card">
                <div class="quick-card-header">
                    <div>
                        <span class="quick-pill">Действия</span>
                        <h2>Быстрые сценарии</h2>
                        <p>Запускайте частые операции в один тап, не углубляясь в меню.</p>
                    </div>
                    <div class="quick-card-meta">
                        <span class="meta-dot"></span> <?= Html::encode(date('d M')) ?>
                    </div>
                </div>
                <div class="quick-actions-grid">
                    <a href="<?= Url::to(['/admin/order/create']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-orange">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <div>
                            <strong>Новый заказ</strong>
                            <p>Предзаказ, оплата, доставка</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/order/index', 'status' => 'created']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-blue">
                            <i class="bi bi-kanban"></i>
                        </div>
                        <div>
                            <strong>Черновики</strong>
                            <p>Заказы, ожидающие действий</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/product/create']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-green">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <strong>Добавить товар</strong>
                            <p>Каталог, карточки, остатки</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/analytics/index']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-purple">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <div>
                            <strong>Аналитика</strong>
                            <p>Выручка, каналы, динамика</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/user/create']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-teal">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div>
                            <strong>Новый пользователь</strong>
                            <p>Команда, роли, доступы</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/dev-tools/index']) ?>" class="quick-action-tile">
                        <div class="tile-icon glass-slate">
                            <i class="bi bi-terminal"></i>
                        </div>
                        <div>
                            <strong>Dev Tools</strong>
                            <p>Инструменты разработчика</p>
                        </div>
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </div>

            <div class="dashboard-calc-card">
                <div class="calc-card-header">
                    <div>
                        <span class="quick-pill">Калькулятор</span>
                        <h2>Мгновенный расчёт заказа</h2>
                    </div>
                    <?php if (!empty($tariffs)): ?>
                        <button class="admin-btn admin-btn--ghost admin-btn--sm" id="calcPanelReset">
                            <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (!empty($tariffs)): ?>
                <form id="dashboardCalcForm" class="calc-form">
                    <div class="calc-form-grid">
                        <label class="calc-field">
                            <span>Тариф</span>
                            <select name="tariff_id" id="calcTariff">
                                <?php foreach ($tariffs as $tariff): ?>
                                    <option value="<?= $tariff->id ?>"><?= Html::encode($tariff->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="calc-field">
                            <span>Цена товара, ¥</span>
                            <input type="number" step="0.01" min="0" value="300" name="price_cny" id="calcPrice">
                        </label>
                        <label class="calc-field">
                            <span>Вес, кг</span>
                            <input type="number" step="0.1" min="0.1" value="0.5" name="weight_kg" id="calcWeight">
                        </label>
                        <label class="calc-field">
                            <span>Заметка</span>
                            <input type="text" name="note" id="calcNote" placeholder="Например, 2 пары Jordan">
                        </label>
                    </div>
                    <div class="calc-actions">
                        <button type="submit" class="admin-btn admin-btn--primary" id="calcSubmit">
                            <i class="bi bi-calculator"></i> Рассчитать
                        </button>
                        <div class="calc-status" id="calcStatus"></div>
                    </div>
                </form>
                <div class="calc-result" id="calcResult" hidden>
                    <div class="calc-result-main">
                        <p>Итого</p>
                        <strong id="calcTotalLocal">0 BYN</strong>
                        <span id="calcTotalCny" class="calc-total-cny">0 ¥</span>
                    </div>
                    <div class="calc-breakdown" id="calcBreakdown"></div>
                </div>
                <?php else: ?>
                    <div class="calc-empty-state">
                        <p>Нет активных тарифов. Добавьте тариф, чтобы воспользоваться калькулятором.</p>
                        <a href="<?= Url::to(['/admin/tariff/index']) ?>" class="admin-btn admin-btn--outline">
                            Перейти к тарифам
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-grid admin-grid--4 admin-mb-6">
            <div class="admin-metric-card">
                <span class="admin-metric-label">Всего заказов</span>
                <span class="admin-metric-value"><?= Html::encode(number_format($orderStats['total'])) ?></span>
                <span class="admin-metric-change admin-metric-change--up">
                    <i class="bi bi-arrow-up"></i>
                    +<?= Html::encode($orderStats['today'] ?? 0) ?> сегодня
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Выручка BYN</span>
                <span class="admin-metric-value"><?= Html::encode($formatter->asDecimal($orderStats['totalAmount'], 0)) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-graph-up"></i>
                    <?= Html::encode($formatter->asDecimal($orderStats['thisMonth'], 0)) ?> за месяц
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Каталог</span>
                <span class="admin-metric-value"><?= Html::encode(number_format($productStats['total'])) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-box-seam"></i>
                    <?= Html::encode($productStats['inStock']) ?> в наличии
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Команда</span>
                <span class="admin-metric-value"><?= Html::encode(number_format($userStats['total'])) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-people"></i>
                    <?= Html::encode($userStats['active']) ?> активных
                </span>
            </div>
        </div>

        <div class="admin-grid admin-grid--sidebar admin-mb-6">
            <div class="admin-card admin-chart-card">
                <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                    <div>
                        <h2 class="admin-card-title">
                            <i class="bi bi-activity"></i>
                            Динамика заказов
                        </h2>
                        <p class="admin-card-subtitle">За последние 7 дней · общая выручка и количество.</p>
                    </div>
                    <a href="<?= Url::to(['/admin/statistics']) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                        Подробнее
                    </a>
                </div>
                <div class="admin-card-body">
                    <canvas id="ordersChart" class="dashboard-chart"></canvas>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                    <div>
                        <h2 class="admin-card-title">
                            <i class="bi bi-clock-history"></i>
                            Последние заказы
                        </h2>
                        <p class="admin-card-subtitle">Свежие заявки за последние 24 часа.</p>
                    </div>
                    <a href="<?= Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                        Все заказы
                    </a>
                </div>
                <div class="admin-card-body recent-orders-list">
                    <?php foreach ($recentOrders as $order): ?>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="recent-order">
                            <div class="recent-order__avatar">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="recent-order__meta">
                                <div class="recent-order__title">№ <?= Html::encode($order->order_number) ?></div>
                                <div class="recent-order__subtitle">
                                    <?= Html::encode($order->client_name ?: 'Не указан клиент') ?>
                                </div>
                            </div>
                            <div class="recent-order__amount">
                                <?= Html::encode($formatter->asDecimal($order->total_amount, 2)) ?> BYN
                                <small><?= Html::encode($formatter->asRelativeTime($order->created_at)) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <div class="admin-empty-state">
                            <div class="admin-empty-state-icon"><i class="bi bi-stars"></i></div>
                            <div class="admin-empty-state-title">Пока нет новых заказов</div>
                            <p class="admin-empty-state-text">Создайте заказ вручную или запустите рекламную кампанию.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="admin-grid admin-grid--2 admin-mb-6">
            <div class="admin-card">
                <div class="admin-card-header admin-flex admin-flex--between">
                    <h3 class="admin-card-title">
                        <i class="bi bi-fire"></i>
                        Топ товары
                    </h3>
                    <a href="<?= Url::to(['/admin/product/index']) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                        Каталог
                    </a>
                </div>
                <div class="admin-card-body top-products-list">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="top-product">
                            <span class="top-product__rank"><?= $index + 1 ?></span>
                            <div>
                                <div class="top-product__title"><?= Html::encode($product['product_name']) ?></div>
                                <div class="top-product__meta">
                                    <?= Html::encode($product['order_count']) ?> заказов ·
                                    <?= Html::encode($product['total_quantity']) ?> шт.
                                </div>
                            </div>
                            <span class="top-product__amount">
                                <?= Html::encode($formatter->asCurrency($product['avg_price'], 'BYN')) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($topProducts)): ?>
                        <div class="admin-text-muted">Недостаточно данных для рейтинга.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header admin-flex admin-flex--between">
                    <h3 class="admin-card-title">
                        <i class="bi bi-person-workspace"></i>
                        Активные логисты
                    </h3>
                    <a href="<?= Url::to(['/admin/user/index']) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                        Команда
                    </a>
                </div>
                <div class="admin-card-body logist-list">
                    <?php foreach ($activeLogists as $logist): ?>
                        <div class="logist-entry">
                            <div class="logist-entry__avatar">
                                <?= Html::encode(mb_strtoupper(mb_substr($logist->username, 0, 2))) ?>
                            </div>
                            <div class="logist-entry__meta">
                                <div class="logist-entry__name"><?= Html::encode($logist->username) ?></div>
                                <div class="logist-entry__role">Логист • <?= Html::encode($logist->email) ?></div>
                            </div>
                            <a href="<?= Url::to(['/admin/user/edit', 'id' => $logist->id]) ?>" class="admin-btn admin-btn--ghost admin-btn--icon">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($activeLogists)): ?>
                        <div class="admin-text-muted">Нет активных логистов.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartData = <?= json_encode($chartData) ?>;
    const ctx = document.getElementById('ordersChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => item.day),
                datasets: [
                    {
                        label: 'Заказы',
                        data: chartData.map(item => item.orders),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.14)',
                        tension: 0.35,
                        borderWidth: 3,
                        fill: true
                    },
                    {
                        label: 'Выручка (BYN)',
                        data: chartData.map(item => item.amount),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        tension: 0.35,
                        borderWidth: 3,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        labels: { usePointStyle: true }
                    },
                    tooltip: {
                        padding: 12,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(15, 23, 42, 0.05)' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: (value) => value.toLocaleString('ru-RU') + ' BYN'
                        }
                    },
                    x: {
                        grid: { color: 'rgba(15, 23, 42, 0.05)' }
                    }
                }
            }
        });
    }
});
</script>
