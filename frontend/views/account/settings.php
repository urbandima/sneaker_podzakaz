<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var bool $passwordChanged */

$this->title = 'Настройки аккаунта - СНИКЕРХЭД';
?>

<style>
.account-page {
    min-height: 100vh;
    background: #f3f4f6;
    padding: 2rem 0;
}

.account-container {
    width: 100%;
    max-width: none;
    margin: 0 auto;
    padding: 0 2rem;
}

@media (max-width: 768px) {
    .account-container {
        padding: 0 1rem;
    }
}

.account-header {
    margin-bottom: 2rem;
}

.account-header h1 {
    font-size: 1.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.account-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
}

@media (max-width: 991px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
}

.account-sidebar {
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.sidebar-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-info {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.user-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
    font-size: 1.5rem;
    color: #fff;
    font-weight: 700;
}

.user-name {
    font-size: 1rem;
    font-weight: 700;
}

.user-email {
    color: #6b7280;
    font-size: 0.8125rem;
}

.account-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-menu li {
    margin-bottom: 0.375rem;
}

.account-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.account-menu a:hover {
    background: #f3f4f6;
}

.account-menu a.active {
    background: #000;
    color: #fff;
}

.account-menu i {
    font-size: 1.125rem;
    width: 24px;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 1rem;
}

.logout-btn:hover {
    background: #fecaca;
}

.account-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.content-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.content-card h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 1.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-save {
    padding: 0.875rem 2rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    background: #333;
}

.info-card {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.info-card.warning {
    background: #fef3c7;
    border-color: #fcd34d;
}

.info-card h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #065f46;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-card.warning h4 {
    color: #92400e;
}

.info-card p {
    margin: 0;
    font-size: 0.8125rem;
    color: #047857;
}

.info-card.warning p {
    color: #a16207;
}

.account-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 640px) {
    .account-info {
        grid-template-columns: 1fr;
    }
}

.info-item {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 10px;
}

.info-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: #1f2937;
}

.danger-zone {
    border: 2px solid #fee2e2;
    border-radius: 12px;
    padding: 1.25rem;
    background: #fef2f2;
}

.danger-zone h3 {
    color: #dc2626;
    font-size: 1rem;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.danger-zone p {
    color: #7f1d1d;
    font-size: 0.875rem;
    margin: 0 0 1rem 0;
}

.btn-danger {
    padding: 0.75rem 1.5rem;
    background: #fff;
    color: #dc2626;
    border: 2px solid #dc2626;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger:hover {
    background: #dc2626;
    color: #fff;
}
</style>

<div class="account-page">
    <div class="account-container">
        <div class="account-header">
            <h1><i class="bi bi-gear"></i> Настройки</h1>
        </div>

        <div class="account-grid">
            <aside class="account-sidebar">
                <div class="sidebar-card">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                        </div>
                        <div class="user-name"><?= Html::encode($customer->getShortName()) ?></div>
                        <div class="user-email"><?= Html::encode($customer->email) ?></div>
                    </div>
                    
                    <ul class="account-menu">
                        <li><a href="<?= Url::to(['/account/profile']) ?>"><i class="bi bi-person"></i> Профиль</a></li>
                        <li><a href="<?= Url::to(['/account/orders']) ?>"><i class="bi bi-bag"></i> Мои заказы</a></li>
                        <li><a href="<?= Url::to(['/catalog/favorites']) ?>"><i class="bi bi-heart"></i> Избранное</a></li>
                        <li><a href="<?= Url::to(['/account/settings']) ?>" class="active"><i class="bi bi-gear"></i> Настройки</a></li>
                    </ul>
                    
                    <?= Html::beginForm(['/account/logout'], 'post') ?>
                        <button type="submit" class="logout-btn">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </button>
                    <?= Html::endForm() ?>
                </div>
            </aside>

            <main class="account-content">
                <div class="content-card">
                    <h2><i class="bi bi-info-circle"></i> Информация об аккаунте</h2>
                    
                    <div class="account-info">
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= Html::encode($customer->email) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Дата регистрации</div>
                            <div class="info-value"><?= Yii::$app->formatter->asDate($customer->created_at, 'long') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Последний вход</div>
                            <div class="info-value">
                                <?= $customer->last_login_at 
                                    ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium') 
                                    : 'Неизвестно' ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Статус</div>
                            <div class="info-value">
                                <?php if ($customer->email_verified): ?>
                                    <span style="color: #059669;"><i class="bi bi-check-circle-fill"></i> Подтвержден</span>
                                <?php else: ?>
                                    <span style="color: #d97706;"><i class="bi bi-exclamation-circle"></i> Не подтвержден</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-shield-lock"></i> Смена пароля</h2>
                    
                    <div class="info-card">
                        <h4><i class="bi bi-info-circle"></i> Рекомендации по безопасности</h4>
                        <p>Используйте пароль длиной не менее 8 символов, включающий буквы, цифры и специальные символы.</p>
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        
                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Подтверждение нового пароля</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn-save">Изменить пароль</button>
                    </form>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-exclamation-triangle"></i> Опасная зона</h2>
                    
                    <div class="danger-zone">
                        <h3><i class="bi bi-trash3"></i> Удаление аккаунта</h3>
                        <p>После удаления аккаунта все ваши данные будут безвозвратно удалены. История заказов сохранится, но не будет привязана к аккаунту.</p>
                        <button type="button" class="btn-danger" onclick="alert('Для удаления аккаунта свяжитесь с поддержкой')">
                            Удалить аккаунт
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
