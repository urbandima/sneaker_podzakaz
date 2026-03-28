<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\returns\models\ReturnRequest;

$this->title = 'Возвраты';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <a href="<?= \yii\helpers\Url::to(['statistics']) ?>" class="admin-btn admin-btn-secondary">
        <i class="bi bi-graph-up"></i>
        Статистика
    </a>
</div>

<div class="admin-card">
    <form method="get" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <input type="text" name="search" class="form-control" placeholder="Поиск по номеру или причине" value="<?= Html::encode(Yii::$app->request->get('search')) ?>" style="flex: 1; min-width: 200px;">
        
        <select name="status" class="form-control" style="min-width: 150px;">
            <option value="">Все статусы</option>
            <?php foreach (ReturnRequest::getStatusList() as $key => $label): ?>
                <option value="<?= $key ?>" <?= Yii::$app->request->get('status') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-search"></i>
            Фильтр
        </button>
    </form>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Номер заявки</th>
                    <th>Заказ</th>
                    <th>Причина возврата</th>
                    <th>Сумма возврата</th>
                    <th>Статус</th>
                    <th>Создана</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dataProvider->getModels())): ?>
                    <?php foreach ($dataProvider->getModels() as $model): ?>
                        <tr>
                            <td><?= Html::a($model->return_number, ['view', 'id' => $model->id], ['style' => 'font-weight: 600;']) ?></td>
                            <td><?= Html::a('Заказ #' . $model->order_id, ['/admin/order/view', 'id' => $model->order_id]) ?></td>
                            <td><?= Html::encode($model->getReasonName()) ?></td>
                            <td><?= number_format($model->refund_amount, 2) ?> BYN</td>
                            <td><span class="admin-badge admin-badge-<?= $model->status === 'completed' ? 'success' : ($model->status === 'pending' ? 'warning' : 'info') ?>"><?= Html::encode($model->getStatusName()) ?></span></td>
                            <td><?= Yii::$app->formatter->asDatetime($model->created_at) ?></td>
                            <td>
                                <a href="<?= \yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
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

<style>
.form-control {
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.375rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    text-decoration: none;
    transition: all 0.2s;
}

.page-link:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
}
</style>
