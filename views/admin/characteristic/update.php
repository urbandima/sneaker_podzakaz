<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Characteristic */
/* @var $values app\models\CharacteristicValue[] */

$this->title = 'Изменить характеристику: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Характеристики', 'url' => ['/admin/characteristic/index']];
$this->params['breadcrumbs'][] = 'Редактирование';
?>

<div class="characteristic-update">
    <div class="page-header mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Обновите свойства и значения характеристики. Все изменения мгновенно влияют на фильтры каталога.</p>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'values' => $values,
    ]) ?>
</div>
