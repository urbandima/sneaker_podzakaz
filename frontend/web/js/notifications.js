/**
 * Глобальная система уведомлений
 * B&W Design System
 */

(function () {
    'use strict';

    // Создаем глобальный объект
    window.NotificationManager = {
        /**
         * Показать уведомление
         * @param {string} message - Текст сообщения
         * @param {string} type - Тип: 'success', 'error', 'warning', 'info'
         * @param {number} duration - Длительность в мс (по умолчанию 3000)
         */
        show: function (message, type = 'info', duration = 4000) {
            const notification = document.createElement('div');
            notification.className = `toast toast-${type}`;

            // Иконка по типу
            const icons = {
                success: '<i class="bi bi-check-circle"></i>',
                error: '<i class="bi bi-x-circle"></i>',
                warning: '<i class="bi bi-exclamation-triangle"></i>',
                info: '<i class="bi bi-info-circle"></i>'
            };

            notification.innerHTML = `
                <div class="toast-icon">${icons[type] || icons.info}</div>
                <div class="toast-message">${message}</div>
                <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 300)">
                    <i class="bi bi-x"></i>
                </button>
            `;

            let container = document.querySelector('.notification-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notification-container';
                document.body.appendChild(container);
            }

            container.appendChild(notification);

            // Анимация появления
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            // Автоудаление
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        },

        /**
         * Сокращения для типов
         */
        success: function (message, duration) {
            this.show(message, 'success', duration);
        },

        error: function (message, duration) {
            this.show(message, 'error', duration);
        },

        warning: function (message, duration) {
            this.show(message, 'warning', duration);
        },

        info: function (message, duration) {
            this.show(message, 'info', duration);
        }
    };

})();
