<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var string|null $search */
/** @var string|null $gender */
/** @var int|null $brandId */
/** @var string|null $status */
/** @var array $brandOptions */
/** @var array $genderOptions */

$this->title = 'Размерные сетки';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];
$this->params['breadcrumbs'][] = $this->title;

// Brand size templates data
$brandTemplates = [
    'Nike' => [
        ['us' => '6',   'eu' => '38'],
        ['us' => '6.5', 'eu' => '38.5'],
        ['us' => '7',   'eu' => '40'],
        ['us' => '7.5', 'eu' => '40.5'],
        ['us' => '8',   'eu' => '41'],
        ['us' => '8.5', 'eu' => '42'],
        ['us' => '9',   'eu' => '42.5'],
        ['us' => '9.5', 'eu' => '43'],
        ['us' => '10',  'eu' => '44'],
        ['us' => '10.5','eu' => '44.5'],
        ['us' => '11',  'eu' => '45'],
        ['us' => '12',  'eu' => '46'],
    ],
    'Adidas' => [
        ['us' => '6',   'eu' => '38'],
        ['us' => '6.5', 'eu' => '38.5'],
        ['us' => '7',   'eu' => '40'],
        ['us' => '7.5', 'eu' => '40.5'],
        ['us' => '8',   'eu' => '41'],
        ['us' => '8.5', 'eu' => '42'],
        ['us' => '9',   'eu' => '42.5'],
        ['us' => '9.5', 'eu' => '43'],
        ['us' => '10',  'eu' => '44'],
        ['us' => '10.5','eu' => '44.5'],
        ['us' => '11',  'eu' => '45'],
        ['us' => '12',  'eu' => '46'],
    ],
    'Jordan' => [
        ['us' => '6',   'eu' => '38'],
        ['us' => '6.5', 'eu' => '38.5'],
        ['us' => '7',   'eu' => '40'],
        ['us' => '7.5', 'eu' => '40.5'],
        ['us' => '8',   'eu' => '41'],
        ['us' => '8.5', 'eu' => '42'],
        ['us' => '9',   'eu' => '42.5'],
        ['us' => '9.5', 'eu' => '43'],
        ['us' => '10',  'eu' => '44'],
        ['us' => '10.5','eu' => '44.5'],
        ['us' => '11',  'eu' => '45'],
        ['us' => '12',  'eu' => '46'],
    ],
    'New Balance' => [
        ['us' => '6',   'eu' => '38'],
        ['us' => '6.5', 'eu' => '38.5'],
        ['us' => '7',   'eu' => '40'],
        ['us' => '7.5', 'eu' => '40.5'],
        ['us' => '8',   'eu' => '41'],
        ['us' => '8.5', 'eu' => '42'],
        ['us' => '9',   'eu' => '42.5'],
        ['us' => '9.5', 'eu' => '43'],
        ['us' => '10',  'eu' => '44'],
        ['us' => '10.5','eu' => '44.5'],
        ['us' => '11',  'eu' => '45'],
        ['us' => '12',  'eu' => '46'],
    ],
];
?>

