<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $settings */
/** @var array $rates */
/** @var string|null $lastUpdate */
/** @var array $captchaBalances */

$this->title = 'Настройки импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => Url::to(['index'])];
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад к импорту', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div class="import-settings">
    <?php $form = ActiveForm::begin(); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div>
            <!-- Автоматический импорт -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Автоматический импорт</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label class="admin-form-label">
                                <input type="checkbox" id="auto_import_enabled" 
                                       name="settings[auto_import_enabled]" value="1"
                                       <?= $settings['auto_import_enabled'] ? 'checked' : '' ?> style="margin-right: 0.5rem;">
                                Включить автоматический импорт
                            </label>
                        </div>
                        <div>
                            <label class="admin-form-label">Интервал запуска (часы)</label>
                            <input type="number" name="settings[import_interval_hours]" 
                                   class="admin-form-input" min="1" max="24"
                                   value="<?= $settings['import_interval_hours'] ?>">
                            <p class="admin-form-hint">Рекомендуется: 8 часов</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="admin-form-label">Максимум товаров за запуск</label>
                            <input type="number" name="settings[max_products_per_run]" 
                                   class="admin-form-input" min="10" max="1000"
                                   value="<?= $settings['max_products_per_run'] ?>">
                        </div>
                        <div>
                            <label class="admin-form-label">Хранить логи (дней)</label>
                            <input type="number" name="settings[log_retention_days]" 
                                   class="admin-form-input" min="7" max="90"
                                   value="<?= $settings['log_retention_days'] ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Уведомления -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Уведомления</h3>
                </div>
                <div class="admin-card-body">
                    <label class="admin-form-label" style="margin-bottom: 1rem;">
                        <input type="checkbox" id="notify_on_complete" 
                               name="settings[notify_on_complete]" value="1"
                               <?= $settings['notify_on_complete'] ? 'checked' : '' ?> style="margin-right: 0.5rem;">
                        Уведомлять о завершении импорта
                    </label>
                    
                    <label class="admin-form-label">
                        <input type="checkbox" id="notify_on_error" 
                               name="settings[notify_on_error]" value="1"
                               <?= $settings['notify_on_error'] ? 'checked' : '' ?> style="margin-right: 0.5rem;">
                        Уведомлять об ошибках импорта
                    </label>
                </div>
            </div>
        </div>

        <div>
            <!-- Курсы валют -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Курсы валют (НБ РБ)</h3>
                    <?= Html::a('<i class="bi bi-arrow-clockwise"></i>', ['update-rates'], [
                        'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
                        'title' => 'Обновить курсы',
                    ]) ?>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($rates)): ?>
                    <table class="admin-table" style="font-size: 0.875rem;">
                        <?php foreach ($rates as $code => $rate): ?>
                        <?php if ($code !== 'BYN'): ?>
                        <tr>
                            <td><strong><?= Html::encode($code) ?></strong></td>
                            <td style="text-align: right;"><?= number_format($rate, 4) ?> BYN</td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p style="color: var(--admin-text-secondary);">Курсы не загружены</p>
                    <?php endif; ?>
                    
                    <?php if ($lastUpdate): ?>
                    <p style="color: var(--admin-text-secondary); font-size: 0.75rem; margin-top: 1rem;">
                        Обновлено: <?= Yii::$app->formatter->asRelativeTime($lastUpdate) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Баланс CAPTCHA сервисов -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">CAPTCHA сервисы</h3>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($captchaBalances)): ?>
                    <table class="admin-table" style="font-size: 0.875rem;">
                        <?php foreach ($captchaBalances as $service => $balance): ?>
                        <tr>
                            <td><strong><?= Html::encode($service) ?></strong></td>
                            <td style="text-align: right;">$<?= number_format($balance, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p style="color: var(--admin-text-secondary);">
                        API ключи не настроены.<br>
                        Настройте в карточке источника.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cron команда -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Cron задача</h3>
                </div>
                <div class="admin-card-body">
                    <p style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-bottom: 0.5rem;">Добавьте в crontab:</p>
                    <code style="display: block; padding: 0.5rem; background: var(--admin-border-subdued); border-radius: var(--admin-radius-sm); font-size: 0.75rem;">
                        0 */<?= $settings['import_interval_hours'] ?> * * * cd <?= Yii::getAlias('@app') ?> && php yii import/run
                    </code>
                    <p style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.5rem;">
                        Запуск каждые <?= $settings['import_interval_hours'] ?> часов
                    </p>
                </div>
            </div>

            <!-- Сохранить -->
            <div class="admin-card">
                <div class="admin-card-body">
                    <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить настройки', [
                        'class' => 'admin-btn admin-btn-primary w-100'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
