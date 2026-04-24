<?php
use yii\helpers\Html;
use app\backend\shared\helpers\TextHelper;

$this->title = 'Все бренды - СНИКЕРХЭД';
?>

<div class="brands-page">
    <div class="container">
        <h1><i class="bi bi-tags"></i> Все бренды</h1>
        <p class="page-subtitle">Оригинальные товары от мировых производителей</p>

        <div class="brands-grid">
            <?php if (!empty($brands)): ?>
                <?php foreach ($brands as $brand): ?>
                    <a href="/brands/<?= $brand->slug ?>" class="brand-card-link">
                        <div class="brand-card">
                            <?php if ($brand->logo): ?>
                                <img src="<?= $brand->logo ?>" alt="<?= Html::encode($brand->name) ?>" class="brand-card__logo">
                            <?php else: ?>
                                <div class="brand-card__icon"><i class="bi bi-circle"></i></div>
                            <?php endif; ?>
                            <h3 class="brand-card__name"><?= Html::encode($brand->name) ?></h3>
                            <p class="brand-card__count"><?= TextHelper::formatProductCount((int)($brand->products_count ?? 0)) ?></p>
                            <span class="brand-card__cta">Смотреть →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="brands-empty">
                    <div class="brands-empty__icon"><i class="bi bi-box-seam"></i></div>
                    <h3 class="brands-empty__title">Бренды не найдены</h3>
                    <p class="brands-empty__text">Скоро здесь появятся новые бренды</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
