<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Receiving $receiving */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\procurement\models\ReceivingExpense;

$this->title = 'Новая приёмка';
?>
<style>
.wizard-steps{display:flex;gap:0;margin-bottom:24px;background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
.wizard-step{flex:1;padding:14px 20px;text-align:center;font-size:.8125rem;font-weight:600;color:#9ca3af;border-right:1px solid #e5e7eb;cursor:pointer;transition:all .2s}
.wizard-step:last-child{border-right:none}
.wizard-step.active{background:var(--admin-primary,#2563eb);color:#fff}
.wizard-step.done{background:#f0fdf4;color:#059669}
.wizard-panel{display:none}
.wizard-panel.active{display:block}
.form-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.form-group{flex:1;min-width:180px}
.form-label{font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:5px}
.form-control{width:100%;height:36px;border:1.5px solid #e5e7eb;border-radius:8px;padding:0 10px;font-size:.8125rem;outline:none;transition:border-color .15s}
.form-control:focus{border-color:var(--admin-primary,#2563eb)}
textarea.form-control{height:80px;padding:8px 10px;resize:vertical}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.btn-primary{background:var(--admin-primary,#2563eb);color:#fff}.btn-primary:hover{background:#1d4ed8;color:#fff}
.btn-outline{background:#fff;color:#374151;border:1.5px solid #d1d5db}.btn-outline:hover{background:#f3f4f6}
.item-row{display:flex;gap:8px;align-items:flex-end;padding:10px;background:#f9fafb;border-radius:8px;margin-bottom:8px;flex-wrap:wrap}
.exp-row{display:flex;gap:8px;align-items:flex-end;padding:10px;background:#f9fafb;border-radius:8px;margin-bottom:8px;flex-wrap:wrap}
.form-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:20px 24px;margin-bottom:16px}
.ri-input{height:32px;border:1.5px solid #e5e7eb;border-radius:8px;padding:0 8px;font-size:.8125rem;outline:none;width:80px;text-align:right}
</style>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <a href="<?= Url::to(['/admin/receiving']) ?>" class="btn btn-outline" style="padding:5px 10px;font-size:.78rem">
        <i class="bi bi-arrow-left"></i> Назад
    </a>
    <h1 style="font-size:1.2rem;font-weight:800;margin:0">Новая приёмка</h1>
</div>

<!-- Wizard steps -->
<div class="wizard-steps">
    <div class="wizard-step active" id="step-tab-1" onclick="rcvGoStep(1)">
        <i class="bi bi-building"></i> 1. Поставщик и даты
    </div>
    <div class="wizard-step" id="step-tab-2" onclick="rcvGoStep(2)">
        <i class="bi bi-boxes"></i> 2. Товары
    </div>
    <div class="wizard-step" id="step-tab-3" onclick="rcvGoStep(3)">
        <i class="bi bi-receipt"></i> 3. Расходы (optional)
    </div>
</div>

<form method="post" id="createForm" action="<?= Url::to(['/admin/receiving/create']) ?>">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <!-- Step 1: Supplier + Dates -->
    <div class="wizard-panel active form-card" id="step-1">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Поставщик</label>
                <select name="supplier_id" class="form-control">
                    <option value="">— Без поставщика —</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s->id ?>"><?= htmlspecialchars($s->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Ожидаемая дата</label>
                <input type="date" name="expected_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label class="form-label">Примечания</label>
                <textarea name="notes" class="form-control" placeholder="Дополнительная информация о поставке…"></textarea>
            </div>
        </div>
        <div style="margin-top:8px">
            <button type="button" class="btn btn-primary" onclick="rcvGoStep(2)">
                Далее: Товары <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Step 2: Items -->
    <div class="wizard-panel form-card" id="step-2">
        <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
            <strong style="font-size:.9rem">Ожидаемые товары</strong>
            <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:.78rem" onclick="rcvAddItemRow()">
                <i class="bi bi-plus"></i> Добавить позицию
            </button>
        </div>
        <div id="itemsContainer">
            <!-- Item rows added dynamically -->
        </div>
        <div style="display:flex;gap:8px;margin-top:12px">
            <button type="button" class="btn btn-outline" onclick="rcvGoStep(1)">
                <i class="bi bi-arrow-left"></i> Назад
            </button>
            <button type="button" class="btn btn-primary" onclick="rcvGoStep(3)">
                Далее: Расходы <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Step 3: Expenses -->
    <div class="wizard-panel form-card" id="step-3">
        <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
            <strong style="font-size:.9rem">Расходы (таможня, доставка, страховка…)</strong>
            <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:.78rem" onclick="rcvAddExpenseRow()">
                <i class="bi bi-plus"></i> Добавить расход
            </button>
        </div>
        <div id="expensesContainer"></div>
        <p style="font-size:.78rem;color:#9ca3af;margin:8px 0 16px">
            Расходы будут автоматически распределены между позициями по выбранному методу.
        </p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
            <button type="button" class="btn btn-outline" onclick="rcvGoStep(2)">
                <i class="bi bi-arrow-left"></i> Назад
            </button>
            <button type="submit" class="btn btn-primary" style="background:#059669">
                <i class="bi bi-check-lg"></i> Создать приёмку
            </button>
        </div>
    </div>
</form>

<script>
const PRODUCTS_API = '<?= Url::to(['/admin/receiving/products']) ?>';
let itemIdx = 0, expIdx = 0;

function rcvGoStep(n) {
    [1,2,3].forEach(i => {
        document.getElementById('step-' + i).classList.toggle('active', i === n);
        const tab = document.getElementById('step-tab-' + i);
        tab.classList.toggle('active', i === n);
        if (i < n) tab.classList.add('done'); else tab.classList.remove('done');
    });
}

function rcvAddItemRow() {
    const idx = itemIdx++;
    const html = `<div class="item-row" id="item-wizard-${idx}">
        <div style="flex:2;min-width:200px;position:relative">
            <label class="form-label">Товар</label>
            <input type="text" class="form-control" placeholder="Поиск по названию или SKU…"
                   oninput="rcvSearchWizard(this, ${idx})" autocomplete="off">
            <div id="sr-${idx}" style="display:none;position:absolute;z-index:200;background:#fff;border:1.5px solid #e5e7eb;border-radius:8px;max-height:180px;overflow-y:auto;width:100%;box-shadow:0 4px 12px rgba(0,0,0,.1)"></div>
            <input type="hidden" name="items[${idx}][product_id]" id="pid-${idx}">
            <select name="items[${idx}][size_id]" id="siz-${idx}" class="form-control" style="margin-top:6px;display:none">
                <option value="">— Размер —</option>
            </select>
        </div>
        <div style="flex:0 0 70px">
            <label class="form-label">Кол-во</label>
            <input type="number" name="items[${idx}][qty_expected]" class="ri-input" value="1" min="1" style="width:100%">
        </div>
        <div style="flex:0 0 90px">
            <label class="form-label">Цена</label>
            <input type="number" name="items[${idx}][unit_cost_source]" class="ri-input" value="0" step="0.01" id="cost-${idx}" style="width:100%">
        </div>
        <div style="flex:0 0 65px">
            <label class="form-label">Валюта</label>
            <select name="items[${idx}][source_currency]" class="form-control" style="padding:0 6px">
                <option>BYN</option><option>USD</option><option>EUR</option><option selected>CNY</option>
            </select>
        </div>
        <div style="flex:0 0 70px">
            <label class="form-label">Курс</label>
            <input type="number" name="items[${idx}][exchange_rate]" class="ri-input" value="1" step="0.0001" style="width:100%">
        </div>
        <button type="button" class="btn btn-outline" style="padding:4px 9px;color:#dc2626;border-color:#dc2626;align-self:flex-end"
                onclick="document.getElementById('item-wizard-${idx}').remove()">
            <i class="bi bi-trash"></i>
        </button>
    </div>`;
    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
}

let _wt = {};
function rcvSearchWizard(input, idx) {
    clearTimeout(_wt[idx]);
    const q = input.value;
    if (!q) { document.getElementById('sr-' + idx).style.display = 'none'; return; }
    _wt[idx] = setTimeout(() => {
        fetch(PRODUCTS_API + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(list => {
                const box = document.getElementById('sr-' + idx);
                if (!list.length) { box.style.display = 'none'; return; }
                box.innerHTML = list.map(p =>
                    `<div style="padding:7px 10px;cursor:pointer;font-size:.8125rem"
                         onmousedown="rcvSelectWizard(${idx},'${p.name.replace(/'/g,"\\'")}',${p.id},${JSON.stringify(p.sizes)},${p.price})"
                         onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                        <strong>${p.name}</strong> ${p.sku ? '<span style=\'color:#9ca3af;font-size:.75rem\'>' + p.sku + '</span>' : ''}
                     </div>`
                ).join('');
                box.style.display = 'block';
            });
    }, 250);
}

function rcvSelectWizard(idx, name, id, sizes, price) {
    document.getElementById('pid-' + idx).value = id;
    document.querySelector(`#item-wizard-${idx} input[type=text]`).value = name;
    document.getElementById('sr-' + idx).style.display = 'none';
    document.getElementById(`cost-${idx}`).value = price || 0;
    const siz = document.getElementById('siz-' + idx);
    siz.innerHTML = '<option value="">— Размер —</option>';
    if (sizes && sizes.length) {
        sizes.forEach(s => { siz.innerHTML += `<option value="${s.id}">${s.size} (${s.price} BYN)</option>`; });
        siz.style.display = 'block';
        document.getElementById(`cost-${idx}`).value = sizes[0].price || price || 0;
    } else {
        siz.style.display = 'none';
    }
}

function rcvAddExpenseRow() {
    const idx = expIdx++;
    const typeOptions = <?= json_encode(ReceivingExpense::getTypes()) ?>;
    const distOptions = <?= json_encode(ReceivingExpense::getDistributionMethods()) ?>;
    const typeHtml = Object.entries(typeOptions).map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
    const distHtml = Object.entries(distOptions).map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
    const html = `<div class="exp-row" id="exp-wizard-${idx}">
        <div style="flex:1;min-width:120px">
            <label class="form-label">Тип</label>
            <select name="expenses[${idx}][type]" class="form-control">${typeHtml}</select>
        </div>
        <div style="flex:0 0 90px">
            <label class="form-label">Сумма</label>
            <input type="number" name="expenses[${idx}][amount]" class="ri-input" step="0.01" placeholder="0.00" style="width:100%">
        </div>
        <div style="flex:0 0 65px">
            <label class="form-label">Валюта</label>
            <select name="expenses[${idx}][currency]" class="form-control" style="padding:0 6px">
                <option selected>BYN</option><option>USD</option><option>EUR</option>
            </select>
        </div>
        <div style="flex:0 0 70px">
            <label class="form-label">Курс</label>
            <input type="number" name="expenses[${idx}][exchange_rate]" class="ri-input" value="1" step="0.0001" style="width:100%">
        </div>
        <div style="flex:1;min-width:130px">
            <label class="form-label">Распределение</label>
            <select name="expenses[${idx}][distribution_method]" class="form-control">${distHtml}</select>
        </div>
        <div style="flex:1;min-width:140px">
            <label class="form-label">Примечания</label>
            <input type="text" name="expenses[${idx}][notes]" class="form-control" placeholder="Опционально">
        </div>
        <button type="button" class="btn btn-outline" style="padding:4px 9px;color:#dc2626;border-color:#dc2626;align-self:flex-end"
                onclick="document.getElementById('exp-wizard-${idx}').remove()">
            <i class="bi bi-trash"></i>
        </button>
    </div>`;
    document.getElementById('expensesContainer').insertAdjacentHTML('beforeend', html);
}

// Add one item row by default
rcvAddItemRow();
</script>
