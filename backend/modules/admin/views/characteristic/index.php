<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $characteristicsProvider
 * @var yii\data\ActiveDataProvider $sizeGridProvider
 * @var string $tab
 */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\catalog\models\Characteristic;
use app\backend\modules\catalog\models\SizeGrid;

$this->title = 'Характеристики и размеры';
$this->params['breadcrumbs'][] = ['label' => 'Управление', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-characteristics-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Каталог · Настройки</p>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="actions">
            <?= Html::a('<i class="bi bi-plus-circle"></i> Новая характеристика', ['create'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="bi bi-journal-text"></i> Справочник', ['guide'], ['class' => 'btn btn-link']) ?>
            <?= Html::a('<i class="bi bi-rulers"></i> Новая размерная сетка', ['size-create'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <ul class="nav nav-pills nav-fill shadow-sm mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'characteristics' ? 'active' : '' ?>"
               href="<?= Html::encode(Url::to(['index', 'tab' => 'characteristics'])) ?>">
                <i class="bi bi-sliders"></i> Характеристики
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'sizes' ? 'active' : '' ?>"
               href="<?= Html::encode(Url::to(['index', 'tab' => 'sizes'])) ?>">
                <i class="bi bi-rulers"></i> Размерные сетки
            </a>
        </li>
    </ul>

    <?php if ($tab === 'sizes'): ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <?= GridView::widget([
                    'dataProvider' => $sizeGridProvider,
                    'layout' => "{items}\n{pager}",
                    'tableOptions' => ['class' => 'table table-hover mb-0 align-middle'],
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['width' => '60'],
                        ],
                        [
                            'attribute' => 'name',
                            'label' => 'Название',
                            'format' => 'raw',
                            'value' => function (SizeGrid $model) {
                                return Html::a(
                                    Html::encode($model->name),
                                    ['size-update', 'id' => $model->id],
                                    ['class' => 'fw-semibold text-decoration-none']
                                );
                            },
                        ],
                        [
                            'attribute' => 'brand_id',
                            'label' => 'Бренд',
                            'format' => 'raw',
                            'value' => function (SizeGrid $model) {
                                if ($model->brand) {
                                    return '<span class="badge bg-dark">' . Html::encode($model->brand->name) . '</span>';
                                }
                                return '<span class="badge bg-secondary">Универсальная</span>';
                            },
                        ],
                        [
                            'attribute' => 'gender',
                            'label' => 'Пол',
                            'format' => 'raw',
                            'value' => function (SizeGrid $model) {
                                $colors = [
                                    'male' => 'primary',
                                    'female' => 'danger',
                                    'unisex' => 'info',
                                    'kids' => 'success',
                                ];
                                $color = $colors[$model->gender] ?? 'secondary';
                                return '<span class="badge bg-' . $color . '">' . $model->getGenderLabel() . '</span>';
                            },
                        ],
                        [
                            'label' => 'Размеров',
                            'value' => function (SizeGrid $model) {
                                return count($model->items);
                            },
                            'headerOptions' => ['width' => '100'],
                        ],
                        [
                            'attribute' => 'is_active',
                            'label' => 'Статус',
                            'format' => 'boolean',
                            'headerOptions' => ['width' => '90'],
                        ],
                        [
                            'attribute' => 'created_at',
                            'format' => 'datetime',
                            'headerOptions' => ['width' => '180'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Действия',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, SizeGrid $model) {
                                    return Html::a(
                                        '<i class="bi bi-pencil"></i>',
                                        ['size-update', 'id' => $model->id],
                                        ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Редактировать']
                                    );
                                },
                                'delete' => function ($url, SizeGrid $model) {
                                    return Html::a(
                                        '<i class="bi bi-trash"></i>',
                                        ['size-delete', 'id' => $model->id],
                                        [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'data-confirm' => 'Удалить размерную сетку?',
                                            'data-method' => 'post',
                                            'title' => 'Удалить',
                                        ]
                                    );
                                },
                            ],
                            'headerOptions' => ['width' => '120'],
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $characteristicsProvider,
                    'layout' => "{items}\n{pager}",
                    'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'id',
                        'key',
                        'name',
                        [
                            'attribute' => 'type',
                            'value' => function (Characteristic $model) {
                                return Characteristic::getTypeList()[$model->type] ?? $model->type;
                            },
                        ],
                        [
                            'attribute' => 'is_filter',
                            'format' => 'boolean',
                        ],
                        [
                            'attribute' => 'is_active',
                            'format' => 'boolean',
                        ],
                        'sort_order',
                        [
                            'label' => 'Значений',
                            'value' => function (Characteristic $model) {
                                return $model->getValues()->count();
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    <?php endif; ?>
</div>
