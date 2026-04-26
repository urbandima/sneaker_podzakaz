<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var array $company */

$this->title = 'Контакты — СНИКЕРХЭД';
$company = $company ?? [];
$phone    = $company['phone'] ?? '+375 (29) 123-45-67';
$email    = $company['email'] ?? 'info@sneakerhead.by';
$address  = $company['address'] ?? 'Минск, ул. Купревича 1, корп. 1';
$workTime = $company['work_time'] ?? 'Пн–Вс: 10:00 — 20:00';
$this->params['breadcrumbs'][] = $this->title;

$css = <<<CSS
/* === CONTACTS PAGE === */
.contacts-page { padding: var(--space-12, 3rem) 0 var(--space-16, 4rem); }
.contacts-page .page-heading {
    margin-bottom: var(--space-10, 2.5rem);
}
.contacts-page .page-heading h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1;
    margin-bottom: var(--space-2, 0.5rem);
}
.contacts-page .page-heading p {
    font-size: var(--text-lg, 1.125rem);
    color: var(--color-text-secondary, #666);
}

/* Contact cards row */
.contact-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-4, 1rem);
    margin-bottom: var(--space-10, 2.5rem);
}
.contact-card {
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-6, 1.5rem);
    display: flex;
    flex-direction: column;
    gap: var(--space-3, 0.75rem);
    transition: box-shadow 0.2s, border-color 0.2s;
}
.contact-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.07);
    border-color: rgba(0,0,0,0.1);
}
.contact-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--color-bg-secondary, #f5f5f5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--color-text-primary, #111);
}
.contact-card h3 {
    font-size: var(--text-base, 1rem);
    font-weight: 700;
    margin: 0;
}
.contact-card p, .contact-card a {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    margin: 0;
    text-decoration: none;
    line-height: 1.5;
}
.contact-card a:hover {
    color: var(--color-text-primary, #111);
    text-decoration: underline;
}
.contact-card-note {
    font-size: 12px;
    color: var(--color-text-muted, #999);
}

/* Social links */
.social-section { margin-bottom: var(--space-10, 2.5rem); }
.social-section h2 {
    font-size: var(--text-xl, 1.25rem);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: var(--space-4, 1rem);
}
.social-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}
.social-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: var(--space-4, 1rem) var(--space-5, 1.25rem);
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-lg, 14px);
    text-decoration: none;
    color: var(--color-text-primary, #111);
    font-weight: 600;
    font-size: var(--text-sm, 0.875rem);
    transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
}
.social-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border-color: rgba(0,0,0,0.12);
    transform: translateY(-1px);
    color: var(--color-text-primary, #111);
}
.social-card i { font-size: 1.2rem; }
.social-card--instagram i { color: #E1306C; }
.social-card--telegram i   { color: #229ED9; }
.social-card--tiktok i     { color: #010101; }
.social-card--youtube i    { color: #FF0000; }

/* Contact form */
.contact-form-section { margin-bottom: var(--space-10, 2.5rem); }
.contact-form-section h2 {
    font-size: var(--text-xl, 1.25rem);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: var(--space-5, 1.25rem);
}
.contact-form-wrap {
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-6, 1.5rem);
}
.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4, 1rem);
    margin-bottom: var(--space-4, 1rem);
}
.contact-form .form-field { margin-bottom: var(--space-4, 1rem); }
.contact-form .form-field:last-child { margin-bottom: 0; }
.contact-form label {
    display: block;
    font-size: var(--text-sm, 0.875rem);
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--color-text-primary, #111);
}
.contact-form input,
.contact-form textarea,
.contact-form select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-md, 8px);
    font-size: var(--text-sm, 0.875rem);
    background: var(--color-bg-primary, #fff);
    color: var(--color-text-primary, #111);
    transition: border-color 0.15s;
    outline: none;
    font-family: inherit;
}
.contact-form input:focus,
.contact-form textarea:focus {
    border-color: var(--c-black, #111);
}
.contact-form textarea { resize: vertical; min-height: 120px; line-height: 1.5; }
.contact-form-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--c-black, #111);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: var(--radius-md, 8px);
    font-weight: 700;
    font-size: var(--text-base, 1rem);
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
    margin-top: var(--space-2, 0.5rem);
    font-family: inherit;
}
.contact-form-submit:hover { opacity: 0.85; transform: translateY(-1px); }

/* FAQ */
.faq-section { margin-bottom: var(--space-10, 2.5rem); }
.faq-section h2 {
    font-size: var(--text-xl, 1.25rem);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: var(--space-4, 1rem);
}
.faq-list { display: flex; flex-direction: column; gap: 8px; }
.faq-item {
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-lg, 14px);
    overflow: hidden;
}
.faq-question {
    width: 100%;
    background: none;
    border: none;
    padding: var(--space-4, 1rem) var(--space-5, 1.25rem);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: var(--text-sm, 0.875rem);
    font-weight: 600;
    color: var(--color-text-primary, #111);
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: background 0.15s;
}
.faq-question:hover { background: var(--color-bg-secondary, #f5f5f5); }
.faq-question i { flex-shrink: 0; transition: transform 0.2s; font-size: 0.75rem; }
.faq-item.open .faq-question i { transform: rotate(180deg); }
.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s ease, padding 0.2s;
}
.faq-item.open .faq-answer { max-height: 300px; }
.faq-answer-inner {
    padding: 0 var(--space-5, 1.25rem) var(--space-4, 1rem);
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    line-height: 1.6;
}

/* Address block */
.address-section h2 {
    font-size: var(--text-xl, 1.25rem);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: var(--space-4, 1rem);
}
.address-card {
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-8, 2rem);
    display: flex;
    align-items: center;
    gap: var(--space-6, 1.5rem);
    flex-wrap: wrap;
}
.address-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--c-black, #111);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.address-info h3 {
    font-size: var(--text-lg, 1.125rem);
    font-weight: 700;
    margin-bottom: 4px;
}
.address-info p {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    margin: 0 0 4px;
}

