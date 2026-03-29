<?php

/**
 * Footer - Полный футер сайта
 * 
 * Секции:
 * - О компании
 * - Помощь
 * - Каталог
 * - Контакты
 * - Социальные сети
 * - Способы оплаты
 */

use yii\helpers\Html;
use yii\helpers\Url;

$company = Yii::$app->settings->getCompany();
?>

<footer class="main-footer site-footer">
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <!-- О компании -->
                <div class="footer-column">
                    <div class="footer-brand">
                        <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>" class="footer-logo">
                        <p class="footer-tagline">Оригинальные кроссовки из США и Европы</p>
                    </div>
                    
                    <div class="footer-badges">
                        <div class="badge-item">
                            <i class="bi bi-shield-check"></i>
                            <span>100% оригинал</span>
                        </div>
                        <div class="badge-item">
                            <i class="bi bi-truck"></i>
                            <span>Быстрая доставка</span>
                        </div>
                        <div class="badge-item">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>14 дней возврат</span>
                        </div>
                    </div>
                </div>
                
                <!-- О компании -->
                <div class="footer-column">
                    <h4 class="footer-title">О компании</h4>
                    <ul class="footer-links">
                        <li><?= Html::a('О нас', ['/page/about']) ?></li>
                        <li><?= Html::a('Контакты', ['/page/contacts']) ?></li>
                        <li><?= Html::a('Политика конфиденциальности', ['/page/privacy']) ?></li>
                        <li><?= Html::a('Реквизиты', ['/page/about']) ?></li>
                    </ul>
                </div>
                
                <!-- Помощь -->
                <div class="footer-column">
                    <h4 class="footer-title">Помощь</h4>
                    <ul class="footer-links">
                        <li><?= Html::a('Условия оплаты', ['/page/payment-terms']) ?></li>
                        <li><?= Html::a('Условия доставки', ['/page/delivery-terms']) ?></li>
                        <li><?= Html::a('Возврат и обмен', ['/page/return-policy']) ?></li>
                        <li><?= Html::a('Часто задаваемые вопросы', ['/contacts']) ?></li>
                        <li><?= Html::a('Отслеживание заказа', ['/account/orders']) ?></li>
                    </ul>
                </div>
                
                <!-- Каталог -->
                <div class="footer-column">
                    <h4 class="footer-title">Каталог</h4>
                    <ul class="footer-links">
                        <li><a href="<?= Url::to(['/catalog/catalog/index']) ?>">Все товары</a></li>
                        <li><a href="<?= Url::to(['/catalog/brands/index']) ?>">Бренды</a></li>
                        <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'sneakers']) ?>">Кроссовки</a></li>
                        <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'boots']) ?>">Ботинки</a></li>
                        <li><a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'sale']) ?>">Распродажа</a></li>
                        <li><a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'new']) ?>">Новинки</a></li>
                    </ul>
                </div>

                <!-- Контакты -->
                <div class="footer-column">
                    <h4 class="footer-title">Контакты</h4>
                    <ul class="footer-contacts">
                        <li>
                            <i class="bi bi-telephone"></i>
                            <a href="tel:+375291234567">+375 (29) 123-45-67</a>
                        </li>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:info@sneakerhead.by">info@sneakerhead.by</a>
                        </li>
                        <li>
                            <i class="bi bi-geo-alt"></i>
                            <span>Минск, ул. Купревича 1, корп. 1</span>
                        </li>
                        <li>
                            <i class="bi bi-clock"></i>
                            <span>Пн-Вс: 10:00 - 20:00</span>
                        </li>
                    </ul>

                    <div class="footer-social">
                        <a href="https://instagram.com" target="_blank" class="social-link" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://t.me" target="_blank" class="social-link" aria-label="Telegram">
                            <i class="bi bi-telegram"></i>
                        </a>
                        <a href="https://tiktok.com" target="_blank" class="social-link" aria-label="TikTok">
                            <i class="bi bi-tiktok"></i>
                        </a>
                        <a href="https://youtube.com" target="_blank" class="social-link" aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="payment-methods">
                    <span class="payment-label">Принимаем к оплате:</span>
                    <div class="payment-icons">
                        <img src="/images/payment/visa.svg" alt="Visa" class="payment-icon">
                        <img src="/images/payment/mastercard.svg" alt="Mastercard" class="payment-icon">
                        <img src="/images/payment/belcard.svg" alt="Белкарт" class="payment-icon">
                        <img src="/images/payment/halva.svg" alt="Халва" class="payment-icon">
                        <img src="/images/payment/erip.svg" alt="ЕРИП" class="payment-icon">
                    </div>
                </div>
                
                <div class="copyright">
                    © <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* =====================================================
   Footer — design tokens
   ===================================================== */
