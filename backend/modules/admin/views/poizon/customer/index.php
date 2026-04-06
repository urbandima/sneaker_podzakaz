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
