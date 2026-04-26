/**
 * Admin Toast notifications
 * SH.toast({type, title, message, duration, action: {label, onClick}})
 * SH.notify(message, type)  ← backward compat
 */
(function () {
    'use strict';

    var iconMap = {
        success: '✓',
        error:   '✕',
        warning: '⚠',
        info:    'ℹ',
    };
    var colorMap = {
        success: '#059669',
        error:   '#dc2626',
        warning: '#d97706',
        info:    '#2563eb',
    };

    function getContainer() {
        var c = document.getElementById('sh-admin-toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'sh-admin-toast-container';
            c.style.cssText = [
                'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
                'display:flex', 'flex-direction:column', 'gap:10px',
                'max-width:420px', 'pointer-events:none',
            ].join(';');
            document.body.appendChild(c);
        }
        return c;
    }

    function toast(opts, legacyType, legacyDuration) {
        if (typeof opts === 'string') {
            opts = { message: opts, type: legacyType || 'info', duration: legacyDuration };
        }
        var type     = opts.type     || 'info';
        var title    = opts.title    || null;
        var message  = opts.message  || '';
        var duration = opts.duration || 5000;
        var action   = opts.action   || null;
        var color    = colorMap[type] || colorMap.info;
        var icon     = iconMap[type]  || iconMap.info;

        var el = document.createElement('div');
        el.style.cssText = [
            'background:#1f2937', 'color:#f9fafb', 'border-radius:10px',
            'border-left:4px solid ' + color,
            'padding:12px 14px', 'display:flex', 'align-items:flex-start', 'gap:10px',
            'box-shadow:0 4px 16px rgba(0,0,0,.3)',
            'transform:translateX(120%)', 'transition:transform .3s cubic-bezier(.16,1,.3,1)',
            'pointer-events:auto', 'max-width:420px', 'width:100%',
        ].join(';');

        var iconEl = document.createElement('span');
        iconEl.textContent = icon;
        iconEl.style.cssText = 'font-size:1rem;font-weight:700;color:' + color + ';flex-shrink:0;margin-top:1px';

        var bodyEl = document.createElement('div');
        bodyEl.style.cssText = 'flex:1;min-width:0';

        if (title) {
            var titleEl = document.createElement('div');
            titleEl.textContent = title;
            titleEl.style.cssText = 'font-weight:700;font-size:.875rem;margin-bottom:2px';
            bodyEl.appendChild(titleEl);
        }

        var msgEl = document.createElement('div');
        msgEl.textContent = message;
        msgEl.style.cssText = 'font-size:.875rem;line-height:1.4;color:#d1d5db';
        bodyEl.appendChild(msgEl);

        if (action && action.label) {
            var actBtn = document.createElement('button');
            actBtn.textContent = action.label;
            actBtn.style.cssText = [
                'margin-top:6px', 'background:none', 'border:1px solid rgba(255,255,255,.3)',
                'color:#f9fafb', 'border-radius:5px', 'padding:2px 9px', 'font-size:.8rem',
                'font-weight:600', 'cursor:pointer',
            ].join(';');
            actBtn.addEventListener('click', function () {
                if (typeof action.onClick === 'function') action.onClick();
                dismiss();
            });
            bodyEl.appendChild(actBtn);
        }

        var closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = [
            'background:none', 'border:none', 'color:#9ca3af', 'cursor:pointer',
            'font-size:1.1rem', 'line-height:1', 'padding:0', 'flex-shrink:0',
        ].join(';');

        el.appendChild(iconEl);
        el.appendChild(bodyEl);
        el.appendChild(closeBtn);

        var timer;
        function dismiss() {
            clearTimeout(timer);
            el.style.transform = 'translateX(120%)';
            setTimeout(function () { el.remove(); }, 300);
        }
        closeBtn.addEventListener('click', dismiss);

        getContainer().appendChild(el);
        setTimeout(function () { el.style.transform = 'translateX(0)'; }, 10);
        timer = setTimeout(dismiss, duration);
    }

    window.SH = window.SH || {};
    window.SH.toast  = toast;
    window.SH.notify = toast;
})();
