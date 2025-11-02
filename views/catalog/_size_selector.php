<?php
/** 
 * Компонент выбора размера с множественной сеткой (EU, CM, UK, US)
 * @var $product app\models\Product
 */
?>

<div class="size-selector-advanced">
    <!-- Size Grid Tabs -->
    <div class="size-tabs">
        <button class="size-tab active" data-system="eu">EU</button>
        <button class="size-tab" data-system="cm">CM</button>
        <button class="size-tab" data-system="uk">UK</button>
        <button class="size-tab" data-system="us">US</button>
    </div>
    
    <!-- Size Grid -->
    <div class="size-grid" id="sizeGrid">
        <!-- EU sizes (default) -->
        <div class="size-system active" data-system="eu">
            <?php 
            $euSizes = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'];
            foreach ($euSizes as $size): ?>
                <button class="size-option" data-size-eu="<?= $size ?>" data-size-cm="<?= $size + 15 ?>" data-size-uk="<?= $size - 33 ?>" data-size-us="<?= $size - 32 ?>">
                    <?= $size ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- CM sizes -->
        <div class="size-system" data-system="cm">
            <?php 
            $cmSizes = ['22', '23', '24', '25', '26', '27', '28', '29', '30', '31'];
            foreach ($cmSizes as $size): ?>
                <button class="size-option" data-size-cm="<?= $size ?>">
                    <?= $size ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- UK sizes -->
        <div class="size-system" data-system="uk">
            <?php 
            $ukSizes = ['3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($ukSizes as $size): ?>
                <button class="size-option" data-size-uk="<?= $size ?>">
                    <?= $size ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- US sizes -->
        <div class="size-system" data-system="us">
            <?php 
            $usSizes = ['4', '5', '6', '7', '8', '9', '10', '11', '12', '13'];
            foreach ($usSizes as $size): ?>
                <button class="size-option" data-size-us="<?= $size ?>">
                    <?= $size ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Size Guide Link -->
    <button class="size-guide-btn" onclick="openSizeGuide()">
        <i class="bi bi-rulers"></i> Таблица размеров
    </button>
</div>

<!-- Sticky Bottom Toolbar (Mobile) -->
<div class="product-sticky-toolbar" id="stickyToolbar">
    <div class="toolbar-content">
        <div class="toolbar-price">
            <div class="price-label">Цена:</div>
            <div class="price-value">1 299 BYN</div>
        </div>
        
        <div class="toolbar-size">
            <button class="select-size-btn" onclick="scrollToSizeSelector()">
                <i class="bi bi-rulers"></i>
                <span id="selectedSizeText">Выбрать размер</span>
            </button>
        </div>
        
        <button class="toolbar-cart-btn" onclick="addToCartFromToolbar()">
            <i class="bi bi-cart-plus"></i>
            <span>В корзину</span>
        </button>
    </div>
</div>

<style>
/* ============================================
   SIZE SELECTOR ADVANCED
   ============================================ */

.size-selector-advanced {
    background: #f9fafb;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.size-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    background: #fff;
    padding: 0.375rem;
    border-radius: 8px;
}

.size-tab {
    flex: 1;
    padding: 0.75rem;
    border: none;
    background: transparent;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.size-tab:hover {
    background: #f3f4f6;
    color: #000;
}

.size-tab.active {
    background: #000;
    color: #fff;
}

.size-system {
    display: none;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 0.75rem;
}

.size-system.active {
    display: grid;
}

.size-option {
    padding: 1rem;
    border: 2px solid #e5e7eb;
    background: #fff;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.size-option:hover {
    border-color: #000;
    transform: translateY(-2px);
}

.size-option.selected {
    border-color: #000;
    background: #000;
    color: #fff;
}

.size-option.selected::after {
    content: '✓';
    position: absolute;
    top: 4px;
    right: 6px;
    font-size: 0.75rem;
}

.size-option:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    text-decoration: line-through;
}

.size-guide-btn {
    margin-top: 1rem;
    width: 100%;
    padding: 0.875rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.size-guide-btn:hover {
    border-color: #000;
    background: #f9fafb;
}

/* ============================================
   STICKY BOTTOM TOOLBAR
   ============================================ */

.product-sticky-toolbar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    z-index: 100;
    transform: translateY(100%);
    transition: transform 0.3s;
    box-shadow: 0 -4px 16px rgba(0,0,0,0.1);
}

.product-sticky-toolbar.visible {
    transform: translateY(0);
}

.toolbar-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    padding: 1rem;
    max-width: 1400px;
    margin: 0 auto;
    align-items: center;
}

