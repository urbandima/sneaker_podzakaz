/**
 * Price Slider Initialization - noUiSlider
 * ИСПРАВЛЕНИЕ ПРОБЛЕМЫ #16
 */

(function() {
    'use strict';

    function initPriceSlider() {
        const priceSlider = document.getElementById('price-slider');
        if (!priceSlider || typeof noUiSlider === 'undefined') {
            return; // Слайдер не нужен на этой странице или библиотека не загружена
        }

        // Получаем мин/макс из data-атрибутов или инпутов
        const priceFromInput = document.getElementById('price-from');
        const priceToInput = document.getElementById('price-to');

        if (!priceFromInput || !priceToInput) {
            return;
        }

        const min = parseFloat(priceFromInput.value) || 0;
        const max = parseFloat(priceToInput.value) || 1000;

        // ИСПРАВЛЕНИЕ: Проверяем, не инициализирован ли слайдер уже
        if (priceSlider.noUiSlider) {
            // Слайдер уже существует - обновляем его
            priceSlider.noUiSlider.updateOptions({
                start: [min, max],
                range: {
                    'min': min,
                    'max': max
                }
            });
            return;
        }

        // Создаем слайдер (только если его еще нет)
        noUiSlider.create(priceSlider, {
            start: [min, max],
            connect: true,
            range: {
                'min': min,
                'max': max
            },
            step: 10,
            format: {
                to: function(value) {
                    return Math.round(value);
                },
                from: function(value) {
                    return Number(value);
                }
            }
        });

        // Обновляем инпуты при изменении слайдера
        priceSlider.noUiSlider.on('update', function(values, handle) {
            const value = values[handle];
            if (handle === 0) {
                priceFromInput.value = value;
            } else {
                priceToInput.value = value;
            }
        });

        // Применяем фильтр при отпускании слайдера
        priceSlider.noUiSlider.on('change', function() {
            // Если есть глобальная функция applyFilters - вызываем её
            if (typeof window.applyFilters === 'function') {
                window.applyFilters();
            }
        });

        // Обновляем слайдер при изменении инпутов
        priceFromInput.addEventListener('change', function() {
            priceSlider.noUiSlider.set([this.value, null]);
        });

        priceToInput.addEventListener('change', function() {
            priceSlider.noUiSlider.set([null, this.value]);
        });
    }

    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', initPriceSlider);
    
    // Экспортируем функцию для повторной инициализации (например, после AJAX)
    window.reinitPriceSlider = initPriceSlider;

})();
