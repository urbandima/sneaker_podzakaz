<?php

use yii\helpers\Html;
use yii\helpers\Url;

$company = Yii::$app->settings->getCompany() ?? ['name' => 'СНИКЕРХЭД'];
$controllerId = Yii::$app->controller->id;
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> — Админ</title>
    
    <!-- Admin CSS -->
    <link href="/css/admin.css?v=<?= filemtime(Yii::getAlias('@webroot') . '/css/admin.css') ?>" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Restore theme before paint -->
    <script>
        (function(){var t=localStorage.getItem('admin-theme');if(t)document.documentElement.setAttribute('data-theme',t)})();
    </script>
    
    <?php if (class_exists('yii\debug\Module')): ?>
    <style>.yii-debug-toolbar{display:none!important}</style>
    <?php endif ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-sidebar-logo">
                <i class="bi bi-shop"></i>
                <span><?= Html::encode($company['name']) ?></span>
            </a>
        </div>
        
        <nav class="admin-sidebar-nav">
            <?php
            $navItems = [
                ['label' => 'Главная', 'url' => '/admin', 'icon' => 'bi-grid-1x2-fill', 'ids' => ['dashboard']],
                ['label' => 'Заказы', 'url' => '/admin/order', 'icon' => 'bi-bag-check-fill', 'ids' => ['order']],
                ['label' => 'Каталог', 'url' => '/admin/catalog', 'icon' => 'bi-collection-fill', 'ids' => ['catalog', 'product']],
                ['label' => 'Клиенты', 'url' => '/admin/customer', 'icon' => 'bi-people-fill', 'ids' => ['customer']],
                ['label' => 'Купоны', 'url' => '/admin/coupon', 'icon' => 'bi-ticket-detailed-fill', 'ids' => ['coupon']],
                ['label' => 'Возвраты', 'url' => '/admin/return', 'icon' => 'bi-arrow-return-left', 'ids' => ['return']],
                ['label' => 'Аналитика', 'url' => '/admin/statistics', 'icon' => 'bi-bar-chart-line-fill', 'ids' => ['statistics']],
                ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'icon' => 'bi-megaphone-fill', 'ids' => ['marketing']],
                ['label' => 'POS-Терминал', 'url' => '/admin/pos', 'icon' => 'bi-shop', 'ids' => ['pos']],
                ['label' => 'Плагины', 'url' => '/admin/plugin', 'icon' => 'bi-plugin', 'ids' => ['plugin']],
                ['label' => 'Настройки', 'url' => '/admin/settings', 'icon' => 'bi-gear-wide-connected', 'ids' => ['settings']],
                ['label' => 'SEO', 'url' => '/admin/seo', 'icon' => 'bi-search-heart', 'ids' => ['seo']],
            ];
            foreach ($navItems as $item): ?>
            <a href="<?= Url::to([$item['url']]) ?>" class="admin-nav-item <?= in_array($controllerId, $item['ids']) ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach ?>

            <div class="admin-nav-divider"></div>
            <a href="<?= Url::to(['/']) ?>" class="admin-nav-item admin-nav-muted" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>На сайт</span>
            </a>
            <a href="<?= Url::to(['/admin/logout']) ?>" class="admin-nav-item admin-nav-danger">
                <i class="bi bi-power"></i>
                <span>Выйти</span>
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <!-- Live Search Overlay -->
        <div class="admin-search-overlay" id="search-overlay" style="display:none">
            <div class="admin-search-modal">
                <div class="admin-search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="admin-search-input" placeholder="Поиск по админке... (Esc — закрыть)" autocomplete="off">
                    <kbd>Ctrl+K</kbd>
                </div>
                <div class="admin-search-results" id="search-results"></div>
            </div>
        </div>

        <?= $content ?>
    </main>
</div>

