<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\grid\GridView;
use yii\helpers\Html;
use app\models\Characteristic;

$this->title = 'Характеристики товаров';
$this->params['breadcrumbs'][] = ['label' => 'Управление', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-characteristics-page">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="actions">
            <?= Html::a('<i class="bi bi-plus-circle"></i> Новая характеристика', ['create'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="bi bi-arrow-repeat"></i> Массовое назначение', ['bulk-assign'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-upload"></i> Импорт CSV', ['import'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-journal-text"></i> Справочник', ['guide'], ['class' => 'btn btn-link']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
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
</div>