<div class="size-grid-admin" id="sizeGridPage">
    <div class="size-grid-shell">
        <!-- Brand Templates Section -->
        <section class="brand-templates-section">
            <div class="brand-templates-header">
                <h2><i class="bi bi-grid-3x3-gap"></i> Шаблоны брендов</h2>
                <a href="<?= Url::to(['/site/size-guide'], true) ?>" target="_blank" class="btn-action btn-ghost btn-sm">
                    <i class="bi bi-box-arrow-up-right"></i> Публичная таблица размеров
                </a>
            </div>
            <div class="brand-templates-grid">
                <?php foreach ($brandTemplates as $brandName => $sizes): ?>
                <div class="brand-template-card" data-brand="<?= Html::encode($brandName) ?>">
                    <div class="brand-template-name">
                        <i class="bi bi-tag"></i>
                        <?= Html::encode($brandName) ?>
                    </div>
                    <div class="brand-template-preview">
                        <?php foreach (array_slice($sizes, 0, 4) as $s): ?>
                            <span class="size-chip">US <?= Html::encode($s['us']) ?> = EU <?= Html::encode($s['eu']) ?></span>
                        <?php endforeach; ?>
                        <span class="size-chip muted">+<?= count($sizes) - 4 ?> ещё</span>
                    </div>
                    <button
                        type="button"
                        class="btn-action btn-primary btn-sm apply-template-btn"
                        data-brand="<?= Html::encode($brandName) ?>"
                        data-sizes="<?= Html::encode(json_encode($sizes)) ?>"
                        onclick="applyBrandTemplate(this)"
                    >
                        <i class="bi bi-check2-square"></i> Применить шаблон
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Template Preview Modal -->
            <div id="templatePreviewModal" class="template-modal" style="display:none;">
                <div class="template-modal-content">
                    <div class="template-modal-header">
                        <h3 id="templateModalTitle">Шаблон размеров</h3>
                        <button type="button" onclick="closeTemplateModal()" class="btn-action btn-ghost btn-sm"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="template-modal-body">
                        <p class="modal-help">Шаблон задаёт размерную сетку при создании/редактировании сетки. Скопируйте данные ниже или используйте их как ориентир при ручном вводе:</p>
                        <table class="template-sizes-table">
                            <thead><tr><th>US</th><th>EU</th></tr></thead>
                            <tbody id="templateSizesBody"></tbody>
                        </table>
                    </div>
                    <div class="template-modal-footer">
                        <button type="button" onclick="closeTemplateModal()" class="btn-action btn-ghost">Закрыть</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="size-grid-hero">
            <div class="hero-context">
                <p class="hero-eyebrow">Каталог · Размеры</p>
                <h1><?= Html::encode($this->title) ?></h1>
                <p class="hero-subtitle">
                    Управляйте сетками и привязывайте их к товарам. Сегодня в системе
                    <strong><?= Html::encode($stats['total']) ?></strong> сеток и
                    <strong><?= Html::encode($stats['sizes']) ?></strong> индивидуальных размеров.
                </p>
                <div class="hero-stats">
                    <span class="hero-stat">
                        <span>Активны</span>
                        <strong><?= Html::encode($stats['active']) ?></strong>
                    </span>
                    <span class="hero-stat">
                        <span>С брендом</span>
                        <strong><?= Html::encode($stats['withBrand']) ?></strong>
                    </span>
                    <span class="hero-stat">
                        <span>Всего размеров</span>
                        <strong><?= Html::encode($stats['sizes']) ?></strong>
                    </span>
                </div>
            </div>
            <div class="hero-actions">
                <a href="<?= Url::to(['create']) ?>" class="btn-action btn-primary">
                    <i class="bi bi-plus-circle"></i> Новая сетка
                </a>
                <a href="<?= Url::to(['/admin/characteristic/index']) ?>" class="btn-action btn-ghost">
                    <i class="bi bi-sliders"></i> Характеристики
                </a>
            </div>
        </section>

        <section class="size-grid-filters" id="filtersPanel">
            <?php Pjax::begin(['id' => 'sizeGridFilters']); ?>
            <form method="get" data-pjax>
                <div class="filters-grid">
                    <label class="filter-control">
                        <span>Поиск</span>
                        <div class="input-with-icon">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" value="<?= Html::encode($search) ?>" placeholder="Название сетки или бренд">
                        </div>
                    </label>
                    <label class="filter-control">
                        <span>Пол</span>
                        <select name="gender">
                            <option value="">Все</option>
                            <?php foreach ($genderOptions as $key => $label): ?>
                                <option value="<?= Html::encode($key) ?>" <?= $gender === $key ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="filter-control">
                        <span>Бренд</span>
                        <select name="brand">
                            <option value="">Все</option>
                            <?php foreach ($brandOptions as $id => $label): ?>
                                <option value="<?= Html::encode($id) ?>" <?= (string)$brandId === (string)$id ? 'selected' : '' ?>>
                                    <?= Html::encode($label) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="0" <?= $brandId === '0' ? 'selected' : '' ?>>Без бренда</option>
                        </select>
                    </label>
                    <label class="filter-control">
                        <span>Статус</span>
                        <select name="status">
                            <option value="">Все</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Активна</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Архив</option>
                        </select>
                    </label>
                </div>
                <div class="filters-actions">
                    <button type="submit" class="btn-action btn-primary"><i class="bi bi-filter"></i> Применить</button>
                    <a href="<?= Url::to(['index']) ?>" class="btn-action btn-ghost">Сбросить</a>
                </div>
            </form>
            <?php Pjax::end(); ?>
        </section>

        <section class="size-grid-list-section">
            <?php Pjax::begin(['id' => 'sizeGridList']); ?>
            <?= ListView::widget([
                'dataProvider' => $dataProvider,
                'itemView' => '_grid_card',
                'layout' => "{items}\n{pager}",
                'options' => ['class' => 'size-grid-list'],
                'pager' => [
                    'class' => yii\bootstrap5\LinkPager::class,
                    'options' => ['class' => 'pagination justify-content-center'],
                ],
            ]) ?>
            <?php Pjax::end(); ?>
        </section>
    </div>
</div>

<style>
.brand-templates-section {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.brand-templates-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}
.brand-templates-header h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--admin-text, #1e293b);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.brand-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.brand-template-card {
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: 0.75rem;
    padding: 1rem;
    background: var(--admin-surface, #f8fafc);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.brand-template-name {
    font-weight: 700;
    color: var(--admin-accent, #2563eb);
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.brand-template-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.size-chip {
    background: var(--admin-border, #e2e8f0);
    color: var(--admin-text, #374151);
    border-radius: 4px;
    padding: 0.15rem 0.45rem;
    font-size: 0.72rem;
    white-space: nowrap;
}
.size-chip.muted {
    opacity: 0.6;
}
/* Template Modal */
.template-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.template-modal-content {
    background: var(--admin-surface, #fff);
    border-radius: 1rem;
    max-width: 480px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.template-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
}
.template-modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}
.template-modal-body {
    padding: 1.5rem;
}
.modal-help {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 1rem;
}
.template-sizes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.template-sizes-table th,
.template-sizes-table td {
    padding: 0.4rem 0.75rem;
    border: 1px solid var(--admin-border, #e2e8f0);
    text-align: center;
}
.template-sizes-table thead th {
    background: var(--admin-surface, #f8fafc);
    font-weight: 700;
}
.template-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--admin-border, #e2e8f0);
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
function applyBrandTemplate(btn) {
    var brand = btn.dataset.brand;
    var sizes = JSON.parse(btn.dataset.sizes);
    document.getElementById('templateModalTitle').textContent = 'Шаблон: ' + brand;
    var tbody = document.getElementById('templateSizesBody');
    tbody.innerHTML = '';
    sizes.forEach(function(s) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>US ' + s.us + '</td><td>EU ' + s.eu + '</td>';
        tbody.appendChild(tr);
    });
    document.getElementById('templatePreviewModal').style.display = 'flex';
}
function closeTemplateModal() {
    document.getElementById('templatePreviewModal').style.display = 'none';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTemplateModal();
});
document.getElementById('templatePreviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeTemplateModal();
});
</script>
