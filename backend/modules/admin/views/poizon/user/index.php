<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;

$request = Yii::$app->request;
$filterSearch = trim((string)$request->get('search', ''));
$filterRole = $request->get('role', '');
$filterStatus = $request->get('status', '');

$totalUsers = $dataProvider->getTotalCount();
$activeUsers = User::find()->where(['status' => User::STATUS_ACTIVE])->count();
$inactiveUsers = User::find()->where(['status' => User::STATUS_INACTIVE])->count();
$roleCounts = [
    User::ROLE_ADMIN => User::find()->where(['role' => User::ROLE_ADMIN])->count(),
    User::ROLE_MANAGER => User::find()->where(['role' => User::ROLE_MANAGER])->count(),
    User::ROLE_LOGIST => User::find()->where(['role' => User::ROLE_LOGIST])->count(),
];

$activeFilters = array_filter([
    'search' => $filterSearch,
    'role' => $filterRole,
    'status' => $filterStatus,
], static fn($value) => $value !== null && $value !== '');

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

?>

<div class="admin-page-container users-admin">
    <div class="admin-page-shell">
        <div class="admin-page-header">
            <div class="admin-page-header-content">
                <div class="admin-page-eyebrow">Команда</div>
                <h1 class="admin-page-title">
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="admin-page-subtitle">
                    Управляйте доступом, ролями и статусами сотрудников компании.
                </p>
            </div>
            <div class="admin-page-actions">
                <button class="admin-btn admin-btn--outline" id="usersFiltersToggle">
                    <i class="bi bi-funnel"></i>
                    Фильтры
                </button>
                <button class="admin-btn admin-btn--ghost" onclick="bulkExport()">
                    <i class="bi bi-download"></i>
                    Экспорт
                </button>
                <a href="<?= Url::to(['/admin/user/create']) ?>" class="admin-btn admin-btn--primary">
                    <i class="bi bi-person-plus"></i>
                    Новый пользователь
                </a>
            </div>
        </div>

        <div class="admin-grid admin-grid--4 admin-mb-6">
            <div class="admin-metric-card">
                <span class="admin-metric-label">Всего пользователей</span>
                <span class="admin-metric-value"><?= Html::encode($totalUsers) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-people"></i>
                    Полный штат
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Активные</span>
                <span class="admin-metric-value"><?= Html::encode($activeUsers) ?></span>
                <span class="admin-metric-change admin-metric-change--up">
                    <i class="bi bi-check-circle"></i>
                    <?= Html::encode($inactiveUsers) ?> вне системы
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Администраторы</span>
                <span class="admin-metric-value"><?= Html::encode($roleCounts[User::ROLE_ADMIN]) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-shield-lock"></i>
                    Полные права
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Менеджеры · Логисты</span>
                <span class="admin-metric-value"><?= Html::encode($roleCounts[User::ROLE_MANAGER] + $roleCounts[User::ROLE_LOGIST]) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-diagram-3"></i>
                    <?= Html::encode($roleCounts[User::ROLE_MANAGER]) ?> · <?= Html::encode($roleCounts[User::ROLE_LOGIST]) ?>
                </span>
            </div>
        </div>

        <div class="admin-card admin-mb-6" id="usersFiltersCard" data-open="false">
            <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                <div>
                    <h2 class="admin-card-title">
                        <i class="bi bi-funnel"></i>
                        Фильтр списка
                    </h2>
                    <p class="admin-card-subtitle">
                        Быстро находите людей по роли, статусу или имени.
                    </p>
                </div>
                <?php if (!empty($activeFilters)): ?>
                    <div class="admin-flex admin-gap-2 admin-flex--wrap">
                        <?php foreach ($activeFilters as $key => $value): ?>
                            <span class="admin-badge admin-badge--neutral">
                                <?= Html::encode($key) ?>: <strong><?= Html::encode($value) ?></strong>
                            </span>
                        <?php endforeach; ?>
                        <a href="<?= Url::to(['/admin/user/index']) ?>" class="admin-btn admin-btn--ghost admin-btn--sm">
                            Сбросить
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <?php $form = Html::beginForm(['/admin/user/index'], 'get', ['class' => 'admin-grid admin-grid--3 admin-gap-4']); ?>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Поиск</label>
                        <?= Html::textInput('search', $filterSearch, [
                            'class' => 'admin-form-input',
                            'placeholder' => 'Имя, email или ID',
                        ]) ?>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Роль</label>
                        <?= Html::dropDownList('role', $filterRole, [
                            '' => 'Все роли',
                            User::ROLE_ADMIN => 'Администратор',
                            User::ROLE_MANAGER => 'Менеджер',
                            User::ROLE_LOGIST => 'Логист',
                        ], ['class' => 'admin-form-select']) ?>
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Статус</label>
                        <?= Html::dropDownList('status', $filterStatus, [
                            '' => 'Все статусы',
                            User::STATUS_ACTIVE => 'Активен',
                            User::STATUS_INACTIVE => 'Неактивен',
                        ], ['class' => 'admin-form-select']) ?>
                    </div>
                    <div class="admin-grid admin-grid--2 admin-gap-4" style="grid-column: 1 / -1;">
                        <button type="submit" class="admin-btn admin-btn--accent">
                            <i class="bi bi-search"></i>
                            Применить фильтры
                        </button>
                        <a href="<?= Url::to(['/admin/user/index']) ?>" class="admin-btn admin-btn--ghost">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Очистить всё
                        </a>
                    </div>
                <?php Html::endForm(); ?>
            </div>
        </div>

        <div class="admin-card admin-mb-6">
            <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                <div>
                    <h2 class="admin-card-title">
                        <i class="bi bi-card-checklist"></i>
                        Реестр пользователей
                    </h2>
                    <p class="admin-card-subtitle">
                        <?= Html::encode($totalUsers) ?> записей · колонку можно пролистать горизонтально.
                    </p>
                </div>
                <div class="admin-flex admin-gap-2">
                    <span class="admin-badge admin-badge--info">
                        <i class="bi bi-activity"></i>
                        Активных: <?= Html::encode($activeUsers) ?>
                    </span>
                    <span class="admin-badge admin-badge--warning">
                        <i class="bi bi-pause-circle"></i>
                        Неактивных: <?= Html::encode($inactiveUsers) ?>
                    </span>
                </div>
            </div>

            <div class="admin-card-body admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dataProvider->getModels() as $user): ?>
                            <tr>
                                <td>#<?= Html::encode($user->id) ?></td>
                                <td>
                                    <div class="admin-flex admin-gap-3 admin-flex--center">
                                        <div class="admin-avatar admin-avatar--md" style="background: var(--admin-surface-muted);">
                                            <?= Html::encode(mb_strtoupper(mb_substr($user->username, 0, 2))) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= Html::encode($user->username) ?></div>
                                            <div class="text-muted small"><?= Html::encode($user->email) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php $role = $roleMeta[$user->role] ?? null; ?>
                                    <?php if ($role): ?>
                                        <span class="<?= $role['badge'] ?>">
                                            <i class="bi bi-<?= $role['icon'] ?>"></i>
                                            <?= Html::encode($role['label']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $status = $statusMeta[$user->status] ?? $statusMeta[User::STATUS_INACTIVE]; ?>
                                    <span class="<?= $status['class'] ?>">
                                        <?= Html::encode($status['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= Html::encode(Yii::$app->formatter->asDate($user->created_at, 'php:d.m.Y')) ?>
                                </td>
                                <td class="text-end">
                                    <div class="admin-flex admin-gap-2 admin-flex--wrap" style="justify-content: flex-end;">
                                        <button class="admin-btn admin-btn--ghost admin-btn--icon"
                                            title="Редактировать"
                                            onclick="editUser(<?= (int)$user->id ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="admin-btn admin-btn--ghost admin-btn--icon"
                                            title="Переключить статус"
                                            onclick="toggleUserStatus(<?= (int)$user->id ?>)">
                                            <i class="bi bi-<?= $user->status == User::STATUS_ACTIVE ? 'pause-circle' : 'play-circle' ?>"></i>
                                        </button>
                                        <?php if ($user->id != Yii::$app->user->id): ?>
                                            <button class="admin-btn admin-btn--danger admin-btn--icon"
                                                title="Удалить"
                                                onclick="deleteUser(<?= (int)$user->id ?>, '<?= Html::encode($user->username) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="admin-btn admin-btn--ghost admin-btn--icon" title="Нельзя удалить себя" disabled>
                                                <i class="bi bi-shield-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($dataProvider->getTotalCount() === 0): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="admin-empty-state">
                                        <div class="admin-empty-state-icon">🫥</div>
                                        <div class="admin-empty-state-title">Нет пользователей</div>
                                        <p class="admin-empty-state-text">
                                            Попробуйте изменить параметры фильтра или создайте первого пользователя.
                                        </p>
                                        <a href="<?= Url::to(['/admin/user/create']) ?>" class="admin-btn admin-btn--primary">
                                            <i class="bi bi-person-plus"></i>
                                            Добавить сотрудника
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-card-footer admin-flex admin-flex--between admin-flex--wrap">
                <div class="admin-text-muted">
                    Показано <?= Html::encode($dataProvider->getCount()) ?> из <?= Html::encode($totalUsers) ?> пользователей
                </div>
                <div class="admin-flex admin-gap-2">
                    <?php
                    $pagination = $dataProvider->getPagination();
                    $currentPage = $pagination->getPage() + 1;
                    $totalPages = $pagination->getPageCount();
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>
                    <?php if ($currentPage > 1): ?>
                        <a class="admin-btn admin-btn--ghost admin-btn--icon" href="<?= Url::current(['page' => $currentPage - 1]) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($startPage > 1): ?>
                        <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::current(['page' => 1]) ?>">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="admin-btn admin-btn--ghost admin-btn--sm" style="pointer-events: none;">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a class="admin-btn admin-btn--sm <?= $i === $currentPage ? 'admin-btn--accent' : 'admin-btn--ghost' ?>"
                           href="<?= Url::current(['page' => $i]) ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="admin-btn admin-btn--ghost admin-btn--sm" style="pointer-events: none;">…</span>
                        <?php endif; ?>
                        <a class="admin-btn admin-btn--ghost admin-btn--sm"
                           href="<?= Url::current(['page' => $totalPages]) ?>">
                            <?= $totalPages ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="admin-btn admin-btn--ghost admin-btn--icon" href="<?= Url::current(['page' => $currentPage + 1]) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="admin-grid admin-grid--3">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-shield-lock"></i>
                        Администраторы
                    </h3>
                </div>
                <div class="admin-card-body">
                    Управляют системой, тарифами, правами сотрудников. Рекомендуется сохранять минимум двух администраторов.
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-briefcase"></i>
                        Менеджеры
                    </h3>
                </div>
                <div class="admin-card-body">
                    Работают с заказами и клиентами, не видят системные настройки. Используйте их для операционного контроля.
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="bi bi-truck"></i>
                        Логисты
                    </h3>
                </div>
                <div class="admin-card-body">
                    Доступ только к назначенным заказам и статусам доставки. Отлично подходит для внешних подрядчиков.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filtersCard = document.getElementById('usersFiltersCard');
    const toggleBtn = document.getElementById('usersFiltersToggle');

    const toggleFilters = () => {
        const isOpen = filtersCard.getAttribute('data-open') === 'true';
        filtersCard.setAttribute('data-open', String(!isOpen));
        filtersCard.classList.toggle('is-collapsed', isOpen);
        filtersCard.querySelector('.admin-card-body').style.display = isOpen ? 'none' : 'block';
    };

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleFilters);
    }

    // Скрываем тело фильтра, если нет активных критериев
    if (Object.keys(<?= json_encode($activeFilters) ?>).length === 0) {
        filtersCard.querySelector('.admin-card-body').style.display = 'none';
    } else {
        filtersCard.setAttribute('data-open', 'true');
    }
});

function editUser(userId) {
    window.location.href = `/admin/user/edit?id=${userId}`;
}

function toggleUserStatus(userId) {
    if (confirm('Изменить статус пользователя?')) {
        fetch(`/admin/user/${userId}/toggle`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(response => response.json())
            .then(data => data.success ? window.location.reload() : alert(data.message || 'Ошибка при изменении статуса'))
            .catch(() => alert('Ошибка сети'));
    }
}

function deleteUser(userId, username) {
    if (confirm(`Удалить пользователя «${username}»? Это действие нельзя отменить.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/user/delete?id=${userId}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf';
            csrfInput.value = csrfToken.content;
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }
}

function bulkExport() {
    window.location.href = '/admin/user/export';
}
</script>
