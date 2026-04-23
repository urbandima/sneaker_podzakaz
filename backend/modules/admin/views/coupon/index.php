<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\coupon\models\Coupon;

$this->title = 'Купоны';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-graph-up"></i> Статистика', ['statistics'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    Html::a('<i class="bi bi-plus-circle"></i> Создать купон', ['create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm'])
];
?>

<!-- Фильтры -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-funnel"></i>
        Фильтры
    </h2>
    
    <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
        <div class="form-group">
            <label>Поиск</label>
            <input type="text" name="search" class="form-control" placeholder="Поиск по коду или названию" value="<?= Html::encode(Yii::$app->request->get('search')) ?>">
        </div>
        
        <div class="form-group">
            <label>Статус</label>
            <select name="status" class="form-control">
                <option value="">Все статусы</option>
                <option value="1" <?= Yii::$app->request->get('status') === '1' ? 'selected' : '' ?>>Активные</option>
                <option value="0" <?= Yii::$app->request->get('status') === '0' ? 'selected' : '' ?>>Неактивные</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Тип</label>
            <select name="type" class="form-control">
                <option value="">Все типы</option>
                <?php foreach (Coupon::getTypeList() as $key => $label): ?>
                    <option value="<?= $key ?>" <?= Yii::$app->request->get('type') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="display: flex; align-items: end; gap: 0.5rem;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-search"></i>
                Фильтр
            </button>
            <a href="<?= \yii\helpers\Url::to(['index']) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-x-circle"></i>
                Сбросить
            </a>
        </div>
    </form>
</div>

<!-- Таблица купонов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">
            <i class="bi bi-ticket-perforated"></i>
            Список купонов
        </h2>
    </div>
    <div class="admin-card-body p-0">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Код</th>
                        <th>Тип</th>
                        <th>Значение</th>
                        <th>Мин. заказ</th>
                        <th>Исп. / Макс.</th>
                        <th>Действителен до</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $model): ?>
                        <tr>
                            <td class="fw-600">
                                <a href="<?= \yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" style="color: var(--admin-primary);">
                                    <?= Html::encode($model->code) ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $typeColors = [
                                    \app\backend\modules\coupon\models\Coupon::TYPE_PERCENTAGE => 'info',
                                    \app\backend\modules\coupon\models\Coupon::TYPE_FIXED => 'warning',
                                    \app\backend\modules\coupon\models\Coupon::TYPE_FREE_SHIPPING => 'success',
                                    \app\backend\modules\coupon\models\Coupon::TYPE_BUY_X_GET_Y => 'primary',
                                ];
                                ?>
                                <span class="admin-badge admin-badge-<?= $typeColors[$model->type] ?? 'secondary' ?>">
                                    <?= Html::encode($model->getTypeName()) ?>
                                </span>
                            </td>
                            <td><?= $model->getDiscountDescription() ?></td>
                            <td><?= $model->min_order_amount ? number_format($model->min_order_amount, 2) . ' BYN' : '—' ?></td>
                            <td>
                                <?php
                                $usedCount = $model->current_uses ?? $model->used_count ?? 0;
                                $maxUses = $model->max_uses ?? null;
                                if ($maxUses) {
                                    $percent = ($usedCount / $maxUses) * 100;
                                    $color = $percent >= 100 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
                                    echo Html::encode($usedCount) . ' / ' . Html::encode($maxUses);
                                    echo ' <span class="admin-badge admin-badge-' . $color . '">' . round($percent) . '%</span>';
                                } else {
                                    echo Html::encode($usedCount) . ' / ∞';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $expiryField = $model->valid_until ?? $model->expires_at ?? $model->expiry ?? null;
                                if (!$expiryField): ?>
                                    —
                                <?php else: ?>
                                    <?php
                                    $date = is_numeric($expiryField) ? $expiryField : strtotime($expiryField);
                                    $now = time();
                                    if ($date < $now): ?>
                                        <span class="text-danger"><?= date('d.m.Y', $date) ?> (истёк)</span>
                                    <?php else: ?>
                                        <?= date('d.m.Y', $date) ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $expiryTs = isset($expiryField) && $expiryField ? (is_numeric($expiryField) ? $expiryField : strtotime($expiryField)) : null;
                                $maxUsesV = $model->max_uses ?? null;
                                $usedV = $model->current_uses ?? $model->used_count ?? 0;
                                $isActive = $model->is_active ?? true;
                                if ($expiryTs && $expiryTs < time()):
                                ?>
                                    <span class="admin-badge admin-badge-secondary">Истёк</span>
                                <?php elseif ($maxUsesV && $usedV >= $maxUsesV): ?>
                                    <span class="admin-badge admin-badge-danger">Лимит исчерпан</span>
                                <?php elseif ($isActive): ?>
                                    <span class="admin-badge admin-badge-success">Активный</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="<?= \yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" 
                                       class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= \yii\helpers\Url::to(['update', 'id' => $model->id]) ?>" 
                                       class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $model->id], [
                                        'class' => 'admin-btn admin-btn-danger',
                                        'style' => 'padding: 0.25rem 0.5rem; font-size: 0.75rem;',
                                        'title' => 'Удалить',
                                        'data-confirm' => 'Вы уверены, что хотите удалить этот купон?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Пагинация -->
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
</div>

