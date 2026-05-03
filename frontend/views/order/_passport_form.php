<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$citizenship = $model->citizenship ?: 'by';
?>

<div class="order-view-section order-view-section--full" id="passport-form-section">
    <h2><i class="bi bi-person-vcard"></i> Паспортные данные для Таможня:ДП</h2>
    <p class="passport-form-hint">
        Для отправки заказа через Таможня:ДП необходимо предоставить паспортные данные получателя.
        Данные передаются в зашифрованном виде и используются только для таможенного оформления.
    </p>

    <div id="passport-form-messages"></div>

    <!-- Citizenship selector -->
    <div class="citizenship-radios">
        <label class="citizenship-option <?= $citizenship === 'by' ? 'citizenship-option--active' : '' ?>">
            <input type="radio" name="citizenship" value="by" <?= $citizenship === 'by' ? 'checked' : '' ?>
                   onchange="switchCitizenship('by')">
            <span><i class="bi bi-flag"></i> Гражданин РБ</span>
        </label>
        <label class="citizenship-option <?= $citizenship === 'ru' ? 'citizenship-option--active' : '' ?>">
            <input type="radio" name="citizenship" value="ru" <?= $citizenship === 'ru' ? 'checked' : '' ?>
                   onchange="switchCitizenship('ru')">
            <span><i class="bi bi-flag-fill"></i> Гражданин РФ</span>
        </label>
    </div>

    <form id="passport-form" class="passport-form" novalidate>
        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
        <input type="hidden" id="pf_citizenship" name="citizenship" value="<?= Html::encode($citizenship) ?>">

        <!-- ── Step 1 — ФИО + дата рождения ── -->
        <div class="form-step">
            <span class="form-step__counter">1</span>
            <span class="form-step__title">ФИО и дата рождения</span>
            <span class="form-step__hint">Шаг 1 из 3</span>
        </div>

        <div class="passport-form-grid">
            <div class="passport-form-group">
                <label for="pf_last_name">Фамилия <span class="required">*</span></label>
                <input type="text" id="pf_last_name" name="recipient_last_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_last_name ?? '') ?>"
                       maxlength="100" required placeholder="ИВАНОВ">
                <span class="passport-form-error" id="err_last_name"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_first_name">Имя <span class="required">*</span></label>
                <input type="text" id="pf_first_name" name="recipient_first_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_first_name ?? '') ?>"
                       maxlength="100" required placeholder="ИВАН">
                <span class="passport-form-error" id="err_first_name"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_middle_name">Отчество</label>
                <input type="text" id="pf_middle_name" name="recipient_middle_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_middle_name ?? '') ?>"
                       maxlength="100" placeholder="ИВАНОВИЧ">
            </div>

            <div class="passport-form-group">
                <label for="pf_birth_date">Дата рождения <span class="required">*</span></label>
                <input type="date" id="pf_birth_date" name="birth_date"
                       class="passport-form-input"
                       value="<?= Html::encode($model->birth_date ?? '') ?>"
                       required>
                <span class="passport-form-error" id="err_birth_date"></span>
            </div>
        </div>

        <!-- ── Step 2 — Паспорт ── -->
        <div class="form-step">
            <span class="form-step__counter">2</span>
            <span class="form-step__title">Паспорт</span>
            <span class="form-step__hint">Шаг 2 из 3</span>
        </div>

        <div class="passport-form-grid">
            <!-- BY: combined Серия+Номер (MP1234567); RU: separate series + number.
                 BY combined field uses the canonical partial frontend/views/partials/_passport_field.php
                 — single source of truth shared with /account and /admin. -->
            <?= $this->render('../partials/_passport_field', [
                'id'         => 'pf_passport_series_by',
                'name'       => null,
                'value'      => $citizenship === 'by' ? ($model->passport_series ?? '') : '',
                'errorId'    => 'err_passport_series_by',
                'required'   => $citizenship === 'by',
                'wrapperId'  => 'by_passport_group',
                'hideStyle'  => $citizenship !== 'by' ? 'display:none' : '',
            ]) ?>

            <div class="passport-form-group" id="ru_series_group" style="<?= $citizenship !== 'ru' ? 'display:none' : '' ?>">
                <label for="pf_passport_series_ru">
                    Серия паспорта <span class="required">*</span>
                </label>
                <input type="text" id="pf_passport_series_ru"
                       class="passport-form-input"
                       value="<?= Html::encode($citizenship === 'ru' ? ($model->passport_series ?? '') : '') ?>"
                       maxlength="4" <?= $citizenship === 'ru' ? 'required' : '' ?>
                       autocomplete="off" spellcheck="false"
                       pattern="[0-9]{4}" inputmode="numeric"
                       placeholder="1234">
                <small class="passport-form-hint-field">4 цифры (напр. 1234)</small>
                <span class="passport-form-error" id="err_passport_series_ru"></span>
            </div>

            <div class="passport-form-group" id="ru_number_group" style="<?= $citizenship !== 'ru' ? 'display:none' : '' ?>">
                <label for="pf_passport_number">
                    Номер паспорта <span class="required">*</span>
                </label>
                <input type="text" id="pf_passport_number" name="passport_number"
                       class="passport-form-input"
                       value="<?= Html::encode($citizenship === 'ru' ? ($model->passport_number ?? '') : '') ?>"
                       maxlength="6" <?= $citizenship === 'ru' ? 'required' : '' ?>
                       inputmode="numeric" autocomplete="off"
                       placeholder="123456">
                <small class="passport-form-hint-field">6 цифр</small>
                <span class="passport-form-error" id="err_passport_number"></span>
            </div>

            <!-- Hidden submit field — populated from BY combined or RU series before submit -->
            <input type="hidden" id="pf_passport_series" name="passport_series"
                   value="<?= Html::encode($model->passport_series ?? '') ?>">

            <div class="passport-form-group">
                <label for="pf_passport_issue_date">Дата выдачи <span class="required">*</span></label>
                <input type="date" id="pf_passport_issue_date" name="passport_issue_date"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_issue_date ?? '') ?>"
                       required>
                <span class="passport-form-error" id="err_passport_issue_date"></span>
            </div>

            <div class="passport-form-group passport-form-group--wide">
                <label for="pf_issued_by">Кем выдан <span class="required">*</span></label>
                <input type="text" id="pf_issued_by" name="passport_issued_by"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_issued_by ?? '') ?>"
                       maxlength="255" required
                       placeholder="Орган, выдавший паспорт">
                <span class="passport-form-error" id="err_issued_by"></span>
            </div>

            <div class="passport-form-group" id="inn_group">
                <label for="pf_inn" id="inn_label">
                    <span id="inn_label_text">
                        <?= $citizenship === 'ru' ? 'ИНН (12 цифр)' : 'Идентификационный номер (14 символов)' ?>
                    </span>
                    <span class="required">*</span>
                </label>
                <input type="text" id="pf_inn" name="inn"
                       class="passport-form-input"
                       value="<?= Html::encode($model->inn ?? '') ?>"
                       maxlength="14" required autocomplete="off">
                <small class="passport-form-hint-field" id="inn_hint">
                    <?= $citizenship === 'ru' ? '12 цифр (СНИЛС не подходит)' : '14 символов (буквы и цифры)' ?>
                </small>
                <span class="passport-form-error" id="err_inn"></span>
            </div>

            <div class="passport-form-group" id="division_code_group"
                 style="<?= $citizenship !== 'ru' ? 'display:none' : '' ?>">
                <label for="pf_division_code">Код подразделения</label>
                <input type="text" id="pf_division_code" name="passport_division_code"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_division_code ?? '') ?>"
                       maxlength="20" placeholder="100-106"
                       pattern="[0-9]{3}-[0-9]{3}|[0-9]{6}">
                <small class="passport-form-hint-field">Формат: 100-106</small>
            </div>
        </div>

        <!-- ── Step 3 — Адрес доставки ── -->
        <div class="form-step">
            <span class="form-step__counter">3</span>
            <span class="form-step__title">Адрес доставки</span>
            <span class="form-step__hint">Шаг 3 из 3</span>
        </div>

        <div class="passport-form-grid">
            <div class="passport-form-group passport-form-group--wide">
                <label for="pf_full_address">Полный адрес <span class="required">*</span></label>
                <input type="text" id="pf_full_address" name="full_address"
                       class="passport-form-input"
                       value="<?= Html::encode($model->full_address ?? '') ?>"
                       maxlength="500" required placeholder="ул. Ленина, д. 1, кв. 5">
                <span class="passport-form-error" id="err_full_address"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_city">Город <span class="required">*</span></label>
                <input type="text" id="pf_city" name="city"
                       class="passport-form-input"
                       value="<?= Html::encode($model->city ?? '') ?>"
                       maxlength="100" required placeholder="Минск">
                <span class="passport-form-error" id="err_city"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_region">Регион</label>
                <input type="text" id="pf_region" name="region"
                       class="passport-form-input"
                       value="<?= Html::encode($model->region ?? '') ?>"
                       maxlength="100" placeholder="Минская область">
            </div>

            <div class="passport-form-group">
                <label for="pf_postal_code">Почтовый индекс <span class="required">*</span></label>
                <input type="text" id="pf_postal_code" name="postal_code"
                       class="passport-form-input"
                       value="<?= Html::encode($model->postal_code ?? '') ?>"
                       maxlength="10" required placeholder="220000" pattern="[0-9]{5,10}"
                       inputmode="numeric">
                <span class="passport-form-error" id="err_postal_code"></span>
            </div>
        </div>

        <div class="passport-form-actions">
            <button type="submit" class="btn btn-primary btn-passport-submit" id="passport-submit-btn">
                <i class="bi bi-shield-check"></i>
                Сохранить паспортные данные
            </button>
        </div>
    </form>
