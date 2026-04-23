<?php
use yii\helpers\Html;
$this->title = 'Email шаблоны';
$events = [
    'confirmed_and_paid' => 'Подтвержден и оплачен',
    'paid' => 'Оплата получена',
    'ordered_poizon' => 'Заказан на Poizon',
    'at_warehouse' => 'На складе',
    'shipped_local' => 'Передан в доставку РБ',
    'completed' => 'Заказ завершён',
];
$templates = $templates ?? [];
$this->params['headerActions'][] = '<a href="/admin/settings" class="admin-btn admin-btn-secondary"><i class="bi bi-arrow-left"></i> Настройки</a>';
?>
<?php foreach ($events as $key => $label):
    $tpl = $templates[$key] ?? [];
?>
<div class="admin-card" style="margin-bottom:1rem">
    <div class="admin-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3 class="admin-card-title"><?= Html::encode($label) ?></h3>
        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="testEmail('<?= $key ?>')">
            <i class="bi bi-send"></i> Тест
        </button>
    </div>
    <div class="admin-form-group mt-4">
        <label class="admin-form-label">Тема письма</label>
        <input type="text" class="admin-form-input" id="subject_<?= $key ?>" value="<?= Html::encode($tpl['subject'] ?? '') ?>" placeholder="Тема...">
    </div>
    <div class="admin-form-group">
        <label class="admin-form-label">Текст (HTML)</label>
        <textarea class="admin-form-input" id="body_<?= $key ?>" rows="5" style="font-family:monospace"><?= Html::encode($tpl['body'] ?? '') ?></textarea>
    </div>
    <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="saveTemplate('<?= $key ?>')">
        <i class="bi bi-check-circle"></i> Сохранить
    </button>
</div>
<?php endforeach ?>

