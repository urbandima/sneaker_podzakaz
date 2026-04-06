/**
 * Admin Wizard JS
 * ===============
 * JS вынесенный из inline <script> блоков wizard-страниц:
 * - order/view-wizard.php
 * - order/view-wizard-new.php
 * - order/create-wizard.php
 */

/* === ORDER VIEW WIZARD === */

/* -- view-wizard.php -- */
/* Note: .js-flag handlers use PHP-injected CSRF and URL, so they remain as registerJs in the view.
   All non-PHP-dependent functions are extracted here. */

document.addEventListener('DOMContentLoaded', function() {

    /* ── COLLAPSIBLE (view-wizard.php) ── */
    window.toggleCollapsible = function(header) {
        var section = header.closest('.collapsible-section');
        if (section) section.classList.toggle('collapsible-section--collapsed');
    };

    /* ── COPY LINK (view-wizard.php) ── */
    window.copyLink = function() {
        var input = document.getElementById('publicLink');
        if (!input) return;
        navigator.clipboard.writeText(input.value).then(function() {
            showSaveIndicator('Ссылка скопирована');
        }).catch(function() {
            input.select();
            document.execCommand('copy');
            showSaveIndicator('Ссылка скопирована');
        });
    };

    /* ── SAVE INDICATOR (view-wizard.php) ── */
    window.showSaveIndicator = function(message) {
        var indicator = document.getElementById('saveIndicator');
        if (!indicator) return;
        indicator.innerHTML = '<span>✅</span><span>' + (message || 'Сохранено') + '</span>';
        indicator.classList.add('save-indicator--show');
        setTimeout(function() { indicator.classList.remove('save-indicator--show'); }, 2000);
    };

    /* ── AUTO-SAVE INDICATOR on edit form fields (view-wizard.php) ── */
    var orderUpdateForm = document.getElementById('orderUpdateForm');
    if (orderUpdateForm) {
        orderUpdateForm.querySelectorAll('input, textarea, select').forEach(function(field) {
            field.addEventListener('input', function() {
                var indicator = document.getElementById('saveIndicator');
                if (indicator) {
                    indicator.innerHTML = '<span>✏️</span><span>Изменения внесены</span>';
                    indicator.classList.add('save-indicator--show');
                }
            });
        });
    }

    /* ── ACCORDION (view-wizard-new.php) ── */
    window.toggleAccordion = function(id) {
        var element = document.getElementById(id);
        if (element) element.classList.toggle('accordion-item--open');
    };

    /* ── COPY PUBLIC LINK (view-wizard-new.php) ── */
    window.copyPublicLink = function() {
        var input = document.querySelector('.public-link input');
        if (!input) return;
        input.select();
        document.execCommand('copy');
        var button = document.querySelector('.public-link button');
        if (button) {
            var originalText = button.innerHTML;
            button.innerHTML = '✅ Скопировано';
            button.style.background = 'var(--success, #10b981)';
            button.style.borderColor = 'var(--success, #10b981)';
            setTimeout(function() {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.borderColor = '';
            }, 2000);
        }
    };

}); // end DOMContentLoaded

/* === ORDER CREATE WIZARD === */

/* -- create-wizard.php -- */
/* Note: JS is fully self-contained (no PHP vars used inside logic), so fully extracted. */

var currentStep = 1;
var productIndex = 1;
var autosaveTimer = null;
var stepErrors = {};

document.addEventListener('DOMContentLoaded', function() {
    loadFromLocalStorage();
    initValidation();
    initCalculation();
    initAutosave();
    updateSummary();
    updateStepValidation();
    updateStepButtons();

    /* Клик по шагам в сайдбаре */
    document.querySelectorAll('.step-item').forEach(function(step) {
        step.addEventListener('click', function() {
            var targetStep = parseInt(this.dataset.step);
            if (targetStep < currentStep || validateCurrentStep()) {
                changeStep(targetStep - currentStep);
            }
        });
    });
});

