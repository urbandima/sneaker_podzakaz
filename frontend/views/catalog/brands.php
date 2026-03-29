<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Все бренды — СНИКЕРХЭД';
?>

<div class="brands-page">
    <div class="container">
        <div class="brands-header">
            <h1><i class="bi bi-tags" aria-hidden="true"></i> Все бренды</h1>
            <p class="brands-subtitle">Оригинальные товары от мировых производителей</p>
        </div>

        <?php if (!empty($brands)): ?>
            <div class="brands-grid">
                <?php foreach ($brands as $brand): ?>
                    <a href="<?= Url::to(['/catalog/catalog/brand', 'slug' => $brand->slug]) ?>"
                       class="brand-card-link">
                        <div class="brand-card">
                            <div class="brand-card__logo">
                                <?php if ($brand->logo): ?>
                                    <img src="<?= Html::encode($brand->logo) ?>"
                                         alt="<?= Html::encode($brand->name) ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <span class="brand-card__icon" aria-hidden="true">
                                        <?= mb_strtoupper(mb_substr($brand->name, 0, 2)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="brand-card__name"><?= Html::encode($brand->name) ?></h3>
                            <p class="brand-card__count">
                                <?= (int)($brand->products_count ?? 0) ?> товаров
                            </p>
                            <span class="btn btn-primary btn-sm brand-card__cta">
                                Смотреть <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-box-seam empty-state__icon" aria-hidden="true"></i>
                <h3 class="empty-state__title">Бренды не найдены</h3>
                <p class="empty-state__text">Скоро здесь появятся новые бренды</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* =====================================================
   Страница брендов — используем design tokens
   ===================================================== */
.brands-page {
    background: var(--surface-secondary);
    min-height: 100vh;
    padding: var(--spacing-12) 0;
}

.brands-header {
    margin-bottom: var(--spacing-8);
}

.brands-header h1 {
    font-size: var(--font-size-3xl);
    font-weight: 900;
    color: var(--text-primary);
    margin-bottom: var(--spacing-2);
}

.brands-subtitle {
    color: var(--text-secondary);
    font-size: var(--font-size-lg);
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: var(--spacing-6);
}

/* Ссылка-обёртка без дефолтных стилей */
.brand-card-link {
    text-decoration: none;
    display: block;
    color: inherit;
}

/* Карточка бренда */
.brand-card {
    background: var(--surface-primary);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    text-align: center;
    transition: transform var(--transition-normal), box-shadow var(--transition-normal);
    box-shadow: var(--card-shadow);
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-3);
}

.brand-card-link:hover .brand-card {
    transform: var(--product-card-hover-transform);
    box-shadow: var(--card-hover-shadow);
}

.brand-card__logo {
    width: 100px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-2);
}

.brand-card__logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Аббревиатура вместо логотипа */
.brand-card__icon {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-black);
    color: var(--color-white);
    border-radius: var(--radius-xl);
    font-size: var(--font-size-xl);
    font-weight: 800;
    letter-spacing: 0.05em;
}

.brand-card__name {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.brand-card__count {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin: 0;
}

.brand-card__cta {
    margin-top: auto;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: var(--spacing-20) var(--spacing-4);
}

.empty-state__icon {
    font-size: 4rem;
    color: var(--text-muted);
    display: block;
    margin-bottom: var(--spacing-4);
}

.empty-state__title {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--spacing-2);
}

.empty-state__text {
    color: var(--text-secondary);
}

/* Адаптивность */
@media (max-width: 768px) {
    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: var(--spacing-4);
    }
    .brand-card {
        padding: var(--spacing-6);
    }
}
</style>
