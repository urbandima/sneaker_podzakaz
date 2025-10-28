<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $filterStatus */
/** @var string $filterLogist */
/** @var string $filterSearch */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use app\models\User;
use app\models\Order;

$this->title = 'Заказы';

// Получаем все заказы с учетом фильтров и прав доступа
$user = Yii::$app->user->identity;
$query = Order::find()->with(['creator', 'logist', 'orderItems']);

if ($user->isLogist()) {
    $query->andWhere(['assigned_logist' => $user->id]);
}

// Применяем фильтры
if ($filterStatus) {
    $query->andWhere(['status' => $filterStatus]);
}
if ($filterLogist) {
    $query->andWhere(['assigned_logist' => $filterLogist]);
}
if ($filterSearch) {
    $query->andWhere([
        'or',
        ['like', 'order_number', $filterSearch],
        ['like', 'client_name', $filterSearch],
        ['like', 'client_phone', $filterSearch],
        ['like', 'client_email', $filterSearch],
    ]);
}

$orders = $query->orderBy(['created_at' => SORT_DESC])->all();

// Группируем заказы по месяцам
$ordersByMonth = [];
foreach ($orders as $order) {
    $monthKey = date('Y-m', $order->created_at);
    $monthLabel = Yii::$app->formatter->asDate($order->created_at, 'LLLL yyyy');
    
    if (!isset($ordersByMonth[$monthKey])) {
        $ordersByMonth[$monthKey] = [
            'label' => $monthLabel,
            'orders' => [],
            'total' => 0,
            'count' => 0,
        ];
    }
    
    $ordersByMonth[$monthKey]['orders'][] = $order;
    $ordersByMonth[$monthKey]['total'] += $order->total_amount;
    $ordersByMonth[$monthKey]['count']++;
}
?>

