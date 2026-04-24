<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\PurchaseOrder $po */
/** @var app\backend\modules\procurement\models\SupplierReturn $returnModel */
/** @var array $reasons */
use yii\helpers\Html;
$this->title = 'Возврат поставщику';
?>
<div class="admin-page">
  <div class="page-header mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/procurement">Закупки</a></li>
        <li class="breadcrumb-item"><a href="/admin/procurement/view/<?= $po->id ?>"><?= Html::encode($po->purchase_number) ?></a></li>
        <li class="breadcrumb-item active">Возврат поставщику</li>
      </ol>
    </nav>
    <h1 class="page-title">Возврат поставщику</h1>
    <div class="text-muted">Закупка: <strong><?= Html::encode($po->purchase_number) ?></strong> — <?= Html::encode($po->supplier->name ?? '') ?></div>
  </div>

  <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/procurement/create-return/<?= $po->id ?>">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <div class="row g-4">
      <!-- Return info -->
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title mb-3">Данные возврата</h6>

            <div class="mb-3">
              <label class="form-label">Номер возврата</label>
              <input type="text" name="return_number" class="form-control" value="<?= Html::encode($returnModel->return_number) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Общая причина</label>
              <select name="reason" class="form-select">
                <option value="">— выбрать —</option>
                <?php foreach ($reasons as $k => $v): ?>
                  <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Примечания</label>
              <textarea name="notes" class="form-control" rows="4"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Items -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="card-title mb-0">Возвращаемые позиции</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addReturnItem()">+ Добавить</button>
            </div>

            <!-- Quick-add from received items -->
            <?php if ($po->items): ?>
            <div class="mb-3">
              <label class="form-label text-muted" style="font-size:12px">Быстрое добавление из полученных позиций:</label>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($po->items as $item): ?>
                  <?php if ($item->received_quantity > 0): ?>
                  <button type="button" class="btn btn-xs btn-outline-secondary"
                    onclick="addItemFromPO(<?= $item->id ?>, <?= json_encode($item->product_name) ?>, <?= json_encode($item->size) ?>, <?= $item->received_quantity ?>, <?= $item->price_byn ?? 0 ?>)">
                    <?= Html::encode($item->product_name) ?> <?= $item->size ? '/ '.$item->size : '' ?>
                  </button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-sm" id="returnItemsTable">
                <thead>
                  <tr>
                    <th style="min-width:180px">Название</th>
                    <th style="width:70px">Размер</th>
                    <th style="width:70px">Кол-во</th>
                    <th style="width:110px">Цена BYN</th>
                    <th>Причина</th>
                    <th style="width:40px"></th>
                  </tr>
                </thead>
                <tbody id="returnItemsBody">
                  <tr class="return-item-row">
                    <td><input type="text" name="item_name[]" class="form-control form-control-sm"><input type="hidden" name="item_poi_id[]" value=""></td>
                    <td><input type="text" name="item_size[]" class="form-control form-control-sm"></td>
                    <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="1" min="1"></td>
                    <td><input type="number" step="0.01" name="item_price_byn[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="item_reason[]" class="form-control form-control-sm" placeholder="Причина"></td>
                    <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeReturnItem(this)">×</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary">Создать возврат</button>
          <a href="/admin/procurement/view/<?= $po->id ?>" class="btn btn-outline-secondary">Отмена</a>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function addReturnItem() {
    const row = document.createElement('tr');
    row.className = 'return-item-row';
    row.innerHTML = `
      <td><input type="text" name="item_name[]" class="form-control form-control-sm"><input type="hidden" name="item_poi_id[]" value=""></td>
      <td><input type="text" name="item_size[]" class="form-control form-control-sm"></td>
      <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="1" min="1"></td>
      <td><input type="number" step="0.01" name="item_price_byn[]" class="form-control form-control-sm"></td>
      <td><input type="text" name="item_reason[]" class="form-control form-control-sm" placeholder="Причина"></td>
      <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeReturnItem(this)">×</button></td>
    `;
    document.getElementById('returnItemsBody').appendChild(row);
}

function addItemFromPO(poiId, name, size, qty, price) {
    const body  = document.getElementById('returnItemsBody');
    const rows  = body.querySelectorAll('.return-item-row');
    const last  = rows[rows.length - 1];
    const nameInput = last.querySelector('[name="item_name[]"]');
    // If last row is empty, fill it; otherwise add new
    if (!nameInput.value) {
        last.querySelector('[name="item_poi_id[]"]').value      = poiId;
        nameInput.value                                          = name;
        last.querySelector('[name="item_size[]"]').value         = size || '';
        last.querySelector('[name="item_qty[]"]').value          = qty;
        last.querySelector('[name="item_price_byn[]"]').value    = price || '';
    } else {
        addReturnItem();
        const newRow = body.lastElementChild;
        newRow.querySelector('[name="item_poi_id[]"]').value      = poiId;
        newRow.querySelector('[name="item_name[]"]').value         = name;
        newRow.querySelector('[name="item_size[]"]').value         = size || '';
        newRow.querySelector('[name="item_qty[]"]').value          = qty;
        newRow.querySelector('[name="item_price_byn[]"]').value    = price || '';
    }
}

function removeReturnItem(btn) {
    const rows = document.querySelectorAll('#returnItemsBody .return-item-row');
    if (rows.length > 1) btn.closest('tr').remove();
}
</script>
