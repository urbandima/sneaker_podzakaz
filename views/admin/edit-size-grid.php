<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

$this->title = 'Редактировать: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = ['label' => 'Размерные сетки', 'url' => ['size-grids']];
$this->params['breadcrumbs'][] = $model->name;
?>

<div class="size-grid-edit">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-arrow-left"></i> Назад к списку', ['size-grids'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <!-- Основная информация -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Основная информация</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Название сетки'
                    ])->label('Название сетки <span class="text-danger">*</span>', ['encode' => false]) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'brand_id')->dropDownList(
                                ArrayHelper::map($brands, 'id', 'name'),
                                ['prompt' => '-- Универсальная --']
                            )->label('Бренд') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'gender')->dropDownList(
                                \app\models\SizeGrid::getGenderOptions()
                            )->label('Пол <span class="text-danger">*</span>', ['encode' => false]) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'description')->textarea([
                        'rows' => 3,
                        'placeholder' => 'Описание особенностей'
                    ])->label('Описание') ?>

                    <?= $form->field($model, 'is_active')->checkbox()->label('Сетка активна') ?>

                    <div class="form-group">
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить', ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Размеры в сетке -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-rulers"></i> Размеры в сетке</h5>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#addSizeItemModal">
                            <i class="bi bi-plus-circle"></i> Добавить
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($model->items) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>US</th>
                                    <th>EU</th>
                                    <th>UK</th>
                                    <th>CM</th>
                                    <th>Размер</th>
                                    <th width="80">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->items as $item): ?>
                                <tr>
                                    <td><?= Html::encode($item->us_size ?: '-') ?></td>
                                    <td><?= Html::encode($item->eu_size ?: '-') ?></td>
                                    <td><?= Html::encode($item->uk_size ?: '-') ?></td>
                                    <td><?= Html::encode($item->cm_size ?: '-') ?></td>
                                    <td><strong><?= Html::encode($item->size) ?></strong></td>
                                    <td>
                                        <?= Html::a('<i class="bi bi-trash"></i>', 
                                            ['delete-size-grid-item', 'id' => $item->id], 
                                            [
                                                'class' => 'btn btn-sm btn-outline-danger',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Удалить размер?',
                                                'title' => 'Удалить'
                                            ]
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-rulers" style="font-size: 48px;"></i>
                        <p class="mt-2">Размеры еще не добавлены</p>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeItemModal">
                            <i class="bi bi-plus-circle"></i> Добавить первый размер
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <strong><i class="bi bi-lightbulb"></i> Совет:</strong><br>
                Добавьте все размеры по порядку. При использовании сетки все эти размеры будут добавлены к товару одним кликом.
            </div>
        </div>
    </div>
</div>

<!-- Modal: Добавить размер в сетку -->
<div class="modal fade" id="addSizeItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Добавить размер в сетку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= yii\helpers\Url::to(['add-size-grid-item', 'gridId' => $model->id]) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">US</label>
                                <input type="text" name="SizeGridItem[us_size]" class="form-control" 
                                       placeholder="9.5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">EU</label>
                                <input type="text" name="SizeGridItem[eu_size]" class="form-control" 
                                       placeholder="43">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">UK</label>
                                <input type="text" name="SizeGridItem[uk_size]" class="form-control" 
                                       placeholder="8.5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">CM</label>
                                <input type="number" step="0.1" name="SizeGridItem[cm_size]" class="form-control" 
                                       placeholder="27.5">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Размер (общее обозначение) <span class="text-danger">*</span></label>
                        <input type="text" name="SizeGridItem[size]" class="form-control" 
                               placeholder="9.5 US" required>
                        <div class="form-text">Обязательное поле</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>
