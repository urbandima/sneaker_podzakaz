<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $stats */
/** @var array $recommendations */

$this->title = 'Рекомендации товаров';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/marketing'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<!-- Статистика рекомендаций -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $stats['total_views'] ?? 0 ?></div>
        <div class="admin-stat-label">Показов</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $stats['total_clicks'] ?? 0 ?></div>
        <div class="admin-stat-label">Кликов</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $stats['total_conversions'] ?? 0 ?></div>
        <div class="admin-stat-label">Конверсий</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= number_format($stats['conversion_rate'] ?? 0, 2) ?>%</div>
        <div class="admin-stat-label">Конверсия</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= Yii::$app->formatter->asCurrency($stats['revenue_generated'] ?? 0, 'BYN') ?></div>
        <div class="admin-stat-label">Выручка</div>
    </div>
</div>

<!-- Примеры рекомендаций -->
<?php if (!empty($recommendations)): ?>
    <?php foreach ($recommendations as $item): ?>
        <?php 
        $product = $item['product'];
        $crossSell = $item['cross_sell'] ?? [];
        $upsell = $item['upsell'] ?? [];
        $frequentlyBought = $item['frequently_bought'] ?? [];
        ?>
        
        <div class="admin-card" style="margin-bottom: 2rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="bi bi-box-seam"></i>
                    <?= Html::encode($product->name) ?>
                    <span class="admin-badge admin-badge-primary"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                </h2>
                <div>
                    <a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                        <i class="bi bi-eye"></i> Просмотр
                    </a>
                </div>
            </div>
            
            <div class="admin-card-body">
                <!-- Cross-sell -->
                <?php if (!empty($crossSell)): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--admin-primary);">
                            <i class="bi bi-diagram-2"></i> Cross-sell (похожие товары)
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                            <?php foreach ($crossSell as $rec): ?>
                                <div style="border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; text-align: center;">
                                    <?php $img = $rec->getMainImageUrl(); if ($img): ?>
                                        <img src="<?= Html::encode($img) ?>" style="width: 100%; height: 120px; object-fit: cover;" alt="<?= Html::encode($rec->name) ?>">
                                    <?php endif; ?>
                                    <div style="padding: 0.5rem;">
                                        <div style="font-size: 0.8rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= Html::encode($rec->name) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem;">
                                            <?= Yii::$app->formatter->asCurrency($rec->price, 'BYN') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Upsell -->
                <?php if (!empty($upsell)): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--admin-success);">
                            <i class="bi bi-arrow-up-circle"></i> Upsell (более дорогие альтернативы)
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                            <?php foreach ($upsell as $rec): ?>
                                <div style="border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; text-align: center;">
                                    <?php $img = $rec->getMainImageUrl(); if ($img): ?>
                                        <img src="<?= Html::encode($img) ?>" style="width: 100%; height: 120px; object-fit: cover;" alt="<?= Html::encode($rec->name) ?>">
                                    <?php endif; ?>
                                    <div style="padding: 0.5rem;">
                                        <div style="font-size: 0.8rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= Html::encode($rec->name) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-success); margin-top: 0.25rem;">
                                            <?= Yii::$app->formatter->asCurrency($rec->price, 'BYN') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Frequently bought together -->
                <?php if (!empty($frequentlyBought)): ?>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--admin-info);">
                            <i class="bi bi-cart-check"></i> Часто покупают вместе
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                            <?php foreach ($frequentlyBought as $rec): ?>
                                <div style="border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; text-align: center;">
                                    <?php $img = $rec->getMainImageUrl(); if ($img): ?>
                                        <img src="<?= Html::encode($img) ?>" style="width: 100%; height: 120px; object-fit: cover;" alt="<?= Html::encode($rec->name) ?>">
                                    <?php endif; ?>
                                    <div style="padding: 0.5rem;">
                                        <div style="font-size: 0.8rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= Html::encode($rec->name) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem;">
                                            <?= Yii::$app->formatter->asCurrency($rec->price, 'BYN') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($crossSell) && empty($upsell) && empty($frequentlyBought)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--admin-text-secondary);">
                        <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                        <p style="margin-top: 1rem;">Нет рекомендаций для этого товара</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 3rem; text-align: center; color: var(--admin-text-secondary);">
            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
            <p style="margin-top: 1rem; font-size: 1.1rem;">Нет товаров для генерации рекомендаций</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Добавьте активные товары в каталог</p>
        </div>
    </div>
<?php endif; ?>
