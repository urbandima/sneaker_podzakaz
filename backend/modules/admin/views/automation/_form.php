<?php
use yii\helpers\Html;
use yii\helpers\Url;

$isNew = $model->isNewRecord;
$this->title = $isNew ? 'Создать триггер' : 'Редактировать: ' . Html::encode($model->name);

$conditions  = $model->getConditionsArray();
$actions     = $model->getActionsArray();

$actionTypeOptions = [];
foreach ($actionTypes as $k => $v) {
    $actionTypeOptions[] = "<option value=\"$k\">$v</option>";
}
$actionTypeOptionsHtml = implode('', $actionTypeOptions);
?>

<div style="max-width:900px">

<form method="POST" action="<?= $isNew ? Url::to(['/admin/settings/triggers/create']) : Url::to(['/admin/settings/triggers/' . $model->id . '/edit']) ?>">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <!-- Basic info -->
    <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> Основное</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <label class="admin-label">Название <span style="color:red">*</span></label>
                    <input type="text" name="name" class="admin-input" required
                           value="<?= Html::encode($model->name) ?>" placeholder="SMS при создании заказа">
                </div>
                <div>
                    <label class="admin-label">Событие <span style="color:red">*</span></label>
                    <select name="event_code" class="admin-select" required>
                        <option value="">— выберите —</option>
                        <?php foreach ($eventCodes as $code => $label): ?>
                        <option value="<?= $code ?>" <?= $model->event_code === $code ? 'selected' : '' ?>>
                            <?= Html::encode($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="admin-label">Приоритет</label>
                    <input type="number" name="priority" class="admin-input" value="<?= (int)$model->priority ?>" min="0" max="999">
                    <div style="font-size:12px;color:var(--admin-text-secondary);margin-top:4px">Меньше — выполняется первым</div>
                </div>
                <div>
                    <label class="admin-label">Активен</label>
                    <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" <?= $model->is_active ? 'checked' : '' ?>>
                        Включён
                    </label>
                </div>
            </div>
            <div style="margin-top:16px">
                <label class="admin-label">Описание</label>
                <input type="text" name="description" class="admin-input"
                       value="<?= Html::encode($model->description) ?>" placeholder="Необязательное описание">
            </div>
        </div>
    </div>

    <!-- Conditions -->
    <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-funnel"></i> Условия</h2>
            <span style="font-size:13px;color:var(--admin-text-secondary)">Все условия AND (пустой список = всегда срабатывает)</span>
        </div>
        <div class="admin-card-body">
            <div id="conditions-list">
                <?php foreach ($conditions as $i => $cond): ?>
                <div class="condition-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                    <input type="text" name="cond_field[]" class="admin-input" style="flex:2"
                           value="<?= Html::encode($cond['field'] ?? '') ?>" placeholder="order.status">
                    <select name="cond_operator[]" class="admin-select" style="flex:1.5">
                        <?php foreach ($operators as $op => $opLabel): ?>
                        <option value="<?= $op ?>" <?= ($cond['operator'] ?? '') === $op ? 'selected' : '' ?>>
                            <?= Html::encode($opLabel) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="cond_value[]" class="admin-input" style="flex:2"
                           value="<?= Html::encode($cond['value'] ?? '') ?>" placeholder="paid">
                    <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="removeRow(this)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="addCondition()">
                <i class="bi bi-plus"></i> Добавить условие
            </button>
            <div style="margin-top:12px;font-size:12px;color:var(--admin-text-secondary)">
                Поля: <code>order.status</code>, <code>order.total</code>, <code>order.customer_id</code>,
                <code>customer.phone</code>, <code>customer.bonus_balance</code> и др.
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-lightning-charge"></i> Действия</h2>
        </div>
        <div class="admin-card-body">
            <div id="actions-list">
                <?php foreach ($actions as $i => $act): ?>
                <?php
                    $actType = $act['type'] ?? '';
                    $params = $act;
                    unset($params['type']);
                    $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
                ?>
                <div class="action-row" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
                    <select name="act_type[]" class="admin-select" style="flex:1.5" onchange="onActionTypeChange(this)">
                        <?php foreach ($actionTypes as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $actType === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="flex:3">
                        <textarea name="act_params[]" class="admin-input" rows="2"
                                  style="font-family:monospace;font-size:12px"
                                  placeholder='{"template":"order_created","to":"client"}'><?= Html::encode($paramsJson) ?></textarea>
                        <div class="action-hint" style="font-size:11px;color:var(--admin-text-secondary);margin-top:2px">
                            <?= automationActionHint($actType) ?>
                        </div>
                    </div>
                    <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="removeRow(this)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="addAction()">
                <i class="bi bi-plus"></i> Добавить действие
            </button>
        </div>
    </div>

    <div style="display:flex;gap:12px">
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-check-lg"></i> <?= $isNew ? 'Создать' : 'Сохранить' ?>
        </button>
        <?= Html::a('<i class="bi bi-x"></i> Отмена',
            Url::to(['/admin/settings/triggers']),
            ['class' => 'admin-btn admin-btn-secondary']
        ) ?>
    </div>
</form>
</div>

<?php
function automationActionHint(string $type): string {
    $hints = [
        'send_sms'       => 'Параметры: template (код шаблона), to (client|admin|custom), phone (если custom)',
        'send_email'     => 'Параметры: subject, body, to (email или "client")',
        'send_telegram'  => 'Параметры: message (текст, поддерживает {order_number}, {total} и др.)',
        'sync_moysklad'  => 'Без параметров — отправляет/обновляет заказ в МойСклад',
        'change_status'  => 'Параметры: status (новый статус заказа)',
        'assign_tag'     => 'Параметры: tag (название тега)',
        'earn_bonus'     => 'Параметры: points (число), description',
        'create_customer'=> 'Без параметров',
        'send_to_dp'     => 'Без параметров',
        'notify_admin'   => 'Параметры: message (текст уведомления)',
        'webhook'        => 'Параметры: url, method (GET|POST), body (шаблон JSON)',
    ];
    return $hints[$type] ?? '';
}
?>

<script>
var operatorOptions = <?= json_encode(array_map(fn($k,$v) => "<option value=\"$k\">$v</option>", array_keys($operators), array_values($operators))) ?>;
var actionHints = <?= json_encode([
    'send_sms'       => 'Параметры: template (код шаблона), to (client|admin|custom), phone (если custom)',
    'send_email'     => 'Параметры: subject, body, to (email или "client")',
    'send_telegram'  => 'Параметры: message (текст, поддерживает {order_number}, {total} и др.)',
    'sync_moysklad'  => 'Без параметров — отправляет/обновляет заказ в МойСклад',
    'change_status'  => 'Параметры: status (новый статус заказа)',
    'assign_tag'     => 'Параметры: tag (название тега)',
    'earn_bonus'     => 'Параметры: points (число), description',
    'create_customer'=> 'Без параметров',
    'send_to_dp'     => 'Без параметров',
    'notify_admin'   => 'Параметры: message (текст уведомления)',
    'webhook'        => 'Параметры: url, method (GET|POST), body (шаблон JSON)',
]) ?>;

function addCondition() {
    var row = document.createElement('div');
    row.className = 'condition-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
    row.innerHTML = `
        <input type="text" name="cond_field[]" class="admin-input" style="flex:2" placeholder="order.status">
        <select name="cond_operator[]" class="admin-select" style="flex:1.5"><?= implode('', array_map(fn($k,$v) => "<option value=\"$k\">$v</option>", array_keys($operators), array_values($operators))) ?></select>
        <input type="text" name="cond_value[]" class="admin-input" style="flex:2" placeholder="paid">
        <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
    `;
    document.getElementById('conditions-list').appendChild(row);
}

function addAction() {
    var firstKey = <?= json_encode(array_key_first($actionTypes)) ?>;
    var row = document.createElement('div');
    row.className = 'action-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:flex-start;margin-bottom:8px';
    var opts = <?= json_encode(implode('', array_map(fn($k,$v) => "<option value=\"$k\">$v</option>", array_keys($actionTypes), array_values($actionTypes)))) ?>;
    row.innerHTML = `
        <select name="act_type[]" class="admin-select" style="flex:1.5" onchange="onActionTypeChange(this)">${opts}</select>
        <div style="flex:3">
            <textarea name="act_params[]" class="admin-input" rows="2" style="font-family:monospace;font-size:12px" placeholder='{"template":"order_created","to":"client"}'>{}</textarea>
            <div class="action-hint" style="font-size:11px;color:var(--admin-text-secondary);margin-top:2px">${actionHints[firstKey]||''}</div>
        </div>
        <button type="button" class="admin-btn admin-btn-sm admin-btn-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
    `;
    document.getElementById('actions-list').appendChild(row);
}

function onActionTypeChange(select) {
    var row = select.closest('.action-row');
    var hint = row.querySelector('.action-hint');
    if (hint) hint.textContent = actionHints[select.value] || '';
}

function removeRow(btn) {
    btn.closest('.condition-row, .action-row').remove();
}
</script>
