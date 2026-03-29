<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var app\backend\modules\catalog\models\Order[] $orders */

$this->title = 'Мой профиль - СНИКЕРХЭД';

// Breadcrumbs для ЛК
$this->params['breadcrumbs'][] = ['label' => 'Главная', 'url' => ['/']];
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['/account']];
$this->params['breadcrumbs'][] = 'Профиль';
?>

<style>
/* Breadcrumbs для ЛК */
.account-breadcrumbs {
    padding: 0.75rem 0;
    margin-bottom: 1rem;
}
.account-breadcrumbs a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s;
}
.account-breadcrumbs a:hover {
    color: #111827;
}
.account-breadcrumbs span {
    color: #9ca3af;
    margin: 0 0.5rem;
}
.account-breadcrumbs .current {
    color: #111827;
    font-weight: 600;
}

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
    margin-bottom: 1rem;
}

.user-info {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.user-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    color: #fff;
    font-weight: 700;
}

.user-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.user-email {
    color: #6b7280;
    font-size: 0.875rem;
}

.user-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 10px;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
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
    color: #000;
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

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-row-3 {
    grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 768px) {
    .form-row-3 {
        grid-template-columns: 1fr;
    }
}

.profile-form .form-group {
    margin-bottom: 1rem;
}

.profile-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.profile-form .form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.profile-form .form-control:focus {
    outline: none;
    border-color: #3b82f6;
}

.profile-form .form-control:disabled {
    background: #f3f4f6;
    color: #6b7280;
}

.profile-form select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}

