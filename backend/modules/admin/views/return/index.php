<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\returns\models\ReturnRequest;

$this->title = 'Возвраты';

$currentStatus = Yii::$app->request->get('status', '');
$tabs = [
    '' => 'Все',
    'pending' => 'Ожидает',
    'approved' => 'Одобрен',
    'rejected' => 'Отклонён',
];
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-circle"></i> Создать возврат', ['create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm'])
];
?>

<!-- Фильтр-табы -->
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 0;">
    <div style="display: flex; gap: 0; border-bottom: 1px solid var(--admin-border);">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= Url::to(['index', 'status' => $key, 'search' => Yii::$app->request->get('search')]) ?>"
               style="padding: 0.875rem 1.5rem; font-weight: 600; font-size: 0.9rem; text-decoration: none; border-bottom: 3px solid <?= $currentStatus === $key ? 'var(--admin-accent, #2563eb)' : 'transparent' ?>; color: <?= $currentStatus === $key ? 'var(--admin-accent, #2563eb)' : 'var(--admin-text-secondary)' ?>; transition: color 0.2s;">
                <?= Html::encode($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card">
    <form method="get" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <input type="hidden" name="status" value="<?= Html::encode($currentStatus) ?>">
        <input type="text" name="search" class="form-control" placeholder="Поиск по номеру заказа или клиенту" value="<?= Html::encode(Yii::$app->request->get('search')) ?>" style="flex: 1; min-width: 200px;">
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-search"></i>
            Фильтр
        </button>
        <a href="<?= Url::to(['index']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-x-circle"></i>
            Сбросить
        </a>
    </form>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th># Заказа</th>
                    <th>Клиент</th>
                    <th>Сумма</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dataProvider->getModels())): ?>
                    <?php foreach ($dataProvider->getModels() as $model): ?>
                        <tr>
                            <td>
                                <?= Html::a(
                                    '#' . ($model->order_id ?? $model->return_number),
                                    ['/admin/order/view', 'id' => $model->order_id],
                                    ['style' => 'font-weight: 600; color: var(--admin-accent, #2563eb);']
                                ) ?>
                            </td>
                            <td>
                                <?= Html::encode($model->order->client_name ?? $model->client_name ?? '—') ?>
                                <?php if (!empty($model->order->client_email ?? $model->client_email ?? null)): ?>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-secondary);"><?= Html::encode($model->order->client_email ?? $model->client_email) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($model->refund_amount ?? 0, 2) ?> BYN</td>
                            <td>
                                <?php $rtype = $model->return_type ?? 'refund'; ?>
                                <span class="admin-badge admin-badge-<?= $rtype === 'commission' ? 'info' : 'warning' ?>">
                                    <i class="bi bi-<?= $rtype === 'commission' ? 'handshake' : 'cash-coin' ?>"></i>
                                    <?= $rtype === 'commission' ? 'Комиссия' : 'Возврат' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'processing' => 'primary',
                                    'completed' => 'success',
                                    'rejected' => 'danger',
                                ];
                                $sc = $statusColors[$model->status] ?? 'secondary';
                                ?>
                                <span class="admin-badge admin-badge-<?= $sc ?>">
                                    <?= Html::encode($model->getStatusName()) ?>
                                </span>
                            </td>
                            <td><?= Yii::$app->formatter->asDate($model->created_at) ?></td>
                            <td>
                                <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" title="Просмотр">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-text-secondary);">
                            Нет заявок на возврат
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($dataProvider->pagination->pageCount > 1): ?>
        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'pagination'],
                'linkOptions' => ['class' => 'page-link'],
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($implicitReturns)): ?>
<div class="admin-card" style="margin-top:1.5rem">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-exclamation-circle text-warning"></i> Заказы со статусом "Возврат" (без заявки)</h2>
    </div>
    <table class="admin-table">
        <thead><tr>
            <th>№ Заказа</th><th>Клиент</th><th>Телефон</th><th>Сумма</th><th>Дата</th><th></th>
        </tr></thead>
        <tbody>
            <?php foreach ($implicitReturns as $ord): ?>
            <tr>
                <td><strong><?= Html::encode($ord->order_number) ?></strong></td>
                <td><?= Html::encode($ord->client_name) ?></td>
                <td><?= Html::encode($ord->client_phone) ?></td>
                <td><?= Yii::$app->formatter->asDecimal($ord->total_amount, 2) ?> Br</td>
                <td><?= $ord->created_at ? Yii::$app->formatter->asDate($ord->created_at, 'dd.MM.yyyy') : '—' ?></td>
                <td>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $ord->id]) ?>" class="admin-btn admin-btn-secondary" style="padding:.25rem .5rem;font-size:.875rem">
                        <i class="bi bi-eye"></i> Заказ
                    </a>
                    <a href="<?= Url::to(['/admin/return/create', 'order_id' => $ord->id]) ?>" class="admin-btn admin-btn-primary" style="padding:.25rem .5rem;font-size:.875rem">
                        <i class="bi bi-plus"></i> Создать заявку
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

