<?php

use app\helpers\ProductCardHelper;
use app\models\Product;

/**
 * Частичный шаблон каталога для списка товаров.
 *
 * Контракт:
 * - $products — уже подготовленные app\models\Product (with() должен подтянуть brand/sizes).
 * - View ожидает, что размеры и активные фильтры попадают в $_GET (см. CatalogController::actionFilter()).
 *
 * Важно: actionFilter() временно записывает POST-параметры в $_GET ради совместимости с applyFilters().
 * Это место помечено как "Нужен ручной пересмотр" в контроллере — при рефакторинге стоит убрать прямую зависимость view от глобального состояния.
 */
/** @var $products app\models\Product[] */

$lazyPlaceholder = ProductCardHelper::LAZY_PLACEHOLDER;
$selectedSizesParam = Yii::$app->request->get('sizes');
$selectedSizesArray = ProductCardHelper::normalizeSelectedSizes($selectedSizesParam);
$currentSizeSystem = Yii::$app->request->get('size_system', ProductCardHelper::DEFAULT_SIZE_SYSTEM);
$sizeField = ProductCardHelper::resolveSizeField($currentSizeSystem);
$searchQuery = isset($searchQuery) ? $searchQuery : trim(Yii::$app->request->get('q', ''));
?>

<?php if (empty($products)): ?>
    <div class="empty">
        <i class="bi bi-inbox"></i>
        <h3>Товары не найдены</h3>
        <button onclick="resetFilters()">Сбросить фильтры</button>
    </div>
<?php else: ?>
    <?php foreach ($products as $index => $product): ?>
        <?php
            $isCriticalCard = $index < ProductCardHelper::CRITICAL_CARD_THRESHOLD;

            // CMP-252: preload первой картинки каталога для LCP.
            // Отправляется в <head> через params['lcpImageUrl'], которые подхватывает layout.
            if ($index === 0 && empty($this->params['lcpImageUrl'])) {
                $firstImages = ProductCardHelper::buildGalleryImages($product, $lazyPlaceholder);
                $firstUrl    = $firstImages[0] ?? null;
                if ($firstUrl && !str_starts_with($firstUrl, 'data:')) {
                    $this->params['lcpImageUrl'] = $firstUrl;
                }
            }

            // Передаём вычисленные параметры, чтобы карточка не повторяла запросы к Yii::$app->request.
            echo $this->render('_product_card', [
                'product' => $product,
                'isCriticalCard' => $isCriticalCard,
                'selectedSizesParam' => $selectedSizesParam,
                'selectedSizesArray' => $selectedSizesArray,
                'currentSizeSystem' => $currentSizeSystem,
                'sizeField' => $sizeField,
                'lazyPlaceholder' => $lazyPlaceholder,
                'searchQuery' => $searchQuery,
            ]);
        ?>
    <?php endforeach; ?>
<?php endif; ?>

