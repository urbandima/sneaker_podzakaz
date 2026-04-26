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
use app\backend\modules\admin\services\AdminLogService;

abstract class BaseAdminController extends Controller
{
    protected bool $adminOnly    = false;
    protected bool $financeOnly  = false;
    protected bool $procureOnly  = false;
    public $layout = 'admin'; // Admin layout с сайдбаром
    public $layoutPath = '@backend/modules/admin/views/layouts';

    // Свойства для логирования
    protected ?array $logOldValues = null;
    protected ?array $logNewValues = null;
    protected string $logEntityType = '';
    protected ?int $logEntityId = null;
    protected string $logEntityName = '';
    protected string $logAction = '';

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
                                if ($this->adminOnly && !$this->isAdmin()) return false;
                                if ($this->financeOnly && !$this->canAccessFinance()) return false;
                                if ($this->procureOnly && !$this->canAccessProcurement()) return false;
                                return true;
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
    protected function isManager(): bool
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isManager();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isDirector(): bool
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->isDirector();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function canAccessFinance(): bool
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->canAccessFinance();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function canAccessProcurement(): bool
    {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->identity->canAccessProcurement();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * RBAC permission check.
     * Falls back to role check when:
     *   - authManager throws (tables missing)
     *   - permission not seeded in auth_item yet (RBAC not initialised)
     */
    protected function canDo(string $permission): bool
    {
        if (Yii::$app->user->isGuest) return false;
        try {
            if (Yii::$app->user->can($permission)) {
                return true;
            }
            // can() returned false — check whether the permission even exists.
            // If it doesn't exist in auth_item, RBAC is not seeded: fall back to role.
            $am = Yii::$app->authManager ?? null;
            if ($am !== null && $am->getPermission($permission) === null) {
                return $this->isAdmin() || $this->isManager();
            }
            return false;
        } catch (\Throwable $e) {
            return $this->isAdmin();
        }
    }

    /**
     * Abort with 403 if permission is denied (JSON-aware).
     */
    protected function requirePermission(string $permission): void
    {
        if ($this->canDo($permission)) return;
        if (Yii::$app->request->isAjax || Yii::$app->request->isOptions) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = ['success' => false, 'message' => 'Недостаточно прав'];
            Yii::$app->response->send();
            Yii::$app->end();
        }
        throw new \yii\web\ForbiddenHttpException('Недостаточно прав для выполнения этого действия.');
    }

    /**
     * Получить текущего пользователя
     * 
     * @return \app\backend\modules\admin\models\User|null
     */
    protected function getCurrentUser()
    {
        try {
            $user = Yii::$app->user->identity;
            if ($user === null) {
                Yii::warning('getCurrentUser: no authenticated user', 'security');
            }
            return $user;
        } catch (\Exception $e) {
            Yii::error('getCurrentUser exception: ' . $e->getMessage(), 'security');
            return null;
        }
    }

    /**
     * Логировать создание сущности
     * 
     * @param string $entityType Тип сущности (order, product, customer и т.д.)
     * @param int $entityId ID сущности
     * @param string $entityName Название/имя сущности
     * @param array|null $data Дополнительные данные
     */
    protected function logCreate(string $entityType, int $entityId, string $entityName, ?array $data = null): void
    {
        AdminLogService::logCreate($entityType, $entityId, $entityName, $data);
    }

    /**
     * Логировать обновление сущности
     * 
     * @param string $entityType Тип сущности
     * @param int $entityId ID сущности
     * @param string $entityName Название сущности
     * @param array $oldValues Старые значения
     * @param array $newValues Новые значения
     */
    protected function logUpdate(string $entityType, int $entityId, string $entityName, array $oldValues, array $newValues): void
    {
        AdminLogService::logUpdate($entityType, $entityId, $entityName, $oldValues, $newValues);
    }

    /**
     * Логировать удаление сущности
     * 
     * @param string $entityType Тип сущности
     * @param int $entityId ID сущности
     * @param string $entityName Название сущности
     * @param array|null $data Данные удаленной сущности
     */
    protected function logDelete(string $entityType, int $entityId, string $entityName, ?array $data = null): void
    {
        AdminLogService::logDelete($entityType, $entityId, $entityName, $data);
    }

    /**
     * Логировать просмотр сущности (для важных операций)
     * 
     * @param string $entityType Тип сущности
     * @param int $entityId ID сущности
     * @param string $entityName Название сущности
     */
    protected function logView(string $entityType, int $entityId, string $entityName): void
    {
        AdminLogService::logView($entityType, $entityId, $entityName);
    }

    /**
     * Логировать смену статуса
     * 
     * @param string $entityType Тип сущности
     * @param int $entityId ID сущности
     * @param string $entityName Название сущности
     * @param string $oldStatus Старый статус
     * @param string $newStatus Новый статус
     */
    protected function logStatusChange(string $entityType, int $entityId, string $entityName, string $oldStatus, string $newStatus): void
    {
        AdminLogService::logStatusChange($entityType, $entityId, $entityName, $oldStatus, $newStatus);
    }

    /**
     * Логировать экспорт
     * 
     * @param string $entityType Тип экспортируемых данных
     * @param int $count Количество записей
     * @param string|null $format Формат файла
     */
    protected function logExport(string $entityType, int $count, ?string $format = null): void
    {
        AdminLogService::logExport($entityType, $count, $format);
    }

    /**
     * Логировать импорт
     * 
     * @param string $entityType Тип импортируемых данных
     * @param int $successCount Успешно импортировано
     * @param int $errorCount Ошибок
     */
    protected function logImport(string $entityType, int $successCount, int $errorCount = 0): void
    {
        AdminLogService::logImport($entityType, $successCount, $errorCount);
    }

    /**
     * Логировать массовое действие
     * 
     * @param string $action Действие (delete, update, activate и т.д.)
     * @param string $entityType Тип сущности
     * @param int $count Количество
     * @param array|null $params Дополнительные параметры
     */
    protected function logBulkAction(string $action, string $entityType, int $count, ?array $params = null): void
    {
        AdminLogService::logBulkAction($action, $entityType, $count, $params);
    }

    /**
     * Логировать произвольное действие
     * 
     * @param string $action Тип действия
     * @param string $entityType Тип сущности
     * @param int|null $entityId ID сущности
     * @param string|null $entityName Название
     * @param string|null $description Описание
     * @param array|null $oldValues Старые значения
     * @param array|null $newValues Новые значения
     */
    protected function logAction(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityName = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        AdminLogService::log($action, $entityType, $entityId, $entityName, $description, $oldValues, $newValues);
    }
}
