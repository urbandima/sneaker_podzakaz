<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Программа лояльности';

$loyaltyEnabled = Yii::$app->settings->get('loyalty', 'enabled', 0);
$publicEnabled  = Yii::$app->settings->get('loyalty', 'public_page', 0);

$levels = [
    'bronze'   => ['label' => 'Bronze',   'icon' => 'award',        'color' => '#cd7f32', 'min' => Yii::$app->settings->get('loyalty', 'bronze_min',   0),     'multiplier' => Yii::$app->settings->get('loyalty', 'bronze_mult',   1.0)],
    'silver'   => ['label' => 'Silver',   'icon' => 'award-fill',   'color' => '#c0c0c0', 'min' => Yii::$app->settings->get('loyalty', 'silver_min',   5000),  'multiplier' => Yii::$app->settings->get('loyalty', 'silver_mult',   1.5)],
    'gold'     => ['label' => 'Gold',     'icon' => 'trophy',       'color' => '#ffd700', 'min' => Yii::$app->settings->get('loyalty', 'gold_min',     15000), 'multiplier' => Yii::$app->settings->get('loyalty', 'gold_mult',     2.0)],
    'platinum' => ['label' => 'Platinum', 'icon' => 'trophy-fill',  'color' => '#e5e4e2', 'min' => Yii::$app->settings->get('loyalty', 'platinum_min', 50000), 'multiplier' => Yii::$app->settings->get('loyalty', 'platinum_mult', 3.0)],
];
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/settings/index']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Настройки
        </a>
    </div>
</div>

<!-- Общие переключатели -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-toggles"></i>
            Статус программы
        </h2>
        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <label class="loyalty-toggle-row">
                <span class="loyalty-toggle-label">
                    <i class="bi bi-stars"></i>
                    Программа лояльности включена
                </span>
                <label class="toggle-switch">
                    <input type="checkbox" id="loyaltyEnabled" <?= $loyaltyEnabled ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </label>
            <label class="loyalty-toggle-row">
                <span class="loyalty-toggle-label">
                    <i class="bi bi-eye"></i>
                    Публичная страница программы
                </span>
                <label class="toggle-switch">
                    <input type="checkbox" id="publicPageEnabled" <?= $publicEnabled ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </label>
        </div>
    </div>

    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-info-circle"></i>
            Как работает
        </h2>
        <div style="margin-top: 1rem; font-size: 0.875rem; color: var(--admin-text-secondary); line-height: 1.7;">
            <p>Покупатели накапливают баллы за каждую покупку. Уровень определяется суммарными расходами в BYN за всё время. Множитель влияет на количество начисляемых баллов.</p>
            <p style="margin-top: 0.5rem;"><strong>1 балл = 1 BYN</strong> при базовом множителе ×1.0</p>
        </div>
    </div>
</div>

<!-- Уровни и пороги -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-bar-chart-steps"></i>
        Уровни и пороги
    </h2>
    <p style="color: var(--admin-text-secondary); font-size: 0.875rem; margin: 0.5rem 0 1.5rem;">Укажите минимальную сумму покупок (BYN) для достижения уровня и коэффициент начисления баллов.</p>

    <div class="loyalty-levels-grid">
        <?php foreach ($levels as $key => $level): ?>
        <div class="loyalty-level-card">
            <div class="loyalty-level-header" style="border-color: <?= $level['color'] ?>; background: <?= $level['color'] ?>20;">
                <i class="bi bi-<?= $level['icon'] ?>" style="color: <?= $level['color'] ?>; font-size: 1.75rem;"></i>
                <span class="loyalty-level-name" style="color: <?= $level['color'] ?>;"><?= $level['label'] ?></span>
            </div>
            <div class="loyalty-level-body">
                <div class="form-group">
                    <label>Минимальная сумма покупок (BYN)</label>
                    <input type="number"
                           class="form-control"
                           id="level_<?= $key ?>_min"
                           value="<?= Html::encode($level['min']) ?>"
                           min="0" step="100"
                           <?= $key === 'bronze' ? 'readonly' : '' ?>>
                    <?php if ($key === 'bronze'): ?>
                        <small style="color: var(--admin-text-secondary);">Стартовый уровень — всегда 0</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Множитель начисления баллов</label>
                    <div style="position: relative;">
                        <input type="number"
                               class="form-control"
                               id="level_<?= $key ?>_mult"
                               value="<?= Html::encode($level['multiplier']) ?>"
                               min="0.1" max="10" step="0.1">
                        <span style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-secondary);">×</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Кнопка сохранения -->
<div class="admin-card">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="margin: 0; font-size: 1rem;">Сохранить изменения</h3>
            <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--admin-text-secondary);">
                    Настройки применятся немедленно для всех новых начислений баллов. &nbsp;|&nbsp;
                    <a href="/loyalty" target="_blank" style="color: var(--admin-accent, #2563eb);">Публичная страница: /loyalty</a>
                </p>
        </div>
        <button class="admin-btn admin-btn-primary" id="saveLoyaltyBtn" onclick="saveLoyaltySettings()"
            data-save-url="<?= Url::to(['/admin/settings/save-loyalty']) ?>">
            <i class="bi bi-check-circle"></i>
            Сохранить настройки
        </button>
    </div>
</div>

<?php $this->registerCss(<<<CSS
.loyalty-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    cursor: pointer;
    gap: 1rem;
}
.loyalty-toggle-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: var(--admin-text);
}
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
    cursor: pointer;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: var(--admin-border);
    border-radius: 26px;
    transition: background 0.2s;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px; height: 20px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
}
.toggle-switch input:checked + .toggle-slider { background: var(--admin-accent, #2563eb); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

.loyalty-levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
}
.loyalty-level-card {
    border: 1px solid var(--admin-border);
    border-radius: 0.75rem;
    overflow: hidden;
}
.loyalty-level-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-bottom: 2px solid;
}
.loyalty-level-name {
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.04em;
}
.loyalty-level-body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.form-control {
    width: 100%;
    padding: 0.65rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg, #fff);
    color: var(--admin-text);
    font-size: 0.95rem;
    box-sizing: border-box;
}
.form-control:focus {
    outline: none;
    border-color: var(--admin-accent, #2563eb);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.form-control[readonly] { opacity: 0.6; cursor: not-allowed; }
.form-group { margin: 0; }
.form-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
CSS
); ?>

<script>
function saveLoyaltySettings() {
    const btn = document.getElementById('saveLoyaltyBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

    const data = {
        loyalty: {
            enabled:      document.getElementById('loyaltyEnabled').checked ? 1 : 0,
            public_page:  document.getElementById('publicPageEnabled').checked ? 1 : 0,
            bronze_min:   document.getElementById('level_bronze_min').value,
            bronze_mult:  document.getElementById('level_bronze_mult').value,
            silver_min:   document.getElementById('level_silver_min').value,
            silver_mult:  document.getElementById('level_silver_mult').value,
            gold_min:     document.getElementById('level_gold_min').value,
            gold_mult:    document.getElementById('level_gold_mult').value,
            platinum_min: document.getElementById('level_platinum_min').value,
            platinum_mult:document.getElementById('level_platinum_mult').value,
        }
    };

    fetch('<?= Url::to(['/admin/settings/save-loyalty']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Сохранено!';
            btn.classList.replace('admin-btn-primary', 'admin-btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки';
                btn.classList.replace('admin-btn-success', 'admin-btn-primary');
            }, 2500);
        } else {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
            alert(res.message || 'Ошибка сохранения');
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки'; }, 2000);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки';
        alert('Ошибка соединения');
    });
}
</script>
