<?php
/**
 * Partial для рендеринга одной характеристики-фильтра
 * 
 * @var app\models\Characteristic $characteristic Данные характеристики
 * @var array $currentFilters Текущие активные фильтры
 */

use yii\helpers\Html;

$charKey = 'char_' . $characteristic['id'];
$currentValues = $currentFilters[$charKey] ?? [];
?>

<div class="filter-group">
    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
        <span><?= Html::encode($characteristic['name']) ?></span>
        <i class="bi bi-chevron-down"></i>
    </h4>
    <div class="filter-content" style="display:none">
        <?php if ($characteristic['type'] === 'select' || $characteristic['type'] === 'multiselect'): ?>
            <!-- Select/Multiselect: чекбоксы с количеством -->
            <?php if (count($characteristic['values']) > 8): ?>
                <input type="text" class="filter-search" placeholder="Поиск..." oninput="searchInFilter(this, '.char-<?= $characteristic['id'] ?>-item')">
            <?php endif; ?>
            <div class="filter-scroll">
                <?php foreach ($characteristic['values'] as $value): ?>
                    <?php $count = $value['count'] ?? 0; ?>
                    <?php
                    // ИСПРАВЛЕНО: Для полей product (gender, season и т.д.) value['id'] - это строка, а не число
                    $valueId = $value['id'];
                    $isChecked = is_array($currentValues) && in_array($valueId, $currentValues);
                    ?>
                    <label class="filter-item char-<?= $characteristic['id'] ?>-item <?= $count == 0 ? 'disabled' : '' ?>">
                        <input type="<?= $characteristic['type'] === 'select' ? 'radio' : 'checkbox' ?>" 
                               name="<?= $charKey ?>[]" 
                               value="<?= Html::encode($valueId) ?>" 
                               data-slug="<?= Html::encode($value['slug']) ?>"
                               <?= $isChecked ? 'checked' : '' ?>
                               <?= $count == 0 ? 'disabled' : '' ?>>
                        <span><?= Html::encode($value['value']) ?></span>
                        <span class="count"><?= $count ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            
        <?php elseif ($characteristic['type'] === 'number'): ?>
            <!-- Number: диапазон -->
            <div class="number-range">
                <input type="number" 
                       name="<?= $charKey ?>_from" 
                       placeholder="От <?= $characteristic['range']['min'] ?? 0 ?>"
                       min="<?= $characteristic['range']['min'] ?? 0 ?>"
                       max="<?= $characteristic['range']['max'] ?? 100 ?>"
                       value="<?= $currentFilters[$charKey . '_from'] ?? '' ?>">
                <span>—</span>
                <input type="number" 
                       name="<?= $charKey ?>_to" 
                       placeholder="До <?= $characteristic['range']['max'] ?? 100 ?>"
                       min="<?= $characteristic['range']['min'] ?? 0 ?>"
                       max="<?= $characteristic['range']['max'] ?? 100 ?>"
                       value="<?= $currentFilters[$charKey . '_to'] ?? '' ?>">
            </div>
            
        <?php elseif ($characteristic['type'] === 'boolean'): ?>
            <!-- Boolean: один чекбокс -->
            <label class="filter-item">
                <input type="checkbox" 
                       name="<?= $charKey ?>" 
                       value="1"
                       <?= !empty($currentFilters[$charKey]) ? 'checked' : '' ?>>
                <span>Да</span>
            </label>
        <?php endif; ?>
    </div>
</div>
