<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\loyalty\models\LoyaltyPoints;

$this->title = 'Мои баллы лояльности';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="loyalty-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-primary mb-0"><?= $info['balance'] ?></h2>
                    <p class="text-muted mb-0">Баллов на счёте</p>
                    <?php if ($info['canRedeem']): ?>
                        <small class="text-success">≈ <?= Yii::$app->formatter->asCurrency($info['redeemValue']) ?></small>
                    <?php else: ?>
                        <small class="text-muted">Минимум для списания: <?= $info['minPoints'] ?? 100 ?> баллов</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="mb-2">
                        <?php if ($info['level']): ?>
                            <span class="badge bg-success"><?= Html::encode($info['level']->name) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Новичок</span>
                        <?php endif; ?>
                    </h5>
                    <p class="text-muted mb-0">Ваш уровень</p>
                    <?php if ($info['level']): ?>
                        <small class="text-info">Множитель: x<?= $info['level']->points_multiplier ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <?php if ($info['nextLevel']): ?>
                        <h5 class="mb-2"><?= Html::encode($info['nextLevel']->name) ?></h5>
                        <p class="text-muted mb-0">Следующий уровень</p>
                        <small class="text-warning">Ещё <?= $info['pointsToNextLevel'] ?> баллов</small>
                    <?php else: ?>
                        <h5 class="mb-2">🏆</h5>
                        <p class="text-muted mb-0">Максимальный уровень</p>
                        <small class="text-success">Поздравляем!</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">История операций</h5>
            <?= Html::a('О программе лояльности', ['program'], ['class' => 'btn btn-sm btn-info']) ?>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover'],
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'label' => 'Дата',
                    ],
                    [
                        'attribute' => 'type',
                        'value' => function ($model) {
                            return LoyaltyPoints::getTypeList()[$model->type] ?? $model->type;
                        },
                        'label' => 'Тип операции',
                    ],
                    [
                        'attribute' => 'points',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->points > 0) {
                                return '<span class="text-success fw-bold">+' . $model->points . '</span>';
                            }
                            return '<span class="text-danger fw-bold">' . $model->points . '</span>';
                        },
                        'label' => 'Баллы',
                    ],
                    [
                        'attribute' => 'description',
                        'label' => 'Описание',
                    ],
                    [
                        'attribute' => 'order_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->order_id) {
                                return Html::a('Заказ #' . $model->order_id, ['/account/orders/view', 'id' => $model->order_id]);
                            }
                            return '—';
                        },
                        'label' => 'Заказ',
                    ],
                    [
                        'attribute' => 'expires_at',
                        'format' => 'date',
                        'value' => function ($model) {
                            if (!$model->expires_at) return '—';
                            $date = strtotime($model->expires_at);
                            $now = time();
                            if ($date < $now) {
                                return '<span class="text-danger">Истекли</span>';
                            }
                            $days = floor(($date - $now) / 86400);
                            if ($days < 30) {
                                return '<span class="text-warning">' . Yii::$app->formatter->asDate($model->expires_at) . '</span>';
                            }
                            return Yii::$app->formatter->asDate($model->expires_at);
                        },
                        'format' => 'raw',
                        'label' => 'Срок действия',
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <div class="alert alert-info">
        <h5>💡 Как использовать баллы?</h5>
        <ul class="mb-0">
            <li>Баллы начисляются за каждый заказ</li>
            <li>Минимум для списания: <?= $info['minPoints'] ?? 100 ?> баллов</li>
            <li>Можно оплатить до 50% заказа баллами</li>
            <li>1 балл = <?= Yii::$app->formatter->asCurrency($info['pointValue'] ?? 0.01) ?></li>
        </ul>
    </div>
</div>
