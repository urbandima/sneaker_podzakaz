<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Characteristic */
/* @var $values app\models\CharacteristicValue[] */

$this->title = 'Создать характеристику';
$this->params['breadcrumbs'][] = ['label' => 'Характеристики', 'url' => ['/admin/characteristic/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="characteristic-create">
    <div class="page-header mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Создайте новую характеристику товара и задайте значения для фильтрации.</p>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'values' => $values,
    ]) ?>
</div>
