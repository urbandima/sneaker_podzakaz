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

<div class="customers-page">
    <div class="page-header">
        <h1><i class="bi bi-people"></i> Покупатели</h1>
        <div class="header-actions">
            <a href="<?= Url::to(['customer/export']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                <i class="bi bi-download"></i> Экспорт CSV
            </a>
        </div>
    </div>

    <div class="admin-stats mb-5">
        <div class="admin-stat-card">
            <div class="admin-stat-icon primary"><i class="bi bi-people-fill"></i></div>
            <div class="admin-stat-content">
                <div class="admin-stat-value"><?= number_format($stats['total']) ?></div>
                <div class="admin-stat-label">Всего покупателей</div>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
            <div class="admin-stat-content">
                <div class="admin-stat-value"><?= number_format($stats['active']) ?></div>
                <div class="admin-stat-label">Активных</div>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon danger"><i class="bi bi-x-circle-fill"></i></div>
            <div class="admin-stat-content">
                <div class="admin-stat-value"><?= number_format($stats['inactive']) ?></div>
                <div class="admin-stat-label">Заблокированных</div>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon info"><i class="bi bi-calendar-check"></i></div>
            <div class="admin-stat-content">
                <div class="admin-stat-value"><?= number_format($stats['new_today']) ?></div>
                <div class="admin-stat-label">Новых сегодня</div>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon warning"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="admin-stat-content">
                <div class="admin-stat-value"><?= number_format($stats['new_week']) ?></div>
                <div class="admin-stat-label">За неделю</div>
            </div>
        </div>
    </div>

    <form method="get" class="filters-bar mb-5">
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

    <div class="admin-card">
        <div class="admin-card-body p-0">
            <?php if ($dataProvider->getCount() > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Покупатель</th>
                            <th>Телефон</th>
                            <th class="text-center">Заказов</th>
                            <th class="text-center">Потрачено</th>
                            <th>Статус</th>
                            <th>Регистрация</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dataProvider->getModels() as $customer): ?>
                            <tr onclick="location.href='<?= Url::to(['customer/view', 'id' => $customer->id]) ?>'" style="cursor:pointer" class="customer-row">
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
                                    <span class="admin-badge admin-badge-<?= $customer->status == 10 ? 'success' : ($customer->status == 9 ? 'danger' : 'secondary') ?>">
                                        <?= $customer->getStatusLabel() ?>
                                    </span>
                                </td>
                                <td><?= Yii::$app->formatter->asDate($customer->created_at, 'short') ?></td>
                                <td onclick="event.stopPropagation()">
                                    <div class="actions-cell">
                                        <a href="<?= Url::to(['customer/view', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" title="Блокировка" onclick="toggleStatus(<?= $customer->id ?>)" data-customer-id="<?= $customer->id ?>">
                                            <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="pagination-wrapper mt-5">
                    <?= LinkPager::widget([
                        'pagination' => $dataProvider->getPagination(),
                        'options' => ['class' => 'pagination'],
                        'linkOptions' => ['class' => 'page-link'],
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="text-align:center;padding:3rem;color:var(--admin-text-secondary)">
                    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.4"></i>
                    <h3 style="margin:0 0 0.5rem 0">Покупатели не найдены</h3>
                    <p class="m-0">Попробуйте изменить параметры поиска</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

