<?php
/**
 * Общий сайдбар личного кабинета.
 * Единственный источник истины — все account-страницы рендерят этот partial.
 *
 * @var yii\web\View $this
 * @var app\models\Customer $customer
 * @var string $activePage — текущая страница ('profile'|'orders'|'loyalty'|'favorites'|'settings')
 * @var app\models\Order[] $orders (optional) — заказы для статистики
 */

use yii\helpers\Html;
use yii\helpers\Url;

$activePage = $activePage ?? '';
$orders = $orders ?? [];

$menuItems = [
    ['route' => '/account/profile',    'icon' => 'bi-person', 'label' => 'Профиль',           'key' => 'profile'],
    ['route' => '/account/orders',     'icon' => 'bi-bag',    'label' => 'Мои заказы',         'key' => 'orders'],
    ['route' => '/account/loyalty',    'icon' => 'bi-gem',    'label' => 'Баллы лояльности',   'key' => 'loyalty'],
    ['route' => '/catalog/favorites',  'icon' => 'bi-heart',  'label' => 'Избранное',          'key' => 'favorites'],
    ['route' => '/account/settings',   'icon' => 'bi-gear',   'label' => 'Настройки',          'key' => 'settings'],
];
?>
<aside class="account-sidebar">
    <div class="sidebar-card">
        <div class="user-info">
            <div class="user-avatar">
                <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
            </div>
            <div class="user-name"><?= Html::encode($customer->fullName) ?></div>
            <div class="user-email"><?= Html::encode($customer->email) ?></div>

            <?php if (!empty($orders)): ?>
            <div class="user-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($orders) ?></div>
                    <div class="stat-label">Заказов</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent ?? 0, 'BYN') ?></div>
                    <div class="stat-label">Потрачено</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <ul class="account-menu">
            <?php foreach ($menuItems as $item): ?>
            <li>
                <a href="<?= Url::to([$item['route']]) ?>"<?= $activePage === $item['key'] ? ' class="active"' : '' ?>>
                    <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?= Html::beginForm(['/account/logout'], 'post') ?>
            <button type="submit" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Выйти
            </button>
        <?= Html::endForm() ?>
    </div>
</aside>
