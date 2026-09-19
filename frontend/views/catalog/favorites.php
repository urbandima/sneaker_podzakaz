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

<?php if ($customer) : ?>
<div class="account-page">
    <div class="account-container">
        <nav class="breadcrumb">
            <a href="/">Главная</a>
            <span class="breadcrumb-separator">/</span>
            <a href="/account">Личный кабинет</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Избранное</span>
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

                    <?php if (empty($favorites)) : ?>
                        <div class="empty-orders">
                            <i class="bi bi-heart"></i>
                            <h3>Избранное пустое</h3>
                            <p>Вы еще не добавили ни одного товара в избранное</p>
                            <a href="/catalog" class="btn btn-primary">
                                <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="catalog-toolbar mb-5">
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
                            <?= $this->render('_products', ['products' => array_map(function ($fav) {
    return $fav->product;
                            }, array_filter($favorites, function ($fav) {
                                return $fav->product !== null;
                            }))]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>
<?php else : ?>
<div class="catalog-page">
    <div class="container">
        <nav class="breadcrumb">
            <a href="/">Главная</a>
            <span class="breadcrumb-separator">/</span>
            <a href="/catalog">Каталог</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Избранное</span>
        </nav>

        <div class="w-100">
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

                <!-- Состояние загрузки -->
                <div id="guestFavLoading" style="display:none;text-align:center;padding:40px 0">
                    <i class="bi bi-arrow-repeat spinner" style="font-size:2rem;color:var(--color-text-muted)"></i>
                </div>

                <!-- Пустое состояние -->
                <div id="guestFavEmpty" class="empty-state" style="display:none">
                    <div class="empty-state__icon"><i class="bi bi-heart"></i></div>
                    <h3 class="empty-state__title">Избранное пустое</h3>
                    <p class="empty-state__text">Вы еще не добавили ни одного товара в избранное</p>
                    <a href="/catalog" class="btn btn-primary">
                        <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                    </a>
                </div>

                <!-- Список товаров -->
                <div id="guestFavProducts" style="display:none">
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <span class="toolbar-meta">
                                <i class="bi bi-heart-fill icon-danger"></i>
                                <span id="guestFavCount">0</span> <span id="guestFavWord">товаров</span>
                            </span>
                        </div>
                        <div class="toolbar-right">
                            <div class="sort-select">
                                <label><i class="bi bi-sort-down"></i> Сортировка:</label>
                                <select id="sortSelect" onchange="sortFavorites(this.value)">
                                    <option value="date_desc">Недавно добавленные</option>
                                    <option value="price_asc">Цена: по возрастанию</option>
                                    <option value="price_desc">Цена: по убыванию</option>
                                    <option value="name_asc">По названию (А-Я)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="products grid-5" id="products"></div>
                </div>

                <script>
                (function () {
                    var GUEST_KEY = 'guestWishlist';
                    function getIds() {
                        try { return JSON.parse(localStorage.getItem(GUEST_KEY) || '[]'); } catch(e) { return []; }
                    }

                    function pluralRu(n) {
                        var r = n % 100;
                        if (r >= 11 && r <= 14) return 'товаров';
                        switch (n % 10) {
                            case 1: return 'товар';
                            case 2: case 3: case 4: return 'товара';
                            default: return 'товаров';
                        }
                    }

                    function renderCard(p) {
                        var imgHtml = p.image
                            ? '<img src="' + p.image + '" alt="' + p.name + '" class="product-image primary" loading="lazy" width="600" height="600">'
                            : '';
                        return '<article class="product-card" data-product-id="' + p.id + '">'
                            + '<div class="product-image-wrapper' + (p.image ? '' : ' is-placeholder') + '">'
                            + '<a href="' + p.url + '" class="product-link">' + imgHtml + '</a>'
                            + '<div class="product-card-empty-placeholder" aria-hidden="true">'
                            + '<svg class="product-card-empty-placeholder__icon" viewBox="0 0 64 64" fill="none"><rect x="6" y="14" width="52" height="36" rx="4" stroke="currentColor" stroke-width="2.5"/><circle cx="22" cy="26" r="4" fill="currentColor"/><path d="M10 46l14-16 12 12 8-8 10 12" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" fill="none"/></svg>'
                            + '</div>'
                            + '<button class="action-btn favorite btn-favorite active" data-product-id="' + p.id + '" onclick="toggleFav(event,' + p.id + ')" aria-label="Убрать из избранного">'
                            + '<i class="bi bi-heart-fill"></i></button>'
                            + '</div>'
                            + '<div class="product-info">'
                            + (p.brand ? '<div class="product-card-brand">' + p.brand + '</div>' : '')
                            + '<h3 class="product-card-name"><a href="' + p.url + '">' + p.name + '</a></h3>'
                            + '<div class="product-price"><span class="product-card-price-current">' + p.price + '</span></div>'
                            + '<button class="product-card-add-to-cart" onclick="quickAddToCart(event,' + p.id + ')" aria-label="Добавить в корзину">'
                            + '<i class="bi bi-bag-plus"></i> В корзину</button>'
                            + '</div></article>';
                    }

                    function loadGuestFavorites() {
                        var ids = getIds();
                        var loading = document.getElementById('guestFavLoading');
                        var empty   = document.getElementById('guestFavEmpty');
                        var list    = document.getElementById('guestFavProducts');
                        var cnt     = document.getElementById('productsCount');

                        if (!ids.length) {
                            if (empty) empty.style.display = '';
                            if (cnt) cnt.textContent = '0';
                            return;
                        }

                        if (loading) loading.style.display = '';

                        fetch('/api/catalog/products-by-ids?ids=' + ids.join(','))
                            .then(function(r) { return r.json(); })
                            .then(function(products) {
                                if (loading) loading.style.display = 'none';

                                if (!products || !products.length) {
                                    if (empty) empty.style.display = '';
                                    return;
                                }

                                var container = document.getElementById('products');
                                var countEl   = document.getElementById('guestFavCount');
                                var wordEl    = document.getElementById('guestFavWord');
                                if (container) {
                                    container.innerHTML = products.map(renderCard).join('');
                                }
                                if (countEl) countEl.textContent = products.length;
                                if (wordEl)  wordEl.textContent  = pluralRu(products.length);
                                if (cnt)     cnt.textContent     = products.length;
                                if (list)    list.style.display  = '';
                            })
                            .catch(function() {
                                if (loading) loading.style.display = 'none';
                                if (empty) empty.style.display = '';
                            });
                    }

                    document.addEventListener('DOMContentLoaded', loadGuestFavorites);
                    window.reloadGuestFavorites = loadGuestFavorites;
                })();
                </script>
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
