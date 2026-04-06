<?php

namespace app\backend\shared\traits;

use Yii;
use yii\web\Response;

/**
 * Трейт для единообразных JSON-ответов в AJAX-контроллерах.
 * Устраняет повторение Yii::$app->response->format = FORMAT_JSON во всех экшенах.
 */
trait AjaxResponseTrait
{
    protected function jsonSuccess(array $data = []): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return array_merge(['success' => true], $data);
    }

    protected function jsonError(string $message, int $code = 400): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = $code;
        return ['success' => false, 'message' => $message];
    }
}
