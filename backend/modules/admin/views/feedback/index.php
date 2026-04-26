<?php
/**
 * @var yii\web\View $this
 * @var app\backend\modules\common\models\Feedback[] $feedbacks
 * @var string $activeStatus
 * @var array $counts
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Сообщения директору';
$csrf = Yii::$app->request->csrfToken;
?>

<!-- Status filter tabs -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach ([
        ''         => ['label' => 'Все',           'count' => array_sum($counts)],
        'new'      => ['label' => 'Новые',          'count' => $counts['new']],
        'read'     => ['label' => 'Прочитанные',    'count' => $counts['read']],
        'replied'  => ['label' => 'Отвечено',       'count' => $counts['replied']],
    ] as $st => $meta): ?>
    <a href="<?= Url::to(['feedback/index', 'status' => $st]) ?>"
       class="admin-btn admin-btn-sm <?= $activeStatus === $st ? 'admin-btn-primary' : 'admin-btn-outline' ?>">
        <?= $meta['label'] ?>
        <?php if ($meta['count']): ?>
        <span class="admin-badge <?= $activeStatus === $st ? '' : ($st === 'new' ? 'admin-badge-danger' : 'admin-badge-secondary') ?>"
              style="margin-left:4px;font-size:10px"><?= $meta['count'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-envelope-heart"></i> Сообщения директору</h2>
    </div>

    <?php if (empty($feedbacks)): ?>
    <div class="admin-card-body" style="text-align:center;padding:3rem">
        <i class="bi bi-envelope-heart" style="font-size:3rem;color:var(--admin-text-muted);display:block;margin-bottom:1rem"></i>
        <p style="color:var(--admin-text-secondary);margin:0">Сообщений нет</p>
    </div>
    <?php else: ?>
    <div class="admin-card-body" style="padding:0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:160px">Дата</th>
                    <th>Покупатель</th>
                    <th style="width:100px;text-align:center">Оценка</th>
                    <th>Сообщение</th>
                    <th style="width:100px">Статус</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $fb): ?>
                <tr id="fb-row-<?= $fb->id ?>">
                    <td style="color:var(--admin-text-secondary);font-size:0.85rem;white-space:nowrap">
                        <?= Yii::$app->formatter->asDatetime($fb->created_at, 'medium') ?>
                    </td>
                    <td>
                        <div style="font-weight:600"><?= Html::encode($fb->customer_name ?: 'Аноним') ?></div>
                        <?php if ($fb->customer_email): ?>
                            <div style="font-size:0.8rem;color:var(--admin-text-secondary)"><?= Html::encode($fb->customer_email) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <div style="color:#f59e0b;font-size:1rem;letter-spacing:-1px">
                            <?= str_repeat('★', (int)$fb->rating) ?><?= str_repeat('☆', 5 - (int)$fb->rating) ?>
                        </div>
                    </td>
                    <td style="max-width:340px">
                        <p style="margin:0;line-height:1.5"><?= Html::encode($fb->message) ?></p>
                        <?php if ($fb->reply_text): ?>
                        <div style="margin-top:6px;padding:6px 10px;background:rgba(37,99,235,.07);border-left:3px solid #2563eb;font-size:.8rem;color:#1e40af;border-radius:0 4px 4px 0">
                            <strong>Ответ:</strong> <?= Html::encode($fb->reply_text) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $statusMap = [
                            'new'     => ['admin-badge-danger',   'Новое'],
                            'read'    => ['admin-badge-secondary', 'Прочитано'],
                            'replied' => ['admin-badge-success',   'Отвечено'],
                        ];
                        [$cls, $lbl] = $statusMap[$fb->status ?? 'new'] ?? ['admin-badge-secondary', 'Прочитано'];
                        ?>
                        <span class="admin-badge <?= $cls ?>"><?= $lbl ?></span>
                    </td>
                    <td style="white-space:nowrap">
                        <?php if ($fb->status !== 'replied'): ?>
                        <button class="admin-btn admin-btn-xs admin-btn-primary"
                                style="margin-bottom:4px"
                                onclick="openReply(<?= $fb->id ?>, '<?= Html::encode(addslashes($fb->customer_name ?: 'Аноним')) ?>')">
                            <i class="bi bi-reply"></i> Ответить
                        </button><br>
                        <?php endif; ?>
                        <a href="<?= Url::to(['feedback/delete', 'id' => $fb->id]) ?>"
                           class="admin-btn admin-btn-xs admin-btn-secondary"
                           onclick="return confirm('Удалить сообщение?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Reply modal -->
<div id="reply-modal" style="display:none;position:fixed;top:0;left:0;z-index:1055;width:100%;height:100%;background:rgba(0,0,0,.45)">
    <div style="max-width:500px;margin:100px auto;background:var(--admin-surface,#fff);border-radius:10px;padding:24px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <h3 style="margin:0 0 14px;font-size:16px">Ответить: <span id="reply-name"></span></h3>
        <textarea id="reply-text" class="admin-form-input" rows="5"
                  style="width:100%;box-sizing:border-box" placeholder="Текст ответа..."></textarea>
        <div style="display:flex;gap:8px;margin-top:12px;justify-content:flex-end">
            <button class="admin-btn admin-btn-secondary" onclick="closeReply()">Отмена</button>
            <button class="admin-btn admin-btn-primary" onclick="sendReply()">
                <i class="bi bi-send"></i> Отправить
            </button>
        </div>
        <div id="reply-result" style="margin-top:8px;font-size:12px"></div>
    </div>
</div>

<script>
(function(){
var _replyId = null;
var csrf = '<?= $csrf ?>';

window.openReply = function(id, name) {
    _replyId = id;
    document.getElementById('reply-name').textContent = name;
    document.getElementById('reply-text').value = '';
    document.getElementById('reply-result').textContent = '';
    document.getElementById('reply-modal').style.display = '';
    setTimeout(function(){ document.getElementById('reply-text').focus(); }, 50);
};
window.closeReply = function() {
    document.getElementById('reply-modal').style.display = 'none';
    _replyId = null;
};
window.sendReply = function() {
    var text = document.getElementById('reply-text').value.trim();
    var res  = document.getElementById('reply-result');
    if (!text) { res.style.color = '#991b1b'; res.textContent = 'Введите текст ответа'; return; }
    res.style.color = '#6b7280'; res.textContent = 'Отправка...';
    fetch('/admin/feedback/reply', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + _replyId + '&reply_text=' + encodeURIComponent(text) + '&_csrf=' + encodeURIComponent(csrf)
    }).then(function(r){ return r.json(); }).then(function(d){
        res.style.color = d.success ? '#065f46' : '#991b1b';
        res.textContent = (d.success ? '✓ ' : '✗ ') + (d.message || '');
        if (d.success) setTimeout(function(){ location.reload(); }, 800);
    }).catch(function(){ res.style.color = '#991b1b'; res.textContent = '✗ Ошибка сети'; });
};
})();
</script>
