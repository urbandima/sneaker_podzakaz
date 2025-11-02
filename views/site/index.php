<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'СНИКЕРХЭД - Закажем любые оригинальные товары для вас';
?>

<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">СНИКЕРХЭД</h1>
                <p class="hero-subtitle">Закажем оригинальные товары<br>из США и Европы</p>
                <p class="hero-description">
                    Работаем с брендовой обувью, одеждой, аксессуарами и электроникой. 
                    Доставка по Беларуси с гарантией подлинности.
                </p>
                <div class="hero-buttons">
                    <a href="<?= \yii\helpers\Url::to(['/catalog/index']) ?>" class="btn-cta">
                        <i class="bi bi-grid-3x3"></i> Каталог товаров
                    </a>
                    <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-cta btn-secondary">
                        <i class="bi bi-telegram"></i> Сделать заказ
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Brands Carousel -->
    <section class="brands-section">
        <div class="container">
            <p class="brands-label">Работаем с ведущими брендами</p>
            <div class="brands-carousel">
                <div class="brands-track">
                    <div class="brand-item">NIKE</div>
                    <div class="brand-item">ADIDAS</div>
                    <div class="brand-item">PUMA</div>
                    <div class="brand-item">NEW BALANCE</div>
                    <div class="brand-item">REEBOK</div>
                    <div class="brand-item">CONVERSE</div>
                    <div class="brand-item">VANS</div>
                    <div class="brand-item">ASICS</div>
                    <!-- Дубликаты для бесшовной прокрутки -->
                    <div class="brand-item">NIKE</div>
                    <div class="brand-item">ADIDAS</div>
                    <div class="brand-item">PUMA</div>
                    <div class="brand-item">NEW BALANCE</div>
                    <div class="brand-item">REEBOK</div>
                    <div class="brand-item">CONVERSE</div>
                    <div class="brand-item">VANS</div>
                    <div class="brand-item">ASICS</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Почему выбирают нас</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title">100% оригинал</h3>
                    <p class="feature-description">
                        Работаем только с проверенными поставщиками. Гарантируем подлинность каждого товара.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3 class="feature-title">Быстрая доставка</h3>
                    <p class="feature-description">
                        Доставка из США и Европы от 14 дней. Отслеживание посылки на всех этапах.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3 class="feature-title">Выгодные цены</h3>
                    <p class="feature-description">
                        Цены ниже розничных в Беларуси. Никаких скрытых комиссий и переплат.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h3 class="feature-title">Поддержка 24/7</h3>
                    <p class="feature-description">
                        Всегда на связи в Telegram. Ответим на любые вопросы и поможем с выбором.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="container">
            <h2 class="section-title">Как это работает</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Выбираете товар</h3>
                    <p class="step-description">
                        Отправьте нам ссылку на понравившийся товар или расскажите, что ищете
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Получаете расчет</h3>
                    <p class="step-description">
                        Мы рассчитаем точную стоимость с доставкой и отправим счет на оплату
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Оплачиваете заказ</h3>
                    <p class="step-description">
                        Переводите деньги любым удобным способом и подтверждаете оплату
                    </p>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Получаете товар</h3>
                    <p class="step-description">
                        Ваш заказ прибудет в течение 14-21 дня. Доставка до двери
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Готовы сделать заказ?</h2>
                <p class="cta-description">
                    Выберите товар в каталоге или напишите нам в Telegram для персонального заказа
                </p>
                <div class="cta-buttons">
                    <a href="<?= \yii\helpers\Url::to(['/catalog/index']) ?>" class="btn-primary btn-large">
                        <i class="bi bi-grid-3x3"></i> Открыть каталог
                    </a>
                    <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-primary btn-large btn-outline">
                        <i class="bi bi-telegram"></i> Написать в Telegram
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4 class="footer-title">СНИКЕРХЭД</h4>
                    <p class="footer-text">
                        Оригинальные товары из США и Европы
                    </p>
                </div>
                <div class="footer-column">
                    <h4 class="footer-title">Контакты</h4>
                    <p class="footer-text">
                        <i class="bi bi-telephone"></i> +375 44 700-90-01<br>
                        <i class="bi bi-envelope"></i> sneakerkultura@gmail.com<br>
                        <i class="bi bi-telegram"></i> <a href="https://t.me/sneakerheadbyweb_bot" target="_blank">@sneakerheadbyweb_bot</a>
                    </p>
                </div>
                <div class="footer-column">
                    <h4 class="footer-title">Информация</h4>
                    <p class="footer-text">
                        <a href="<?= \yii\helpers\Url::to(['/site/offer-agreement']) ?>">Договор оферты</a><br>
                        <a href="https://sneaker-head.by/page/politika-konfidencialnosti" target="_blank">Политика конфиденциальности</a><br>
                        <a href="https://sneaker-head.by/page/dostavka-i-oplata" target="_blank">Доставка и оплата</a>
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.</p>
            </div>
        </div>
    </footer>
</div>

