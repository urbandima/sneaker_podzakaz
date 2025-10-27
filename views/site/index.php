<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'СНИКЕРХЭД - Закажем любую оригинальную обувь для вас';
?>

<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">СНИКЕРХЭД</h1>
                    <p class="hero-subtitle">Закажем любую пару оригинальной обуви для вас</p>
                    <p class="hero-description">
                        Мы специализируемся на заказе оригинальной обуви из США и Европы. 
                        Доставим любую модель по выгодной цене с гарантией подлинности.
                    </p>
                    <div class="hero-buttons">
                        <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-primary">
                            <i class="bi bi-telegram"></i> Заказать в Telegram
                        </a>
                        <a href="<?= \yii\helpers\Url::to(['/site/offer-agreement']) ?>" class="btn-secondary">
                            Договор оферты
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-placeholder">
                        <i class="bi bi-box2-heart"></i>
                    </div>
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
                        Работаем только с проверенными поставщиками. Гарантируем подлинность каждой пары.
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
                    <h3 class="step-title">Выбираете обувь</h3>
                    <p class="step-description">
                        Отправьте нам ссылку на понравившуюся модель или расскажите, что ищете
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
                    <h3 class="step-title">Получаете обувь</h3>
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
                    Напишите нам в Telegram и получите расчет стоимости в течение 10 минут
                </p>
                <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-primary btn-large">
                    <i class="bi bi-telegram"></i> Написать в Telegram
                </a>
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
                        Оригинальная обувь из США и Европы
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.landing-page {
    background: #ffffff;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Hero Section */
.hero-section {
    padding: 5rem 0;
    background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
    letter-spacing: -1px;
}

.hero-subtitle {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1.5rem;
}

.hero-description {
    font-size: 1.125rem;
    color: #6b7280;
    line-height: 1.7;
    margin-bottom: 2rem;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary {
    background: #111827;
    color: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.125rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #1f2937;
    transform: translateY(-2px);
}

.btn-secondary {
    background: transparent;
    color: #111827;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.125rem;
    border: 2px solid #e5e7eb;
    transition: all 0.2s;
}

.btn-secondary:hover {
    border-color: #111827;
    background: #f9fafb;
}

.hero-image-placeholder {
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #e5e7eb 0%, #f9fafb 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8rem;
    color: #9ca3af;
}

/* Features Section */
.features-section {
    padding: 5rem 0;
    background: #ffffff;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #111827;
    text-align: center;
    margin-bottom: 3rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    text-align: center;
    padding: 2rem;
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: #f9fafb;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.5rem;
    color: #111827;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.75rem;
}

.feature-description {
    font-size: 1rem;
    color: #6b7280;
    line-height: 1.6;
}

/* How It Works Section */
.how-it-works-section {
    padding: 5rem 0;
    background: #f9fafb;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.step-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.step-number {
    width: 60px;
    height: 60px;
    background: #111827;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 auto 1.5rem;
}

.step-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.75rem;
}

.step-description {
    font-size: 0.9375rem;
    color: #6b7280;
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    padding: 5rem 0;
    background: #111827;
    color: white;
}

.cta-content {
    text-align: center;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-description {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    color: #d1d5db;
}

.btn-large {
    padding: 1.25rem 3rem;
    font-size: 1.25rem;
}

/* Footer */
.landing-footer {
    padding: 3rem 0 1.5rem;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1rem;
}

.footer-text {
    font-size: 0.9375rem;
    color: #6b7280;
    line-height: 1.8;
}

.footer-text a {
    color: #6b7280;
    text-decoration: none;
}

.footer-text a:hover {
    color: #111827;
    text-decoration: underline;
}

.footer-bottom {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.footer-bottom p {
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.25rem;
    }
    
    .hero-image-placeholder {
        height: 300px;
        font-size: 5rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .cta-title {
        font-size: 2rem;
    }
    
    .features-grid,
    .steps-grid {
        grid-template-columns: 1fr;
    }
}
</style>
