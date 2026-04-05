<?php
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;

class HealthController extends Controller
{
    public function beforeAction($action)
    {
        return true; // skip auth
    }

    public function actionIndex()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
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
