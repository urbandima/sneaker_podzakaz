<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\returns\models\ReturnRequest $model */

$this->title = 'Заявка ' . Html::encode($model->return_number);

$isCommission = ($model->return_type ?? '') === 'commission';

// Checklist steps for commission type
$steps = [
    'contract'      => ['label' => 'Договор подписан',         'icon' => 'file-earmark-text'],
    'epass'         => ['label' => 'ePass оформлен',           'icon' => 'passport'],
    'e_signature'   => ['label' => 'Электронный знак получен', 'icon' => 'pen-fill'],
    'published'     => ['label' => 'Размещение выполнено',     'icon' => 'box-arrow-up'],
];

$checklistData = is_string($model->checklist_data ?? null)
    ? json_decode($model->checklist_data, true) ?? []
    : ($model->checklist_data ?? []);

$statusColors = [
    'pending'    => 'warning',
    'approved'   => 'info',
    'processing' => 'primary',
    'completed'  => 'success',
    'rejected'   => 'danger',
];
$statusColor = $statusColors[$model->status] ?? 'secondary';
?>

<div class="admin-header">
    <div>
        <a href="<?= Url::to(['index']) ?>" style="color: var(--admin-text-secondary); text-decoration: none; font-size: 0.875rem;">
            <i class="bi bi-arrow-left"></i> Все возвраты
        </a>
        <h1 class="admin-header-title" style="margin-top: 0.25rem;"><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="admin-header-actions">
        <?php if ($isCommission): ?>
        <a href="<?= Url::to(['contract', 'id' => $model->id]) ?>" class="admin-btn admin-btn-secondary" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i>
            PDF договор
        </a>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: start;">

    <!-- Left column -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Main info -->
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-info-circle"></i>
                Информация о заявке
            </h2>
            <div class="return-info-grid" style="margin-top: 1.5rem;">
                <div class="return-info-row">
                    <span class="return-info-label">Тип возврата</span>
                    <span class="return-info-value">
                        <?php if ($isCommission): ?>
                            <span class="admin-badge admin-badge-info"><i class="bi bi-handshake"></i> Комиссионный договор</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-warning"><i class="bi bi-cash-coin"></i> Возврат денег</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="return-info-row">
                    <span class="return-info-label">Заказ</span>
                    <span class="return-info-value">
                        <a href="<?= Url::to(['/admin/order/view', 'id' => $model->order_id]) ?>" style="color: var(--admin-accent, #2563eb);">
                            Заказ #<?= Html::encode($model->order_id) ?>
                        </a>
                    </span>
                </div>
                <div class="return-info-row">
                    <span class="return-info-label">Статус</span>
                    <span class="return-info-value">
                        <span class="admin-badge admin-badge-<?= $statusColor ?>">
                            <?= Html::encode($model->getStatusName()) ?>
                        </span>
                    </span>
                </div>
                <div class="return-info-row">
                    <span class="return-info-label">Причина</span>
                    <span class="return-info-value"><?= Html::encode($model->getReasonName()) ?></span>
                </div>
                <?php if ($model->refund_amount): ?>
                <div class="return-info-row">
                    <span class="return-info-label">Сумма возврата</span>
                    <span class="return-info-value" style="font-weight: 700; font-size: 1.1rem;">
                        <?= number_format($model->refund_amount, 2) ?> BYN
                    </span>
                </div>
                <?php endif; ?>
                <div class="return-info-row">
                    <span class="return-info-label">Создана</span>
                    <span class="return-info-value"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></span>
                </div>
                <?php if (!empty($model->description)): ?>
                <div class="return-info-row" style="flex-direction: column; gap: 0.5rem;">
                    <span class="return-info-label">Описание</span>
                    <span class="return-info-value"><?= nl2br(Html::encode($model->description)) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Commission checklist -->
        <?php if ($isCommission): ?>
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-list-check"></i>
                Чек-лист комиссионного договора
            </h2>
            <p style="color: var(--admin-text-secondary); font-size: 0.875rem; margin: 0.5rem 0 1.5rem;">
                Отмечайте шаги по мере выполнения. Каждый шаг фиксируется с датой и исполнителем.
            </p>
            <div class="checklist-steps">
                <?php $stepIdx = 0; foreach ($steps as $stepKey => $step):
                    $stepData   = $checklistData[$stepKey] ?? [];
                    $done       = !empty($stepData['done']);
                    $doneDate   = $stepData['date'] ?? null;
                    $doneAuthor = $stepData['author'] ?? null;
                    $stepIdx++;
                ?>
                <div class="checklist-step <?= $done ? 'checklist-step--done' : '' ?>" id="step-<?= $stepKey ?>">
                    <div class="checklist-step-number"><?= $stepIdx ?></div>
                    <div class="checklist-step-icon">
                        <i class="bi bi-<?= $done ? 'check-circle-fill' : $step['icon'] ?>"></i>
                    </div>
                    <div class="checklist-step-content">
                        <div class="checklist-step-label"><?= Html::encode($step['label']) ?></div>
                        <?php if ($done && $doneDate): ?>
                            <div class="checklist-step-meta">
                                <i class="bi bi-calendar3"></i> <?= Html::encode($doneDate) ?>
                                <?php if ($doneAuthor): ?>
                                    &nbsp;&bull;&nbsp;<i class="bi bi-person"></i> <?= Html::encode($doneAuthor) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="checklist-step-action">
                        <?php if (!$done): ?>
                            <button class="admin-btn admin-btn-primary"
                                    style="padding: 0.35rem 0.9rem; font-size: 0.8rem;"
                                    onclick="markStepDone(<?= $model->id ?>, '<?= $stepKey ?>', this)"
                                    data-update-url="<?= \yii\helpers\Url::to(['/admin/return/update-step']) ?>">
                                <i class="bi bi-check2"></i> Выполнено
                            </button>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-success"><i class="bi bi-check2-circle"></i> Готово</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin comment -->
        <?php if (!empty($model->admin_comment)): ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-chat-text"></i> Комментарий</h2>
            <p style="margin-top: 1rem; line-height: 1.6;"><?= nl2br(Html::encode($model->admin_comment)) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column: actions -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <?php if ($model->status === 'pending'): ?>
        <div class="admin-card">
            <h3 class="admin-card-title" style="font-size: 0.95rem;"><i class="bi bi-lightning"></i> Действия</h3>
            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?= Html::beginForm(['approve', 'id' => $model->id], 'post') ?>
                    <div class="form-group">
                        <label>Комментарий</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Необязательно"></textarea>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-success" style="width: 100%;">
                        <i class="bi bi-check-circle"></i> Одобрить
                    </button>
                <?= Html::endForm() ?>

                <?= Html::beginForm(['reject', 'id' => $model->id], 'post') ?>
                    <div class="form-group">
                        <label>Причина отказа <span style="color: var(--admin-danger);">*</span></label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Укажите причину отклонения" required></textarea>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-danger" style="width: 100%;">
                        <i class="bi bi-x-circle"></i> Отклонить
                    </button>
                <?= Html::endForm() ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($model->status === 'approved'): ?>
        <div class="admin-card">
            <h3 class="admin-card-title" style="font-size: 0.95rem;"><i class="bi bi-play-circle"></i> Обработка</h3>
            <?= Html::beginForm(['process', 'id' => $model->id], 'post', ['style' => 'margin-top: 1rem;']) ?>
                <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%;">
                    <i class="bi bi-gear"></i> Начать обработку
                </button>
            <?= Html::endForm() ?>
        </div>
        <?php endif; ?>

        <?php if ($model->status === 'processing'): ?>
        <div class="admin-card">
            <h3 class="admin-card-title" style="font-size: 0.95rem;"><i class="bi bi-flag-fill"></i> Завершение</h3>
            <?= Html::beginForm(['complete', 'id' => $model->id], 'post', ['style' => 'margin-top: 1rem;']) ?>
                <p style="font-size: 0.8rem; color: var(--admin-text-secondary); margin-bottom: 1rem;">
                    При завершении средства будут возвращены клиенту.
                </p>
                <button type="submit" class="admin-btn admin-btn-success" style="width: 100%;">
                    <i class="bi bi-check2-all"></i> Завершить возврат
                </button>
            <?= Html::endForm() ?>
        </div>
        <?php endif; ?>

        <!-- Status history -->
        <div class="admin-card">
            <h3 class="admin-card-title" style="font-size: 0.95rem;"><i class="bi bi-clock-history"></i> История</h3>
            <div style="margin-top: 1rem;">
                <div class="timeline-item">
                    <div class="timeline-dot" style="background: var(--admin-accent, #2563eb);"></div>
                    <div class="timeline-body">
                        <div style="font-weight: 600; font-size: 0.875rem;">Заявка создана</div>
                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary);"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></div>
                    </div>
                </div>
                <?php if (!empty($model->updated_at) && $model->updated_at !== $model->created_at): ?>
                <div class="timeline-item">
                    <div class="timeline-dot" style="background: <?= $statusColor === 'success' ? '#22c55e' : '#f59e0b' ?>;"></div>
                    <div class="timeline-body">
                        <div style="font-weight: 600; font-size: 0.875rem;"><?= Html::encode($model->getStatusName()) ?></div>
                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary);"><?= Yii::$app->formatter->asDatetime($model->updated_at) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

