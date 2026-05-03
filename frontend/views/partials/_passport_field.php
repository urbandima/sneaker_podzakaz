<?php

/**
 * Canonical BY-passport combined "Серия+Номер" input field.
 *
 * Used in: order/_passport_form.php, account/_passport_form.php, admin order/view.
 * Rendering and validation logic live in frontend/web/js/passport-format.js
 * (auto-wired via data-format="by-passport").
 *
 * Required vars:
 *   string      $id        — DOM id (e.g. "pf_passport_series_by")
 *   string      $value     — current value
 *
 * Optional vars:
 *   string|null $name      — form-field name. Pass null/false to omit `name`
 *                            (used by order form where a hidden mirror field
 *                            collects the canonical `passport_series`).
 *   string $errorId        — DOM id for the error span (default: "err_<name>")
 *   bool   $required       — render `required` attribute (default: true)
 *   string $labelClass     — class on the wrapping <label>/group (default: "passport-form-group passport-form-group--wide")
 *   string $inputClass     — class on the <input> (default: "passport-form-input passport-form-input--mono")
 *   string $errorClass     — class on the error span (default: "passport-form-error")
 *   string $hintClass      — class on the small hint (default: "passport-form-hint-field")
 *   string $hideStyle      — extra inline style on wrapper (e.g. "display:none")
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;

$name        = $name        ?? 'passport_series';
$id          = $id          ?? 'pf_passport_series_by';
$value       = $value       ?? '';
$errorId     = $errorId     ?? 'err_' . ($name ?: 'passport_series');
$required    = $required    ?? true;
$labelClass  = $labelClass  ?? 'passport-form-group passport-form-group--wide';
$inputClass  = $inputClass  ?? 'passport-form-input passport-form-input--mono';
$errorClass  = $errorClass  ?? 'passport-form-error';
$hintClass   = $hintClass   ?? 'passport-form-hint-field';
$hideStyle   = $hideStyle   ?? '';
$wrapperId   = $wrapperId   ?? '';
?>
<div class="<?= Html::encode($labelClass) ?>"
     <?= $wrapperId ? 'id="' . Html::encode($wrapperId) . '"' : '' ?>
     <?= $hideStyle ? 'style="' . Html::encode($hideStyle) . '"' : '' ?>>
    <label for="<?= Html::encode($id) ?>">
        Серия+Номер паспорта <span class="required req">*</span>
    </label>
    <input type="text"
           id="<?= Html::encode($id) ?>"
           <?= $name ? 'name="' . Html::encode($name) . '"' : '' ?>
           class="<?= Html::encode($inputClass) ?>"
           data-format="by-passport"
           value="<?= Html::encode($value) ?>"
           <?= $required ? 'required' : '' ?>
           placeholder="MP1234567">
    <small class="<?= Html::encode($hintClass) ?>">
        2 латинские буквы + 7 цифр (например, MP1234567)
    </small>
    <span class="<?= Html::encode($errorClass) ?>" id="<?= Html::encode($errorId) ?>"></span>
</div>
