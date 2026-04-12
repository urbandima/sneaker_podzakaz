<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'О компании СНИКЕРХЭД — магазин оригинальных кроссовок';
$this->params['breadcrumbs'][] = $this->title;

\app\frontend\assets\AboutAsset::register($this);
?>

<div class="about-page">

    <!-- Заголовок -->
    <div class="about-header">
        <h1 class="about-title">О компании</h1>
        <p class="about-subtitle">Ведущий магазин оригинальных кроссовок в Беларуси с гарантией подлинности</p>
    </div>

    <!-- Преимущества -->
    <div class="about-benefits">
        <div class="benefits-grid-2">
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3>100% подлинность</h3>
                <p>Гарантируем оригинальность всех товаров. Каждая пара проходит проверку перед отправкой.</p>
            </div>
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-truck"></i>
                </div>
                <h3>Быстрая доставка</h3>
                <p>Доставляем по всей Беларуси. Курьер в Минске — на следующий день, почта — 3–5 дней.</p>
            </div>
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <h3>14 дней на возврат</h3>
                <p>Не подошёл размер или фасон — вернём деньги без вопросов в течение 14 дней.</p>
            </div>
            <div class="benefit-card-large">
                <div class="benefit-icon-large">
                    <i class="bi bi-headset"></i>
                </div>
                <h3>Поддержка 24/7</h3>
                <p>Наши менеджеры всегда на связи. Отвечаем в течение 15 минут по любому вопросу.</p>
            </div>
        </div>
    </div>

    <!-- Миссия -->
    <div class="about-mission">
        <h2>Наша миссия</h2>
        <div class="mission-box">
            <p>
                СНИКЕРХЭД — это не просто магазин, а сообщество ценителей оригинальной уличной культуры.
                Мы стремимся сделать лучшие мировые бренды кроссовок доступными для каждого жителя Беларуси.
            </p>
            <p>
                Мы работаем только с проверенными поставщиками из США и Европы, поэтому каждая пара,
                которую вы получаете, — стопроцентный оригинал с подтверждёнными документами.
            </p>
        </div>
    </div>

    <!-- Статистика -->
    <div class="about-stats">
        <div class="stat-card">
            <div class="stat-number">10 000+</div>
            <div class="stat-label">Довольных клиентов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">5 000+</div>
            <div class="stat-label">Моделей в каталоге</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">100%</div>
            <div class="stat-label">Оригинальных товаров</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4.9 ★</div>
            <div class="stat-label">Средняя оценка</div>
        </div>
    </div>

    <!-- CTA -->
    <div class="about-cta">
        <h2>Готовы найти свою пару?</h2>
        <div class="cta-buttons">
            <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary">Смотреть каталог</a>
            <a href="<?= Url::to(['/page/contacts']) ?>" class="btn btn-secondary">Связаться с нами</a>
        </div>
    </div>

</div>
