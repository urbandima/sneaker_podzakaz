<?php
/**
 * Страница результатов поиска
 * 
 * @var array $products
 * @var Pagination $pagination
 * @var string $query
 * @var ProductTag|null $currentTag
 * @var array $popularTags
 * @var string $sort
 * @var int $totalCount
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\backend\modules\catalog\models\ProductTag;

$this->title = $query ? 'Поиск: ' . Html::encode($query) : ($currentTag ? $currentTag->name : 'Поиск товаров');
$this->params['breadcrumbs'][] = ['label' => 'Каталог', 'url' => ['/catalog/catalog/index']];
$this->params['breadcrumbs'][] = $this->title;

// SEO
$this->registerMetaTag(['name' => 'description', 'content' => 'Поиск товаров по запросу: ' . Html::encode($query)]);
?>

<div class="search-page">
    <div class="container">
        <!-- Заголовок -->
        <div class="search-header">
            <h1 class="search-title">
                <?php if ($query): ?>
                    Результаты поиска: "<?= Html::encode($query) ?>"
                <?php elseif ($currentTag): ?>
                    Товары с тегом: <?= Html::encode($currentTag->name) ?>
                <?php else: ?>
                    Поиск товаров
                <?php endif; ?>
            </h1>
            
            <?php if ($totalCount > 0): ?>
                <p class="search-count">Найдено товаров: <?= $totalCount ?></p>
            <?php endif; ?>
        </div>

        <div class="search-layout">
            <!-- Боковая панель с тегами -->
            <aside class="search-sidebar">
                <?php if (!empty($popularTags)): ?>
                    <div class="search-sidebar-section">
                        <h3 class="search-sidebar-title">Популярные теги</h3>
                        <div class="search-tags-cloud">
                            <?php foreach ($popularTags as $tag): ?>
                                <a href="<?= Url::to(['/catalog/search/tag', 'slug' => $tag->slug]) ?>" 
                                   class="search-tag<?= $currentTag && $currentTag->id === $tag->id ? ' active' : '' ?>"
                                   <?= $tag->color ? 'style="background-color: ' . $tag->color . '"' : '' ?>>
                                    <?= Html::encode($tag->name) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($currentTag): ?>
                    <div class="search-sidebar-section">
                        <h3 class="search-sidebar-title">О теге</h3>
                        <div class="current-tag-info">
                            <?php if ($currentTag->color): ?>
                                <span class="tag-color" style="background-color: <?= $currentTag->color ?>"></span>
                            <?php endif; ?>
                            <span class="tag-name"><?= Html::encode($currentTag->name) ?></span>
                            <?php if ($currentTag->description): ?>
                                <p class="tag-description"><?= Html::encode($currentTag->description) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- Основной контент -->
            <div class="search-content">
                <!-- Форма поиска -->
                <div class="search-form-container">
                    <form action="<?= Url::to(['/catalog/search/index']) ?>" method="get" class="search-form">
                        <input type="text" name="q" value="<?= Html::encode($query) ?>" 
                               class="search-input" placeholder="Поиск товаров..."
                               autocomplete="off">
                        <button type="submit" class="search-submit">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <circle cx="9" cy="9" r="8" stroke="currentColor" stroke-width="2"/>
                                <line x1="15" y1="15" x2="19" y2="19" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Сортировка -->
                <?php if ($totalCount > 0): ?>
                    <div class="search-sort">
                        <span class="search-sort-label">Сортировка:</span>
                        <?php
                        $sortOptions = [
                            'relevance' => 'По релевантности',
                            'price_asc' => 'Цена: по возрастанию',
                            'price_desc' => 'Цена: по убыванию',
                            'new' => 'Сначала новые',
                            'popular' => 'Популярные',
                        ];
                        foreach ($sortOptions as $key => $label):
                            $isActive = $sort === $key;
                            $url = array_merge(['/catalog/search/index'], 
                                array_filter(['q' => $query, 'tag' => $currentTag ? $currentTag->slug : null, 'sort' => $key !== 'relevance' ? $key : null]));
                        ?>
                            <a href="<?= Url::to($url) ?>" class="search-sort-link<?= $isActive ? ' active' : '' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Результаты -->
                <?php if (empty($products)): ?>
                    <div class="search-empty">
                        <div class="search-empty-icon">🔍</div>
                        <h2>Ничего не найдено</h2>
                        <p>По вашему запросу не найдено товаров. Попробуйте изменить запрос или посмотрите популярные товары.</p>
                        
                        <div class="search-suggestions">
                            <h3>Попробуйте:</h3>
                            <ul>
                                <li>Проверить правописание</li>
                                <li>Использовать более общие слова</li>
                                <li>Уменьшить количество слов в запросе</li>
                                <li>Использовать фильтры по тегам справа</li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <?= $this->render('_product_card', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Пагинация -->
                    <div class="search-pagination">
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
// CSS для страницы поиска
$this->registerCss("
.search-page {
    padding: 40px 0;
}

.search-header {
    margin-bottom: 30px;
}

.search-title {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 10px;
}

.search-count {
    color: #6b7280;
    font-size: 16px;
}

.search-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 40px;
}

@media (max-width: 768px) {
    .search-layout {
        grid-template-columns: 1fr;
    }
}

.search-sidebar {
    space-y: 24px;
}

.search-sidebar-section {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.search-sidebar-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #111827;
}

.search-tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.search-tag {
    display: inline-block;
    padding: 6px 12px;
    background: #f3f4f6;
    border-radius: 20px;
    font-size: 13px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.search-tag:hover,
.search-tag.active {
    background: #000;
    color: #fff;
}

.current-tag-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tag-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.tag-name {
    font-weight: 500;
}

.tag-description {
    margin-top: 10px;
    font-size: 13px;
    color: #6b7280;
}

.search-form-container {
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #000;
}

.search-submit {
    padding: 12px 24px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.search-submit:hover {
    background: #374151;
}

.search-sort {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.search-sort-label {
    color: #6b7280;
    font-size: 14px;
}

.search-sort-link {
    font-size: 14px;
    color: #374151;
    text-decoration: none;
    transition: color 0.2s;
}

.search-sort-link:hover,
.search-sort-link.active {
    color: #000;
    font-weight: 500;
}

.search-empty {
    text-align: center;
    padding: 60px 20px;
}

.search-empty-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.search-empty h2 {
    font-size: 20px;
    margin-bottom: 10px;
}

.search-empty p {
    color: #6b7280;
    max-width: 400px;
    margin: 0 auto 30px;
}

.search-suggestions {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    max-width: 400px;
    margin: 0 auto;
    text-align: left;
}

.search-suggestions h3 {
    font-size: 14px;
    margin-bottom: 10px;
}

.search-suggestions ul {
    list-style: disc;
    padding-left: 20px;
    color: #6b7280;
    font-size: 14px;
}

.search-suggestions li {
    margin-bottom: 5px;
}

.search-pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    gap: 8px;
    list-style: none;
}

.page-link {
    display: block;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.page-link:hover,
.pagination .active .page-link {
    background: #000;
    color: #fff;
    border-color: #000;
}
");
?>
