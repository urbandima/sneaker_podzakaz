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

$company = Yii::$app->settings->getCompany();
?>

<footer class="site-footer">
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
                        <li><a href="/catalog">Все товары</a></li>
                        <li><a href="/brands">Бренды</a></li>
                        <li><a href="/catalog?category=sneakers">Кроссовки</a></li>
                        <li><a href="/catalog?category=boots">Ботинки</a></li>
                        <li><a href="/sale">Распродажа</a></li>
                        <li><a href="/catalog?sort=new">Новинки</a></li>
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
/* Footer Styles */
.site-footer {
    background: #0f172a;
    color: #e2e8f0;
    margin-top: auto;
}

.footer-main {
    padding: 0.5rem 0 0.25rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(4, 1fr);
    gap: 0.5rem;
}

.footer-brand {
    margin-bottom: 0.25rem;
}

.footer-logo {
    height: 20px;
    margin-bottom: 0.25rem;
}

.footer-tagline {
    color: #999999;
    font-size: 1rem;
    line-height: 1.2;
}

.footer-badges {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.badge-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 1rem;
    color: #999999;
}

.badge-item i {
    color: #000000;
    font-size: 0.75rem;
}

.footer-title {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.25rem;
}

.footer-links a {
    color: #999999;
    text-decoration: none;
    display: block;
    padding: var(--spacing-1) 0;
    transition: color var(--transition-fast);
    font-size: 1rem;
}

.footer-links a:hover {
    color: #ffffff;
}

.footer-contacts {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contacts li {
    display: flex;
    align-items: flex-start;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.footer-contacts i {
    color: #000000;
    font-size: 1rem;
    margin-top: 0.125rem;
}

.footer-contacts a {
    color: #999999;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-contacts a:hover {
    color: #ffffff;
}

.footer-social {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: #1a1a1a;
    color: #ffffff;
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
}

.social-link:hover {
    background: #000000;
    color: white;
    transform: translateY(-2px);
}

.footer-bottom {
    background: #020617;
    padding: 0.5rem 0;
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.payment-methods {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-label {
    color: #64748b;
    font-size: 0.75rem;
}

.payment-icons {
    display: flex;
    gap: 0.5rem;
}

.payment-icon {
    height: 20px;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.payment-icon:hover {
    opacity: 1;
}

.copyright {
    color: #64748b;
    font-size: 0.75rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .footer-column:first-child {
        grid-column: 1 / -1;
    }
    
    .footer-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .footer-badges {
        flex-direction: row;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .footer-main {
        padding: 3rem 0 2rem;
    }
    
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
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
        gap: 1rem;
        text-align: center;
    }
    
    .payment-methods {
        flex-direction: column;
    }
}
</style>
