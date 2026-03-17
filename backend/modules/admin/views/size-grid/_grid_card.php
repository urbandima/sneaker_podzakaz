<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Карточка размерной сетки для ListView.
 *
 * @var yii\web\View $this
 * @var app\backend\modules\catalog\models\SizeGrid $model
 */

$statusClass = $model->is_active ? 'status-active' : 'status-archived';
$statusText = $model->is_active ? 'Активна' : 'Архив';
$genderLabel = $model->gender === 'male' ? 'Муж' : ($model->gender === 'female' ? 'Жен' : 'Унисекс');
?>

<div class="size-grid-card <?= $statusClass ?>">
    <div class="card-header">
        <div class="card-title">
            <h3><?= Html::encode($model->name) ?></h3>
            <?php if ($model->brand): ?>
                <span class="brand-badge"><?= Html::encode($model->brand->name) ?></span>
            <?php else: ?>
                <span class="brand-badge no-brand">Без бренда</span>
            <?php endif; ?>
        </div>
        <div class="card-status">
            <span class="status-indicator <?= $statusClass ?>">
                <i class="bi bi-circle-fill"></i>
                <?= $statusText ?>
            </span>
        </div>
    </div>

    <div class="card-body">
        <div class="card-meta">
            <div class="meta-item">
                <i class="bi bi-gender-ambiguous"></i>
                <span><?= $genderLabel ?></span>
            </div>
            <div class="meta-item">
                <i class="bi bi-rulers"></i>
                <span><?= $model->getSizeCount() ?> размеров</span>
            </div>
            <?php if ($model->description): ?>
                <div class="meta-item description">
                    <i class="bi bi-info-circle"></i>
                    <span><?= Html::encode($model->description) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($model->sizes)): ?>
            <div class="sizes-preview">
                <strong>Размеры:</strong>
                <div class="sizes-list">
                    <?php 
                    $sizes = array_slice($model->sizes, 0, 8);
                    foreach ($sizes as $size): 
                    ?>
                        <span class="size-tag"><?= Html::encode($size->eu_size ?? $size->us_size ?? $size->uk_size) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($model->sizes) > 8): ?>
                        <span class="size-tag more">+<?= count($model->sizes) - 8 ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-footer">
        <div class="card-stats">
            <span class="stat">
                <i class="bi bi-box"></i>
                <?= $model->getProductsCount() ?> товаров
            </span>
            <span class="stat">
                <i class="bi bi-calendar"></i>
                <?= Yii::$app->formatter->asDate($model->updated_at, 'short') ?>
            </span>
        </div>
        <div class="card-actions">
            <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" class="btn-action btn-sm btn-ghost" title="Просмотр">
                <i class="bi bi-eye"></i>
            </a>
            <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" class="btn-action btn-sm btn-ghost" title="Редактировать">
                <i class="bi bi-pencil"></i>
            </a>
            <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" 
               class="btn-action btn-sm btn-danger" 
               title="Удалить"
               data-method="post"
               data-confirm="Вы уверены, что хотите удалить эту размерную сетку?">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>
</div>
