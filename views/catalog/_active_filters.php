<?php
/** @var array $activeFilters */

use yii\helpers\Html;
?>

<?php if (!empty($activeFilters)): ?>
    <?php foreach ($activeFilters as $filter): ?>
        <div class="tag" data-filter-type="<?= Html::encode($filter['type'] ?? '') ?>">
            <?= Html::encode($filter['label']) ?>
            <a href="<?= Html::encode($filter['removeUrl']) ?>"><i class="bi bi-x"></i></a>
        </div>
    <?php endforeach; ?>
    <a href="/catalog/" class="clear-all">Сбросить все</a>
<?php endif; ?>
