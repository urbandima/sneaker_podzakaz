<?php

use yii\helpers\Html;

?>

<!-- Footer -->
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
                        <li><a href="/about">О нас</a></li>
                        <li><a href="/contacts">Контакты</a></li>
                        <li><a href="/delivery">Доставка</a></li>
                        <li><a href="/return">Возврат</a></li>
                        <li><a href="/privacy">Политика конфиденциальности</a></li>
                        <li><a href="/terms">Условия использования</a></li>
                    </ul>
                </div>
                
                <!-- Помощь -->
                <div class="footer-column">
                    <h4 class="footer-title">Помощь</h4>
                    <ul class="footer-links">
                        <li><a href="/faq">Частые вопросы</a></li>
                        <li><a href="/size-guide">Размерная сетка</a></li>
                        <li><a href="/payment">Оплата</a></li>
                        <li><a href="/guarantee">Гарантия</a></li>
                        <li><a href="/loyalty">Программа лояльности</a></li>
                        <li><a href="/tracking">Отследить заказ</a></li>
                    </ul>
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
                            <span><?= Html::encode($company['address'] ?? 'Минск, Беларусь') ?></span>
                        </li>
                    </ul>
                    
                    <!-- Социальные сети -->
                    <div class="footer-social">
                        <a href="<?= $company['instagram'] ?? '#' ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="<?= $company['telegram'] ?? '#' ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">
                            <i class="bi bi-telegram"></i>
                        </a>
                        <a href="<?= $company['viber'] ?? '#' ?>" target="_blank" rel="noopener noreferrer" aria-label="Viber">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <div class="footer-payment">
        <div class="container">
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
        </div>
    </div>
    
    <!-- Copyright -->
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>. Все права защищены.</p>
        </div>
    </div>
</footer>
