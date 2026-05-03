<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $order */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="content-card passport-card" id="passport-form-section">
    <h2><i class="bi bi-person-vcard"></i> Паспортные данные</h2>
    <p class="passport-hint">
        Для отправки вашего заказа через Таможня:ДП необходимо предоставить паспортные данные.
        Данные передаются в зашифрованном виде и используются только для таможенного оформления.
    </p>

    <div id="passport-form-messages"></div>

    <form id="passport-form" class="passport-form" novalidate>
        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">

        <!-- ── Step 1 — ФИО + дата рождения ── -->
        <div class="form-step">
            <span class="form-step__counter">1</span>
            <span class="form-step__title">ФИО и дата рождения</span>
            <span class="form-step__hint">Шаг 1 из 3</span>
        </div>

        <div class="passport-row">
            <div class="passport-field">
                <label>Фамилия <span class="req">*</span></label>
                <input type="text" id="pf_last_name" name="recipient_last_name"
                       value="<?= Html::encode($order->recipient_last_name ?? '') ?>"
                       maxlength="100" required placeholder="ИВАНОВ">
                <span class="field-error" id="err_last_name"></span>
            </div>
            <div class="passport-field">
                <label>Имя <span class="req">*</span></label>
                <input type="text" id="pf_first_name" name="recipient_first_name"
                       value="<?= Html::encode($order->recipient_first_name ?? '') ?>"
                       maxlength="100" required placeholder="ИВАН">
                <span class="field-error" id="err_first_name"></span>
            </div>
            <div class="passport-field">
                <label>Отчество</label>
                <input type="text" id="pf_middle_name" name="recipient_middle_name"
                       value="<?= Html::encode($order->recipient_middle_name ?? '') ?>"
                       maxlength="100" placeholder="ИВАНОВИЧ">
            </div>
        </div>

        <div class="passport-row">
            <div class="passport-field">
                <label>Дата рождения <span class="req">*</span></label>
                <input type="date" id="pf_birth_date" name="birth_date"
                       value="<?= Html::encode($order->birth_date ?? '') ?>"
                       required>
                <span class="field-error" id="err_birth_date"></span>
            </div>
        </div>

        <!-- ── Step 2 — Паспорт ── -->
        <div class="form-step">
            <span class="form-step__counter">2</span>
            <span class="form-step__title">Паспорт</span>
            <span class="form-step__hint">Шаг 2 из 3</span>
        </div>

        <div class="passport-row">
            <!-- BY combined Серия+Номер via canonical partial -->
            <?= $this->render('../partials/_passport_field', [
                'id'          => 'pf_passport_series',
                'name'        => 'passport_series',
                'value'       => $order->passport_series ?? '',
                'errorId'     => 'err_passport_series',
                'required'    => true,
                'labelClass'  => 'passport-field passport-field--wide',
                'inputClass'  => 'passport-input--mono',
                'errorClass'  => 'field-error',
                'hintClass'   => '',
            ]) ?>
            <div class="passport-field">
                <label>Дата выдачи <span class="req">*</span></label>
                <input type="date" id="pf_passport_issue_date" name="passport_issue_date"
                       value="<?= Html::encode($order->passport_issue_date ?? '') ?>"
                       required>
                <span class="field-error" id="err_passport_issue_date"></span>
            </div>
            <div class="passport-field">
                <label>Идентификационный номер <span class="req">*</span></label>
                <input type="text" id="pf_inn" name="inn"
                       value="<?= Html::encode($order->inn ?? '') ?>"
                       maxlength="14" required placeholder="14 символов">
                <small>14 символов (буквы и цифры)</small>
                <span class="field-error" id="err_inn"></span>
            </div>
        </div>

        <!-- ── Step 3 — Адрес доставки ── -->
        <div class="form-step">
            <span class="form-step__counter">3</span>
            <span class="form-step__title">Адрес доставки</span>
            <span class="form-step__hint">Шаг 3 из 3</span>
        </div>

        <div class="passport-row">
            <div class="passport-field passport-field--wide">
                <label>Полный адрес <span class="req">*</span></label>
                <input type="text" id="pf_full_address" name="full_address"
                       value="<?= Html::encode($order->full_address ?? '') ?>"
                       maxlength="500" required placeholder="ул. Ленина, д. 1, кв. 5">
                <span class="field-error" id="err_full_address"></span>
            </div>
        </div>
        <div class="passport-row">
            <div class="passport-field">
                <label>Город <span class="req">*</span></label>
                <input type="text" id="pf_city" name="city"
                       value="<?= Html::encode($order->city ?? '') ?>"
                       maxlength="100" required placeholder="Минск">
                <span class="field-error" id="err_city"></span>
            </div>
            <div class="passport-field">
                <label>Регион</label>
                <input type="text" id="pf_region" name="region"
                       value="<?= Html::encode($order->region ?? '') ?>"
                       maxlength="100" placeholder="Минская область">
            </div>
            <div class="passport-field">
                <label>Почтовый индекс <span class="req">*</span></label>
                <input type="text" id="pf_postal_code" name="postal_code"
                       value="<?= Html::encode($order->postal_code ?? '') ?>"
                       maxlength="10" required placeholder="220000">
                <span class="field-error" id="err_postal_code"></span>
            </div>
        </div>

        <div class="passport-actions">
            <button type="submit" id="passport-submit-btn">
                <i class="bi bi-shield-check"></i> Сохранить данные
            </button>
        </div>
    </form>
