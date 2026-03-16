<?php
/**
 * TemporaryAdminIdentity — Временный класс для авторизации в админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Временная реализация IdentityInterface для входа в админку без БД.
 * Используется только для разработки и тестирования.
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * AdminController::actionLogin() для временной авторизации
 */
namespace app\backend\modules\admin\models;

use Yii;
use yii\web\IdentityInterface;

class TemporaryAdminIdentity implements IdentityInterface
{
    public $id = 1;
    public $username = 'admin';
    public $role = 'admin';
    public $authKey = 'temp-admin-key-2026';
    
    public static function findIdentity($id)
    {
        // Возвращаем экземпляр для ID=1 (admin)
        if ($id == 1) {
            return new self();
        }
        return null;
    }
    
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null; // Не используется для временной авторизации
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getAuthKey()
    {
        return $this->authKey;
    }
    
    public function validateAuthKey($authKey)
    {
        return $authKey === $this->authKey;
    }
    
    // Дополнительные методы для админки
    public function isAdmin()
    {
        return true;
    }
    
    public function getIsAdmin()
    {
        return $this->isAdmin();
    }
    
    public function can($permission)
    {
        return true; // Временный доступ ко всему
    }
    
    public function isLogist()
    {
        return false; // Временный пользователь - не логист
    }
    
    public function isManager()
    {
        return false; // Временный пользователь - не менеджер
    }
    
    public function getRole()
    {
        return 'admin';
    }
    
    /**
     * Очистка всех сессий для этого пользователя
     */
    public static function clearAllSessions()
    {
        // ВРЕМЕННО: Отключаем очистку сессий - таблица session не существует
        // В продакшене нужно создать таблицу session или использовать Redis
        error_log("Session clearing disabled - table 'session' not found");
    }
}
