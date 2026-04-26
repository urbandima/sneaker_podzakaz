/**
 * ADMIN GLOBAL SEARCH & CALCULATOR
 * B2.1: Глобальный поиск Cmd+K
 * B2.2: Быстрые действия
 * B2.3: Калькулятор цены
 */

(function() {
    'use strict';

    // Search (Ctrl+K, overlay, AJAX) is handled by admin.js — no duplicate here.
    // Only close the calculator on Escape.
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCalculator();
        }
    });

    // ===== CALCULATOR =====
    const calcOverlay = document.createElement('div');
    calcOverlay.className = 'admin-calculator-overlay';
    calcOverlay.id = 'calculator-overlay';
    document.body.appendChild(calcOverlay);

    const calcDrawer = document.createElement('div');
    calcDrawer.className = 'admin-calculator-drawer';
    calcDrawer.id = 'calculator-drawer';
    calcDrawer.innerHTML = `
        <div class="admin-calculator-header">
            <h3><i class="bi bi-calculator"></i> Калькулятор цены</h3>
            <button class="admin-btn admin-btn-secondary" onclick="closeCalculator()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="admin-calculator-body">
            <div class="admin-form-group">
                <label class="admin-form-label">Цена в CNY (юанях)</label>
                <input type="number" id="calc-cny" class="admin-form-input" placeholder="0.00" step="0.01">
            </div>
            <div class="admin-calculator-row">
                <div class="admin-form-group">
                    <label class="admin-form-label">Курс CNY/BYN</label>
                    <input type="number" id="calc-rate" class="admin-form-input" value="0.45" step="0.001">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Доставка CNY</label>
                    <input type="number" id="calc-shipping" class="admin-form-input" value="15" step="1">
                </div>
            </div>
            <div class="admin-calculator-row">
                <div class="admin-form-group">
                    <label class="admin-form-label">Комиссия (%)</label>
                    <input type="number" id="calc-commission" class="admin-form-input" value="15" step="1">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Доставка РБ (BYN)</label>
                    <input type="number" id="calc-local-shipping" class="admin-form-input" value="8" step="0.5">
                </div>
            </div>
            <div class="admin-calculator-result">
                <div class="admin-calculator-result-row">
                    <span>Цена товара:</span>
                    <span id="res-product">0 BYN</span>
                </div>
                <div class="admin-calculator-result-row">
                    <span>Доставка из Китая:</span>
                    <span id="res-shipping">0 BYN</span>
                </div>
                <div class="admin-calculator-result-row">
                    <span>Комиссия:</span>
                    <span id="res-commission">0 BYN</span>
                </div>
                <div class="admin-calculator-result-row">
                    <span>Доставка по РБ:</span>
                    <span id="res-local">0 BYN</span>
                </div>
                <div class="admin-calculator-result-row total">
                    <span>ИТОГО:</span>
                    <span id="res-total">0 BYN</span>
                </div>
            </div>
            <div style="margin-top: 24px;">
                <button class="admin-btn admin-btn-primary w-100" onclick="resetCalculator()">
                    <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(calcDrawer);

    // Открыть калькулятор
    window.openCalculator = function() {
        calcOverlay.classList.add('active');
        calcDrawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    // Закрыть калькулятор
    window.closeCalculator = function() {
        calcOverlay.classList.remove('active');
        calcDrawer.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Сбросить калькулятор
    window.resetCalculator = function() {
        document.getElementById('calc-cny').value = '';
        document.getElementById('calc-rate').value = '0.45';
        document.getElementById('calc-shipping').value = '15';
        document.getElementById('calc-commission').value = '15';
        document.getElementById('calc-local-shipping').value = '8';
        calculatePrice();
    };

    // Расчет цены
    function calculatePrice() {
        const cny = parseFloat(document.getElementById('calc-cny')?.value) || 0;
        const rate = parseFloat(document.getElementById('calc-rate')?.value) || 0.45;
        const shippingCny = parseFloat(document.getElementById('calc-shipping')?.value) || 0;
        const commissionPct = parseFloat(document.getElementById('calc-commission')?.value) || 0;
        const localShipping = parseFloat(document.getElementById('calc-local-shipping')?.value) || 0;

        const productByn = cny * rate;
        const shippingByn = shippingCny * rate;
        const subtotal = productByn + shippingByn;
        const commission = subtotal * (commissionPct / 100);
        const total = subtotal + commission + localShipping;

        document.getElementById('res-product').textContent = productByn.toFixed(2) + ' BYN';
        document.getElementById('res-shipping').textContent = shippingByn.toFixed(2) + ' BYN';
        document.getElementById('res-commission').textContent = commission.toFixed(2) + ' BYN';
        document.getElementById('res-local').textContent = localShipping.toFixed(2) + ' BYN';
        document.getElementById('res-total').textContent = total.toFixed(2) + ' BYN';
    }

    // Слушатели изменений
    ['calc-cny', 'calc-rate', 'calc-shipping', 'calc-commission', 'calc-local-shipping'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculatePrice);
    });

    calcOverlay.addEventListener('click', closeCalculator);

    // ===== QUICK ACTIONS =====
    window.openQuickAction = function(action) {
        switch(action) {
            case 'order':
                window.location.href = '/admin/order/create';
                break;
            case 'product':
                window.location.href = '/admin/product/create';
                break;
            case 'customer':
                window.location.href = '/admin/customer/create';
                break;
            case 'calculator':
                openCalculator();
                break;
        }
    };

    // Обновить курс с NB.RB (можно вызвать вручную или по таймеру)
    window.updateExchangeRate = function() {
        fetch('/admin/api/exchange-rate')
            .then(r => r.json())
            .then(data => {
                if (data.rate && document.getElementById('calc-rate')) {
                    document.getElementById('calc-rate').value = data.rate;
                    calculatePrice();
                }
            })
            .catch(err => { /* production: silent */ });
    };

})();
