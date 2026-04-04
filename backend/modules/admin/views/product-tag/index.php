<?php
/**
 * Список тегов в админ-панели
 * 
 * @var ProductTagSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Теги товаров';
$this->params['breadcrumbs'][] = ['label' => 'Каталог', 'url' => '#'];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="product-tag-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="page-header-actions">
            <?= Html::a('<i class="icon-plus"></i> Создать тег', ['create'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="icon-tags"></i> Массовое назначение', ['assign'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function($model) {
                    $color = $model->color ? ' style="background-color: ' . $model->color . '"' : '';
                    return Html::a(
                        '<span class="tag-badge"' . $color . '>' . Html::encode($model->name) . '</span>',
                        ['update', 'id' => $model->id]
                    );
                },
            ],
            
            [
                'attribute' => 'slug',
                'format' => 'text',
                'contentOptions' => ['class' => 'text-muted'],
            ],
            
            [
                'attribute' => 'color',
                'format' => 'raw',
                'value' => function($model) {
                    if (!$model->color) {
                        return '<span class="text-muted">—</span>';
                    }
                    return '<span class="color-preview" style="background-color: ' . $model->color . '"></span> ' . $model->color;
                },
                'contentOptions' => ['class' => 'color-cell'],
            ],
            
            [
                'attribute' => 'is_active',
                'format' => 'raw',
                'filter' => [1 => 'Активен', 0 => 'Неактивен'],
                'value' => function($model) {
                    $class = $model->is_active ? 'status-active' : 'status-inactive';
                    $text = $model->is_active ? 'Активен' : 'Неактивен';
                    return Html::a($text, ['toggle-active', 'id' => $model->id], [
                        'class' => 'status-toggle ' . $class,
                        'data-pjax' => 0,
                        'data-method' => 'post',
                    ]);
                },
            ],
            
            [
                'attribute' => 'sort_order',
                'format' => 'integer',
                'contentOptions' => ['class' => 'text-center'],
            ],
            
            [
                'label' => 'Товаров',
                'format' => 'raw',
                'value' => function($model) {
                    $count = $model->getProductsCount();
                    return Html::tag('span', $count, ['class' => 'badge']);
                },
            ],
            
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update} {delete}',
                'buttons' => [
                    'update' => function($url, $model) {
                        return Html::a('<i class="icon-edit"></i>', $url, [
                            'title' => 'Редактировать',
                            'class' => 'btn-icon',
                        ]);
                    },
                    'delete' => function($url, $model) {
                        return Html::a('<i class="icon-trash"></i>', $url, [
                            'title' => 'Удалить',
                            'class' => 'btn-icon btn-danger',
                            'data-confirm' => 'Удалить тег «' . $model->name . '»? Товаров с этим тегом: ' . $model->getProductsCount(),
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
        'pager' => [
            'class' => 'yii\widgets\LinkPager',
            'options' => ['class' => 'pagination'],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>

<?php
$this->registerCss("
.product-tag-index {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
}

.page-header-actions {
    display: flex;
    gap: 10px;
}

.tag-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #000;
    color: #fff;
    border-radius: 20px;
    font-size: 13px;
    text-decoration: none;
    transition: opacity 0.2s;
}

.tag-badge:hover {
    opacity: 0.8;
}

.color-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.color-preview {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
}

.status-toggle {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.status-toggle:hover {
    opacity: 0.8;
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    background: #f3f4f6;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #e5e7eb;
}

.btn-icon.btn-danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

.pagination {
    display: flex;
    gap: 4px;
    list-style: none;
    justify-content: center;
    margin-top: 20px;
}

.pagination li a,
.pagination li span {
    display: block;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
}

.pagination li.active span {
    background: #000;
    color: #fff;
    border-color: #000;
}
");
?>
