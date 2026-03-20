<?php

use yii\helpers\Html;

$this->title = 'Условия возврата и обмена';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="page-header text-center mb-5">
                <h1 class="h2 mb-3">Условия возврата и обмена</h1>
                <p class="text-muted">Информация о порядке возврата и обмена товаров в соответствии с законодательством РБ</p>
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
                <h2 class="h4 mb-3">🔄 Общие условия возврата</h2>
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
                <h2 class="h4 mb-3">🚫 Товары, не подлежащие возврату</h2>
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
                <h2 class="h4 mb-3">🔧 Возврат товара с дефектом</h2>
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
                    <h6 class="alert-heading">📖 Законодательство</h6>
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
</div>

<style>
.return-info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    text-align: center;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: transform 0.2s;
}

.info-card:hover {
    transform: translateY(-2px);
}

.info-icon {
    font-size: 2rem;
    color: #6c5ce7;
    margin-bottom: 15px;
}

.info-card h5 {
    color: #2c3e50;
    margin-bottom: 8px;
}

.return-steps {
    display: grid;
    gap: 20px;
}

.step-item {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border-left: 4px solid #6c5ce7;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #6c5ce7;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h6 {
    color: #2c3e50;
    margin-bottom: 8px;
}

.non-returnable-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.non-returnable-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(220, 53, 69, 0.1);
    border-radius: 6px;
}

.returnable-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.category-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 8px;
    border-left: 4px solid #28a745;
}

.category-icon {
    font-size: 1.5rem;
    color: #28a745;
}

.defect-return-process {
    display: grid;
    gap: 20px;
}

.process-card {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
}

.refund-methods {
    display: grid;
    gap: 15px;
}

.method-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.method-icon {
    font-size: 1.5rem;
    color: #6c5ce7;
    width: 40px;
    text-align: center;
}

.faq-section {
    display: grid;
    gap: 20px;
}

.faq-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.faq-question {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.faq-answer {
    padding: 15px 20px;
}

.contact-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.contact-card {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.contact-card h6 {
    color: #6c5ce7;
    margin-bottom: 8px;
}

.content-section {
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .step-item {
        flex-direction: column;
        text-align: center;
    }
    
    .method-item {
        flex-direction: column;
        text-align: center;
    }
    
    .category-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>
