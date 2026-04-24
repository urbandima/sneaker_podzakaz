<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="order-view-section order-view-section--full" id="passport-form-section">
    <h2><i class="bi bi-person-vcard"></i> Паспортные данные для Таможня:ДП</h2>
    <p class="passport-form-hint">
        Для отправки заказа через Таможня:ДП необходимо предоставить паспортные данные получателя.
        Данные передаются в зашифрованном виде и используются только для таможенного оформления.
    </p>

    <div id="passport-form-messages"></div>

    <form id="passport-form" class="passport-form" novalidate>
        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">

        <div class="passport-form-grid">
            <div class="passport-form-group passport-form-group--full">
                <span class="passport-form-section-label">ФИО получателя</span>
            </div>

            <div class="passport-form-group">
                <label for="pf_last_name">Фамилия <span class="required">*</span></label>
                <input type="text" id="pf_last_name" name="recipient_last_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_last_name ?? '') ?>"
                       maxlength="100" required
                       placeholder="ИВАНОВ">
                <span class="passport-form-error" id="err_last_name"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_first_name">Имя <span class="required">*</span></label>
                <input type="text" id="pf_first_name" name="recipient_first_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_first_name ?? '') ?>"
                       maxlength="100" required
                       placeholder="ИВАН">
                <span class="passport-form-error" id="err_first_name"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_middle_name">Отчество</label>
                <input type="text" id="pf_middle_name" name="recipient_middle_name"
                       class="passport-form-input"
                       value="<?= Html::encode($model->recipient_middle_name ?? '') ?>"
                       maxlength="100"
                       placeholder="ИВАНОВИЧ">
            </div>

            <div class="passport-form-group">
                <label for="pf_birth_date">Дата рождения <span class="required">*</span></label>
                <input type="date" id="pf_birth_date" name="birth_date"
                       class="passport-form-input"
                       value="<?= Html::encode($model->birth_date ?? '') ?>"
                       required>
                <span class="passport-form-error" id="err_birth_date"></span>
            </div>

            <div class="passport-form-group--full">
                <span class="passport-form-section-label">Паспорт</span>
            </div>

            <div class="passport-form-group">
                <label for="pf_passport_series">Серия паспорта <span class="required">*</span></label>
                <input type="text" id="pf_passport_series" name="passport_series"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_series ?? '') ?>"
                       maxlength="4" minlength="2" required
                       placeholder="AB12 или 1234" pattern="[A-Za-z0-9]{2,4}"
                       autocomplete="off" spellcheck="false">
                <small class="passport-form-hint-field">2–4 латинских буквы или цифры</small>
                <span class="passport-form-error" id="err_passport_series"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_passport_number">Номер паспорта <span class="required">*</span></label>
                <input type="text" id="pf_passport_number" name="passport_number"
                       class="passport-form-input"
                       value="<?= Html::encode($model->passport_number ?? '') ?>"
                       maxlength="7" minlength="6" required
                       placeholder="123456" pattern="[0-9]{6,7}"
                       inputmode="numeric" autocomplete="off">
                <small class="passport-form-hint-field">6–7 цифр, без пробелов</small>
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

            <div class="passport-form-group">
                <label for="pf_inn">ИНН (12 цифр) <span class="required">*</span></label>
                <input type="text" id="pf_inn" name="inn"
                       class="passport-form-input"
                       value="<?= Html::encode($model->inn ?? '') ?>"
                       maxlength="12" minlength="12" required
                       placeholder="ИНН (только для РФ)" pattern="[0-9]{12}">
                <span class="passport-form-error" id="err_inn"></span>
            </div>

            <div class="passport-form-group--full">
                <span class="passport-form-section-label">Адрес доставки</span>
            </div>

            <div class="passport-form-group passport-form-group--wide">
                <label for="pf_full_address">Полный адрес <span class="required">*</span></label>
                <input type="text" id="pf_full_address" name="full_address"
                       class="passport-form-input"
                       value="<?= Html::encode($model->full_address ?? '') ?>"
                       maxlength="500" required
                       placeholder="ул. Ленина, д. 1, кв. 5">
                <span class="passport-form-error" id="err_full_address"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_city">Город <span class="required">*</span></label>
                <input type="text" id="pf_city" name="city"
                       class="passport-form-input"
                       value="<?= Html::encode($model->city ?? '') ?>"
                       maxlength="100" required
                       placeholder="Минск">
                <span class="passport-form-error" id="err_city"></span>
            </div>

            <div class="passport-form-group">
                <label for="pf_region">Регион</label>
                <input type="text" id="pf_region" name="region"
                       class="passport-form-input"
                       value="<?= Html::encode($model->region ?? '') ?>"
                       maxlength="100"
                       placeholder="Минская область">
            </div>

            <div class="passport-form-group">
                <label for="pf_postal_code">Почтовый индекс <span class="required">*</span></label>
                <input type="text" id="pf_postal_code" name="postal_code"
                       class="passport-form-input"
                       value="<?= Html::encode($model->postal_code ?? '') ?>"
                       maxlength="10" required
                       placeholder="220000" pattern="[0-9]{5,10}">
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

<style>
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

.passport-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.passport-form-group--full,
.passport-form-group--wide {
    grid-column: 1 / -1;
}

