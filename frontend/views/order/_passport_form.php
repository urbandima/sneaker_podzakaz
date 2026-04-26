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

        <div class="passport-form-grid">
            <!-- ── ФИО ── -->
            <div class="passport-form-group passport-form-group--full">
                <span class="passport-form-section-label">ФИО получателя</span>
            </div>

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

            <!-- ── Паспорт (динамические поля) ── -->
            <div class="passport-form-group passport-form-group--full">
                <span class="passport-form-section-label">Паспорт</span>
            </div>

            <!-- Серия: BY = 2-4 лат. буквы, RU = 4 цифры -->
            <div class="passport-form-group">
                <label for="pf_passport_series">
                    Серия паспорта <span class="required">*</span>
                </label>
                <input type="text" id="pf_passport_series" name="passport_series"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_series ?? '') ?>"
                       maxlength="4" required autocomplete="off" spellcheck="false">
                <small class="passport-form-hint-field" id="series_hint">
                    <?= $citizenship === 'ru' ? '4 цифры (напр. 1234)' : '2–4 лат. буквы (напр. AB)' ?>
                </small>
                <span class="passport-form-error" id="err_passport_series"></span>
            </div>

            <!-- Номер: BY = 7 цифр, RU = 6 цифр -->
            <div class="passport-form-group">
                <label for="pf_passport_number">
                    Номер паспорта <span class="required">*</span>
                </label>
                <input type="text" id="pf_passport_number" name="passport_number"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_number ?? '') ?>"
                       maxlength="7" required inputmode="numeric" autocomplete="off">
                <small class="passport-form-hint-field" id="number_hint">
                    <?= $citizenship === 'ru' ? '6 цифр' : '7 цифр' ?>
                </small>
                <span class="passport-form-error" id="err_passport_number"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_passport_issue_date">Дата выдачи <span class="required">*</span></label>
                <input type="date" id="pf_passport_issue_date" name="passport_issue_date"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_issue_date ?? '') ?>"
                       required>
                <span class="passport-form-error" id="err_passport_issue_date"></span>
            </div>

            <!-- Кем выдан -->
            <div class="passport-form-group passport-form-group--wide">
                <label for="pf_issued_by">Кем выдан <span class="required">*</span></label>
                <input type="text" id="pf_issued_by" name="passport_issued_by"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_issued_by ?? '') ?>"
                       maxlength="255" required
                       placeholder="Орган, выдавший паспорт">
                <span class="passport-form-error" id="err_issued_by"></span>
            </div>

            <!-- BY: идентификационный номер (14 chars); RU: ИНН (12 цифр) -->
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

            <!-- RU only: код подразделения -->
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

            <!-- ── Адрес ── -->
            <div class="passport-form-group passport-form-group--full">
                <span class="passport-form-section-label">Адрес доставки</span>
            </div>

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
        </div><!-- /.passport-form-grid -->

        <div class="passport-form-actions">
            <button type="submit" class="btn btn-primary btn-passport-submit" id="passport-submit-btn">
                <i class="bi bi-shield-check"></i>
                Сохранить паспортные данные
            </button>
        </div>
    </form>
</div>