</div>

<?php
// Styles live in frontend/web/css/components/passport-form.css (registered via AppAsset).
?>

<?php
$saveUrl = Url::to(['/order/save-passport', 'token' => $model->token]);
$this->registerJs(<<<JS
(function() {
    var form = document.getElementById('passport-form');
    if (!form) return;

    // ── Citizenship switch ───────────────────────────────────────────────────
    window.switchCitizenship = function(cit) {
        document.getElementById('pf_citizenship').value = cit;
        document.querySelectorAll('.citizenship-option').forEach(function(el) {
            el.classList.toggle('citizenship-option--active', el.querySelector('input').value === cit);
        });

        var byGroup = document.getElementById('by_passport_group');
        var ruSerGroup = document.getElementById('ru_series_group');
        var ruNumGroup = document.getElementById('ru_number_group');
        var byInput = document.getElementById('pf_passport_series_by');
        var ruSerInput = document.getElementById('pf_passport_series_ru');
        var ruNumInput = document.getElementById('pf_passport_number');

        if (cit === 'ru') {
            byGroup.style.display = 'none';
            ruSerGroup.style.display = '';
            ruNumGroup.style.display = '';
            byInput.required = false;
            ruSerInput.required = true;
            ruNumInput.required = true;
        } else {
            byGroup.style.display = '';
            ruSerGroup.style.display = 'none';
            ruNumGroup.style.display = 'none';
            byInput.required = true;
            ruSerInput.required = false;
            ruNumInput.required = false;
        }

        var innLabelText = document.getElementById('inn_label_text');
        var innHint = document.getElementById('inn_hint');
        var divGroup = document.getElementById('division_code_group');
        if (cit === 'ru') {
            innLabelText.textContent = 'ИНН (12 цифр)';
            innHint.textContent = '12 цифр (СНИЛС не подходит)';
            document.getElementById('pf_inn').maxLength = 12;
            divGroup.style.display = '';
        } else {
            innLabelText.textContent = 'Идентификационный номер (14 символов)';
            innHint.textContent = '14 символов (буквы и цифры)';
            document.getElementById('pf_inn').maxLength = 14;
            divGroup.style.display = 'none';
        }
    };

    // ── Input masks ──────────────────────────────────────────────────────────
    // BY combined: handled by frontend/web/js/passport-format.js via data-format="by-passport".
    // RU series: 4 digits.
    var ruSerInput = document.getElementById('pf_passport_series_ru');
    if (ruSerInput) {
        ruSerInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 4);
            if (v !== this.value) this.value = v;
        });
    }
    var numInput = document.getElementById('pf_passport_number');
    if (numInput) {
        numInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            if (v !== this.value) this.value = v;
        });
    }
    var innInput = document.getElementById('pf_inn');
    if (innInput) {
        innInput.addEventListener('input', function() {
            var cit = document.getElementById('pf_citizenship').value;
            var max = (cit === 'ru') ? 12 : 14;
            var v;
            if (cit === 'ru') {
                v = this.value.replace(/[^0-9]/g, '').slice(0, max);
            } else {
                v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, max);
            }
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

    // ── Validation ───────────────────────────────────────────────────────────
    function showMessage(msg, type) {
        var el = document.getElementById('passport-form-messages');
        el.innerHTML = '<div class="passport-alert passport-alert-' + type + '">' + msg + '</div>';
        el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }
    function clearErrors() {
        form.querySelectorAll('.passport-form-error').forEach(function(el) { el.textContent = ''; });
        form.querySelectorAll('.passport-form-input').forEach(function(el) { el.classList.remove('is-invalid'); });
    }
    function showFieldError(key, msg) {
        var errEl = document.getElementById('err_' + key);
        var inputEl = document.getElementById('pf_' + key);
        if (errEl) errEl.textContent = msg;
        if (inputEl) inputEl.classList.add('is-invalid');
    }

    function validateForm() {
        clearErrors();
        var cit = document.getElementById('pf_citizenship').value || 'by';
        var valid = true;

        var common = [
            {id:'pf_last_name',           key:'last_name',          label:'Фамилия'},
            {id:'pf_first_name',          key:'first_name',         label:'Имя'},
            {id:'pf_birth_date',          key:'birth_date',         label:'Дата рождения'},
            {id:'pf_passport_issue_date', key:'passport_issue_date',label:'Дата выдачи'},
            {id:'pf_issued_by',           key:'issued_by',          label:'Кем выдан'},
            {id:'pf_inn',                 key:'inn',                label:cit==='ru'?'ИНН':'Идентификационный номер'},
            {id:'pf_full_address',        key:'full_address',       label:'Адрес'},
            {id:'pf_city',                key:'city',               label:'Город'},
            {id:'pf_postal_code',         key:'postal_code',        label:'Почтовый индекс'}
        ];
        common.forEach(function(f) {
            var el = document.getElementById(f.id);
            if (!el || !el.value.trim()) { showFieldError(f.key, f.label + ' обязателен'); valid = false; }
        });

        var seriesHidden = document.getElementById('pf_passport_series');
        if (cit === 'by') {
            var byEl = document.getElementById('pf_passport_series_by');
            var byVal = (byEl.value || '').toUpperCase();
            if (!byVal) {
                showFieldError('passport_series_by', 'Серия+Номер паспорта обязательны');
                valid = false;
            } else if (window.PassportFormat && !window.PassportFormat.validate(byVal)) {
                showFieldError('passport_series_by', 'Формат: 2 латинские буквы + 7 цифр (например MP1234567)');
                valid = false;
            }
            seriesHidden.value = byVal;
        } else {
            var ruSerEl = document.getElementById('pf_passport_series_ru');
            var ruNumEl = document.getElementById('pf_passport_number');
            if (!ruSerEl.value || !/^[0-9]{4}\$/.test(ruSerEl.value)) {
                showFieldError('passport_series_ru', 'Серия РФ: ровно 4 цифры'); valid = false;
            }
            if (!ruNumEl.value || !/^[0-9]{6}\$/.test(ruNumEl.value)) {
                showFieldError('passport_number', 'Номер: ровно 6 цифр'); valid = false;
            }
            seriesHidden.value = ruSerEl.value;
        }

        var innEl = document.getElementById('pf_inn');
        if (innEl && innEl.value) {
            if (cit === 'ru') {
                if (!/^[0-9]{12}\$/.test(innEl.value)) { showFieldError('inn', 'ИНН: ровно 12 цифр'); valid = false; }
            } else {
                if (innEl.value.length !== 14) { showFieldError('inn', 'Идентификационный номер: ровно 14 символов'); valid = false; }
            }
        }
        return valid;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validateForm()) return;

        var btn = document.getElementById('passport-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

        fetch('{$saveUrl}', {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success) {
                showMessage(resp.message || 'Паспортные данные сохранены!', 'success');
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранено';
                setTimeout(function() {
                    document.getElementById('passport-form-section').style.display = 'none';
                }, 2000);
            } else {
                showMessage(resp.message || 'Ошибка при сохранении. Проверьте данные.', 'error');
                if (resp.errors) {
                    Object.keys(resp.errors).forEach(function(k) {
                        // Server sends passport_series for both citizenships; route to the visible field.
                        var key = k;
                        if (k === 'passport_series') {
                            key = (document.getElementById('pf_citizenship').value === 'by')
                                ? 'passport_series_by' : 'passport_series_ru';
                        }
                        showFieldError(key, resp.errors[k]);
                    });
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-shield-check"></i> Сохранить паспортные данные';
            }
        })
        .catch(function() {
            showMessage('Ошибка сети. Попробуйте ещё раз.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-check"></i> Сохранить паспортные данные';
        });
    });
})();
JS
);
?>
