<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $logs */
/** @var string|null $containerClass */

if (empty($logs)) {
    echo '<div class="admin-card"><div class="admin-card-body"><p class="text-center text-muted">Нет записей</p></div></div>';
    return;
}

?>
<div class="admin-card <?= $containerClass ?>">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title">
            <i class="bi bi-clock-history"></i> Недавние действия
        </h2>
        <a href="<?= Url::to(['/admin/log/index']) ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
            Все записи
        </a>
    </div>
    <div class="admin-card-body" style="padding:0">
        <div class="recent-actions-list">
            <?php foreach ($logs as $log): ?>
                <?php $url = $log->getEntityUrl(); ?>
                <div class="recent-action-item">
                    <div class="recent-action-icon <?= $log->getActionClass() ?>">
                        <i class="bi <?= $log->getActionIcon() ?>"></i>
                    </div>
                    <div class="recent-action-content">
                        <div class="recent-action-header">
                            <span class="recent-action-user"><?= Html::encode($log->username) ?></span>
                            <span class="recent-action-time" title="<?= Yii::$app->formatter->asDatetime($log->created_at) ?>">
                                <?= $log->getRelativeTime() ?>
                            </span>
                        </div>
                        <div class="recent-action-body">
                            <?php if ($url): ?>
                                <a href="<?= $url ?>" class="recent-action-link">
                                    <span class="recent-action-badge <?= $log->getActionClass() ?>">
                                        <?= $log->getActionLabel() ?>
                                    </span>
                                    <?php if ($log->entity_name): ?>
                                        <span class="recent-action-entity"><?= Html::encode($log->entity_name) ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <span class="recent-action-badge <?= $log->getActionClass() ?>">
                                    <?= $log->getActionLabel() ?>
                                </span>
                                <?php if ($log->entity_name): ?>
                                    <span class="recent-action-entity"><?= Html::encode($log->entity_name) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($log->description): ?>
                            <div class="recent-action-description">
                                <?= Html::encode($log->description) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.recent-actions-list {
    max-height: 400px;
    overflow-y: auto;
}

.recent-action-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--admin-border-light, #f3f4f6);
    transition: background 0.2s;
}

.recent-action-item:last-child {
    border-bottom: none;
}

.recent-action-item:hover {
    background: var(--admin-bg, #f9fafb);
}

.recent-action-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.recent-action-icon.success {
    background: #d1fae5;
    color: #065f46;
}

.recent-action-icon.warning {
    background: #fef3c7;
    color: #92400e;
}

.recent-action-icon.danger {
    background: #fee2e2;
    color: #991b1b;
}

.recent-action-icon.info {
    background: #dbeafe;
    color: #1d4ed8;
}

.recent-action-icon.primary {
    background: #e0e7ff;
    color: #312e81;
}

.recent-action-icon.secondary {
    background: #f3f4f6;
    color: #374151;
}

.recent-action-content {
    flex: 1;
    min-width: 0;
}

.recent-action-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.recent-action-user {
    font-size: 13px;
    font-weight: 600;
    color: var(--admin-text, #111827);
}

.recent-action-time {
    font-size: 12px;
    color: var(--admin-text-secondary, #6b7280);
}

.recent-action-body {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.recent-action-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    flex-wrap: wrap;
}

.recent-action-link:hover .recent-action-entity {
    text-decoration: underline;
}

.recent-action-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.recent-action-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.recent-action-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.recent-action-badge.danger {
    background: #fee2e2;
    color: #991b1b;
}

.recent-action-badge.info {
    background: #dbeafe;
    color: #1d4ed8;
}

.recent-action-badge.primary {
    background: #e0e7ff;
    color: #312e81;
}

.recent-action-badge.secondary {
    background: #f3f4f6;
    color: #374151;
}

.recent-action-entity {
    font-size: 13px;
    color: var(--admin-text, #111827);
    font-weight: 500;
}

.recent-action-description {
    font-size: 12px;
    color: var(--admin-text-secondary, #6b7280);
    margin-top: 4px;
    line-height: 1.4;
}
</style>
