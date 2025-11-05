<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var array $brands */
/** @var array $categories */

$this->title = 'Управление товарами';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-products-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <small>Управление каталогом товаров с интеграцией Poizon. Используйте меню "📦 Товары" для импорта.</small>
            </p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-book"></i> Справочники
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Информационные</h6></li>
                <li>
                    <?= Html::a(
                        '<i class="bi bi-tags"></i> Справочник характеристик',
                        ['characteristics-guide'],
                        ['class' => 'dropdown-item']
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<i class="bi bi-info-circle"></i> Информация о размерах',
                        ['size-guide'],
                        ['class' => 'dropdown-item']
                    ) ?>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Управление</h6></li>
                <li>
                    <?= Html::a(
                        '<i class="bi bi-rulers"></i> Размерные сетки',
                        ['size-grids'],
                        ['class' => 'dropdown-item']
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<i class="bi bi-plus-circle"></i> Создать размерную сетку',
                        ['create-size-grid'],
                        ['class' => 'dropdown-item']
                    ) ?>
                </li>
            </ul>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h3 class="mt-2"><?= number_format($stats['total']) ?></h3>
                    <p class="text-muted mb-0">Всего товаров</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <h3 class="mt-2"><?= number_format($stats['active']) ?></h3>
                    <p class="text-muted mb-0">Активные</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-cloud-download fs-1 text-info"></i>
                    <h3 class="mt-2"><?= number_format($stats['poizon']) ?></h3>
                    <p class="text-muted mb-0">Из Poizon</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <i class="bi bi-pencil-square fs-1 text-secondary"></i>
                    <h3 class="mt-2"><?= number_format($stats['manual']) ?></h3>
                    <p class="text-muted mb-0">Ручные</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Фильтры</h5>
        </div>
        <div class="card-body">
            <form method="get" action="<?= Url::to(['products']) ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Поиск</label>
                        <input type="text" name="search" class="form-control" 
                               value="<?= Html::encode($filterSearch) ?>" 
                               placeholder="Название, SKU, Poizon ID...">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Бренд</label>
                        <select name="brand" class="form-select">
                            <option value="">Все</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand->id ?>" <?= $filterBrand == $brand->id ? 'selected' : '' ?>>
                                    <?= Html::encode($brand->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Категория</label>
                        <select name="category" class="form-select">
                            <option value="">Все</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category->id ?>" <?= $filterCategory == $category->id ? 'selected' : '' ?>>
                                    <?= Html::encode($category->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Источник</label>
                        <select name="source" class="form-select">
                            <option value="">Все</option>
                            <option value="poizon" <?= $filterSource === 'poizon' ? 'selected' : '' ?>>Poizon</option>
                            <option value="manual" <?= $filterSource === 'manual' ? 'selected' : '' ?>>Ручные</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Статус</label>
                        <select name="is_active" class="form-select">
                            <option value="">Все</option>
                            <option value="1" <?= $filterActive === '1' ? 'selected' : '' ?>>Активные</option>
                            <option value="0" <?= $filterActive === '0' ? 'selected' : '' ?>>Неактивные</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Кнопки быстрых действий Poizon теперь в меню -->
    <?php /* Закомментировано - теперь в выпадающем меню "📦 Товары"
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-info-circle"></i>
            <strong>Импорт из Poizon:</strong> Быстрый импорт и обновление товаров из Poizon
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-play-circle"></i> Запустить импорт', ['poizon-run'], [
                'class' => 'btn btn-sm btn-success',
            ]) ?>
            <?= Html::a('<i class="bi bi-arrow-repeat"></i> Обновить цены', ['poizon-import'], [
                'class' => 'btn btn-sm btn-info',
            ]) ?>
            <?= Html::a('<i class="bi bi-list-check"></i> История импорта', ['poizon-import'], [
                'class' => 'btn btn-sm btn-secondary',
            ]) ?>
        </div>
    </div>
    */ ?>

    <!-- Таблица товаров -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['width' => '60'],
                    ],
                    [
                        'label' => 'Фото',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->images && count($model->images) > 0) {
                                $image = $model->images[0];
                                return Html::img($image->getImageUrl(), [
                                    'style' => 'width: 60px; height: 60px; object-fit: cover; border-radius: 4px;'
                                ]);
                            }
                            return '<div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-image text-muted"></i></div>';
                        },
                        'headerOptions' => ['width' => '80'],
                    ],
                    [
                        'attribute' => 'name',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $name = Html::a(Html::encode($model->name), ['view-product', 'id' => $model->id], [
                                'style' => 'font-weight: 500; color: #2c3e50;'
                            ]);
                            
                            $meta = [];
                            $badges = [];
                            
                            // Vendor code (если поле существует после миграции)
                            if ($model->hasAttribute('vendor_code') && $model->vendor_code) {
                                $meta[] = '<span class="text-muted" title="Артикул">📋 ' . Html::encode($model->vendor_code) . '</span>';
                            }
                            
                            // Badges
                            if ($model->hasAttribute('poizon_id') && $model->poizon_id) {
                                $badges[] = '<span class="badge bg-info" title="Импортирован из Poizon">🏷 Poizon</span>';
                            }
                            if ($model->hasAttribute('parent_product_id') && $model->parent_product_id) {
                                $badges[] = '<span class="badge bg-secondary" title="Вариант товара">🔗 Variant</span>';
                            }
                            if ($model->is_limited) {
                                $badges[] = '<span class="badge bg-warning text-dark">⭐ Limited</span>';
                            }
                            if (!$model->is_active) {
                                $badges[] = '<span class="badge bg-secondary">❌ Неактивен</span>';
                            }
                            
                            $metaHtml = count($meta) > 0 ? '<br><small>' . implode(' • ', $meta) . '</small>' : '';
                            $badgeHtml = count($badges) > 0 ? '<br>' . implode(' ', $badges) : '';
                            
                            return $name . $metaHtml . $badgeHtml;
                        },
                    ],
                    [
                        'attribute' => 'sku',
                        'headerOptions' => ['width' => '120'],
                    ],
                    [
                        'attribute' => 'brand_id',
                        'value' => function ($model) {
                            return $model->brand ? $model->brand->name : '-';
                        },
                        'headerOptions' => ['width' => '120'],
                    ],
                    [
                        'label' => 'Цены',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '<div>';
                            
                            // Цена продажи
                            $html .= '<strong class="text-success">' . ($model->price ? number_format($model->price, 2) : '0.00') . ' BYN</strong>';
                            
                            // Закупочная цена (если поле существует)
                            if ($model->hasAttribute('purchase_price') && $model->purchase_price > 0) {
                                $margin = $model->price - $model->purchase_price;
                                $marginPercent = round(($margin / $model->purchase_price) * 100);
                                $html .= '<br><small class="text-muted" title="Закупочная цена">💰 ' . ($model->purchase_price ? number_format($model->purchase_price, 2) : '0.00') . ' BYN</small>';
                                $html .= '<br><small class="text-info" title="Наценка">📈 +' . $marginPercent . '%</small>';
                            }
                            
                            // Цена CNY
                            if ($model->hasAttribute('poizon_price_cny') && $model->poizon_price_cny) {
                                $html .= '<br><small class="text-muted" title="Цена в юанях">¥ ' . number_format($model->poizon_price_cny, 2) . '</small>';
                            }
                            
                            $html .= '</div>';
                            return $html;
                        },
                        'headerOptions' => ['width' => '140'],
                    ],
                    [
                        'label' => 'Наличие',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '<div>';
                            
                            // Количество на складе (если поле существует)
                            if ($model->hasAttribute('stock_count')) {
                                if ($model->stock_count > 0) {
                                    $html .= '<span class="badge bg-success" title="На складе">📦 ' . $model->stock_count . ' шт</span>';
                                } else {
                                    $html .= '<span class="badge bg-warning text-dark" title="Под заказ">⏳ Под заказ</span>';
                                }
                            } else {
                                // Если нет поля - показываем старый способ
                                $html .= '<span class="badge bg-warning text-dark" title="Под заказ">⏳ Под заказ</span>';
                            }
                            
                            // Время доставки (если поля существуют)
                            if ($model->hasAttribute('delivery_time_min') && $model->hasAttribute('delivery_time_max') && $model->delivery_time_min && $model->delivery_time_max) {
                                $html .= '<br><small class="text-muted" title="Время доставки">🚚 ' . $model->delivery_time_min . '-' . $model->delivery_time_max . ' дней</small>';
                            }
                            
                            // Варианты (если поле существует)
                            if ($model->hasAttribute('parent_product_id') && !$model->parent_product_id) {
                                $variantsCount = \app\models\Product::find()
                                    ->where(['parent_product_id' => $model->id])
                                    ->count();
                                if ($variantsCount > 0) {
                                    $html .= '<br><small class="text-info" title="Количество вариантов">🎨 ' . $variantsCount . ' вариантов</small>';
                                }
                            }
                            
                            $html .= '</div>';
                            return $html;
                        },
                        'headerOptions' => ['width' => '130'],
                    ],
                    [
                        'label' => 'Инфо',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $html = '<div style="font-size: 12px;">';
                            
                            // Синхронизация
                            if ($model->hasAttribute('poizon_id') && $model->poizon_id) {
                                if ($model->hasAttribute('last_sync_at') && $model->last_sync_at) {
                                    $diff = time() - strtotime($model->last_sync_at);
                                    $hours = floor($diff / 3600);
                                    
                                    if ($hours < 24) {
                                        $color = 'success';
                                        $text = $hours . 'ч назад';
                                        $icon = '✅';
                                    } elseif ($hours < 72) {
                                        $color = 'warning';
                                        $text = floor($hours / 24) . 'д назад';
                                        $icon = '⚠️';
                                    } else {
                                        $color = 'danger';
                                        $text = floor($hours / 24) . 'д назад';
                                        $icon = '❌';
                                    }
                                    
                                    $html .= '<span class="text-' . $color . '" title="Последняя синхронизация">' . $icon . ' ' . $text . '</span>';
                                } else {
                                    $html .= '<span class="text-danger" title="Не синхронизирован">❌ Не синхр.</span>';
                                }
                            } else {
                                $html .= '<span class="text-muted">Ручной</span>';
                            }
                            
                            // Favorite count (если поле существует)
                            if ($model->hasAttribute('favorite_count') && $model->favorite_count > 0) {
                                $html .= '<br><span class="text-danger" title="Добавлено в избранное">❤️ ' . $model->favorite_count . '</span>';
                            }
                            
                            // Country (если поле существует)
                            if ($model->hasAttribute('country_of_origin') && $model->country_of_origin) {
                                $html .= '<br><span class="text-muted" title="Страна">🌍 ' . Html::encode($model->country_of_origin) . '</span>';
                            }
                            
                            $html .= '</div>';
                            return $html;
                        },
                        'headerOptions' => ['width' => '110'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {edit} {sync} {toggle} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="bi bi-eye"></i>', ['view-product', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'title' => 'Просмотр',
                                ]);
                            },
                            'edit' => function ($url, $model) {
                                return Html::a('<i class="bi bi-pencil"></i>', ['edit-product', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-secondary',
                                    'title' => 'Редактировать',
                                ]);
                            },
                            'sync' => function ($url, $model) {
                                if (!$model->poizon_id) {
                                    return '';
                                }
                                return Html::a('<i class="bi bi-arrow-repeat"></i>', ['sync-product', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-info',
                                    'title' => 'Синхронизировать с Poizon',
                                    'data-method' => 'post',
                                ]);
                            },
                            'toggle' => function ($url, $model) {
                                $icon = $model->is_active ? 'eye-slash' : 'eye';
                                $class = $model->is_active ? 'warning' : 'success';
                                return Html::a('<i class="bi bi-' . $icon . '"></i>', ['toggle-product', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-' . $class,
                                    'title' => $model->is_active ? 'Деактивировать' : 'Активировать',
                                    'data-method' => 'post',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="bi bi-trash"></i>', ['delete-product', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Удалить',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Вы уверены, что хотите удалить этот товар?',
                                ]);
                            },
                        ],
                        'headerOptions' => ['width' => '200'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