.passport-form-group--wide {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.passport-form-section-label {
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-top: 8px;
    border-top: 1px solid #dee2e6;
}

.passport-form-group label {
    font-size: 13px;
    font-weight: 500;
    color: #1a1a2e;
}

.passport-form-group label .required {
    color: #dc3545;
}

.passport-form-input {
    padding: 10px 14px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    color: #1a1a2e;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    width: 100%;
    box-sizing: border-box;
}

.passport-form-input:focus {
    outline: none;
    border-color: #4472C4;
    box-shadow: 0 0 0 3px rgba(68, 114, 196, 0.15);
}

.passport-form-input.is-invalid {
    border-color: #dc3545;
}

.passport-form-hint-field {
    font-size: 11px;
    color: #6c757d;
    margin-top: -4px;
}

.passport-form-error {
    font-size: 12px;
    color: #dc3545;
    min-height: 16px;
}

.passport-form-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn-passport-submit {
    padding: 12px 28px;
    font-size: 15px;
    font-weight: 600;
}

.btn-passport-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.passport-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.passport-alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.passport-alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .passport-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$saveUrl = \yii\helpers\Url::to(['/order/save-passport', 'token' => $model->token]);
$this->registerJs(<<<JS
(function() {
    var form = document.getElementById('passport-form');
    if (!form) return;

    function showMessage(msg, type) {
        var el = document.getElementById('passport-form-messages');
        el.innerHTML = '<div class="passport-alert passport-alert-' + type + '">' + msg + '</div>';
        el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }

    function clearErrors() {
        form.querySelectorAll('.passport-form-error').forEach(function(el) { el.textContent = ''; });
        form.querySelectorAll('.passport-form-input').forEach(function(el) { el.classList.remove('is-invalid'); });
    }

    function showFieldError(fieldName, msg) {
        var errEl = document.getElementById('err_' + fieldName);
        var inputEl = document.getElementById('pf_' + fieldName);
        if (errEl) errEl.textContent = msg;
        if (inputEl) inputEl.classList.add('is-invalid');
    }

    function validateForm() {
        var valid = true;
        clearErrors();

        var required = [
            {id: 'pf_last_name', key: 'last_name', label: 'Фамилия'},
            {id: 'pf_first_name', key: 'first_name', label: 'Имя'},
            {id: 'pf_birth_date', key: 'birth_date', label: 'Дата рождения'},
            {id: 'pf_passport_series', key: 'passport_series', label: 'Серия паспорта'},
            {id: 'pf_passport_number', key: 'passport_number', label: 'Номер паспорта'},
            {id: 'pf_passport_issue_date', key: 'passport_issue_date', label: 'Дата выдачи'},
            {id: 'pf_inn', key: 'inn', label: 'ИНН'},
            {id: 'pf_full_address', key: 'full_address', label: 'Адрес'},
            {id: 'pf_city', key: 'city', label: 'Город'},
            {id: 'pf_postal_code', key: 'postal_code', label: 'Почтовый индекс'},
        ];

        required.forEach(function(f) {
            var el = document.getElementById(f.id);
            if (!el || !el.value.trim()) {
                showFieldError(f.key, f.label + ' обязателен');
                valid = false;
            }
        });

        var innEl = document.getElementById('pf_inn');
        if (innEl && innEl.value && !/^[0-9]{12}$/.test(innEl.value)) {
            showFieldError('inn', 'ИНН должен содержать ровно 12 цифр');
            valid = false;
        }

        var seriesEl = document.getElementById('pf_passport_series');
        if (seriesEl && seriesEl.value && !/^[A-Za-z0-9]{2,4}$/.test(seriesEl.value.trim())) {
            showFieldError('passport_series', 'Серия: 2–4 латинских буквы или цифры (напр. AB12)');
            valid = false;
        }

        var numEl = document.getElementById('pf_passport_number');
        if (numEl && numEl.value && !/^[0-9]{6,7}$/.test(numEl.value)) {
            showFieldError('passport_number', 'Номер должен содержать 6–7 цифр');
            valid = false;
        }

        return valid;
    }

    // ── Input masks ──────────────────────────────────────────────────────────
    // Series: allow only latin letters and digits, auto-uppercase
    var seriesInput = document.getElementById('pf_passport_series');
    if (seriesInput) {
        seriesInput.addEventListener('input', function() {
            var v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4);
            if (v !== this.value) this.value = v;
        });
    }
    // Passport number: digits only
    var numInput = document.getElementById('pf_passport_number');
    if (numInput) {
        numInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 7);
            if (v !== this.value) this.value = v;
        });
    }
    // INN: digits only
    var innInput = document.getElementById('pf_inn');
    if (innInput) {
        innInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 12);
            if (v !== this.value) this.value = v;
        });
    }
    // Postal code: digits only
    var postalInput = document.getElementById('pf_postal_code');
    if (postalInput) {
        postalInput.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            if (v !== this.value) this.value = v;
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validateForm()) return;

        var btn = document.getElementById('passport-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

        var data = new FormData(form);
        fetch('{$saveUrl}', {
            method: 'POST',
            body: data,
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
                        showFieldError(k, resp.errors[k]);
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
