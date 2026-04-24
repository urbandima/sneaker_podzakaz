<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Скидки и акции';
$this->params['breadcrumbs'][] = $this->title;

$css = <<<CSS
/* === SALE PAGE === */
.sale-hero {
    background: var(--c-black, #111);
    color: var(--c-white, #fff);
    padding: var(--space-16, 4rem) var(--space-6, 1.5rem);
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: var(--space-12, 3rem);
}
.sale-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% -10%, rgba(248,113,113,0.22) 0%, transparent 65%);
    pointer-events: none;
}
.sale-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(248,113,113,0.15);
    border: 1px solid rgba(248,113,113,0.3);
    color: #f87171;
    padding: 4px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: var(--space-4, 1rem);
}
.sale-hero h1 {
    font-size: clamp(3rem, 10vw, 6rem);
    font-weight: 900;
    letter-spacing: -0.04em;
    line-height: 0.9;
    margin-bottom: var(--space-4, 1rem);
}
.sale-hero h1 span { color: #f87171; }
.sale-hero-sub {
    font-size: var(--text-lg, 1.125rem);
    color: rgba(255,255,255,0.6);
    margin-bottom: var(--space-8, 2rem);
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
}
.sale-hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f87171;
    color: white;
    padding: 14px 28px;
    border-radius: var(--radius-md, 8px);
    font-weight: 700;
    font-size: var(--text-base, 1rem);
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}
.sale-hero-cta:hover {
    background: #ef4444;
    transform: translateY(-1px);
    color: white;
}

