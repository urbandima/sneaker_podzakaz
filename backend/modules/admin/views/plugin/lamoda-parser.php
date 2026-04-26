<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Lamoda Parser';

$lastResultJson = json_encode($lastResult);
$defaultUrl = 'https://www.lamoda.by/c/17/shoes-men/?display_locations=outlet&is_sale=1&brands=1061,1163,34285,35002,2767,4063,1169,1287,2691,23678,5710,22293,29193,34252,5494,32517,5816,2047,29556,1107,1063,5706,5162,35000,25889,26687,4978';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:1100px">

    <!-- Управление парсером -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-cloud-download"></i> Запуск парсинга</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>URL каталога Lamoda BY</label>
                <textarea class="admin-form-input" id="lamoda-url" rows="4"
                          style="font-size:12px;font-family:monospace"
                          placeholder="https://www.lamoda.by/c/17/shoes-men/?..."
                ><?= Html::encode($lastUrl ?: $defaultUrl) ?></textarea>
                <small style="color:var(--admin-text-secondary);font-size:11px">
                    Вставьте URL из браузера с нужными фильтрами бренда/аутлета.
                </small>
            </div>
            <div class="form-group">
                <label>Лимит товаров</label>
                <input type="number" class="admin-form-input" id="lamoda-limit" value="50" min="1" max="500"
                       style="width:120px">
            </div>
            <div style="display:flex;gap:10px;align-items:center;margin-top:14px">
                <button class="admin-btn admin-btn-primary" id="btn-run" onclick="runParser()">
                    <i class="bi bi-play-fill"></i> Запустить парсинг
                </button>
                <span id="parse-status-badge" class="admin-badge admin-badge-secondary" style="display:none"></span>
            </div>
            <div id="run-msg" style="margin-top:10px;font-size:13px"></div>
        </div>
    </div>

    <!-- Настройки расписания + последний результат -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-clock"></i> Расписание и последний запуск</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Автоматический запуск</label>
                <select class="admin-form-input" id="lamoda-schedule" style="width:auto"
                        onchange="saveSchedule(this.value)">
                    <option value="manual" <?= $schedule === 'manual' ? 'selected' : '' ?>>Вручную</option>
                    <option value="daily"  <?= $schedule === 'daily'  ? 'selected' : '' ?>>Ежедневно</option>
                    <option value="weekly" <?= $schedule === 'weekly' ? 'selected' : '' ?>>Еженедельно</option>
                </select>
                <small style="color:var(--admin-text-secondary);font-size:11px;display:block;margin-top:4px">
                    Для автозапуска настройте cron: <code>php yii lamoda/parse</code>
                </small>
            </div>

            <hr style="border-color:var(--admin-border);margin:14px 0">

            <?php if (!empty($lastResult)): ?>
                <div style="font-size:13px">
                    <div style="margin-bottom:6px;color:var(--admin-text-secondary)">
                        Последний запуск: <strong><?= Html::encode($lastResult['date'] ?? $lastResult['finished_at'] ?? '—') ?></strong>
                    </div>
                    <div style="display:flex;gap:16px;flex-wrap:wrap">
                        <div style="text-align:center">
                            <div style="font-size:22px;font-weight:700;color:#059669"><?= (int)($lastResult['new'] ?? $lastResult['created'] ?? 0) ?></div>
                            <div style="color:var(--admin-text-secondary);font-size:11px">Новых</div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:22px;font-weight:700;color:#2563eb"><?= (int)($lastResult['updated'] ?? 0) ?></div>
                            <div style="color:var(--admin-text-secondary);font-size:11px">Обновлено</div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:22px;font-weight:700;color:#6b7280"><?= (int)($lastResult['skipped'] ?? 0) ?></div>
                            <div style="color:var(--admin-text-secondary);font-size:11px">Пропущено</div>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:22px;font-weight:700;color:#dc2626"><?= (int)($lastResult['errors'] ?? 0) ?></div>
                            <div style="color:var(--admin-text-secondary);font-size:11px">Ошибок</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p style="font-size:13px;color:var(--admin-text-secondary)">Парсинг ещё не запускался.</p>
            <?php endif; ?>

            <div id="log-block" style="margin-top:14px;display:none">
                <label style="font-size:12px;font-weight:500;color:#555">Лог (последние строки):</label>
                <pre id="parse-log" style="background:#1e1e1e;color:#d4d4d4;padding:10px;border-radius:6px;font-size:11px;max-height:160px;overflow-y:auto;margin:4px 0 0"></pre>
            </div>
        </div>
    </div>

</div>

