<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Category $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Категории', 'url' => ['/admin/category']];
$this->params['breadcrumbs'][] = $this->title;

$productCount = \app\backend\modules\catalog\models\Product::find()
    ->where(['category_id' => $model->id])
    ->count();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1"><?= Html::encode($model->name) ?></h1>
        <?php if ($model->is_active): ?>
            <span class="badge bg-success">Активна</span>
        <?php else: ?>
            <span class="badge bg-secondary">Неактивна</span>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['/admin/category/'.$model->id.'/edit'], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/category'], ['class' => 'btn btn-secondary']) ?>
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

                    <dt class="col-sm-4 text-muted">Родитель</dt>
                    <dd class="col-sm-8">
                        <?= $model->parent ? Html::encode($model->parent->name) : '<span class="text-muted">Нет</span>' ?>
                    </dd>

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
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold">Изображение</div>
            <div class="card-body text-center">
                <?php if ($model->image): ?>
                    <img src="<?= Html::encode($model->image) ?>"
                         alt="<?= Html::encode($model->name) ?>"
                         style="max-width:100%; max-height:220px; border-radius:10px; object-fit:cover;">
                <?php else: ?>
                    <div style="width:100%;height:140px;background:#f3f4f6;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-direction:column;">
                        <i class="bi bi-image" style="font-size:2rem"></i>
                        <div class="mt-2" style="font-size:.85rem">Нет изображения</div>
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <?= Html::a(
                        $model->image ? '<i class="bi bi-arrow-repeat"></i> Заменить фото' : '<i class="bi bi-upload"></i> Загрузить фото',
                        ['/admin/category/'.$model->id.'/edit'],
                        ['class' => 'btn btn-sm btn-outline-primary']
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <?= Html::a('<i class="bi bi-trash"></i> Удалить', ['/admin/category/'.$model->id.'/delete'],
        ['class' => 'btn btn-outline-danger',
         'data-confirm' => 'Удалить категорию «'.$model->name.'»? Это действие необратимо.',
         'data-method' => 'post']) ?>
</div>
