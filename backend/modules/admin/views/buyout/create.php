<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Buyout $buyout */
/** @var array $statuses */
/** @var array $sources */

use yii\helpers\Html;

$isNew = $buyout->isNewRecord;
$this->title = $isNew ? 'Новый выкуп' : 'Редактирование выкупа #' . $buyout->id;
$action = $isNew ? '/admin/procurement/buyout/create' : '/admin/procurement/buyout/' . $buyout->id . '/edit';
?>
<style>
.buyout-form-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
.form-group { margin-bottom:14px; }
.form-label { display:block; font-size:0.78rem; font-weight:600; color:#6b7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
.form-control { width:100%; height:36px; padding:0 10px; border:1px solid #e5e7eb; border-radius:8px;
    font-size:0.875rem; background:#fff; color:#111; box-sizing:border-box; }
textarea.form-control { height:auto; padding:8px 10px; resize:vertical; }
.form-control:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.parse-row { display:flex; gap:8px; }
.parse-row .form-control { flex:1; }
</style>

<div class="crm-wrap">
<div class="crm-topbar">
    <div class="crm-topbar-left">
        <a href="/admin/procurement/buyouts" class="admin-btn admin-btn-sm admin-btn-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="crm-order-num"><?= Html::encode($this->title) ?></span>
    </div>
    <div class="crm-topbar-actions">
        <button type="submit" form="buyout-form" class="admin-btn admin-btn-primary">
            <i class="bi bi-floppy"></i> <?= $isNew ? 'Создать' : 'Сохранить' ?>
        </button>
    </div>
</div>

<form id="buyout-form" method="POST" action="<?= Html::encode($action) ?>">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
    <?php if (!$isNew): ?><input type="hidden" name="_method" value="POST"><?php endif; ?>

<div class="buyout-form-grid" style="padding:16px">

<!-- Main column -->
<div style="display:flex;flex-direction:column;gap:16px">

    <!-- URL Parse -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-link-45deg"></i> URL источника</h3></div>
        <div class="crm-card-body">
            <div class="parse-row">
                <input type="url" id="source-url-input" name="source_url" class="form-control"
                       placeholder="https://poizon.com/... или lamoda.by/..."
                       value="<?= Html::encode($buyout->source_url ?? '') ?>">
                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="parseUrl()">
                    <i class="bi bi-magic"></i> Распарсить
                </button>
            </div>
            <div id="parse-status" style="font-size:0.78rem;color:#6b7280;margin-top:6px"></div>
        </div>
    </div>

    <!-- Product info -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-box-seam"></i> Товар</h3></div>
        <div class="crm-card-body">
            <div id="product-preview" style="<?= empty($snap['image'] ?? '') ? 'display:none' : '' ?>;display:flex;gap:12px;align-items:center;margin-bottom:14px">
                <img id="product-img" src="<?= Html::encode($snap['image'] ?? '') ?>" alt=""
                     style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
                <div>
                    <div id="product-name-preview" style="font-weight:600;font-size:0.875rem"></div>
                    <div id="product-brand-preview" style="font-size:0.78rem;color:#6b7280"></div>
                </div>
            </div>
            <input type="hidden" name="product_snapshot" id="product-snapshot-input"
                   value="<?= Html::encode(is_array($buyout->product_snapshot) ? json_encode($buyout->product_snapshot) : ($buyout->product_snapshot ?? '')) ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Источник *</label>
                    <select name="source" class="form-control" id="source-select">
                        <?php foreach ($sources as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($buyout->source ?? '') === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Внешний ID</label>
                    <input type="text" name="external_id" class="form-control" value="<?= Html::encode($buyout->external_id ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Размер</label>
                    <input type="text" name="size" class="form-control" value="<?= Html::encode($buyout->size ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Financials -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-cash-stack"></i> Финансы</h3></div>
        <div class="crm-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Цена ед. (источник)</label>
                    <input type="number" step="0.01" name="unit_cost_source" class="form-control" id="fin-cost-source"
                           value="<?= $buyout->unit_cost_source ?? '' ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Валюта</label>
                    <select name="source_currency" class="form-control" id="fin-currency">
                        <?php foreach (['CNY','USD','EUR','RUB','BYN'] as $c): ?>
                        <option <?= ($buyout->source_currency ?? 'CNY') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Курс → BYN</label>
                    <input type="number" step="0.0001" name="exchange_rate" class="form-control" id="fin-rate"
                           value="<?= $buyout->exchange_rate ?? '' ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Цена ед. (BYN)</label>
                    <input type="number" step="0.01" name="unit_cost_byn" class="form-control" id="fin-cost-byn"
                           value="<?= $buyout->unit_cost_byn ?? '' ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Кол-во *</label>
                    <input type="number" min="1" name="qty" class="form-control" id="fin-qty"
                           value="<?= (int)($buyout->qty ?? 1) ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Доставка (BYN)</label>
                    <input type="number" step="0.01" name="shipping_cost" class="form-control" id="fin-ship"
                           value="<?= $buyout->shipping_cost ?? 0 ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Комиссии (BYN)</label>
                    <input type="number" step="0.01" name="fees" class="form-control" id="fin-fees"
                           value="<?= $buyout->fees ?? 0 ?>" oninput="recalcTotal()">
                </div>
                <div class="form-group" style="grid-column:2/-1">
                    <label class="form-label">Итого (BYN)</label>
                    <input type="number" step="0.01" name="total_cost_byn" class="form-control" id="fin-total"
                           value="<?= $buyout->total_cost_byn ?? 0 ?>" readonly style="background:#f9fafb;font-weight:700">
                </div>
            </div>
        </div>
    </div>

    <!-- Linked orders (create mode) -->
    <?php if ($isNew): ?>
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-link-45deg"></i> Привязать заказы</h3></div>
        <div class="crm-card-body">
            <div style="display:flex;gap:8px">
                <input type="text" id="order-id-input" placeholder="ID заказа" class="form-control" style="flex:1">
                <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" onclick="addOrder()">Добавить</button>
            </div>
            <div id="order-list" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-sticky"></i> Заметки</h3></div>
        <div class="crm-card-body">
            <textarea name="notes" class="form-control" rows="3"><?= Html::encode($buyout->notes ?? '') ?></textarea>
        </div>
    </div>

</div><!-- /left -->

<!-- Sidebar -->
<div style="display:flex;flex-direction:column;gap:14px">

    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-flag"></i> Статус</h3></div>
        <div class="crm-card-body">
            <select name="status" class="form-control">
                <?php foreach ($statuses as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($buyout->status ?? 'draft') === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-truck"></i> Трекинг</h3></div>
        <div class="crm-card-body">
            <div class="form-group">
                <label class="form-label">Трек-номер</label>
                <input type="text" name="tracking_number" class="form-control" value="<?= Html::encode($buyout->tracking_number ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Перевозчик</label>
                <input type="text" name="carrier" class="form-control" value="<?= Html::encode($buyout->carrier ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-calendar3"></i> Даты</h3></div>
        <div class="crm-card-body">
            <div class="form-group">
                <label class="form-label">Дата заказа</label>
                <input type="datetime-local" name="ordered_at" class="form-control"
                       value="<?= $buyout->ordered_at ? date('Y-m-d\TH:i', strtotime($buyout->ordered_at)) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Дата прибытия</label>
                <input type="datetime-local" name="arrived_at" class="form-control"
                       value="<?= $buyout->arrived_at ? date('Y-m-d\TH:i', strtotime($buyout->arrived_at)) : '' ?>">
            </div>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-receipt"></i> Чек / инвойс</h3></div>
        <div class="crm-card-body">
            <div class="form-group">
                <label class="form-label">URL или путь</label>
                <input type="text" name="receipt_url" class="form-control" value="<?= Html::encode($buyout->receipt_url ?? '') ?>">
            </div>
        </div>
    </div>

</div><!-- /sidebar -->
</div><!-- /grid -->
</form>
</div><!-- /crm-wrap -->

<script>
function recalcTotal() {
    const costByn = parseFloat(document.getElementById('fin-cost-byn').value) || 0;
    const costSrc = parseFloat(document.getElementById('fin-cost-source').value) || 0;
    const rate    = parseFloat(document.getElementById('fin-rate').value) || 0;
    const qty     = parseInt(document.getElementById('fin-qty').value) || 1;
    const ship    = parseFloat(document.getElementById('fin-ship').value) || 0;
    const fees    = parseFloat(document.getElementById('fin-fees').value) || 0;

    let unitByn = costByn;
    if (!unitByn && costSrc && rate) unitByn = costSrc * rate;

    document.getElementById('fin-cost-byn').value = unitByn ? unitByn.toFixed(2) : '';
    document.getElementById('fin-total').value = (unitByn * qty + ship + fees).toFixed(2);
}

function parseUrl() {
    const url = document.getElementById('source-url-input').value.trim();
    if (!url) return;
    const status = document.getElementById('parse-status');
    status.textContent = 'Парсим...';

    fetch('/admin/procurement/buyout/parse-url', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':yii.getCsrfToken()},
        body: 'url=' + encodeURIComponent(url)
    }).then(r=>r.json()).then(d=>{
        if (!d.success) { status.textContent = 'Ошибка: ' + (d.message||''); return; }
        const data = d.data;
        status.textContent = 'Готово';

        // Fill source select
        if (data.source) document.getElementById('source-select').value = data.source;
        if (data.external_id) document.querySelector('[name=external_id]').value = data.external_id;

        // Store snapshot
        document.getElementById('product-snapshot-input').value = JSON.stringify({
            name: data.name, brand: data.brand, image: data.image, images: data.images
        });

        // Show preview
        const prev = document.getElementById('product-preview');
        if (data.image) {
            document.getElementById('product-img').src = data.image;
            prev.style.display = 'flex';
        }
        document.getElementById('product-name-preview').textContent = data.name || '';
        document.getElementById('product-brand-preview').textContent = data.brand || '';

        // Fill price
        if (data.price) {
            document.getElementById('fin-cost-source').value = data.price;
            const cur = data.currency || 'CNY';
            document.getElementById('fin-currency').value = cur;
            if (cur === 'BYN') {
                document.getElementById('fin-cost-byn').value = data.price;
            }
            recalcTotal();
        }
    }).catch(()=>{ status.textContent = 'Сетевая ошибка'; });
}

// Order linking in create mode
const linkedOrders = [];
function addOrder() {
    const val = parseInt(document.getElementById('order-id-input').value.trim());
    if (!val || linkedOrders.includes(val)) return;
    linkedOrders.push(val);
    refreshOrderList();
    document.getElementById('order-id-input').value = '';
}
function removeOrder(id) {
    const idx = linkedOrders.indexOf(id);
    if (idx > -1) linkedOrders.splice(idx, 1);
    refreshOrderList();
}
function refreshOrderList() {
    const container = document.getElementById('order-list');
    container.innerHTML = linkedOrders.map(id =>
        `<span style="background:#dbeafe;border:1px solid #bfdbfe;padding:3px 9px;border-radius:999px;font-size:0.78rem;display:inline-flex;gap:6px;align-items:center">
            #${id}
            <input type="hidden" name="order_ids[]" value="${id}">
            <button type="button" onclick="removeOrder(${id})" style="border:none;background:none;cursor:pointer;font-size:0.9em;color:#374151">&times;</button>
         </span>`
    ).join('');
}
</script>
<?php
// Snap var reuse
$snap = is_array($buyout->product_snapshot)
    ? $buyout->product_snapshot
    : (array)json_decode((string)$buyout->product_snapshot, true);
?>
