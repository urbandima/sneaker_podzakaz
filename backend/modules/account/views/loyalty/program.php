<?php

use yii\helpers\Html;

$this->title = 'Программа лояльности';
$this->params['breadcrumbs'][] = ['label' => 'Мои баллы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="loyalty-program">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ваш текущий статус</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if ($info['level']): ?>
                            <h2 class="text-primary"><?= Html::encode($info['level']->name) ?></h2>
                            <p class="text-muted">Множитель баллов: <strong>x<?= $info['level']->points_multiplier ?></strong></p>
                        <?php else: ?>
                            <h2 class="text-secondary">Новичок</h2>
                            <p class="text-muted">Совершите первый заказ!</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Баллов на счёте:</span>
                            <strong><?= $info['balance'] ?></strong>
                        </div>
                        <?php if ($info['nextLevel']): ?>
                            <div class="progress">
                                <?php 
                                $current = $info['level'] ? $info['level']->min_points : 0;
                                $next = $info['nextLevel']->min_points;
                                $progress = (($info['balance'] - $current) / ($next - $current)) * 100;
                                ?>
                                <div class="progress-bar" style="width: <?= min(100, $progress) ?>%"></div>
                            </div>
                            <small class="text-muted">До уровня "<?= $info['nextLevel']->name ?>": <?= $info['pointsToNextLevel'] ?> баллов</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Как работает программа?</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <strong>Покупайте</strong> — получайте <?= $service->pointsPerByn ?> баллов за каждый 1 BYN
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <strong>Копите</strong> — повышайте уровень и множитель баллов
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <strong>Тратьте</strong> — оплачивайте до <?= $service->maxRedeemPercent ?>% заказа баллами
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <strong>Бонусы</strong> — за регистрацию, отзывы, приглашения друзей
                        </li>
                    </ul>
                    
                    <div class="alert alert-warning mb-0">
                        <small>
                            <strong>Важно:</strong> Минимум для списания — <?= $service->minPointsToRedeem ?> баллов.
                            1 балл = <?= Yii::$app->formatter->asCurrency($service->pointValue) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Уровни программы лояльности</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Уровень</th>
                            <th>Минимум баллов</th>
                            <th>Множитель</th>
                            <th>Преимущества</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($levels as $level): ?>
                            <tr class="<?= $info['level'] && $info['level']->id === $level->id ? 'table-primary' : '' ?>">
                                <td>
                                    <strong><?= Html::encode($level->name) ?></strong>
                                    <?php if ($info['level'] && $info['level']->id === $level->id): ?>
                                        <span class="badge bg-primary ms-2">Ваш уровень</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= Yii::$app->formatter->asInteger($level->min_points) ?></td>
                                <td>
                                    <span class="badge bg-success">x<?= $level->points_multiplier ?></span>
                                </td>
                                <td>
                                    <?php if ($level->discount_percent > 0): ?>
                                        <span class="badge bg-info">Скидка <?= $level->discount_percent ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($level->free_shipping): ?>
                                        <span class="badge bg-warning">Бесплатная доставка</span>
                                    <?php endif; ?>
                                    <?php if ($level->priority_support): ?>
                                        <span class="badge bg-danger">Приоритетная поддержка</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Способы заработать баллы</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                        <h1 class="text-primary">🛒</h1>
                        <h5>Покупки</h5>
                        <p class="text-muted mb-0"><?= $service->pointsPerByn ?> баллов за 1 BYN</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                        <h1 class="text-success">📝</h1>
                        <h5>Регистрация</h5>
                        <p class="text-muted mb-0">100 баллов</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                        <h1 class="text-info">⭐</h1>
                        <h5>Отзыв</h5>
                        <p class="text-muted mb-0">50 баллов</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                        <h1 class="text-warning">👥</h1>
                        <h5>Реферал</h5>
                        <p class="text-muted mb-0">200 баллов</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <?= Html::a('Вернуться к истории баллов', ['index'], ['class' => 'btn btn-primary']) ?>
    </div>
</div>