.site-footer {
    background: var(--footer-bg);
    color: var(--footer-text);
    margin-top: auto;
}

.footer-main {
    padding: var(--spacing-2) 0 var(--spacing-1);
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(4, 1fr);
    gap: var(--spacing-2);
}

.footer-brand {
    margin-bottom: var(--spacing-1);
}

.footer-logo {
    height: 20px;
    margin-bottom: var(--spacing-1);
}

.footer-tagline {
    color: var(--text-muted);
    font-size: var(--font-size-base);
    line-height: 1.2;
}

.footer-badges {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
    margin-top: var(--spacing-2);
}

.badge-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-1);
    font-size: var(--font-size-base);
    color: var(--text-muted);
}

.badge-item i {
    color: var(--footer-text);
    font-size: var(--font-size-xs);
}

.footer-title {
    font-size: var(--font-size-base);
    font-weight: 700;
    color: var(--color-white);
    margin-bottom: var(--spacing-4);
}

/* footer-links defined in app.css — extend only what differs */
.footer-links a {
    font-size: var(--font-size-base);
}

.footer-contacts {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contacts li {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-1);
    margin-bottom: var(--spacing-2);
    font-size: var(--font-size-base);
}

.footer-contacts i {
    color: var(--footer-text);
    font-size: var(--font-size-base);
    margin-top: 0.125rem;
}

.footer-contacts a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.footer-contacts a:hover {
    color: var(--color-white);
}

.footer-social {
    display: flex;
    gap: var(--spacing-3);
    margin-top: var(--spacing-3);
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: var(--color-dark-gray);
    color: var(--color-white);
    border-radius: var(--radius-md);
    transition: background var(--transition-normal), transform var(--transition-normal);
    text-decoration: none;
}

.social-link:hover {
    background: var(--color-black);
    color: var(--color-white);
    transform: translateY(-2px);
}

.footer-bottom {
    background: var(--color-black);
    padding: var(--spacing-2) 0;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.payment-methods {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.payment-label {
    color: var(--footer-link);
    font-size: var(--font-size-xs);
}

.payment-icons {
    display: flex;
    gap: var(--spacing-2);
}

.payment-icon {
    height: 20px;
    opacity: 0.7;
    transition: opacity var(--transition-fast);
}

.payment-icon:hover {
    opacity: 1;
}

.copyright {
    color: var(--footer-link);
    font-size: var(--font-size-xs);
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-8);
    }

    .footer-column:first-child {
        grid-column: 1 / -1;
    }

    .footer-brand {
        display: flex;
        align-items: center;
        gap: var(--spacing-4);
    }

    .footer-badges {
        flex-direction: row;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .footer-main {
        padding: var(--spacing-12) 0 var(--spacing-8);
    }

    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-6);
    }

    .footer-column:first-child {
        grid-column: 1 / -1;
    }

    .footer-brand {
        flex-direction: column;
        align-items: flex-start;
    }

    .footer-bottom-content {
        flex-direction: column;
        gap: var(--spacing-4);
        text-align: center;
    }

    .payment-methods {
        flex-direction: column;
    }
}
</style>
