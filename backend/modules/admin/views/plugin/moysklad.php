<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'МойСклад — настройки';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge admin-badge-secondary" id="ms-status-badge">Не подключено</span>'
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key"></i> Авторизация API</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>API Token</label>
                <input type="password" class="admin-form-input" id="moysklad-token" placeholder="••••••••••••••••"
                       value="<?= Html::encode(Yii::$app->settings->get('moysklad', 'api_key', '')) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">Получите в настройках МойСклад → Профиль → Безопасность → API</small>
            </div>
            <div class="form-group">
                <label>ID склада</label>
                <input type="text" class="admin-form-input" id="moysklad-warehouse" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       value="<?= Html::encode(Yii::$app->settings->get('moysklad', 'warehouse_id', '')) ?>">
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="admin-btn admin-btn-primary" onclick="testMoySkladConn()">
                    <i class="bi bi-check-circle"></i> Проверить подключение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveMoySkladConn()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
            <div id="ms-test-result" style="margin-top:10px;font-size:13px"></div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-arrow-left-right"></i> Маппинг статусов</h2>
        </div>
        <div class="admin-card-body">
            <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                Соответствие статусов заказов между Сникерхэд и МойСклад
            </p>
            <div style="display:flex;flex-direction:column;gap:10px">
                <?php
                $statusMap = [
                    'new'      => 'Новый',
                    'paid'     => 'Оплачен',
                    'ordered'  => 'Заказано',
                    'at_warehouse' => 'На складе',
                    'delivered' => 'Выдан',
                    'canceled' => 'Отменён',
                ];
                foreach ($statusMap as $key => $label):
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                    <span style="font-size:14px;min-width:120px"><?= $label ?> →</span>
                    <input type="text" class="admin-form-input" style="flex:1"
                           placeholder="Статус в МойСклад"
                           value="<?= Html::encode(Yii::$app->settings->get('moysklad', 'status_map_' . $key, '')) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <button class="admin-btn admin-btn-secondary" style="margin-top:12px" onclick="saveMoySkladMapping()">
                <i class="bi bi-save"></i> Сохранить маппинг
            </button>
        </div>
    </div>

</div>

<script>
const msTestResult = document.getElementById('ms-test-result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testMoySkladConn() {
    const token = document.getElementById('moysklad-token').value;
    if (!token) { msTestResult.textContent = '⚠ Введите API Token'; msTestResult.style.color = '#b45309'; return; }
    msTestResult.textContent = 'Проверяем...'; msTestResult.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/settings/test-moysklad']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({api_key: token})
    })
    .then(r => r.json())
    .then(d => {
        msTestResult.textContent = d.success ? ('✓ ' + (d.message || 'Подключение успешно')) : ('✗ ' + (d.message || 'Ошибка'));
        msTestResult.style.color = d.success ? '#065f46' : '#991b1b';
        document.getElementById('ms-status-badge').textContent = d.success ? 'Подключено' : 'Не подключено';
        document.getElementById('ms-status-badge').className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
    })
    .catch(() => { msTestResult.textContent = '✗ Ошибка сети'; msTestResult.style.color = '#991b1b'; });
}

function saveMoySkladConn() {
    const token = document.getElementById('moysklad-token').value;
    const warehouse = document.getElementById('moysklad-warehouse').value;
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({moysklad: {api_key: token, warehouse_id: warehouse}})
    })
    .then(r => r.json())
    .then(d => {
        msTestResult.textContent = d.success ? '✓ Сохранено' : ('✗ ' + d.message);
        msTestResult.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => { msTestResult.textContent = '✗ Ошибка сети'; msTestResult.style.color = '#991b1b'; });
}

function saveMoySkladMapping() {
    const inputs = document.querySelectorAll('.admin-form-input[placeholder="Статус в МойСклад"]');
    const keys = <?= json_encode(array_keys($statusMap)) ?>;
    const map = {};
    inputs.forEach((el, i) => { if (keys[i]) map['status_map_' + keys[i]] = el.value; });
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({moysklad: map})
    })
    .then(r => r.json())
    .then(d => alert(d.success ? '✓ Маппинг сохранён' : ('✗ ' + d.message)));
}
</script>
