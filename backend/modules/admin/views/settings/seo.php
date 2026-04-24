<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'SEO настройки';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> К настройкам', ['/admin/settings'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<?php if (!empty($seo_flash)): ?>
<div class="admin-alert admin-alert-success" style="display:flex;align-items:center;gap:10px;padding:12px 16px;margin-bottom:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:8px;color:#065f46">
    <i class="bi bi-check-circle-fill"></i>
    <?= Html::encode($seo_flash) ?>
</div>
<?php endif; ?>

<?= Html::beginForm(['/admin/settings/seo'], 'post', ['id' => 'seo-form']) ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:1.5rem">

    <!-- Мета-теги -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-tags"></i>
                Мета-теги
            </h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Шаблон тега &lt;title&gt;</label>
                <?= Html::textInput('meta_title_tpl', $meta_title_tpl, [
                    'class'       => 'form-control',
                    'placeholder' => '{page_title} — {site_name}',
                ]) ?>
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Переменные: <code>{page_title}</code>, <code>{site_name}</code>, <code>{category}</code>
                </small>
            </div>

            <div class="form-group">
                <label>Шаблон meta description</label>
                <?= Html::textarea('meta_desc_tpl', $meta_desc_tpl, [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => 'Описание страницы для поисковых систем (до 160 символов)',
                ]) ?>
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Переменные: <code>{page_title}</code>, <code>{site_name}</code>, <code>{description}</code>
                </small>
            </div>
        </div>
    </div>

    <!-- Аналитика -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-graph-up-arrow"></i>
                Системы аналитики
            </h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Google Analytics ID</label>
                <?= Html::textInput('ga_id', $ga_id, [
                    'class'       => 'form-control',
                    'placeholder' => 'G-XXXXXXXXXX или UA-XXXXXXXX-X',
                ]) ?>
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Идентификатор из Google Analytics 4 или Universal Analytics
                </small>
            </div>

            <div class="form-group">
                <label>Яндекс Метрика ID</label>
                <?= Html::textInput('metrika_id', $metrika_id, [
                    'class'       => 'form-control',
                    'placeholder' => '12345678',
                    'type'        => 'text',
                    'pattern'     => '\d*',
                ]) ?>
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Числовой идентификатор счётчика Яндекс Метрики
                </small>
            </div>
        </div>
    </div>

    <!-- Sitemap -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-diagram-2"></i>
                Карта сайта (Sitemap)
            </h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Автогенерация sitemap.xml</label>
                <?= Html::dropDownList('sitemap_enabled', $sitemap_enabled, [
                    '1' => 'Включена — sitemap.xml генерируется автоматически',
                    '0' => 'Выключена',
                ], ['class' => 'form-control']) ?>
            </div>
            <div style="margin-top:0.75rem;padding:10px 12px;background:var(--admin-surface-2,#f8f9fa);border-radius:8px;font-size:13px;color:var(--admin-text-secondary)">
                <i class="bi bi-info-circle"></i>
                Файл доступен по адресу: <a href="/sitemap.xml" target="_blank" style="color:var(--admin-primary)">/sitemap.xml</a>
            </div>
        </div>
    </div>

    <!-- robots.txt -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-robot"></i>
                robots.txt
            </h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <?= Html::textarea('robots_txt', $robots_txt, [
                    'class'  => 'form-control',
                    'rows'   => 10,
                    'style'  => 'font-family:monospace;font-size:13px;line-height:1.6;resize:vertical',
                    'placeholder' => "User-agent: *\nAllow: /\nDisallow: /admin/\n\nSitemap: https://example.com/sitemap.xml",
                ]) ?>
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Содержимое файла <code>/robots.txt</code>. Будет автоматически отдаваться поисковым роботам.
                </small>
            </div>
            <div style="margin-top:0.75rem;padding:10px 12px;background:var(--admin-surface-2,#f8f9fa);border-radius:8px;font-size:13px;color:var(--admin-text-secondary)">
                <i class="bi bi-info-circle"></i>
                Файл доступен по адресу: <a href="/robots.txt" target="_blank" style="color:var(--admin-primary)">/robots.txt</a>
            </div>
        </div>
    </div>

</div>

<div style="margin-top:1.5rem;display:flex;gap:0.75rem">
    <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить SEO настройки', ['class' => 'admin-btn admin-btn-primary']) ?>
    <a href="<?= Url::to(['/admin/settings']) ?>" class="admin-btn admin-btn-secondary">Отмена</a>
</div>

<?= Html::endForm() ?>
