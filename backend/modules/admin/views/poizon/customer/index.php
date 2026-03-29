<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|null $search */
/** @var int|null $status */
/** @var array $stats */

$this->title = 'Покупатели';
?>

<style>
.customers-page {
    padding: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-export {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-export:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-card .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 0.8125rem;
    color: #6b7280;
}

.stat-card.total .stat-icon { background: #dbeafe; color: #1d4ed8; }
.stat-card.active .stat-icon { background: #d1fae5; color: #059669; }
.stat-card.inactive .stat-icon { background: #fee2e2; color: #dc2626; }
.stat-card.today .stat-icon { background: #fef3c7; color: #d97706; }
.stat-card.week .stat-icon { background: #e0e7ff; color: #4338ca; }

.filters-bar {
    background: #fff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
}

.search-box input:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select {
    padding: 0.625rem 2rem 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    background: #fff;
    cursor: pointer;
}

.customers-table {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.customers-table table {
    width: 100%;
    border-collapse: collapse;
}

.customers-table th {
    background: #f9fafb;
    padding: 0.875rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.customers-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.customers-table tr:hover {
    background: #f9fafb;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
}

.customer-name {
    font-weight: 600;
    color: #1f2937;
}

.customer-email {
    font-size: 0.8125rem;
    color: #6b7280;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active { background: #d1fae5; color: #059669; }
.status-badge.inactive { background: #fee2e2; color: #dc2626; }
.status-badge.deleted { background: #f3f4f6; color: #6b7280; }

.stats-cell {
    text-align: center;
}

.stats-value {
    font-weight: 700;
    font-size: 1rem;
}

.stats-label {
    font-size: 0.6875rem;
    color: #9ca3af;
}

.actions-cell {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background: #f3f4f6;
    color: #374151;
}

.btn-action.view:hover { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
.btn-action.block:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.pagination-wrapper {
    padding: 1rem;
    display: flex;
    justify-content: center;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
}
</style>

<div class="customers-page">
    <div class="page-header">
        <h1><i class="bi bi-people"></i> Покупатели</h1>
        <div class="header-actions">
            <a href="<?= Url::to(['customer/export']) ?>" class="btn-export">
                <i class="bi bi-download"></i> Экспорт CSV
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Всего покупателей</div>
        </div>
        <div class="stat-card active">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value"><?= number_format($stats['active']) ?></div>
            <div class="stat-label">Активных</div>
        </div>
        <div class="stat-card inactive">
            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value"><?= number_format($stats['inactive']) ?></div>
            <div class="stat-label">Заблокированных</div>
        </div>
        <div class="stat-card today">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value"><?= number_format($stats['new_today']) ?></div>
            <div class="stat-label">Новых сегодня</div>
        </div>
        <div class="stat-card week">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value"><?= number_format($stats['new_week']) ?></div>
            <div class="stat-label">За неделю</div>
        </div>
    </div>

    <form method="get" class="filters-bar">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="<?= Html::encode($search) ?>" placeholder="Поиск по email, телефону, имени...">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <option value="10" <?= $status == 10 ? 'selected' : '' ?>>Активные</option>
            <option value="9" <?= $status == 9 ? 'selected' : '' ?>>Заблокированные</option>
        </select>
    </form>

    <div class="customers-table">
        <?php if ($dataProvider->getCount() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Покупатель</th>
                        <th>Телефон</th>
                        <th style="text-align:center">Заказов</th>
                        <th style="text-align:center">Потрачено</th>
                        <th>Статус</th>
                        <th>Регистрация</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-avatar">
                                        <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="customer-name"><?= Html::encode($customer->getFullName()) ?></div>
                                        <div class="customer-email"><?= Html::encode($customer->email) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= Html::encode($customer->phone ?: '-') ?></td>
                            <td class="stats-cell">
                                <div class="stats-value"><?= $customer->orders_count ?></div>
                                <div class="stats-label">заказов</div>
                            </td>
                            <td class="stats-cell">
                                <div class="stats-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?= $customer->status == 10 ? 'active' : ($customer->status == 9 ? 'inactive' : 'deleted') ?>">
                                    <?= $customer->getStatusLabel() ?>
                                </span>
                            </td>
                            <td><?= Yii::$app->formatter->asDate($customer->created_at, 'short') ?></td>
                            <td>
                                <div class="actions-cell">
                                    <a href="<?= Url::to(['customer/view', 'id' => $customer->id]) ?>" class="btn-action view" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn-action block" title="Блокировка" onclick="toggleStatus(<?= $customer->id ?>)">
                                        <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination-wrapper">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->getPagination(),
                    'options' => ['class' => 'pagination'],
                    'linkOptions' => ['class' => 'page-link'],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h3>Покупатели не найдены</h3>
                <p>Попробуйте изменить параметры поиска</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleStatus(id) {
    if (!confirm('Изменить статус покупателя?')) return;
    
    fetch('/admin/customer/' + id + '/toggle-status', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>
