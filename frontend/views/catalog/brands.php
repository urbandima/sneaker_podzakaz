<?php
use yii\helpers\Html;

$this->title = 'Все бренды - СНИКЕРХЭД';
?>

<div class="page-container" style="background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem;"><i class="bi bi-tags"></i> Все бренды</h1>
        <p style="color: #666; margin-bottom: 2rem;">Оригинальные товары от мировых производителей</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
            <?php if (!empty($brands)): ?>
                <?php foreach ($brands as $brand): ?>
                    <a href="/catalog/brand/<?= $brand->slug ?>" style="text-decoration: none;">
                        <div style="background: #fff; border-radius: 12px; padding: 2rem; text-align: center; transition: all 0.2s; border: 1px solid rgba(0,0,0,0.04);">
                            <?php if ($brand->logo): ?>
                                <img src="<?= $brand->logo ?>" alt="<?= Html::encode($brand->name) ?>" style="max-width: 120px; height: auto; margin-bottom: 1rem;">
                            <?php else: ?>
                                <div style="font-size: 3rem; margin-bottom: 1rem;"><i class="bi bi-circle" style="color: #6366f1;"></i></div>
                            <?php endif; ?>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #000; margin-bottom: 0.5rem;">
                                <?= Html::encode($brand->name) ?>
                            </h3>
                            <p style="color: #666; font-size: 0.875rem; margin-bottom: 1rem;">
                                <?= $brand->products_count ?? 0 ?> товаров
                            </p>
                            <span style="display: inline-block; padding: 0.5rem 1rem; background: #000; color: #fff; border-radius: 6px; font-weight: 600; font-size: 0.875rem;">
                                Смотреть →
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;"><i class="bi bi-box-seam" style="color: #94a3b8;"></i></div>
                    <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Бренды не найдены</h3>
                    <p style="color: #666;">Скоро здесь появятся новые бренды</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-container a > div:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
</style>
