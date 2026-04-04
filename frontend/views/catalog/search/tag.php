<?php
/**
 * Страница товаров по тегу
 * 
 * @var ProductTag $tag
 * @var array $products
 * @var Pagination $pagination
 * @var array $relatedTags
 * @var int $totalCount
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = $tag->name . ' - Товары с тегом';
$this->params['breadcrumbs'][] = ['label' => 'Каталог', 'url' => ['/catalog/catalog/index']];
$this->params['breadcrumbs'][] = ['label' => 'Теги', 'url' => ['/catalog/search/index']];
$this->params['breadcrumbs'][] = $tag->name;

// SEO
$this->registerMetaTag(['name' => 'description', 'content' => 'Товары с тегом ' . Html::encode($tag->name) . '. ' . ($tag->description ?: '')]);
?>

<div class="tag-page">
    <div class="container">
        <!-- Заголовок тега -->
        <div class="tag-header" <?= $tag->color ? 'style="background-color: ' . $tag->color . '20"' : '' ?>>
            <div class="tag-header-content">
                <?php if ($tag->color): ?>
                    <span class="tag-badge" style="background-color: <?= $tag->color ?>">
                        <?= Html::encode($tag->name) ?>
                    </span>
                <?php else: ?>
                    <span class="tag-badge"><?= Html::encode($tag->name) ?></span>
                <?php endif; ?>
                
                <h1 class="tag-title">Товары с тегом «<?= Html::encode($tag->name) ?>»</h1>
                
                <?php if ($tag->description): ?>
                    <p class="tag-description"><?= Html::encode($tag->description) ?></p>
                <?php endif; ?>
                
                <p class="tag-count">Найдено товаров: <?= $totalCount ?></p>
            </div>
        </div>

        <div class="tag-layout">
            <!-- Боковая панель с похожими тегами -->
            <?php if (!empty($relatedTags)): ?>
                <aside class="tag-sidebar">
                    <div class="tag-sidebar-section">
                        <h3 class="tag-sidebar-title">Другие теги</h3>
                        <div class="tag-list">
                            <?php foreach ($relatedTags as $relatedTag): ?>
                                <a href="<?= Url::to(['/catalog/search/tag', 'slug' => $relatedTag->slug]) ?>" 
                                   class="tag-item"
                                   <?= $relatedTag->color ? 'style="border-left-color: ' . $relatedTag->color . '"' : '' ?>>
                                    <?= Html::encode($relatedTag->name) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>

            <!-- Список товаров -->
            <div class="tag-content">
                <?php if (empty($products)): ?>
                    <div class="tag-empty">
                        <p>В данный момент нет товаров с этим тегом.</p>
                        <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary">
                            Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <?= $this->render('_product_card', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Пагинация -->
                    <div class="tag-pagination">
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'pagination'],
                            'linkOptions' => ['class' => 'page-link'],
                            'activePageCssClass' => 'active',
                            'disabledPageCssClass' => 'disabled',
                            'prevPageLabel' => '←',
                            'nextPageLabel' => '→',
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss("
.tag-page {
    padding: 40px 0;
}

.tag-header {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 40px;
    text-align: center;
}

.tag-header-content {
    max-width: 600px;
    margin: 0 auto;
}

.tag-badge {
    display: inline-block;
    padding: 8px 20px;
    background: #000;
    color: #fff;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}

.tag-title {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 12px;
}

.tag-description {
    color: #6b7280;
    margin-bottom: 16px;
}

.tag-count {
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}

.tag-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 40px;
}

@media (max-width: 768px) {
    .tag-layout {
        grid-template-columns: 1fr;
    }
}

.tag-sidebar-section {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tag-sidebar-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #111827;
}

.tag-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tag-item {
    display: block;
    padding: 10px 12px;
    background: #f9fafb;
    border-radius: 8px;
    font-size: 14px;
    color: #374151;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.2s;
}

.tag-item:hover {
    background: #f3f4f6;
    color: #000;
}

.tag-empty {
    text-align: center;
    padding: 60px 20px;
}

.tag-empty p {
    color: #6b7280;
    margin-bottom: 20px;
}

.tag-pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}
");
?>
