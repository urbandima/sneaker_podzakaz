/**
 * Глобальная система уведомлений
 * B&W Design System
 */

(function () {
    'use strict';

    var escapeHtml = SH.escapeHtml;

    window.NotificationManager = {
        /**
         * Показать уведомление
         * @param {string} message - Текст сообщения
         * @param {string} type - Тип: 'success', 'error', 'warning', 'info'
         * @param {number} duration - Длительность в мс (по умолчанию 4000)
         */
        show: function (message, type, duration) {
            type = type || 'info';
            duration = duration || 4000;

            // Валидация типа
            var validTypes = ['success', 'error', 'warning', 'info'];
            if (validTypes.indexOf(type) === -1) type = 'info';

            var notification = document.createElement('div');
            notification.className = 'toast toast-' + type;

            // Иконка (безопасный HTML — нет пользовательских данных)
            var iconMap = {
                success: 'bi-check-circle',
                error: 'bi-x-circle',
                warning: 'bi-exclamation-triangle',
                info: 'bi-info-circle'
            };

            var iconEl = document.createElement('div');
            iconEl.className = 'toast-icon';
            var icon = document.createElement('i');
            icon.className = 'bi ' + iconMap[type];
            iconEl.appendChild(icon);

            var messageEl = document.createElement('div');
            messageEl.className = 'toast-message';
            messageEl.textContent = message; // textContent — XSS-безопасно

            var closeBtn = document.createElement('button');
            closeBtn.className = 'toast-close';
            closeBtn.setAttribute('aria-label', 'Закрыть');
            var closeIcon = document.createElement('i');
            closeIcon.className = 'bi bi-x';
            closeBtn.appendChild(closeIcon);
            closeBtn.addEventListener('click', function () {
                notification.classList.remove('show');
                setTimeout(function () { notification.remove(); }, 300);
            });

            notification.appendChild(iconEl);
            notification.appendChild(messageEl);
            notification.appendChild(closeBtn);

            var container = document.querySelector('.notification-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notification-container';
                document.body.appendChild(container);
            }

            container.appendChild(notification);

            setTimeout(function () {
                notification.classList.add('show');
            }, 10);

            setTimeout(function () {
                notification.classList.remove('show');
                setTimeout(function () { notification.remove(); }, 300);
            }, duration);
        },

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
