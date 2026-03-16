<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\base\DynamicModel $model
 */

$this->title = 'Импорт характеристик из CSV';
$this->params['breadcrumbs'][] = ['label' => 'Характеристики', 'url' => ['/admin/characteristic/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="characteristic-import">
    <div class="page-header mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Загрузите CSV файл со столбцами <code>key, name, type, value, slug, sort_order</code>. Новые характеристики будут созданы автоматически.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'options' => ['enctype' => 'multipart/form-data']
            ]); ?>

            <?= $form->field($model, 'file')->fileInput() ?>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Пример строки CSV:
                <pre class="mb-0">material,Материал,select,Кожа,leather,0</pre>
            </div>

            <div class="form-actions mt-4">
                <?= Html::submitButton('<i class="bi bi-upload"></i> Импортировать', ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/characteristic/index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
