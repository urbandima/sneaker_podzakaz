<?php

/** @var yii\web\View $this */
/** @var app\models\Brand[] $brands */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Страница не найдена';
$this->registerMetaTag([
    'name' => 'robots',
    'content' => 'noindex, nofollow'
]);

// Подключаем стили для 404 страницы
$this->registerCssFile('@web/css/page-404.css', ['depends' => [\app\assets\AppAsset::class]]);
?>

<div class="page-404">
    <!-- Hero Section с анимированным 404 -->
    <section class="error-hero">
        <div class="container">
            <div class="error-content">
                <!-- Большой анимированный 404 -->
                <div class="error-number">
                    <span class="digit digit-4" data-digit="4">4</span>
                    <span class="digit digit-0 sneaker-icon">
                        <i class="bi bi-bag-x"></i>
                    </span>
                    <span class="digit digit-4-second" data-digit="4">4</span>
                </div>
                
                <h1 class="error-title">Упс! Страница не найдена</h1>
                <p class="error-description">
                    Похоже, вы зашли не туда. Эта страница удалена, перемещена или никогда не существовала.
                </p>
                
                <!-- Поиск -->
                <div class="search-wrapper">
                    <form action="<?= Url::to(['/catalog/index']) ?>" method="get" class="search-form">
                        <div class="search-input-group">
                            <i class="bi bi-search search-icon"></i>
                            <input 
                                type="text" 
                                name="q" 
                                class="search-input" 
                                placeholder="Поиск кроссовок, брендов, артикулов..."
                                autocomplete="off"
                            >
                            <button type="submit" class="search-button">
                                Найти
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Быстрые действия -->
                <div class="quick-actions">
                    <a href="<?= Url::to(['/']) ?>" class="action-btn btn-primary">
                        <i class="bi bi-house-door"></i>
                        <span>На главную</span>
                    </a>
                    <a href="<?= Url::to(['/catalog/index']) ?>" class="action-btn btn-secondary">
                        <i class="bi bi-grid-3x3"></i>
                        <span>Каталог</span>
                    </a>
                    <a href="<?= Url::to(['/site/contacts']) ?>" class="action-btn btn-secondary">
                        <i class="bi bi-headset"></i>
                        <span>Поддержка</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Help Section -->
    <section class="help-section">
        <div class="container">
            <div class="help-card">
                <div class="help-icon">
                    <i class="bi bi-question-circle"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-title">Нужна помощь?</h3>
                    <p class="help-description">
                        Свяжитесь с нами в Telegram — ответим в течение 5 минут!
                    </p>
                </div>
                <div class="help-actions">
                    <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-telegram">
                        <i class="bi bi-telegram"></i>
                        Написать в Telegram
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Анимация при загрузке -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация чисел 404
    const digits = document.querySelectorAll('.digit');
    digits.forEach((digit, index) => {
        setTimeout(() => {
            digit.classList.add('animate-in');
        }, index * 150);
    });
    
    // Анимация при вводе в поиск
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.closest('.search-input-group').classList.add('focused');
        });
        
        searchInput.addEventListener('blur', function() {
            this.closest('.search-input-group').classList.remove('focused');
        });
    }
    
    // Добавляем анимацию при наведении на карточки
    const cards = document.querySelectorAll('.brand-card, .category-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
