<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $characteristicsProvider
 * @var yii\data\ActiveDataProvider $sizeGridProvider
 * @var string $tab
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Характеристики и размеры';

$this->params['headerActions'] = [
    $tab === 'sizes'
        ? Html::a('<i class="bi bi-plus-circle"></i> Новая сетка', ['size-create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm'])
        : Html::a('<i class="bi bi-plus-circle"></i> Новая характеристика', ['create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']),
];
?>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:1px solid var(--admin-border);margin-bottom:16px">
    <a href="<?= Url::to(['index', 'tab' => 'characteristics']) ?>"
       style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $tab === 'characteristics' ? 'var(--admin-accent)' : 'transparent' ?>;color:<?= $tab === 'characteristics' ? 'var(--admin-accent)' : 'var(--admin-text-secondary)' ?>">
        <i class="bi bi-sliders"></i> Характеристики
    </a>
    <a href="<?= Url::to(['index', 'tab' => 'sizes']) ?>"
       style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $tab === 'sizes' ? 'var(--admin-accent)' : 'transparent' ?>;color:<?= $tab === 'sizes' ? 'var(--admin-accent)' : 'var(--admin-text-secondary)' ?>">
        <i class="bi bi-rulers"></i> Размерные сетки
    </a>
</div>

<?php if ($tab === 'sizes'): ?>
<!-- SIZE GRIDS -->
<div class="admin-card">
    <div class="admin-card-body" style="padding:0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Бренд</th>
                    <th>Пол</th>
                    <th>Размеров</th>
                    <th>Статус</th>
                    <th>Создана</th>
                    <th style="width:100px">Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sizeGridProvider->getModels() as $grid): ?>
            <tr>
                <td style="color:var(--admin-text-secondary);font-size:12px">#<?= $grid->id ?></td>
                <td>
                    <a href="<?= Url::to(['size-update', 'id' => $grid->id]) ?>" style="font-weight:600;text-decoration:none;color:var(--admin-text)">
                        <?= Html::encode($grid->name) ?>
                    </a>
                </td>
                <td>
                    <?php if ($grid->brand): ?>
                        <span class="admin-badge admin-badge-secondary"><?= Html::encode($grid->brand->name) ?></span>
                    <?php else: ?>
                        <span style="color:var(--admin-text-secondary);font-size:12px">Универсальная</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $genderLabels = ['male'=>'Мужской','female'=>'Женский','unisex'=>'Унисекс','kids'=>'Детский'];
                    $genderBadge = ['male'=>'admin-badge-primary','female'=>'admin-badge-danger','unisex'=>'admin-badge-info','kids'=>'admin-badge-success'];
                    $g = $grid->gender ?? 'unisex';
                    ?>
                    <span class="admin-badge <?= $genderBadge[$g] ?? 'admin-badge-secondary' ?>"><?= $genderLabels[$g] ?? $g ?></span>
                </td>
                <td><?= count($grid->items) ?></td>
                <td>
                    <span class="admin-badge <?= $grid->is_active ? 'admin-badge-success' : 'admin-badge-secondary' ?>">
                        <?= $grid->is_active ? 'Активна' : 'Неактивна' ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asDate($grid->created_at) ?></td>
                <td>
                    <div style="display:flex;gap:4px">
                        <?= Html::a('<i class="bi bi-pencil"></i>', ['size-update', 'id' => $grid->id], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm', 'title' => 'Редактировать']) ?>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['size-delete', 'id' => $grid->id], ['class' => 'admin-btn admin-btn-sm', 'style' => 'color:var(--admin-danger)', 'data-confirm' => 'Удалить размерную сетку?', 'data-method' => 'post']) ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$sizeGridProvider->getModels()): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:3rem;color:var(--admin-text-secondary)">
                    <i class="bi bi-rulers" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
                    Нет размерных сеток. <?= Html::a('Создать первую', ['size-create']) ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- CHARACTERISTICS -->
<!-- Z34: note about sizes being in a separate section -->
<div style="margin-bottom:12px;padding:10px 14px;background:var(--admin-surface-hover,#f6f6f7);border-radius:8px;font-size:12px;color:var(--admin-text-secondary,#6d7175);border:1px solid var(--admin-border,#e1e3e5)">
    <i class="bi bi-info-circle"></i>
    Размеры (41.5 EU / 8 US и др.) управляются через раздел
    <a href="<?= Url::to(['index', 'tab' => 'sizes']) ?>" style="color:var(--admin-accent)">Размерные сетки</a>.
    Здесь хранятся прочие характеристики товара (цвет, материал, пол и т.д.).
</div>

<?php if (empty($characteristicsProvider->getModels())): ?>
<div style="text-align:center;padding:3rem;color:var(--admin-text-secondary,#6d7175)">
    <i class="bi bi-list-ul" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3"></i>
    <p>Характеристики не созданы. <?= Html::a('Создать первую', ['create']) ?></p>
</div>
<?php else: ?>
<div class="admin-card">
    <div class="admin-card-body" style="padding:0">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ключ</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Фильтр</th>
                    <th>Значений</th>
                    <th>Статус</th>
                    <th style="width:80px">Порядок</th>
                    <th style="width:100px">Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($characteristicsProvider->getModels() as $char): ?>
            <tr>
                <td style="color:var(--admin-text-secondary);font-size:12px">#<?= $char->id ?></td>
                <td><code style="font-size:12px;background:var(--admin-surface-hover);padding:2px 6px;border-radius:4px"><?= Html::encode($char->key) ?></code></td>
                <td>
                    <a href="<?= Url::to(['update', 'id' => $char->id]) ?>" style="font-weight:600;text-decoration:none;color:var(--admin-text)">
                        <?= Html::encode($char->name) ?>
                    </a>
                </td>
                <td>
                    <?php
                    $typeLabels = ['select'=>'Список','multiselect'=>'Мультивыбор','boolean'=>'Да/Нет','color'=>'Цвет','size'=>'Размер','text'=>'Текст','number'=>'Число'];
                    ?>
                    <span class="admin-badge admin-badge-secondary" style="font-size:11px"><?= $typeLabels[$char->type] ?? Html::encode($char->type) ?></span>
                </td>
                <td>
                    <?php if ($char->is_filter): ?>
                        <span class="admin-badge admin-badge-info"><i class="bi bi-funnel"></i></span>
                    <?php else: ?>
                        <span style="color:var(--admin-text-secondary);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    try { $valCount = $char->getValues()->count(); } catch (\Exception $e) { $valCount = '?'; }
                    ?>
                    <span class="admin-badge admin-badge-secondary"><?= $valCount ?></span>
                </td>
                <td>
                    <span class="admin-badge <?= $char->is_active ? 'admin-badge-success' : 'admin-badge-secondary' ?>">
                        <?= $char->is_active ? 'Активна' : 'Отключена' ?>
                    </span>
                </td>
                <td style="text-align:center"><?= (int)$char->sort_order ?></td>
                <td>
                    <div style="display:flex;gap:4px">
                        <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $char->id], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']) ?>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $char->id], ['class' => 'admin-btn admin-btn-sm', 'style' => 'color:var(--admin-danger)', 'data-confirm' => 'Удалить характеристику и все её значения?', 'data-method' => 'post']) ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
