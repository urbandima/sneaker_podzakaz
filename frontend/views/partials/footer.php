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
                    <h4 class="footer-title">О компании</h4>
                    <ul class="footer-links">
                        <li><?= Html::a('О нас', ['/page/about']) ?></li>
                        <li><?= Html::a('Контакты', ['/page/contacts']) ?></li>
                        <li><?= Html::a('Договор оферты', ['/site/offer-agreement']) ?></li>
                        <li><?= Html::a('Инструкция по оплате', ['/site/payment-instruction']) ?></li>
                        <li><?= Html::a('Политика конфиденциальности', ['/page/privacy']) ?></li>
                        <li><?= Html::a('Реквизиты', ['/page/about']) ?></li>
                        <li style="margin-top:0.5rem"><?= Html::a('<i class="bi bi-chat-heart"></i>&nbsp; Написать директору', ['/feedback'], ['encode' => false, 'style' => 'color:#e11d48;font-weight:600']) ?></li>
                    </ul>
                </div>

                <!-- Помощь -->
                <div class="footer-column">
                    <h4 class="footer-title">Помощь</h4>
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
                    <h4 class="footer-title">Каталог</h4>
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
                    <h4 class="footer-title">Контакты</h4>
                    <ul class="footer-contacts">
                        <?php if (!empty($company['phone'])): ?>
                        <li>
                            <i class="bi bi-telephone"></i>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $company['phone']) ?>">
                                <?= Html::encode($company['phone']) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($company['email'])): ?>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:<?= Html::encode($company['email']) ?>">
                                <?= Html::encode($company['email']) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($company['address'])): ?>
                        <li>
                            <i class="bi bi-geo-alt"></i>
                            <span><?= Html::encode($company['address']) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($company['work_time'])): ?>
                        <li>
                            <i class="bi bi-clock"></i>
                            <span><?= Html::encode($company['work_time']) ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <?php
                    $s = Yii::$app->settings;
                    $socialLinks = [
                        'instagram' => ['icon' => 'bi-instagram', 'label' => 'Instagram'],
                        'telegram'  => ['icon' => 'bi-telegram',  'label' => 'Telegram'],
                        'tiktok'    => ['icon' => 'bi-tiktok',    'label' => 'TikTok'],
                        'youtube'   => ['icon' => 'bi-youtube',   'label' => 'YouTube'],
                        'vk'        => ['icon' => 'bi-vk',        'label' => 'VK'],
                    ];
                    $hasSocial = false;
                    foreach ($socialLinks as $key => $meta) {
                        $url = $s->get('social', $key, '');
                        if (!empty($url) && $url !== '#' && strlen($url) > 5) { $hasSocial = true; break; }
                    }
                    if ($hasSocial): ?>
                    <div class="footer-social">
                        <?php foreach ($socialLinks as $key => $meta):
                            $url = $s->get('social', $key, '');
                            if (empty($url) || $url === '#' || strlen($url) <= 5) continue; ?>
                            <a href="<?= Html::encode($url) ?>" target="_blank" rel="noopener noreferrer"
                               class="social-link" aria-label="<?= $meta['label'] ?>">
                                <i class="bi <?= $meta['icon'] ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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

                        <!-- Visa — official colours #1A1F71 wordmark -->
                        <span class="payment-icon payment-icon--visa" title="Visa" aria-label="Visa">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 780 500" width="48" height="30">
                                <rect width="780" height="500" rx="40" fill="#1A1F71"/>
                                <text x="390" y="330" font-family="'Helvetica Neue',Helvetica,Arial,sans-serif" font-size="280" font-weight="900" fill="#FFFFFF" text-anchor="middle" letter-spacing="-10">VISA</text>
                            </svg>
                        </span>

                        <!-- Mastercard — two overlapping circles -->
                        <span class="payment-icon payment-icon--mc" title="Mastercard" aria-label="Mastercard">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 780 500" width="48" height="30">
                                <rect width="780" height="500" rx="40" fill="#252525"/>
                                <circle cx="290" cy="250" r="160" fill="#EB001B"/>
                                <circle cx="490" cy="250" r="160" fill="#F79E1B"/>
                                <path d="M390 121a160 160 0 0 1 0 258 160 160 0 0 1 0-258z" fill="#FF5F00"/>
                            </svg>
                        </span>

                        <!-- Белкарт — blue/red stripes wordmark -->
                        <span class="payment-icon payment-icon--belcard" title="Белкарт" aria-label="Белкарт">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 780 500" width="48" height="30">
                                <rect width="780" height="500" rx="40" fill="#FFFFFF"/>
                                <rect y="0" width="780" height="167" rx="0" fill="#005BAA"/>
                                <rect y="167" width="780" height="167" fill="#FFFFFF"/>
                                <rect y="334" width="780" height="166" rx="0" fill="#C8102E"/>
                                <text x="390" y="295" font-family="'Arial',sans-serif" font-size="150" font-weight="800" fill="#005BAA" text-anchor="middle" letter-spacing="2">БЕЛКАРТ</text>
                            </svg>
                        </span>

                        <!-- Халва (Беларусбанк) — purple card -->
                        <span class="payment-icon payment-icon--halva" title="Халва" aria-label="Халва">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 780 500" width="48" height="30">
                                <rect width="780" height="500" rx="40" fill="#5B2D8E"/>
                                <text x="390" y="310" font-family="'Arial',sans-serif" font-size="200" font-weight="900" fill="#FFFFFF" text-anchor="middle">Халва</text>
                            </svg>
                        </span>

                        <!-- ЕРИП — green -->
                        <span class="payment-icon payment-icon--erip" title="ЕРИП" aria-label="ЕРИП">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 780 500" width="48" height="30">
                                <rect width="780" height="500" rx="40" fill="#009A44"/>
                                <text x="390" y="330" font-family="'Arial',sans-serif" font-size="260" font-weight="900" fill="#FFFFFF" text-anchor="middle" letter-spacing="10">ЕРИП</text>
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
