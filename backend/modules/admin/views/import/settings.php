<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $settings */
/** @var array $rates */
/** @var string|null $lastUpdate */
/** @var array $captchaBalances */

$this->title = 'Настройки импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-settings">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Автоматический импорт -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Автоматический импорт</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_import_enabled" 
                                       name="settings[auto_import_enabled]" value="1"
                                       <?= $settings['auto_import_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_import_enabled">
                                    Включить автоматический импорт
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Интервал запуска (часы)</label>
                            <input type="number" name="settings[import_interval_hours]" 
                                   class="form-control" min="1" max="24"
                                   value="<?= $settings['import_interval_hours'] ?>">
                            <p class="text-muted small">Рекомендуется: 8 часов</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Максимум товаров за запуск</label>
                            <input type="number" name="settings[max_products_per_run]" 
                                   class="form-control" min="10" max="1000"
                                   value="<?= $settings['max_products_per_run'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Хранить логи (дней)</label>
                            <input type="number" name="settings[log_retention_days]" 
                                   class="form-control" min="7" max="90"
                                   value="<?= $settings['log_retention_days'] ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Уведомления -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Уведомления</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_on_complete" 
                               name="settings[notify_on_complete]" value="1"
                               <?= $settings['notify_on_complete'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_complete">
                            Уведомлять о завершении импорта
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_on_error" 
                               name="settings[notify_on_error]" value="1"
                               <?= $settings['notify_on_error'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_error">
                            Уведомлять об ошибках импорта
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Курсы валют -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Курсы валют (НБ РБ)</h5>
                    <?= Html::a('<i class="fas fa-sync"></i>', ['update-rates'], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'title' => 'Обновить курсы',
                    ]) ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($rates)): ?>
                    <table class="table table-sm mb-0">
                        <?php foreach ($rates as $code => $rate): ?>
                        <?php if ($code !== 'BYN'): ?>
                        <tr>
                            <td><strong><?= Html::encode($code) ?></strong></td>
                            <td class="text-end"><?= number_format($rate, 4) ?> BYN</td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p class="text-muted mb-0">Курсы не загружены</p>
                    <?php endif; ?>
                    
                    <?php if ($lastUpdate): ?>
                    <p class="text-muted small mt-2 mb-0">
                        Обновлено: <?= Yii::$app->formatter->asRelativeTime($lastUpdate) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Баланс CAPTCHA сервисов -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">CAPTCHA сервисы</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($captchaBalances)): ?>
                    <table class="table table-sm mb-0">
                        <?php foreach ($captchaBalances as $service => $balance): ?>
                        <tr>
                            <td><strong><?= Html::encode($service) ?></strong></td>
                            <td class="text-end">$<?= number_format($balance, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p class="text-muted mb-0">
                        API ключи не настроены.<br>
                        Настройте в карточке источника.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cron команда -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Cron задача</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Добавьте в crontab:</p>
                    <code class="d-block p-2 bg-light">
                        0 */<?= $settings['import_interval_hours'] ?> * * * cd <?= Yii::getAlias('@app') ?> && php yii import/run
                    </code>
                    <p class="small text-muted mt-2 mb-0">
                        Запуск каждые <?= $settings['import_interval_hours'] ?> часов
                    </p>
                </div>
            </div>

            <!-- Сохранить -->
            <div class="card">
                <div class="card-body">
                    <?= Html::submitButton('<i class="fas fa-save"></i> Сохранить настройки', [
                        'class' => 'btn btn-primary w-100 btn-lg'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
