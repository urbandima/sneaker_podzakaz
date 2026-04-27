/**
 * SneakerHead Orders Widget — AmoCRM private widget
 *
 * Architecture: AMD module, constructor function returning `this`.
 * Callbacks: render → init → bind_actions (AmoCRM lifecycle).
 *
 * render()      — sets up sidebar template, injects CSS.
 * init()        — fetches order data from shop API, updates UI.
 * bind_actions()— attaches click handlers for create/sync.
 */
define(['jquery'], function ($) {
    'use strict';

    var SneakerheadWidget = function () {
        var self = this;

        // ── Private state ────────────────────────────────────────────────────
        var _s      = {};   // settings: api_url, api_key
        var _lang   = {};   // i18n strings
        var _leadId = 0;

        // ── Helpers ──────────────────────────────────────────────────────────

        function url()  { return (_s.api_url || '').replace(/\/$/, ''); }
        function key()  { return _s.api_key || ''; }
        function auth() { return { Authorization: 'Bearer ' + key() }; }
        function t(k)   { return _lang[k] || k; }

        /** True when the widget is rendered inside a lead card. */
        function onLeadCard() {
            var area = self.system().area;
            // AmoCRM area names vary by version; cover all known values.
            return area === 'lcard'
                || area === 'lead_card'
                || area === 'lead-card-leads'
                || String(area).indexOf('lead') !== -1;
        }

        /** Resolve current lead ID from APP global or URL. */
        function leadId() {
            if (typeof APP !== 'undefined'
                    && APP.data && APP.data.current_card
                    && APP.data.current_card.id) {
                return APP.data.current_card.id;
            }
            var m = window.location.pathname.match(/\/leads\/detail\/(\d+)/);
            return m ? parseInt(m[1], 10) : 0;
        }

        // ── DOM helpers ──────────────────────────────────────────────────────

        var STATUS = {
            new:           'Новый',
            paid:          'Оплачен',
            processing:    'В обработке',
            ready_to_ship: 'Готов к отправке',
            shipped:       'Отправлен',
            delivered:     'Доставлен',
            completed:     'Выполнен',
            cancelled:     'Отменён',
            refunded:      'Возврат'
        };

        function $body() { return $('#sh-widget-body'); }

        function showLoading() {
            $body().html('<div class="sh-spin">' + t('loading') + '</div>');
        }

        function showError(msg) {
            $body().html('<p class="sh-err">' + (msg || t('error_load')) + '</p>');
        }

        function showNoOrder() {
            $body().html(
                '<p class="sh-empty">' + t('order_not_found') + '</p>' +
                '<div class="sh-row">' +
                    '<button class="button-input" id="sh-create-btn">' + t('create_order') + '</button>' +
                '</div>'
            );
        }

        function showOrder(d) {
            $body().html(
                '<table class="sh-tbl">' +
                    '<tr><td class="sh-lbl">' + t('order_id') + '</td>' +
                        '<td><a href="' + d.admin_url + '" target="_blank" class="sh-a">#' + d.order_id + '</a></td></tr>' +
                    '<tr><td class="sh-lbl">' + t('status') + '</td>' +
                        '<td><span class="sh-pill sh-' + d.status + '">' + (STATUS[d.status] || d.status) + '</span></td></tr>' +
                    '<tr><td class="sh-lbl">' + t('total') + '</td>' +
                        '<td>' + parseFloat(d.total || 0).toFixed(2) + ' BYN</td></tr>' +
                    '<tr><td class="sh-lbl">' + t('client') + '</td>' +
                        '<td>' + (d.recipient_name || '—') + '</td></tr>' +
                    '<tr><td class="sh-lbl">' + t('phone') + '</td>' +
                        '<td>' + (d.phone || '—') + '</td></tr>' +
                '</table>' +
                '<div class="sh-row">' +
                    '<button class="button-input" id="sh-sync-btn">' + t('sync') + '</button>' +
                    '<a href="' + d.admin_url + '" target="_blank" class="button-input button-input_blue">' + t('open_order') + '</a>' +
                '</div>'
            );
        }

        // ── Data fetch ───────────────────────────────────────────────────────

        function loadOrder() {
            if (!_leadId) return;
            showLoading();
            $.ajax({
                url:      url() + '/api/amocrm/order?external_id=' + _leadId,
                method:   'GET',
                headers:  auth(),
                dataType: 'json',
                success: function (d) {
                    if (d.found) showOrder(d);
                    else showNoOrder();
                },
                error: function () { showError(); }
            });
        }

        // ── CSS (injected once into <head>) ──────────────────────────────────

        var CSS = [
            '#sh-widget-body{font-size:13px;line-height:1.5;padding:2px 0}',
            '.sh-tbl{width:100%;border-collapse:collapse;margin-bottom:8px}',
            '.sh-tbl td{padding:3px 4px;vertical-align:top}',
            '.sh-lbl{color:#9b9b9b;width:88px;white-space:nowrap}',
            '.sh-a{color:#1f7ae0;text-decoration:none}',
            '.sh-a:hover{text-decoration:underline}',
            '.sh-pill{display:inline-block;padding:1px 7px;border-radius:8px;font-size:11px;font-weight:600}',
            '.sh-new{background:#dbeafe;color:#1e40af}',
            '.sh-paid,.sh-completed{background:#dcfce7;color:#166534}',
            '.sh-processing,.sh-ready_to_ship{background:#fef9c3;color:#854d0e}',
            '.sh-shipped{background:#e0f2fe;color:#075985}',
            '.sh-delivered{background:#d1fae5;color:#065f46}',
            '.sh-cancelled,.sh-refunded{background:#fee2e2;color:#991b1b}',
            '.sh-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}',
            '.sh-empty,.sh-spin{color:#9b9b9b;font-size:12px;padding:6px 0}',
            '.sh-err{color:#e53e3e;font-size:12px;padding:6px 0}',
            '.sh-ok{color:#16a34a;font-size:12px;padding:6px 0}'
        ].join('');

        // ── AmoCRM callbacks ─────────────────────────────────────────────────

        this.callbacks = {

            /**
             * render() — called ONCE when the widget first loads.
             * Inject CSS, create sidebar placeholder via render_template().
             */
            render: function () {
                _s    = self.get_settings();
                _lang = self.i18n('widget') || {};

                // Inject stylesheet once
                var cssId = 'sh-widget-css';
                if (!document.getElementById(cssId)) {
                    var style = document.createElement('style');
                    style.id = cssId;
                    style.textContent = CSS;
                    document.head.appendChild(style);
                }

                // Place widget block in right sidebar
                self.render_template({
                    caption: { class_name: 'sneakerhead_orders' },
                    body:    '',
                    render:  '<div id="sh-widget-body"><div class="sh-spin">' + t('loading') + '</div></div>'
                }, { w_code: _s.widget_code });

                return true;
            },

            /**
             * init() — called each time the user navigates to a page that
             * includes this widget. Fetch order data and update the placeholder.
             */
            init: function () {
                if (!onLeadCard()) return true;

                _leadId = leadId();
                loadOrder();
                return true;
            },

            /**
             * bind_actions() — attach DOM event handlers.
             * Uses $(document).on() so handlers survive re-renders.
             */
            bind_actions: function () {

                // "Создать заказ"
                $(document).on('click', '#sh-create-btn', function () {
                    var $btn = $(this).prop('disabled', true).text(t('creating'));

                    $.ajax({
                        url:         url() + '/api/amocrm/create-order',
                        method:      'POST',
                        contentType: 'application/json',
                        headers:     auth(),
                        data:        JSON.stringify({ lead_id: _leadId }),
                        dataType:    'json',
                        success: function (r) {
                            if (r.success) {
                                $body().html(
                                    '<p class="sh-ok">' + t('order_created') + ' ' +
                                    '<a href="' + (r.admin_url || '#') + '" target="_blank" class="sh-a">#' + r.order_number + '</a></p>'
                                );
                            } else {
                                $btn.prop('disabled', false).text(t('create_order'));
                                $body().prepend('<p class="sh-err">' + (r.message || t('create_error')) + '</p>');
                            }
                        },
                        error: function () {
                            $btn.prop('disabled', false).text(t('create_order'));
                            $body().prepend('<p class="sh-err">' + t('create_error') + '</p>');
                        }
                    });
                });

                // "Синхронизировать"
                $(document).on('click', '#sh-sync-btn', function () {
                    var $btn = $(this).prop('disabled', true).text('...');

                    $.ajax({
                        url:         url() + '/api/amocrm/sync',
                        method:      'POST',
                        contentType: 'application/json',
                        headers:     auth(),
                        data:        JSON.stringify({ lead_id: _leadId }),
                        dataType:    'json',
                        success: function (r) {
                            $btn.prop('disabled', false)
                                .text(r.success ? t('sync_ok') : t('sync_error'));
                        },
                        error: function () {
                            $btn.prop('disabled', false).text(t('sync_error'));
                        }
                    });
                });

                return true;
            },

            /**
             * settings() — called when user opens widget settings modal.
             * AmoCRM auto-renders api_url / api_key from manifest.json.
             */
            settings: function ($modal_body) {
                return true;
            },

            /**
             * onSave() — called when settings are saved.
             * Return true to let AmoCRM persist the values.
             */
            onSave: function () {
                return true;
            },

            /** destroy() — cleanup before widget removal. */
            destroy: function () {
                $(document).off('click', '#sh-create-btn');
                $(document).off('click', '#sh-sync-btn');
                var style = document.getElementById('sh-widget-css');
                if (style) style.parentNode.removeChild(style);
            },

            // Mass-action callbacks (not used, but must be present)
            contacts: { selected: function () {} },
            leads:    { selected: function () {} },
            todo:     { selected: function () {} }
        };

        return this;
    };

    return SneakerheadWidget;
});
