<?php

use yii\helpers\Html;
use app\backend\modules\admin\models\import\ImportNotification;

/** @var yii\web\View $this */

$notifications = ImportNotification::getUnread(5);
$unreadCount = ImportNotification::getUnreadCount();
?>

<li class="nav-item dropdown">
    <a class="nav-link" href="#" data-bs-toggle="dropdown">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
        <span class="badge badge-danger navbar-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">
            <?= $unreadCount ?> уведомлений
        </span>
        <div class="dropdown-divider"></div>
        
        <?php if (empty($notifications)): ?>
        <a class="dropdown-item" href="#">
            <i class="fas fa-check-circle text-muted"></i> Нет новых уведомлений
        </a>
        <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
        <a class="dropdown-item notification-item" href="#" data-id="<?= $notification->id ?>">
            <div class="media">
                <div class="media-body">
                    <p class="mb-0">
                        <i class="fas fa-<?= $notification->icon ?> text-<?= $notification->cssClass ?>"></i>
                        <strong><?= Html::encode($notification->title) ?></strong>
                    </p>
                    <small class="text-muted">
                        <?= Yii::$app->formatter->asRelativeTime($notification->created_at) ?>
                    </small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        
        <div class="dropdown-divider"></div>
        <a class="dropdown-item dropdown-footer" href="#" id="mark-all-read">
            <i class="fas fa-check-double"></i> Отметить все как прочитанные
        </a>
        <?php endif; ?>
        
        <div class="dropdown-divider"></div>
        <a class="dropdown-item dropdown-footer" href="<?= \yii\helpers\Url::to(['/admin/import/logs']) ?>">
            <i class="fas fa-list"></i> Все логи импорта
        </a>
    </div>
</li>

<?php
$this->registerJs(<<<JS
// Отметить уведомление как прочитанное
$('.notification-item').click(function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    
    $.post('/admin/import-ajax/mark-notification-read', {id: id}, function(data) {
        if (data.success) {
            location.reload();
        }
    });
});

// Отметить все как прочитанные
$('#mark-all-read').click(function(e) {
    e.preventDefault();
    
    $.post('/admin/import-ajax/mark-all-notifications-read', function(data) {
        if (data.success) {
            location.reload();
        }
    });
});
JS
);
?>
