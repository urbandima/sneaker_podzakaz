<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'Настройки доставки';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="shipping-page">
    <div class="shipping-header">
        <div>
            <h1><i class="bi bi-gear" style="color: #6b7280;"></i> <?= Html::encode($this->title) ?></h1>
            <p style="margin: 0.25rem 0 0; color: #6b7280; font-size: 0.9rem;">
                Глобальные настройки системы доставки
            </p>
        </div>
        <a href="<?= Url::to(['/admin/shipping']) ?>" class="btn-action btn-secondary-action">
            <i class="bi bi-arrow-left"></i>
            Назад
        </a>
    </div>

    <div class="settings-grid">
        <!-- Основные настройки -->
        <div class="settings-card">
            <h3><i class="bi bi-truck"></i> Основные настройки</h3>
            
            <div class="form-group">
                <label class="form-label">Страна по умолчанию</label>
                <select class="form-select">
                    <option value="BY" selected>Беларусь</option>
                    <option value="RU">Россия</option>
                    <option value="KZ">Казахстан</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Валюта доставки</label>
                <select class="form-select">
                    <option value="BYN" selected>BYN (Белорусский рубль)</option>
                    <option value="RUB">RUB (Российский рубль)</option>
                    <option value="USD">USD (Доллар США)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" checked>
                    <span>Автоматический расчет стоимости</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" checked>
                    <span>Бесплатная доставка от определенной суммы</span>
                </label>
            </div>
        </div>

        <!-- Порог бесплатной доставки -->
        <div class="settings-card">
            <h3><i class="bi bi-gift"></i> Бесплатная доставка</h3>
            
            <div class="form-group">
                <label class="form-label">Минимальная сумма заказа</label>
                <input type="number" class="form-input" value="150" step="10">
                <div class="help-text">При заказе от этой суммы доставка бесплатная</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Методы с бесплатной доставкой</label>
                <label class="form-checkbox mb-2">
                    <input type="checkbox" checked>
                    <span>Самовывоз</span>
                </label>
                <label class="form-checkbox mb-2">
                    <input type="checkbox">
                    <span>Курьер</span>
                </label>
                <label class="form-checkbox">
                    <input type="checkbox">
                    <span>Почта</span>
                </label>
            </div>
        </div>

        <!-- Международная доставка -->
        <div class="settings-card">
            <h3><i class="bi bi-globe"></i> Международная доставка</h3>
            
            <div class="form-group">
                <label class="form-label">Страна отправления</label>
                <select class="form-select">
                    <option value="CN" selected>Китай</option>
                    <option value="US">США</option>
                    <option value="DE">Германия</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Курс CNY для расчетов</label>
                <input type="number" class="form-input" value="0.45" step="0.01">
                <div class="help-text">Автоматически обновляется ежедневно</div>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" checked>
                    <span>Таможенные сборы включены</span>
                </label>
            </div>
        </div>

        <!-- Уведомления -->
        <div class="settings-card">
            <h3><i class="bi bi-bell"></i> Уведомления</h3>
            
            <div class="form-group">
                <label class="form-checkbox mb-2">
                    <input type="checkbox" checked>
                    <span>Email при отправке заказа</span>
                </label>
                <label class="form-checkbox mb-2">
                    <input type="checkbox" checked>
                    <span>SMS при доставке</span>
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" checked>
                    <span>Пуш-уведомления в приложении</span>
                </label>
            </div>
        </div>
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
        <a href="<?= Url::to(['/admin/shipping']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
            Отмена
        </a>
        <button class="admin-btn admin-btn-primary admin-btn-sm">
            <i class="bi bi-check-lg"></i> Сохранить настройки
        </button>
    </div>
</div>
