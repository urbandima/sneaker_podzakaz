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

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <div>
        <!-- Основные настройки -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Основные настройки</h2>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'class' => 'admin-form-input']) ?>
                    <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'readonly' => !$model->isNewRecord, 'class' => 'admin-form-input']) ?>
                </div>
                
                <?= $form->field($model, 'base_url')->textInput(['maxlength' => true, 'class' => 'admin-form-input']) ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <?= $form->field($model, 'currency_code')->dropDownList([
                        'BYN' => 'BYN - Белорусский рубль',
                        'USD' => 'USD - Доллар США',
                        'EUR' => 'EUR - Евро',
                        'CNY' => 'CNY - Китайский юань',
                        'RUB' => 'RUB - Российский рубль',
                    ], ['class' => 'admin-form-input']) ?>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                            <?= $form->field($model, 'is_active')->checkbox() ?>
                            <span>Активен</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Настройки парсинга -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Настройки парсинга</h2>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <?= $form->field($model, 'parse_delay_min')->textInput(['type' => 'number', 'min' => 1, 'max' => 60, 'class' => 'admin-form-input']) ?>
                    <?= $form->field($model, 'parse_delay_max')->textInput(['type' => 'number', 'min' => 1, 'max' => 60, 'class' => 'admin-form-input']) ?>
                </div>
                
                <p style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                    <i class="bi bi-info-circle"></i> 
                    Задержка между запросами к сайту (секунды). Увеличьте если сайт блокирует частые запросы.
                </p>
            </div>
        </div>

        <!-- Прокси -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Прокси</h2>
                <?php if ($model->proxy_enabled): ?>
                    <span class="admin-badge admin-badge-success">Включено</span>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <?= $form->field($model, 'proxy_enabled')->checkbox() ?>
                
                <div id="proxy-settings" style="<?= $model->proxy_enabled ? '' : 'display: none;' ?>">
                    <?= $form->field($model, 'proxy_rotation')->dropDownList([
                        ImportSource::PROXY_ROTATION_RANDOM => 'Случайная ротация',
                        ImportSource::PROXY_ROTATION_SEQUENTIAL => 'Последовательная ротация',
                    ], ['class' => 'admin-form-input']) ?>
                    
                    <div class="form-group">
                        <label style="color: var(--admin-text-secondary); font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Список прокси (один на строку)</label>
                        <textarea name="proxy_list_text" class="admin-form-input" rows="6" 
                                  placeholder="http://user:pass@proxy:port&#10;socks5://user:pass@proxy:port"><?= 
                            implode("\n", $model->getProxyListArray())
                        ?></textarea>
                        <p style="color: var(--admin-text-secondary); font-size: 0.875rem; margin-top: 0.25rem;">
                            Формат: <code>http://user:pass@proxy:port</code> или <code>socks5://proxy:port</code>
                        </p>
                    </div>
                    
                    <?php if (!$model->isNewRecord && !empty($model->getProxyListArray())): ?>
                    <?= Html::a('<i class="bi bi-check-circle"></i> Проверить прокси', ['check-proxies', 'sourceId' => $model->id], [
                        'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
                    ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CAPTCHA -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">CAPTCHA сервисы</h2>
            </div>
            <div class="admin-card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <?= $form->field($model, 'captcha_service')->dropDownList([
                        ImportSource::CAPTCHA_2CAPTCHA => '2Captcha',
                        ImportSource::CAPTCHA_ANTICAPTCHA => 'Anti-Captcha',
                        ImportSource::CAPTCHA_CAPMONSTER => 'CapMonster',
                    ], ['class' => 'admin-form-input']) ?>
                    <?= $form->field($model, 'captcha_api_key')->passwordInput(['maxlength' => true, 'class' => 'admin-form-input']) ?>
                </div>
                
                <hr style="border-color: var(--admin-border); margin: 1.5rem 0;">
                
                <h3 style="font-size: 1rem; margin-bottom: 1rem;">Резервный сервис</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <?= $form->field($model, 'captcha_fallback_service')->dropDownList([
                        '' => '-- Не использовать --',
                        ImportSource::CAPTCHA_2CAPTCHA => '2Captcha',
                        ImportSource::CAPTCHA_ANTICAPTCHA => 'Anti-Captcha',
                        ImportSource::CAPTCHA_CAPMONSTER => 'CapMonster',
                    ], ['class' => 'admin-form-input']) ?>
                    <?= $form->field($model, 'captcha_fallback_api_key')->passwordInput(['maxlength' => true, 'class' => 'admin-form-input']) ?>
                </div>
                
                <p style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                    <i class="bi bi-info-circle"></i> 
                    Приоритет: основной сервис → резервный → остальные
                </p>
            </div>
        </div>
    </div>

    <!-- Боковая панель -->
    <div>
        <!-- Статистика -->
        <?php if (!$model->isNewRecord): ?>
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Статистика</h2>
            </div>
            <div class="admin-card-body">
                <table class="admin-table">
                    <tr>
                        <td style="color: var(--admin-text-secondary);">Последний запуск:</td>
                        <td><?= $model->last_run_at 
                            ? Yii::$app->formatter->asDatetime($model->last_run_at) 
                            : '<span style="color: var(--admin-text-secondary);">Никогда</span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: var(--admin-text-secondary);">Успешных запусков:</td>
                        <td style="color: var(--admin-success);"><?= $model->successful_runs ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--admin-text-secondary);">Ошибок:</td>
                        <td style="color: var(--admin-danger);"><?= $model->failed_runs ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--admin-text-secondary);">Товаров спарсено:</td>
                        <td><?= number_format($model->total_products_parsed, 0, '', ' ') ?></td>
                    </tr>
                </table>
                
                <?php if ($model->is_active): ?>
                <?= Html::a('<i class="bi bi-play"></i> Запустить импорт', ['run', 'sourceId' => $model->id], [
                    'class' => 'admin-btn admin-btn-success admin-btn-sm',
                    'style' => 'width: 100%; margin-top: 1rem;',
                    'data-method' => 'post',
                ]) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Маппинг категорий -->
        <?php if (!empty($categoryMaps)): ?>
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-header">
                <h2 class="admin-card-title">Маппинг категорий</h2>
            </div>
            <div class="admin-card-body" style="padding: 0;">
                <table class="admin-table">
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
                                <span class="admin-badge admin-badge-secondary">авто</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($map->category): ?>
                                <?= Html::encode($map->category->name) ?>
                                <?php else: ?>
                                <span style="color: var(--admin-text-secondary);">Не сопоставлена</span>
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
        <div class="admin-card">
            <div class="admin-card-body">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить', [
                        'class' => 'admin-btn admin-btn-primary admin-btn-lg'
                    ]) ?>
                    
                    <?php if (!$model->isNewRecord): ?>
                    <?= Html::a('<i class="bi bi-trash"></i> Удалить', ['delete', 'id' => $model->id], [
                        'class' => 'admin-btn admin-btn-danger admin-btn-sm',
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