<style>
/* Mobile First Approach */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.landing-page {
    background: #ffffff;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: #000000;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Hero Section - Mobile First */
.hero-section {
    padding: 3rem 0;
    background: #ffffff;
    text-align: center;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 900;
    color: #000000;
    margin-bottom: 1rem;
    letter-spacing: -2px;
    line-height: 1;
}

.hero-subtitle {
    font-size: 1.125rem;
    font-weight: 400;
    color: #666666;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.hero-description {
    font-size: 1rem;
    color: #666666;
    line-height: 1.6;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-cta {
    background: #000000;
    color: #ffffff;
    padding: 1rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 2px solid #000000;
}

.btn-cta:hover {
    background: #ffffff;
    color: #000000;
}

.btn-secondary {
    background: #ffffff;
    color: #000000;
    border: 2px solid #000000;
}

.btn-secondary:hover {
    background: #000000;
    color: #ffffff;
}

/* Brands Carousel - Mobile First */
.brands-section {
    padding: 2rem 0;
    background: #000000;
    overflow: hidden;
}

.brands-label {
    text-align: center;
    color: #ffffff;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.brands-carousel {
    overflow: hidden;
    position: relative;
}

.brands-track {
    display: flex;
    gap: 2rem;
    animation: scroll 30s linear infinite;
}

.brand-item {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 900;
    letter-spacing: 2px;
    white-space: nowrap;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.brand-item:hover {
    opacity: 1;
}

@keyframes scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.brands-track:hover {
    animation-play-state: paused;
}

/* Features Section - Mobile First */
.features-section {
    padding: 3rem 0;
    background: #f8f8f8;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #000000;
    text-align: center;
    margin-bottom: 2rem;
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.feature-card {
    text-align: center;
    padding: 1.5rem;
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: #000000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.75rem;
    color: #ffffff;
}

.feature-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #000000;
    margin-bottom: 0.5rem;
}

.feature-description {
    font-size: 0.9375rem;
    color: #666666;
    line-height: 1.6;
}

/* How It Works Section - Mobile First */
.how-it-works-section {
    padding: 3rem 0;
    background: #ffffff;
}

.steps-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.step-card {
    background: #f8f8f8;
    padding: 1.5rem;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    text-align: center;
}

.step-number {
    width: 50px;
    height: 50px;
    background: #000000;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 auto 1rem;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #000000;
    margin-bottom: 0.5rem;
}

.step-description {
    font-size: 0.9375rem;
    color: #666666;
    line-height: 1.6;
}

/* CTA Section - Mobile First */
.cta-section {
    padding: 3rem 0;
    background: #000000;
    color: #ffffff;
}

.cta-content {
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.cta-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-description {
    font-size: 1rem;
    margin-bottom: 2rem;
    color: #cccccc;
}

.btn-large {
    padding: 1.125rem 2.5rem;
    font-size: 1.125rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary {
    background: #ffffff;
    color: #000000;
    padding: 1rem 2rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 2px solid #ffffff;
}

.btn-primary:hover {
    background: transparent;
    color: #ffffff;
    border-color: #ffffff;
}

.btn-outline {
    background: transparent;
    color: #ffffff;
    border: 2px solid #ffffff;
}

.btn-outline:hover {
    background: #ffffff;
    color: #000000;
}

/* Footer - Mobile First */
.landing-footer {
    padding: 2.5rem 0 1.5rem;
    background: #000000;
    color: #ffffff;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-title {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.75rem;
}

.footer-text {
    font-size: 0.875rem;
    color: #cccccc;
    line-height: 1.8;
}

.footer-text a {
    color: #cccccc;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-text a:hover {
    color: #ffffff;
}

.footer-bottom {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid #333333;
}

.footer-bottom p {
    color: #888888;
    font-size: 0.8125rem;
}

/* Tablet - 768px and up */
@media (min-width: 768px) {
    .container {
        padding: 0 2rem;
    }
    
    .hero-section {
        padding: 4rem 0;
    }
    
    .hero-title {
        font-size: 3.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.375rem;
    }
    
    .hero-description {
        font-size: 1.125rem;
    }
    
    .section-title {
        font-size: 2.25rem;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
    }
    
    .steps-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
    }
    
    .footer-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .cta-title {
        font-size: 2.25rem;
    }
    
    .cta-description {
        font-size: 1.125rem;
    }
}

/* Desktop - 1024px and up */
@media (min-width: 1024px) {
    .hero-section {
        padding: 5rem 0;
    }
    
    .hero-title {
        font-size: 4rem;
    }
    
    .section-title {
        font-size: 2.5rem;
        margin-bottom: 3rem;
    }
    
    .features-section,
    .how-it-works-section {
        padding: 4rem 0;
    }
    
    .features-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .steps-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .cta-section {
        padding: 4rem 0;
    }
    
    .cta-title {
        font-size: 2.75rem;
    }
    
    .brand-item {
        font-size: 1.5rem;
    }
}
</style>
