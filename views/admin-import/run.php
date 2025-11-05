<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */

$this->title = 'Запуск импорта товаров';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-run">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Настройки импорта</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['method' => 'post']); ?>

                    <div class="mb-3">
                        <label class="form-label">Лимит товаров</label>
                        <?= Html::input('number', 'limit', 100, [
                            'class' => 'form-control',
                            'min' => 1,
                            'max' => 10000,
                        ]) ?>
                        <small class="form-text text-muted">
                            Максимальное количество товаров для импорта (рекомендуется 100-500 для первого запуска)
                        </small>
                    </div>

                    <hr>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Информация об импорте:</h6>
                        <ul class="mb-0">
                            <li>Импорт запускается в фоновом режиме</li>
                            <li>Процесс может занять несколько минут</li>
                            <li>Все товары публикуются со статусом "Под заказ"</li>
                            <li>Срок доставки: 14-30 дней</li>
                            <li>Формула цены: CNY × курс × 1.5 + 40 BYN</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2">
                        <?= Html::submitButton('<i class="bi bi-play-circle-fill"></i> Запустить импорт', [
                            'class' => 'btn btn-success btn-lg',
                            'onclick' => 'return confirm("Вы уверены, что хотите запустить импорт?")',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Справка</h5>
                </div>
                <div class="card-body">
                    <h6>Что делает импорт?</h6>
                    <ul class="small">
                        <li>Загружает товары из Poizon API</li>
                        <li>Создает новые товары</li>
                        <li>Обновляет существующие</li>
                        <li>Импортирует размеры (US/EU/UK/CM)</li>
                        <li>Устанавливает характеристики обуви</li>
                        <li>Логирует все действия</li>
                    </ul>

                    <hr>

                    <h6>Консольные команды:</h6>
                    <pre class="small bg-dark text-light p-2 rounded"><code>php yii poizon-import/run --limit=100
php yii poizon-import/test
php yii poizon-import/logs</code></pre>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Быстрые ссылки</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?= Html::a('История импортов', ['index'], ['class' => 'list-group-item list-group-item-action']) ?>
                    <?= Html::a('Логи ошибок', ['errors'], ['class' => 'list-group-item list-group-item-action']) ?>
                    <?= Html::a('Управление товарами', ['/catalog/index'], ['class' => 'list-group-item list-group-item-action']) ?>
                </div>
            </div>
        </div>
    </div>

</div>
