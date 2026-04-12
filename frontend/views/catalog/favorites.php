<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\ProductFavorite[] $favorites */
/** @var app\backend\modules\account\models\Customer|null $customer */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\CatalogAsset;

$this->title = 'Избранное - СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Ваши избранные товары']);

CatalogAsset::register($this);

$this->registerJsFile('@web/js/favorites.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/lazy-load.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/catalog.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/global-helpers.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<?php if ($customer): ?>
<div class="account-page">
    <div class="account-container">
        <nav class="account-breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <a href="/account">Личный кабинет</a>
            <span>/</span>
            <span class="current">Избранное</span>
        </nav>

        <div class="account-header">
            <h1><i class="bi bi-heart"></i> Личный кабинет</h1>
        </div>

        <div class="account-grid">
            <?= $this->render('../account/_sidebar', [
                'customer' => $customer,
                'activePage' => 'favorites',
            ]) ?>

            <main class="account-content">
                <div class="content-card">
                    <div class="content-header">
                        <h2><i class="bi bi-heart-fill"></i> Избранное <span class="orders-count"><?= count($favorites) ?></span></h2>
                    </div>

                    <?php if (empty($favorites)): ?>
                        <div class="empty-orders">
                            <i class="bi bi-heart"></i>
                            <h3>Избранное пустое</h3>
                            <p>Вы еще не добавили ни одного товара в избранное</p>
                            <a href="/catalog" class="btn btn-primary">
                                <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="catalog-toolbar" style="margin-bottom: 1.5rem;">
                            <div class="toolbar-left">
                                <span class="toolbar-meta">
                                    <i class="bi bi-heart-fill icon-danger"></i>
                                    <?= count($favorites) ?> <?= count($favorites) === 1 ? 'товар' : (count($favorites) < 5 ? 'товара' : 'товаров') ?>
                                </span>
                            </div>
                            <div class="toolbar-right">
                                <div class="sort-select">
                                    <label><i class="bi bi-sort-down"></i> Сортировка:</label>
                                    <select id="sortSelect" onchange="sortFavorites(this.value)">
                                        <option value="date_desc">Недавно добавленные</option>
                                        <option value="date_asc">Давно добавленные</option>
                                        <option value="price_asc">Цена: по возрастанию</option>
                                        <option value="price_desc">Цена: по убыванию</option>
                                        <option value="name_asc">По названию (А-Я)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="products grid-4" id="products">
                            <?= $this->render('_products', ['products' => array_map(function($fav) { return $fav->product; }, array_filter($favorites, function($fav) { return $fav->product !== null; }))]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>
<?php else: ?>
<div class="catalog-page">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a> /
            <a href="/catalog">Каталог</a> /
            <span>Избранное</span>
        </nav>

        <div style="width: 100%;">
            <main class="content" style="max-width: 100%;">
                <div class="content-header">
                    <h1>Избранное <span class="products-count">(<span id="productsCount"><?= count($favorites) ?></span>)</span></h1>
                </div>

                <!-- Баннер для гостей: сохраните избранное -->
                <div class="favorites-guest-banner">
                    <div class="favorites-guest-banner__icon"><i class="bi bi-heart-fill"></i></div>
                    <div class="favorites-guest-banner__text">
                        <strong>Войдите, чтобы сохранить избранное навсегда</strong>
                        <span>Список сохраняется в текущей сессии. После входа всё перенесётся в аккаунт.</span>
                    </div>
                    <a href="/account/login" class="btn btn-primary btn-sm">
                        <i class="bi bi-person"></i> Войти
                    </a>
                </div>

                <?php if (empty($favorites)): ?>
                    <div class="empty-state">
                        <div class="empty-state__icon"><i class="bi bi-heart"></i></div>
                        <h3 class="empty-state__title">Избранное пустое</h3>
                        <p class="empty-state__text">Вы еще не добавили ни одного товара в избранное</p>
                        <a href="/catalog" class="btn btn-primary">
                            <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <span class="toolbar-meta">
                                <i class="bi bi-heart-fill icon-danger"></i>
                                <?= count($favorites) ?> <?= count($favorites) === 1 ? 'товар' : (count($favorites) < 5 ? 'товара' : 'товаров') ?>
                            </span>
                        </div>
                        <div class="toolbar-right">
                            <div class="sort-select">
                                <label><i class="bi bi-sort-down"></i> Сортировка:</label>
                                <select id="sortSelect" onchange="sortFavorites(this.value)">
                                    <option value="date_desc">Недавно добавленные</option>
                                    <option value="date_asc">Давно добавленные</option>
                                    <option value="price_asc">Цена: по возрастанию</option>
                                    <option value="price_desc">Цена: по убыванию</option>
                                    <option value="name_asc">По названию (А-Я)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="products grid-5" id="products">
                        <?= $this->render('_products', ['products' => array_map(function($fav) { return $fav->product; }, array_filter($favorites, function($fav) { return $fav->product !== null; }))]) ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function sortFavorites(sortType) {
    const container = document.getElementById('products');
    const products = Array.from(container.children);

    products.sort((a, b) => {
        const priceA = parseFloat(a.querySelector('.product-card-price-current')?.textContent.replace(/[^\d.]/g, '') || 0);
        const priceB = parseFloat(b.querySelector('.product-card-price-current')?.textContent.replace(/[^\d.]/g, '') || 0);
        const nameA = a.querySelector('.product-card-name')?.textContent.trim() || '';
        const nameB = b.querySelector('.product-card-name')?.textContent.trim() || '';

        switch(sortType) {
            case 'price_asc': return priceA - priceB;
            case 'price_desc': return priceB - priceA;
            case 'name_asc': return nameA.localeCompare(nameB, 'ru');
            case 'date_asc': return 1;
            case 'date_desc':
            default: return -1;
        }
    });

    container.innerHTML = '';
    products.forEach(product => container.appendChild(product));
}
</script>
