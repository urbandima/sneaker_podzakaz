<?php

use yii\helpers\Html;

$this->title = 'Sitemap.xml';
?>

<div class="seo-sitemap">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-success mb-3">
        <h5>🎯 Быстрая победа — генерация sitemap</h5>
        <p class="mb-2">Sitemap.xml помогает поисковикам (Google, Яндекс) быстрее найти все страницы вашего сайта.</p>
        <p class="mb-0"><strong>Шаг 1:</strong> Нажмите "Сгенерировать"<br>
        <strong>Шаг 2:</strong> Проверьте что файл открывается по ссылке /sitemap.xml<br>
        <strong>Шаг 3:</strong> Добавьте сайт в <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></p>
    </div>
    
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">Статус sitemap</h5>
            <p class="card-text">
                <?php if ($exists): ?>
                    <span class="badge bg-success">Существует</span><br>
                    Последнее обновление: <?= $lastModified ?>
                <?php else: ?>
                    <span class="badge bg-warning">Не создан</span>
                <?php endif; ?>
            </p>
            
            <?= Html::beginForm(['sitemap'], 'post') ?>
                <?= Html::hiddenInput('regenerate', 1) ?>
                <?= Html::submitButton('Сгенерировать sitemap.xml', ['class' => 'btn btn-primary']) ?>
            <?= Html::endForm() ?>
            
            <?php if ($exists): ?>
                <?= Html::a('Открыть sitemap.xml', '/sitemap.xml', ['class' => 'btn btn-outline-secondary ms-2', 'target' => '_blank']) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <strong>Информация:</strong><br>
        Sitemap.xml автоматически включает:<br>
        • Все активные товары<br>
        • Все активные категории<br>
        • Все активные бренды<br>
        • Статические страницы<br>
        <br>
        Обновляйте sitemap при добавлении новых товаров или изменении структуры.
    </div>
</div>
