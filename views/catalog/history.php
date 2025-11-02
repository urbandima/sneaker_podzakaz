<?php
/** @var yii\web\View $this */
/** @var app\models\Product[] $products */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Недавно смотрели';
$this->registerJsFile('@web/js/view-history.js', ['position' => \yii\web\View::POS_END]);
?>

<div class="history-page">
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1>
                <i class="bi bi-clock-history"></i>
                Недавно смотрели
            </h1>
            <p class="subtitle">История просмотренных товаров</p>
        </div>

        <!-- История -->
        <div class="view-history-section">
            <div class="history-actions">
                <div class="history-stats">
                    <span id="historyCount">0</span> товаров в истории
                </div>
                <button class="btn-clear-history" onclick="clearHistoryPage()">
                    <i class="bi bi-trash"></i>
                    Очистить историю
                </button>
            </div>
            
            <div id="viewHistoryContainer">
                <div class="loading-history">
                    <i class="bi bi-hourglass-split"></i>
                    Загрузка...
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div id="emptyState" style="display:none" class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <h2>История просмотров пуста</h2>
            <p>Вы ещё не смотрели товары</p>
            <a href="/catalog" class="btn-go-catalog">
                <i class="bi bi-grid-3x3-gap"></i>
                Перейти в каталог
            </a>
        </div>
    </div>
</div>

<style>
.history-page{min-height:80vh;padding:2rem 0;background:#fff}
.container{max-width:1400px;margin:0 auto;padding:0 1rem}

.page-header{text-align:center;padding:2rem 0;border-bottom:2px solid #f3f4f6;margin-bottom:2rem}
.page-header h1{font-size:2.5rem;font-weight:900;display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:0.5rem}
.page-header h1 i{font-size:3rem;color:#3b82f6}
.page-header .subtitle{font-size:1.125rem;color:#666}

.history-actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;padding:1rem;background:#f9fafb;border-radius:12px}
.history-stats{font-size:1.125rem;font-weight:600;color:#000}
.history-stats #historyCount{color:#3b82f6;font-size:1.5rem}

.btn-clear-history{background:#fff;border:2px solid #e5e7eb;padding:0.75rem 1.5rem;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:0.625rem;color:#ef4444;transition:all 0.2s}
.btn-clear-history:hover{background:#fef2f2;border-color:#ef4444;transform:translateY(-2px)}
.btn-clear-history i{font-size:1.125rem}

.empty-state{text-align:center;padding:4rem 2rem}
.empty-icon{font-size:8rem;color:#e5e7eb;margin-bottom:1.5rem}
.empty-state h2{font-size:2rem;font-weight:700;margin-bottom:0.75rem}
.empty-state p{font-size:1.125rem;color:#666;margin-bottom:2rem}
.btn-go-catalog{display:inline-flex;align-items:center;gap:0.625rem;background:#000;color:#fff;padding:1rem 2rem;border-radius:12px;text-decoration:none;font-weight:700;transition:all 0.2s}
.btn-go-catalog:hover{background:#333;transform:translateY(-2px)}

.loading-history{text-align:center;padding:3rem;font-size:1.25rem;color:#666}
.loading-history i{font-size:2rem;display:block;margin-bottom:1rem}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показываем историю
    if (typeof viewHistory !== 'undefined') {
        viewHistory.show('viewHistoryContainer');
        
        // Обновляем счётчик
        const history = viewHistory.get();
        const countEl = document.getElementById('historyCount');
        const emptyState = document.getElementById('emptyState');
        const historySection = document.querySelector('.view-history-section');
        
        if (history.length === 0) {
            emptyState.style.display = 'block';
            historySection.style.display = 'none';
        } else {
            countEl.textContent = history.length;
        }
    }
});

function clearHistoryPage() {
    if (!confirm('Вы уверены, что хотите очистить историю просмотров?')) {
        return;
    }
    
    if (typeof viewHistory !== 'undefined') {
        viewHistory.clear();
        
        // Показываем пустое состояние
        document.getElementById('emptyState').style.display = 'block';
        document.querySelector('.view-history-section').style.display = 'none';
    }
}
</script>
