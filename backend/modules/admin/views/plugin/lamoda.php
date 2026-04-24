<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Lamoda Parser';

// Последний результат парсинга
$lastParseRaw = Yii::$app->settings->get('lamoda', 'last_parse', '{}');
$lastParse    = json_decode($lastParseRaw, true) ?: [];

// Настройки
$savedUrl   = Yii::$app->settings->get('lamoda', 'parse_url', 'https://www.lamoda.by/c/17/shoes-men/?sitelink=topmenuM&l=men');
$savedPages = (int)Yii::$app->settings->get('lamoda', 'max_pages', '10');
$schedule   = Yii::$app->settings->get('lamoda', 'schedule', 'manual');

// Импортированные товары из Lamoda
$totalLamodaProducts = \app\backend\modules\catalog\models\Product::find()
    ->where(['source' => 'lamoda'])->count();

$recentProducts = \app\backend\modules\catalog\models\Product::find()
    ->where(['source' => 'lamoda'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(20)
    ->all();
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge admin-badge-primary">' . $totalLamodaProducts . ' товаров</span>'
];
?>

<!-- 1. Настройки парсинга -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-gear"></i> Настройки парсера</h2>
    </div>
    <div class="admin-card-body">
        <div style="margin-bottom:12px">
            <label class="admin-form-label">URL каталога Lamoda</label>
            <input type="url" class="admin-form-input" id="lm-url"
                   value="<?= Html::encode($savedUrl) ?>"
                   placeholder="https://www.lamoda.by/c/17/shoes-men/">
            <small style="color:var(--admin-text-secondary);font-size:11px;display:block;margin-top:4px">
                Откройте нужный раздел на lamoda.by, примените фильтры и скопируйте URL
            </small>
        </div>

        <div style="display:flex;gap:12px;margin-bottom:12px">
            <div style="flex:1">
                <label class="admin-form-label">Макс. страниц</label>
                <input type="number" class="admin-form-input" id="lm-pages" value="<?= $savedPages ?>" min="1" max="50" step="1">
            </div>
            <div style="flex:1">
                <label class="admin-form-label">Расписание</label>
                <select class="admin-form-input" id="lm-schedule">
                    <option value="manual" <?= $schedule === 'manual' ? 'selected' : '' ?>>Вручную</option>
                    <option value="daily" <?= $schedule === 'daily' ? 'selected' : '' ?>>Ежедневно (03:00)</option>
                    <option value="weekly" <?= $schedule === 'weekly' ? 'selected' : '' ?>>Еженедельно (пн 03:00)</option>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <button class="admin-btn admin-btn-primary" id="lm-parse-btn" onclick="startParsing()">
                <i class="bi bi-play-fill"></i> Запустить парсинг
            </button>
            <button class="admin-btn admin-btn-secondary" onclick="saveSettings()">
                <i class="bi bi-save"></i> Сохранить настройки
            </button>
            <button class="admin-btn admin-btn-secondary" onclick="startParsing(true)">
                <i class="bi bi-eye"></i> Тестовый запуск (dry-run)
            </button>
        </div>
        <span id="lm-settings-msg" style="display:block;margin-top:8px;font-size:13px"></span>
    </div>
</div>

<!-- 2. Прогресс -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px" id="lm-progress-card" style="display:none">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-hourglass-split"></i> Прогресс</h2>
    </div>
    <div class="admin-card-body">
        <div style="margin-bottom:10px">
            <div style="background:var(--admin-bg);border-radius:6px;height:8px;overflow:hidden">
                <div id="lm-progress-bar" style="width:0%;height:100%;background:var(--admin-primary,#2563eb);transition:width .3s;border-radius:6px"></div>
            </div>
        </div>
        <div id="lm-progress-text" style="font-size:13px;color:var(--admin-text-secondary)">Ожидание...</div>
        <pre id="lm-progress-log" style="max-height:250px;overflow-y:auto;font-size:11px;background:var(--admin-bg);padding:10px;border-radius:6px;margin-top:10px;white-space:pre-wrap;display:none"></pre>
    </div>
</div>

<!-- 3. Последний результат -->
<?php if (!empty($lastParse)): ?>
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-clock-history"></i> Последний запуск</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px">
            <div><strong>Дата:</strong> <?= Html::encode($lastParse['date'] ?? '-') ?></div>
            <div><strong>Режим:</strong> <?= !empty($lastParse['dry_run']) ? 'Тестовый' : 'Боевой' ?></div>
            <div><strong>URL:</strong> <span style="word-break:break-all"><?= Html::encode(mb_substr($lastParse['url'] ?? '-', 0, 60)) ?>...</span></div>
            <div><strong>Страниц:</strong> <?= (int)($lastParse['pages'] ?? 0) ?></div>
            <div><strong>Товаров найдено:</strong> <?= (int)($lastParse['total'] ?? 0) ?></div>
            <div><strong>Создано/обновлено:</strong> <span style="color:#065f46;font-weight:600"><?= (int)($lastParse['created'] ?? 0) ?></span></div>
            <div><strong>Ошибок:</strong> <span style="color:<?= ($lastParse['errors'] ?? 0) > 0 ? '#991b1b' : '#065f46' ?>"><?= (int)($lastParse['errors'] ?? 0) ?></span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 4. Импортированные товары -->
<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-box-seam"></i> Товары из Lamoda</h2>
        <span class="admin-badge admin-badge-primary"><?= $totalLamodaProducts ?></span>
    </div>
    <div class="admin-card-body" style="padding:0">
        <?php if (empty($recentProducts)): ?>
            <div style="padding:32px;text-align:center;color:var(--admin-text-secondary)">
                <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5"></i>
                <p style="margin:0">Товаров из Lamoda пока нет. Запустите парсинг.</p>
            </div>
        <?php else: ?>
            <table class="admin-table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:50px"></th>
                        <th>Название</th>
                        <th>Бренд</th>
                        <th>Цена</th>
                        <th>Размеров</th>
                        <th>Дата</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProducts as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product->main_image): ?>
                                    <img src="<?= Html::encode($product->getMainImageUrl()) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px">
                                <?php else: ?>
                                    <div style="width:40px;height:40px;background:var(--admin-bg);border-radius:4px;display:flex;align-items:center;justify-content:center">
                                        <i class="bi bi-image" style="color:var(--admin-text-secondary)"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= Html::encode(mb_substr($product->name, 0, 50)) ?></strong>
                                <?php if ($product->sku): ?>
                                    <br><small style="color:var(--admin-text-secondary)"><?= Html::encode($product->sku) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= Html::encode($product->brand_name ?? '-') ?></td>
                            <td>
                                <?= number_format($product->price, 2) ?> BYN
                                <?php if ($product->old_price): ?>
                                    <br><small style="text-decoration:line-through;color:var(--admin-text-secondary)"><?= number_format($product->old_price, 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= count($product->sizes) ?></td>
                            <td style="font-size:12px;color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asRelativeTime($product->created_at) ?></td>
                            <td style="white-space:nowrap">
                                <a href="<?= Url::to(['/admin/product/' . $product->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" title="Открыть">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($product->source_url): ?>
                                    <a href="<?= Html::encode($product->source_url) ?>" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary admin-btn-sm" title="Lamoda">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function saveSettings() {
    const data = {
        lamoda: {
            parse_url: document.getElementById('lm-url').value,
            max_pages: document.getElementById('lm-pages').value,
            schedule:  document.getElementById('lm-schedule').value,
        }
    };

    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        const el = document.getElementById('lm-settings-msg');
        el.textContent = d.success ? '✓ Настройки сохранены' : '✗ Ошибка';
        el.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => {
        document.getElementById('lm-settings-msg').textContent = '✗ Ошибка сети';
        document.getElementById('lm-settings-msg').style.color = '#991b1b';
    });
}

