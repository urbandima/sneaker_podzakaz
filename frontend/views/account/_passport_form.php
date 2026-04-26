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

        <div class="passport-section-title">ФИО получателя</div>
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

        <div class="passport-section-title">Паспорт</div>
        <div class="passport-row">
            <div class="passport-field">
                <label>Серия <span class="req">*</span></label>
                <input type="text" id="pf_passport_series" name="passport_series"
                       value="<?= Html::encode($order->passport_series ?? '') ?>"
                       maxlength="4" required placeholder="AB"
                       pattern="[A-Z]{2,4}" autocomplete="off" spellcheck="false"
                       oninput="this.value=this.value.replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,4)">
                <small>2–4 лат. буквы</small>
                <span class="field-error" id="err_passport_series"></span>
            </div>
            <div class="passport-field">
                <label>Номер <span class="req">*</span></label>
                <input type="text" id="pf_passport_number" name="passport_number"
                       value="<?= Html::encode($order->passport_number ?? '') ?>"
                       maxlength="7" required placeholder="1234567">
                <small>6–7 цифр</small>
                <span class="field-error" id="err_passport_number"></span>
            </div>
            <div class="passport-field">
                <label>Дата выдачи <span class="req">*</span></label>
                <input type="date" id="pf_passport_issue_date" name="passport_issue_date"
                       value="<?= Html::encode($order->passport_issue_date ?? '') ?>"
                       required>
                <span class="field-error" id="err_passport_issue_date"></span>
            </div>
            <div class="passport-field">
                <label>ИНН (12 цифр) <span class="req">*</span></label>
                <input type="text" id="pf_inn" name="inn"
                       value="<?= Html::encode($order->inn ?? '') ?>"
                       maxlength="12" required placeholder="ИНН (только для РФ)">
                <span class="field-error" id="err_inn"></span>
            </div>
        </div>

        <div class="passport-section-title">Адрес доставки</div>
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

<style>
.passport-card {
    border-left: 4px solid #4472C4;
}

.passport-hint {
    font-size: 13px;
    color: #6c757d;
    background: #fff8e1;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.passport-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin: 20px 0 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
}

.passport-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 4px;
}

.passport-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.passport-field--wide {
    grid-column: 1 / -1;
}

.passport-field label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}

.passport-field .req {
    color: #ef4444;
}

.passport-field input {
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    transition: border-color 0.15s;
    width: 100%;
    box-sizing: border-box;
}

.passport-field input:focus {
    outline: none;
    border-color: #4472C4;
    box-shadow: 0 0 0 3px rgba(68, 114, 196, 0.12);
}

.passport-field input.is-invalid {
    border-color: #ef4444;
}

.passport-field small {
    font-size: 11px;
    color: #9ca3af;
}

.field-error {
    font-size: 11px;
    color: #ef4444;
    min-height: 14px;
}

.passport-actions {
    margin-top: 20px;
}

.passport-actions button {
    padding: 11px 24px;
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.passport-actions button:hover {
    background: #2d2d44;
}

.passport-actions button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.passport-msg {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 14px;
}

.passport-msg-success {
    background: #d1fae5;
    color: #065f46;
}

.passport-msg-error {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 600px) {
    .passport-row {
        grid-template-columns: 1fr;
    }
}
</style>

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

    function validate() {
        clearErrors();
        var ok = true;
        [
            ['last_name', 'Фамилия'],
            ['first_name', 'Имя'],
            ['birth_date', 'Дата рождения'],
            ['passport_series', 'Серия паспорта'],
            ['passport_number', 'Номер паспорта'],
            ['passport_issue_date', 'Дата выдачи'],
            ['inn', 'ИНН'],
            ['full_address', 'Адрес'],
            ['city', 'Город'],
            ['postal_code', 'Индекс'],
        ].forEach(function(f) {
            var el = document.getElementById('pf_' + f[0]);
            if (el && !el.value.trim()) { fieldErr(f[0], f[1] + ' обязателен'); ok = false; }
        });

        var inn = document.getElementById('pf_inn');
        if (inn && inn.value && !/^[0-9]{12}$/.test(inn.value)) {
            fieldErr('inn', 'ИНН — 12 цифр'); ok = false;
        }
        var num = document.getElementById('pf_passport_number');
        if (num && num.value && !/^[0-9]{6,7}$/.test(num.value)) {
            fieldErr('passport_number', '6–7 цифр'); ok = false;
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