<!-- Список спарсенных товаров -->
<div class="admin-card" style="max-width:1100px;margin-top:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title">
            <i class="bi bi-box-seam"></i> Товары из Lamoda (<?= count($products) ?>)
        </h2>
    </div>
    <div class="admin-card-body">
        <?php if (empty($products)): ?>
            <p style="font-size:13px;color:var(--admin-text-secondary)">
                Товаров из Lamoda пока нет. Запустите парсинг.
            </p>
        <?php else: ?>
            <table class="admin-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:60px">Фото</th>
                        <th>Название</th>
                        <th>Бренд</th>
                        <th>Цена</th>
                        <th>Стар. цена</th>
                        <th>Источник</th>
                        <th>Добавлен</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if ($p->main_image): ?>
                                    <img src="<?= Html::encode($p->main_image) ?>"
                                         style="width:48px;height:48px;object-fit:cover;border-radius:4px"
                                         loading="lazy">
                                <?php else: ?>
                                    <div style="width:48px;height:48px;background:var(--admin-bg-secondary);border-radius:4px"></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:500"><?= Html::encode($p->name) ?></td>
                            <td style="color:var(--admin-text-secondary)"><?= Html::encode($p->brand_name ?? '') ?></td>
                            <td><?= number_format($p->price, 2) ?> BYN</td>
                            <td style="color:var(--admin-text-secondary);text-decoration:line-through">
                                <?= $p->old_price ? number_format($p->old_price, 2) . ' BYN' : '—' ?>
                            </td>
                            <td>
                                <?php if ($p->source_url): ?>
                                    <a href="<?= Html::encode($p->source_url) ?>" target="_blank" rel="noopener"
                                       style="color:var(--admin-accent);font-size:12px">
                                        lamoda.by <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="font-size:12px;color:var(--admin-text-secondary)">
                                <?= date('d.m.Y', $p->created_at) ?>
                            </td>
                            <td>
                                <?= Html::a('<i class="bi bi-pencil"></i>', ['/admin/product/' . $p->id . '/edit'], [
                                    'class' => 'admin-btn admin-btn-sm admin-btn-secondary',
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($products) >= 50): ?>
                <div style="margin-top:10px;text-align:center">
                    <?= Html::a('Все товары из Lamoda →', ['/admin/product', 'source' => 'lamoda'], [
                        'class' => 'admin-btn admin-btn-secondary admin-btn-sm'
                    ]) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const runMsg    = document.getElementById('run-msg');
const statusBadge = document.getElementById('parse-status-badge');
let pollTimer = null;

function runParser() {
    const url   = document.getElementById('lamoda-url').value.trim();
    const limit = parseInt(document.getElementById('lamoda-limit').value) || 50;

    if (!url) { showMsg('err', 'Укажите URL каталога Lamoda'); return; }

    document.getElementById('btn-run').disabled = true;
    document.getElementById('btn-run').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Запуск...';

    fetch('<?= Url::to(['/admin/plugin/lamoda-parser/run']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken},
        body: `url=${encodeURIComponent(url)}&limit=${limit}`
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('btn-run').disabled = false;
        document.getElementById('btn-run').innerHTML = '<i class="bi bi-play-fill"></i> Запустить парсинг';

        if (d.success) {
            showMsg('ok', '✓ ' + d.message + ' Обновление статуса каждые 5 сек...');
            startPolling();
        } else {
            showMsg('err', '✗ ' + (d.message || 'Ошибка'));
        }
    })
    .catch(() => {
        document.getElementById('btn-run').disabled = false;
        document.getElementById('btn-run').innerHTML = '<i class="bi bi-play-fill"></i> Запустить парсинг';
        showMsg('err', '✗ Ошибка сети');
    });
}

function startPolling() {
    clearInterval(pollTimer);
    pollTimer = setInterval(pollStatus, 5000);
    pollStatus();
}

function pollStatus() {
    fetch('<?= Url::to(['/admin/plugin/lamoda-parser/status']) ?>')
    .then(r => r.json())
    .then(d => {
        const status = d.status || 'idle';

        statusBadge.style.display = 'inline';
        if (status === 'running') {
            statusBadge.className = 'admin-badge admin-badge-warning';
            statusBadge.innerHTML = '<i class="bi bi-hourglass-split"></i> Идёт парсинг...';
        } else {
            statusBadge.className = 'admin-badge admin-badge-success';
            statusBadge.textContent = '✓ Готово';
            clearInterval(pollTimer);
            // Показать итоги
            if (d.result && d.result.new !== undefined) {
                showMsg('ok', `✓ Завершено: ${d.result.new || 0} новых, ${d.result.skipped || 0} пропущено, ${d.result.errors || 0} ошибок`);
            }
            setTimeout(() => location.reload(), 2000);
        }

        if (d.log) {
            document.getElementById('log-block').style.display = 'block';
            document.getElementById('parse-log').textContent = d.log;
        }
    })
    .catch(() => {});
}

function saveSchedule(val) {
    fetch('<?= Url::to(['/admin/plugin/lamoda-parser/schedule']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken},
        body: `schedule=${encodeURIComponent(val)}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showMsg('ok', '✓ Расписание сохранено'); }
    })
    .catch(() => {});
}

function showMsg(type, text) {
    runMsg.className = '';
    runMsg.style.color = type === 'ok' ? '#065f46' : '#991b1b';
    runMsg.textContent = text;
}
</script>