.toolbar-price {
    display: flex;
    flex-direction: column;
}

.price-label {
    font-size: 0.75rem;
    color: #666;
}

.price-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #000;
}

.toolbar-size {
    display: flex;
    justify-content: center;
}

.select-size-btn {
    padding: 0.875rem 1.5rem;
    border: 2px solid #e5e7eb;
    background: #fff;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.select-size-btn:hover {
    border-color: #000;
}

.select-size-btn.selected {
    border-color: #10b981;
    background: #ecfdf5;
    color: #10b981;
}

.toolbar-cart-btn {
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #000, #1f2937);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.toolbar-cart-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.toolbar-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Mobile adjustments */
@media (max-width: 640px) {
    .toolbar-content {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .toolbar-price {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    .select-size-btn,
    .toolbar-cart-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Desktop - hide sticky toolbar */
@media (min-width: 1024px) {
    .product-sticky-toolbar {
        display: none;
    }
}
</style>

<script>
// Size selector functionality
document.querySelectorAll('.size-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const system = tab.getAttribute('data-system');
        
        // Switch tabs
        document.querySelectorAll('.size-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        // Switch size systems
        document.querySelectorAll('.size-system').forEach(s => s.classList.remove('active'));
        document.querySelector(`.size-system[data-system="${system}"]`).classList.add('active');
    });
});

// Size selection
let selectedSize = null;

document.querySelectorAll('.size-option').forEach(option => {
    option.addEventListener('click', () => {
        if (option.disabled) return;
        
        // Deselect all
        document.querySelectorAll('.size-option').forEach(o => o.classList.remove('selected'));
        
        // Select this
        option.classList.add('selected');
        
        // Store selected size
        selectedSize = {
            eu: option.getAttribute('data-size-eu'),
            cm: option.getAttribute('data-size-cm'),
            uk: option.getAttribute('data-size-uk'),
            us: option.getAttribute('data-size-us')
        };
        
        // Update toolbar
        const sizeText = option.textContent.trim();
        const system = document.querySelector('.size-tab.active').getAttribute('data-system').toUpperCase();
        document.getElementById('selectedSizeText').textContent = `Размер: ${system} ${sizeText}`;
        document.querySelector('.select-size-btn').classList.add('selected');
        
        // Enable cart button
        document.querySelector('.toolbar-cart-btn').disabled = false;
    });
});

// Sticky toolbar visibility
let lastScroll = 0;
const stickyToolbar = document.getElementById('stickyToolbar');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    // Show toolbar when scrolling down past 300px
    if (currentScroll > 300 && currentScroll > lastScroll) {
        stickyToolbar.classList.add('visible');
    } else if (currentScroll < lastScroll - 50) {
        stickyToolbar.classList.remove('visible');
    }
    
    lastScroll = currentScroll;
});

// Scroll to size selector
function scrollToSizeSelector() {
    document.querySelector('.size-selector-advanced').scrollIntoView({ 
        behavior: 'smooth',
        block: 'center'
    });
}

// Add to cart from toolbar
function addToCartFromToolbar() {
    if (!selectedSize) {
        alert('Выберите размер');
        scrollToSizeSelector();
        return;
    }
    
    // TODO: Implement cart logic
    console.log('Adding to cart:', selectedSize);
    alert('Товар добавлен в корзину!');
}

// Size guide modal
function openSizeGuide() {
    // TODO: Implement size guide modal
    alert('Открыть таблицу размеров');
}
</script>
