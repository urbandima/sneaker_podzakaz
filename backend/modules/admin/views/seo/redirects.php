<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = 'Управление редиректами';
?>

<div class="seo-redirects">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-info mb-3">
        <h5>🎯 Быстрые победы — исправление 404 ошибок</h5>
        <p class="mb-2">Создайте 301 редиректы для:</p>
        <ul class="mb-2">
            <li><strong>Удалённые товары:</strong> <code>/catalog/product/old-nike</code> → <code>/catalog</code></li>
            <li><strong>Изменённые URL:</strong> <code>/catalog/product/old-url</code> → <code>/catalog/product/new-url</code></li>
            <li><strong>Опечатки:</strong> <code>/catlog/product/nike</code> → <code>/catalog/product/nike</code></li>
        </ul>
        <p class="mb-0"><strong>Тип 301</strong> — постоянный редирект (передаёт SEO вес)<br>
        <strong>Тип 302</strong> — временный редирект<br>
        <strong>Тип 404</strong> — просто логировать (без редиректа)</p>
    </div>
    
    <p>
        <?= Html::a('➕ Добавить редирект', ['redirect-edit'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('← SEO панель', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'from_url',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::tag('code', Html::encode($model->from_url));
                },
            ],
            [
                'attribute' => 'to_url',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::tag('code', Html::encode($model->to_url));
                },
            ],
            [
                'attribute' => 'type',
                'value' => 'typeLabel',
            ],
            [
                'attribute' => 'is_active',
                'format' => 'boolean',
            ],
            'hit_count',
            [
                'attribute' => 'last_hit_at',
                'format' => 'datetime',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update} {delete}',
                'urlCreator' => function($action, $model) {
                    if ($action === 'update') {
                        return Url::to(['redirect-edit', 'id' => $model->id]);
                    }
                    if ($action === 'delete') {
                        return Url::to(['redirect-delete', 'id' => $model->id]);
                    }
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
