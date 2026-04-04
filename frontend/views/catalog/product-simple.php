<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product|stdClass $product */

use yii\helpers\Html;
use yii\helpers\Url;

$title = $product->name ?? 'Товар';
$this->title = $title . ' — СНИКЕРХЭД';

$this->registerMetaTag(['name' => 'description', 'content' => 'Описание товара']);
?>
<div class="container">
    <h1><?= Html::encode($title) ?></h1>
    <p>Тестовая страница товара</p>
    <a href="<?= Url::to(['/catalog']) ?>">Вернуться в каталог</a>
</div>
