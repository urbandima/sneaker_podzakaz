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

<style>
.admin-page-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.admin-page-header {
    margin-bottom: 30px;
}

.admin-page-header h1 {
    color: #333;
    margin-bottom: 10px;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.admin-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}
</style>
