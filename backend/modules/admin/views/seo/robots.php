<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Robots.txt';
?>

<div class="seo-robots">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-success mb-3">
        <h5>🎯 Быстрая победа — проверка robots.txt</h5>
        <p class="mb-2">Robots.txt управляет доступом поисковых роботов к сайту.</p>
        <p class="mb-0"><strong>Обязательно проверьте:</strong><br>
        ✓ <code>Disallow: /admin/</code> — админка закрыта от индексации<br>
        ✓ <code>Allow: /</code> — сайт открыт для индексации<br>
        ✓ <code>Sitemap: /sitemap.xml</code> — указан путь к sitemap</p>
    </div>
    
    <?php $form = ActiveForm::begin(); ?>
    
    <div class="form-group">
        <label for="robots_content">Содержимое robots.txt</label>
        <?= Html::textarea('robots_content', $content, [
            'class' => 'form-control',
            'rows' => 15,
            'style' => 'font-family: monospace;',
        ]) ?>
    </div>
    
    <div class="form-group mt-3">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Открыть robots.txt', '/robots.txt', ['class' => 'btn btn-outline-secondary ms-2', 'target' => '_blank']) ?>
    </div>
    
    <?php ActiveForm::end(); ?>
    
    <div class="alert alert-info mt-3">
        <strong>Справка:</strong><br>
        <code>User-agent: *</code> — правила для всех роботов<br>
        <code>Disallow: /admin/</code> — запретить индексацию<br>
        <code>Allow: /</code> — разрешить индексацию<br>
        <code>Sitemap: /sitemap.xml</code> — путь к карте сайта
    </div>
</div>
