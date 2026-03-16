<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\bootstrap5\Modal;
use app\backend\modules\catalog\models\SizeGrid;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\SizeGrid $model */
/** @var app\backend\modules\catalog\models\Brand[] $brands */

$this->title = 'Размерная сетка: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Управление', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => 'Характеристики и размеры', 'url' => ['/admin/characteristic/index', 'tab' => 'sizes']];
$this->params['breadcrumbs'][] = $model->name;
?>

<div class="size-grid-update">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="page-eyebrow">Размерные сетки</p>
            <h1><?= Html::encode($model->name) ?></h1>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-arrow-left"></i> Все сетки', ['/admin/characteristic/index', 'tab' => 'sizes'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-trash"></i>', ['size-delete', 'id' => $model->id], [
                'class' => 'btn btn-outline-danger',
                'data-confirm' => 'Удалить размерную сетку?',
                'data-method' => 'post'
            ]) ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong><i class="bi bi-info-circle"></i> Основная информация</strong>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'brand_id')->dropDownList(
                                ArrayHelper::map($brands, 'id', 'name'),
                                ['prompt' => '-- Универсальная --']
                            ) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'gender')->dropDownList(SizeGrid::getGenderOptions()) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>

                    <?= $form->field($model, 'is_active')->checkbox() ?>

                    <div class="form-group">
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить', ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-rulers"></i> Размеры в сетке</strong>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sizeItemModal">
                        <i class="bi bi-plus-circle"></i> Добавить
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if ($model->items): ?>
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
                                                <?= Html::a('<i class="bi bi-trash"></i>', ['size-delete-item', 'id' => $item->id], [
                                                    'class' => 'btn btn-sm btn-outline-danger',
                                                    'title' => 'Удалить',
                                                    'data-method' => 'post',
                                                    'data-confirm' => 'Удалить размер?'
                                                ]) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-rulers" style="font-size: 48px;"></i>
                            <p class="mt-2">Размеры еще не добавлены</p>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#sizeItemModal">
                                <i class="bi bi-plus-circle"></i> Добавить первый размер
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="alert alert-info mt-3">
                <strong><i class="bi bi-lightbulb"></i> Совет:</strong> Добавьте размеры в порядке возрастания.
                Это ускорит выбор сетки при привязке к товарам.
            </div>
        </div>
    </div>
</div>

<?php Modal::begin([
    'id' => 'sizeItemModal',
    'title' => '<i class="bi bi-plus-circle"></i> Добавить размер',
]); ?>
<form action="<?= Url::to(['size-add-item', 'gridId' => $model->id]) ?>" method="post">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">US</label>
            <input type="text" name="SizeGridItem[us_size]" class="form-control" placeholder="9.5">
        </div>
        <div class="col-md-6">
            <label class="form-label">EU</label>
            <input type="text" name="SizeGridItem[eu_size]" class="form-control" placeholder="43">
        </div>
        <div class="col-md-6">
            <label class="form-label">UK</label>
            <input type="text" name="SizeGridItem[uk_size]" class="form-control" placeholder="8.5">
        </div>
        <div class="col-md-6">
            <label class="form-label">CM</label>
            <input type="number" step="0.1" name="SizeGridItem[cm_size]" class="form-control" placeholder="27.5">
        </div>
        <div class="col-12">
            <label class="form-label">Размер (общее обозначение) <span class="text-danger">*</span></label>
            <input type="text" name="SizeGridItem[size]" class="form-control" placeholder="9.5 US" required>
        </div>
    </div>
    <div class="form-group mt-3 text-end">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="btn btn-success">Добавить</button>
    </div>
</form>
<?php Modal::end(); ?>
