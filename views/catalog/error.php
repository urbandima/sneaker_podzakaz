<?php

/** @var yii\web\View $this */
/** @var string $message */
/** @var int $statusCode */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $statusCode . ' - Страница не найдена';
?>

<div class="catalog-error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-code"><?= $statusCode ?></div>
            <h1 class="error-title"><?= $message ?></h1>
            
            <?php if ($statusCode == 404): ?>
                <p class="error-description">
                    К сожалению, запрашиваемая страница не найдена.
                    Возможно, товар был удален или вы перешли по неверной ссылке.
                </p>
            <?php else: ?>
                <p class="error-description">
                    Произошла ошибка при обработке запроса.
                    Пожалуйста, попробуйте позже.
                </p>
            <?php endif; ?>

            <div class="error-actions">
                <a href="<?= Url::to(['/catalog/index']) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i>
                    Вернуться в каталог
                </a>
                <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-secondary">
                    <i class="bi bi-house"></i>
                    На главную
                </a>
            </div>

            <?php if ($statusCode == 404): ?>
                <div class="popular-links">
                    <h3>Популярные разделы:</h3>
                    <div class="links-grid">
                        <a href="<?= Url::to(['/catalog/brand/nike']) ?>" class="link-item">NIKE</a>
                        <a href="<?= Url::to(['/catalog/brand/adidas']) ?>" class="link-item">ADIDAS</a>
                        <a href="<?= Url::to(['/catalog/brand/puma']) ?>" class="link-item">PUMA</a>
                        <a href="<?= Url::to(['/catalog/category/krossovki']) ?>" class="link-item">Кроссовки</a>
                        <a href="<?= Url::to(['/catalog/category/kedy']) ?>" class="link-item">Кеды</a>
                        <a href="<?= Url::to(['/catalog/favorites']) ?>" class="link-item">Избранное</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="catalog-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.</p>
        </div>
    </footer>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.catalog-error-page {
    min-height: 100vh;
    background: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    display: flex;
    flex-direction: column;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
    flex: 1;
}

/* Убран catalog-header */

.main-nav {
    display: flex;
    gap: 2rem;
}

.nav-link {
    color: #666666;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.nav-link:hover {
    color: #000000;
}

/* Error Content */
.error-content {
    text-align: center;
    padding: 4rem 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.error-code {
    font-size: 8rem;
    font-weight: 900;
    color: #000000;
    line-height: 1;
    margin-bottom: 1rem;
    letter-spacing: -4px;
}

.error-title {
    font-size: 2rem;
    font-weight: 700;
    color: #000000;
    margin-bottom: 1.5rem;
}

.error-description {
    font-size: 1.125rem;
    color: #666666;
    margin-bottom: 3rem;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 4rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #000000;
    color: #ffffff;
}

.btn-primary:hover {
    background: #333333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: #ffffff;
    color: #000000;
    border: 2px solid #000000;
}

.btn-secondary:hover {
    background: #f9fafb;
}

.popular-links {
    background: #f9fafb;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 3rem;
}

.popular-links h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #000000;
}

.links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.link-item {
    padding: 1rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    color: #000000;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.link-item:hover {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
    transform: translateY(-2px);
}

/* Footer */
.catalog-footer {
    background: #000000;
    color: #ffffff;
    padding: 2rem 0;
    text-align: center;
    margin-top: auto;
}

@media (max-width: 768px) {
    .error-code {
        font-size: 5rem;
    }
    
    .error-title {
        font-size: 1.5rem;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>
