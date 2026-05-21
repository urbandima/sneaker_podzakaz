<?php
/**
 * @var \yii\web\View $this
 * @var \app\backend\modules\catalog\models\Product[] $products
 * @var \yii\data\Pagination $pagination
 * @var string $query
 * @var \app\backend\modules\catalog\models\ProductTag|null $currentTag
 * @var \app\backend\modules\catalog\models\ProductTag[] $popularTags
 * @var string $sort
 * @var int $totalCount
 * @var string[] $highlightedNames  product_id => HTML with <mark> tags
 * @var string|null $suggestion     Levenshtein suggestion when $totalCount === 0
 * @var array $facets               [brands, categories, priceMin, priceMax]
 * @var int[] $currentBrandIds
 * @var int[] $currentCatIds
 * @var string|null $priceFrom
 * @var string|null $priceTo
 * @var array $popularSearches      [{query, cnt}]
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\frontend\assets\CatalogAsset;

CatalogAsset::register($this);

$this->params['breadcrumbs'][] = ['label' => 'Каталог', 'url' => ['/catalog']];
$this->params['breadcrumbs'][] = $this->title;

// Build canonical filter URL helper
$filterUrl = function (array $extra = []) use ($query, $sort, $currentBrandIds, $currentCatIds, $priceFrom, $priceTo, $currentTag) {
    $params = array_filter(array_merge([
        '/catalog/search/index',
        'q'          => $query,
        'sort'       => $sort !== 'relevance' ? $sort : null,
        'tag'        => $currentTag ? $currentTag->slug : null,
        'price_from' => $priceFrom,
        'price_to'   => $priceTo,
    ], $extra));
    foreach ($currentBrandIds as $id) { $params['brand'][] = $id; }
    foreach ($currentCatIds as $id) { $params['category'][] = $id; }
    return Url::to($params);
};
?>

<div class="search-page">
    <div class="container">

        <!-- Search header -->
        <div class="search-header">
            <h1 class="search-title">
                <?php if ($query): ?>
                    Результаты поиска: <em>"<?= Html::encode($query) ?>"</em>
                <?php elseif ($currentTag): ?>
                    Товары с тегом: <?= Html::encode($currentTag->name) ?>
                <?php else: ?>
                    Поиск товаров
                <?php endif; ?>
            </h1>
            <?php if ($totalCount > 0): ?>
                <p class="search-count">Найдено: <strong><?= $totalCount ?></strong></p>
            <?php endif; ?>
        </div>

        <div class="search-layout">

            <!-- ── Sidebar ─────────────────────────────────────────────── -->
            <aside class="search-sidebar">

                <!-- Search form -->
                <form action="<?= Url::to(['/catalog/search/index']) ?>" method="get" class="search-form-mini">
                    <input type="text" name="q" value="<?= Html::encode($query) ?>"
                           class="search-input" placeholder="Поиск…" autocomplete="off">
                    <button type="submit" class="search-submit" aria-label="Найти">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <!-- Popular searches -->
                <?php if (!empty($popularSearches)): ?>
                <div class="search-sidebar-section">
                    <h3 class="search-sidebar-title">Популярные запросы</h3>
                    <div class="search-tags-cloud">
                        <?php foreach ($popularSearches as $ps): ?>
                            <a href="<?= Url::to(['/catalog/search/index', 'q' => $ps['query']]) ?>"
                               class="search-tag<?= $ps['query'] === $query ? ' active' : '' ?>">
                                <?= Html::encode($ps['query']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Brand facets -->
                <?php if (!empty($facets['brands'])): ?>
                <div class="search-sidebar-section">
                    <h3 class="search-sidebar-title">Бренд</h3>
                    <?php foreach ($facets['brands'] as $b):
                        if (empty($b['brand_id'])) continue;
                        $active  = in_array($b['brand_id'], $currentBrandIds);
                        $newIds  = $active
                            ? array_values(array_diff($currentBrandIds, [$b['brand_id']]))
                            : array_merge($currentBrandIds, [$b['brand_id']]);
                        $url     = Url::to(array_merge(
                            ['/catalog/search/index', 'q' => $query, 'sort' => $sort !== 'relevance' ? $sort : null],
                            array_map(fn($id) => ['brand[]' => $id], $newIds)
                        ));
                    ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" <?= $active ? 'checked' : '' ?>
                                   onchange="window.location='<?= Url::to(array_merge(
                                       ['/catalog/search/index', 'q' => $query],
                                       [['brand[]' => $b['brand_id']]]
                                   )) ?>'">
                            <span class="checkbox-mark"></span>
                            <span><?= Html::encode($b['brand_name'] ?? 'Без бренда') ?></span>
                            <span class="filter-count"><?= (int)$b['cnt'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Category facets -->
                <?php if (!empty($facets['categories'])): ?>
                <div class="search-sidebar-section">
                    <h3 class="search-sidebar-title">Категория</h3>
                    <?php foreach ($facets['categories'] as $cat):
                        if (empty($cat['category_id'])) continue;
                        $active = in_array($cat['category_id'], $currentCatIds);
                    ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" <?= $active ? 'checked' : '' ?>
                                   onchange="searchFacetToggle('category', <?= (int)$cat['category_id'] ?>, this.checked)">
                            <span class="checkbox-mark"></span>
                            <span><?= Html::encode($cat['category_name'] ?? 'Без категории') ?></span>
                            <span class="filter-count"><?= (int)$cat['cnt'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Price range -->
                <div class="search-sidebar-section">
                    <h3 class="search-sidebar-title">Цена (BYN)</h3>
                    <form method="get" action="<?= Url::to(['/catalog/search/index']) ?>" class="price-range-form">
                        <input type="hidden" name="q" value="<?= Html::encode($query) ?>">
                        <?php if ($sort !== 'relevance'): ?>
                            <input type="hidden" name="sort" value="<?= Html::encode($sort) ?>">
                        <?php endif; ?>
                        <?php foreach ($currentBrandIds as $id): ?>
                            <input type="hidden" name="brand[]" value="<?= (int)$id ?>">
                        <?php endforeach; ?>
                        <?php foreach ($currentCatIds as $id): ?>
                            <input type="hidden" name="category[]" value="<?= (int)$id ?>">
                        <?php endforeach; ?>
                        <div class="price-inputs">
                            <input type="number" name="price_from" placeholder="от"
                                   value="<?= Html::encode($priceFrom ?? '') ?>" min="0" class="price-input">
                            <span>—</span>
                            <input type="number" name="price_to" placeholder="до"
                                   value="<?= Html::encode($priceTo ?? '') ?>" min="0" class="price-input">
                            <button type="submit" class="btn btn-sm btn-primary">OK</button>
                        </div>
                        <div class="price-hint">
                            <?= number_format($facets['priceMin']) ?> — <?= number_format($facets['priceMax']) ?> BYN
                        </div>
                    </form>
                </div>

                <!-- Popular tags -->
                <?php if (!empty($popularTags)): ?>
                <div class="search-sidebar-section">
                    <h3 class="search-sidebar-title">Популярные теги</h3>
                    <div class="search-tags-cloud">
                        <?php foreach ($popularTags as $tag): ?>
                            <a href="<?= Url::to(['/catalog/search/tag', 'slug' => $tag->slug]) ?>"
                               class="search-tag<?= $currentTag && $currentTag->id === $tag->id ? ' active' : '' ?>"
                               <?= $tag->color ? 'style="background-color:' . Html::encode($tag->color) . '"' : '' ?>>
                                <?= Html::encode($tag->name) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </aside>

            <!-- ── Main content ──────────────────────────────────────── -->
            <div class="search-content">

                <!-- Sort toolbar -->
                <?php if ($totalCount > 0): ?>
                <div class="search-sort">
                    <span class="search-sort-label">Сортировка:</span>
                    <?php
                    $sortOptions = [
                        'relevance'  => 'По релевантности',
                        'price_asc'  => 'Цена ↑',
                        'price_desc' => 'Цена ↓',
                        'new'        => 'Новинки',
                        'popular'    => 'Популярные',
                    ];
                    foreach ($sortOptions as $key => $label): ?>
                        <a href="<?= Url::to(array_filter([
                                '/catalog/search/index',
                                'q'    => $query,
                                'sort' => $key !== 'relevance' ? $key : null,
                            ])) ?>"
                           class="search-sort-link<?= $sort === $key ? ' active' : '' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Active filters -->
                <?php if (!empty($currentBrandIds) || !empty($currentCatIds) || $priceFrom || $priceTo): ?>
                <div class="active-filters" style="margin-bottom:12px">
                    <?php foreach ($facets['brands'] as $b):
                        if (!in_array($b['brand_id'], $currentBrandIds)) continue; ?>
                        <span class="filter-tag">
                            <?= Html::encode($b['brand_name']) ?>
                            <a href="<?= Url::to(['/catalog/search/index', 'q' => $query]) ?>" class="filter-tag-remove"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endforeach; ?>
                    <?php if ($priceFrom || $priceTo): ?>
                        <span class="filter-tag">
                            <?= Html::encode($priceFrom ?? '0') ?> — <?= Html::encode($priceTo ?? '∞') ?> BYN
                            <a href="<?= Url::to(['/catalog/search/index', 'q' => $query]) ?>" class="filter-tag-remove"><i class="bi bi-x"></i></a>
                        </span>
                    <?php endif; ?>
                    <a href="<?= Url::to(['/catalog/search/index', 'q' => $query]) ?>" class="filter-clear-all">Сбросить фильтры</a>
                </div>
                <?php endif; ?>

                <!-- Results grid -->
                <?php if (empty($products)): ?>
                    <div class="search-empty">
                        <div class="search-empty-icon"><i class="bi bi-search"></i></div>
                        <h2>Ничего не найдено</h2>
                        <?php if ($suggestion): ?>
                            <p class="search-suggestion">
                                Возможно, вы имели в виду:
                                <a href="<?= Url::to(['/catalog/search/index', 'q' => $suggestion]) ?>">
                                    <strong><?= Html::encode($suggestion) ?></strong>
                                </a>?
                            </p>
                        <?php else: ?>
                            <p>По запросу <strong>"<?= Html::encode($query) ?>"</strong> ничего не найдено. Попробуйте изменить запрос или выбрать другую категорию.</p>
                        <?php endif; ?>

                        <?php if (!empty($popularSearches)): ?>
                        <div class="search-empty-popular">
                            <p>Популярные запросы:</p>
                            <?php foreach (array_slice($popularSearches, 0, 6) as $ps): ?>
                                <a href="<?= Url::to(['/catalog/search/index', 'q' => $ps['query']]) ?>"
                                   class="search-tag">
                                    <?= Html::encode($ps['query']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="products-grid search-products-grid">
                        <?php foreach ($products as $product):
                            // Pass highlighted name via a wrapper; cards use $product->name internally.
                            // For highlighting, we overlay a custom title in the card area below.
                        ?>
                            <div class="search-result-card">
                                <?= $this->render('//catalog/_product_card', ['product' => $product]) ?>
                                <?php if (!empty($highlightedNames[$product->id])): ?>
                                <div class="search-highlighted-name" aria-hidden="true">
                                    <?= $highlightedNames[$product->id] ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="search-pagination">
                        <?= LinkPager::widget([
                            'pagination'          => $pagination,
                            'options'             => ['class' => 'pagination'],
                            'linkOptions'         => ['class' => 'page-link'],
                            'activePageCssClass'  => 'active',
                            'disabledPageCssClass'=> 'disabled',
                            'prevPageLabel'       => '←',
                            'nextPageLabel'       => '→',
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->registerCss("
/* Search page */
.search-page { padding: 40px 0; }
.search-header { margin-bottom: 24px; }
.search-title { font-size: 1.6rem; font-weight: 700; }
.search-title em { font-style: normal; color: var(--color-primary, #000); }
.search-count { color: var(--color-text-muted, #6b7280); }

.search-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
    align-items: start;
}
@media (max-width: 768px) { .search-layout { grid-template-columns: 1fr; } }

/* Sidebar */
.search-sidebar { display: flex; flex-direction: column; gap: 16px; }
.search-sidebar-section {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #e5e7eb);
    border-radius: 12px;
    padding: 18px;
}
.search-sidebar-title { font-size: .95rem; font-weight: 600; margin-bottom: 12px; }

.search-form-mini { display: flex; gap: 8px; }
.search-form-mini .search-input {
    flex: 1; padding: 10px 14px;
    border: 1.5px solid var(--color-border, #e5e7eb);
    border-radius: 8px; font-size: .9rem;
}
.search-form-mini .search-input:focus { outline: none; border-color: #000; }
.search-form-mini .search-submit {
    padding: 10px 14px; background: #000; color: #fff;
    border: none; border-radius: 8px; cursor: pointer;
}

.search-tags-cloud { display: flex; flex-wrap: wrap; gap: 6px; }
.search-tag {
    display: inline-block; padding: 4px 10px;
    background: #f3f4f6; border-radius: 20px;
    font-size: .8rem; color: #374151; text-decoration: none;
    transition: background .15s, color .15s;
}
.search-tag:hover, .search-tag.active { background: #000; color: #fff; }

.price-inputs { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.price-inputs .price-input { width: 80px; padding: 6px 8px; border: 1.5px solid #e5e7eb; border-radius: 6px; font-size: .85rem; }
.price-hint { font-size: .8rem; color: #9ca3af; }

/* Sort */
.search-sort {
    display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
    margin-bottom: 16px; padding-bottom: 12px;
    border-bottom: 1px solid var(--color-border, #e5e7eb);
}
.search-sort-label { color: #6b7280; font-size: .85rem; }
.search-sort-link { font-size: .85rem; color: #374151; text-decoration: none; padding: 3px 8px; border-radius: 4px; }
.search-sort-link:hover { background: #f3f4f6; }
.search-sort-link.active { background: #000; color: #fff; }

/* Results */
.search-result-card { position: relative; }
.search-highlighted-name {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 4px 8px;
    font-size: .8rem; line-height: 1.3;
    background: rgba(255,255,255,.9);
    pointer-events: none; border-radius: 0 0 8px 8px;
}
mark { background: #fef08a; color: inherit; padding: 0 1px; border-radius: 2px; }

/* Empty state */
.search-empty { text-align: center; padding: 60px 20px; }
.search-empty-icon { font-size: 3rem; color: #d1d5db; margin-bottom: 16px; }
.search-empty h2 { font-size: 1.25rem; margin-bottom: 10px; }
.search-suggestion { font-size: 1rem; margin-bottom: 24px; }
.search-suggestion a { color: #000; text-decoration: underline; }
.search-empty-popular { margin-top: 24px; display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; align-items: center; }
.search-empty-popular p { width: 100%; color: #6b7280; font-size: .9rem; }

/* Pagination */
.search-pagination { margin-top: 40px; display: flex; justify-content: center; }
.pagination { display: flex; gap: 6px; list-style: none; padding: 0; flex-wrap: wrap; }
.page-link {
    display: block; padding: 7px 12px;
    border: 1px solid #e5e7eb; border-radius: 6px;
    color: #374151; text-decoration: none;
    font-size: .875rem; transition: all .15s;
}
.page-link:hover, .pagination .active .page-link { background: #000; color: #fff; border-color: #000; }
") ?>

<script>
function searchFacetToggle(type, id, checked) {
    var url = new URL(window.location.href);
    var params = url.searchParams;
    var key = type + '[]';
    var vals = params.getAll(key).map(Number).filter(Boolean);
    if (checked) { if (!vals.includes(id)) vals.push(id); }
    else { vals = vals.filter(function(v){ return v !== id; }); }
    params.delete(key);
    vals.forEach(function(v){ params.append(key, v); });
    window.location.href = url.toString();
}
</script>
