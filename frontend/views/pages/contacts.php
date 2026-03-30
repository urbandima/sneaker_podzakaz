<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\ContactsAsset;

/* @var $this yii\web\View */

$this->title = 'Контакты СНИКЕРХЭД';
$this->params['breadcrumbs'][] = $this->title;

ContactsAsset::register($this);
?>

<div class="contacts-page">
    
    <!-- Заголовок страницы -->
    <div class="contacts-header">
        <h1 class="contacts-title">Контакты</h1>
        <p class="contacts-subtitle">Мы всегда на связи и готовы ответить на ваши вопросы</p>
    </div>

    <!-- Контактная информация -->
    <section class="contacts-info">
        <div class="contact-cards-grid">
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="bi bi-telephone"></i>
                </div>
                <h3>Телефон</h3>
                <p>
                    <a href="tel:+375291234567">+375 (29) 123-45-67</a>
                </p>
                <p>Пн-Вс: 10:00 - 20:00</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="bi bi-envelope"></i>
                </div>
                <h3>Email</h3>
                <p>
                    <a href="mailto:info@sneakerhead.by">info@sneakerhead.by</a>
                </p>
                <p>Ответ в течение 24 часов</p>
            </div>
        </div>
    </section>

    <!-- Социальные сети -->
    <section class="contacts-social">
        <h2>Мы в социальных сетях</h2>
        <div class="social-cards-grid">
            <a href="#" class="social-card">
                <i class="bi bi-instagram"></i>
                <h4>Instagram</h4>
                <span>@sneakerhead_by</span>
            </a>
            <a href="#" class="social-card">
                <i class="bi bi-telegram"></i>
                <h4>Telegram</h4>
                <span>@sneakerhead_by</span>
            </a>
            <a href="#" class="social-card">
                <i class="bi bi-youtube"></i>
                <h4>YouTube</h4>
                <span>СНИКЕРХЭД</span>
            </a>
        </div>
    </section>

    <!-- Форма обратной связи -->
    <section class="contacts-form">
        <h2>Напишите нам</h2>
        <div class="contact-form-wrapper">
            <form class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Ваше имя</label>
                        <input type="text" id="name" class="form-control" placeholder="Иван Иванов" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control" placeholder="ivan@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="subject">Тема сообщения</label>
                    <input type="text" id="subject" class="form-control" placeholder="Вопрос о заказе" required>
                </div>
                <div class="form-group">
                    <label for="message">Сообщение</label>
                    <textarea id="message" class="form-control" rows="5" placeholder="Опишите ваш вопрос..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Отправить сообщение</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Карта -->
    <section class="contacts-map">
        <h2>Наш адрес</h2>
        <div class="map-card">
            <i class="bi bi-geo-alt"></i>
            <h4>Минск, ул. Купревича 1, корпус 1</h4>
            <p>Пункт выдачи заказов работает с 10:00 до 20:00</p>
            <a href="#" class="btn btn-secondary">Показать на карте</a>
        </div>
    </section>

</div>
