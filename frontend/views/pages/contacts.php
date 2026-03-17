<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Контакты СНИКЕРХЭД — свяжитесь с нами';
$this->params['breadcrumbs'][] = $this->title;

// SEO мета-теги уже установлены в контроллере
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- Заголовок страницы -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3"><?= Html::encode($this->title) ?></h1>
                <p class="lead text-muted">Мы всегда на связи и готовы ответить на ваши вопросы</p>
            </div>

            <!-- Контактная информация -->
            <section class="mb-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-telephone text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title">Телефон</h5>
                                <p class="card-text">
                                    <a href="tel:+375291234567" class="text-decoration-none">
                                        +375 (29) 123-45-67
                                    </a>
                                </p>
                                <p class="text-muted small">Пн-Вс: 10:00 - 20:00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-envelope text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title">Email</h5>
                                <p class="card-text">
                                    <a href="mailto:info@sneaker-head.by" class="text-decoration-none">
                                        info@sneaker-head.by
                                    </a>
                                </p>
                                <p class="text-muted small">Ответ в течение 24 часов</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Социальные сети -->
            <section class="mb-5">
                <h2 class="h3 mb-4 text-center">Мы в социальных сетях</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-instagram text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title">Instagram</h5>
                                <p class="card-text">@sneakerhead_by</p>
                                <a href="#" class="btn btn-outline-danger btn-sm">Подписаться</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-telegram text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title">Telegram</h5>
                                <p class="card-text">@sneakerhead_by</p>
                                <a href="#" class="btn btn-outline-primary btn-sm">Подписаться</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="bi bi-youtube text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title">YouTube</h5>
                                <p class="card-text">СНИКЕРХЭД</p>
                                <a href="#" class="btn btn-outline-danger btn-sm">Подписаться</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Форма обратной связи -->
            <section class="mb-5">
                <h2 class="h3 mb-4 text-center">Напишите нам</h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Ваше имя</label>
                                    <input type="text" class="form-control" id="name" placeholder="Иван Иванов">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" placeholder="ivan@example.com">
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">Тема сообщения</label>
                                    <input type="text" class="form-control" id="subject" placeholder="Вопрос о заказе">
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Сообщение</label>
                                    <textarea class="form-control" id="message" rows="5" placeholder="Опишите ваш вопрос..."></textarea>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>Отправить сообщение
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section class="mb-5">
                <h2 class="h3 mb-4 text-center">Часто задаваемые вопросы</h2>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Как проверить оригинальность кроссовок?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Все наши кроссовки проходят проверку подлинности. Мы предоставляем сертификаты качества 
                                и гарантируем оригинальность каждого товара.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Сколько времени занимает доставка?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Доставка по Беларуси занимает 2-5 рабочих дней. Международная доставка - 7-14 дней.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Можно ли вернуть или обменять товар?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Да, вы можете вернуть или обменять товар в течение 14 дней после покупки при сохранении 
                                товарного вида и упаковки.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Карта (заглушка) -->
            <section class="mb-5">
                <h2 class="h3 mb-4 text-center">Наш адрес</h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="bg-light rounded-3 p-5 text-center">
                            <i class="bi bi-geo-alt text-primary" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Минск, ул. Купревича 1, корпус 1</h4>
                            <p class="text-muted">Пункт выдачи заказов работает с 10:00 до 20:00</p>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="bi bi-map me-2"></i>Показать на карте
                            </a>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>
