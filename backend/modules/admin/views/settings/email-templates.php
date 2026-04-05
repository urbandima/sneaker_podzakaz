<?php
use yii\helpers\Html;
$this->title = 'Email шаблоны';
$events = [
    'confirmed' => 'Заказ подтверждён',
    'paid' => 'Оплата получена',
    'ordered_poizon' => 'Заказан на Poizon',
    'at_warehouse' => 'На складе',
    'shipped_local' => 'Передан в доставку РБ',
    'completed' => 'Заказ завершён',
];
$templates = $templates ?? [];
?>
<div class="admin-header">
    <h1 class="admin-header-title">Email шаблоны</h1>
    <a href="/admin/settings" class="admin-btn admin-btn-secondary"><i class="bi bi-arrow-left"></i> Настройки</a>
</div>
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
    <div class="admin-form-group" style="margin-top:1rem;">
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

<style>
.admin-form-input {
    width: 100%;
    padding: 0.65rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg, #fff);
    color: var(--admin-text);
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.admin-form-input:focus {
    outline: none;
    border-color: var(--admin-accent, #2563eb);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.admin-form-group { margin-bottom: 0.75rem; }
.admin-form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.admin-btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
</style>

<script>
function saveTemplate(key) {
    fetch('/admin/settings/save-email-template', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
        body: JSON.stringify({key, subject: document.getElementById('subject_'+key).value, body: document.getElementById('body_'+key).value})
    }).then(r=>r.json()).then(d=>alert(d.success?'Сохранено':'Ошибка: '+(d.message||'')));
}
function testEmail(key) {
    fetch('/admin/settings/test-email', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
        body: JSON.stringify({key})
    }).then(r=>r.json()).then(d=>alert(d.message||'Отправлено'));
}
</script>
