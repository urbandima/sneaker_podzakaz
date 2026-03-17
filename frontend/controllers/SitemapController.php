<?php

namespace app\frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\services\Sitemap\SitemapGenerator;
use app\backend\shared\components\SitemapNotifier;
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
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        
        $generator = new SitemapGenerator();
        $xml = $generator->generateDynamic();
        
        return $xml;
    }
}
