<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="page-sale">
    <div class="container">
        <div class="page-header text-center mb-5">
            <h1 class="display-4 fw-bold text-primary"><?= Html::encode($this->title) ?></h1>
            <p class="lead text-muted">Выгодные цены и специальные предложения на оригинальные кроссовки</p>
        </div>

        <!-- Текущие акции -->
        <section class="current-promotions mb-5">
            <h2 class="h3 mb-4"><i class="bi bi-fire"></i> Текущие акции</h2>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card promotion-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge bg-danger fs-6 me-3">-20%</div>
                                <h5 class="card-title mb-0">Скидка на первый заказ</h5>
                            </div>
                            <p class="card-text">Дарим скидку 20% на первый заказ для новых клиентов!</p>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success"></i> Минимальный заказ: 50 BYN</li>
                                <li><i class="bi bi-check-circle text-success"></i> Промокод: NEW20</li>
                                <li><i class="bi bi-check-circle text-success"></i> Действует до 31.03.2026</li>
                            </ul>
                            <?= Html::a('Перейти в каталог', ['/catalog'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card promotion-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge bg-warning text-dark fs-6 me-3">Бесплатная доставка</div>
                                <h5 class="card-title mb-0">Доставка от 100 BYN</h5>
                            </div>
                            <p class="card-text">Бесплатная доставка по всей Беларуси при заказе от 100 BYN</p>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success"></i> Курьерская доставка</li>
                                <li><i class="bi bi-check-circle text-success"></i> Европочта</li>
                                <li><i class="bi bi-check-circle text-success"></i> Белпочта</li>
                            </ul>
                            <?= Html::a('Подробнее', ['/page/delivery-terms'], ['class' => 'btn btn-outline-primary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Программа лояльности -->
        <section class="loyalty-program mb-5">
            <h2 class="h3 mb-4">⭐ Программа лояльности</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="card-title">Зарабатывайте баллы за покупки!</h5>
                            <p class="card-text">Присоединяйтесь к нашей программе лояльности и получайте бонусы за каждую покупку.</p>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-star-fill text-warning"></i> 10 баллов за каждый 1 BYN покупки</li>
                                <li><i class="bi bi-star-fill text-warning"></i> 100 баллов за регистрацию</li>
                                <li><i class="bi bi-star-fill text-warning"></i> 50 баллов за отзыв</li>
                                <li><i class="bi bi-star-fill text-warning"></i> 1 балл = 0.01 BYN</li>
                            </ul>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="loyalty-badge">
                                <i class="bi bi-award-fill text-primary fs-1"></i>
                                <p class="mt-2 mb-0">Участвуйте в программе лояльности!</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?= Html::a('Подробнее о программе', ['/loyalty/program'], ['class' => 'btn btn-primary me-2']) ?>
                        <?= Html::a('Мои баллы', ['/loyalty/index'], ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Купоны -->
        <section class="coupons mb-5">
            <h2 class="h3 mb-4">🎫 Специальные купоны</h2>
            
            <div class="alert alert-info">
                <h5 class="alert-heading">Как использовать купоны?</h5>
                <p class="mb-2">1. Добавьте товары в корзину</p>
                <p class="mb-2">2. Введите промокод в поле "Купон" при оформлении заказа</p>
                <p class="mb-0">3. Скидка автоматически применится к сумме заказа</p>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title text-success">SUMMER2025</h6>
                            <p class="card-text small">Скидка 15% на летние модели</p>
                            <span class="badge bg-success">Активен</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title text-primary">FREESHIP</h6>
                            <p class="card-text small">Бесплатная доставка</p>
                            <span class="badge bg-primary">Активен</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="card-title text-warning">VIP2025</h6>
                            <p class="card-text small">Скидка 25% для VIP клиентов</p>
                            <span class="badge bg-warning text-dark">По запросу</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Условия -->
        <section class="terms">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Условия акций</h5>
                    <ul class="mb-0">
                        <li>Акции не суммируются с другими скидками</li>
                        <li>Промокоды действуют только на товары без скидки</li>
                        <li>Администрация оставляет за собой право изменить условия акций</li>
                        <li>Подробные условия в соответствующих разделах сайта</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</div>

