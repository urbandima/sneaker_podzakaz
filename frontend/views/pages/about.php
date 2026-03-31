<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'О компании СНИКЕРХЭД — магазин оригинальных кроссовок';
$this->params['breadcrumbs'][] = $this->title;

// SEO мета-теги уже установлены в контроллере
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- Заголовок страницы -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3"><?= Html::encode($this->title) ?></h1>
                <p class="lead text-muted">Ведущий магазин оригинальных кроссовок в Беларуси</p>
            </div>

            <!-- Основная информация -->
            <section class="mb-5">
                <h2 class="h3 mb-4">О компании</h2>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-shield-check text-primary fs-2"></i>
                                </div>
                                <h5 class="card-title">100% подлинность</h5>
                                <p class="card-text">Гарантируем оригинальность всех товаров. Каждый кроссовок проходит проверку качества.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-truck text-primary fs-2"></i>
                                </div>
                                <h5 class="card-title">Быстрая доставка</h5>
                                <p class="card-text">Доставка по всей Беларуси в кратчайшие сроки. Отслеживание на каждом этапе.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Наша миссия -->
            <section class="mb-5">
                <h2 class="h3 mb-4">Наша миссия</h2>
                <div class="bg-light p-4 rounded-3">
                    <p class="mb-3">
                        СНИКЕРХЭД — это не просто магазин, а сообщество ценителей оригинальной уличной культуры. 
                        Мы стремимся сделать доступными лучшие мировые бренды кроссовок для жителей Беларуси.
                    </p>
                    <p class="mb-0">
                        Наша цель — предоставить качественный сервис, оригинальную продукцию и создать 
                        комфортные условия для покупки кроссовок онлайн.
                    </p>
                </div>
            </section>

            <!-- Преимущества -->
            <section class="mb-5">
                <h2 class="h3 mb-4">Почему выбирают нас</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="bi bi-award text-warning fs-1"></i>
                            </div>
                            <h5>Гарантия качества</h5>
                            <p class="text-muted">Проверка каждого товара перед отправкой</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="bi bi-headset text-info fs-1"></i>
                            </div>
                            <h5>Поддержка 24/7</h5>
                            <p class="text-muted">Всегда готовы помочь с выбором</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="bi bi-arrow-repeat text-success fs-1"></i>
                            </div>
                            <h5>Легкий возврат</h5>
                            <p class="text-muted">Удобный процесс возврата и обмена</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Статистика -->
            <section class="mb-5">
                <h2 class="h3 mb-4">Наши достижения</h2>
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="p-3">
                            <h3 class="text-primary fw-bold">5000+</h3>
                            <p class="text-muted mb-0">Довольных клиентов</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3">
                            <h3 class="text-primary fw-bold">50+</h3>
                            <p class="text-muted mb-0">Брендов</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3">
                            <h3 class="text-primary fw-bold">1000+</h3>
                            <p class="text-muted mb-0">Моделей</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="p-3">
                            <h3 class="text-primary fw-bold">5 лет</h3>
                            <p class="text-muted mb-0">На рынке</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Призыв к действию -->
            <section class="text-center py-5">
                <h2 class="h3 mb-4">Присоединяйтесь к сообществу СНИКЕРХЭД</h2>
                <p class="lead mb-4">Откройте для себя мир оригинальных кроссовок</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-shop me-2"></i>Перейти в каталог
                    </a>
                    <a href="<?= Url::to(['/contacts']) ?>" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-envelope me-2"></i>Связаться с нами
                    </a>
                </div>
            </section>

        </div>
    </div>
</div>