function startParsing(dryRun = false) {
    const url   = document.getElementById('lm-url').value.trim();
    const pages = document.getElementById('lm-pages').value;
    const btn   = document.getElementById('lm-parse-btn');

    if (!url) {
        alert('Укажите URL каталога');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Парсинг...';

    const progressCard = document.getElementById('lm-progress-card');
    progressCard.style.display = '';
    const progressBar  = document.getElementById('lm-progress-bar');
    const progressText = document.getElementById('lm-progress-text');
    const progressLog  = document.getElementById('lm-progress-log');

    progressBar.style.width = '10%';
    progressText.textContent = 'Запускаем парсер...';
    progressLog.style.display = 'block';
    progressLog.textContent = '';

    fetch('<?= Url::to(['/admin/plugin/lamoda-run']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({url, pages: parseInt(pages), dry_run: dryRun})
    })
    .then(r => r.json())
    .then(d => {
        progressBar.style.width = '100%';
        if (d.success) {
            progressText.innerHTML = '✓ Парсинг завершён. Создано: <strong>' + d.created + '</strong>, ошибок: ' + d.errors;
            progressBar.style.background = '#22c55e';
        } else {
            progressText.textContent = '✗ Ошибка: ' + (d.message || 'Неизвестная ошибка');
            progressBar.style.background = '#ef4444';
        }
        if (d.log) {
            progressLog.textContent = d.log;
        }
        // Перезагрузка через 2 сек для обновления таблицы
        if (d.success && !dryRun) {
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(err => {
        progressText.textContent = '✗ Ошибка сети: ' + err.message;
        progressBar.style.background = '#ef4444';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-fill"></i> Запустить парсинг';
    });
}
</script>
