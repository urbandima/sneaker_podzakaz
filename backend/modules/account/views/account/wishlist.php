<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\ProductFavorite[] $favorites */
/** @var app\backend\modules\account\models\Customer $customer */

use yii\helpers\Html;
use app\frontend\assets\CatalogAsset;

$this->title = 'Избранное';
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['account/index']];
$this->params['breadcrumbs'][] = $this->title;

CatalogAsset::register($this);
$this->registerJsFile('@web/js/favorites.js', ['depends' => ['\app\frontend\assets\CatalogAsset']]);

$count     = count($favorites);
$csrfToken = Yii::$app->request->csrfToken;
?>

<style>
.wishlist-page{max-width:1200px;margin:0 auto;padding:0 16px 48px}
.wishlist-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.wishlist-title{font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:10px}
.wishlist-count{font-size:.8125rem;font-weight:700;background:var(--color-primary,#111);color:#fff;border-radius:20px;padding:2px 10px}
.wishlist-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.wishlist-sort{height:34px;border:1.5px solid var(--c-gray-200,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;background:#fff;cursor:pointer}
.wishlist-clear-btn{height:34px;padding:0 14px;border-radius:8px;border:1.5px solid #fca5a5;color:#dc2626;background:#fff;font-size:.8125rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s}
.wishlist-clear-btn:hover{background:#fef2f2;border-color:#dc2626}
.wishlist-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.wishlist-card{background:#fff;border-radius:12px;overflow:hidden;border:1.5px solid var(--c-gray-100,#f3f4f6);transition:box-shadow .18s,transform .18s,opacity .35s;position:relative}
.wishlist-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-2px)}
.wishlist-card.removing{opacity:0;transform:scale(.94) translateY(8px);pointer-events:none}
.wishlist-card-img{position:relative;aspect-ratio:1;overflow:hidden;background:var(--c-gray-50,#f9fafb)}
.wishlist-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.wishlist-card:hover .wishlist-card-img img{transform:scale(1.04)}
.wishlist-remove-btn{position:absolute;top:8px;right:8px;width:30px;height:30px;border-radius:50%;border:none;background:rgba(255,255,255,.92);color:#dc2626;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .15s;z-index:2}
.wishlist-remove-btn:hover{background:#dc2626;color:#fff;transform:scale(1.1)}
.wishlist-card-body{padding:12px}
.wishlist-card-brand{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--c-gray-400,#9ca3af);margin-bottom:4px}
.wishlist-card-name{font-size:.875rem;font-weight:600;line-height:1.35;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.wishlist-card-name a{color:inherit;text-decoration:none}
.wishlist-card-name a:hover{color:var(--color-primary,#111);text-decoration:underline}
.wishlist-card-footer{display:flex;align-items:center;justify-content:space-between;gap:8px}
.wishlist-card-price{font-size:1rem;font-weight:800}
.wishlist-card-price-old{font-size:.75rem;color:var(--c-gray-400,#9ca3af);text-decoration:line-through}
.wishlist-cart-btn{flex-shrink:0;width:34px;height:34px;border-radius:8px;border:none;background:var(--color-primary,#111);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:all .15s}
.wishlist-cart-btn:hover{background:#333;transform:scale(1.05)}
.wishlist-empty{text-align:center;padding:64px 24px}
.wishlist-empty i{font-size:3.5rem;color:var(--c-gray-200,#e5e7eb);display:block;margin-bottom:16px}
.wishlist-empty h3{font-size:1.25rem;font-weight:700;margin-bottom:8px}
.wishlist-empty p{color:var(--c-gray-400,#9ca3af);margin-bottom:24px}
</style>

<div class="wishlist-page">
    <div class="row">
        <div class="col-md-3">
            <div class="account-sidebar">
                <div class="list-group">
                    <?= Html::a('Профиль', ['account/profile'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Мои заказы', ['account/orders'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Избранное', ['account/wishlist'], ['class' => 'list-group-item active']) ?>
                    <?= Html::a('Настройки', ['account/settings'], ['class' => 'list-group-item']) ?>
                    <?= Html::a('Выход', ['account/logout'], ['class' => 'list-group-item text-danger']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="wishlist-header">
                <div class="wishlist-title">
                    <i class="bi bi-heart-fill" style="color:#dc2626"></i>
                    Избранное
                    <span class="wishlist-count" id="wishlistCount"><?= $count ?></span>
                </div>
                <div class="wishlist-actions" id="wishlistActions" style="<?= $count === 0 ? 'display:none' : '' ?>">
                    <select class="wishlist-sort" id="wishlistSort" onchange="sortWishlist(this.value)" aria-label="Сортировка">
                        <option value="date_desc">Недавно добавленные</option>
                        <option value="date_asc">Сначала давние</option>
                        <option value="price_asc">Цена: ↑</option>
                        <option value="price_desc">Цена: ↓</option>
                    </select>
                    <button class="wishlist-clear-btn" onclick="clearWishlist()">
                        <i class="bi bi-trash3"></i> Очистить всё
                    </button>
                </div>
            </div>

            <?php if (empty($favorites)): ?>
            <div class="wishlist-empty" id="wishlistEmpty">
                <i class="bi bi-heart"></i>
                <h3>В избранном пусто</h3>
                <p>Добавляйте товары, нажимая ♡ на карточке товара</p>
                <a href="/catalog" class="btn btn-primary">
                    <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                </a>
            </div>
            <?php else: ?>
            <div class="wishlist-grid" id="wishlistGrid">
                <?php foreach ($favorites as $fav):
                    $p = $fav->product;
                    if (!$p) continue;
                    $imgUrl   = $p->getMainImageUrl() ?: '/images/placeholder.png';
                    $price    = number_format((float)$p->price, 2, '.', ' ');
                    $oldPrice = $p->old_price ? number_format((float)$p->old_price, 2, '.', ' ') : null;
                    $brand    = $p->brand ? Html::encode($p->brand->name) : '';
                ?>
                <div class="wishlist-card"
                     data-product-id="<?= $p->id ?>"
                     data-price="<?= (float)$p->price ?>"
                     data-date="<?= $fav->created_at ?>">
                    <div class="wishlist-card-img">
                        <a href="<?= Html::encode($p->getUrl()) ?>" tabindex="-1" aria-hidden="true">
                            <img src="<?= Html::encode($imgUrl) ?>"
                                 alt="<?= Html::encode($p->name) ?>"
                                 loading="lazy"
                                 onerror="this.src='/images/placeholder.png'">
                        </a>
                        <button class="wishlist-remove-btn"
                                onclick="removeFromWishlist(event, <?= $p->id ?>)"
                                title="Удалить из избранного"
                                aria-label="Удалить <?= Html::encode($p->name) ?> из избранного">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="wishlist-card-body">
                        <?php if ($brand): ?>
                        <div class="wishlist-card-brand"><?= $brand ?></div>
                        <?php endif; ?>
                        <div class="wishlist-card-name">
                            <a href="<?= Html::encode($p->getUrl()) ?>"><?= Html::encode($p->name) ?></a>
                        </div>
                        <div class="wishlist-card-footer">
                            <div>
                                <div class="wishlist-card-price"><?= $price ?> BYN</div>
                                <?php if ($oldPrice): ?>
                                <div class="wishlist-card-price-old"><?= $oldPrice ?> BYN</div>
                                <?php endif; ?>
                            </div>
                            <button class="wishlist-cart-btn"
                                    onclick="wishlistAddToCart(event, <?= $p->id ?>)"
                                    title="Добавить в корзину"
                                    aria-label="Добавить <?= Html::encode($p->name) ?> в корзину">
                                <i class="bi bi-bag-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var CSRF = '<?= Html::encode($csrfToken) ?>';

    window.removeFromWishlist = function (e, productId) {
        e.preventDefault();
        var card = document.querySelector('.wishlist-card[data-product-id="' + productId + '"]');
        if (!card) return;
        card.classList.add('removing');
        setTimeout(function () {
            SH.fetch('/favorite/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId + '&_csrf=' + encodeURIComponent(CSRF),
            }).then(function () {
                card.remove();
                _updateCount();
            }).catch(function () { card.classList.remove('removing'); });
        }, 350);
    };

    window.clearWishlist = function () {
        if (!confirm('Очистить всё избранное?')) return;
        SH.fetch('/favorite/clear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent(CSRF),
        }).then(function () {
            document.querySelectorAll('.wishlist-card').forEach(function (c) { c.classList.add('removing'); });
            setTimeout(_showEmpty, 400);
        });
    };

    window.wishlistAddToCart = function (e, productId) {
        e.preventDefault();
        var btn = e.currentTarget;
        btn.disabled = true;
        SH.fetch('/cart/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId + '&qty=1&_csrf=' + encodeURIComponent(CSRF),
        }).then(function (data) {
            btn.disabled = false;
            if (data && data.success) {
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                SH.notify('Товар добавлен в корзину', 'success');
                setTimeout(function () { btn.innerHTML = '<i class="bi bi-bag-plus"></i>'; }, 2000);
            } else {
                SH.notify((data && data.message) || 'Выберите размер на странице товара', 'info');
            }
        }).catch(function () { btn.disabled = false; });
    };

    window.sortWishlist = function (val) {
        var grid = document.getElementById('wishlistGrid');
        if (!grid) return;
        var cards = Array.from(grid.querySelectorAll('.wishlist-card'));
        cards.sort(function (a, b) {
            if (val === 'price_asc')  return +a.dataset.price - +b.dataset.price;
            if (val === 'price_desc') return +b.dataset.price - +a.dataset.price;
            if (val === 'date_asc')   return +a.dataset.date  - +b.dataset.date;
            return +b.dataset.date - +a.dataset.date;
        });
        cards.forEach(function (c) { grid.appendChild(c); });
    };

    function _updateCount() {
        var n = document.querySelectorAll('.wishlist-card').length;
        var badge = document.getElementById('wishlistCount');
        if (badge) badge.textContent = n;
        var favBadge = document.getElementById('favCount');
        if (favBadge) { favBadge.textContent = n; favBadge.style.display = n > 0 ? 'flex' : 'none'; }
        if (n === 0) _showEmpty();
    }

    function _showEmpty() {
        var grid = document.getElementById('wishlistGrid');
        var actions = document.getElementById('wishlistActions');
        if (grid) grid.remove();
        if (actions) actions.style.display = 'none';
        var badge = document.getElementById('wishlistCount');
        if (badge) badge.textContent = '0';
        var favBadge = document.getElementById('favCount');
        if (favBadge) { favBadge.textContent = '0'; favBadge.style.display = 'none'; }
        var content = document.querySelector('.col-md-9');
        if (content && !document.getElementById('wishlistEmpty')) {
            content.insertAdjacentHTML('beforeend',
                '<div class="wishlist-empty" id="wishlistEmpty">' +
                '<i class="bi bi-heart"></i>' +
                '<h3>В избранном пусто</h3>' +
                '<p>Добавляйте товары, нажимая ♡ на карточке</p>' +
                '<a href="/catalog" class="btn btn-primary">Перейти в каталог</a>' +
                '</div>');
        }
    }
}());
</script>
