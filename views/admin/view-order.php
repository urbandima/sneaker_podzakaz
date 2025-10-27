<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . $model->order_number;
$user = Yii::$app->user->identity;
?>

<div class="admin-view-order">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if (!$user->isLogist()): ?>
                <?= Html::a('Редактировать', ['update-order', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?php endif; ?>
            <?= Html::a('← К списку', ['orders'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Информация о заказе -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Информация о клиенте</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ФИО:</strong> <?= Html::encode($model->client_name) ?></p>
                            <p><strong>Телефон:</strong> <?= Html::encode($model->client_phone) ?></p>
                            <p><strong>Email:</strong> <?= Html::encode($model->client_email) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Срок доставки:</strong> <?= Html::encode($model->delivery_date) ?></p>
                            <p><strong>Создан:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
                            <p><strong>Менеджер:</strong> <?= $model->creator ? Html::encode($model->creator->username) : '-' ?></p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p><strong>Публичная ссылка:</strong></p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= $model->getPublicUrl() ?>" id="public-link" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">
                                <i class="bi bi-clipboard"></i> Копировать
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Товары</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Наименование</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Итого</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= Html::encode($item->product_name) ?></td>
                                <td><?= $item->quantity ?></td>
                                <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</td>
                                <td><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> BYN</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="4" class="text-end">ИТОГО:</th>
                                <th><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- История изменений -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">История изменений</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="badge bg-secondary rounded-circle" style="width: 40px; height: 40px; line-height: 28px;">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold"><?= $history->getNewStatusLabel() ?></div>
                                    <div class="text-muted small">
                                        <?= Yii::$app->formatter->asDatetime($history->created_at) ?>
                                        <?php if ($history->changer): ?>
                                            • <?= Html::encode($history->changer->username) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($history->comment): ?>
                                        <div class="mt-1"><?= Html::encode($history->comment) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Статус -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Статус заказа</h5>
                </div>
                <div class="card-body">
                    <h4 class="text-center mb-3">
                        <span class="badge bg-primary"><?= $model->getStatusLabel() ?></span>
                    </h4>

                    <form method="post" action="<?= Url::to(['change-status', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Изменить статус</label>
                            <select name="status" class="form-select">
                                <?php
                                $statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();
                                foreach ($statuses as $key => $label):
                                ?>
                                    <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Опционально..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Изменить статус</button>
                    </form>
                </div>
            </div>

            <!-- Назначение логиста (только для админа) -->
            <?php if ($user->isAdmin()): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Логистика</h5>
                </div>
                <div class="card-body">
                    <p><strong>Текущий логист:</strong><br>
                    <?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></p>

                    <form method="post" action="<?= Url::to(['assign-logist', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Назначить логиста</label>
                            <select name="logist_id" class="form-select">
                                <option value="">Не назначен</option>
                                <?php
                                $logists = \app\models\User::find()->where(['role' => 'logist'])->all();
                                foreach ($logists as $logist):
                                ?>
                                    <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                        <?= Html::encode($logist->username) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">Назначить</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Подтверждение оплаты -->
            <?php if ($model->payment_proof): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Подтверждение оплаты</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        Загружено: <?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?>
                    </p>
                    <a href="<?= $model->payment_proof ?>" target="_blank" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-image"></i> Просмотреть файл
                    </a>

                    <?php if ($model->offer_accepted): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="bi bi-check-circle"></i> Оферта принята
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const link = document.getElementById('public-link');
    
    // Современный API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link.value).then(function() {
            showCopyNotification('Ссылка скопирована!');
        }).catch(function(err) {
            fallbackCopy(link);
        });
    } else {
        fallbackCopy(link);
    }
}

function fallbackCopy(input) {
    input.select();
    try {
        document.execCommand('copy');
        showCopyNotification('Ссылка скопирована!');
    } catch (err) {
        alert('Не удалось скопировать ссылку');
    }
}

function showCopyNotification(message) {
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Скопировано!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    
    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
