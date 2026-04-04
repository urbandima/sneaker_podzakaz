<?php
/**
 * AJAX результаты поиска для живого поиска
 * 
 * @var array $products
 * @var string $query
 */

use yii\helpers\Html;
use yii\helpers\Url;

if (empty($products)): ?>
    <div class="search-results-empty">
        <p>Ничего не найдено по запросу "<?= Html::encode($query) ?>"</p>
    </div>
<?php else: ?>
    <div class="search-results-list">
        <?php foreach ($products as $product): ?>
            <a href="<?= $product->getUrl() ?>" class="search-result-item">
                <div class="search-result-image">
                    <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>" loading="lazy">
                </div>
                <div class="search-result-info">
                    <div class="search-result-brand"><?= Html::encode($product->brand_name) ?></div>
                    <div class="search-result-name"><?= Html::encode($product->name) ?></div>
                    <div class="search-result-price"><?= number_format($product->price, 0, '.', ' ') ?> BYN</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div class="search-results-footer">
        <a href="<?= Url::to(['/catalog/search/index', 'q' => $query]) ?>" class="search-results-all">
            Все результаты →
        </a>
    </div>
<?php endif; ?>

<?php
// CSS для результатов поиска
$this->registerCss("
.search-results-list {
    max-height: 400px;
    overflow-y: auto;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: #f9fafb;
}

.search-result-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f3f4f6;
}

.search-result-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.search-result-info {
    flex: 1;
    min-width: 0;
}

.search-result-brand {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-result-name {
    font-size: 14px;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-result-price {
    font-size: 13px;
    color: #000;
    font-weight: 600;
}

.search-results-empty {
    padding: 30px;
    text-align: center;
    color: #6b7280;
}

.search-results-footer {
    padding: 12px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}

.search-results-all {
    font-size: 14px;
    color: #000;
    text-decoration: none;
    font-weight: 500;
}

.search-results-all:hover {
    text-decoration: underline;
}
");
?>
