<?php
/** @var yii\web\View $this */
/** @var string $orderNumber */

$this->title = 'Отслеживание заказа - СНИКЕРХЭД';
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/pages-mobile.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="page-wrapper">
    <header class="catalog-header">
        <div class="container">
            <button type="button" class="back-btn" onclick="history.back()">
                <i class="bi bi-arrow-left"></i>
            </button>
            <a href="/" class="logo">СНИКЕРХЭД</a>
            <a href="/catalog/favorites" class="favorites">
                <i class="bi bi-heart"></i>
            </a>
        </div>
    </header>
    
    <div class="page-content">
        <div class="container" style="max-width: 600px;">
            <h1 class="page-title">📦 Отследить заказ</h1>
            <p class="page-subtitle">Введите номер заказа для отслеживания</p>
            
            <form method="get" class="content-section">
                <div class="form-group">
                    <label class="form-label">Номер заказа</label>
                    <input type="text" name="order" class="form-input" value="<?= htmlspecialchars($orderNumber ?? '') ?>" placeholder="Например: #12345">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-search"></i>
                    <span>Отследить</span>
                </button>
            </form>
            
            <?php if (!empty($orderNumber)): ?>
            <div class="content-section">
                <h3 class="section-title">Статус заказа <?= htmlspecialchars($orderNumber) ?></h3>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon completed">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Заказ оформлен</div>
                            <div class="timeline-date">01.11.2025, 14:30</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon completed">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Заказ собран</div>
                            <div class="timeline-date">01.11.2025, 16:00</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon current">🚚</div>
                        <div class="timeline-content">
                            <div class="timeline-title">В пути</div>
                            <div class="timeline-date">Ожидается доставка 02.11.2025</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon pending">📦</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Доставлен</div>
                            <div class="timeline-date">Ожидается</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h4 class="info-box-title">💡 Где найти номер заказа?</h4>
                <p class="info-box-text">
                    Номер заказа указан в письме-подтверждении, которое было отправлено на вашу почту после оформления заказа.
                </p>
            </div>
        </div>
    </div>
</div>
