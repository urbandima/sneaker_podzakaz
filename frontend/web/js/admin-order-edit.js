/**
 * admin-order-edit.js
 * Inline-editable fields for /admin/order/:id
 *
 * Usage: place data-editable="<field_name>" on any element.
 * Additional attributes:
 *   data-type="text|number|textarea|select|date"  (default: text)
 *   data-options='[{"value":"v","label":"l"},...]' (required for type=select)
 *   data-order-id="<id>"                           (read from closest [data-order-id] or window.INLINE_ORDER_ID)
 *   data-update-url="/admin/order/update-field"    (override endpoint)
 */
(function () {
    'use strict';

    var DEBOUNCE_MS  = 400;
    var CHECK_MS     = 1000; // green checkmark duration

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getCsrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function debounce(fn, ms) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function formatValue(val) {
        if (val === null || val === undefined) return '';
        return String(val);
    }

    // ── Conflict modal ────────────────────────────────────────────────────────

    function showConflictModal(info, onOverwrite, onCancel) {
        var existing = document.getElementById('ie-conflict-modal');
        if (existing) existing.remove();

        var modal = document.createElement('div');
        modal.id = 'ie-conflict-modal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center';
        modal.innerHTML =
            '<div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25)">' +
            '  <h3 style="margin:0 0 12px;font-size:1rem;color:#111">Конфликт редактирования</h3>' +
            '  <p style="margin:0 0 18px;font-size:.875rem;color:#374151">Поле <strong>' + (info.field_label || info.field) + '</strong> было изменено пользователем <strong>' + (info.updated_by || '?') + '</strong> пока вы редактировали.</p>' +
            '  <p style="margin:0 0 20px;font-size:.875rem;color:#374151">Текущее значение в базе: <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px">' + formatValue(info.current_value) + '</code></p>' +
            '  <div style="display:flex;gap:10px;justify-content:flex-end">' +
            '    <button id="ie-conflict-cancel" style="padding:8px 18px;border:1px solid #d1d5db;border-radius:7px;background:#fff;cursor:pointer;font-size:.875rem">Отменить</button>' +
            '    <button id="ie-conflict-overwrite" style="padding:8px 18px;border:none;border-radius:7px;background:#dc2626;color:#fff;cursor:pointer;font-size:.875rem">Перезаписать</button>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(modal);

        document.getElementById('ie-conflict-cancel').onclick = function () {
            modal.remove();
            if (onCancel) onCancel(info.current_value);
        };
        document.getElementById('ie-conflict-overwrite').onclick = function () {
            modal.remove();
            if (onOverwrite) onOverwrite();
        };
    }

    // ── InlineEdit class ──────────────────────────────────────────────────────

    function InlineEdit(el) {
        if (!el || el._inlineEdit) return;
        el._inlineEdit = this;

        this.el      = el;
        this.field   = el.dataset.editable;
        this.type    = el.dataset.type || 'text';
        this.options = el.dataset.options ? JSON.parse(el.dataset.options) : [];
        this.url     = el.dataset.updateUrl || (window.INLINE_UPDATE_URL || '/admin/order/update-field');
        this.orderId = el.dataset.orderId || (function () {
            var p = el.closest('[data-order-id]');
            return p ? p.dataset.orderId : (window.INLINE_ORDER_ID || '');
        }());

        this._originalText = el.textContent.trim();
        this._state        = 'idle';   // idle | editing | saving | saved
        this._input        = null;
        this._lastUpdatedAt = window.INLINE_ORDER_UPDATED_AT || null;

        this._bindIdle();
    }

    InlineEdit.prototype._bindIdle = function () {
        var self = this;
        this.el.style.cursor = 'text';
        this.el.title        = 'Нажмите для редактирования';
        this.el.addEventListener('click', function (e) {
            if (self._state !== 'idle') return;
            e.stopPropagation();
            self._enterEditing();
        });
    };

    InlineEdit.prototype._enterEditing = function () {
        if (this._state !== 'idle') return;
        this._state = 'editing';
        this.el.classList.add('ie-editing');

        var rawValue = this.el.dataset.value !== undefined
            ? this.el.dataset.value
            : this.el.textContent.trim();

        var input = this._buildInput(rawValue);
        this._input = input;

        // Replace display text with input
        this.el.innerHTML = '';
        this.el.appendChild(input);
        this._addIndicator();

        var self = this;

        if (this.type === 'select') {
            input.addEventListener('change', function () { self._save(input.value); });
        } else if (this.type === 'textarea') {
            input.addEventListener('blur', function () { self._save(input.value); });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') self._cancel();
            });
        } else {
            var debouncedSave = debounce(function () { self._save(input.value); }, DEBOUNCE_MS);
            input.addEventListener('input', debouncedSave);
            input.addEventListener('blur', function () {
                clearTimeout(self._debounceTimer);
                self._save(input.value);
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter')  { e.preventDefault(); self._save(input.value); }
                if (e.key === 'Escape') { e.preventDefault(); self._cancel(); }
            });
        }

        // Defer focus so the click event fully settles
        setTimeout(function () {
            input.focus();
            if (input.select) input.select();
        }, 0);
    };

    InlineEdit.prototype._buildInput = function (value) {
        var input;
        if (this.type === 'textarea') {
            input = document.createElement('textarea');
            input.className = 'ie-input ie-textarea';
            input.rows = 3;
            input.value = value;
        } else if (this.type === 'select') {
            input = document.createElement('select');
            input.className = 'ie-input ie-select';
            this.options.forEach(function (opt) {
                var o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.label;
                if (String(opt.value) === String(value)) o.selected = true;
                input.appendChild(o);
            });
        } else {
            input = document.createElement('input');
            input.className = 'ie-input';
            input.type = this.type === 'number' ? 'number' : (this.type === 'date' ? 'date' : 'text');
            if (this.type === 'number') input.step = '0.01';
            input.value = value;
        }
        return input;
    };

    InlineEdit.prototype._addIndicator = function () {
        var ind = document.createElement('span');
        ind.className = 'ie-indicator';
        ind.textContent = '';
        this.el.appendChild(ind);
        this._indicator = ind;
    };

    InlineEdit.prototype._setState = function (state, text) {
        this._state = state;
        if (!this._indicator) return;
        var c = this._indicator;
        c.className = 'ie-indicator ie-indicator--' + state;
        c.textContent = text || '';
    };

    InlineEdit.prototype._cancel = function () {
        this._state = 'idle';
        this.el.classList.remove('ie-editing');
        this.el.innerHTML = '';
        this.el.textContent = this._originalText;
        this._input     = null;
        this._indicator = null;
        this._bindIdle();
    };

    InlineEdit.prototype._revertTo = function (value) {
        this._state = 'idle';
        this.el.classList.remove('ie-editing');
        this.el.innerHTML = '';
        this.el.textContent = formatValue(value);
        this.el.dataset.value = value;
        this._originalText = formatValue(value);
        this._input     = null;
        this._indicator = null;
        this._bindIdle();
    };

    InlineEdit.prototype._save = function (value, force) {
        // Prevent double-save if already saving
        if (this._state === 'saving') return;
        this._setState('saving', '');

        var self    = this;
        var payload = {
            id:    parseInt(this.orderId, 10),
            field: this.field,
            value: value,
        };
        if (!force && this._lastUpdatedAt) {
            payload.client_updated_at = this._lastUpdatedAt;
        }

        fetch(this.url, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrf(),
            },
            body: JSON.stringify(payload),
        })
        .then(function (r) {
            if (r.status === 409) {
                return r.json().then(function (d) { d.__status = 409; return d; });
            }
            return r.json();
        })
        .then(function (d) {
            if (d.__status === 409) {
                // Conflict — show modal
                self._state = 'editing'; // allow overwrite retry
                d.field_label = d.field_label || d.field;
                showConflictModal(d, function () {
                    // Overwrite: re-save without conflict check
                    self._save(value, true);
                }, function (serverValue) {
                    // Cancel: revert to server value
                    self._revertTo(serverValue);
                });
                return;
            }

            if (d.ok) {
                // Update cached state
                self._originalText = formatValue(d.value);
                self.el.dataset.value = d.value;
                if (d.history) {
                    self._lastUpdatedAt = d.history.timestamp;
                    if (window.INLINE_ORDER_UPDATED_AT !== undefined) {
                        window.INLINE_ORDER_UPDATED_AT = d.history.timestamp;
                    }
                    // Prepend to history list
                    OrderHistoryUI.prepend(d.history);
                }

                // Transition: saving → saved → idle
                self._setState('saved', '✓');
                self.el.classList.remove('ie-editing');
                self.el.classList.add('ie-saved');

                // After CHECK_MS show final value and go idle
                setTimeout(function () {
                    self.el.classList.remove('ie-saved');
                    self._state = 'idle';
                    self.el.innerHTML = '';
                    self.el.textContent = self._originalText;
                    self._input     = null;
                    self._indicator = null;
                    self._bindIdle();
                    // Mark page title as saved
                    PageSaveState.setSaved();
                }, CHECK_MS);
            } else {
                // Server validation error — show inline
                self._setState('error', '');
                var errSpan = document.createElement('span');
                errSpan.className = 'ie-error';
                errSpan.textContent = d.error || 'Ошибка';
                // Insert error below input
                var existing = self.el.querySelector('.ie-error');
                if (existing) existing.remove();
                self.el.appendChild(errSpan);
                self._state = 'editing'; // allow correction
                // Show toast
                SH && SH.notify && SH.notify(d.error || 'Ошибка сохранения', 'error');
            }
        })
        .catch(function (err) {
            self._setState('error', '');
            self._state = 'editing';
            SH && SH.notify && SH.notify('Ошибка сети: ' + err.message, 'error');
            PageSaveState.setUnsaved();
        });
    };

    // ── Page save state (title indicator) ────────────────────────────────────

    var PageSaveState = {
        _origTitle: document.title,
        setSaved: function () {
            document.title = this._origTitle;
        },
        setUnsaved: function () {
            if (document.title.indexOf('●') === -1) {
                document.title = '● ' + this._origTitle;
            }
        },
    };

    // ── History UI ────────────────────────────────────────────────────────────

    var OrderHistoryUI = {
        _list: null,
        _total: 0,
        _page: 1,
        _orderId: null,

        init: function (orderId) {
            this._orderId = orderId;
            this._list = document.getElementById('ie-history-list');
            var btn = document.getElementById('ie-history-load-more');
            if (btn) {
                btn.addEventListener('click', function () {
                    OrderHistoryUI._page++;
                    OrderHistoryUI.load(OrderHistoryUI._page);
                });
            }
            this.load(1);
        },

        load: function (page) {
            if (!this._list) return;
            var self = this;
            fetch('/admin/order/history', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() },
                body:    JSON.stringify({ id: this._orderId, page: page }),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) return;
                d.items.forEach(function (item) {
                    self._list.appendChild(self._buildRow(item));
                });
                self._total = d.total;
                var btn = document.getElementById('ie-history-load-more');
                if (btn) {
                    var shown = page * d.limit;
                    btn.style.display = shown >= d.total ? 'none' : '';
                    btn.textContent = 'Показать ещё (' + (d.total - shown) + ')';
                }
            })
            .catch(function () {});
        },

        prepend: function (entry) {
            if (!this._list) return;
            var row = this._buildRow(entry);
            this._list.insertBefore(row, this._list.firstChild);
            this._total++;
        },

        _buildRow: function (item) {
            var row = document.createElement('div');
            row.className = 'ie-history-row';

            var from = this._valueHtml(item.field, item.from);
            var to   = this._valueHtml(item.field, item.to);
            var isNote = item.comment && item.from === item.to;
            var changeHtml = isNote ? '' :
                '<div class="ie-history-change">' +
                '  <span class="ie-history-field">' + this._esc(item.field_label) + '</span>: ' +
                '  ' + from + ' → ' + to +
                '</div>';

            row.innerHTML =
                '<div class="ie-history-meta">' +
                '  <time class="ie-history-ts">' + item.timestamp_f + '</time>' +
                '  <span class="ie-history-user">' + this._esc(item.user) + '</span>' +
                (isNote ? '  <span class="ie-history-note-badge">заметка</span>' : '') +
                '</div>' +
                changeHtml +
                (item.comment ? '<div class="ie-history-comment">' + this._esc(item.comment) + '</div>' : '');

            return row;
        },

        _valueHtml: function (field, val) {
            if (!val && val !== 0) return '<em class="ie-history-empty">—</em>';
            if (field === 'status') {
                return '<span class="ie-status-badge ie-status-' + val + '">' + this._esc(val) + '</span>';
            }
            return '<code class="ie-history-val">' + this._esc(String(val)) + '</code>';
        },

        _esc: function (s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
    };

    // ── Boot ──────────────────────────────────────────────────────────────────

    function boot() {
        document.querySelectorAll('[data-editable]').forEach(function (el) {
            new InlineEdit(el);
        });

        var orderId = window.INLINE_ORDER_ID;
        if (orderId) {
            OrderHistoryUI.init(orderId);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Expose for external use
    window.InlineEdit       = InlineEdit;
    window.OrderHistoryUI   = OrderHistoryUI;
}());
