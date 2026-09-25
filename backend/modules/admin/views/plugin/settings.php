<?php

/**
 * Generic settings page for dynamic plugins registered in PluginManager
 * (StripePaymentPlugin/YooKassaPaymentPlugin/BelpostShippingPlugin/LiveDunePlugin).
 *
 * CMP-470-B: this view was missing entirely — PluginController::actionSettings()
 * called $this->render('settings', ...) but the file never existed, so every
 * visit 500'd (yii\base\ViewNotFoundException), confirmed live for
 * /admin/plugin/settings?id=stripe once the plugin is activated (the "Настройки"
 * button on /admin/plugin only appears for active payment plugins, so this was
 * reachable from a real button in the live UI, not just a raw URL).
 *
 * NB (documented, not fixed here — see CMP-470-B report "Найдено, не
 * исправлено"): saving settings here only writes to the
 * plugin_settings_{id} cache key read by BasePlugin::loadSettings(). None of
 * PluginManager's payment/shipping providers are wired into the real
 * checkout (nothing outside PluginController calls getActivePaymentGateways()/
 * getActiveShippingProviders()), and LiveDunePlugin's real API token is read
 * from a completely different place (Yii::$app->settings->get('integrations',
 * 'livedune_api_token')). This page is functional but currently cosmetic.
 *
 * @var yii\web\View $this
 * @var \app\infrastructure\plugins\interfaces\PluginInterface $plugin
 * @var array $settings
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $plugin->getName() . ' — настройки';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($plugin->isActive() ? 'admin-badge-success' : 'admin-badge-secondary') . '">'
        . ($plugin->isActive() ? 'Активен' : 'Неактивен') . '</span>',
];
?>

<div class="admin-card" style="max-width:640px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-gear"></i> <?= Html::encode($plugin->getName()) ?></h2>
    </div>
    <div class="admin-card-body">
        <p style="color:var(--admin-text-secondary);margin-top:0"><?= Html::encode($plugin->getDescription()) ?></p>
        <p style="font-size:12px;color:var(--admin-text-secondary)">
            v<?= Html::encode($plugin->getVersion()) ?> · <?= Html::encode($plugin->getAuthor()) ?>
        </p>

        <?php $form = \yii\widgets\ActiveForm::begin([
            'action' => Url::to(['plugin/settings', 'id' => $plugin->getId()]),
            'method' => 'post',
        ]); ?>

        <?php if (empty($settings)) : ?>
            <p style="color:var(--admin-text-secondary)">У этого плагина пока нет сохранённых настроек.</p>
        <?php endif; ?>

        <?php foreach ($settings as $key => $value) : ?>
            <?php if ($key === 'is_active') {
                continue;
            } // controlled by the activate/deactivate toggle on the index page ?>
            <div class="admin-form-group">
                <label class="admin-form-label"><?= Html::encode($key) ?></label>
                <input type="text" class="admin-form-input"
                       name="settings[<?= Html::encode($key) ?>]"
                       value="<?= Html::encode(is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE)) ?>">
            </div>
        <?php endforeach; ?>

        <div class="admin-form-group">
            <label class="admin-form-label">Добавить/изменить параметр</label>
            <div style="display:flex;gap:8px">
                <input type="text" class="admin-form-input" name="new_setting_key" placeholder="ключ, например api_key" style="flex:1">
                <input type="text" class="admin-form-input" name="new_setting_value" placeholder="значение" style="flex:1">
            </div>
            <small style="color:var(--admin-text-secondary);font-size:12px">
                Заполните оба поля, чтобы сохранить новый параметр вместе с остальными.
            </small>
        </div>

        <div class="admin-form-actions" style="margin-top:16px">
            <button type="submit" class="admin-btn admin-btn-primary"><i class="bi bi-save"></i> Сохранить</button>
        </div>

        <?php \yii\widgets\ActiveForm::end(); ?>
    </div>
</div>

<script>
// Merge the free-form key/value row into settings[...] before submit, so the
// controller's Yii::$app->request->post('settings', []) sees it as one array.
document.querySelector('.admin-form-actions button[type="submit"]')?.closest('form')?.addEventListener('submit', function (e) {
    const keyInput = this.querySelector('input[name="new_setting_key"]');
    const valInput = this.querySelector('input[name="new_setting_value"]');
    const key = keyInput ? keyInput.value.trim() : '';
    if (key) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'settings[' + key + ']';
        hidden.value = valInput ? valInput.value : '';
        this.appendChild(hidden);
    }
    if (keyInput) keyInput.disabled = true;
    if (valInput) valInput.disabled = true;
});
</script>
