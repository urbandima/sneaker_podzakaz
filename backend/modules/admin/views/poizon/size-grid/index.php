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
?>

<div class="size-grid-admin" id="sizeGridPage">
    <div class="size-grid-shell">
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
