<?php
/**
 * Social Proof Component
 * Отображает социальные доказательства на сайте
 */

namespace app\frontend\widgets;

use yii\base\Widget;

class SocialProofWidget extends Widget
{
    public $type = 'product'; // product, cart, global
    public $productId = null;
    
    public function run()
    {
        if ($this->type === 'product' && $this->productId) {
            return $this->renderProductProof();
        }
        
        return $this->renderGlobalProof();
    }
    
    protected function renderProductProof()
    {
        // Симуляция данных (в продакшене - из БД/Redis)
        $views = rand(15, 150);
        $recentPurchases = rand(0, 8);
        $inCart = rand(3, 25);
        
        return <<<HTML
<div class="social-proof-product" data-product-id="{$this->productId}">
    <div class="proof-item proof-views">
        <i class="bi bi-eye"></i>
        <span class="proof-count">{$views}</span> смотрят сейчас
    </div>
    <div class="proof-item proof-purchases">
        <i class="bi bi-bag-check"></i>
        Купили {$recentPurchases} чел. сегодня
    </div>
    <div class="proof-item proof-cart">
        <i class="bi bi-cart"></i>
        {$inCart} в корзине
    </div>
</div>
HTML;
    }
    
    protected function renderGlobalProof()
    {
        return <<<'HTML'
<div class="social-proof-global" id="socialProofGlobal">
    <!-- Toast уведомления будут появляться здесь -->
</div>

<script>
// Социальные доказательства в реальном времени
(function() {
    const cities = ['Минск', 'Гродно', 'Брест', 'Витебск', 'Гомель', 'Могилев'];
    const products = ['Nike Air Force', 'Adidas Yeezy', 'Jordan 1', 'New Balance 550'];
    const actions = ['только что купил', 'добавил в избранное', 'добавил в корзину'];
    
    function showToast(city, product, action) {
        const container = document.getElementById('socialProofGlobal');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'social-toast';
        toast.innerHTML = `
            <div class="toast-icon">👟</div>
            <div class="toast-content">
                <strong>${city}:</strong> ${action}<br>
                <small>${product}</small>
            </div>
            <div class="toast-time">только что</div>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    // Показывать toast каждые 15-45 секунд
    function scheduleToast() {
        const delay = Math.random() * 30000 + 15000;
        setTimeout(() => {
            const city = cities[Math.floor(Math.random() * cities.length)];
            const product = products[Math.floor(Math.random() * products.length)];
            const action = actions[Math.floor(Math.random() * actions.length)];
            showToast(city, product, action);
            scheduleToast();
        }, delay);
    }
    
    // Старт через 5 секунд после загрузки
    setTimeout(scheduleToast, 5000);
})();
</script>
HTML;
    }
}
