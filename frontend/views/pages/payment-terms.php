<?php

use yii\helpers\Html;

$this->title = 'Условия оплаты';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="page-header text-center mb-5">
                <h1 class="h2 mb-3">Условия оплаты</h1>
                <p class="text-muted">Информация о способах оплаты и условиях расчётов</p>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">📱 Способы онлайн-оплаты</h2>
                
                <div class="payment-methods-grid">
                    <div class="payment-method-card">
                        <div class="payment-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h5>Банковские карты</h5>
                        <ul class="list-unstyled">
                            <li>• Белкарт</li>
                            <li>• VISA</li>
                            <li>• MasterCard</li>
                            <li>• МИР</li>
                        </ul>
                        <p class="small text-muted">Мгновенная обработка, безопасная транзакция</p>
                    </div>

                    <div class="payment-method-card">
                        <div class="payment-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h5>Мобильный банкинг</h5>
                        <ul class="list-unstyled">
                            <li>• A1</li>
                            <li>• МТС</li>
                            <li>• life:)</li>
                        </ul>
                        <p class="small text-muted">Оплата через мобильное приложение банка</p>
                    </div>

                    <div class="payment-method-card">
                        <div class="payment-icon">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h5>Интернет-банкинг</h5>
                        <ul class="list-unstyled">
                            <li>• Белагропромбанк</li>
                            <li>• Беларусбанк</li>
                            <li>• Приорбанк</li>
                        </ul>
                        <p class="small text-muted">Прямой перевод через онлайн-банкинг</p>
                    </div>

                    <div class="payment-method-card">
                        <div class="payment-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h5>Электронные деньги</h5>
                        <ul class="list-unstyled">
                            <li>• EasyPay</li>
                            <li>• WebPay</li>
                            <li>• ЕРИП</li>
                        </ul>
                        <p class="small text-muted">Быстрая оплата через электронные кошельки</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">💳 Наличный расчёт</h2>
                <div class="alert alert-info">
                    <h6 class="alert-heading">📋 Важная информация</h6>
                    <p class="mb-2">
                        При оплате наличными вы получаете кассовый чек в соответствии с законодательством Республики Беларусь.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Места приёма наличных:</strong><br>
                        • Самовывоз со склада<br>
                        • Курьер при доставке<br>
                        • Пункты выдачи заказов
                    </p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">🔒 Безопасность платежей</h2>
                <div class="security-features">
                    <div class="security-item">
                        <i class="bi bi-shield-check text-success"></i>
                        <div>
                            <h6>SSL-шифрование</h6>
                            <p class="small text-muted mb-0">Все данные передаются по защищённому протоколу HTTPS</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <i class="bi bi-bank text-primary"></i>
                        <div>
                            <h6>3D Secure</h6>
                            <p class="small text-muted mb-0">Дополнительная проверка через SMS-код</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <i class="bi bi-patch-check text-info"></i>
                        <div>
                            <h6>PCI DSS</h6>
                            <p class="small text-muted mb-0">Соответствие стандартам безопасности платёжных карт</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">⏱️ Сроки зачисления средств</h2>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Способ оплаты</th>
                                <th>Время зачисления</th>
                                <th>Комиссия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Банковские карты</td>
                                <td>Мгновенно</td>
                                <td>0% (покупатель)</td>
                            </tr>
                            <tr>
                                <td>ЕРИП</td>
                                <td>5-15 минут</td>
                                <td>0% (покупатель)</td>
                            </tr>
                            <tr>
                                <td>EasyPay/WebPay</td>
                                <td>Мгновенно</td>
                                <td>0% (покупатель)</td>
                            </tr>
                            <tr>
                                <td>Наличные</td>
                                <td>Немедленно</td>
                                <td>0%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">🔄 Возврат средств</h2>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">💰 Условия возврата</h6>
                    <p class="mb-2">
                        Возврат денежных средств осуществляется в течение 5-10 банковских дней с момента получения товара.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Способы возврата:</strong><br>
                        • На карту, с которой была оплата<br>
                        • На счёт в банке<br>
                        • Наличными (при самовывозе)
                    </p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">📞 Поддержка</h2>
                <div class="contact-info">
                    <p class="mb-2">
                        <strong>По вопросам оплаты:</strong><br>
                        📞 +375 (29) 123-45-67<br>
                        📧 payment@snikered.by<br>
                        ⏰ Пн-Пт: 9:00 - 18:00
                    </p>
                    <p class="mb-0">
                        <strong>Техническая поддержка:</strong><br>
                        📞 +375 (29) 765-43-21<br>
                        📧 support@snikered.by<br>
                        ⏰ Круглосуточно
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.payment-method-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: transform 0.2s, box-shadow 0.2s;
}

.payment-method-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.payment-icon {
    font-size: 2.5rem;
    color: #6c5ce7;
    margin-bottom: 15px;
}

.payment-method-card h5 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.payment-method-card ul {
    margin-bottom: 10px;
}

.security-features {
    display: grid;
    gap: 15px;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.security-item i {
    font-size: 1.5rem;
}

.content-section {
    margin-bottom: 40px;
}

.table th {
    background-color: #6c5ce7;
    color: white;
    border: none;
}

.table td {
    vertical-align: middle;
}

@media (max-width: 768px) {
    .payment-methods-grid {
        grid-template-columns: 1fr;
    }
    
    .security-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>
