<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AboutAsset;

/* @var $this yii\web\View */

$this->title = 'О компании СНИКЕРХЭД';
$this->params['breadcrumbs'][] = $this->title;

AboutAsset::register($this);
?>

<div class="about-page">
    
    <!-- Заголовок страницы -->
    <div class="about-header">
        <h1 class="about-title">О компании</h1>
        <p class="about-subtitle">Ведущий магазин оригинальных кроссовок в Беларуси</p>
    </div>

    <!-- Преимущества -->
    <section class="about-benefits">
        <div class="benefits-grid-2">
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3>100% подлинность</h3>
                <p>Гарантируем оригинальность всех товаров. Каждый кроссовок проходит проверку качества.</p>
            </div>
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-truck"></i>
                </div>
                <h3>Быстрая доставка</h3>
                <p>Доставка по всей Беларуси в кратчайшие сроки. Отслеживание на каждом этапе.</p>
            </div>
        </div>
    </section>

    <!-- Наша миссия -->
    <section class="about-mission">
        <h2>Наша миссия</h2>
        <div class="mission-box">
            <p>
                СНИКЕРХЭД — это не просто магазин, а сообщество ценителей оригинальной уличной культуры. 
                Мы стремимся сделать доступными лучшие мировые бренды кроссовок для жителей Беларуси.
            </p>
            <p>
                Наша цель — предоставить качественный сервис, оригинальную продукцию и создать 
                комфортные условия для покупки кроссовок онлайн.
            </p>
        </div>
    </section>

    <!-- Статистика -->
    <section class="about-stats">
        <div class="stat-card">
            <div class="stat-number">5000+</div>
            <div class="stat-label">Клиентов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">50+</div>
            <div class="stat-label">Брендов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">1000+</div>
            <div class="stat-label">Моделей</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">5 лет</div>
            <div class="stat-label">На рынке</div>
        </div>
    </section>

    <!-- Призыв к действию -->
    <section class="about-cta">
        <h2>Присоединяйтесь к нам</h2>
        <p>Откройте для себя мир оригинальных кроссовок</p>
        <div class="cta-buttons">
            <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-primary btn-lg">
                Перейти в каталог
            </a>
            <a href="<?= Url::to(['/contacts']) ?>" class="btn btn-secondary btn-lg">
                Связаться с нами
            </a>
        </div>
    </section>

</div>
