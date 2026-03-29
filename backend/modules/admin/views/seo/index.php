<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'SEO Инструменты';
?>

<div class="seo-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="row mt-4">
        <!-- Редиректы -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Редиректы</h5>
                    <p class="card-text">
                        Активных: <strong><?= $stats['activeRedirects'] ?></strong><br>
                        Всего: <?= $stats['redirects'] ?>
                    </p>
                    <?= Html::a('Управление', ['redirects'], ['class' => 'btn btn-primary btn-sm']) ?>
                </div>
            </div>
        </div>
        
        <!-- Sitemap -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sitemap.xml</h5>
                    <p class="card-text">
                        Автогенерация карты сайта для поисковых систем
                    </p>
                    <?= Html::a('Генерация', ['sitemap'], ['class' => 'btn btn-primary btn-sm']) ?>
                </div>
            </div>
        </div>
        
        <!-- Robots.txt -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Robots.txt</h5>
                    <p class="card-text">
                        Управление инструкциями для поисковых роботов
                    </p>
                    <?= Html::a('Редактировать', ['robots'], ['class' => 'btn btn-primary btn-sm']) ?>
                </div>
            </div>
        </div>
        
        <!-- Meta теги -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Meta-теги</h5>
                    <p class="card-text">
                        Товаров без meta: <strong><?= $stats['productsWithoutMeta'] ?></strong><br>
                        Категорий без meta: <?= $stats['categoriesWithoutMeta'] ?>
                    </p>
                    <?= Html::a('Массовое редактирование', ['bulk-meta'], ['class' => 'btn btn-primary btn-sm']) ?>
                </div>
            </div>
        </div>
        
        <!-- ALT тексты -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📷 ALT тексты</h5>
                    <p class="card-text">
                        Изображений без ALT: <strong><?= $stats['imagesWithoutAlt'] ?? '?' ?></strong><br>
                        <small class="text-muted">Улучшает SEO и доступность</small>
                    </p>
                    <?= Html::a('Управление', ['alt-texts'], ['class' => 'btn btn-primary btn-sm']) ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <div class="alert alert-info">
            <h5>🎯 Быстрые победы для SEO 100/100:</h5>
            <ol>
                <li><strong>Meta-теги:</strong> Заполните title и description для всех товаров</li>
                <li><strong>Редиректы:</strong> Исправьте 404 ошибки через 301 редиректы</li>
                <li><strong>Sitemap:</strong> Сгенерируйте и проверьте sitemap.xml</li>
                <li><strong>Robots.txt:</strong> Убедитесь что админка закрыта, сайт открыт</li>
                <li><strong>ALT тексты:</strong> Добавьте описания ко всем изображениям</li>
            </ol>
            <p class="mb-0 mt-2"><strong>Подробный чек-лист:</strong> <?= Html::a('SEO_100_100_CHECKLIST.md', ['/docs/SEO_100_100_CHECKLIST.md']) ?></p>
        </div>
    </div>
</div>
