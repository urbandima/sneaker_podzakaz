<?php

/**
 * BaseAdminController — Базовый контроллер админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Абстрактный базовый класс для всех контроллеров админ-панели.
 * Содержит общую логику доступа, хелперы и утилиты.
 * 
 * ФУНКЦИИ:
 * - AccessControl: только авторизованные пользователи
 * - VerbFilter: ограничения HTTP методов (DELETE только через POST)
 * - Проверка прав доступа (adminOnly, isAdmin, isManager, isLogist)
 * - Хелперы для flash-сообщений (success, error, warning)
 * - Получение текущего пользователя (getCurrentUser)
 * 
 * НАСЛЕДНИКИ:
 * - DashboardController
 * - OrderController
 * - ProductController
 * - CustomerController
 * - CharacteristicController
 * - PoizonController
 * - ReviewController
 * - UserController
 * - TariffController
 * - SizeGridController
 * - SearchController
 * - StatisticsController
 * - DevToolsController
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * Установить protected $adminOnly = true; в наследнике для ограничения только админам
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

abstract class BaseAdminController extends Controller
{
    protected bool $adminOnly = false;

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
                        'matchCallback' => function ($rule, $action) {
                            // ИСПРАВЛЕНО: Явная проверка демо-режима через конфиг
                            $isDemoMode = defined('YII_ENV') && YII_ENV === 'demo' 
                                || (Yii::$app->params['demoMode'] ?? false);
                            
                            // В демо-режиме разрешаем доступ всем
                            if ($isDemoMode) {
                                return true;
                            }
                            
                            // В проде: проверяем права
                            try {
                                return !$this->adminOnly || $this->isAdmin();
                            } catch (\Exception $e) {
                                // При ошибке - доступ запрещён (безопасно по умолчанию)
                                Yii::error('Access control error: ' . $e->getMessage(), 'security');
                                return false;
                            }
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'], // Разрешаем гостям вход
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
     * ИСПРАВЛЕНО: При ошибке возвращаем false вместо true
     * 
     * @return bool
     */
    protected function isAdmin()
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin();
        } catch (\Exception $e) {
            // ИСПРАВЛЕНО: При ошибке - не админ (безопасно по умолчанию)
            return false;
        }
    }

    /**
     * Проверить, является ли текущий пользователь логистом
     * 
     * @return bool
     */
    protected function isLogist()
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isLogist();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Проверить, является ли текущий пользователь менеджером
     * 
     * @return bool
     */
    protected function isManager()
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isManager();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить текущего пользователя
     * 
     * @return \app\backend\modules\admin\models\User|null
     */
    protected function getCurrentUser()
    {
        try {
            return Yii::$app->user->identity;
        } catch (\Exception $e) {
            // Демо-пользователь
            return new \app\backend\modules\admin\models\User([
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'role' => \app\backend\modules\admin\models\User::ROLE_ADMIN,
                'status' => \app\backend\modules\admin\models\User::STATUS_ACTIVE,
            ]);
        }
    }
}
