<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\PurchaseOrder $po */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */
use yii\helpers\Html;
$this->title = 'Новая закупка';
?>
<div class="admin-page">
  <div class="page-header mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/procurement">Закупки</a></li>
        <li class="breadcrumb-item active">Новая закупка</li>
      </ol>
    </nav>
    <h1 class="page-title">Новая закупка</h1>
  </div>

  <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/procurement/create">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <div class="row g-4">
      <!-- Left: PO details -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title mb-3">Данные закупки</h6>

            <div class="mb-3">
              <label class="form-label">Номер закупки</label>
              <input type="text" name="purchase_number" class="form-control" value="<?= Html::encode($po->purchase_number) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Поставщик *</label>
              <select name="supplier_id" class="form-select" required>
                <option value="">— выбрать —</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?= $s->id ?>"><?= Html::encode($s->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Тип</label>
              <select name="order_type" class="form-select">
                <?php foreach (\app\backend\modules\procurement\models\PurchaseOrder::getTypes() as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $po->order_type === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Статус</label>
              <select name="status" class="form-select">
                <?php foreach (\app\backend\modules\procurement\models\PurchaseOrder::getStatuses() as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $po->status === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Курс CNY/BYN</label>
              <input type="number" step="0.0001" name="exchange_rate" class="form-control" placeholder="Необязательно">
            </div>

            <div class="mb-3">
              <label class="form-label">Дата заказа</label>
              <input type="date" name="ordered_at" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label">Заказ клиента ID</label>
              <input type="number" name="order_id" class="form-control" placeholder="Необязательно">
            </div>

            <div class="mb-3">
              <label class="form-label">Примечания</label>
              <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: items -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="card-title mb-0">Позиции</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">+ Добавить позицию</button>
            </div>

            <div class="table-responsive">
              <table class="table table-sm" id="itemsTable">
                <thead>
                  <tr>
                    <th style="min-width:200px">Название</th>
                    <th style="width:80px">Размер</th>
                    <th style="width:80px">Кол-во</th>
                    <th style="width:110px">Цена CNY</th>
                    <th style="width:110px">Цена BYN</th>
                    <th style="width:40px"></th>
                  </tr>
                </thead>
                <tbody id="itemsBody">
                  <tr class="item-row">
                    <td><input type="text" name="item_name[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="item_size[]" class="form-control form-control-sm" placeholder="XL"></td>
                    <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="1" min="1"></td>
                    <td><input type="number" step="0.01" name="item_price_cny[]" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" name="item_price_byn[]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem(this)">×</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary">Создать закупку</button>
          <a href="/admin/procurement" class="btn btn-outline-secondary">Отмена</a>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function addItem() {
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
      <td><input type="text" name="item_name[]" class="form-control form-control-sm"></td>
      <td><input type="text" name="item_size[]" class="form-control form-control-sm" placeholder="XL"></td>
      <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="1" min="1"></td>
      <td><input type="number" step="0.01" name="item_price_cny[]" class="form-control form-control-sm"></td>
      <td><input type="number" step="0.01" name="item_price_byn[]" class="form-control form-control-sm"></td>
      <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeItem(this)">×</button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('#itemsBody .item-row');
    if (rows.length > 1) btn.closest('tr').remove();
}
</script>
