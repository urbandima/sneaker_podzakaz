/**
 * Глобальная система уведомлений
 * ИСПРАВЛЕНИЕ ПРОБЛЕМЫ #3: Единая реализация для всего сайта
 */

(function() {
    'use strict';

    // Создаем глобальный объект
    window.NotificationManager = {
        /**
         * Показать уведомление
         * @param {string} message - Текст сообщения
         * @param {string} type - Тип: 'success', 'error', 'warning', 'info'
         * @param {number} duration - Длительность в мс (по умолчанию 3000)
         */
        show: function(message, type = 'info', duration = 2000) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            // Иконка по типу
            const icons = {
                success: '<i class="bi bi-check-circle-fill"></i>',
                error: '<i class="bi bi-x-circle-fill"></i>',
                warning: '<i class="bi bi-exclamation-triangle-fill"></i>',
                info: '<i class="bi bi-info-circle-fill"></i>'
            };
            
            notification.innerHTML = `
                ${icons[type] || icons.info}
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            // Стили
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${this.getBackgroundColor(type)};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                animation: slideIn 0.3s ease-out;
                min-width: 300px;
                max-width: 500px;
            `;
            
            document.body.appendChild(notification);
            
            // Автоудаление
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        },

        /**
         * Сокращения для типов
         */
        success: function(message, duration) {
            this.show(message, 'success', duration);
        },

        error: function(message, duration) {
            this.show(message, 'error', duration);
        },

        warning: function(message, duration) {
            this.show(message, 'warning', duration);
        },

        info: function(message, duration) {
            this.show(message, 'info', duration);
        },

        /**
         * Получить цвет фона по типу
         */
        getBackgroundColor: function(type) {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            return colors[type] || colors.info;
        }
    };

    // Backward compatibility: глобальная функция showNotification
    window.showNotification = function(message, type = 'info', duration = 2000) {
        window.NotificationManager.show(message, type, duration);
    };

    // Добавляем CSS анимации если еще нет
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { 
                    transform: translateX(100%); 
                    opacity: 0; 
                }
                to { 
                    transform: translateX(0); 
                    opacity: 1; 
                }
            }
            
            @keyframes slideOut {
                from { 
                    transform: translateX(0); 
                    opacity: 1; 
                }
                to { 
                    transform: translateX(100%); 
                    opacity: 0; 
                }
            }

            .notification {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 14px;
                font-weight: 500;
            }

            .notification i {
                font-size: 20px;
            }

            .notification-close {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
                font-size: 18px;
                opacity: 0.8;
                transition: opacity 0.2s;
            }

            .notification-close:hover {
                opacity: 1;
            }

            /* Mobile адаптация */
            @media (max-width: 768px) {
                .notification {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    min-width: auto;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(style);
    }

})();