function changeStep(direction) {
    var newStep = currentStep + direction;
    if (newStep < 1 || newStep > 4) return;
    saveToLocalStorage();
    document.querySelectorAll('.wizard-step-content').forEach(function(el) { el.classList.remove('wizard-step-content--active'); });
    var newStepContent = document.querySelector('[data-step="' + newStep + '"].wizard-step-content');
    if (newStepContent) newStepContent.classList.add('wizard-step-content--active');
    document.querySelectorAll('.step-item').forEach(function(el) { el.classList.remove('step-item--active'); });
    var newStepItem = document.querySelector('.step-item[data-step="' + newStep + '"]');
    if (newStepItem) newStepItem.classList.add('step-item--active');
    for (var i = 1; i < newStep; i++) {
        var stepItem = document.querySelector('.step-item[data-step="' + i + '"]');
        if (stepItem) {
            stepItem.classList.add('step-item--completed');
            if (stepErrors[i] && stepErrors[i].length > 0) {
                stepItem.classList.add('step-item--has-errors');
                var errorSummary = document.getElementById('step' + i + '-errors');
                if (errorSummary) { errorSummary.textContent = stepErrors[i].length + ' ошибок'; errorSummary.classList.remove('step-validation-summary--hidden'); }
            }
        }
    }
    for (var j = newStep; j <= 4; j++) {
        var si = document.querySelector('.step-item[data-step="' + j + '"]');
        if (si) { si.classList.remove('step-item--completed'); si.classList.remove('step-item--has-errors'); }
        var es = document.getElementById('step' + j + '-errors');
        if (es) es.classList.add('step-validation-summary--hidden');
    }
    currentStep = newStep;
    updateStepButtons();
    updateSummary();
    updateStepValidation();
}

function updateStepButtons() {
    var prevBtn = document.getElementById('prevBtn');
    var nextBtn = document.getElementById('nextBtn');
    var submitBtn = document.getElementById('submitBtn');
    var currentStepEl = document.getElementById('currentStep');
    if (prevBtn) prevBtn.disabled = currentStep === 1;
    if (currentStep === 4) {
        if (nextBtn) nextBtn.style.display = 'none';
        if (submitBtn) submitBtn.style.display = 'inline-flex';
    } else {
        if (nextBtn) nextBtn.style.display = 'inline-flex';
        if (submitBtn) submitBtn.style.display = 'none';
    }
    if (currentStepEl) currentStepEl.textContent = currentStep;
}

function updateStepValidation() {
    var currentStepElement = document.querySelector('.wizard-step-content--active');
    if (!currentStepElement) return;
    var requiredFields = currentStepElement.querySelectorAll('[data-validation*="required"]');
    var errors = [];
    requiredFields.forEach(function(field) {
        var value = field.value.trim();
        var validation = field.dataset.validation;
        if (validation.includes('required') && !value) { errors.push('Обязательное поле не заполнено'); }
        else if (validation.includes('email') && value && !isValidEmail(value)) { errors.push('Некорректный email'); }
        else if (validation.includes('phone') && value && !isValidPhone(value)) { errors.push('Некорректный телефон'); }
        else if (validation.includes('positive') && value && parseFloat(value) <= 0) { errors.push('Значение должно быть положительным'); }
    });
    if (currentStep === 2) {
        if (document.querySelectorAll('#productsTableBody tr').length === 0 || !hasValidProducts()) {
            errors.push('Добавьте хотя бы один товар с корректными данными');
        }
    }
    stepErrors[currentStep] = errors;
    var stepItem = document.querySelector('.step-item[data-step="' + currentStep + '"]');
    var errorSummary = document.getElementById('step' + currentStep + '-errors');
    if (errors.length > 0) {
        if (stepItem) stepItem.classList.add('step-item--has-errors');
        if (errorSummary) { errorSummary.textContent = errors.length + ' ошибок'; errorSummary.classList.remove('step-validation-summary--hidden'); }
    } else {
        if (stepItem) stepItem.classList.remove('step-item--has-errors');
        if (errorSummary) errorSummary.classList.add('step-validation-summary--hidden');
    }
}

