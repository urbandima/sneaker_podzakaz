<?php

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\ChangePasswordForm $model */
/** @var app\models\User $user */

$this->title = 'Профиль пользователя';
$this->params['breadcrumbs'][] = $this->title;

$roleMeta = [
    User::ROLE_ADMIN => ['label' => 'Администратор', 'icon' => 'shield-lock', 'badge' => 'admin-badge admin-badge--danger'],
    User::ROLE_MANAGER => ['label' => 'Менеджер', 'icon' => 'briefcase', 'badge' => 'admin-badge admin-badge--info'],
    User::ROLE_LOGIST => ['label' => 'Логист', 'icon' => 'truck', 'badge' => 'admin-badge admin-badge--success'],
];

$statusMeta = [
    User::STATUS_ACTIVE => ['label' => 'Активен', 'class' => 'admin-status admin-status--active'],
    User::STATUS_INACTIVE => ['label' => 'Неактивен', 'class' => 'admin-status admin-status--inactive'],
    User::STATUS_DELETED => ['label' => 'Удалён', 'class' => 'admin-status admin-status--error'],
];

$form = null;
$formatter = Yii::$app->formatter;
?>

<div class="admin-page-container admin-profile">
    <div class="admin-page-shell">
        <div class="admin-page-header">
            <div class="admin-page-header-content">
                <div class="admin-page-eyebrow">Мой аккаунт</div>
                <h1 class="admin-page-title"><?= Html::encode($user->username) ?></h1>
                <p class="admin-page-subtitle">
                    Управляйте личными данными, ролями и безопасностью входа в едином CRM-интерфейсе.
                </p>
                <div class="admin-flex admin-gap-2 admin-flex--wrap admin-mt-4">
                    <?php if (isset($roleMeta[$user->role])): ?>
                        <span class="<?= $roleMeta[$user->role]['badge'] ?>">
                            <i class="bi bi-<?= $roleMeta[$user->role]['icon'] ?>"></i>
                            <?= Html::encode($roleMeta[$user->role]['label']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="<?= $statusMeta[$user->status]['class'] ?? 'admin-status admin-status--inactive' ?>">
                        <?= Html::encode($statusMeta[$user->status]['label'] ?? 'Неизвестно') ?>
                    </span>
                </div>
            </div>
            <div class="admin-page-actions">
                <button class="admin-btn admin-btn--ghost" type="button" onclick="location.reload()">
                    <i class="bi bi-arrow-repeat"></i>
                    Обновить данные
                </button>
                <a href="<?= Html::encode(Yii::$app->homeUrl) ?>" class="admin-btn admin-btn--outline">
                    <i class="bi bi-grid"></i>
                    В админку
                </a>
                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) .
                    Html::submitButton('<i class="bi bi-box-arrow-right"></i> Выйти', [
                        'class' => 'admin-btn admin-btn--danger',
                        'title' => 'Выйти из системы'
                    ]) .
                    Html::endForm(); ?>
            </div>
        </div>

        <div class="admin-grid admin-grid--4 admin-mb-6">
            <div class="admin-metric-card">
                <span class="admin-metric-label">ID в системе</span>
                <span class="admin-metric-value">#<?= Html::encode($user->id) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-hash"></i>
                    Уникальный идентификатор
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Статус</span>
                <span class="admin-metric-value"><?= Html::encode($statusMeta[$user->status]['label'] ?? 'Нет данных') ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-activity"></i>
                    Активность аккаунта
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Дата создания</span>
                <span class="admin-metric-value">
                    <?= Html::encode($formatter->asDate($user->created_at, 'php:d.m.Y')) ?>
                </span>
                <span class="admin-metric-change">
                    <i class="bi bi-calendar"></i>
                    <?= Html::encode($formatter->asTime($user->created_at, 'php:H:i')) ?>
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Email</span>
                <span class="admin-metric-value" style="font-size: 1.25rem;">
                    <?= Html::encode($user->email ?: '—') ?>
                </span>
                <span class="admin-metric-change">
                    <i class="bi bi-envelope"></i>
                    Основной контакт
                </span>
            </div>
        </div>

        <div class="admin-grid admin-grid--sidebar admin-mb-6">
            <div class="admin-card">
                <div class="admin-card-header admin-flex admin-flex--between">
                    <div>
                        <h2 class="admin-card-title">
                            <i class="bi bi-person-vcard"></i>
                            Данные профиля
                        </h2>
                        <p class="admin-card-subtitle">
                            Системные параметры и права доступа.
                        </p>
                    </div>
                    <span class="admin-badge admin-badge--neutral">
                        Последнее изменение: <?= Html::encode($formatter->asDatetime($user->updated_at)) ?>
                    </span>
                </div>
                <div class="admin-card-body">
                    <div class="admin-grid admin-grid--2 admin-gap-6">
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Логин</div>
                            <div class="fw-semibold"><?= Html::encode($user->username) ?></div>
                        </div>
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Email</div>
                            <div class="fw-semibold"><?= Html::encode($user->email ?: 'Не задан') ?></div>
                        </div>
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Роль</div>
                            <div>
                                <?php if (isset($roleMeta[$user->role])): ?>
                                    <span class="<?= $roleMeta[$user->role]['badge'] ?>">
                                        <i class="bi bi-<?= $roleMeta[$user->role]['icon'] ?>"></i>
                                        <?= Html::encode($roleMeta[$user->role]['label']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge--neutral"><?= Html::encode($user->role) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Статус</div>
                            <div class="<?= $statusMeta[$user->status]['class'] ?? '' ?>">
                                <?= Html::encode($statusMeta[$user->status]['label'] ?? 'Неизвестно') ?>
                            </div>
                        </div>
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Дата создания</div>
                            <div><?= Html::encode($formatter->asDatetime($user->created_at)) ?></div>
                        </div>
                        <div>
                            <div class="admin-text-muted text-uppercase small mb-2">Последнее обновление</div>
                            <div><?= Html::encode($formatter->asDatetime($user->updated_at)) ?></div>
                        </div>
                    </div>
                    <div class="admin-mt-6">
                        <div class="admin-card-subtitle mb-2">Быстрые действия</div>
                        <div class="admin-flex admin-gap-2 admin-flex--wrap">
                            <button class="admin-btn admin-btn--ghost" type="button" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i>
                                Синхронизировать
                            </button>
                            <a class="admin-btn admin-btn--outline" href="<?= Html::encode(Url::to(['/admin/help'])) ?>">
                                <i class="bi bi-question-circle"></i>
                                Центр поддержки
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header admin-flex admin-flex--between">
                    <div>
                        <h2 class="admin-card-title">
                            <i class="bi bi-key"></i>
                            Смена пароля
                        </h2>
                        <p class="admin-card-subtitle">
                            Используйте сложные пароли и обновляйте их регулярно.
                        </p>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'change-password-form',
                        'options' => ['class' => 'admin-form admin-grid admin-grid--1 admin-gap-4'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'admin-form-label'],
                            'errorOptions' => ['class' => 'text-danger small mt-1'],
                            'inputOptions' => ['class' => 'admin-form-input'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'old_password')->passwordInput([
                        'placeholder' => 'Введите текущий пароль',
                        'autocomplete' => 'current-password',
                    ]) ?>

                    <?= $form->field($model, 'new_password')->passwordInput([
                        'placeholder' => 'Минимум 6 символов',
                        'autocomplete' => 'new-password',
                    ]) ?>

                    <?= $form->field($model, 'new_password_repeat')->passwordInput([
                        'placeholder' => 'Повторите новый пароль',
                        'autocomplete' => 'new-password',
                    ]) ?>

                    <div class="admin-badge admin-badge--info" style="justify-content: flex-start;">
                        <i class="bi bi-shield-check"></i>
                        После смены пароля сессия останется активной.
                    </div>

                    <div class="admin-grid admin-grid--2 admin-gap-4">
                        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сменить пароль', [
                            'class' => 'admin-btn admin-btn--accent',
                            'name' => 'change-password-button'
                        ]) ?>
                        <button type="reset" class="admin-btn admin-btn--ghost">
                            <i class="bi bi-eraser"></i>
                            Очистить поля
                        </button>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="admin-grid admin-grid--3">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-shield-lock"></i>
                        Безопасность входа
                    </h3>
                </div>
                <div class="admin-card-body admin-text-muted">
                    <ul class="list-unstyled admin-flex admin-flex--col admin-gap-2">
                        <li><i class="bi bi-check2-circle text-success"></i> SSL-защищённые соединения</li>
                        <li><i class="bi bi-check2-circle text-success"></i> Контроль активности в журналах</li>
                        <li><i class="bi bi-check2-circle text-success"></i> Уведомления о подозрительных входах</li>
                    </ul>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-lightning-charge"></i>
                        Советы
                    </h3>
                </div>
                <div class="admin-card-body admin-text-muted">
                    <ul class="list-unstyled admin-flex admin-flex--col admin-gap-2">
                        <li>Используйте менеджер паролей и 2FA.</li>
                        <li>Обновляйте пароль каждые 90 дней.</li>
                        <li>Не делитесь учётной записью с коллегами.</li>
                    </ul>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-chat-dots"></i>
                        Поддержка
                    </h3>
                </div>
                <div class="admin-card-body">
                    <p class="admin-text-muted">
                        Нужна помощь? Команда реагирует в течение 15 минут в рабочее время.
                    </p>
                    <div class="admin-flex admin-gap-2">
                        <a href="mailto:support@sneaker-head.by" class="admin-btn admin-btn--ghost">
                            <i class="bi bi-envelope-open"></i>
                            Написать
                        </a>
                        <a href="<?= Html::encode(Url::to(['/admin/help/faq'])) ?>" class="admin-btn admin-btn--outline">
                            <i class="bi bi-journal-text"></i>
                            FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
