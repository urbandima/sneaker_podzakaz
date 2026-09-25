<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Публичный health-check для внешнего аптайм-мониторинга.
 * Не требует логина в админку (мониторы не могут пройти /admin/login),
 * но закрыт shared-secret токеном, чтобы не отдавать статус БД и версию анонимно всем.
 */
class HealthController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $expected = env('HEALTH_CHECK_TOKEN');
        if (empty($expected)) {
            // Токен не настроен — endpoint полностью закрыт, а не "открыт по умолчанию".
            Yii::$app->response->statusCode = 503;
            Yii::$app->end(0, Yii::$app->response);
            return false;
        }

        $provided = Yii::$app->request->headers->get('X-Health-Token')
            ?? Yii::$app->request->get('token');

        if (!is_string($provided) || !hash_equals($expected, $provided)) {
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = ['status' => 'unauthorized'];
            Yii::$app->end(0, Yii::$app->response);
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $db = 'ok';
        try {
            Yii::$app->db->createCommand('SELECT 1')->execute();
        } catch (\Exception $e) {
            $db = 'error';
        }
        return [
            'status'    => 'ok',
            'db'        => $db,
            'version'   => '1.0.0',
            'timestamp' => date('c'),
        ];
    }
}
