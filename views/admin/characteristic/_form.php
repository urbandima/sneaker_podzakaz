<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Characteristic;

/**
 * @var yii\web\View $this
 * @var app\models\Characteristic $model
 * @var app\models\CharacteristicValue[] $values
 */
?>

<div class="characteristic-form">
    <?php $form = ActiveForm::begin([
        'options' => ['data-controller' => 'characteristic-form'],
    ]); ?>

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Например: Материал, Сезон, Пол'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'key')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Латинский ключ, например: material'
                    ])->hint('Используется в API и импортах. Только буквы, цифры, символ "_".') ?>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <?= $form->field($model, 'type')->dropDownList(
                        Characteristic::getTypeList(),
                        ['prompt' => '-- Выберите тип --']
                    ) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                </div>
                <div class="col-md-2 pt-4">
                    <?= $form->field($model, 'is_filter')->checkbox()->label('Показывать в фильтрах') ?>
                </div>
                <div class="col-md-2 pt-4">
                    <?= $form->field($model, 'is_required')->checkbox()->label('Обязательная') ?>
                </div>
                <div class="col-md-2 pt-4">
                    <?= $form->field($model, 'is_active')->checkbox()->label('Активна') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Значения характеристики</strong>
                <span class="text-muted">(для типов select/multiselect)</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-value-row">
                <i class="bi bi-plus-circle"></i> Добавить значение
            </button>
        </div>
        <div class="card-body">
            <div id="values-container" class="row g-3">
                <?php foreach ($values as $index => $value): ?>
                    <div class="col-md-6 value-row" data-index="<?= $index ?>">
                        <?= Html::activeHiddenInput($value, "[$index]id") ?>
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Значение #<?= $index + 1 ?></strong>
                                    <button type="button" class="btn btn-sm btn-link text-danger remove-value-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <?= Html::activeLabel($value, "[$index]value", ['class' => 'form-label']) ?>
                                    <?= Html::activeTextInput($value, "[$index]value", [
                                        'class' => 'form-control',
                                        'placeholder' => 'Например: Кожа, Зима, Мужской',
                                        'required' => true,
                                    ]) ?>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <?= Html::activeLabel($value, "[$index]slug", ['class' => 'form-label']) ?>
                                        <?= Html::activeTextInput($value, "[$index]slug", [
                                            'class' => 'form-control',
                                            'placeholder' => 'slug'
                                        ]) ?>
                                        <div class="form-text">Необязательно. Если пусто — сгенерируется автоматически.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <?= Html::activeLabel($value, "[$index]sort_order", ['class' => 'form-label']) ?>
                                        <?= Html::activeInput('number', $value, "[$index]sort_order", [
                                            'class' => 'form-control',
                                            'min' => 0,
                                        ]) ?>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-center">
                                        <div class="form-check mt-3">
                                            <?= Html::activeCheckbox($value, "[$index]is_active", [
                                                'class' => 'form-check-input',
                                                'label' => 'Активно',
                                                'checked' => $value->is_active ?? true,
                                            ]) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template id="value-row-template">
                <div class="col-md-6 value-row" data-index="__index__">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Значение #__number__</strong>
                                <button type="button" class="btn btn-sm btn-link text-danger remove-value-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Значение</label>
                                <input type="text" class="form-control" name="CharacteristicValue[__index__][value]" placeholder="Например: Кожа" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Slug</label>
                                    <input type="text" class="form-control" name="CharacteristicValue[__index__][slug]" placeholder="slug">
                                    <div class="form-text">Необязательно</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Порядок</label>
                                    <input type="number" class="form-control" name="CharacteristicValue[__index__][sort_order]" value="0" min="0">
                                </div>
                                <div class="col-md-3 d-flex align-items-center">
                                    <div class="form-check mt-3">
                                        <input type="hidden" name="CharacteristicValue[__index__][is_active]" value="0">
                                        <input type="checkbox" class="form-check-input" name="CharacteristicValue[__index__][is_active]" value="1" checked>
                                        <label class="form-check-label">Активно</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="form-actions mt-4">
        <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/characteristic/index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<'JS'
(function(){
    const container = document.getElementById('values-container');
    const template = document.getElementById('value-row-template').innerHTML;
    const addButton = document.getElementById('add-value-row');

    function reindexRows() {
        if (!container) return;
        const rows = container.querySelectorAll('.value-row');
        rows.forEach((row, index) => {
            row.setAttribute('data-index', index);
            const title = row.querySelector('strong');
            if (title) {
                title.textContent = `Значение #${index + 1}`;
            }
            row.querySelectorAll('input, select, textarea').forEach(input => {
                if (!input.name) return;
                input.name = input.name.replace(/CharacteristicValue\[\d+\]/, `CharacteristicValue[${index}]`);
            });
        });
    }

    function addRow() {
        if (!container) return;
        const currentIndex = container.querySelectorAll('.value-row').length;
        const html = template
            .replace(/__index__/g, currentIndex)
            .replace(/__number__/g, currentIndex + 1);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        container.appendChild(tempDiv.firstChild);
        reindexRows();
    }

    function removeRow(button) {
        if (!container) return;
        const row = button.closest('.value-row');
        if (!row) return;
        row.remove();
        reindexRows();
    }

    if (addButton) {
        addButton.addEventListener('click', function(){
            addRow();
        });
    }

    if (container) {
        container.addEventListener('click', function(event){
            const target = event.target;
            if (target.closest('.remove-value-btn')) {
                event.preventDefault();
                removeRow(target.closest('.remove-value-btn'));
            }
        });
    }
})();
JS;
$this->registerJs($js);
?>
