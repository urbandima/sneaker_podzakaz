<?php

namespace app\frontend\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use Yii;

class BlogController extends Controller
{
    public $layout = 'main';

    private function getArticles(): array
    {
        return [
            'kak-otlichit-originalnye-krossovki-ot-poddelki' => [
                'slug'        => 'kak-otlichit-originalnye-krossovki-ot-poddelki',
                'title'       => 'Как отличить оригинальные кроссовки от подделки',
                'metaTitle'   => 'Как отличить оригинал кроссовок от подделки — 10 способов | СНИКЕРХЭД',
                'metaDesc'    => 'Подробное руководство: как отличить оригинал кроссовок от подделки по коробке, ярлыкам, швам, подошве и QR-коду. Покупайте только у проверенных продавцов.',
                'description' => 'Узнайте, как отличить оригинал кроссовок от подделки по 10 ключевым признакам.',
                'datePublished' => '2026-05-01',
                'dateModified'  => '2026-05-29',
                'image'       => '/img/blog/originalnye-krossovki.jpg',
                'keywords'    => ['оригинал кроссовки', 'отличить оригинал', 'кроссовки подделка'],
            ],
            'razmernye-tablicy-nike-adidas-new-balance' => [
                'slug'        => 'razmernye-tablicy-nike-adidas-new-balance',
                'title'       => 'Размерные таблицы Nike, Adidas, New Balance',
                'metaTitle'   => 'Таблицы размеров кроссовок Nike, Adidas, New Balance — как выбрать размер | СНИКЕРХЭД',
                'metaDesc'    => 'Таблицы размеров кроссовок Nike, Adidas и New Balance для мужчин, женщин и детей. Как измерить стопу и выбрать правильный размер обуви.',
                'description' => 'Полные таблицы размеров кроссовок для выбора правильного размера обуви.',
                'datePublished' => '2026-05-10',
                'dateModified'  => '2026-05-29',
                'image'       => '/img/blog/razmernye-tablicy.jpg',
                'keywords'    => ['размеры кроссовок', 'таблица размеров', 'размер Nike', 'размер Adidas'],
            ],
            'top-10-krossovok-2026' => [
                'slug'        => 'top-10-krossovok-2026',
                'title'       => 'Топ-10 кроссовок 2026 года',
                'metaTitle'   => 'Топ-10 самых модных кроссовок 2026 года — обзор | СНИКЕРХЭД',
                'metaDesc'    => 'Лучшие модные кроссовки 2026 года: топ-10 моделей от Nike, Adidas, New Balance и других брендов. Тренды, стиль, где купить в Беларуси.',
                'description' => 'Обзор самых модных кроссовок 2026 года — топ-10 моделей сезона.',
                'datePublished' => '2026-05-20',
                'dateModified'  => '2026-05-29',
                'image'       => '/img/blog/top-krossovki-2026.jpg',
                'keywords'    => ['модные кроссовки 2026', 'топ кроссовки 2026', 'лучшие кроссовки'],
            ],
        ];
    }

    public function actionIndex()
    {
        $articles = $this->getArticles();

        $this->view->title = 'Блог о кроссовках — советы, обзоры, размерные таблицы | СНИКЕРХЭД';
        $this->view->registerMetaTag(['name' => 'description', 'content' => 'Блог СНИКЕРХЭД: как отличить оригинал от подделки, размерные таблицы Nike и Adidas, топ модных кроссовок 2026. Полезные статьи о выборе обуви.']);
        $this->view->registerMetaTag(['property' => 'og:title',       'content' => 'Блог о кроссовках — СНИКЕРХЭД']);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => 'Полезные статьи о кроссовках: оригинальность, размеры, тренды 2026']);
        $this->view->registerMetaTag(['property' => 'og:type',        'content' => 'website']);
        $this->view->registerMetaTag(['property' => 'og:url',         'content' => Yii::$app->request->absoluteUrl]);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);

        return $this->render('index', ['articles' => $articles]);
    }

    public function actionView(string $slug)
    {
        $articles = $this->getArticles();
        if (!isset($articles[$slug])) {
            throw new NotFoundHttpException('Статья не найдена.');
        }
        $article = $articles[$slug];

        $this->view->title = $article['metaTitle'];
        $this->view->registerMetaTag(['name' => 'description', 'content' => $article['metaDesc']]);
        $this->view->registerMetaTag(['name' => 'keywords',    'content' => implode(', ', $article['keywords'])]);
        $this->view->registerMetaTag(['property' => 'og:title',       'content' => $article['title']]);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => $article['description']]);
        $this->view->registerMetaTag(['property' => 'og:type',        'content' => 'article']);
        $this->view->registerMetaTag(['property' => 'og:url',         'content' => Yii::$app->request->absoluteUrl]);
        $this->view->registerMetaTag(['property' => 'article:published_time', 'content' => $article['datePublished']]);
        $this->view->registerMetaTag(['property' => 'article:modified_time',  'content' => $article['dateModified']]);
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);

        return $this->render('view', ['article' => $article]);
    }
}