function initValidation() {
    document.querySelectorAll('[data-validation]').forEach(function(field) {
        field.addEventListener('blur', function() { validateField(this); updateStepValidation(); });
        field.addEventListener('input', function() { if (this.classList.contains('error')) { validateField(this); updateStepValidation(); } });
    });
}

function validateField(field) {
    var validation = field.dataset.validation;
    var value = field.value.trim();
    var isValid = true;
    var errorMessage = '';
    field.classList.remove('error', 'success');
    var existingError = field.parentNode.querySelector('.field-error');
    var existingSuccess = field.parentNode.querySelector('.field-success');
    if (existingError) existingError.remove();
    if (existingSuccess) existingSuccess.remove();
    if (validation.includes('required') && !value) { isValid = false; errorMessage = 'Это поле обязательно для заполнения'; }
    else if (validation.includes('email') && value && !isValidEmail(value)) { isValid = false; errorMessage = 'Введите корректный email'; }
    else if (validation.includes('phone') && value && !isValidPhone(value)) { isValid = false; errorMessage = 'Введите телефон в формате +37529XXXXXXX'; }
    else if (validation.includes('positive') && value && parseFloat(value) <= 0) { isValid = false; errorMessage = 'Значение должно быть положительным'; }
    if (!isValid) {
        field.classList.add('error');
        var errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = '❌ ' + errorMessage;
        field.parentNode.appendChild(errorDiv);
    } else if (value) {
        field.classList.add('success');
        var successDiv = document.createElement('div');
        successDiv.className = 'field-success';
        successDiv.innerHTML = '✅';
        field.parentNode.appendChild(successDiv);
    }
    return isValid;
}

function validateCurrentStep() {
    var errors = stepErrors[currentStep] || [];
    if (errors.length > 0) { showMessage('Есть ошибки в текущем шаге. Пожалуйста, исправьте их перед отправкой формы.', 'error'); return false; }
    return true;
}

function hasValidProducts() {
    var products = document.querySelectorAll('#productsTableBody tr');
    for (var i = 0; i < products.length; i++) {
        var product = products[i];
        var nameInput = product.querySelector('input[name*="[product_name]"]');
        var priceInput = product.querySelector('input[name*="[price]"]');
        var quantityInput = product.querySelector('input[name*="[quantity]"]');
        if (nameInput && priceInput && quantityInput) {
            var name = nameInput.value.trim();
            var price = parseFloat(priceInput.value);
            var quantity = parseInt(quantityInput.value);
            if (name && price > 0 && quantity > 0) return true;
        }
    }
    return false;
}

function isValidEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
function isValidPhone(phone) { return /^\+375\d{9}$/.test(phone); }

window.addProduct = function() {
    var tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    var row = document.createElement('tr');
    row.dataset.index = productIndex;
    row.innerHTML = '<td><input type="text" name="OrderItem[' + productIndex + '][product_name]" placeholder="Введите название товара" data-validation="required" data-field="product_name_' + productIndex + '"></td>' +
        '<td><input type="number" name="OrderItem[' + productIndex + '][quantity]" value="1" min="1" data-field="quantity_' + productIndex + '" data-calc="quantity"></td>' +
        '<td><input type="number" name="OrderItem[' + productIndex + '][price]" step="0.01" placeholder="0.00" data-validation="required positive" data-field="price_' + productIndex + '" data-calc="price"></td>' +
        '<td class="total-cell" data-total="' + productIndex + '">0.00</td>' +
        '<td class="product-actions"><button type="button" class="icon-btn icon-btn--danger" onclick="removeProduct(' + productIndex + ')" title="Удалить">🗑️</button></td>';
    tbody.appendChild(row);
    var idx = productIndex;
    row.querySelectorAll('input').forEach(function(input) {
        if (input.dataset.validation) {
            input.addEventListener('blur', function() { validateField(this); });
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) validateField(this);
                if (this.dataset.calc) calculateProductTotal(idx);
            });
        }
    });
    productIndex++;
    updateRemoveButtons();
};

