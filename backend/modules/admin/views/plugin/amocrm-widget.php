<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM Widget — настройки';
$widgetApiKey = Yii::$app->settings->get('amocrm', 'widget_api_key', '');
$apiUrl = rtrim(Yii::$app->params['frontendUrl'] ?? Yii::$app->request->hostInfo, '/');
$embedCode = '<script>window.sneakerheadWidget={apiUrl:"' . $apiUrl . '",apiKey:"' . htmlspecialchars($widgetApiKey) . '"};</script>' . "\n" .
             '<script src="' . $apiUrl . '/amocrm-widget/widget.js"></script>';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:1000px">

    <!-- API ключ -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key"></i> API ключ виджета</h2>
        </div>
        <div class="admin-card-body">
            <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                Ключ используется виджетом в AmoCRM для авторизации запросов к вашему сайту.
            </p>
            <div class="form-group">
                <label>Текущий ключ</label>
                <div style="display:flex;gap:8px">
                    <input type="text" class="admin-form-input" id="widget-api-key" readonly
                           value="<?= Html::encode($widgetApiKey) ?>"
                           placeholder="Ключ не сгенерирован">
                    <button class="admin-btn admin-btn-secondary" onclick="copyKey()" title="Скопировать">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button class="admin-btn admin-btn-primary" onclick="generateKey()">
                    <i class="bi bi-arrow-clockwise"></i> Сгенерировать новый
                </button>
            </div>
            <div id="key-msg" style="margin-top:10px;font-size:13px"></div>
        </div>
    </div>

    <!-- Код для вставки -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-code-slash"></i> Код виджета для AmoCRM</h2>
        </div>
        <div class="admin-card-body">
            <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                Вставьте этот код в настройки кастомного виджета AmoCRM
                (Настройки → Интеграции → Создать виджет → код HTML).
            </p>
            <div class="form-group">
                <label>Embed-код</label>
                <textarea class="admin-form-input" id="embed-code" rows="4" readonly
                          style="font-family:monospace;font-size:12px"><?= Html::encode($embedCode) ?></textarea>
            </div>
            <button class="admin-btn admin-btn-secondary" onclick="copyEmbed()">
                <i class="bi bi-clipboard"></i> Скопировать код
            </button>

            <hr style="margin:16px 0;border-color:var(--admin-border)">

            <div class="form-group">
                <label>URL webhook (для справки)</label>
                <input type="text" class="admin-form-input" readonly
                       value="<?= Html::encode($apiUrl . '/api/amocrm/create-order') ?>">
            </div>
            <div class="form-group">
                <label>URL товаров (autocomplete)</label>
                <input type="text" class="admin-form-input" readonly
                       value="<?= Html::encode($apiUrl . '/api/amocrm/products') ?>">
            </div>
        </div>
    </div>

</div>

<!-- Последние заказы из AmoCRM -->
<div class="admin-card" style="max-width:1000px;margin-top:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-list-check"></i> Последние заказы из AmoCRM</h2>
    </div>
    <div class="admin-card-body">
        <?php
        $orders = \app\backend\modules\checkout\models\Order::find()
            ->where(['source' => 'amoCRM'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20)
            ->all();
        ?>
        <?php if (empty($orders)): ?>
            <p style="color:var(--admin-text-secondary);font-size:13px">Заказов из AmoCRM пока нет.</p>
        <?php else: ?>
            <table class="admin-table" style="width:100%">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Сумма</th>
                        <th>Сделка AmoCRM</th>
                        <th>Статус</th>
                        <th>Создан</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= Html::encode($o->order_number) ?></td>
                            <td><?= Html::encode($o->client_name) ?></td>
                            <td><?= Html::encode($o->client_phone) ?></td>
                            <td><?= number_format($o->total_amount, 2) ?> BYN</td>
                            <td>
                                <?php if ($o->ms_deal_link): ?>
                                    <a href="<?= Html::encode($o->ms_deal_link) ?>" target="_blank" rel="noopener"
                                       style="color:var(--admin-accent)">
                                        #<?= Html::encode($o->amocrm_deal_id) ?>
                                    </a>
                                <?php elseif ($o->amocrm_deal_id): ?>
                                    #<?= Html::encode($o->amocrm_deal_id) ?>
                                <?php else: ?>
                                    <span style="color:var(--admin-text-secondary)">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge"><?= Html::encode($o->status) ?></span>
                            </td>
                            <td style="font-size:12px;color:var(--admin-text-secondary)">
                                <?= date('d.m.Y H:i', $o->created_at) ?>
                            </td>
                            <td>
                                <?= Html::a('<i class="bi bi-eye"></i>', ['/admin/order/' . $o->id], [
                                    'class' => 'admin-btn admin-btn-sm admin-btn-secondary',
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
const keyMsg = document.getElementById('key-msg');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function generateKey() {
    fetch('<?= Url::to(['/admin/plugin/amocrm-widget-key']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({action: 'generate'})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('widget-api-key').value = d.key;
            // Обновить embed-код
            const embed = document.getElementById('embed-code');
            embed.value = embed.value.replace(/apiKey:"[^"]*"/, 'apiKey:"' + d.key + '"');
            keyMsg.style.color = '#065f46';
            keyMsg.textContent = '✓ Новый ключ сохранён';
        } else {
            keyMsg.style.color = '#991b1b';
            keyMsg.textContent = '✗ ' + (d.message || 'Ошибка');
        }
    })
    .catch(() => { keyMsg.style.color='#991b1b'; keyMsg.textContent='✗ Ошибка сети'; });
}

function copyKey() {
    const el = document.getElementById('widget-api-key');
    navigator.clipboard.writeText(el.value).then(() => {
        keyMsg.style.color = '#065f46';
        keyMsg.textContent = '✓ Скопировано';
    });
}

function copyEmbed() {
    const el = document.getElementById('embed-code');
    navigator.clipboard.writeText(el.value).then(() => {
        keyMsg.style.color = '#065f46';
        keyMsg.textContent = '✓ Код скопирован';
    });
}
</script>
