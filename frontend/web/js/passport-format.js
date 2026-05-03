/* ==============================================
   PASSPORT FORMAT — single source of truth
   BY combined "Серия+Номер": 2 latin letters + 7 digits (MP1234567)
   RU separate series (4 digits) + number (6 digits)
   Wire via data-format="by-passport" on .form-control inputs.
   ============================================== */
(function (global) {
    'use strict';

    var BY_PATTERN_RE = /^[A-Z]{2}[0-9]{7}$/;
    var BY_HTML_PATTERN = '[A-Z]{2}[0-9]{7}';
    var BY_MAX_LEN = 9;

    /**
     * Format the value of a BY passport input in place.
     * First 2 chars → uppercase A-Z, rest → digits, max 9 chars.
     * Restores caret to the closest valid position.
     */
    function formatBYPassport(input) {
        if (!input) return;
        var pos = input.selectionStart;
        var raw = (input.value || '').toUpperCase();
        var letters = raw.slice(0, 2).replace(/[^A-Z]/g, '');
        var digits = raw.slice(2).replace(/[^0-9]/g, '').slice(0, 7);
        var next = letters + digits;
        if (input.value !== next) {
            input.value = next;
            try {
                var caret = Math.min(pos == null ? next.length : pos, next.length);
                input.setSelectionRange(caret, caret);
            } catch (e) { /* setSelectionRange not supported on some types */ }
        }
    }

    function validateBYPassport(value) {
        return BY_PATTERN_RE.test((value || '').toUpperCase());
    }

    /**
     * Wire a single input as a BY-passport combined field.
     * Sets attributes (maxlength, pattern, autocomplete) and attaches the input listener once.
     */
    function attachBYPassport(input) {
        if (!input || input._byPassportAttached) return;
        input._byPassportAttached = true;
        input.setAttribute('maxlength', String(BY_MAX_LEN));
        input.setAttribute('pattern', BY_HTML_PATTERN);
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('spellcheck', 'false');
        input.setAttribute('inputmode', 'text');
        input.addEventListener('input', function () { formatBYPassport(this); });
        formatBYPassport(input); // normalize any pre-filled server value
    }

    /**
     * Detach BY behaviour: clears the attached flag so a future call re-wires.
     * Kept minimal — listeners stay but become no-ops once `_byPassportAttached`
     * is reset and the citizenship logic clears the value.
     */
    function detachBYPassport(input) {
        if (!input) return;
        input._byPassportAttached = false;
    }

    /**
     * Auto-wire any input matching `[data-format="by-passport"]`.
     * Idempotent — safe to call multiple times.
     */
    function autoWire(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll('[data-format="by-passport"]');
        for (var i = 0; i < nodes.length; i++) attachBYPassport(nodes[i]);
    }

    var api = {
        pattern: BY_PATTERN_RE,
        htmlPattern: BY_HTML_PATTERN,
        maxLength: BY_MAX_LEN,
        format: formatBYPassport,
        validate: validateBYPassport,
        attach: attachBYPassport,
        detach: detachBYPassport,
        autoWire: autoWire
    };

    global.PassportFormat = api;
    // Back-compat: admin view + old inline calls reference this name.
    global.formatBYPassport = formatBYPassport;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { autoWire(); });
    } else {
        autoWire();
    }
})(typeof window !== 'undefined' ? window : this);
