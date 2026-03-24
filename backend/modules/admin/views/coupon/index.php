<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\coupon\models\Coupon;

$this->title = 'Купоны';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= \yii\helpers\Url::to(['statistics']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-graph-up"></i>
            Статистика
        </a>
        <a href="<?= \yii\helpers\Url::to(['create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Создать купон
        </a>
    </div>
</div>

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
    <h2 class="admin-card-title">
        <i class="bi bi-ticket-perforated"></i>
        Список купонов
    </h2>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Скидка</th>
                    <th>Использований</th>
                    <th>Действителен до</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataProvider->getModels() as $model): ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <a href="<?= \yii\helpers\Url::to(['view', 'id' => $model->id]) ?>" style="color: var(--admin-primary);">
                                <?= Html::encode($model->code) ?>
                            </a>
                        </td>
                        <td><?= Html::encode($model->name) ?></td>
                        <td><?= Html::encode($model->getTypeName()) ?></td>
                        <td><?= $model->getDiscountDescription() ?></td>
                        <td>
                            <?php
                            $text = $model->current_uses;
                            if ($model->max_uses) {
                                $text .= ' / ' . $model->max_uses;
                                $percent = ($model->current_uses / $model->max_uses) * 100;
                                $color = $percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
                                echo $text . ' <span class="admin-badge admin-badge-' . $color . '">' . round($percent) . '%</span>';
                            } else {
                                echo $text;
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!$model->valid_until): ?>
                                —
                            <?php else: ?>
                                <?php
                                $date = strtotime($model->valid_until);
                                $now = time();
                                if ($date < $now): ?>
                                    <span style="color: var(--admin-danger);"><?= Yii::$app->formatter->asDate($model->valid_until) ?> (истёк)</span>
                                <?php else: ?>
                                    <?= Yii::$app->formatter->asDate($model->valid_until) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-<?= $model->is_active ? 'success' : 'secondary' ?>">
                                <?= $model->is_active ? 'Активен' : 'Неактивен' ?>
                            </span>
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

<style>
.form-control {
    width: 100%;
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

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
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
