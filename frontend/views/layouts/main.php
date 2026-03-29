<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\frontend\assets\AppAsset;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\widgets\Breadcrumbs;
use yii\helpers\Url;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$formatter = Yii::$app->formatter;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100 admin-layout">
<?php $this->beginBody() ?>

<header class="admin-header">
    <div class="admin-header-bar container-fluid">
        <a href="<?= Html::encode(Url::to(['/admin/dashboard/index'])) ?>" class="admin-logo">
            <span class="logo-image">
                <img src="https://sneaker-head.by/images/logo.png" alt="<?= Html::encode(Yii::$app->name) ?>">
            </span>
            <span class="logo-text">
                <strong>СНИКЕРХЭД</strong>
                <small>Оригинальные товары<br>под заказ</small>
            </span>
        </a>

        <div class="admin-actions">
            <button class="admin-icon-button" id="searchToggle" title="Поиск по админке" type="button">
                <i class="bi bi-search"></i>
            </button>

            <?php
            $menuItems = [];

            if (!Yii::$app->user->isGuest) {
                $user = Yii::$app->user->identity;

                // Заказы (dropdown)
                $ordersItems = [
                    ['label' => 'Все заказы', 'url' => ['/admin/order/index']],
                ];

                if ($user->isAdmin() || $user->isManager()) {
                    $ordersItems[] = ['label' => 'Создать заказ', 'url' => ['/admin/order/create']];
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
                        'label' => '<i class="bi bi-box"></i> Товары',
                        'items' => [
                            ['label' => '<i class="bi bi-box-seam"></i> Все товары', 'url' => ['/admin/product/index']],
                            ['label' => '<i class="bi bi-star"></i> Отзывы', 'url' => ['/admin/review/index']],
                            ['label' => '<i class="bi bi-layers"></i> Размерные сетки', 'url' => ['/admin/size-grid/index']],
                            ['label' => '<i class="bi bi-sliders"></i> Управление фильтром', 'url' => ['/admin/characteristic/index']],
                            ['label' => '<i class="bi bi-rocket"></i> Импорт Poizon', 'url' => ['/admin/poizon/index']],
                        ],
                        'options' => ['class' => 'nav-item dropdown'],
                        'encode' => false,
                    ];

                    // Финансы
                    $menuItems[] = [
                        'label' => '<i class="bi bi-currency-dollar"></i> Финансы',
                        'items' => [
                            ['label' => '<i class="bi bi-calculator"></i> Тарифы и комиссии', 'url' => ['/admin/tariff/index']],
                            ['label' => '<i class="bi bi-graph-up"></i> Аналитика', 'url' => ['/admin/analytics/index']],
                            ['label' => '<i class="bi bi-bar-chart"></i> Статистика', 'url' => ['/admin/statistics/index']],
                        ],
                        'options' => ['class' => 'nav-item dropdown'],
                        'encode' => false,
                    ];
                }

                // Покупатели (CRM)
                if ($user->isAdmin() || $user->isManager()) {
                    $menuItems[] = [
                        'label' => '<i class="bi bi-people-fill"></i> Покупатели',
                        'url' => ['/admin/customer/index'],
                        'encode' => false,
                    ];
                }

                // Настройки (выпадающий список для админов)
                if ($user->isAdmin()) {
                    $menuItems[] = [
                        'label' => '<i class="bi bi-gear"></i> Настройки',
                        'items' => [
                            ['label' => '<i class="bi bi-person-circle"></i> Профиль', 'url' => ['/admin/profile']],
                            ['label' => '<i class="bi bi-people"></i> Пользователи', 'url' => ['/admin/user/index']],
                            ['label' => '<i class="bi bi-tools"></i> Dev Tools', 'url' => ['/admin/dev-tools/index']],
                            ['label' => '<i class="bi bi-building"></i> Настройки компании', 'url' => ['/admin/settings']],
                        ],
                        'options' => ['class' => 'nav-item dropdown'],
                        'encode' => false,
                    ];
                } else {
                    // Для не-админов только профиль
                    $menuItems[] = ['label' => '<i class="bi bi-person-circle me-2"></i>Профиль', 'url' => ['/admin/profile']];
                }
                $userInitials = mb_strtoupper(mb_substr($user->username ?? 'User', 0, 2));
            }
            ?>

            <div class="admin-nav-container">
                <?= Nav::widget([
                    'options' => ['class' => 'nav admin-nav flex-nowrap'],
                    'items' => $menuItems,
                    'encodeLabels' => false,
                    'activateParents' => true,
                ]); ?>
            </div>

            <?php if (!Yii::$app->user->isGuest): ?>
                <a href="<?= Html::encode(Url::to(['/admin/profile'])) ?>" class="admin-icon-button admin-user-chip" title="Открыть профиль">
                    <span><?= Html::encode($userInitials) ?></span>
                </a>
                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'admin-logout-form']) .
                    Html::submitButton('<i class="bi bi-box-arrow-right"></i>', [
                        'class' => 'admin-icon-button',
                        'title' => 'Выйти из системы',
                        'aria-label' => 'Выйти из системы'
                    ]) .
                    Html::endForm(); ?>
            <?php endif; ?>

            <button class="admin-theme-toggle" id="themeToggle" title="Переключить тему">
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>
        </div>
    </div>

    <div class="admin-search-panel" id="searchPanel">
        <div class="search-panel-header">
            <div class="search-panel-title">
                <i class="bi bi-search"></i>
                <span>Глобальный поиск</span>
            </div>
            <button class="search-panel-close" id="searchClose" aria-label="Закрыть поиск" type="button">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="admin-search global-search-wrapper">
            <div class="search-input-wrapper">
                <i class="bi bi-search"></i>
                <input type="text"
                       class="form-control search-input"
                       placeholder="Поиск по админке..."
                       id="globalSearch">
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchPanel = document.getElementById('searchPanel');
    const searchToggle = document.getElementById('searchToggle');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('globalSearch');
    let searchTimeout = null;
    let currentRequest = null;

    // Фикс выпадающих меню в шапке (не закрываются при наведении)
    const navDropdownToggles = document.querySelectorAll('.admin-nav .dropdown-toggle');
    navDropdownToggles.forEach(toggle => {
        const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(toggle, {
            autoClose: 'outside'
        });

        const parent = toggle.closest('.dropdown');
        if (!parent) {
            return;
        }

        parent.addEventListener('mouseenter', () => dropdownInstance.show());
        parent.addEventListener('mouseleave', () => dropdownInstance.hide());

        parent.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => dropdownInstance.hide());
        });
    });

    const openSearchPanel = () => {
        if (!searchPanel) return;
        searchPanel.classList.add('open');
        setTimeout(() => searchInput?.focus(), 150);
    };

    const closeSearchPanel = () => {
        if (!searchPanel) return;
        searchPanel.classList.remove('open');
        searchResults?.classList.remove('show');
    };

    searchToggle?.addEventListener('click', () => {
        if (searchPanel?.classList.contains('open')) {
            closeSearchPanel();
        } else {
            openSearchPanel();
        }
    });

    searchClose?.addEventListener('click', closeSearchPanel);

    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Отменяем предыдущий запрос
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (currentRequest) {
            currentRequest.abort();
        }
        
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        // Показываем загрузку
        searchResults.innerHTML = '<div class="search-loading"><i class="bi bi-hourglass-split"></i> Поиск...</div>';
        searchResults.classList.add('show');
        
        // Debounce запрос
        searchTimeout = setTimeout(() => {
            currentRequest = fetch('/admin/search?q=' + encodeURIComponent(query), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data.results || []);
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="search-no-results">Ошибка поиска</div>';
                }
            })
            .finally(() => {
                currentRequest = null;
            });
        }, 300);
    });
    
    // Закрытие поиска при клике вне
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.global-search-wrapper')) {
            searchResults.classList.remove('show');
        }
        
        // Ctrl/Cmd + F для быстрого поиска
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            openSearchPanel();
        }
    });
    
    // Переключение темной темы
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const html = document.documentElement;
    
    // Загружаем сохраненную тему
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Обработчик переключения темы
    themeToggle?.addEventListener('click', function() {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('admin-theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Показываем уведомление
        showNotification(`Тема изменена на ${newTheme === 'dark' ? 'темную' : 'светлую'}`, 'success');
    });
    
    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
    }
    
    // Горячие клавиши
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K для фокуса на поиске
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Ctrl/Cmd + D для переключения темы
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            themeToggle?.click();
        }
        
        // Ctrl/Cmd + N для создания заказа
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/admin/order/create';
        }
        
        // Ctrl/Cmd + P для создания товара
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.location.href = '/admin/product/create';
        }
        
        // Ctrl/Cmd + U для создания пользователя
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            e.preventDefault();
            window.location.href = '/admin/user/create';
        }
        
        // Escape для закрытия результатов
        if (e.key === 'Escape') {
            if (searchResults.classList.contains('show')) {
                searchResults.classList.remove('show');
            } else if (searchPanel?.classList.contains('open')) {
                closeSearchPanel();
                searchInput.blur();
            } else {
                searchInput.blur();
            }
            
            // Закрыть все модальные окна
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
        
        // F1 для помощи
        if (e.key === 'F1') {
            e.preventDefault();
            showHelp();
        }
    });
    
    // Функция показа помощи
    function showHelp() {
        const helpContent = `
            <div style="max-height: 400px; overflow-y: auto;">
                <h5>🎯 Горячие клавиши</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><kbd>Ctrl+K</kbd> - Глобальный поиск</p>
                        <p><kbd>Ctrl+D</kbd> - Переключить тему</p>
                        <p><kbd>Ctrl+N</kbd> - Создать заказ</p>
                        <p><kbd>Ctrl+P</kbd> - Создать товар</p>
                    </div>
                    <div class="col-md-6">
                        <p><kbd>Ctrl+U</kbd> - Создать пользователя</p>
                        <p><kbd>Esc</kbd> - Закрыть модальное окно</p>
                        <p><kbd>F1</kbd> - Показать помощь</p>
                        <p><kbd>Ctrl+/</kbd> - Показать shortcuts</p>
                    </div>
                </div>
                <hr>
                <h6>💡 Советы</h6>
                <ul>
                    <li>Двойной клик на ячейке таблицы для inline редактирования</li>
                    <li>Ctrl+Click для множественного выбора</li>
                    <li>Drag & Drop для сортировки колонок</li>
                    <li>Правый клик для контекстного меню</li>
                </ul>
            </div>
        `;
        
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">⚡ Справка по админке</h5>
                        <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                    </div>
                    <div class="modal-body">
                        ${helpContent}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Закрыть</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    function displaySearchResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">Ничего не найдено</div>';
            return;
        }
        
        let html = '';
        results.forEach(result => {
            html += `
                <a href="${result.url}" class="search-result-item">
                    <div class="search-result-icon ${result.type}">
                        <i class="bi ${result.icon}"></i>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-description">${result.description}</div>
                        <div class="search-result-meta">
                            <span>${result.date}</span>
                            <span class="search-result-status">${result.status}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        
        searchResults.innerHTML = html;
    }
});
</script>


<main role="main" class="flex-shrink-0">
    <div class="container-fluid py-4">
        <!-- Флеш сообщения -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= Yii::$app->session->getFlash('warning') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
