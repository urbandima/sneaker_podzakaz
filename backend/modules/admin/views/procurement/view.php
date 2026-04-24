<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\PurchaseOrder $po */
$this->title = $po->purchase_number;
?>
<div class="admin-page">
  <div class="page-header mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/procurement">Закупки</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($po->purchase_number) ?></li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1 class="page-title"><?= htmlspecialchars($po->purchase_number) ?></h1>
        <span class="badge" style="background:<?= $po->getStatusColor() ?>;font-size:14px" id="statusBadge">
          <?= $po->getStatusLabel() ?>
        </span>
      </div>
      <div class="d-flex gap-2">
        <?php if ($po->status === \app\backend\modules\procurement\models\PurchaseOrder::STATUS_RECEIVED): ?>
          <a href="/admin/procurement/create-return/<?= $po->id ?>" class="btn btn-outline-warning">
            Возврат поставщику
          </a>
        <?php endif; ?>
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Изменить статус</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach (\app\backend\modules\procurement\models\PurchaseOrder::getStatuses() as $k => $v): ?>
            <?php if ($k !== $po->status): ?>
            <li><a class="dropdown-item" href="#" onclick="updateStatus('<?= $k ?>'); return false"><?= $v ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- PO info -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-body">
          <h6 class="card-title">Информация</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr><th>Тип</th><td><?= $po->getTypeLabel() ?></td></tr>
            <tr><th>Поставщик</th><td><?= htmlspecialchars($po->supplier->name ?? '—') ?></td></tr>
            <tr><th>Курс CNY</th><td><?= $po->exchange_rate ? $po->exchange_rate : '—' ?></td></tr>
            <tr><th>Заказ клиента</th><td><?= $po->order_id ? '<a href="/admin/order/'.$po->order_id.'">#'.$po->order_id.'</a>' : '—' ?></td></tr>
            <tr><th>Дата заказа</th><td><?= $po->ordered_at ? date('d.m.Y', strtotime($po->ordered_at)) : '—' ?></td></tr>
            <tr><th>Дата получения</th><td><?= $po->received_at ? date('d.m.Y', strtotime($po->received_at)) : '—' ?></td></tr>
            <tr><th>Создан</th><td><?= $po->created_at ? date('d.m.Y H:i', strtotime($po->created_at)) : '—' ?></td></tr>
          </table>
        </div>
      </div>

      <!-- Totals -->
      <div class="card mb-3">
        <div class="card-body">
          <h6 class="card-title">Итоги</h6>
          <div class="d-flex justify-content-between mb-2">
            <span>Сумма CNY:</span>
            <strong><?= $po->total_amount_cny ? number_format($po->total_amount_cny, 2) . ' ¥' : '—' ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>Сумма BYN:</span>
            <strong><?= $po->total_amount_byn ? number_format($po->total_amount_byn, 2) . ' BYN' : '—' ?></strong>
          </div>
        </div>
      </div>

      <?php if ($po->notes): ?>
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Примечания</h6>
          <p class="mb-0"><?= nl2br(htmlspecialchars($po->notes)) ?></p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3">
            Позиции (<?= count($po->items) ?>)
            <?php
            $totalQty = array_sum(array_column($po->items, 'quantity'));
            $rcvQty   = array_sum(array_column($po->items, 'received_quantity'));
            $pct = $po->getReceivedPercent();
            ?>
            <span class="text-muted ms-2" style="font-size:12px">Получено: <?= $rcvQty ?>/<?= $totalQty ?> (<?= $pct ?>%)</span>
          </h6>

          <?php if ($pct > 0 && $pct < 100): ?>
          <div class="progress mb-3" style="height:6px">
            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
          </div>
          <?php endif; ?>

          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Название</th><th>Размер</th>
                  <th class="text-end">Кол-во</th>
                  <th class="text-end">Цена CNY</th>
                  <th class="text-end">Итого CNY</th>
                  <th class="text-end">Цена BYN</th>
                  <th class="text-end">Итого BYN</th>
                  <th class="text-end">Получено</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($po->items as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item->product_name) ?></td>
                  <td><?= htmlspecialchars($item->size ?? '—') ?></td>
                  <td class="text-end"><?= $item->quantity ?></td>
                  <td class="text-end"><?= $item->price_cny ? number_format($item->price_cny, 2) : '—' ?></td>
                  <td class="text-end"><?= $item->price_cny ? number_format($item->getTotalCny(), 2) : '—' ?></td>
                  <td class="text-end"><?= $item->price_byn ? number_format($item->price_byn, 2) : '—' ?></td>
                  <td class="text-end"><?= $item->price_byn ? number_format($item->getTotalByn(), 2) : '—' ?></td>
                  <td class="text-end">
                    <?php $ok = $item->received_quantity >= $item->quantity; ?>
                    <span style="color:<?= $ok ? '#059669' : '#6b7280' ?>"><?= $item->received_quantity ?>/<?= $item->quantity ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
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
    fetch('/admin/procurement/update-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id: <?= $po->id ?>, status})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка');
    });
}
</script>
