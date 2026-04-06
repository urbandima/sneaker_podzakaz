<?php
/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Панель управления';
?>

<div class="admin-page-container">
    <div class="admin-page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p>Админ-панель работает!</p>
    </div>
    
    <div class="admin-page-content">
        <div class="admin-grid">
            <div class="admin-card">
                <h3>Заказы</h3>
                <p>Всего: 0</p>
            </div>
            <div class="admin-card">
                <h3>Товары</h3>
                <p>Всего: 0</p>
            </div>
            <div class="admin-card">
                <h3>Пользователи</h3>
                <p>Всего: 0</p>
            </div>
        </div>
    </div>
</div>

