/**
 * SneakerHead × AmoCRM Widget
 *
 * Встраиваемый виджет для создания заказов прямо из интерфейса AmoCRM.
 *
 * Конфигурация (задаётся при встраивании):
 *   window.sneakerheadWidget = { apiUrl: '...', apiKey: '...' }
 */
(function () {
    'use strict';

    var cfg = window.sneakerheadWidget || {};
    var API_URL = (cfg.apiUrl || '').replace(/\/$/, '');
    var API_KEY = cfg.apiKey || '';

    if (!API_URL || !API_KEY) {
        console.warn('[SneakerHead Widget] apiUrl и apiKey не заданы');
        return;
    }

    // ----------------------------------------------------------------
    // CSS
    // ----------------------------------------------------------------
    var CSS = [
        '.sh-widget{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;',
        'max-width:480px;margin:0 auto;padding:16px;box-sizing:border-box;}',
        '.sh-widget *{box-sizing:border-box;}',
        '.sh-widget h2{font-size:17px;font-weight:600;margin:0 0 14px;color:#1a1a1a;}',
        '.sh-field{margin-bottom:12px;}',
        '.sh-field label{display:block;font-size:12px;font-weight:500;color:#555;margin-bottom:4px;}',
        '.sh-field input,.sh-field select,.sh-field textarea{',
        '  width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;',
        '  font-size:14px;outline:none;transition:border .15s;}',
        '.sh-field input:focus,.sh-field select:focus,.sh-field textarea:focus{border-color:#6366f1;}',
        '.sh-field textarea{resize:vertical;min-height:60px;}',
        '.sh-row{display:flex;gap:10px;}',
        '.sh-row .sh-field{flex:1;}',
        '.sh-ac-wrap{position:relative;}',
        '.sh-ac-list{position:absolute;top:100%;left:0;right:0;background:#fff;',
        '  border:1px solid #d1d5db;border-top:none;border-radius:0 0 6px 6px;',
        '  max-height:200px;overflow-y:auto;z-index:9999;display:none;}',
        '.sh-ac-item{padding:8px 10px;cursor:pointer;font-size:13px;color:#1a1a1a;}',
        '.sh-ac-item:hover,.sh-ac-item.active{background:#f3f4f6;}',
        '.sh-ac-item span{font-size:11px;color:#888;margin-left:6px;}',
        '.sh-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;',
        '  border:none;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer;transition:.15s;}',
        '.sh-btn-primary{background:#6366f1;color:#fff;}',
        '.sh-btn-primary:hover{background:#4f46e5;}',
        '.sh-btn-primary:disabled{background:#a5b4fc;cursor:not-allowed;}',
        '.sh-msg{margin-top:12px;padding:10px 14px;border-radius:6px;font-size:13px;display:none;}',
        '.sh-msg.ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}',
        '.sh-msg.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}',
        '.sh-spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);',
        '  border-top-color:#fff;border-radius:50%;animation:sh-spin .6s linear infinite;}',
        '@keyframes sh-spin{to{transform:rotate(360deg)}}'
    ].join('');

    var style = document.createElement('style');
    style.textContent = CSS;
    document.head.appendChild(style);

    // ----------------------------------------------------------------
    // HTML
    // ----------------------------------------------------------------
    function render(container, dealId, dealLink) {
        container.innerHTML = [
            '<div class="sh-widget">',
            '  <h2>&#128197; Создать заказ</h2>',
            '  <div class="sh-row">',
            '    <div class="sh-field">',
            '      <label>Имя клиента *</label>',
            '      <input id="sh-name" type="text" placeholder="Иван Иванов">',
            '    </div>',
            '    <div class="sh-field">',
            '      <label>Телефон *</label>',
            '      <input id="sh-phone" type="tel" placeholder="+375291234567">',
            '    </div>',
            '  </div>',
            '  <div class="sh-field">',
            '    <label>Email</label>',
            '    <input id="sh-email" type="email" placeholder="client@example.com">',
            '  </div>',
            '  <div class="sh-field">',
            '    <label>Товар</label>',
            '    <div class="sh-ac-wrap">',
            '      <input id="sh-product" type="text" placeholder="Начните вводить название..." autocomplete="off">',
            '      <div class="sh-ac-list" id="sh-ac-list"></div>',
            '    </div>',
            '  </div>',
            '  <div class="sh-row">',
            '    <div class="sh-field">',
            '      <label>Размер</label>',
            '      <select id="sh-size"><option value="">— выберите —</option></select>',
            '    </div>',
            '    <div class="sh-field">',
            '      <label>Цена (BYN)</label>',
            '      <input id="sh-price" type="number" min="0" step="0.01" placeholder="0.00">',
            '    </div>',
            '  </div>',
            '  <div class="sh-field">',
            '    <label>Примечание</label>',
            '    <textarea id="sh-notes" placeholder="Доп. информация по заказу..."></textarea>',
            '  </div>',
            '  <button class="sh-btn sh-btn-primary" id="sh-submit">',
            '    Создать заказ',
            '  </button>',
            '  <div class="sh-msg" id="sh-msg"></div>',
            '</div>'
        ].join('');

        bindEvents(container, dealId, dealLink);
    }

    // ----------------------------------------------------------------
    // Autocomplete
    // ----------------------------------------------------------------
    var acTimer = null;
    var acItems = [];
    var acSelected = null;

    function bindEvents(container, dealId, dealLink) {
        var inp      = container.querySelector('#sh-product');
        var list     = container.querySelector('#sh-ac-list');
        var sizeEl   = container.querySelector('#sh-size');
        var priceEl  = container.querySelector('#sh-price');
        var submitEl = container.querySelector('#sh-submit');
        var msgEl    = container.querySelector('#sh-msg');

        inp.addEventListener('input', function () {
            clearTimeout(acTimer);
            var q = inp.value.trim();
            if (q.length < 2) { hideList(list); return; }
            acTimer = setTimeout(function () { fetchProducts(q, list, sizeEl, priceEl); }, 300);
        });

        inp.addEventListener('blur', function () {
            setTimeout(function () { hideList(list); }, 200);
        });

        submitEl.addEventListener('click', function () {
            submitOrder(container, dealId, dealLink, submitEl, msgEl);
        });
    }

    function fetchProducts(q, list, sizeEl, priceEl) {
        fetch(API_URL + '/api/amocrm/products?q=' + encodeURIComponent(q), {
            headers: { 'X-Api-Key': API_KEY }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            acItems = data;
            showList(list, data, sizeEl, priceEl);
        })
        .catch(function () {});
    }

    function showList(list, items, sizeEl, priceEl) {
        list.innerHTML = '';
        if (!items.length) { hideList(list); return; }

        items.forEach(function (p, i) {
            var el = document.createElement('div');
            el.className = 'sh-ac-item';
            el.innerHTML = escHtml(p.name) + (p.article ? '<span>' + escHtml(p.article) + '</span>' : '');
            el.addEventListener('mousedown', function () {
                selectProduct(p, sizeEl, priceEl, list);
            });
            list.appendChild(el);
        });

        list.style.display = 'block';
    }

    function hideList(list) {
        list.style.display = 'none';
    }

    function selectProduct(p, sizeEl, priceEl, list) {
        var inp = document.getElementById('sh-product');
        if (inp) inp.value = p.name;
        acSelected = p;
        hideList(list);

        // Заполнить размеры
        sizeEl.innerHTML = '<option value="">— выберите —</option>';
        if (p.sizes && p.sizes.length) {
            p.sizes.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.size;
                opt.textContent = s.size;
                opt.dataset.price = s.price;
                sizeEl.appendChild(opt);
            });
        }

        priceEl.value = p.price || '';

        sizeEl.onchange = function () {
            var opt = sizeEl.options[sizeEl.selectedIndex];
            if (opt && opt.dataset.price) priceEl.value = opt.dataset.price;
        };
    }

    // ----------------------------------------------------------------
    // Submit
    // ----------------------------------------------------------------
    function submitOrder(container, dealId, dealLink, btn, msgEl) {
        var name  = val(container, '#sh-name');
        var phone = val(container, '#sh-phone');
        var email = val(container, '#sh-email');
        var prod  = val(container, '#sh-product');
        var size  = val(container, '#sh-size');
        var price = parseFloat(val(container, '#sh-price')) || 0;
        var notes = val(container, '#sh-notes');

        if (!name || !phone) {
            showMsg(msgEl, 'err', 'Заполните имя и телефон.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="sh-spinner"></span> Отправка...';

        var payload = {
            name:         name,
            phone:        phone,
            email:        email,
            product_name: prod,
            size:         size,
            price:        price,
            notes:        notes,
            source:       'amocrm',
            deal_id:      dealId || '',
            deal_link:    dealLink || ''
        };

        fetch(API_URL + '/api/amocrm/create-order', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-Api-Key': API_KEY },
            body:    JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                showMsg(msgEl, 'ok',
                    '&#10003; Заказ #' + d.order_number + ' создан. ' +
                    '<a href="' + escHtml(d.admin_url) + '" target="_blank">Открыть в системе</a>');
                resetForm(container);
            } else {
                showMsg(msgEl, 'err', d.message || 'Ошибка при создании заказа.');
            }
        })
        .catch(function () {
            showMsg(msgEl, 'err', 'Ошибка сети. Попробуйте ещё раз.');
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = 'Создать заказ';
        });
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    function val(container, sel) {
        var el = container.querySelector(sel);
        return el ? el.value.trim() : '';
    }

    function showMsg(el, cls, html) {
        el.className = 'sh-msg ' + cls;
        el.innerHTML = html;
        el.style.display = 'block';
    }

    function resetForm(container) {
        ['#sh-name','#sh-phone','#sh-email','#sh-product','#sh-notes'].forEach(function (s) {
            var el = container.querySelector(s);
            if (el) el.value = '';
        });
        var size = container.querySelector('#sh-size');
        if (size) size.innerHTML = '<option value="">— выберите —</option>';
        var price = container.querySelector('#sh-price');
        if (price) price.value = '';
        acSelected = null;
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ----------------------------------------------------------------
    // AmoCRM integration entry point
    // ----------------------------------------------------------------
    function getDealInfo() {
        var dealId   = '';
        var dealLink = '';
        try {
            // AmoCRM custom widget context
            if (window.AMOCRM && window.AMOCRM.data && window.AMOCRM.data.current_entity) {
                dealId   = window.AMOCRM.data.current_entity.id || '';
                dealLink = window.location.href;
            }
        } catch (e) {}
        return { dealId: String(dealId), dealLink: dealLink };
    }

    function init() {
        var containers = document.querySelectorAll('[data-sneakerhead-widget]');
        if (!containers.length) {
            // Авто-вставка в тело страницы если контейнер не объявлен явно
            var div = document.createElement('div');
            div.setAttribute('data-sneakerhead-widget', '1');
            document.body.appendChild(div);
            containers = [div];
        }

        var info = getDealInfo();

        for (var i = 0; i < containers.length; i++) {
            render(containers[i], info.dealId, info.dealLink);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Экспортируем для ручного вызова
    window.sneakerheadWidgetInit = init;
})();
