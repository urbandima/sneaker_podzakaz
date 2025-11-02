<?php
/** @var yii\web\View $this */

$this->title = 'О нас - СНИКЕРХЭД';
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="page-wrapper">
    <header class="catalog-header">
        <div class="container">
            <button type="button" class="back-btn" onclick="history.back()">
                <i class="bi bi-arrow-left"></i>
            </button>
            <a href="/" class="logo">СНИКЕРХЭД</a>
            <a href="/catalog/favorites" class="favorites">
                <i class="bi bi-heart"></i>
            </a>
        </div>
    </header>
    
    <div class="page-content">
        <div class="container">
            <h1 class="page-title">О нас</h1>
            
            <div class="content-sections">
                <section class="content-section">
                    <h2 class="section-title">👟 СНИКЕРХЭД - Оригинальные товары из США и Европы</h2>
                    <p class="section-text">
                        Мы специализируемся на продаже оригинальной обуви и одежды от мировых брендов. 
                        Каждый товар проходит тщательную проверку подлинности.
                    </p>
                </section>
                
                <section class="content-section">
                    <h3 class="subsection-title">🎯 Наши преимущества</h3>
                    <div class="features-grid">
                        <div class="feature-card">
                            <h4 class="feature-title">✅ 100% оригинал</h4>
                            <p class="feature-text">Работаем только с официальными поставщиками</p>
                        </div>
                        <div class="feature-card">
                            <h4 class="feature-title">🚚 Быстрая доставка</h4>
                            <p class="feature-text">По всей Беларуси за 1-3 дня</p>
                        </div>
                        <div class="feature-card">
                            <h4 class="feature-title">💳 Удобная оплата</h4>
                            <p class="feature-text">Наличные, карты, рассрочка</p>
                        </div>
                        <div class="feature-card">
                            <h4 class="feature-title">🔄 Обмен и возврат</h4>
                            <p class="feature-text">14 дней на обмен или возврат</p>
                        </div>
                    </div>
                </section>
                
                <section class="content-section">
                    <h3 class="subsection-title">📞 Контакты</h3>
                    <p class="section-text">
                        Телефон: <a href="tel:+375291234567" class="contact-link">+375 29 123-45-67</a><br>
                        Email: <a href="mailto:info@sneakerhead.by" class="contact-link">info@sneakerhead.by</a>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.page-wrapper {
    min-height: 100vh;
    background: var(--gray-light, #f3f4f6);
}

.page-content {
    padding: var(--spacing-lg, 24px) 0;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 900;
    margin-bottom: var(--spacing-lg, 24px);
    color: var(--dark, #111827);
}

.content-sections {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl, 32px);
}

.content-section {
    background: white;
    padding: var(--spacing-lg, 24px);
    border-radius: var(--radius-lg, 16px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--spacing-md, 16px);
    color: var(--dark, #111827);
    line-height: 1.3;
}

.subsection-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: var(--spacing-md, 16px);
    color: var(--dark, #111827);
}

.section-text {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--gray, #6b7280);
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-md, 16px);
}

.feature-card {
    padding: var(--spacing-lg, 24px);
    background: var(--gray-light, #f3f4f6);
    border-radius: var(--radius-md, 12px);
    transition: var(--transition, 0.2s);
}

.feature-card:active {
    transform: scale(0.98);
}

.feature-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--dark, #111827);
}

.feature-text {
    font-size: 0.9375rem;
    color: var(--gray, #6b7280);
    line-height: 1.5;
}

.contact-link {
    color: var(--dark, #111827);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition, 0.2s);
}

.contact-link:hover {
    color: var(--primary, #2563eb);
}

@media (min-width: 768px) {
    .page-title {
        font-size: 2.5rem;
    }
    
    .section-title {
        font-size: 1.75rem;
    }
    
    .subsection-title {
        font-size: 1.5rem;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-lg, 24px);
    }
    
    .content-section {
        padding: var(--spacing-xl, 32px);
    }
}

@media (min-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
