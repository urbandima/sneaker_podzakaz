<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product[] $products */

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\shared\components\AssetOptimizer;
use app\frontend\assets\CatalogAsset;

$this->title = 'История просмотров - СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Недавно просмотренные товары']);

// Подключаем AssetBundle для каталога (все стили автоматически с версионированием)
CatalogAsset::register($this);

// JS файлы для функционала
$this->registerJsFile('@web/js/view-history.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/favorites.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/lazy-load.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/catalog.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/global-helpers.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs (как в каталоге) -->
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a> / 
            <span>История просмотров</span>
        </nav>

        <!-- Content (без sidebar - на всю ширину) -->
        <div class="w-100">
            <main class="content" style="max-width: 100%;">
                <div class="content-header">
                    <h1><i class="bi bi-clock-history"></i> История просмотров <span class="products-count d-none" id="historyCountHeader">(<span id="productsCount">0</span>)</span></h1>
                </div>
                
                <!-- Пустое состояние (показывается если истории нет) -->
                <div id="emptyState" class="d-none">
                    <div class="empty-state">
                        <div class="empty-state__icon"><i class="bi bi-clock-history"></i></div>
                        <h3 class="empty-state__title">История просмотров пуста</h3>
                        <p class="empty-state__text">Вы ещё не смотрели товары</p>
                        <a href="/catalog" class="btn btn-primary">
                            <i class="bi bi-grid-3x3-gap"></i> Перейти в каталог
                        </a>
                    </div>
                </div>

                <!-- История (показывается если есть товары) -->
                <div id="historySection" class="d-none">
                    <!-- Toolbar (с кнопкой очистки) -->
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <span class="toolbar-meta">
                                <i class="bi bi-clock-history"></i>
                                <span id="historyCount">0</span> <span id="historyLabel">товаров</span> в истории
                            </span>
                        </div>
                        
                        <div class="toolbar-right">
                            <button onclick="clearHistoryPage()" class="btn-clear-history">
                                <i class="bi bi-trash"></i>
                                <span>Очистить историю</span>
                            </button>
                        </div>
                    </div>

                    <!-- Сетка товаров (точно как в каталоге) -->
                    <div class="products grid-5" id="products">
                        <div class="loading-placeholder">
                            <i class="bi bi-hourglass-split"></i>
                            Загрузка...
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<style>
/* Hover эффект для кнопки очистки */
.toolbar-right button:hover {
    background: #fef2f2 !important;
    border-color: #ef4444 !important;
    transform: translateY(-2px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показываем историю через viewHistory API
    if (typeof viewHistory !== 'undefined') {
        const history = viewHistory.get();
        const emptyState = document.getElementById('emptyState');
        const historySection = document.getElementById('historySection');
        const historyCountHeader = document.getElementById('historyCountHeader');
        const productsCount = document.getElementById('productsCount');
        const historyCount = document.getElementById('historyCount');
        const historyLabel = document.getElementById('historyLabel');
        const productsContainer = document.getElementById('products');
        
        if (history.length === 0) {
            // Показываем пустое состояние
            emptyState.style.display = 'block';
            historySection.style.display = 'none';
        } else {
            // Показываем товары
            emptyState.style.display = 'none';
            historySection.style.display = 'block';
            historyCountHeader.style.display = 'inline';
            
            // Обновляем счетчики
            const count = history.length;
            productsCount.textContent = count;
            historyCount.textContent = count;
            
            // Правильное склонение
            if (count === 1) {
                historyLabel.textContent = 'товар';
            } else if (count >= 2 && count <= 4) {
                historyLabel.textContent = 'товара';
            } else {
                historyLabel.textContent = 'товаров';
            }
            
            // Рендерим товары через viewHistory API
            viewHistory.show('products');
            
            // КРИТИЧНО: Реинициализация lazy loading для новых элементов
            setTimeout(() => {
                if (window.LazyLoadUtils) {
                    LazyLoadUtils.observe(productsContainer);
                }
            }, 100);
        }
    } else {
        console.error('viewHistory API не найден');
        document.getElementById('emptyState').style.display = 'block';
    }
});

function clearHistoryPage() {
    if (!confirm('Вы уверены, что хотите очистить историю просмотров?')) {
        return;
    }
    
    if (typeof viewHistory !== 'undefined') {
        viewHistory.clear();
        
        // Перезагружаем страницу для обновления интерфейса
        window.location.reload();
    }
}
</script>
