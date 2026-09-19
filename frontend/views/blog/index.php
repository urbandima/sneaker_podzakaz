<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $articles array */

$this->params['breadcrumbs'][] = 'Блог';
?>

<div class="blog-page container" style="max-width:1200px;margin:0 auto;padding:24px 16px;">

    <h1 style="font-size:2rem;font-weight:700;margin-bottom:8px;">Блог СНИКЕРХЭД</h1>
    <p style="color:#666;margin-bottom:32px;">Полезные статьи о выборе кроссовок, уходе за обувью и модных трендах.</p>

    <div class="blog-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;">
        <?php foreach ($articles as $article) : ?>
        <article class="blog-card" style="border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;transition:box-shadow .2s;" itemscope itemtype="https://schema.org/Article">
            <meta itemprop="datePublished" content="<?= Html::encode($article['datePublished']) ?>">
            <meta itemprop="dateModified"  content="<?= Html::encode($article['dateModified']) ?>">
            <a href="<?= Url::to(['/blog/view', 'slug' => $article['slug']]) ?>" style="display:block;text-decoration:none;color:inherit;">
                <div style="padding:24px;">
                    <h2 itemprop="headline" style="font-size:1.2rem;font-weight:600;margin:0 0 12px;line-height:1.4;">
                        <?= Html::encode($article['title']) ?>
                    </h2>
                    <p itemprop="description" style="color:#555;font-size:.95rem;margin:0 0 16px;line-height:1.5;">
                        <?= Html::encode($article['description']) ?>
                    </p>
                    <span style="color:#2563eb;font-size:.9rem;font-weight:500;">Читать статью →</span>
                </div>
            </a>
        </article>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:48px;padding:24px;background:#f8f9fa;border-radius:12px;text-align:center;">
        <p style="margin:0 0 12px;font-size:1rem;">Смотрите наш каталог оригинальных кроссовок</p>
        <a href="<?= Url::to(['/catalog/catalog/index']) ?>" style="display:inline-block;background:#1a1a1a;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:500;">
            Перейти в каталог
        </a>
    </div>

</div>
