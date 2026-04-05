<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'О компании СНИКЕРХЭД — магазин оригинальных кроссовок';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="about-page">
    <div class="about-container">
        
        <!-- Заголовок страницы -->
        <div class="about-hero">
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="about-subtitle">Ведущий магазин оригинальных кроссовок в Беларуси</p>
        </div>

        <!-- Основная информация -->
        <section class="about-section">
            <h2>О компании</h2>
            <div class="about-cards">
                <div class="about-card">
                    <div class="about-card-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>100% подлинность</h3>
                    <p>Гарантируем оригинальность всех товаров. Каждый кроссовок проходит проверку качества.</p>
                </div>
                <div class="about-card">
                    <div class="about-card-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3>Быстрая доставка</h3>
                    <p>Доставка по всей Беларуси в кратчайшие сроки. Отслеживание на каждом этапе.</p>
                </div>
            </div>
        </section>

        <!-- Наша миссия -->
        <section class="about-section">
            <h2>Наша миссия</h2>
            <div class="about-mission">
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

        <!-- Преимущества -->
        <section class="about-section">
            <h2>Почему выбирают нас</h2>
            <div class="about-stats">
                <div class="stat-item">
                    <i class="bi bi-award"></i>
                    <h4>Гарантия качества</h4>
                    <p>Проверка каждого товара перед отправкой</p>
                </div>
                <div class="stat-item">
                    <i class="bi bi-shield-check"></i>
                    <h4>Защищенный платеж</h4>
                    <p>Безопасные способы оплаты</p>
                </div>
                <div class="stat-item">
                    <i class="bi bi-patch-check"></i>
                    <h4>Оригинальная продукция</h4>
                    <p>Только оригинальные товары от брендов</p>
                </div>
            </div>
        </section>

    </div>
</div>
