<?php

namespace app\controllers\admin;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Базовый контроллер для админ-панели
 * 
 * Содержит общую логику для всех админ-контроллеров:
 * - AccessControl (только для авторизованных пользователей)
 * - VerbFilter (ограничения HTTP методов)
 * - Хелперы для flash-сообщений
 * - Проверки прав доступа
 */
abstract class BaseAdminController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Только авторизованные
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-*' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Установить flash-сообщение об успехе
     * 
     * @param string $message
     */
    protected function flashSuccess($message)
    {
        Yii::$app->session->setFlash('success', $message);
    }

    /**
     * Установить flash-сообщение об ошибке
     * 
     * @param string $message
     */
    protected function flashError($message)
    {
        Yii::$app->session->setFlash('error', $message);
    }

    /**
     * Установить flash-сообщение с предупреждением
     * 
     * @param string $message
     */
    protected function flashWarning($message)
    {
        Yii::$app->session->setFlash('warning', $message);
    }

    /**
     * Установить flash-сообщение с информацией
     * 
     * @param string $message
     */
    protected function flashInfo($message)
    {
        Yii::$app->session->setFlash('info', $message);
    }

    /**
     * Проверить, является ли текущий пользователь администратором
     * 
     * @return bool
     */
    protected function isAdmin()
    {
        return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin();
    }

    /**
     * Проверить, является ли текущий пользователь логистом
     * 
     * @return bool
     */
    protected function isLogist()
    {
        return !Yii::$app->user->isGuest && Yii::$app->user->identity->isLogist();
    }

    /**
     * Проверить, является ли текущий пользователь менеджером
     * 
     * @return bool
     */
    protected function isManager()
    {
        return !Yii::$app->user->isGuest && Yii::$app->user->identity->isManager();
    }

    /**
     * Получить текущего пользователя
     * 
     * @return \app\models\User|null
     */
    protected function getCurrentUser()
    {
        return Yii::$app->user->identity;
    }
}