window.removeProduct = function(index) {
    var row = document.querySelector('tr[data-index="' + index + '"]');
    if (row) { row.remove(); calculateProductsTotal(); updateRemoveButtons(); updateSummary(); }
};

function updateRemoveButtons() {
    var rows = document.querySelectorAll('#productsTableBody tr');
    document.querySelectorAll('#productsTableBody .icon-btn--danger').forEach(function(btn) {
        btn.disabled = rows.length === 1;
    });
}

function calculateProductTotal(index) {
    var row = document.querySelector('tr[data-index="' + index + '"]');
    if (!row) return;
    var quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    var price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
    var total = quantity * price;
    var totalCell = row.querySelector('.total-cell');
    if (totalCell) totalCell.textContent = total.toFixed(2);
    calculateProductsTotal();
}

function calculateProductsTotal() {
    var total = 0;
    document.querySelectorAll('#productsTableBody tr').forEach(function(row) {
        var totalCell = row.querySelector('.total-cell');
        total += parseFloat(totalCell ? totalCell.textContent : 0) || 0;
    });
    var productsTotalEl = document.getElementById('productsTotal');
    if (productsTotalEl) productsTotalEl.textContent = total.toFixed(2) + ' BYN';
    var productPriceField = document.querySelector('[data-field="product_price"]');
    if (productPriceField) productPriceField.value = total.toFixed(2);
    calculateFinalTotal();
    updateSummary();
}

function calculateFinalTotal() {
    var productsTotalEl = document.getElementById('productsTotal');
    var productsTotal = productsTotalEl ? parseFloat(productsTotalEl.textContent) || 0 : 0;
    var logisticsPrice = parseFloat(document.querySelector('[data-field="logistics_price"]') ? document.querySelector('[data-field="logistics_price"]').value : 0) || 0;
    var commissionPrice = parseFloat(document.querySelector('[data-field="commission_price"]') ? document.querySelector('[data-field="commission_price"]').value : 0) || 0;
    var deliveryCost = parseFloat(document.querySelector('[data-field="delivery_cost"]') ? document.querySelector('[data-field="delivery_cost"]').value : 0) || 0;
    var finalTotal = productsTotal + logisticsPrice + commissionPrice + deliveryCost;
    var totalAmountField = document.querySelector('[data-field="total_amount"]');
    if (totalAmountField) totalAmountField.value = finalTotal.toFixed(2);
}

function initCalculation() {
    document.querySelectorAll('[data-calc]').forEach(function(field) {
        field.addEventListener('input', function() {
            if (this.dataset.calc === 'logistics') calculateLogisticsPrice();
            calculateFinalTotal();
            updateSummary();
        });
    });
    document.querySelectorAll('#productsTableBody input[data-calc]').forEach(function(input) {
        input.addEventListener('input', function() {
            var row = this.closest('tr');
            if (row) calculateProductTotal(row.dataset.index);
        });
    });
}

function calculateLogisticsPrice() {
    var shipmentValueEl = document.querySelector('[data-field="shipment_value_cny"]');
    var shipmentValue = shipmentValueEl ? parseFloat(shipmentValueEl.value) || 0 : 0;
    var cnyToBynRate = 0.39;
    var logisticsPrice = shipmentValue * cnyToBynRate;
    var logisticsField = document.querySelector('[data-field="logistics_price"]');
    if (logisticsField) logisticsField.value = logisticsPrice.toFixed(2);
}

function updateSummary() {
    var productsCount = document.querySelectorAll('#productsTableBody tr').length;
    var productsTotalEl = document.getElementById('productsTotal');
    var productsTotal = productsTotalEl ? parseFloat(productsTotalEl.textContent) || 0 : 0;
    var logisticsEl = document.querySelector('[data-field="logistics_price"]');
    var logisticsPrice = logisticsEl ? parseFloat(logisticsEl.value) || 0 : 0;
    var totalAmountEl = document.querySelector('[data-field="total_amount"]');
    var finalTotal = totalAmountEl ? parseFloat(totalAmountEl.value) || 0 : 0;
    var si = document.getElementById('summaryItems');
    var st = document.getElementById('summaryTotal');
    var sl = document.getElementById('summaryLogistics');
    var sf = document.getElementById('summaryFinal');
    if (si) si.textContent = productsCount;
    if (st) st.textContent = productsTotal.toFixed(2) + ' BYN';
    if (sl) sl.textContent = logisticsPrice.toFixed(2) + ' BYN';
    if (sf) sf.textContent = finalTotal.toFixed(2) + ' BYN';
}

