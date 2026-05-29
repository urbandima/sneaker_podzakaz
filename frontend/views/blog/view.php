<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $article array */

$baseUrl = Yii::$app->request->hostInfo;
$articleUrl = Yii::$app->request->absoluteUrl;
$imageUrl = !empty($article['image']) ? $baseUrl . $article['image'] : $baseUrl . '/img/logo.png';

// Schema.org Article JSON-LD
$jsonLd = [
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => $article['title'],
    'description'   => $article['description'],
    'image'         => $imageUrl,
    'datePublished' => $article['datePublished'],
    'dateModified'  => $article['dateModified'],
    'author'        => ['@type' => 'Organization', 'name' => 'СНИКЕРХЭД'],
    'publisher'     => [
        '@type' => 'Organization',
        'name'  => 'СНИКЕРХЭД',
        'logo'  => ['@type' => 'ImageObject', 'url' => $baseUrl . '/img/logo.png'],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $articleUrl],
];

$breadcrumbJsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',  'item' => $baseUrl . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Блог',     'item' => $baseUrl . '/blog'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'], 'item' => $articleUrl],
    ],
];

$this->registerJs('', \yii\web\View::POS_HEAD);
$this->registerMetaTag(['property' => 'og:image', 'content' => $imageUrl]);
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbJsonLd, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></script>

<div class="blog-article container" style="max-width:800px;margin:0 auto;padding:24px 16px;" itemscope itemtype="https://schema.org/Article">
    <meta itemprop="datePublished" content="<?= Html::encode($article['datePublished']) ?>">
    <meta itemprop="dateModified"  content="<?= Html::encode($article['dateModified']) ?>">
    <meta itemprop="image"         content="<?= Html::encode($imageUrl) ?>">

    <!-- Breadcrumb -->
    <nav style="font-size:.85rem;color:#888;margin-bottom:24px;" aria-label="Хлебные крошки">
        <a href="/" style="color:#888;text-decoration:none;">Главная</a>
        <span style="margin:0 8px;">/</span>
        <a href="/blog" style="color:#888;text-decoration:none;">Блог</a>
        <span style="margin:0 8px;">/</span>
        <span style="color:#333;"><?= Html::encode($article['title']) ?></span>
    </nav>

    <h1 itemprop="headline" style="font-size:2rem;font-weight:700;margin:0 0 24px;line-height:1.3;">
        <?= Html::encode($article['title']) ?>
    </h1>

    <div itemprop="articleBody" class="article-content" style="line-height:1.7;font-size:1rem;color:#333;">
        <?php require __DIR__ . '/articles/' . $article['slug'] . '.php'; ?>
    </div>

    <!-- Internal link to catalog -->
    <div style="margin-top:40px;padding:24px;background:#f8f9fa;border-radius:12px;text-align:center;">
        <p style="margin:0 0 12px;font-size:1rem;font-weight:500;">Смотрите оригинальные кроссовки в нашем каталоге</p>
        <a href="<?= Url::to(['/catalog/catalog/index']) ?>" style="display:inline-block;background:#1a1a1a;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:500;">
            Перейти в каталог
        </a>
    </div>

    <!-- Back to blog -->
    <div style="margin-top:24px;">
        <a href="/blog" style="color:#2563eb;text-decoration:none;font-size:.9rem;">← Все статьи блога</a>
    </div>
</div>
