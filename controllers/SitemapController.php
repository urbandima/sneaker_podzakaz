<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\services\Sitemap\SitemapGenerator;
use app\components\SitemapNotifier;
use yii\web\NotFoundHttpException;

/**
 * Контроллер генерации sitemap.xml
 */
class SitemapController extends Controller
{
    /**
     * Генерация sitemap.xml
     */
    public function actionIndex()
    {
        $filePath = Yii::getAlias('@webroot/sitemap.xml');

        if (!file_exists($filePath)) {
            $this->generateSitemap();
        }

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Sitemap не найден.');
        }

        return Yii::$app->response->sendFile($filePath, null, [
            'mimeType' => 'application/xml',
            'inline' => true,
        ]);
    }

    private function generateSitemap(): void
    {
        $generator = new SitemapGenerator();
        $generator->generate();
        SitemapNotifier::reset();
        Yii::info('Sitemap regenerated via web controller fallback', __METHOD__);
    }
}
