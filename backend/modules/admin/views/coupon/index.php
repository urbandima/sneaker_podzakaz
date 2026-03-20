<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\coupon\models\Coupon;

$this->title = 'Купоны';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="coupon-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('Статистика', ['statistics'], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Создать купон', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по коду или названию" value="<?= Html::encode(Yii::$app->request->get('search')) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Все статусы</option>
                        <option value="1" <?= Yii::$app->request->get('status') === '1' ? 'selected' : '' ?>>Активные</option>
                        <option value="0" <?= Yii::$app->request->get('status') === '0' ? 'selected' : '' ?>>Неактивные</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Все типы</option>
                        <?php foreach (Coupon::getTypeList() as $key => $label): ?>
                            <option value="<?= $key ?>" <?= Yii::$app->request->get('type') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Фильтр</button>
                </div>
            </form>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover'],
        'columns' => [
            [
                'attribute' => 'code',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->code), ['view', 'id' => $model->id], ['class' => 'fw-bold']);
                },
            ],
            'name',
            [
                'attribute' => 'type',
                'value' => function ($model) {
                    return $model->getTypeName();
                },
            ],
            [
                'label' => 'Скидка',
                'value' => function ($model) {
                    return $model->getDiscountDescription();
                },
            ],
            [
                'attribute' => 'current_uses',
                'label' => 'Использований',
                'format' => 'raw',
                'value' => function ($model) {
                    $text = $model->current_uses;
                    if ($model->max_uses) {
                        $text .= ' / ' . $model->max_uses;
                        $percent = ($model->current_uses / $model->max_uses) * 100;
                        $color = $percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
                        return $text . ' <span class="badge bg-' . $color . '">' . round($percent) . '%</span>';
                    }
                    return $text;
                },
            ],
            [
                'attribute' => 'valid_until',
                'format' => 'date',
                'value' => function ($model) {
                    if (!$model->valid_until) return '—';
                    $date = strtotime($model->valid_until);
                    $now = time();
                    if ($date < $now) {
                        return '<span class="text-danger">' . Yii::$app->formatter->asDate($model->valid_until) . ' (истёк)</span>';
                    }
                    return Yii::$app->formatter->asDate($model->valid_until);
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'is_active',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->is_active) {
                        return '<span class="badge bg-success">Активен</span>';
                    }
                    return '<span class="badge bg-secondary">Неактивен</span>';
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="bi bi-eye"></i>', $url, ['class' => 'btn btn-sm btn-info', 'title' => 'Просмотр']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<i class="bi bi-pencil"></i>', $url, ['class' => 'btn btn-sm btn-primary', 'title' => 'Редактировать']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<i class="bi bi-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-danger',
                            'title' => 'Удалить',
                            'data-confirm' => 'Вы уверены, что хотите удалить этот купон?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
