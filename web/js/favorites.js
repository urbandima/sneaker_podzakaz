/**
 * Функционал избранного - Vanilla JS
 * ИСПРАВЛЕНО (Проблема #4): Переписано с jQuery на Vanilla JS
 * Используется глобальный NotificationManager из notifications.js
 */

(function() {
    'use strict';

    // Получить CSRF токен
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Toggle избранное (универсальная функция)
     * Работает и в каталоге, и на странице товара
     */
    window.toggleFavorite = function(event, productId) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const isActive = button.classList.contains('active');
        const url = isActive ? '/catalog/remove-favorite' : '/catalog/add-favorite';
        const icon = button.querySelector('i');
        const label = button.querySelector('.favorite-label');

        // Оптимистическое состояние загрузки
        button.classList.add('loading');
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        if (icon) {
            icon.dataset.prevClass = icon.className;
            icon.className = 'bi bi-hourglass-split spin';
        }
        if (label) {
            label.dataset.prevText = label.textContent;
            label.textContent = isActive ? 'Удаляем...' : 'Добавляем...';
        }

        const formData = new FormData();
        formData.append('productId', productId);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            finalizeFavorite(button, icon, label, () => {
                if (!data.success) {
                    throw new Error(data.message || 'Не удалось обновить избранное');
                }

                button.classList.toggle('active');
                button.setAttribute('aria-pressed', button.classList.contains('active') ? 'true' : 'false');

                if (icon) {
                    icon.className = button.classList.contains('active') ? 'bi bi-heart-fill' : 'bi bi-heart';
                }

                showFavoriteToast(button.classList.contains('active'));
                updateFavCount(data.count);
            });
        })
        .catch(error => {
            console.error('Favorites error:', error);
            finalizeFavorite(button, icon, label, () => {
                showFavoriteToast(false, error.message || 'Ошибка соединения', true);
            });
        });
    };

    function finalizeFavorite(button, icon, label, callback) {
        button.classList.remove('loading');
        button.disabled = false;
        button.removeAttribute('aria-busy');

        if (icon) {
            icon.className = icon.dataset.prevClass || 'bi bi-heart';
            delete icon.dataset.prevClass;
        }

        if (label && label.dataset.prevText) {
            label.textContent = label.dataset.prevText;
            delete label.dataset.prevText;
        }

        if (typeof callback === 'function') {
            callback();
        }
    }

    function showFavoriteToast(isActive, message = '', isError = false) {
        if (!window.NotificationManager) {
            return;
        }

        if (isError) {
            NotificationManager.error(message || 'Ошибка операции');
            return;
        }

        if (isActive) {
            NotificationManager.success('✓ Добавлено в избранное');
        } else {
            NotificationManager.info(message || 'Удалено из избранного');
        }
    }

    /**
     * Toggle избранное (старая версия с другими параметрами)
     * DEPRECATED: Используйте toggleFavorite() с event
     * Эта функция оставлена для backward compatibility
     */
    window.toggleFavoriteOld = function(productId, button) {
        // Определяем текущее состояние
        const isActive = button.classList.contains('active');
        const url = isActive ? '/catalog/remove-favorite' : '/catalog/add-favorite';
        
        const formData = new FormData();
        formData.append('productId', productId);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Переключаем состояние кнопки
                button.classList.toggle('active');
                
                // Переключаем иконку
                const icon = button.querySelector('i');
                if (icon) {
                    if (button.classList.contains('active')) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                    } else {
                        icon.classList.remove('bi-heart-fill');
                        icon.classList.add('bi-heart');
                    }
                }
                
                // Показываем уведомление
                if (window.NotificationManager) {
                    if (button.classList.contains('active')) {
                        NotificationManager.success('✓ Добавлено в избранное');
                    } else {
                        NotificationManager.info('Удалено из избранного');
                    }
                }
                
                // Обновляем счетчик
                updateFavCount(data.count);
            } else {
                if (window.NotificationManager) {
                    NotificationManager.error(data.message || 'Ошибка');
                }
            }
        })
        .catch(error => {
            console.error('Favorites error:', error);
            if (window.NotificationManager) {
                NotificationManager.error('Ошибка соединения');
            }
        });
    };

    /**
     * Обновить счетчик избранного
     */
    function updateFavCount(count) {
        const badge = document.getElementById('favCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    /**
     * Загрузить текущее количество при загрузке страницы
     */
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/catalog/favorites-count', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                updateFavCount(data.count);
            }
        })
        .catch(error => {
            console.warn('Failed to load favorites count:', error);
        });
    });

    // Экспортируем функцию для обратной совместимости
    window.updateFavCount = updateFavCount;

})();
