/**
 * Глобальная система уведомлений
 * API: SH.toast({type, title, message, duration, action: {label, onClick}})
 *      SH.notify(message, type, duration)  ← обратная совместимость
 */

(function () {
    'use strict';

    var iconMap = {
        success: 'bi-check-circle-fill',
        error:   'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill'
    };

    function getContainer() {
        var c = document.querySelector('.notification-container');
        if (!c) {
            c = document.createElement('div');
            c.className = 'notification-container';
            document.body.appendChild(c);
        }
        return c;
    }

    /**
     * Показать тост
     * @param {Object|string} opts  объект {type,title,message,duration,action} или строка
     * @param {string}  [legacyType]    обратная совместимость: тип если opts — строка
     * @param {number}  [legacyDuration]
     */
    function toast(opts, legacyType, legacyDuration) {
        if (typeof opts === 'string') {
            opts = { message: opts, type: legacyType || 'info', duration: legacyDuration };
        }

        var type     = opts.type     || 'info';
        var title    = opts.title    || null;
        var message  = opts.message  || '';
        var duration = opts.duration || 4500;
        var action   = opts.action   || null;

        var validTypes = ['success', 'error', 'warning', 'info'];
        if (validTypes.indexOf(type) === -1) type = 'info';

        var el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.setAttribute('role', 'alert');

        var iconEl = document.createElement('div');
        iconEl.className = 'toast-icon';
        var iconI = document.createElement('i');
        iconI.className = 'bi ' + (iconMap[type] || 'bi-info-circle-fill');
        iconEl.appendChild(iconI);

        var bodyEl = document.createElement('div');
        bodyEl.className = 'toast-body';

        if (title) {
            var titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.textContent = title;
            bodyEl.appendChild(titleEl);
        }

        var msgEl = document.createElement('div');
        msgEl.className = 'toast-message';
        msgEl.textContent = message;
        bodyEl.appendChild(msgEl);

        if (action && action.label) {
            var actBtn = document.createElement('button');
            actBtn.className = 'toast-action';
            actBtn.textContent = action.label;
            actBtn.addEventListener('click', function () {
                if (typeof action.onClick === 'function') action.onClick();
                dismiss();
            });
            bodyEl.appendChild(actBtn);
        }

        var closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Закрыть');
        var closeI = document.createElement('i');
        closeI.className = 'bi bi-x';
        closeBtn.appendChild(closeI);

        el.appendChild(iconEl);
        el.appendChild(bodyEl);
        el.appendChild(closeBtn);

        var timer;
        function dismiss() {
            clearTimeout(timer);
            el.classList.remove('show');
            setTimeout(function () { el.remove(); }, 300);
        }

        closeBtn.addEventListener('click', dismiss);

        getContainer().appendChild(el);
        setTimeout(function () { el.classList.add('show'); }, 10);
        timer = setTimeout(dismiss, duration);
    }

    window.NotificationManager = {
        show: function(message, type, duration) { toast(message, type, duration); },
        success: function(m, d) { toast({type:'success', message: m, duration: d}); },
        error:   function(m, d) { toast({type:'error',   message: m, duration: d}); },
        warning: function(m, d) { toast({type:'warning', message: m, duration: d}); },
        info:    function(m, d) { toast({type:'info',    message: m, duration: d}); },
    };

    window.SH = window.SH || {};
    window.SH.toast = toast;

})();