</div>

<?php
// Styles live in frontend/web/css/components/passport-form.css (registered via AppAsset).
?>

<?php
$saveUrl = \yii\helpers\Url::to(['/account/save-passport', 'id' => $order->id]);
$this->registerJs(<<<JS
(function() {
    var form = document.getElementById('passport-form');
    if (!form) return;

    function showMsg(msg, type) {
        var el = document.getElementById('passport-form-messages');
        el.innerHTML = '<div class="passport-msg passport-msg-' + type + '">' + msg + '</div>';
    }

    function clearErrors() {
        form.querySelectorAll('.field-error').forEach(function(el) { el.textContent = ''; });
        form.querySelectorAll('input').forEach(function(el) { el.classList.remove('is-invalid'); });
    }

    function fieldErr(key, msg) {
        var errEl = document.getElementById('err_' + key);
        var inEl = document.getElementById('pf_' + key);
        if (errEl) errEl.textContent = msg;
        if (inEl) inEl.classList.add('is-invalid');
    }

    // INN: alphanumeric, max 14, auto-uppercase.
    var innInput = document.getElementById('pf_inn');
    if (innInput) {
        innInput.addEventListener('input', function() {
            var v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 14);
            if (v !== this.value) this.value = v;
        });
    }
    var postalInput = document.getElementById('pf_postal_code');
    if (postalInput) {
        postalInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            if (v !== this.value) this.value = v;
        });
    }

    function validate() {
        clearErrors();
        var ok = true;
        [
            ['last_name', 'Фамилия'],
            ['first_name', 'Имя'],
            ['birth_date', 'Дата рождения'],
            ['passport_series', 'Серия+Номер паспорта'],
            ['passport_issue_date', 'Дата выдачи'],
            ['inn', 'Идентификационный номер'],
            ['full_address', 'Адрес'],
            ['city', 'Город'],
            ['postal_code', 'Индекс']
        ].forEach(function(f) {
            var el = document.getElementById('pf_' + f[0]);
            if (el && !el.value.trim()) { fieldErr(f[0], f[1] + ' обязателен'); ok = false; }
        });

        var seriesEl = document.getElementById('pf_passport_series');
        if (seriesEl && seriesEl.value) {
            var val = seriesEl.value.toUpperCase();
            if (window.PassportFormat && !window.PassportFormat.validate(val)) {
                fieldErr('passport_series', 'Формат: 2 латинские буквы + 7 цифр (например MP1234567)');
                ok = false;
            }
        }

        var inn = document.getElementById('pf_inn');
        if (inn && inn.value && inn.value.length !== 14) {
            fieldErr('inn', 'Идентификационный номер: ровно 14 символов'); ok = false;
        }
        return ok;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validate()) return;

        var btn = document.getElementById('passport-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

        fetch('{$saveUrl}', {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                showMsg(d.message || 'Данные сохранены!', 'success');
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранено';
                setTimeout(function() {
                    document.getElementById('passport-form-section').style.display = 'none';
                }, 2000);
            } else {
                showMsg(d.message || 'Проверьте данные и попробуйте снова', 'error');
                if (d.errors) {
                    Object.keys(d.errors).forEach(function(k) { fieldErr(k, d.errors[k]); });
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-shield-check"></i> Сохранить данные';
            }
        })
        .catch(function() {
            showMsg('Ошибка сети. Попробуйте ещё раз.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-check"></i> Сохранить данные';
        });
    });
})();
JS
);
?>
