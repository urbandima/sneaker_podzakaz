<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\SupplierReturn $return */
$this->title = $return->return_number;
?>
<div class="admin-page">
  <div class="page-header mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/procurement">Закупки</a></li>
        <li class="breadcrumb-item"><a href="/admin/procurement/returns">Возвраты</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($return->return_number) ?></li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1 class="page-title"><?= htmlspecialchars($return->return_number) ?></h1>
        <span class="badge" style="background:<?= $return->getStatusColor() ?>;font-size:14px">
          <?= $return->getStatusLabel() ?>
        </span>
      </div>
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Изменить статус</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach (\app\backend\modules\procurement\models\SupplierReturn::getStatuses() as $k => $v): ?>
            <?php if ($k !== $return->status): ?>
            <li><a class="dropdown-item" href="#" onclick="updateStatus('<?= $k ?>'); return false">
              <?= $v ?><?= $k === 'refunded' ? ' (создаст запись расхода)' : '' ?>
            </a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Info -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-body">
          <h6 class="card-title">Информация</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr><th>Поставщик</th><td><?= htmlspecialchars($return->supplier->name ?? '—') ?></td></tr>
            <tr>
              <th>Закупка</th>
              <td>
                <?php if ($return->purchaseOrder): ?>
                  <a href="/admin/procurement/view/<?= $return->purchase_order_id ?>"><?= htmlspecialchars($return->purchaseOrder->purchase_number) ?></a>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
            <tr><th>Общая причина</th><td><?= $return->getReasonLabel() ?></td></tr>
            <tr><th>Сумма</th><td><strong><?= $return->total_amount ? number_format($return->total_amount, 2) . ' BYN' : '—' ?></strong></td></tr>
            <tr><th>Создан</th><td><?= $return->created_at ? date('d.m.Y H:i', strtotime($return->created_at)) : '—' ?></td></tr>
          </table>
        </div>
      </div>

      <?php if ($return->notes): ?>
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Примечания</h6>
          <p class="mb-0"><?= nl2br(htmlspecialchars($return->notes)) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($return->status === 'refunded'): ?>
      <div class="alert alert-success mt-3">
        Возврат денег получен. Запись в расходах создана автоматически.
      </div>
      <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3">Позиции возврата (<?= count($return->items) ?>)</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Название</th><th>Размер</th>
                  <th class="text-end">Кол-во</th>
                  <th class="text-end">Цена BYN</th>
                  <th class="text-end">Итого BYN</th>
                  <th>Причина</th>
                </tr>
              </thead>
              <tbody>
                <?php $total = 0; ?>
                <?php foreach ($return->items as $item): ?>
                <?php $rowTotal = $item->getTotalByn(); $total += $rowTotal; ?>
                <tr>
                  <td><?= htmlspecialchars($item->product_name) ?></td>
                  <td><?= htmlspecialchars($item->size ?? '—') ?></td>
                  <td class="text-end"><?= $item->quantity ?></td>
                  <td class="text-end"><?= $item->price_byn ? number_format($item->price_byn, 2) : '—' ?></td>
                  <td class="text-end"><?= $item->price_byn ? number_format($rowTotal, 2) : '—' ?></td>
                  <td><?= htmlspecialchars($item->reason ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="fw-bold">
                  <td colspan="4" class="text-end">Итого:</td>
                  <td class="text-end"><?= number_format($total, 2) ?> BYN</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

function updateStatus(status) {
    const labels = <?= json_encode(\app\backend\modules\procurement\models\SupplierReturn::getStatuses()) ?>;
    if (!confirm('Изменить статус на "' + labels[status] + '"?')) return;
    fetch('/admin/procurement/update-return-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id: <?= $return->id ?>, status})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка');
    });
}
</script>