window.toggleCollapsible = function(header) {
    var section = header.closest('.collapsible-section');
    if (section) section.classList.toggle('collapsible-section--collapsed');
};

function initAutosave() {
    document.querySelectorAll('input, textarea, select').forEach(function(field) {
        field.addEventListener('input', function() {
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(saveToLocalStorage, 1000);
        });
    });
}

function saveToLocalStorage() {
    var form = document.getElementById('orderWizardForm');
    if (!form) return;
    var formData = new FormData(form);
    var data = {};
    for (var pair of formData.entries()) {
        if (!data[pair[0]]) data[pair[0]] = [];
        data[pair[0]].push(pair[1]);
    }
    localStorage.setItem('orderWizardDraft', JSON.stringify({ step: currentStep, data: data, timestamp: Date.now() }));
    showAutosaveIndicator();
}

function loadFromLocalStorage() {
    var saved = localStorage.getItem('orderWizardDraft');
    if (!saved) return;
    try {
        var saveData = JSON.parse(saved);
        if (Date.now() - saveData.timestamp > 24 * 60 * 60 * 1000) { localStorage.removeItem('orderWizardDraft'); return; }
        var data = saveData.data;
        for (var key in data) {
            var field = document.querySelector('[name="' + key + '"]');
            if (field) field.value = data[key][0];
        }
        if (saveData.step && saveData.step !== currentStep) { changeStep(saveData.step - currentStep); }
        showMessage('Черновик заказа восстановлен', 'success');
    } catch(e) { console.error('Error loading draft:', e); }
}

function showAutosaveIndicator() {
    var indicator = document.getElementById('autosaveIndicator');
    if (!indicator) return;
    indicator.classList.add('autosave-indicator--show');
    setTimeout(function() { indicator.classList.remove('autosave-indicator--show'); }, 2000);
}

function showMessage(message, type) {
    type = type || 'info';
    var messageDiv = document.createElement('div');
    messageDiv.className = type + '-message';
    messageDiv.innerHTML = '<span>' + (type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️') + '</span><span>' + message + '</span>';
    var wizardMain = document.querySelector('.wizard-main');
    if (wizardMain) wizardMain.insertBefore(messageDiv, wizardMain.firstChild);
    setTimeout(function() { messageDiv.remove(); }, 5000);
}

window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges()) {
        e.preventDefault();
        e.returnValue = 'У вас есть несохранённые данные. Вы уверены, что хотите уйти?';
        return e.returnValue;
    }
});

function hasUnsavedChanges() {
    var saved = localStorage.getItem('orderWizardDraft');
    if (!saved) return false;
    try { var saveData = JSON.parse(saved); return Date.now() - saveData.timestamp < 5 * 60 * 1000; }
    catch(e) { return false; }
}

window.submitForm = function() {
    var allValid = true;
    var allErrors = [];
    for (var step = 1; step <= 4; step++) {
        if (stepErrors[step] && stepErrors[step].length > 0) {
            allValid = false;
            allErrors.push('Шаг ' + step + ': ' + stepErrors[step].join(', '));
        }
    }
    if (!allValid) { showMessage('Пожалуйста, исправьте все ошибки во всех шагах перед созданием заказа:\n\n' + allErrors.join('\n\n'), 'error'); return; }
    if (!hasValidProducts()) { showMessage('Добавьте хотя бы один товар с корректными данными', 'error'); return; }
    var form = document.getElementById('orderWizardForm');
    if (form) form.submit();
};

window.changeStep = changeStep;
