<?php

/**
 * Footer - Полный футер сайта
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
                    <h3 class="footer-title">О компании</h3>
                    <ul class="footer-links">
                        <li><?= Html::a('О нас', ['/page/about']) ?></li>
                        <li><?= Html::a('Контакты', ['/page/contacts']) ?></li>
                        <li><?= Html::a('Договор оферты', ['/site/offer-agreement']) ?></li>
                        <li><?= Html::a('Инструкция по оплате', ['/site/payment-instruction']) ?></li>
                        <li><?= Html::a('Политика конфиденциальности', ['/page/privacy']) ?></li>
                        <li><?= Html::a('Реквизиты', ['/page/about']) ?></li>
                    </ul>
                </div>

                <!-- Помощь -->
                <div class="footer-column">
                    <h3 class="footer-title">Помощь</h3>
                    <ul class="footer-links">
                        <li><?= Html::a('Инструкция по оплате', ['/site/payment-instruction']) ?></li>
                        <li><?= Html::a('Условия оплаты', ['/page/payment-terms']) ?></li>
                        <li><?= Html::a('Условия доставки', ['/page/delivery-terms']) ?></li>
                        <li><?= Html::a('Возврат и обмен', ['/page/return-policy']) ?></li>
                        <li><?= Html::a('Часто задаваемые вопросы', ['/contacts']) ?></li>
                        <li><?= Html::a('Отслеживание заказа', ['/account/orders']) ?></li>
                    </ul>
                </div>

                <!-- Каталог -->
                <div class="footer-column">
                    <h3 class="footer-title">Каталог</h3>
                    <ul class="footer-links">
                        <li><a href="<?= Url::to(['/catalog']) ?>">Все товары</a></li>
                        <li><a href="<?= Url::to(['/brands']) ?>">Бренды</a></li>
                        <li><a href="<?= Url::to(['/catalog', 'category' => 'sneakers']) ?>">Кроссовки</a></li>
                        <li><a href="<?= Url::to(['/catalog', 'category' => 'boots']) ?>">Ботинки</a></li>
                        <li><a href="<?= Url::to(['/catalog', 'sort' => 'sale']) ?>">Распродажа</a></li>
                        <li><a href="<?= Url::to(['/catalog', 'sort' => 'new']) ?>">Новинки</a></li>
                    </ul>
                </div>

                <!-- Контакты -->
                <div class="footer-column">
                    <h3 class="footer-title">Контакты</h3>
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

                        <!-- Visa -->
                        <span class="payment-icon payment-icon--visa" role="img" title="Visa" aria-label="Visa">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 30" width="48" height="30" fill="none">
                                <rect width="48" height="30" rx="4" fill="#1A1F71"/>
                                <path d="M19.5 21H16.7L18.5 9H21.3L19.5 21Z" fill="white"/>
                                <path d="M30.2 9.3C29.6 9.1 28.7 9 27.6 9C24.9 9 22.9 10.3 22.9 12.2C22.9 13.7 24.3 14.5 25.4 15C26.5 15.5 26.9 15.9 26.9 16.4C26.9 17.2 25.9 17.6 25 17.6C23.8 17.6 23.1 17.4 22.1 17L21.7 16.8L21.3 19.4C22 19.7 23.3 20 24.6 20C27.5 20 29.5 18.7 29.5 16.7C29.5 15.6 28.8 14.7 27.2 14C26.2 13.5 25.6 13.2 25.6 12.7C25.6 12.2 26.2 11.7 27.3 11.7C28.2 11.7 28.8 11.9 29.3 12.1L29.6 12.2L30.2 9.3Z" fill="white"/>
                                <path d="M35.4 9H33.2C32.5 9 32 9.2 31.7 9.9L27.8 21H30.7L31.3 19.2H34.8L35.1 21H37.7L35.4 9ZM32.1 17C32.3 16.4 33.3 13.7 33.3 13.7C33.3 13.7 33.6 12.9 33.8 12.4L34 13.6C34 13.6 34.6 16.3 34.8 17H32.1Z" fill="white"/>
                                <path d="M14.2 9L11.5 17.3L11.2 15.9C10.7 14.2 9.1 12.3 7.3 11.3L9.8 21H12.7L17 9H14.2Z" fill="white"/>
                                <path d="M8.9 9H4.3L4.2 9.2C8 10.1 10.5 12.4 11.2 15.9L10.4 9.9C10.3 9.2 9.7 9 8.9 9Z" fill="#F9A533"/>
                            </svg>
                        </span>

                        <!-- Mastercard -->
                        <span class="payment-icon payment-icon--mc" role="img" title="Mastercard" aria-label="Mastercard">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 30" width="48" height="30" fill="none">
                                <rect width="48" height="30" rx="4" fill="#252525"/>
                                <circle cx="19" cy="15" r="8" fill="#EB001B"/>
                                <circle cx="29" cy="15" r="8" fill="#F79E1B"/>
                                <path d="M24 8.8A8 8 0 0 1 28.2 15 8 8 0 0 1 24 21.2 8 8 0 0 1 19.8 15 8 8 0 0 1 24 8.8Z" fill="#FF5F00"/>
                            </svg>
                        </span>

                        <!-- Белкарт -->
                        <span class="payment-icon payment-icon--belcard" role="img" title="Белкарт" aria-label="Белкарт">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 30" width="48" height="30" fill="none">
                                <rect width="48" height="30" rx="4" fill="#005BAA"/>
                                <rect x="0" y="20" width="48" height="10" rx="0" fill="#C8102E"/>
                                <rect x="0" y="20" width="48" height="4" rx="0" fill="#C8102E"/>
                                <text x="8" y="17" font-family="Arial, sans-serif" font-size="9" font-weight="700" fill="white" letter-spacing="0.5">БЕЛКАРТ</text>
                            </svg>
                        </span>

                        <!-- Халва -->
                        <span class="payment-icon payment-icon--halva" role="img" title="Халва" aria-label="Халва">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 30" width="48" height="30" fill="none">
                                <rect width="48" height="30" rx="4" fill="#6B3FA0"/>
                                <circle cx="24" cy="13" r="7" fill="none" stroke="#FFD700" stroke-width="2"/>
                                <path d="M20 13 Q24 9 28 13 Q24 17 20 13Z" fill="#FFD700"/>
                                <text x="50%" y="26" font-family="Arial, sans-serif" font-size="7" font-weight="700" fill="white" text-anchor="middle">ХАЛВА</text>
                            </svg>
                        </span>

                        <!-- ЕРИП -->
                        <span class="payment-icon payment-icon--erip" role="img" title="ЕРИП" aria-label="ЕРИП">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 30" width="48" height="30" fill="none">
                                <rect width="48" height="30" rx="4" fill="#009A44"/>
                                <rect x="6" y="8" width="36" height="14" rx="2" fill="none" stroke="white" stroke-width="1.5"/>
                                <line x1="14" y1="8" x2="14" y2="22" stroke="white" stroke-width="1.5"/>
                                <text x="19" y="18.5" font-family="Arial, sans-serif" font-size="8" font-weight="700" fill="white">ЕРИП</text>
                            </svg>
                        </span>

                    </div>
                </div>

                <div class="copyright">
                    © <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.
                </div>
            </div>
        </div>
    </div>
</footer>
