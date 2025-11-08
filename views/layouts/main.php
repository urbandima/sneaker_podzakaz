<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        /* ============================================
           СОВРЕМЕННЫЙ HEADER АДМИНКИ - ПРЕМИУМ ДИЗАЙН
           ============================================ */
        
        /* Основной navbar - УЛЬТРА КОМПАКТНЫЙ ЧЕРНЫЙ (плоское меню) */
        .navbar.admin-header {
            background: #1a1a1a !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: visible;
            min-height: 38px;
        }
        
        /* Логотип/Бренд - УЛЬТРА КОМПАКТНЫЙ */
        .navbar-brand {
            font-weight: 600;
            font-size: 0.95rem;
            color: #fff !important;
            letter-spacing: 0.2px;
            transition: all 0.2s ease;
            padding: 0.25rem 0.5rem;
        }
        
        .navbar-brand:hover {
            color: #4a9eff !important;
        }
        
        .navbar-brand i {
            font-size: 0.9rem;
            margin-right: 0.3rem;
        }
        
        /* Пункты меню - УЛУЧШЕННЫЙ UX */
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem !important;
            margin: 0 0.15rem;
            border-radius: 5px;
            transition: all 0.2s ease;
            position: relative;
            line-height: 1.2;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }
        
        .navbar-nav .nav-link i {
            font-size: 0.85rem;
            margin-right: 0.35rem;
        }
        
        .navbar-nav .nav-link:hover {
            color: #fff !important;
            background-color: rgba(74, 158, 255, 0.2);
            transform: translateY(-1px);
        }
        
        /* Активный пункт меню */
        .navbar-nav .nav-link.active {
            background: rgba(74, 158, 255, 0.15);
            color: #4a9eff !important;
        }
        
        /* Dropdown toggle */
        .navbar-nav .nav-link.dropdown-toggle {
            padding-right: 1rem !important;
        }
        
        .navbar-nav .nav-link.dropdown-toggle::after {
            margin-left: 0.4rem;
            font-size: 0.7rem;
            transition: transform 0.2s ease;
        }
        
        .navbar-nav .nav-link.dropdown-toggle.show {
            background-color: rgba(74, 158, 255, 0.25);
            color: #fff !important;
            transform: translateY(0);
        }
        
        .navbar-nav .nav-link.dropdown-toggle.show::after {
            transform: rotate(180deg);
        }
        
        /* Dropdown menu - базовые стили */
        .navbar-nav .dropdown-menu {
            background: #2a2a2a !important;
            border: 1px solid rgba(74, 158, 255, 0.3) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6) !important;
            padding: 0.4rem 0 !important;
            margin-top: 0.3rem !important;
            min-width: 180px !important;
            position: absolute !important;
            z-index: 99999 !important;
            left: 0 !important;
            top: 100% !important;
            /* По умолчанию скрыто */
            display: none;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-10px);
            transition: all 0.15s ease;
        }
        
        /* Показ dropdown - МАКСИМАЛЬНАЯ СПЕЦИФИЧНОСТЬ */
        .navbar-nav .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transform: translateY(0) !important;
        }
        
        /* Hover альтернатива */
        .nav-item.dropdown:hover .dropdown-menu,
        .navbar-nav .nav-item.dropdown:hover > .dropdown-menu {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transform: translateY(0) !important;
        }
        
        .navbar-nav .nav-item.dropdown:hover > .dropdown-toggle {
            background-color: rgba(74, 158, 255, 0.25) !important;
            color: #fff !important;
        }
        
        .navbar-nav .nav-item.dropdown:hover > .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        /* Убираем gap между toggle и menu для плавного hover */
        .navbar-nav .nav-item.dropdown {
            position: relative;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .navbar-nav .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        
        .navbar-nav .dropdown-item:hover {
            background: rgba(74, 158, 255, 0.2);
            color: #fff;
            border-left-color: #4a9eff;
            padding-left: 1.2rem;
        }
        
        .navbar-nav .dropdown-item:active {
            background: rgba(74, 158, 255, 0.3);
            color: #fff;
        }
        
        /* Кнопка выхода - УЛУЧШЕННЫЙ UX */
        .btn-link.logout {
            color: rgba(255, 255, 255, 0.85) !important;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
            border-radius: 5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-link.logout:hover {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff6b6b !important;
            transform: translateY(-1px);
        }
        
        .btn-link.logout i {
            font-size: 0.85rem;
            margin-right: 0.35rem;
        }
        
        /* Фикс для формы выхода */
        .form-inline {
            margin: 0;
            padding: 0;
        }
        
        /* Мобильная адаптация */
        @media (max-width: 768px) {
            .navbar-nav {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                padding: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .navbar-nav .nav-link {
                margin: 0.2rem 0;
            }
        }
        
        /* Анимация появления navbar */
        .admin-header {
            animation: slideInDown 0.5s ease;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Улучшенная типографика */
        .navbar {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Убран глянцевый эффект для черной темы */
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => '<i class="bi bi-grid-3x3-gap-fill me-2"></i>' . Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar navbar-expand-md navbar-dark admin-header fixed-top'],
    ]);

    $menuItems = [];

    if (!Yii::$app->user->isGuest) {
        $user = Yii::$app->user->identity;
        
        // Заказы (dropdown)
        $ordersItems = [
            ['label' => 'Все заказы', 'url' => ['/admin/orders']],
        ];
        
        if ($user->isAdmin() || $user->isManager()) {
            $ordersItems[] = ['label' => 'Создать заказ', 'url' => ['/admin/create-order']];
        }
        
        $ordersItems[] = ['label' => 'Статистика', 'url' => ['/admin/statistics']];
        
        $menuItems[] = [
            'label' => '<i class="bi bi-receipt me-2"></i>Заказы',
            'items' => $ordersItems,
            'options' => ['class' => 'dropdown'],
        ];
        
        // Товары (dropdown) - только для админов
        if ($user->isAdmin()) {
            $menuItems[] = [
                'label' => '<i class="bi bi-box-seam me-2"></i>Товары',
                'items' => [
                    ['label' => 'Все товары', 'url' => ['/admin/products']],
                    ['label' => 'Дашборд Poizon', 'url' => ['/admin/poizon-import']],
                ],
                'options' => ['class' => 'dropdown'],
            ];
            
            $menuItems[] = ['label' => '<i class="bi bi-people me-2"></i>Пользователи', 'url' => ['/admin/users']];
            $menuItems[] = ['label' => '<i class="bi bi-gear me-2"></i>Настройки', 'url' => ['/admin/settings']];
        }
        
        // Профиль
        $menuItems[] = ['label' => '<i class="bi bi-person-circle me-2"></i>Профиль', 'url' => ['/admin/profile']];

        $menuItems[] = '<li class="nav-item">'
            . Html::beginForm(['/site/logout'], 'post', ['class' => 'form-inline'])
            . Html::submitButton(
                '<i class="bi bi-box-arrow-right me-2"></i>Выход',
                ['class' => 'btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto'],
        'items' => $menuItems,
        'encodeLabels' => false,
        'activateParents' => true,
    ]);

    NavBar::end();
    ?>
    
    <script>
    // Dropdown при наведении (hover)
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggles = document.querySelectorAll('.navbar-nav .nav-link.dropdown-toggle');
        
        dropdownToggles.forEach(function(toggle) {
            toggle.removeAttribute('data-bs-toggle');
            
            const navItem = toggle.closest('li');
            const menu = navItem.querySelector('.dropdown-menu');
            
            if (!menu) return;
            
            let closeTimeout;
            
            function openMenu() {
                clearTimeout(closeTimeout);
                
                // Закрываем все другие dropdown
                document.querySelectorAll('.navbar-nav .dropdown-menu').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('show');
                        otherMenu.style.display = '';
                        otherMenu.style.opacity = '';
                        otherMenu.style.visibility = '';
                        otherMenu.style.pointerEvents = '';
                        otherMenu.style.transform = '';
                    }
                });
                
                // Открываем текущий
                menu.classList.add('show');
                menu.style.display = 'block';
                menu.style.opacity = '1';
                menu.style.visibility = 'visible';
                menu.style.pointerEvents = 'auto';
                menu.style.transform = 'translateY(0)';
                toggle.setAttribute('aria-expanded', 'true');
            }
            
            function closeMenu() {
                menu.classList.remove('show');
                menu.style.display = '';
                menu.style.opacity = '';
                menu.style.visibility = '';
                menu.style.pointerEvents = '';
                menu.style.transform = '';
                toggle.setAttribute('aria-expanded', 'false');
            }
            
            navItem.addEventListener('mouseenter', openMenu);
            navItem.addEventListener('mouseleave', function() {
                closeTimeout = setTimeout(closeMenu, 100);
            });
            
            menu.addEventListener('mouseenter', function() {
                clearTimeout(closeTimeout);
            });
            
            menu.addEventListener('mouseleave', function() {
                closeTimeout = setTimeout(closeMenu, 100);
            });
        });
    });
    </script>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container" style="margin-top: 46px;">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row text-muted">
            <div class="col-md-6 text-center text-md-start">
                &copy; <?= Yii::$app->params['senderName'] ?> <?= date('Y') ?>
            </div>
            <div class="col-md-6 text-center text-md-end">
                Система управления заказами
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