.profile-form .help-block {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.form-section {
    padding-top: 1.25rem;
    margin-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
}

.form-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
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

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.order-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.order-card:hover {
    background: #f3f4f6;
    transform: translateX(4px);
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-number {
    font-weight: 700;
}

.order-date {
    font-size: 0.8125rem;
    color: #6b7280;
}

.order-meta {
    text-align: right;
}

.order-total {
    font-weight: 700;
    font-size: 1rem;
}

.order-status {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.6875rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.order-status.created { background: #dbeafe; color: #1d4ed8; }
.order-status.processing { background: #fef3c7; color: #d97706; }
.order-status.shipped { background: #e0e7ff; color: #4338ca; }
.order-status.delivered { background: #d1fae5; color: #059669; }
.order-status.completed { background: #d1fae5; color: #059669; }
.order-status.cancelled { background: #fee2e2; color: #dc2626; }

.empty-orders {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.empty-orders i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.view-all-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    color: #3b82f6;
    font-weight: 600;
    text-decoration: none;
}

.view-all-link:hover {
    text-decoration: underline;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #3b82f6;
}

.checkbox-item label {
    margin: 0;
    font-weight: 500;
    cursor: pointer;
}
</style>

<div class="account-page">
    <div class="account-container">
        <!-- Breadcrumbs -->
        <nav class="account-breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <a href="/account">Личный кабинет</a>
            <span>/</span>
            <span class="current">Профиль</span>
        </nav>
        
        <div class="account-header">
            <h1><i class="bi bi-person-circle"></i> Личный кабинет</h1>
        </div>

        <div class="account-grid">
            <aside class="account-sidebar">
                <div class="sidebar-card">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                        </div>
                        <div class="user-name"><?= Html::encode($customer->getFullName()) ?></div>
                        <div class="user-email"><?= Html::encode($customer->email) ?></div>
                        
                        <div class="user-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?= $customer->orders_count ?></div>
                                <div class="stat-label">Заказов</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></div>
                                <div class="stat-label">Потрачено</div>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="account-menu">
                        <li><a href="<?= Url::to(['/account/profile']) ?>" class="active"><i class="bi bi-person"></i> Профиль</a></li>
                        <li><a href="<?= Url::to(['/account/orders']) ?>"><i class="bi bi-bag"></i> Мои заказы</a></li>
                        <li><a href="<?= Url::to(['/catalog/favorites']) ?>"><i class="bi bi-heart"></i> Избранное</a></li>
                        <li><a href="<?= Url::to(['/account/settings']) ?>"><i class="bi bi-gear"></i> Настройки</a></li>
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
                    <h2><i class="bi bi-person-lines-fill"></i> Личные данные</h2>
                    
                    <?php $form = ActiveForm::begin([
                        'id' => 'profile-form',
                        'options' => ['class' => 'profile-form'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'help-block'],
                        ],
                    ]); ?>

                    <div class="form-row">
                        <?= $form->field($customer, 'last_name')->textInput(['placeholder' => 'Иванов']) ?>
                        <?= $form->field($customer, 'first_name')->textInput(['placeholder' => 'Иван']) ?>
                    </div>
                    
                    <div class="form-row">
                        <?= $form->field($customer, 'middle_name')->textInput(['placeholder' => 'Иванович']) ?>
                        <?= $form->field($customer, 'phone')->textInput(['placeholder' => '+375 29 123-45-67']) ?>
                    </div>
                    
                    <div class="form-row">
                        <?= $form->field($customer, 'birth_date')->input('date') ?>
                        <?= $form->field($customer, 'gender')->dropDownList([
                            '' => 'Не указан',
                            'male' => 'Мужской',
                            'female' => 'Женский',
                        ]) ?>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= Html::encode($customer->email) ?>" disabled>
                        <small class="form-hint">Для смены email обратитесь в поддержку</small>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Адрес доставки по умолчанию</div>
                        
                        <div class="form-row form-row-3">
                            <?= $form->field($customer, 'default_country')->dropDownList([
                                'BY' => 'Беларусь',
                                'RU' => 'Россия',
                                'KZ' => 'Казахстан',
                            ]) ?>
                            <?= $form->field($customer, 'default_city')->textInput(['placeholder' => 'Минск']) ?>
                            <?= $form->field($customer, 'default_postal_code')->textInput(['placeholder' => '220000']) ?>
                        </div>
                        
                        <?= $form->field($customer, 'default_address')->textarea([
                            'rows' => 2,
                            'placeholder' => 'ул. Примерная, д. 1, кв. 123',
                        ]) ?>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Паспортные данные (для таможенного оформления)</div>
                        
                        <div class="form-row form-row-3">
                            <?= $form->field($customer, 'passport_series')->textInput(['placeholder' => 'AB']) ?>
                            <?= $form->field($customer, 'passport_number')->textInput(['placeholder' => '1234567']) ?>
                            <?= $form->field($customer, 'passport_issue_date')->input('date') ?>
                        </div>
                        
                        <?= $form->field($customer, 'inn')->textInput(['placeholder' => 'ИНН (если есть)']) ?>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Уведомления</div>
                        
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <?= Html::activeCheckbox($customer, 'subscribe_news', ['label' => false, 'id' => 'subscribe_news']) ?>
                                <label for="subscribe_news">Получать новости о новых поступлениях</label>
                            </div>
                            <div class="checkbox-item">
                                <?= Html::activeCheckbox($customer, 'subscribe_promo', ['label' => false, 'id' => 'subscribe_promo']) ?>
                                <label for="subscribe_promo">Получать информацию об акциях и скидках</label>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <?= Html::submitButton('Сохранить изменения', ['class' => 'btn-save']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-bag-check"></i> Последние заказы</h2>
                    
                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <i class="bi bi-bag-x"></i>
                            <p>У вас пока нет заказов</p>
                            <a href="<?= Url::to(['/catalog']) ?>" class="view-all-link">
                                Перейти в каталог <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <a href="<?= Url::to(['/account/order-view', 'id' => $order->id]) ?>" class="order-card">
                                    <div class="order-info">
                                        <span class="order-number">Заказ #<?= $order->order_number ?: $order->id ?></span>
                                        <span class="order-date"><?= Yii::$app->formatter->asDate($order->created_at, 'long') ?></span>
                                    </div>
                                    <div class="order-meta">
                                        <div class="order-total"><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></div>
                                        <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="<?= Url::to(['/account/orders']) ?>" class="view-all-link">
                            Все заказы <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>
