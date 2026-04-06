<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Покупатель: ' . $customer->getFullName();
?>


<div class="customer-view-page">
    <div class="page-header">
        <a href="<?= Url::to(['customer/index']) ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Покупатель</h1>
        <div class="header-actions">
            <a href="<?= Url::to(['customer/update', 'id' => $customer->id]) ?>" class="btn-header edit">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </div>
    </div>

    <div class="content-grid">
        <div class="content-main">
            <div class="content-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                    </div>
                    <div class="profile-info">
                        <h3><?= Html::encode($customer->getFullName()) ?></h3>
                        <div class="email"><?= Html::encode($customer->email) ?></div>
                        <div class="status">
                            <span class="status-badge <?= $customer->status == 10 ? 'active' : 'inactive' ?>">
                                <?= $customer->getStatusLabel() ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value"><?= $customer->orders_count ?></div>
                        <div class="stat-label">Заказов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></div>
                        <div class="stat-label">Потрачено</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $customer->last_order_at ? Yii::$app->formatter->asDate($customer->last_order_at, 'short') : '-' ?></div>
                        <div class="stat-label">Последний заказ</div>
                    </div>
                </div>

                <h2><i class="bi bi-person-lines-fill"></i> Контактные данные</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value"><?= Html::encode($customer->phone ?: 'Не указан') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Дата рождения</div>
                        <div class="info-value"><?= $customer->birth_date ? Yii::$app->formatter->asDate($customer->birth_date, 'long') : 'Не указана' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Город</div>
                        <div class="info-value"><?= Html::encode($customer->default_city ?: 'Не указан') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Адрес</div>
                        <div class="info-value"><?= Html::encode($customer->default_address ?: 'Не указан') ?></div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h2><i class="bi bi-bag-check"></i> История заказов</h2>
                
                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <a href="<?= Url::to(['order/view', 'id' => $order->id]) ?>" class="order-item">
                                <div class="order-info">
                                    <div class="order-number">Заказ #<?= $order->order_number ?: $order->id ?></div>
                                    <div class="order-date"><?= Yii::$app->formatter->asDatetime($order->created_at, 'short') ?></div>
                                </div>
                                <div class="order-meta">
                                    <div class="order-total"><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></div>
                                    <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="bi bi-bag-x" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                        Заказов нет
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-sidebar">
            <div class="content-card">
                <h2><i class="bi bi-info-circle"></i> Информация</h2>
                
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">ID покупателя</div>
                    <div class="info-value">#<?= $customer->id ?></div>
                </div>
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">Дата регистрации</div>
                    <div class="info-value"><?= Yii::$app->formatter->asDatetime($customer->created_at, 'medium') ?></div>
                </div>
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">Последний вход</div>
                    <div class="info-value"><?= $customer->last_login_at ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium') : 'Нет данных' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">IP последнего входа</div>
                    <div class="info-value"><?= Html::encode($customer->last_login_ip ?: 'Нет данных') ?></div>
                </div>
            </div>

            <div class="content-card">
                <h2><i class="bi bi-gear"></i> Действия</h2>
                
                <div class="action-buttons">
                    <button type="button" class="btn-action primary" onclick="linkOrders(<?= $customer->id ?>)">
                        <i class="bi bi-link-45deg"></i> Связать заказы
                    </button>
                    <button type="button" class="btn-action warning" onclick="resetPassword(<?= $customer->id ?>)">
                        <i class="bi bi-key"></i> Сбросить пароль
                    </button>
                    <button type="button" class="btn-action" onclick="toggleStatus(<?= $customer->id ?>)">
                        <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>"></i>
                        <?= $customer->status == 10 ? 'Заблокировать' : 'Разблокировать' ?>
                    </button>
                    <a href="<?= Url::to(['customer/delete', 'id' => $customer->id]) ?>" class="btn-action danger" onclick="return confirm('Удалить покупателя?')">
                        <i class="bi bi-trash3"></i> Удалить
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