<!-- Global Admin JS -->
<script>
(function() {
    // === Live Search (Ctrl+K) ===
    const overlay = document.getElementById('search-overlay');
    const input = document.getElementById('admin-search-input');
    const results = document.getElementById('search-results');

    const searchItems = [
        { label: 'Главная', url: '<?= Url::to(['/admin']) ?>', icon: 'bi-grid-1x2-fill', tags: 'dashboard панель' },
        { label: 'Заказы', url: '<?= Url::to(['/admin/order']) ?>', icon: 'bi-bag-check-fill', tags: 'orders заказ' },
        { label: 'Новый заказ', url: '<?= Url::to(['/admin/order/create']) ?>', icon: 'bi-plus-circle', tags: 'create new' },
        { label: 'Каталог', url: '<?= Url::to(['/admin/catalog']) ?>', icon: 'bi-collection-fill', tags: 'catalog товары products' },
        { label: 'Клиенты', url: '<?= Url::to(['/admin/customer']) ?>', icon: 'bi-people-fill', tags: 'customers покупатели' },
        { label: 'Купоны', url: '<?= Url::to(['/admin/coupon']) ?>', icon: 'bi-ticket-detailed-fill', tags: 'coupons скидки промокод' },
        { label: 'Возвраты', url: '<?= Url::to(['/admin/return']) ?>', icon: 'bi-arrow-return-left', tags: 'returns' },
        { label: 'Аналитика', url: '<?= Url::to(['/admin/statistics']) ?>', icon: 'bi-bar-chart-line-fill', tags: 'statistics отчёты' },
        { label: 'Маркетинг', url: '<?= Url::to(['/admin/marketing']) ?>', icon: 'bi-megaphone-fill', tags: 'marketing upsell корзины рекомендации' },
        { label: 'POS-Терминал', url: '<?= Url::to(['/admin/pos']) ?>', icon: 'bi-shop', tags: 'pos касса терминал продажа' },
        { label: 'Плагины', url: '<?= Url::to(['/admin/plugin']) ?>', icon: 'bi-plugin', tags: 'plugins расширения интеграции' },
        { label: 'SEO', url: '<?= Url::to(['/admin/seo']) ?>', icon: 'bi-search-heart', tags: 'seo robots sitemap' },
        { label: 'Настройки', url: '<?= Url::to(['/admin/settings']) ?>', icon: 'bi-gear-wide-connected', tags: 'settings конфигурация' },
    ];

    function openSearch() { overlay.style.display = 'flex'; input.value = ''; renderResults(''); setTimeout(() => input.focus(), 50); }
    function closeSearch() { overlay.style.display = 'none'; }

    function renderResults(q) {
        const lq = q.toLowerCase();
        const filtered = q ? searchItems.filter(i => (i.label + ' ' + i.tags).toLowerCase().includes(lq)) : searchItems;
        results.innerHTML = filtered.map((item, idx) =>
            '<a href="' + item.url + '" class="admin-search-item' + (idx === 0 ? ' active' : '') + '">' +
            '<i class="bi ' + item.icon + '"></i><span>' + item.label + '</span></a>'
        ).join('') || '<div class="admin-search-empty">Ничего не найдено</div>';
    }

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'k') { e.preventDefault(); openSearch(); }
        if (e.key === 'Escape') closeSearch();
        if (overlay.style.display === 'flex' && e.key === 'Enter') {
            const first = results.querySelector('.admin-search-item.active, .admin-search-item');
            if (first) { window.location.href = first.href; }
        }
        if (overlay.style.display === 'flex' && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            e.preventDefault();
            const items = results.querySelectorAll('.admin-search-item');
            if (!items.length) return;
            let cur = results.querySelector('.admin-search-item.active');
            if (cur) cur.classList.remove('active');
            let idx = Array.from(items).indexOf(cur);
            idx = e.key === 'ArrowDown' ? Math.min(idx + 1, items.length - 1) : Math.max(idx - 1, 0);
            items[idx].classList.add('active');
            items[idx].scrollIntoView({ block: 'nearest' });
        }
    });

    if (input) input.addEventListener('input', function() { renderResults(this.value); });
    if (overlay) overlay.addEventListener('click', function(e) { if (e.target === overlay) closeSearch(); });

    // === Mobile Sidebar Toggle ===
    const sidebar = document.getElementById('admin-sidebar');
    document.addEventListener('click', function(e) {
        if (e.target.closest('.admin-mobile-toggle')) {
            sidebar.classList.toggle('open');
        }
    });
})();
</script>

<style>
/* Search overlay */
.admin-search-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: flex-start; justify-content: center; padding-top: 15vh; backdrop-filter: blur(4px); }
.admin-search-modal { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius-lg); width: 100%; max-width: 520px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25); }
.admin-search-input-wrap { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--admin-border); }
.admin-search-input-wrap i { color: var(--admin-text-secondary); font-size: 1.1rem; }
.admin-search-input-wrap input { flex: 1; border: none; outline: none; background: transparent; font-size: 1rem; color: var(--admin-text); }
.admin-search-input-wrap kbd { background: var(--admin-border-light); border: 1px solid var(--admin-border); border-radius: 4px; padding: 0.1rem 0.4rem; font-size: 0.65rem; color: var(--admin-text-secondary); white-space: nowrap; }
.admin-search-results { max-height: 320px; overflow-y: auto; padding: 0.5rem; }
.admin-search-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.75rem; border-radius: var(--admin-radius); text-decoration: none; color: var(--admin-text); transition: background 0.1s; }
.admin-search-item:hover, .admin-search-item.active { background: var(--admin-accent); color: white; }
.admin-search-item i { font-size: 1rem; width: 1.25rem; text-align: center; }
.admin-search-empty { text-align: center; padding: 2rem; color: var(--admin-text-secondary); font-size: 0.875rem; }

/* Nav divider & muted/danger nav items */
.admin-nav-divider { margin: 1rem 0; border-top: 1px solid var(--admin-primary-soft); }
.admin-nav-muted { opacity: 0.6; }
.admin-nav-muted:hover { opacity: 1; }
.admin-nav-danger { color: #f87171 !important; }
.admin-nav-danger:hover { background: rgba(248,113,113,0.15) !important; }
</style>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
