<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Маркетинговые кампании';

// Демо данные для кампаний
$campaigns = [
    [
        'id' => 1,
        'name' => 'Весенняя распродажа 2026',
        'type' => 'email',
        'status' => 'active',
        'recipients' => 15420,
        'opened' => 4230,
        'clicked' => 1250,
        'created_at' => '2026-04-01',
    ],
    [
        'id' => 2,
        'name' => 'Новинки апреля',
        'type' => 'sms',
        'status' => 'completed',
        'recipients' => 8900,
        'opened' => 8500,
        'clicked' => 2100,
        'created_at' => '2026-03-15',
    ],
    [
        'id' => 3,
        'name' => 'Скидки для постоянных клиентов',
        'type' => 'promo',
        'status' => 'draft',
        'recipients' => 0,
        'opened' => 0,
        'clicked' => 0,
        'created_at' => '2026-04-10',
    ],
    [
        'id' => 4,
        'name' => 'Re-активация спящих клиентов',
        'type' => 'email',
        'status' => 'scheduled',
        'recipients' => 5200,
        'opened' => 0,
        'clicked' => 0,
        'created_at' => '2026-04-12',
    ],
];

$typeLabels = [
    'email' => ['label' => 'Email', 'icon' => 'bi-envelope', 'color' => '#3b82f6'],
    'sms' => ['label' => 'SMS', 'icon' => 'bi-chat-dots', 'color' => '#10b981'],
    'promo' => ['label' => 'Промо', 'icon' => 'bi-tag', 'color' => '#f59e0b'],
];

$statusLabels = [
    'active' => ['label' => 'Активна', 'class' => 'admin-badge-success'],
    'completed' => ['label' => 'Завершена', 'class' => 'admin-badge-secondary'],
    'draft' => ['label' => 'Черновик', 'class' => 'admin-badge-warning'],
    'scheduled' => ['label' => 'Запланирована', 'class' => 'admin-badge-info'],
];
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-circle"></i> Новая кампания', ['#'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm'])
];
?>

<!-- Статистика -->
<div class="admin-stats" style="margin-bottom: 24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-envelope-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value">24</div>
            <div class="admin-stat-label">Всего кампаний</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value">18</div>
            <div class="admin-stat-label">Завершено</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-clock-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value">4</div>
            <div class="admin-stat-label">Активных</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-eye-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value">32.5%</div>
            <div class="admin-stat-label">Средний CTR</div>
        </div>
    </div>
</div>

<!-- Таблица кампаний -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Все кампании</h2>
        <div class="admin-card-actions">
            <button class="admin-btn admin-btn-secondary admin-btn-sm">
                <i class="bi bi-funnel"></i> Фильтр
            </button>
            <button class="admin-btn admin-btn-secondary admin-btn-sm">
                <i class="bi bi-download"></i> Экспорт
            </button>
        </div>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Название кампании</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th style="text-align: center;">Получатели</th>
                    <th style="text-align: center;">Открытия</th>
                    <th style="text-align: center;">Клики</th>
                    <th style="text-align: center;">CTR</th>
                    <th>Дата создания</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                <?php
                    $type = $typeLabels[$campaign['type']] ?? $typeLabels['email'];
                    $status = $statusLabels[$campaign['status']] ?? $statusLabels['draft'];
                    $ctr = $campaign['recipients'] > 0 ? round(($campaign['clicked'] / $campaign['recipients']) * 100, 1) : 0;
                ?>
                <tr>
                    <td>
                        <i class="bi <?= $type['icon'] ?>" style="color: <?= $type['color'] ?>; font-size: 18px;"></i>
                    </td>
                    <td>
                        <strong><?= Html::encode($campaign['name']) ?></strong>
                    </td>
                    <td>
                        <span class="admin-badge" style="background: <?= $type['color'] ?>20; color: <?= $type['color'] ?>;">
                            <?= $type['label'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="admin-badge <?= $status['class'] ?>">
                            <?= $status['label'] ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?= number_format($campaign['recipients'], 0, '.', ' ') ?>
                    </td>
                    <td style="text-align: center;">
                        <?= $campaign['opened'] > 0 ? number_format($campaign['opened'], 0, '.', ' ') : '-' ?>
                    </td>
                    <td style="text-align: center;">
                        <?= $campaign['clicked'] > 0 ? number_format($campaign['clicked'], 0, '.', ' ') : '-' ?>
                    </td>
                    <td style="text-align: center;">
                        <?= $ctr > 0 ? $ctr . '%' : '-' ?>
                    </td>
                    <td>
                        <?= date('d.m.Y', strtotime($campaign['created_at'])) ?>
                    </td>
                    <td style="text-align: right;">
                        <div class="admin-actions">
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Статистика">
                                <i class="bi bi-graph-up"></i>
                            </button>
                            <?php if ($campaign['status'] === 'draft'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-primary" title="Запустить">
                                <i class="bi bi-play-fill"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Типы кампаний -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 24px;">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-envelope" style="color: #3b82f6;"></i> Email-рассылки</h3>
        </div>
        <div class="admin-card-body">
            <p style="color: var(--admin-text-secondary); margin-bottom: 16px;">
                Создавайте красивые email-письма с персонализацией для ваших клиентов.
            </p>
            <ul style="color: var(--admin-text-secondary); font-size: 14px; margin: 0; padding-left: 20px;">
                <li>Шаблоны писем</li>
                <li>Автоматические рассылки</li>
                <li>A/B тестирование</li>
            </ul>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-chat-dots" style="color: #10b981;"></i> SMS-кампании</h3>
        </div>
        <div class="admin-card-body">
            <p style="color: var(--admin-text-secondary); margin-bottom: 16px;">
                Отправляйте SMS-уведомления для быстрой доставки информации.
            </p>
            <ul style="color: var(--admin-text-secondary); font-size: 14px; margin: 0; padding-left: 20px;">
                <li>Мгновенная доставка</li>
                <li>Короткие ссылки</li>
                <li>Отслеживание доставки</li>
            </ul>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-tag" style="color: #f59e0b;"></i> Промо-акции</h3>
        </div>
        <div class="admin-card-body">
            <p style="color: var(--admin-text-secondary); margin-bottom: 16px;">
                Создавайте промо-коды и специальные предложения для клиентов.
            </p>
            <ul style="color: var(--admin-text-secondary); font-size: 14px; margin: 0; padding-left: 20px;">
                <li>Промо-коды</li>
                <li>Скидки и акции</li>
                <li>Лимитированные предложения</li>
            </ul>
        </div>
    </div>
</div>
