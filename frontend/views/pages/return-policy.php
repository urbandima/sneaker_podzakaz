<?php

use yii\helpers\Html;

$this->title = 'Условия возврата и обмена';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.info-page-wrap{max-width:760px;margin:0 auto}
.page-edit-admin-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#0f0f0f;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;opacity:.7;transition:opacity .15s}
.page-edit-admin-btn:hover{opacity:1;color:#fff}
.alert{padding:14px 18px;border-radius:10px;margin-bottom:16px}
.alert-primary{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.alert-warning{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.alert-info{background:#f0f9ff;border:1px solid #bae6fd;color:#0c4a6e}
.alert-heading{font-size:14px;font-weight:700;margin:0 0 6px}
.mb-0{margin-bottom:0!important}.mb-3{margin-bottom:1rem}
.text-success{color:#16a34a}.text-primary{color:#2563eb}.text-info{color:#0284c7}.text-warning{color:#d97706}.text-muted{color:var(--color-text-secondary,#666)}
.small{font-size:.85em}.list-unstyled{list-style:none;padding-left:0}
.h4{font-size:1.1rem;font-weight:700}.h6{font-size:.9rem;font-weight:700}
.content-section h2{font-size:1.1rem!important;font-weight:700!important;margin-bottom:.75rem!important}
</style>
<div class="container" style="padding-top:var(--space-12,3rem);padding-bottom:var(--space-16,4rem)">
    <div class="info-page-wrap">
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity && Yii::$app->user->identity->isAdmin()): ?>
        <div style="margin-bottom:1rem;text-align:right">
            <a href="/admin/page/edit?slug=return-policy" class="page-edit-admin-btn" target="_blank">
                <i class="bi bi-pencil-square"></i> Редактировать страницу
            </a>
        </div>
        <?php endif; ?>
            <div style="margin-bottom:var(--space-10,2.5rem)">
                <h1 style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:900;letter-spacing:-0.03em;margin-bottom:var(--space-2,.5rem)">Условия возврата и обмена</h1>
                <p style="color:var(--color-text-secondary,#666);font-size:var(--text-base,1rem)">Информация о порядке возврата и обмена товаров в соответствии с законодательством РБ</p>
            </div>

            <div class="content-section">
                <div class="alert alert-success">
                    <h6 class="alert-heading"><i class="bi bi-check-circle"></i> Гарантия качества</h6>
                    <p class="mb-0">
                        Мы гарантируем подлинность и качество всех товаров. При обнаружении несоответствия 
                        вы можете вернуть товар или обменять его в течение установленных сроков.
                    </p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Общие условия возврата</h2>
                <div class="return-info-cards">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="info-content">
                            <h5>Срок возврата</h5>
                            <p>14 дней с момента получения товара</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="info-content">
                            <h5>Необходимые документы</h5>
                            <p>Товарный чек или кассовый чек</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="info-content">
                            <h5>Состояние товара</h5>
                            <p>Товарный вид, потребительские свойства, пломбы</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-box-seam"></i> Возврат товара надлежащего качества</h2>
                <div class="return-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h6>Свяжитесь с нами</h6>
                            <p class="mb-0">
                                Позвоните по телефону +375 (29) 123-45-67 или напишите на 
                                <a href="mailto:returns@snikered.by">returns@snikered.by</a>
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h6>Заполните заявление</h6>
                            <p class="mb-0">
                                Мы вышлем вам бланк заявления на возврат, который нужно заполнить и подписать
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h6>Передайте товар</h6>
                            <p class="mb-0">
                                Доставьте товар в наш магазин или передайте курьером (за наш счёт)
                            </p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h6>Получите деньги</h6>
                            <p class="mb-0">
                                Возврат средств в течение 10 дней с момента получения товара
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Товары, не подлежащие возврату</h2>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Внимание!</h6>
                    <p class="mb-3">
                        В соответствии с Постановлением Совета Министров РБ № 714 от 14 июня 2007 г., 
                        следующие товары не подлежат возврату и обмену:
                    </p>
                    <div class="non-returnable-grid">
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Нательное бельё и чулочно-носочные изделия</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Лекарственные средства</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Ювелирные изделия из драгоценных металлов</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Табачные изделия</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Растения и животные</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Метражные текстильные изделия</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Строительные и отделочные материалы</span>
                        </div>
                        <div class="non-returnable-item">
                            <i class="bi bi-x-circle text-danger"></i>
                            <span>Предметы личной гигиены</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-check-circle"></i> Товары, подлежащие возврату</h2>
                <div class="returnable-items">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-bag"></i>
                        </div>
                        <div class="category-content">
                            <h5>Одежда и обувь</h5>
                            <p>Все виды одежды и обуви, кроме белья</p>
                        </div>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <div class="category-content">
                            <h5>Электроника</h5>
                            <p>Смартфоны, ноутбуки, аксессуары</p>
                        </div>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-house"></i>
                        </div>
                        <div class="category-content">
                            <h5>Бытовая техника</h5>
                            <p>Крупная и мелкая бытовая техника</p>
                        </div>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-bag-dash"></i>
                        </div>
                        <div class="category-content">
                            <h5>Аксессуары</h5>
                            <p>Сумки, рюкзаки, часы, очки</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Возврат товара с дефектом</h2>
                <div class="defect-return-process">
                    <div class="process-card">
                        <h6><i class="bi bi-exclamation-triangle text-warning"></i> Обнаружение дефекта</h6>
                        <p>
                            Если вы обнаружили дефект товара в течение гарантийного срока, 
                            немедленно свяжитесь с нами для оформления гарантийного случая.
                        </p>
                    </div>

                    <div class="process-card">
                        <h6><i class="bi bi-tools text-primary"></i> Гарантийное обслуживание</h6>
                        <p>
                            Мы проведём экспертизу и при подтверждении дефекта: 
                            • отремонтируем товар бесплатно<br>
                            • заменим на аналогичный<br>
                            • вернём полную стоимость
                        </p>
                    </div>

                    <div class="process-card">
                        <h6><i class="bi bi-clock-history text-info"></i> Сроки</h6>
                        <p>
                            Экспертиза — до 14 дней<br>
                            Ремонт — до 30 дней<br>
                            Возврат денег — до 10 дней после экспертизы
                        </p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-currency-dollar"></i> Способы возврата средств</h2>
                <div class="refund-methods">
                    <div class="method-item">
                        <div class="method-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div class="method-details">
                            <h6>На банковскую карту</h6>
                            <p class="text-muted">5-10 банковских дней</p>
                            <p class="small">Возврат на карту, с которой была оплата</p>
                        </div>
                    </div>

                    <div class="method-item">
                        <div class="method-icon">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div class="method-details">
                            <h6>На банковский счёт</h6>
                            <p class="text-muted">3-5 банковских дней</p>
                            <p class="small">Перевод на счёт в банке РБ</p>
                        </div>
                    </div>

                    <div class="method-item">
                        <div class="method-icon">
                            <i class="bi bi-cash"></i>
                        </div>
                        <div class="method-details">
                            <h6>Наличными</h6>
                            <p class="text-muted">Немедленно</p>
                            <p class="small">При самовывозе в нашем магазине</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-question-circle"></i> Часто задаваемые вопросы</h2>
                <div class="faq-section">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h6>Можно ли вернуть товар, если он не подошёл по размеру?</h6>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Да, если товар сохраняет товарный вид, не был в употреблении и имеет все пломбы и упаковку. 
                                Срок возврата — 14 дней с момента получения.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h6>Что делать, если товар пришёл с браком?</h6>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Немедленно свяжитесь с нами. Мы организуем экспертизу и при подтверждении брака 
                                бесплатно заменим товар или вернём деньги.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h6>Кто оплачивает доставку при возврате?</h6>
                        </div>
                        <div class="faq-answer">
                            <p>
                                При возврате товара надлежащего качества доставку оплачивает покупатель. 
                                При возврате бракованного товара — мы.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-telephone"></i> Контакты для возврата</h2>
                <div class="contact-info">
                    <div class="contact-card">
                        <h6><i class="bi bi-telephone"></i> Телефон</h6>
                        <p>+375 (29) 123-45-67 (отдел возвратов)</p>
                    </div>
                    <div class="contact-card">
                        <h6><i class="bi bi-envelope"></i> Email</h6>
                        <p>returns@snikered.by</p>
                    </div>
                    <div class="contact-card">
                        <h6><i class="bi bi-geo-alt"></i> Адрес</h6>
                        <p>г. Минск, ул. Купревича 1, корп. 1</p>
                    </div>
                    <div class="contact-card">
                        <h6><i class="bi bi-clock"></i> Время работы</h6>
                        <p>Пн-Пт: 10:00 - 19:00, Сб: 10:00 - 16:00</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Законодательство</h6>
                    <p class="mb-0">
                        Возврат товаров осуществляется в соответствии с:<br>
                        • Законом РБ "О защите прав потребителей"<br>
                        • Постановлением Совета Министров РБ № 714 от 14.06.2007<br>
                        • Правилами дистанционной торговли
                    </p>
                </div>
            </div>
    </div>
</div>

<style>
.return-info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.info-card {
    text-align: center;
    padding: 22px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border: 1px solid var(--color-border, #e5e5e5);
    transition: transform 0.2s, box-shadow 0.2s;
}
.info-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
.info-icon { font-size: 1.8rem; color: var(--color-text-primary, #111); margin-bottom: 12px; }
.info-card h5 { color: var(--color-text-primary, #111); margin-bottom: 6px; font-size: var(--text-base, 1rem); }
.return-steps { display: grid; gap: 16px; }
.step-item {
    display: flex;
    gap: 18px;
    padding: 22px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border-left: 3px solid var(--c-black, #111);
}
.step-number {
    width: 36px;
    height: 36px;
    background: var(--c-black, #111);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: var(--text-sm, 0.875rem);
    flex-shrink: 0;
}
.step-content h6 { color: var(--color-text-primary, #111); margin-bottom: 6px; font-weight: 700; }
.step-content p { font-size: var(--text-sm, 0.875rem); color: var(--color-text-secondary, #666); margin: 0; }
.non-returnable-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}
.non-returnable-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #fef2f2;
    border-radius: var(--radius-md, 8px);
    font-size: var(--text-sm, 0.875rem);
}
.returnable-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px;
}
.category-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    background: #f0fdf4;
    border-radius: var(--radius-lg, 12px);
    border-left: 3px solid #16a34a;
}
.category-icon { font-size: 1.4rem; color: #16a34a; flex-shrink: 0; }
.defect-return-process { display: grid; gap: 16px; }
.process-card {
    padding: 18px 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border-left: 3px solid #f59e0b;
}
.refund-methods { display: grid; gap: 12px; }
.method-item {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 18px 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.method-icon { font-size: 1.4rem; color: var(--color-text-primary, #111); width: 36px; text-align: center; flex-shrink: 0; }
.faq-section { display: grid; gap: 12px; }
.faq-item {
    border: 1px solid var(--color-border, #e5e5e5);
    border-radius: var(--radius-lg, 12px);
    overflow: hidden;
}
.faq-question {
    background: var(--color-bg-secondary, #f5f5f5);
    padding: 14px 18px;
    font-weight: 600;
    font-size: var(--text-sm, 0.875rem);
    border-bottom: 1px solid var(--color-border, #e5e5e5);
}
.faq-answer {
    padding: 14px 18px;
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
    line-height: 1.6;
}
.contact-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 14px;
}
.contact-card {
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border: 1px solid var(--color-border, #e5e5e5);
    text-align: center;
}
.contact-card h6 { color: var(--color-text-primary, #111); margin-bottom: 8px; font-weight: 700; }
.content-section { margin-bottom: 36px; }
.content-section h2 { font-size: var(--text-lg, 1.125rem) !important; font-weight: 700 !important; }

@media (max-width: 768px) {
    .step-item, .method-item, .category-card { flex-direction: column; text-align: center; }
}
</style>
