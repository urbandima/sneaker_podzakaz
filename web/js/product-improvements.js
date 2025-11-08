/**
 * Product Page Improvements - JavaScript
 * Sticky панель с выбором размера + Быстрая покупка в 1 клик
 */

(function() {
    'use strict';

    let selectedSize = null;
    let selectedPrice = null;

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        initStickyBar();
        initQuickOrderModal();
        initSizeSelection();
    });

    /**
     * Инициализация sticky панели
     */
    function initStickyBar() {
        const stickyBar = document.getElementById('stickyBar');
        if (!stickyBar) return;

        let lastScrollY = window.scrollY;
        const triggerHeight = 800; // Показывать sticky после 800px скролла

        window.addEventListener('scroll', function() {
            const scrollY = window.scrollY;
            
            if (scrollY > triggerHeight) {
                stickyBar.classList.add('visible');
            } else {
                stickyBar.classList.remove('visible');
            }

            lastScrollY = scrollY;
        });

        // Закрытие dropdown при клике вне его
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('stickySizeDropdown');
            const btn = document.getElementById('stickySizeBtn');
            
            if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.remove('show');
                btn.classList.remove('active');
            }
        });
    }

    /**
     * Переключение dropdown размера в sticky панели
     */
    window.toggleStickySizeDropdown = function() {
        const dropdown = document.getElementById('stickySizeDropdown');
        const btn = document.getElementById('stickySizeBtn');
        
        if (!dropdown || !btn) return;

        const isOpen = dropdown.classList.contains('show');
        
        if (isOpen) {
            dropdown.classList.remove('show');
            btn.classList.remove('active');
        } else {
            dropdown.classList.add('show');
            btn.classList.add('active');
        }
    };

    /**
     * Выбор размера в sticky панели
     */
    window.selectStickySize = function(element) {
        const size = element.dataset.size;
        const price = parseFloat(element.dataset.price);
        
        // Сохраняем выбранный размер
        selectedSize = size;
        selectedPrice = price;
        
        // Обновляем label кнопки
        const label = document.getElementById('stickySizeLabel');
        if (label) {
            label.textContent = size + ' EU';
        }
        
        // Обновляем цену
        const priceElement = document.getElementById('stickyPrice');
        if (priceElement && price) {
            priceElement.textContent = formatCurrency(price);
        }
        
        // Визуальное выделение выбранного размера
        document.querySelectorAll('.sticky-size-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        element.classList.add('selected');
        
        // Закрываем dropdown
        const dropdown = document.getElementById('stickySizeDropdown');
        const btn = document.getElementById('stickySizeBtn');
        if (dropdown && btn) {
            dropdown.classList.remove('show');
            btn.classList.remove('active');
        }

        // Синхронизируем с основным выбором размера на странице
        syncMainSizeSelection(size);
    };

    /**
     * Синхронизация выбора размера с основной формой
     */
    function syncMainSizeSelection(size) {
        const sizeInputs = document.querySelectorAll('input[name="size"]');
        sizeInputs.forEach(input => {
            if (input.value === size) {
                input.checked = true;
                // Триггерим change event для обновления цены
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    /**
     * Инициализация выбора размера в основной форме
     */
    function initSizeSelection() {
        const sizeInputs = document.querySelectorAll('input[name="size"]');
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    selectedSize = this.value;
                    const price = parseFloat(this.dataset.price);
                    if (price) {
                        selectedPrice = price;
                        
                        // Обновляем sticky панель
                        const label = document.getElementById('stickySizeLabel');
                        if (label) {
                            label.textContent = this.dataset.euSize || this.value;
                        }
                        
                        const priceElement = document.getElementById('stickyPrice');
                        if (priceElement) {
                            priceElement.textContent = formatCurrency(price);
                        }
                    }
                }
            });
        });
    }

    /**
     * Быстрый заказ из sticky панели
     */
    window.quickOrderFromSticky = function() {
        if (!selectedSize) {
            // Если размер не выбран, открываем dropdown
            const dropdown = document.getElementById('stickySizeDropdown');
            const btn = document.getElementById('stickySizeBtn');
            if (dropdown && btn) {
                dropdown.classList.add('show');
                btn.classList.add('active');
                
                // Анимация привлечения внимания
                btn.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    btn.style.animation = '';
                }, 500);
            }
            return;
        }
        
        // Открываем модальное окно быстрой покупки
        openQuickOrderModal();
    };

    /**
     * Инициализация модального окна быстрой покупки
     */
    function initQuickOrderModal() {
        const modal = document.getElementById('quickOrderModal');
        if (!modal) return;

        // Закрытие по клику на overlay
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeQuickOrderModal();
            }
        });

        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQuickOrderModal();
            }
        });

        // Обработка выбора размера в модальном окне
        const sizeSelect = document.getElementById('quickOrderSize');
        if (sizeSelect) {
            sizeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = parseFloat(selectedOption.dataset.price);
                
                if (price) {
                    const priceElement = document.getElementById('quickOrderPrice');
                    if (priceElement) {
                        priceElement.textContent = formatCurrency(price);
                    }
                }
            });
        }
    }

    /**
     * Открыть модальное окно быстрой покупки
     */
    window.openQuickOrderModal = function() {
        const modal = document.getElementById('quickOrderModal');
        if (!modal) return;

        // Предзаполняем размер если выбран
        if (selectedSize) {
            const sizeSelect = document.getElementById('quickOrderSize');
            if (sizeSelect) {
                const option = Array.from(sizeSelect.options).find(opt => opt.value === selectedSize);
                if (option) {
                    option.selected = true;
                    // Обновляем цену
                    const event = new Event('change');
                    sizeSelect.dispatchEvent(event);
                }
            }
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Фокус на первое поле
        setTimeout(() => {
            const firstInput = modal.querySelector('input[type="text"], select');
            if (firstInput) firstInput.focus();
        }, 100);
    };

    /**
     * Закрыть модальное окно
     */
    window.closeQuickOrderModal = function() {
        const modal = document.getElementById('quickOrderModal');
        if (!modal) return;

        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Очистка формы (опционально)
        // const form = document.getElementById('quickOrderForm');
        // if (form) form.reset();
    };

    /**
     * Отправка формы быстрого заказа
     */
    window.submitQuickOrder = function(event) {
        event.preventDefault();

        const form = document.getElementById('quickOrderForm');
        const submitBtn = document.getElementById('quickOrderSubmitBtn');
        
        if (!form || !submitBtn) return;

        // Валидация
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Disable кнопки
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';

        // Собираем данные
        const formData = new FormData(form);
        const data = {
            product_id: getProductId(),
            size: formData.get('size'),
            name: formData.get('name'),
            phone: formData.get('phone'),
            comment: formData.get('comment') || ''
        };

        // Отправка на сервер
        fetch('/catalog/quick-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Успех
                showSuccessMessage();
                setTimeout(() => {
                    closeQuickOrderModal();
                    form.reset();
                }, 2000);
            } else {
                // Ошибка
                showErrorMessage(result.message || 'Произошла ошибка. Попробуйте еще раз.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Ошибка соединения. Попробуйте еще раз.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Оформить заказ';
        });
    };

    /**
     * Получить ID товара
     */
    function getProductId() {
        const meta = document.querySelector('meta[name="product-id"]');
        return meta ? meta.content : null;
    }

    /**
     * Получить CSRF токен
     */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Показать сообщение об успехе
     */
    function showSuccessMessage() {
        const modal = document.getElementById('quickOrderModal');
        const modalBody = modal.querySelector('.modal-body');
        
        if (modalBody) {
            const originalContent = modalBody.innerHTML;
            modalBody.innerHTML = `
                <div style="text-align:center;padding:3rem 2rem">
                    <div style="width:80px;height:80px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 8px 24px rgba(16,185,129,0.3);animation:scaleIn 0.3s">
                        <i class="bi bi-check-lg" style="font-size:3rem;color:#fff"></i>
                    </div>
                    <h3 style="font-size:1.5rem;font-weight:800;margin-bottom:0.5rem">Заказ оформлен!</h3>
                    <p style="color:#666;font-size:1rem;margin-bottom:1.5rem">Менеджер свяжется с вами в течение 15 минут</p>
                </div>
            `;
            
            // Восстановление через 2 секунды
            setTimeout(() => {
                modalBody.innerHTML = originalContent;
                initQuickOrderModal(); // Переинициализация обработчиков
            }, 2000);
        }
    }

    /**
     * Показать сообщение об ошибке
     */
    function showErrorMessage(message) {
        alert(message); // Можно заменить на более красивое уведомление
    }

    /**
     * Форматирование цены
     */
    function formatCurrency(price) {
        return new Intl.NumberFormat('ru-BY', {
            style: 'currency',
            currency: 'BYN',
            minimumFractionDigits: 2
        }).format(price);
    }

    // Добавляем CSS для анимации shake
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

})();
