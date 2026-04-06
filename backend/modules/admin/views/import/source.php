<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\backend\modules\admin\models\import\ImportSource;

/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\import\ImportSource $model */
/** @var app\backend\modules\admin\models\import\ImportCategoryMap[] $categoryMaps */
/** @var app\backend\modules\catalog\models\Category[] $categories */

$this->title = $model->isNewRecord ? 'Новый источник' : $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-source">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="row">
        <!-- Основные настройки -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Основные настройки</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'readonly' => !$model->isNewRecord]) ?>
                        </div>
                    </div>
                    
                    <?= $form->field($model, 'base_url')->textInput(['maxlength' => true]) ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'currency_code')->dropDownList([
                                'BYN' => 'BYN - Белорусский рубль',
                                'USD' => 'USD - Доллар США',
                                'EUR' => 'EUR - Евро',
                                'CNY' => 'CNY - Китайский юань',
                                'RUB' => 'RUB - Российский рубль',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'is_active')->checkbox() ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Настройки парсинга -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Настройки парсинга</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'parse_delay_min')->textInput(['type' => 'number', 'min' => 1, 'max' => 60]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'parse_delay_max')->textInput(['type' => 'number', 'min' => 1, 'max' => 60]) ?>
                        </div>
                    </div>
                    
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> 
                        Задержка между запросами к сайту (секунды). Увеличьте если сайт блокирует частые запросы.
                    </p>
                </div>
            </div>

            <!-- Прокси -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        Прокси
                        <?= $model->proxy_enabled ? '<span class="badge bg-success float-end">Включено</span>' : '' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?= $form->field($model, 'proxy_enabled')->checkbox() ?>
                    
                    <div id="proxy-settings" style="<?= $model->proxy_enabled ? '' : 'display: none;' ?>">
                        <?= $form->field($model, 'proxy_rotation')->dropDownList([
                            ImportSource::PROXY_ROTATION_RANDOM => 'Случайная ротация',
                            ImportSource::PROXY_ROTATION_SEQUENTIAL => 'Последовательная ротация',
                        ]) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Список прокси (один на строку)</label>
                            <textarea name="proxy_list_text" class="form-control" rows="6" 
                                      placeholder="http://user:pass@proxy:port&#10;socks5://user:pass@proxy:port"><?= 
                                implode("\n", $model->getProxyListArray())
                            ?></textarea>
                            <p class="text-muted small mt-1">
                                Формат: <code>http://user:pass@proxy:port</code> или <code>socks5://proxy:port</code>
                            </p>
                        </div>
                        
                        <?php if (!$model->isNewRecord && !empty($model->getProxyListArray())): ?>
                        <?= Html::a('<i class="fas fa-check"></i> Проверить прокси', ['check-proxies', 'sourceId' => $model->id], [
                            'class' => 'btn btn-outline-info btn-sm',
                        ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CAPTCHA -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">CAPTCHA сервисы</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'captcha_service')->dropDownList([
                                ImportSource::CAPTCHA_2CAPTCHA => '2Captcha',
                                ImportSource::CAPTCHA_ANTICAPTCHA => 'Anti-Captcha',
                                ImportSource::CAPTCHA_CAPMONSTER => 'CapMonster',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'captcha_api_key')->passwordInput(['maxlength' => true]) ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Резервный сервис</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'captcha_fallback_service')->dropDownList([
                                '' => '-- Не использовать --',
                                ImportSource::CAPTCHA_2CAPTCHA => '2Captcha',
                                ImportSource::CAPTCHA_ANTICAPTCHA => 'Anti-Captcha',
                                ImportSource::CAPTCHA_CAPMONSTER => 'CapMonster',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'captcha_fallback_api_key')->passwordInput(['maxlength' => true]) ?>
                        </div>
                    </div>
                    
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> 
                        Приоритет: основной сервис → резервный → остальные
                    </p>
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Статистика -->
            <?php if (!$model->isNewRecord): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Статистика</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Последний запуск:</td>
                            <td><?= $model->last_run_at 
                                ? Yii::$app->formatter->asDatetime($model->last_run_at) 
                                : '<span class="text-muted">Никогда</span>' ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Успешных запусков:</td>
                            <td class="text-success"><?= $model->successful_runs ?></td>
                        </tr>
                        <tr>
                            <td>Ошибок:</td>
                            <td class="text-danger"><?= $model->failed_runs ?></td>
                        </tr>
                        <tr>
                            <td>Товаров спарсено:</td>
                            <td><?= number_format($model->total_products_parsed, 0, '', ' ') ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($model->is_active): ?>
                    <?= Html::a('<i class="fas fa-play"></i> Запустить импорт', ['run', 'sourceId' => $model->id], [
                        'class' => 'btn btn-success w-100',
                        'data-method' => 'post',
                    ]) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Маппинг категорий -->
            <?php if (!empty($categoryMaps)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Маппинг категорий</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Категория источника</th>
                                <th>→ Наша категория</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryMaps as $map): ?>
                            <tr>
                                <td>
                                    <?= Html::encode($map->source_category_name) ?>
                                    <?php if ($map->is_auto_mapped): ?>
                                    <span class="badge bg-secondary">авто</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($map->category): ?>
                                    <?= Html::encode($map->category->name) ?>
                                    <?php else: ?>
                                    <span class="text-muted">Не сопоставлена</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Действия -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::submitButton('<i class="fas fa-save"></i> Сохранить', [
                            'class' => 'btn btn-primary btn-lg'
                        ]) ?>
                        
                        <?php if (!$model->isNewRecord): ?>
                        <?= Html::a('<i class="fas fa-trash"></i> Удалить', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-outline-danger',
                            'data-method' => 'post',
                            'data-confirm' => 'Удалить источник?',
                        ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