<style>
.citizenship-radios {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.citizenship-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    background: #fff;
    transition: border-color .15s, background .15s;
    user-select: none;
}
.citizenship-option input[type=radio] { display: none; }
.citizenship-option--active,
.citizenship-option:has(input:checked) {
    border-color: #4472C4;
    background: #eff4ff;
    color: #1a3b8a;
}
.passport-form-hint {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #fff3cd;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
}
.passport-form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.passport-form-group { display: flex; flex-direction: column; gap: 6px; }
.passport-form-group--full,
.passport-form-group--wide { grid-column: 1 / -1; }
.passport-form-section-label {
    font-size: 12px; font-weight: 700; color: #6c757d;
    text-transform: uppercase; letter-spacing: .05em;
    padding-top: 8px; border-top: 1px solid #dee2e6;
}
.passport-form-group label { font-size: 13px; font-weight: 500; color: #1a1a2e; }
.passport-form-group label .required { color: #dc3545; }
.passport-form-input {
    padding: 10px 14px; border: 1px solid #dee2e6; border-radius: 8px;
    font-size: 14px; color: #1a1a2e; background: #fff;
    transition: border-color .2s, box-shadow .2s; width: 100%; box-sizing: border-box;
}
.passport-form-input:focus { outline: none; border-color: #4472C4; box-shadow: 0 0 0 3px rgba(68,114,196,.15); }
.passport-form-input.is-invalid { border-color: #dc3545; }
.passport-form-hint-field { font-size: 11px; color: #6c757d; margin-top: -4px; }
.passport-form-error { font-size: 12px; color: #dc3545; min-height: 16px; }
.passport-form-actions { display: flex; gap: 12px; align-items: center; }
.btn-passport-submit { padding: 12px 28px; font-size: 15px; font-weight: 600; }
.btn-passport-submit:disabled { opacity: .6; cursor: not-allowed; }
.passport-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.passport-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.passport-alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
@media (max-width: 768px) { .passport-form-grid { grid-template-columns: 1fr; } }
</style>

<?php
$saveUrl = Url::to(['/order/save-passport', 'token' => $model->token]);
$this->registerJs(<<<JS
(function() {
    var form = document.getElementById('passport-form');
    if (!form) return;

    // ── Citizenship switch ───────────────────────────────────────────────────
    window.switchCitizenship = function(cit) {
        document.getElementById('pf_citizenship').value = cit;
        // Sync radio labels
        document.querySelectorAll('.citizenship-option').forEach(function(el) {
            el.classList.toggle('citizenship-option--active', el.querySelector('input').value === cit);
        });

        var seriesEl  = document.getElementById('pf_passport_series');
        var numberEl  = document.getElementById('pf_passport_number');
        var seriesHint = document.getElementById('series_hint');
        var numberHint = document.getElementById('number_hint');
        var innLabelText = document.getElementById('inn_label_text');
        var innHint = document.getElementById('inn_hint');
        var divGroup = document.getElementById('division_code_group');

        if (cit === 'ru') {
            seriesEl.maxLength = 4;
            seriesEl.placeholder = '1234';
            seriesHint.textContent = '4 цифры (напр. 1234)';
            numberEl.maxLength = 6;
            numberHint.textContent = '6 цифр';
            innLabelText.textContent = 'ИНН (12 цифр)';
            innHint.textContent = '12 цифр (СНИЛС не подходит)';
            document.getElementById('pf_inn').maxLength = 12;
            divGroup.style.display = '';
        } else {
            seriesEl.maxLength = 4;
            seriesEl.placeholder = 'AB';
            seriesHint.textContent = '2–4 лат. буквы (напр. AB)';
            numberEl.maxLength = 7;
            numberHint.textContent = '7 цифр';
            innLabelText.textContent = 'Идентификационный номер (14 символов)';
            innHint.textContent = '14 символов (буквы и цифры)';
            document.getElementById('pf_inn').maxLength = 14;
            divGroup.style.display = 'none';
        }

        // Re-attach input mask for series
        attachSeriesMask(cit);
    };

    // ── Input masks ──────────────────────────────────────────────────────────
    function attachSeriesMask(cit) {
        var el = document.getElementById('pf_passport_series');
        if (!el) return;
        el._cit = cit;
        if (!el._maskAttached) {
            el._maskAttached = true;
            el.addEventListener('input', function() {
                var c = this._cit || 'by';
                var v;
                if (c === 'ru') {
                    v = this.value.replace(/[^0-9]/g, '').slice(0, 4);
                } else {
                    v = this.value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 4);
                }
                if (v !== this.value) this.value = v;
            });
        }
    }
    attachSeriesMask(document.getElementById('pf_citizenship').value || 'by');

    var numInput = document.getElementById('pf_passport_number');
    if (numInput) {
        numInput.addEventListener('input', function() {
            var max = (document.getElementById('pf_citizenship').value === 'ru') ? 6 : 7;
            var v = this.value.replace(/[^0-9]/g, '').slice(0, max);
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
                // BY: alphanumeric
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
            {id:'pf_last_name',           key:'last_name',         label:'Фамилия'},
            {id:'pf_first_name',          key:'first_name',        label:'Имя'},
            {id:'pf_birth_date',          key:'birth_date',        label:'Дата рождения'},
            {id:'pf_passport_series',     key:'passport_series',   label:'Серия паспорта'},
            {id:'pf_passport_number',     key:'passport_number',   label:'Номер паспорта'},
            {id:'pf_passport_issue_date', key:'passport_issue_date',label:'Дата выдачи'},
            {id:'pf_issued_by',           key:'issued_by',         label:'Кем выдан'},
            {id:'pf_inn',                 key:'inn',               label:cit==='ru'?'ИНН':'Идентификационный номер'},
            {id:'pf_full_address',        key:'full_address',      label:'Адрес'},
            {id:'pf_city',                key:'city',              label:'Город'},
            {id:'pf_postal_code',         key:'postal_code',       label:'Почтовый индекс'},
        ];
        common.forEach(function(f) {
            var el = document.getElementById(f.id);
            if (!el || !el.value.trim()) { showFieldError(f.key, f.label + ' обязателен'); valid = false; }
        });

        var seriesEl = document.getElementById('pf_passport_series');
        if (seriesEl && seriesEl.value) {
            if (cit === 'ru') {
                if (!/^[0-9]{4}$/.test(seriesEl.value)) { showFieldError('passport_series', 'Серия РФ: ровно 4 цифры'); valid = false; }
            } else {
                if (!/^[A-Z]{2,4}$/.test(seriesEl.value.toUpperCase())) { showFieldError('passport_series', 'Серия РБ: 2–4 латинские буквы'); valid = false; }
            }
        }
        var numEl = document.getElementById('pf_passport_number');
        if (numEl && numEl.value) {
            var expectedLen = (cit === 'ru') ? 6 : 7;
            if (!/^[0-9]+$/.test(numEl.value) || numEl.value.length !== expectedLen) {
                showFieldError('passport_number', 'Номер: ровно ' + expectedLen + ' цифр'); valid = false;
            }
        }
        var innEl = document.getElementById('pf_inn');
        if (innEl && innEl.value) {
            if (cit === 'ru') {
                if (!/^[0-9]{12}$/.test(innEl.value)) { showFieldError('inn', 'ИНН: ровно 12 цифр'); valid = false; }
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
                    Object.keys(resp.errors).forEach(function(k) { showFieldError(k, resp.errors[k]); });
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
