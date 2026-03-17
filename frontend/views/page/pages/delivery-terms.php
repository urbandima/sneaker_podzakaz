<?php

use yii\helpers\Html;

$this->title = 'Условия доставки';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="page-header text-center mb-5">
                <h1 class="h2 mb-3">Условия доставки</h1>
                <p class="text-muted">Информация о способах и стоимости доставки по Республике Беларусь</p>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">🚚 Способы доставки</h2>
                
                <div class="delivery-methods">
                    <div class="delivery-card">
                        <div class="delivery-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="delivery-content">
                            <h5>Самовывоз</h5>
                            <p class="text-muted">Бесплатно</p>
                            <ul class="list-unstyled">
                                <li>📍 г. Минск, ул. Купревича 1, корп. 1</li>
                                <li>⏰ Пн-Пт: 10:00 - 19:00</li>
                                <li>⏰ Сб: 10:00 - 16:00</li>
                                <li>📞 +375 (29) 123-45-67</li>
                            </ul>
                            <button class="btn btn-outline-primary btn-sm">Показать на карте</button>
                        </div>
                    </div>

                    <div class="delivery-card">
                        <div class="delivery-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="delivery-content">
                            <h5>Курьерская доставка по Минску</h5>
                            <p class="text-muted">10 BYN</p>
                            <ul class="list-unstyled">
                                <li>⏰ Доставка в день заказа (до 16:00)</li>
                                <li>⏰ На следующий день (после 16:00)</li>
                                <li>⏰ Время доставки: 10:00 - 20:00</li>
                                <li>📦 Заказ до 30 кг</li>
                            </ul>
                            <div class="alert alert-info small">
                                <strong>Бесплатно</strong> при заказе от 200 BYN
                            </div>
                        </div>
                    </div>

                    <div class="delivery-card">
                        <div class="delivery-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="delivery-content">
                            <h5>СДЭК</h5>
                            <p class="text-muted">от 5 BYN</p>
                            <ul class="list-unstyled">
                                <li>📦 Доставка по всей Беларуси</li>
                                <li>⏰ 2-4 рабочих дня</li>
                                <li>🏪 200+ пунктов выдачи</li>
                                <li>📱 Отслеживание заказа</li>
                            </ul>
                            <button class="btn btn-outline-primary btn-sm">Найти пункт выдачи</button>
                        </div>
                    </div>

                    <div class="delivery-card">
                        <div class="delivery-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="delivery-content">
                            <h5>Почта России</h5>
                            <p class="text-muted">от 8 BYN</p>
                            <ul class="list-unstyled">
                                <li>🌍 Доставка по всей стране</li>
                                <li>⏰ 5-10 рабочих дней</li>
                                <li>📦 До 20 кг</li>
                                <li>📡 Уведомление о прибытии</li>
                            </ul>
                            <div class="alert alert-warning small">
                                <strong>Внимание:</strong> возможна наложенная оплата
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">💰 Стоимость доставки</h2>
                <div class="table-responsive">
                    <table class="table table-bordered delivery-table">
                        <thead>
                            <tr>
                                <th>Способ доставки</th>
                                <th>Стоимость</th>
                                <th>Бесплатно от</th>
                                <th>Срок доставки</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Самовывоз (Минск)</td>
                                <td class="text-success">Бесплатно</td>
                                <td>-</td>
                                <td>В день заказа</td>
                            </tr>
                            <tr>
                                <td>Курьер (Минск)</td>
                                <td>10 BYN</td>
                                <td>200 BYN</td>
                                <td>1-2 дня</td>
                            </tr>
                            <tr>
                                <td>СДЭК (РБ)</td>
                                <td>от 5 BYN</td>
                                <td>150 BYN</td>
                                <td>2-4 дня</td>
                            </tr>
                            <tr>
                                <td>Почта России</td>
                                <td>от 8 BYN</td>
                                <td>300 BYN</td>
                                <td>5-10 дней</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">📦 Условия приёма заказа</h2>
                <div class="order-conditions">
                    <div class="condition-item">
                        <div class="condition-number">1</div>
                        <div class="condition-text">
                            <h6>Проверка товара при получении</h6>
                            <p class="mb-0">
                                Внимательно осмотрите товар на наличие дефектов и соответствие заказу. 
                                При получении товара через курьера или пункт выдачи у вас есть 15 минут на проверку.
                            </p>
                        </div>
                    </div>

                    <div class="condition-item">
                        <div class="condition-number">2</div>
                        <div class="condition-text">
                            <h6>Документы при получении</h6>
                            <p class="mb-0">
                                При получении заказа вам будет выдан:<br>
                                • Товарный чек<br>
                                • Гарантийный талон (если применимо)<br>
                                • Инструкция по возврату
                            </p>
                        </div>
                    </div>

                    <div class="condition-item">
                        <div class="condition-number">3</div>
                        <div class="condition-text">
                            <h6>Паспортные данные</h6>
                            <p class="mb-0">
                                Для получения заказа наложенным платежом или при стоимости свыше 100 BYN 
                                может потребоваться документ, удостоверяющий личность.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">⏰ Время доставки</h2>
                <div class="delivery-schedule">
                    <div class="schedule-card">
                        <h6>Курьерская доставка по Минску</h6>
                        <ul class="list-unstyled">
                            <li><strong>Пн-Пт:</strong> 10:00 - 20:00</li>
                            <li><strong>Сб:</strong> 10:00 - 16:00</li>
                            <li><strong>Вс:</strong> выходной</li>
                        </ul>
                    </div>

                    <div class="schedule-card">
                        <h6>Пункты выдачи СДЭК</h6>
                        <ul class="list-unstyled">
                            <li><strong>Пн-Пт:</strong> 09:00 - 20:00</li>
                            <li><strong>Сб:</strong> 09:00 - 18:00</li>
                            <li><strong>Вс:</strong> 10:00 - 16:00</li>
                        </ul>
                    </div>

                    <div class="schedule-card">
                        <h6>Самовывоз со склада</h6>
                        <ul class="list-unstyled">
                            <li><strong>Пн-Пт:</strong> 10:00 - 19:00</li>
                            <li><strong>Сб:</strong> 10:00 - 16:00</li>
                            <li><strong>Вс:</strong> выходной</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">🚫 Ограничения доставки</h2>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Важная информация</h6>
                    <ul class="mb-0">
                        <li>Максимальный вес одного заказа — 30 кг</li>
                        <li>Максимальная габаритная сумма (длина+ширина+высота) — 150 см</li>
                        <li>Доставка хрупких товаров осуществляется только курьером</li>
                        <li>Доставка в отдалённые районы может занимать до 7 дней</li>
                    </ul>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">📞 Контакты для связи</h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="bi bi-telephone text-primary"></i>
                        <div>
                            <h6>Отдел доставки</h6>
                            <p class="mb-0">+375 (29) 123-45-67</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-envelope text-primary"></i>
                        <div>
                            <h6>Email</h6>
                            <p class="mb-0">delivery@snikered.by</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-clock text-primary"></i>
                        <div>
                            <h6>Время работы</h6>
                            <p class="mb-0">Пн-Пт: 9:00 - 18:00, Сб: 10:00 - 16:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.delivery-methods {
    display: grid;
    gap: 25px;
    margin-bottom: 30px;
}

.delivery-card {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: transform 0.2s, box-shadow 0.2s;
}

.delivery-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.delivery-icon {
    font-size: 2.5rem;
    color: #6c5ce7;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: rgba(108, 92, 231, 0.1);
    border-radius: 12px;
}

.delivery-content h5 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.delivery-content p.text-muted {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.order-conditions {
    display: grid;
    gap: 20px;
}

.condition-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.condition-number {
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

.condition-text h6 {
    color: #2c3e50;
    margin-bottom: 8px;
}

.delivery-schedule {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.schedule-card {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #6c5ce7;
}

.schedule-card h6 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.contact-info {
    display: grid;
    gap: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.contact-item i {
    font-size: 1.5rem;
}

.delivery-table th {
    background-color: #6c5ce7;
    color: white;
    border: none;
}

.delivery-table td {
    vertical-align: middle;
}

.content-section {
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .delivery-card {
        flex-direction: column;
        text-align: center;
    }
    
    .delivery-icon {
        margin: 0 auto 15px;
    }
    
    .condition-item {
        flex-direction: column;
        text-align: center;
    }
    
    .delivery-schedule {
        grid-template-columns: 1fr;
    }
}
</style>
