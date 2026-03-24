<?php

/**
 * Settings — Компонент настроек приложения
 * 
 * НАЗНАЧЕНИЕ:
 * Централизованный доступ к настройкам системы: реквизиты компании,
 * статусы заказов, глобальные параметры. Кэширование данных.
 * 
 * МЕТОДЫ:
 * - getCompany(): реквизиты компании (название, УНП, адрес, контакты)
 * - getStatuses(): список статусов заказов
 * - getLogistStatuses(): статусы, доступные логистам
 * - get($key, $default): получение настройки по ключу
 * 
 * КЭШИРОВАНИЕ:
 * - Реквизиты компании кэшируются в памяти
 * - Статусы кэшируются в памяти
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - Yii::$app->settings->getCompany()
 * - Yii::$app->settings->getStatuses()
 * 
 * ОСОБЕННОСТИ:
 * - Singleton pattern для настроек
 * - Автоматическое кэширование
 */
namespace app\backend\shared\components;

use Yii;
use yii\base\Component;
use app\backend\modules\admin\models\CompanySettings;
use app\backend\modules\checkout\models\OrderStatus;

class Settings extends Component
{
    private $_company;
    private $_statuses;

    public function getCompany(): array
    {
        if ($this->_company === null) {
            // Временно возвращаем значения по умолчанию, пока нет таблицы
            $this->_company = [
                'name' => 'СНИКЕРХЭД',
                'address' => 'Минск, Беларусь',
                'phone' => '+375 (29) 123-45-67',
                'email' => 'info@sneakerhead.by',
                'work_time' => 'Пн-Вс: 10:00-22:00'
            ];
            
            // Раскомментировать когда таблица будет создана
            // $row = CompanySettings::find()->orderBy(['id' => SORT_ASC])->asArray()->one();
            // $this->_company = $row ?: [];
        }
        return $this->_company;
    }

    public function getStatuses(bool $onlyKeys = false, bool $includeInactive = false): array
    {
        if ($this->_statuses === null) {
            $query = OrderStatus::find()->orderBy(['sort' => SORT_ASC]);
            if (!$includeInactive) {
                $query->where(['is_active' => true]);
            }
            $this->_statuses = $query->asArray()->all();
        }
        if ($onlyKeys) {
            return array_column($this->_statuses, 'key');
        }
        $map = [];
        foreach ($this->_statuses as $s) {
            if (!$includeInactive && !$s['is_active']) {
                continue;
            }
            $map[$s['key']] = $s['label'];
        }
        return $map;
    }

    public function getLogistStatuses(): array
    {
        $rows = OrderStatus::find()->where(['logist_available' => 1])->orderBy(['sort' => SORT_ASC])->asArray()->all();
        $map = [];
        foreach ($rows as $s) {
            $map[$s['key']] = $s['label'];
        }
        return $map;
    }

    /**
     * Получение настройки по ключу
     * @param string $section Секция настроек
     * @param string $key Ключ настройки
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get($section, $key, $default = null)
    {
        // Временно возвращаем значения по умолчанию для импорта
        if ($section === 'import') {
            $defaults = [
                'auto_import_enabled' => true,
                'import_interval_hours' => 8,
                'max_products_per_run' => 100,
                'log_retention_days' => 30,
                'notify_on_complete' => true,
                'notify_on_error' => true,
            ];
            return $defaults[$key] ?? $default;
        }
        
        // Возвращаем из временного хранилища
        return self::$storage[$section][$key] ?? $default;
    }

    /**
     * Установка настройки
     * @param string $section Секция настроек
     * @param string $key Ключ настройки
     * @param mixed $value Значение
     */
    public function set($section, $key, $value)
    {
        if (!isset(self::$storage[$section])) {
            self::$storage[$section] = [];
        }
        self::$storage[$section][$key] = $value;
    }
}
