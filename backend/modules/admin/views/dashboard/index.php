<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Панель управления';
$user = Yii::$app->user->identity;
?>

<!-- Admin Header -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
        <p style="margin: 0; color: var(--admin-text-secondary);">
            Привет, <?= Html::encode($user->username) ?>! Добро пожаловать в админ-панель.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Новый заказ
        </a>
        <button class="admin-btn admin-btn-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-repeat"></i>
            Обновить
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number">1,234</p>
        <p class="admin-stat-label">Всего заказов</p>
        <div style="margin-top: 0.5rem;">
            <span class="admin-badge admin-badge-success">+12% сегодня</span>
        </div>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-success);">
        <p class="admin-stat-number">45.2K BYN</p>
        <p class="admin-stat-label">Выручка</p>
        <div style="margin-top: 0.5rem;">
            <span class="admin-badge admin-badge-success">+8% за неделю</span>
        </div>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-warning);">
        <p class="admin-stat-number">856</p>
        <p class="admin-stat-label">Товары</p>
        <div style="margin-top: 0.5rem;">
            <span class="admin-badge admin-badge-warning">42 в наличии</span>
        </div>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-info);">
        <p class="admin-stat-number">24</p>
        <p class="admin-stat-label">Клиенты</p>
        <div style="margin-top: 0.5rem;">
            <span class="admin-badge admin-badge-info">3 новых</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-lightning"></i>
        Быстрые действия
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
        <a href="<?= Url::to(['/admin/product']) ?>" class="admin-btn admin-btn-secondary" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            <i class="bi bi-box" style="font-size: 2rem;"></i>
            <span>Товары</span>
        </a>
        
        <a href="<?= Url::to(['/admin/customer']) ?>" class="admin-btn admin-btn-secondary" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            <i class="bi bi-people" style="font-size: 2rem;"></i>
            <span>Клиенты</span>
        </a>
        
        <a href="<?= Url::to(['/admin/coupon']) ?>" class="admin-btn admin-btn-secondary" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            <i class="bi bi-ticket-perforated" style="font-size: 2rem;"></i>
            <span>Купоны</span>
        </a>
        
        <a href="<?= Url::to(['/admin/statistics']) ?>" class="admin-btn admin-btn-secondary" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
            <span>Статистика</span>
        </a>
    </div>
</div>

<!-- Recent Orders -->
<div class="admin-card">
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="admin-card-title">
            <i class="bi bi-cart3"></i>
            Последние заказы
        </h2>
        <a href="<?= Url::to(['/admin/order']) ?>" class="admin-btn admin-btn-secondary">
            Все заказы
        </a>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Товары</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#1234</td>
                    <td>Иван Петров</td>
                    <td>Nike Air Max 90</td>
                    <td>320 BYN</td>
                    <td><span class="admin-badge admin-badge-success">Оплачен</span></td>
                    <td>24.03.2026</td>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => 1234]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>#1233</td>
                    <td>Мария Сидорова</td>
                    <td>Adidas Ultraboost</td>
                    <td>280 BYN</td>
                    <td><span class="admin-badge admin-badge-warning">В обработке</span></td>
                    <td>24.03.2026</td>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => 1233]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>#1232</td>
                    <td>Алексей Иванов</td>
                    <td>Jordan 1 Retro</td>
                    <td>450 BYN</td>
                    <td><span class="admin-badge admin-badge-info">Новый</span></td>
                    <td>23.03.2026</td>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => 1232]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- System Info -->
<div class="admin-stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="admin-card" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--admin-text-secondary);">Система</h3>
        <p style="margin: 0; font-size: 1.25rem; font-weight: 600;">Yii 2.0.53</p>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--admin-text-secondary);">PHP 8.4.13</p>
    </div>
    
    <div class="admin-card" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--admin-text-secondary);">База данных</h3>
        <p style="margin: 0; font-size: 1.25rem; font-weight: 600;">MySQL</p>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--admin-text-secondary);">sneakerhead</p>
    </div>
    
    <div class="admin-card" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--admin-text-secondary);">Окружение</h3>
        <p style="margin: 0; font-size: 1.25rem; font-weight: 600;">Development</p>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--admin-text-secondary);">Localhost</p>
    </div>
    
    <div class="admin-card" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: var(--admin-text-secondary);">Время</h3>
        <p style="margin: 0; font-size: 1.25rem; font-weight: 600;"><?= date('H:i') ?></p>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--admin-text-secondary);"><?= date('d.m.Y') ?></p>
    </div>
</div>

<style>
/* Дополнительные стили для dashboard */
.admin-header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .admin-header-actions {
        justify-content: center;
    }
    
    .admin-stats {
        grid-template-columns: 1fr;
    }
}
</style>