@media (max-width: 640px) {
    .contact-form .form-row { grid-template-columns: 1fr; }
    .address-card { flex-direction: column; align-items: flex-start; }
}
.page-edit-admin-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#0f0f0f;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;opacity:.7;transition:opacity .15s}
.page-edit-admin-btn:hover{opacity:1;color:#fff}
CSS;
$this->registerCss($css);
?>

<div class="contacts-page">
    <div class="container">
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity && Yii::$app->user->identity->isAdmin()): ?>
        <div style="margin-bottom:1rem;text-align:right">
            <a href="/admin/page/edit?slug=contacts" class="page-edit-admin-btn" target="_blank">
                <i class="bi bi-pencil-square"></i> Редактировать страницу
            </a>
        </div>
        <?php endif; ?>
        <div class="page-heading">
            <h1>Контакты</h1>
            <p>Мы всегда на связи и готовы ответить на ваши вопросы</p>
        </div>

        <!-- Contact info cards -->
        <div class="contact-cards">
            <?php if ($phone): ?>
            <div class="contact-card">
                <div class="contact-card-icon"><i class="bi bi-telephone-fill"></i></div>
                <h3>Телефон</h3>
                <a href="tel:<?= preg_replace('/[^\d+]/', '', $phone) ?>"><?= Html::encode($phone) ?></a>
                <span class="contact-card-note"><?= Html::encode($workTime) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($email): ?>
            <div class="contact-card">
                <div class="contact-card-icon"><i class="bi bi-envelope-fill"></i></div>
                <h3>Email</h3>
                <a href="mailto:<?= Html::encode($email) ?>"><?= Html::encode($email) ?></a>
                <span class="contact-card-note">Ответ в течение 24 часов</span>
            </div>
            <?php endif; ?>
            <div class="contact-card">
                <div class="contact-card-icon"><i class="bi bi-telegram"></i></div>
                <h3>Telegram</h3>
                <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" rel="noopener">@sneakerheadbyweb_bot</a>
                <span class="contact-card-note">Быстрые ответы 24/7</span>
            </div>
            <?php if ($address): ?>
            <div class="contact-card">
                <div class="contact-card-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <h3>Адрес</h3>
                <p><?= Html::encode($address) ?></p>
                <span class="contact-card-note">Пункт выдачи заказов</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Social -->
        <section class="social-section">
            <h2>Мы в социальных сетях</h2>
            <div class="social-grid">
                <a href="https://instagram.com/sneakerhead_by" target="_blank" rel="noopener" class="social-card social-card--instagram">
                    <i class="bi bi-instagram"></i> Instagram
                </a>
                <a href="https://t.me/sneakerhead_by" target="_blank" rel="noopener" class="social-card social-card--telegram">
                    <i class="bi bi-telegram"></i> Telegram-канал
                </a>
                <a href="https://tiktok.com/@sneakerhead_by" target="_blank" rel="noopener" class="social-card social-card--tiktok">
                    <i class="bi bi-tiktok"></i> TikTok
                </a>
                <a href="https://youtube.com/@sneakerheadby" target="_blank" rel="noopener" class="social-card social-card--youtube">
                    <i class="bi bi-youtube"></i> YouTube
                </a>
            </div>
        </section>

        <!-- Contact form -->
        <section class="contact-form-section">
            <h2>Напишите нам</h2>
            <div class="contact-form-wrap">
                <form class="contact-form" onsubmit="submitContactForm(event)">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="cf-name">Ваше имя</label>
                            <input type="text" id="cf-name" placeholder="Иван Иванов" autocomplete="name">
                        </div>
                        <div class="form-field">
                            <label for="cf-email">Email</label>
                            <input type="email" id="cf-email" placeholder="ivan@example.com" required autocomplete="email">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="cf-subject">Тема</label>
                        <input type="text" id="cf-subject" placeholder="Вопрос о заказе" required>
                    </div>
                    <div class="form-field">
                        <label for="cf-message">Сообщение</label>
                        <textarea id="cf-message" placeholder="Опишите ваш вопрос..." required></textarea>
                    </div>
                    <button type="submit" class="contact-form-submit">
                        <i class="bi bi-send"></i> Отправить
                    </button>
                </form>
            </div>
        </section>

        <!-- FAQ -->
        <section class="faq-section">
            <h2>Частые вопросы</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Как проверить оригинальность кроссовок?
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Все наши кроссовки проходят проверку подлинности. Мы предоставляем сертификаты качества и гарантируем оригинальность каждого товара. Также вы можете воспользоваться приложением CheckCheck или Legit App для самостоятельной проверки.
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Сколько времени занимает доставка?
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Доставка по Беларуси занимает 2–5 рабочих дней. Курьерская доставка по Минску — 1–2 дня. Самовывоз из пункта выдачи — в день поступления заказа.
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Можно ли вернуть или обменять товар?
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Да, вы можете вернуть или обменять товар в течение 14 дней после получения при сохранении товарного вида и упаковки. Подробнее в разделе <a href="<?= Url::to(['/page/return-policy']) ?>">Возврат и обмен</a>.
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Как оплатить заказ на юридическое лицо?
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Мы выставляем счёт на организацию. Подробная инструкция по оплате для всех банков Беларуси — в разделе <a href="<?= Url::to(['/site/payment-instruction']) ?>">Инструкция по оплате</a>.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Address -->
        <?php if ($address): ?>
        <section class="address-section">
            <h2>Наш адрес</h2>
            <div class="address-card">
                <div class="address-icon-wrap">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div class="address-info">
                    <h3><?= Html::encode($address) ?></h3>
                    <p>Пункт выдачи заказов: <?= Html::encode($workTime) ?></p>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFaq(btn) {
    var item = btn.closest('.faq-item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(function(el) { el.classList.remove('open'); });
    if (!isOpen) item.classList.add('open');
}

function submitContactForm(e) {
    e.preventDefault();
    var btn = e.target.querySelector('.contact-form-submit');
    btn.innerHTML = '<i class="bi bi-check-lg"></i> Отправлено!';
    btn.disabled = true;
    setTimeout(function() {
        btn.innerHTML = '<i class="bi bi-send"></i> Отправить';
        btn.disabled = false;
        e.target.reset();
    }, 3000);
}
</script>