/* Sections */
.sale-section { margin-bottom: var(--space-12, 3rem); }
.sale-section-title {
    font-size: var(--text-2xl, 1.5rem);
    font-weight: var(--font-weight-bold, 700);
    letter-spacing: -0.02em;
    margin-bottom: var(--space-6, 1.5rem);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Promo cards */
.promos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-4, 1rem);
}
.promo-card {
    background: var(--color-bg-primary, #fff);
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-6, 1.5rem);
    display: flex;
    flex-direction: column;
    gap: var(--space-4, 1rem);
    transition: box-shadow 0.2s, border-color 0.2s;
}
.promo-card:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    border-color: rgba(0,0,0,0.12);
}
.promo-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.promo-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: var(--text-sm, 0.875rem);
    font-weight: 700;
    white-space: nowrap;
}
.promo-badge--red    { background: #fef2f2; color: #ef4444; }
.promo-badge--green  { background: #f0fdf4; color: #16a34a; }
.promo-badge--blue   { background: #eff6ff; color: #2563eb; }
.promo-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--color-bg-secondary, #f5f5f5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.promo-card h3 {
    font-size: var(--text-lg, 1.125rem);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: 4px;
}
.promo-card > div > p {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    line-height: 1.5;
    margin: 0;
}
.promo-card-meta {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.promo-card-meta li {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    display: flex;
    align-items: center;
    gap: 8px;
}
.promo-card-meta li i { color: #16a34a; font-size: 0.8rem; }
.promo-card-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: var(--text-sm, 0.875rem);
    font-weight: 600;
    color: var(--color-text-primary, #111);
    text-decoration: none;
    border-bottom: 1px solid currentColor;
    padding-bottom: 1px;
    width: fit-content;
    margin-top: auto;
    transition: opacity 0.15s;
}
.promo-card-link:hover { opacity: 0.6; color: var(--color-text-primary, #111); }

/* Coupon steps */
.coupon-steps {
    display: flex;
    gap: var(--space-6, 1.5rem);
    flex-wrap: wrap;
    padding: var(--space-5, 1.25rem) var(--space-6, 1.5rem);
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-xl, 20px);
    margin-bottom: var(--space-5, 1.25rem);
}
.coupon-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex: 1;
    min-width: 160px;
}
.coupon-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--c-black, #111);
    color: white;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.coupon-step-text { font-size: var(--text-sm, 0.875rem); color: var(--color-text-secondary, #666); line-height: 1.4; }
.coupon-step-text strong { color: var(--color-text-primary, #111); display: block; margin-bottom: 2px; font-weight: 600; }

/* Coupon cards */
.coupon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}
.coupon-card {
    background: var(--color-bg-primary, #fff);
    border: 1.5px dashed var(--color-border, #e5e5e5);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-5, 1.25rem);
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    position: relative;
    overflow: hidden;
}
.coupon-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--c-black, #111);
}
.coupon-card:hover {
    border-color: var(--c-black, #111);
    background: var(--color-bg-secondary, #f9f9f9);
}
.coupon-code {
    font-size: var(--text-xl, 1.25rem);
    font-weight: 900;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    font-family: monospace;
    color: var(--color-text-primary, #111);
}
.coupon-desc {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    margin-bottom: 12px;
    line-height: 1.4;
}
.coupon-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 3px 10px;
    border-radius: 999px;
}
.coupon-status--active  { background: #f0fdf4; color: #16a34a; }
.coupon-status--request { background: #fef9c3; color: #854d0e; }
.coupon-copy-hint {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.85);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: var(--text-sm, 0.875rem);
    opacity: 0;
    transition: opacity 0.18s;
    border-radius: calc(var(--radius-xl, 20px) - 2px);
}
.coupon-card:hover .coupon-copy-hint { opacity: 1; }

/* Loyalty */
.loyalty-card {
    background: var(--c-black, #111);
    color: var(--c-white, #fff);
    border-radius: var(--radius-xl, 20px);
    padding: var(--space-8, 2rem);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--space-6, 1.5rem);
    align-items: center;
}
.loyalty-title {
    font-size: var(--text-2xl, 1.5rem);
    font-weight: 900;
    letter-spacing: -0.02em;
    line-height: 1.1;
    margin-bottom: 8px;
}
.loyalty-sub {
    color: rgba(255,255,255,0.55);
    font-size: var(--text-sm, 0.875rem);
    margin-bottom: var(--space-5, 1.25rem);
}
.loyalty-perks {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: var(--space-6, 1.5rem);
}
.loyalty-perk {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: var(--text-sm, 0.875rem);
    color: rgba(255,255,255,0.8);
}
.loyalty-perk i { color: #fbbf24; font-size: 0.75rem; }
.loyalty-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.loyalty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: var(--radius-md, 8px);
    font-size: var(--text-sm, 0.875rem);
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}
.loyalty-btn--primary  { background: white; color: black; }
.loyalty-btn--secondary { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); }
.loyalty-btn:hover { opacity: 0.82; }
.loyalty-stat { text-align: center; }
.loyalty-stat-number {
    font-size: 4.5rem;
    font-weight: 900;
    letter-spacing: -0.04em;
    line-height: 1;
    color: #fbbf24;
}
.loyalty-stat-label {
    font-size: 11px;
    color: rgba(255,255,255,0.45);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 4px;
    line-height: 1.4;
}

/* Terms */
.terms-card {
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 14px);
    padding: var(--space-6, 1.5rem);
}
.terms-card h3 {
    font-size: var(--text-base, 1rem);
    font-weight: 600;
    margin-bottom: var(--space-4, 1rem);
    display: flex;
    align-items: center;
    gap: 8px;
}
.terms-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.terms-list li {
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    display: flex;
    align-items: flex-start;
    gap: 8px;
    line-height: 1.5;
}
.terms-list li::before { content: '—'; color: var(--color-text-muted, #999); flex-shrink: 0; }

@media (max-width: 768px) {
    .sale-hero { padding: 3rem 1.25rem; }
    .sale-hero h1 { font-size: 3.5rem; }
    .loyalty-card { grid-template-columns: 1fr; }
    .loyalty-stat { display: none; }
    .coupon-steps { flex-direction: column; gap: 12px; }
}
.page-edit-admin-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#0f0f0f;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;opacity:.7;transition:opacity .15s}
.page-edit-admin-btn:hover{opacity:1;color:#fff}
CSS;
$this->registerCss($css);
?>

<div class="sale-page">
    <!-- Hero -->
    <div class="sale-hero">
        <div class="sale-hero-badge"><i class="bi bi-fire"></i> Горячие скидки</div>
        <h1>СКИДКИ<br>ДО <span>50%</span></h1>
        <p class="sale-hero-sub">Оригинальные кроссовки Nike, Adidas, Jordan и New Balance по лучшим ценам</p>
        <a href="<?= Url::to(['/catalog', 'sort' => 'sale']) ?>" class="sale-hero-cta">
            Смотреть все скидки <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="container">
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity && Yii::$app->user->identity->isAdmin()): ?>
        <div style="margin-bottom:1rem;text-align:right">
            <a href="/admin/page/edit?slug=sale" class="page-edit-admin-btn" target="_blank">
                <i class="bi bi-pencil-square"></i> Редактировать страницу
            </a>
        </div>
        <?php endif; ?>
        <!-- Current promos -->
        <section class="sale-section">
            <h2 class="sale-section-title">
                <i class="bi bi-lightning-charge-fill" style="color:#f87171"></i> Текущие акции
            </h2>
            <div class="promos-grid">
                <div class="promo-card">
                    <div class="promo-card-top">
                        <span class="promo-badge promo-badge--red">−20%</span>
                        <div class="promo-card-icon"><i class="bi bi-gift"></i></div>
                    </div>
                    <div>
                        <h3>Скидка на первый заказ</h3>
                        <p>Дарим скидку 20% на первый заказ для новых клиентов</p>
                    </div>
                    <ul class="promo-card-meta">
                        <li><i class="bi bi-check-circle-fill"></i> Минимальная сумма: 50 BYN</li>
                        <li><i class="bi bi-check-circle-fill"></i> Промокод: NEW20</li>
                        <li><i class="bi bi-check-circle-fill"></i> Действует до 31.12.2026</li>
                    </ul>
                    <a href="<?= Url::to(['/catalog']) ?>" class="promo-card-link">
                        Перейти в каталог <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="promo-card">
                    <div class="promo-card-top">
                        <span class="promo-badge promo-badge--green">Бесплатно</span>
                        <div class="promo-card-icon"><i class="bi bi-truck"></i></div>
                    </div>
                    <div>
                        <h3>Бесплатная доставка</h3>
                        <p>При заказе от 100 BYN — доставка по всей Беларуси за наш счёт</p>
                    </div>
                    <ul class="promo-card-meta">
                        <li><i class="bi bi-check-circle-fill"></i> Курьерская доставка</li>
                        <li><i class="bi bi-check-circle-fill"></i> Европочта ПВЗ</li>
                        <li><i class="bi bi-check-circle-fill"></i> Белпочта</li>
                    </ul>
                    <a href="<?= Url::to(['/page/delivery-terms']) ?>" class="promo-card-link">
                        Условия доставки <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="promo-card">
                    <div class="promo-card-top">
                        <span class="promo-badge promo-badge--blue">Возврат 14 дней</span>
                        <div class="promo-card-icon"><i class="bi bi-arrow-return-left"></i></div>
                    </div>
                    <div>
                        <h3>Лёгкий возврат</h3>
                        <p>14 дней на возврат без объяснения причин — гарантируем оригинальность каждого товара</p>
                    </div>
                    <ul class="promo-card-meta">
                        <li><i class="bi bi-check-circle-fill"></i> Без вопросов и бюрократии</li>
                        <li><i class="bi bi-check-circle-fill"></i> Деньги вернём в течение 5 дней</li>
                    </ul>
                    <a href="<?= Url::to(['/page/return-policy']) ?>" class="promo-card-link">
                        Условия возврата <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Coupon codes -->
        <section class="sale-section">
            <h2 class="sale-section-title">
                <i class="bi bi-ticket-perforated" style="color:#f59e0b"></i> Промокоды
            </h2>

            <div class="coupon-steps">
                <div class="coupon-step">
                    <div class="coupon-step-num">1</div>
                    <div class="coupon-step-text">
                        <strong>Выберите товар</strong>Добавьте в корзину
                    </div>
                </div>
                <div class="coupon-step">
                    <div class="coupon-step-num">2</div>
                    <div class="coupon-step-text">
                        <strong>Кликните на промокод</strong>Он скопируется в буфер обмена
                    </div>
                </div>
                <div class="coupon-step">
                    <div class="coupon-step-num">3</div>
                    <div class="coupon-step-text">
                        <strong>Введите при оформлении</strong>Вставьте в поле «Промокод»
                    </div>
                </div>
            </div>

            <div class="coupon-grid">
                <div class="coupon-card" onclick="copyCoupon(this, 'NEW20')">
                    <div class="coupon-copy-hint"><i class="bi bi-clipboard-check"></i>&nbsp;Скопировать</div>
                    <div class="coupon-code">NEW20</div>
                    <div class="coupon-desc">−20% на первый заказ</div>
                    <span class="coupon-status coupon-status--active">
                        <i class="bi bi-circle-fill" style="font-size:5px"></i> Активен
                    </span>
                </div>
                <div class="coupon-card" onclick="copyCoupon(this, 'SUMMER2025')">
                    <div class="coupon-copy-hint"><i class="bi bi-clipboard-check"></i>&nbsp;Скопировать</div>
                    <div class="coupon-code">SUMMER2025</div>
                    <div class="coupon-desc">−15% на летние модели</div>
                    <span class="coupon-status coupon-status--active">
                        <i class="bi bi-circle-fill" style="font-size:5px"></i> Активен
                    </span>
                </div>
                <div class="coupon-card" onclick="copyCoupon(this, 'FREESHIP')">
                    <div class="coupon-copy-hint"><i class="bi bi-clipboard-check"></i>&nbsp;Скопировать</div>
                    <div class="coupon-code">FREESHIP</div>
                    <div class="coupon-desc">Бесплатная доставка</div>
                    <span class="coupon-status coupon-status--active">
                        <i class="bi bi-circle-fill" style="font-size:5px"></i> Активен
                    </span>
                </div>
                <div class="coupon-card" style="cursor:default">
                    <div class="coupon-code">VIP2025</div>
                    <div class="coupon-desc">−25% для VIP клиентов</div>
                    <span class="coupon-status coupon-status--request">
                        <i class="bi bi-clock" style="font-size:9px"></i>&nbsp;По запросу
                    </span>
                </div>
            </div>
        </section>

        <!-- Loyalty program -->
        <section class="sale-section">
            <h2 class="sale-section-title">
                <i class="bi bi-star-fill" style="color:#fbbf24"></i> Программа лояльности
            </h2>
            <div class="loyalty-card">
                <div>
                    <div class="loyalty-title">Зарабатывай баллы<br>за каждую покупку</div>
                    <div class="loyalty-sub">Каждая покупка приближает вас к следующей скидке</div>
                    <div class="loyalty-perks">
                        <div class="loyalty-perk">
                            <i class="bi bi-star-fill"></i> 10 баллов за каждый 1 BYN покупки
                        </div>
                        <div class="loyalty-perk">
                            <i class="bi bi-star-fill"></i> 100 баллов за регистрацию
                        </div>
                        <div class="loyalty-perk">
                            <i class="bi bi-star-fill"></i> 50 баллов за отзыв о товаре
                        </div>
                        <div class="loyalty-perk">
                            <i class="bi bi-star-fill"></i> 1 балл = 0.01 BYN скидки
                        </div>
                    </div>
                    <div class="loyalty-actions">
                        <a href="<?= Url::to(['/account']) ?>" class="loyalty-btn loyalty-btn--primary">
                            <i class="bi bi-person"></i> Личный кабинет
                        </a>
                        <a href="<?= Url::to(['/catalog']) ?>" class="loyalty-btn loyalty-btn--secondary">
                            <i class="bi bi-bag"></i> В каталог
                        </a>
                    </div>
                </div>
                <div class="loyalty-stat">
                    <div class="loyalty-stat-number">×10</div>
                    <div class="loyalty-stat-label">баллов<br>за покупку</div>
                </div>
            </div>
        </section>

        <!-- Terms -->
        <section class="sale-section">
            <div class="terms-card">
                <h3><i class="bi bi-info-circle"></i> Условия акций</h3>
                <ul class="terms-list">
                    <li>Акции не суммируются с другими скидками</li>
                    <li>Промокоды действуют только на товары без скидки</li>
                    <li>Администрация оставляет за собой право изменить условия акций</li>
                    <li>Подробные условия уточняйте в соответствующих разделах или у менеджера</li>
                </ul>
            </div>
        </section>
    </div>
</div>

<script>
function copyCoupon(el, code) {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(code).then(function() {
        var hint = el.querySelector('.coupon-copy-hint');
        if (hint) {
            hint.innerHTML = '<i class="bi bi-check-lg"></i>&nbsp;Скопировано!';
            hint.style.opacity = '1';
        }
        setTimeout(function() {
            if (hint) {
                hint.innerHTML = '<i class="bi bi-clipboard-check"></i>&nbsp;Скопировать';
                hint.style.opacity = '';
            }
        }, 1600);
    });
}
</script>
