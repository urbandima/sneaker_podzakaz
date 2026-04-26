<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Brand $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Бренды', 'url' => ['/admin/brand']];
$this->params['breadcrumbs'][] = $this->title;

$productCount = \app\backend\modules\catalog\models\Product::find()
    ->where(['brand_id' => $model->id])
    ->count();
$logoSrc = $model->logo ?: ($model->logo_url ?: null);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1"><?= Html::encode($model->name) ?></h1>
        <?php if ($model->is_active): ?>
            <span class="badge bg-success">Активен</span>
        <?php else: ?>
            <span class="badge bg-secondary">Неактивен</span>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['/admin/brand/'.$model->id.'/edit'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/brand'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Основное</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Название</dt>
                    <dd class="col-sm-8"><?= Html::encode($model->name) ?></dd>

                    <dt class="col-sm-4 text-muted">Slug</dt>
                    <dd class="col-sm-8"><code><?= Html::encode($model->slug) ?></code></dd>

                    <dt class="col-sm-4 text-muted">Порядок</dt>
                    <dd class="col-sm-8"><?= $model->sort_order ?></dd>

                    <dt class="col-sm-4 text-muted">Товаров</dt>
                    <dd class="col-sm-8"><span class="badge bg-light text-dark"><?= $productCount ?></span></dd>

                    <?php if ($model->description): ?>
                    <dt class="col-sm-4 text-muted">Описание</dt>
                    <dd class="col-sm-8"><?= Html::encode($model->description) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">SEO</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Meta Title</dt>
                    <dd class="col-sm-8"><?= Html::encode($model->meta_title ?: '—') ?></dd>
                    <dt class="col-sm-4 text-muted">Meta Description</dt>
                    <dd class="col-sm-8"><?= Html::encode($model->meta_description ?: '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-bold">Логотип</div>
            <div class="card-body text-center">
                <?php if ($logoSrc): ?>
                    <img src="<?= Html::encode($logoSrc) ?>"
                         alt="<?= Html::encode($model->name) ?>"
                         style="max-width:100%; max-height:120px; object-fit:contain; background:#f8fafc; padding:8px; border-radius:8px; border:1px solid #e5e7eb;">
                <?php else: ?>
                    <div style="width:100%;height:100px;background:#f3f4f6;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-direction:column;">
                        <i class="bi bi-image" style="font-size:1.8rem"></i>
                        <div class="mt-2" style="font-size:.85rem">Нет логотипа</div>
                    </div>
                <?php endif; ?>
                <?php if ($model->logo_url): ?>
                    <div class="mt-2 text-muted" style="font-size:.7rem; word-break:break-all;">
                        URL: <?= Html::encode($model->logo_url) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($model->cover_image)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">Обложка</div>
            <div class="card-body text-center">
                <img src="<?= Html::encode($model->cover_image) ?>"
                     alt="Обложка <?= Html::encode($model->name) ?>"
                     style="max-width:100%; border-radius:8px; object-fit:cover;">
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4">
    <?= Html::a('<i class="bi bi-trash"></i> Удалить', ['/admin/brand/'.$model->id.'/delete'],
        ['class' => 'btn btn-outline-danger',
         'data-confirm' => 'Удалить бренд «'.$model->name.'»? Это действие необратимо.',
         'data-method' => 'post']) ?>
</div>
