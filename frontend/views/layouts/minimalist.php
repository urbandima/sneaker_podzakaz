<?php

use yii\helpers\Html;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$company = Yii::$app->settings->getCompany();
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <?php // JSON-LD Schema.org разметка ?>
    <?php if (!empty($this->params['jsonLdSchemas'])): ?>
        <?php foreach ($this->params['jsonLdSchemas'] as $schema): ?>
            <script type="application/ld+json"><?= $schema ?></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php // hreflang для SEO ?>
    <link rel="alternate" hreflang="ru-BY" href="<?= Yii::$app->request->absoluteUrl ?>">
    <link rel="alternate" hreflang="x-default" href="<?= Yii::$app->request->absoluteUrl ?>">
    
    <?php // Bootstrap Icons CSS ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="frontend-layout">
<?php $this->beginBody() ?>

<!-- Frontend Header -->
<header class="frontend-header">
    <div class="container">
        <div class="frontend-header-content">
            <?php if (!empty($company) && is_object($company)): ?>
                <a href="/" class="frontend-logo">
                    <?= Html::encode($company->name) ?>
                </a>
            <?php elseif (!empty($company) && is_array($company)): ?>
                <a href="/" class="frontend-logo">
                    <?= Html::encode($company['name'] ?? 'SNEAKERHEAD') ?>
                </a>
            <?php else: ?>
                <a href="/" class="frontend-logo">SNEAKERHEAD</a>
            <?php endif; ?>
            
            <nav class="frontend-nav">
                <a href="/catalog" class="frontend-nav-link <?= Yii::$app->controller->id === 'catalog' ? 'active' : '' ?>">Каталог</a>
                <a href="/brands" class="frontend-nav-link <?= Yii::$app->controller->id === 'brand' ? 'active' : '' ?>">Бренды</a>
                <a href="/sale" class="frontend-nav-link <?= Yii::$app->controller->id === 'page' && Yii::$app->controller->action->id === 'sale' ? 'active' : '' ?>">Акции</a>
                <a href="/about" class="frontend-nav-link <?= Yii::$app->controller->id === 'page' && Yii::$app->controller->action->id === 'about' ? 'active' : '' ?>">О нас</a>
            </nav>
            
            <div class="frontend-header-actions">
                <a href="/cart" class="frontend-cart">
                    <i class="bi bi-cart"></i>
                    <span class="frontend-cart-count" id="cart-count">0</span>
                </a>
                
                <?php if (Yii::$app->user->isGuest): ?>
                    <a href="/account/login" class="btn btn-primary btn-sm">Войти</a>
                <?php else: ?>
                    <a href="/account" class="btn btn-secondary btn-sm">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="frontend-main">
    <?= $content ?>
</main>

<!-- Frontend Footer -->
<footer class="frontend-footer">
    <div class="container">
        <div class="frontend-footer-content">
            <div class="frontend-footer-section">
                <h3>
                    <?php if (!empty($company) && is_object($company)): ?>
                        <?= Html::encode($company->name) ?>
                    <?php elseif (!empty($company) && is_array($company)): ?>
                        <?= Html::encode($company['name'] ?? 'SNEAKERHEAD') ?>
                    <?php else: ?>
                        SNEAKERHEAD
                    <?php endif; ?>
                </h3>
                <p style="color: var(--color-gray-400); font-size: var(--font-size-sm); margin-top: var(--spacing-4);">
                    Минималистичный магазин кроссовок. Чистый дизайн, высокое качество.
                </p>
            </div>
            
            <div class="frontend-footer-section">
                <h3>Каталог</h3>
                <ul class="frontend-footer-links">
                    <li><a href="/catalog">Все товары</a></li>
                    <li><a href="/brands">Бренды</a></li>
                    <li><a href="/sale">Акции</a></li>
                </ul>
            </div>
            
            <div class="frontend-footer-section">
                <h3>Помощь</h3>
                <ul class="frontend-footer-links">
                    <li><a href="/delivery">Доставка</a></li>
                    <li><a href="/payment">Оплата</a></li>
                    <li><a href="/return">Возврат</a></li>
                    <li><a href="/faq">FAQ</a></li>
                </ul>
            </div>
            
            <div class="frontend-footer-section">
                <h3>Контакты</h3>
                <ul class="frontend-footer-links">
                    <?php if (!empty($company) && is_object($company)): ?>
                        <?php if (!empty($company->phone)): ?>
                            <li><a href="tel:<?= Html::encode($company->phone) ?>">
                                📞 <?= Html::encode($company->phone) ?>
                            </a></li>
                        <?php endif; ?>
                        <?php if (!empty($company->email)): ?>
                            <li><a href="mailto:<?= Html::encode($company->email) ?>">
                                📧 <?= Html::encode($company->email) ?>
                            </a></li>
                        <?php endif; ?>
                    <?php elseif (!empty($company) && is_array($company)): ?>
                        <?php if (!empty($company['phone'])): ?>
                            <li><a href="tel:<?= Html::encode($company['phone']) ?>">
                                📞 <?= Html::encode($company['phone']) ?>
                            </a></li>
                        <?php endif; ?>
                        <?php if (!empty($company['email'])): ?>
                            <li><a href="mailto:<?= Html::encode($company['email']) ?>">
                                📧 <?= Html::encode($company['email']) ?>
                            </a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li><a href="/contacts">📍 г. Минск</a></li>
                </ul>
            </div>
        </div>
        
        <div class="frontend-footer-bottom">
            <p>&copy; <?= date('Y') ?> 
                <?php if (!empty($company) && is_object($company)): ?>
                    <?= Html::encode($company->name) ?>
                <?php elseif (!empty($company) && is_array($company)): ?>
                    <?= Html::encode($company['name'] ?? 'SNEAKERHEAD') ?>
                <?php else: ?>
                    SNEAKERHEAD
                <?php endif; ?>. 
                Минималистичный дизайн 100/100.
            </p>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
