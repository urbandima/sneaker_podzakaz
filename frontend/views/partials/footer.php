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
                        <img src="/images/logo-white.png" alt="СНИКЕРХЭД" class="footer-logo">
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
                        <li><?= Html::a('Часто задаваемые вопросы', ['/page/faq']) ?></li>
                        <li><?= Html::a('Отслеживание заказа', ['/account/tracking']) ?></li>
                    </ul>
                </div>
                
                <!-- Каталог -->
                <div class="footer-column">
                    <h4 class="footer-title">Каталог</h4>
                    <ul class="footer-links">
                        <li><a href="/catalog">Все товары</a></li>
                        <li><a href="/catalog/brands">Бренды</a></li>
                        <li><a href="/catalog/categories">Категории</a></li>
                        <li><a href="/sale">Распродажа</a></li>
                        <li><a href="/catalog/new">Новинки</a></li>
                    </ul>
                </div>
                
                <!-- Контакты -->
                <div class="footer-column">
                    <h4 class="footer-title">Контакты</h4>
                    <div class="footer-contact">
                        <div class="contact-item">
                            <i class="bi bi-telephone"></i>
                            <a href="tel:+375291234567">+375 (29) 123-45-67</a>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:info@snikered.by">info@snikered.by</a>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-geo-alt"></i>
                            <span>г. Минск, ул. Купревича 1, корп. 1</span>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-clock"></i>
                            <span>Пн-Пт: 9:00 - 18:00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Каталог -->
                <div class="footer-column">
                    <h4 class="footer-title">Каталог</h4>
                    <ul class="footer-links">
                        <li><a href="/catalog?category=sneakers">Кроссовки</a></li>
                        <li><a href="/catalog?category=boots">Ботинки</a></li>
                        <li><a href="/catalog?category=sandals">Сандалии</a></li>
                        <li><a href="/catalog?sort=new">Новинки</a></li>
                        <li><a href="/catalog?sort=sale">Скидки</a></li>
                        <li><a href="/brands">Все бренды</a></li>
                    </ul>
                </div>
                
                <!-- Контакты -->
                <div class="footer-column">
                    <h4 class="footer-title">Контакты</h4>
                    <ul class="footer-contacts">
                        <li>
                            <i class="bi bi-telephone"></i>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $company['phone'] ?? '+375291234567') ?>">
                                <?= Html::encode($company['phone'] ?? '+375 (29) 123-45-67') ?>
                            </a>
                        </li>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:<?= $company['email'] ?? 'info@sneakerhead.by' ?>">
                                <?= Html::encode($company['email'] ?? 'info@sneakerhead.by') ?>
                            </a>
                        </li>
                        <li>
                            <i class="bi bi-geo-alt"></i>
                            <span><?= Html::encode($company['address'] ?? 'Минск, ул. Примерная, 1') ?></span>
                        </li>
                        <li>
                            <i class="bi bi-clock"></i>
                            <span>Пн-Вс: 10:00 - 22:00</span>
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
    color: #94a3b8;
    font-size: 0.6rem;
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
    font-size: 0.6rem;
    color: #94a3b8;
}

.badge-item i {
    color: #6366f1;
    font-size: 0.75rem;
}

.footer-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 0.5rem;
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
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.6rem;
    transition: color 0.2s;
}

.footer-links a:hover {
    color: #f8fafc;
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
    font-size: 0.6rem;
}

.footer-contacts i {
    color: #6366f1;
    font-size: 1rem;
    margin-top: 0.125rem;
}

.footer-contacts a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-contacts a:hover {
    color: #f8fafc;
}

.footer-social {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.social-link {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.3s;
}

.social-link:hover {
    background: #6366f1;
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
