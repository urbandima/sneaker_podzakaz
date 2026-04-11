<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Мой профиль - СНИКЕРХЭД';

// Breadcrumbs для ЛК
$this->params['breadcrumbs'][] = ['label' => 'Главная', 'url' => ['/']];
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['/account']];
$this->params['breadcrumbs'][] = 'Профиль';
?>

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
            <?= $this->render('_sidebar', [
                'customer' => $customer,
                'activePage' => 'profile',
                'orders' => $orders,
            ]) ?>

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

                    <div class="profile-form-submit">
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