<div class="admin-orders">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if (!$user->isLogist()): ?>
            <?= Html::a('<i class="bi bi-plus-circle me-2"></i>Создать заказ', ['create-order'], ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </div>

    <?php
    // Панель статусов со счетчиками (актуальные данные из БД)
    $statuses = Yii::$app->settings->getStatuses();
    $statusCounts = [];
    
    // Подсчитываем количество заказов по каждому статусу
    foreach ($statuses as $key => $label) {
        $q = Order::find()->where(['status' => $key]);
        if ($user->isLogist()) {
            $q->andWhere(['assigned_logist' => $user->id]);
        }
        $statusCounts[$key] = (int)$q->count();
    }
    
    // Общее количество
    $totalQ = Order::find();
    if ($user->isLogist()) {
        $totalQ->andWhere(['assigned_logist' => $user->id]);
    }
    $totalCount = (int)$totalQ->count();
    
    // Активный фильтр
    $activeStatus = $filterStatus ?? '';
    ?>

    <!-- Компактная панель статусов -->
    <div class="status-filter-bar sticky-top bg-white border-bottom mb-3 pb-2">
        <div class="status-pills d-flex gap-2 flex-wrap">
            <a href="<?= Url::to(['orders']) ?>" 
               class="status-pill <?= $activeStatus === '' ? 'active' : '' ?>">
                <span class="label">Все</span>
                <span class="count"><?= $totalCount ?></span>
            </a>
            <?php foreach ($statuses as $key => $label): ?>
                <a href="<?= Url::to(['orders', 'status' => $key]) ?>" 
                   class="status-pill <?= $activeStatus === $key ? 'active' : '' ?>"
                   data-status="<?= Html::encode($key) ?>">
                    <span class="label"><?= Html::encode($label) ?></span>
                    <span class="count"><?= $statusCounts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Расширенные фильтры -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-light py-2">
            <a class="text-decoration-none fw-semibold" data-bs-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="false">
                <i class="bi bi-funnel me-2"></i>Расширенные фильтры
                <i class="bi bi-chevron-down float-end"></i>
            </a>
        </div>
        <div class="collapse" id="advancedFilters">
            <div class="card-body">
                <form method="get" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Поиск</label>
                        <input type="text" class="form-control form-control-sm" name="search" value="<?= Html::encode($filterSearch) ?>" placeholder="Номер, ФИО, телефон...">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Статус</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">Все статусы</option>
                            <?php foreach (Yii::$app->settings->getStatuses() as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $filterStatus == $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($user->isAdmin()): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Логист</label>
                        <select class="form-select form-select-sm" name="logist">
                            <option value="">Все логисты</option>
                            <?php
                            $logists = User::find()->where(['role' => 'logist'])->all();
                            foreach ($logists as $logist): ?>
                                <option value="<?= $logist->id ?>" <?= $filterLogist == $logist->id ? 'selected' : '' ?>>
                                    <?= Html::encode($logist->username) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i>Применить
                        </button>
                        <a href="<?= Url::to(['orders']) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Сбросить
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Общая статистика -->
    <?php if (!empty($ordersByMonth)): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-cart-check me-2"></i>Всего заказов</h5>
                    <h2 class="mb-0"><?= count($orders) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-currency-exchange me-2"></i>Общая сумма</h5>
                    <h2 class="mb-0"><?= Yii::$app->formatter->asDecimal(array_sum(array_column($orders, 'total_amount')), 2) ?> BYN</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-calendar3 me-2"></i>Месяцев</h5>
                    <h2 class="mb-0"><?= count($ordersByMonth) ?></h2>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Заказы по месяцам -->
    <?php if (empty($ordersByMonth)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Заказы не найдены. Попробуйте изменить фильтры.
        </div>
    <?php else: ?>
        <div class="accordion" id="ordersAccordion">
            <?php $index = 0; foreach ($ordersByMonth as $monthKey => $monthData): ?>
            <?php
                list($year, $month) = explode('-', $monthKey);
                $isFirst = $index === 0;
            ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header" id="heading<?= $index ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-link text-decoration-none fw-bold fs-5 text-start flex-grow-1 <?= $isFirst ? '' : 'collapsed' ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?= $index ?>" 
                                aria-expanded="<?= $isFirst ? 'true' : 'false' ?>" 
                                aria-controls="collapse<?= $index ?>">
                            <i class="bi bi-calendar-month me-2"></i>
                            <?= ucfirst($monthData['label']) ?>
                            <span class="badge bg-primary ms-2"><?= $monthData['count'] ?> заказов</span>
                            <span class="badge bg-success ms-1"><?= Yii::$app->formatter->asDecimal($monthData['total'], 2) ?> BYN</span>
                        </button>
                        <a href="<?= Url::to(['export-orders', 'year' => $year, 'month' => $month]) ?>" 
                           class="btn btn-success btn-sm"
                           title="Экспортировать в Excel">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Экспорт Excel
                        </a>
                    </div>
                </div>

                <div id="collapse<?= $index ?>" 
                     class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>" 
                     aria-labelledby="heading<?= $index ?>" 
                     data-bs-parent="#ordersAccordion">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm table-striped mb-0 compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 100px;">№ Заказа</th>
                                        <th style="width: 180px;">Клиент</th>
                                        <th style="width: 110px;">Сумма</th>
                                        <th style="width: 130px;">Статус</th>
                                        <th style="width: 200px;">Комментарий</th>
                                        <th style="width: 60px;" class="text-center">Чек</th>
                                        <th style="width: 100px;">Ответств.</th>
                                        <th style="width: 100px;">Дата</th>
                                        <th style="width: 90px;" class="text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthData['orders'] as $order): ?>
                                    <tr class="clickable-row" data-href="<?= Url::to(['view-order', 'id' => $order->id]) ?>">
                                        <td>
                                            <?= Html::a(
                                                Html::encode($order->order_number),
                                                ['view-order', 'id' => $order->id],
                                                ['class' => 'fw-bold text-decoration-none', 'title' => 'Просмотр заказа']
                                            ) ?>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 180px;" title="<?= Html::encode($order->client_name . ' | ' . $order->client_phone) ?>">
                                                <strong><?= Html::encode($order->client_name) ?></strong><br>
                                                <small class="text-muted"><?= Html::encode($order->client_phone) ?></small>
                                            </div>
                                        </td>
                                        <td class="fw-semibold"><?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN</td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'created' => 'warning',
                                                'paid' => 'info',
                                                'ordered' => 'primary',
                                                'received' => 'success',
                                                'issued' => 'dark',
                                                'canceled' => 'danger',
                                            ];
                                            $badgeClass = $badges[$order->status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?> w-100"><?= $order->getStatusLabel() ?></span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?= Html::encode($order->comment) ?>">
                                                <?= $order->comment ? Html::encode($order->comment) : '<span class="text-muted">—</span>' ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($order->payment_proof): ?>
                                                <?php
                                                // Формируем полный URL к файлу
                                                $fileUrl = Yii::$app->request->hostInfo . $order->payment_proof;
                                                ?>
                                                <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Просмотр чека">
                                                    <i class="bi bi-file-earmark-image"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted" title="Чек не загружен">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($order->logist): ?>
                                                    <i class="bi bi-person-badge text-primary" title="Логист"></i> <?= Html::encode($order->logist->username) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Не назначен</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><small><?= Yii::$app->formatter->asDatetime($order->created_at, 'php:d.m.Y') ?></small></td>
                                        <td class="text-center action-cell">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?= Html::a('<i class="bi bi-eye"></i>', ['view-order', 'id' => $order->id], [
                                                    'title' => 'Просмотр',
                                                    'class' => 'btn btn-outline-primary btn-sm',
                                                ]) ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2" class="text-end">Итого за месяц:</td>
                                        <td><?= Yii::$app->formatter->asDecimal($monthData['total'], 2) ?> BYN</td>
                                        <td colspan="5"><?= $monthData['count'] ?> заказов</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php $index++; endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Быстрая навигация по статусам стрелками
document.addEventListener('DOMContentLoaded', function() {
    const pills = Array.from(document.querySelectorAll('.status-pill'));
    if (!pills.length) return;
    
    const activeIndex = pills.findIndex(p => p.classList.contains('active'));
    
    document.addEventListener('keydown', function(e) {
        // Игнорируем, если фокус в поле ввода
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable)) {
            return;
        }
        
        let targetIndex = -1;
        
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            targetIndex = Math.min(pills.length - 1, activeIndex + 1);
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            targetIndex = Math.max(0, activeIndex - 1);
        }
        
        if (targetIndex >= 0 && pills[targetIndex]) {
            pills[targetIndex].scrollIntoView({ inline: 'center', behavior: 'smooth', block: 'nearest' });
            window.location.href = pills[targetIndex].getAttribute('href');
        }
    });
    
    // Автоскролл к активному пину при загрузке
    if (activeIndex >= 0 && pills[activeIndex]) {
        setTimeout(() => {
            pills[activeIndex].scrollIntoView({ inline: 'center', behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
});

// Кликабельные строки таблицы
document.addEventListener('DOMContentLoaded', function() {
    const clickableRows = document.querySelectorAll('.clickable-row');
    
    clickableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Игнорируем клик, если это кнопка действия или чекбокс
            if (e.target.closest('.action-cell') || e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            
            const href = this.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
        
        // Изменяем курсор при наведении
        row.style.cursor = 'pointer';
    });
});
</script>

<style>
/* Компактная таблица */
.compact-table {
    font-size: 0.875rem;
}

.compact-table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    vertical-align: middle;
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}

.compact-table tbody td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f1f3f5;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.card {
    border-radius: 0.5rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}

/* Быстрые фильтры */
.btn-group .btn {
    white-space: nowrap;
}

/* Статистические карточки */
.card-body h5 {
    font-size: 0.9rem;
}

.card-body h2 {
    font-size: 1.5rem;
}

/* Текст с обрезкой */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Компактная панель статусов с пинами */
.status-filter-bar {
    padding-top: 0.5rem;
    z-index: 1020;
}

.status-pills {
    overflow-x: auto;
    scrollbar-width: thin;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 50px;
    text-decoration: none;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    white-space: nowrap;
}

.status-pill:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #111827;
    transform: translateY(-1px);
}

.status-pill.active {
    background: #111827;
    border-color: #111827;
    color: white;
}

.status-pill .label {
    font-weight: 500;
}

.status-pill .count {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 24px;
    text-align: center;
}

.status-pill.active .count {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.status-pill:not(.active) .count {
    background: #e5e7eb;
    color: #111827;
}

/* Кликабельные строки */
.clickable-row {
    transition: background-color 0.2s ease;
}

.clickable-row:hover {
    background-color: #f3f4f6 !important;
}

.clickable-row:active {
    background-color: #e5e7eb !important;
}
</style>
