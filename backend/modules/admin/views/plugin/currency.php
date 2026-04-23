<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Курс валют — настройки';

$markup = Yii::$app->settings->get('currency', 'cny_markup', '5');

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-graph-up"></i> Текущий курс</h2>
        </div>
        <div class="admin-card-body">
            <div style="text-align:center;padding:28px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;margin-bottom:16px">
                <p style="font-size:14px;color:#92400e;margin:0 0 8px">Текущий курс CNY → BYN</p>
                <p style="font-size:52px;font-weight:800;margin:0;color:#92400e" id="cny-rate">—</p>
                <p style="font-size:12px;color:#92400e;margin:8px 0 0">Обновлено: <span id="cny-updated">—</span></p>
            </div>
            <button class="admin-btn admin-btn-primary w-100" id="update-rate-btn" onclick="updateCNYRateNow()">
                <i class="bi bi-arrow-clockwise"></i> Обновить курс сейчас
            </button>
            <div id="rate-result" style="margin-top:10px;font-size:13px;text-align:center"></div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-sliders"></i> Настройки</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Источник курса</label>
                <select class="admin-form-input">
                    <option selected>НБРБ API (автоматически, ежедневно)</option>
                    <option>Вручную</option>
                </select>
            </div>
            <div class="form-group">
                <label>Наценка на курс (%)</label>
                <input type="number" class="admin-form-input" id="markup-percent"
                       value="<?= Html::encode($markup) ?>" step="0.1" min="0" placeholder="5.0">
                <small class="text-muted-sm">
                    Применяется поверх официального курса НБРБ при расчёте цен
                </small>
            </div>
            <button class="admin-btn admin-btn-secondary w-100" onclick="saveCurrencySettings()">
                <i class="bi bi-save"></i> Сохранить
            </button>
            <div id="currency-save-result" style="margin-top:10px;font-size:13px;text-align:center"></div>
        </div>
    </div>

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// Load current rate on page open
fetch('/admin/exchange-rate/current')
    .then(r => r.json())
    .then(d => {
        if (d.rate) {
            document.getElementById('cny-rate').textContent = d.rate;
            document.getElementById('cny-updated').textContent = d.updated_at || '—';
        }
    })
    .catch(() => {});

function updateCNYRateNow() {
    const btn = document.getElementById('update-rate-btn');
    const res = document.getElementById('rate-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Обновление...';
    res.textContent = '';

    fetch('/admin/exchange-rate/update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken}
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(d => {
        if (d.success) {
            document.getElementById('cny-rate').textContent = d.rate;
            document.getElementById('cny-updated').textContent = new Date().toLocaleString('ru-RU');
            res.textContent = '✓ Курс обновлён';
            res.style.color = '#065f46';
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Обновлено!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить курс сейчас';
                res.textContent = '';
            }, 3000);
        } else {
            throw new Error(d.message || 'Ошибка обновления');
        }
    })
    .catch(err => {
        res.textContent = '✗ ' + err.message;
        res.style.color = '#991b1b';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить курс сейчас';
    });
}

function saveCurrencySettings() {
    const markup = document.getElementById('markup-percent').value;
    const res = document.getElementById('currency-save-result');
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({currency: {cny_markup: markup}})
    })
    .then(r => r.json())
    .then(d => {
        res.textContent = d.success ? '✓ Сохранено' : ('✗ ' + d.message);
        res.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => { res.textContent = '✗ Ошибка сети'; res.style.color = '#991b1b'; });
}
</script>
