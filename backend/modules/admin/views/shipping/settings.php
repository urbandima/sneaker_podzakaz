<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'Настройки доставки';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.shipping-page {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
}

.shipping-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.shipping-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.settings-card {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    background: #ffffff;
}

.settings-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 1rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    background: #ffffff;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.form-checkbox input {
    width: 1rem;
    height: 1rem;
    accent-color: #3b82f6;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border-radius: 8px;
    border: 1px solid transparent;
    padding: 0.6rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-primary-action {
    background: #111827;
    color: #ffffff;
}

.btn-primary-action:hover {
    background: #000000;
}

.btn-secondary-action {
    background: #f3f4f6;
    color: #111827;
    border-color: #e5e7eb;
}

.btn-secondary-action:hover {
    background: #e5e7eb;
}

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
</style>

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
                <label class="form-checkbox" style="margin-bottom: 0.5rem;">
                    <input type="checkbox" checked>
                    <span>Самовывоз</span>
                </label>
                <label class="form-checkbox" style="margin-bottom: 0.5rem;">
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
                <label class="form-checkbox" style="margin-bottom: 0.5rem;">
                    <input type="checkbox" checked>
                    <span>Email при отправке заказа</span>
                </label>
                <label class="form-checkbox" style="margin-bottom: 0.5rem;">
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
        <a href="<?= Url::to(['/admin/shipping']) ?>" class="btn-action btn-secondary-action">
            Отмена
        </a>
        <button class="btn-action btn-primary-action">
            <i class="bi bi-check-lg"></i>
            Сохранить настройки
        </button>
    </div>
</div>
