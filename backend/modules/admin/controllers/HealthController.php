<?php
/**
 * HealthController — Health check endpoint
 *
 * B21.2: GET /admin/health → JSON {status: ok, db: ok, version: "..."}
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;

class HealthController extends BaseAdminController
{
    public $enableCsrfValidation = false;

    /**
     * Health check endpoint
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $checks = [
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => time(),
            'db' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'mail' => $this->checkMail(),
        ];

        $allOk = !in_array(false, $checks, true);

        Yii::$app->response->statusCode = $allOk ? 200 : 503;

        return $checks;
    }

    /**
     * Проверка подключения к БД
     */
    private function checkDatabase()
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Проверка кэша
     */
    private function checkCache()
    {
        try {
            $key = 'health_check_' . time();
            Yii::$app->cache->set($key, 'test', 10);
            $value = Yii::$app->cache->get($key);
            return $value === 'test';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Проверка почты
     */
    private function checkMail()
    {
        return !empty(Yii::$app->params['adminEmail']);
    }
}
