<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Characteristic;

/**
 * @var yii\web\View $this
 * @var yii\base\DynamicModel $model
 * @var app\models\Characteristic[] $characteristics
 * @var array $brands
 * @var array $categories
 */

$this->title = 'Массовое назначение характеристик';
$this->params['breadcrumbs'][] = ['label' => 'Характеристики', 'url' => ['/admin/characteristic/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="characteristic-bulk-assign">
    <div class="page-header mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="text-muted">
            Выберите характеристику и значение, затем задайте фильтр по товарам. Все подходящие товары получат выбранное значение.
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <?= $form->field($model, 'characteristic_id')->dropDownList(
                        ArrayHelper::map($characteristics, 'id', 'name'),
                        ['prompt' => '-- Выберите характеристику --', 'id' => 'bulk-characteristic-select']
                    ) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'value_id')->dropDownList([], [
                        'prompt' => '-- Сначала выберите характеристику --',
                        'id' => 'bulk-characteristic-value',
                    ]) ?>
                </div>
            </div>

            <hr>

            <h5>Фильтр по товарам</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <?= $form->field($model, 'brand_id')->dropDownList($brands, [
                        'prompt' => '-- Любой бренд --'
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'category_id')->dropDownList($categories, [
                        'prompt' => '-- Любая категория --'
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'price_from')->input('number', ['min' => 0, 'step' => '0.01']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'price_to')->input('number', ['min' => 0, 'step' => '0.01']) ?>
                </div>
            </div>

            <div class="alert alert-warning mt-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Внимание:</strong> операция необратима. Перед запуском рекомендуем экспортировать список товаров.
            </div>

            <div class="form-actions mt-4">
                <?= Html::submitButton('<i class="bi bi-check-circle"></i> Назначить значения', ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/characteristic/index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$valuesMap = [];
foreach ($characteristics as $characteristic) {
    $valuesMap[$characteristic->id] = ArrayHelper::map($characteristic->values, 'id', 'value');
}
$valuesJson = json_encode($valuesMap, JSON_UNESCAPED_UNICODE);

$js = <<<JS
(function(){
    const valuesData = {$valuesJson};
    const selectCharacteristic = document.getElementById('bulk-characteristic-select');
    const selectValue = document.getElementById('bulk-characteristic-value');

    function populateValues(characteristicId) {
        selectValue.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = '-- Выберите значение --';
        selectValue.appendChild(option);

        if (!characteristicId || !valuesData[characteristicId]) {
            option.textContent = '-- Сначала выберите характеристику --';
            return;
        }

        Object.entries(valuesData[characteristicId]).forEach(([id, label]) => {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = label;
            selectValue.appendChild(opt);
        });
    }

    if (selectCharacteristic) {
        selectCharacteristic.addEventListener('change', function(){
            populateValues(this.value);
        });
        if (selectCharacteristic.value) {
            populateValues(selectCharacteristic.value);
        }
    }
})();
JS;
$this->registerJs($js);
?>
