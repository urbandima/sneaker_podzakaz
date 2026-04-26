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
    private static $storage = [];

    public function getCompany(): array
    {
        if ($this->_company === null) {
            // B0.5: Загружаем настройки из БД
            $row = CompanySettings::find()->orderBy(['id' => SORT_ASC])->asArray()->one();
            
            // Fallback на значения по умолчанию если таблица пуста
            $this->_company = $row ?: [
                'name' => 'СНИКЕРХЭД',
                'address' => 'Минск, Беларусь',
                'phone' => '+375 (29) 123-45-67',
                'email' => 'info@sneakerhead.by',
                'work_time' => 'Пн-Вс: 10:00-22:00'
            ];
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

    public function resetStatusesCache()
    {
        $this->_statuses = null;
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
        // Возвращаем из кеша в памяти если есть
        if (isset(self::$storage[$section][$key])) {
            return self::$storage[$section][$key];
        }

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
            if (isset($defaults[$key])) {
                return $defaults[$key];
            }
        }

        // Читаем из БД
        try {
            $row = Yii::$app->db->createCommand(
                'SELECT value FROM {{%app_setting}} WHERE section = :s AND `key` = :k LIMIT 1',
                [':s' => $section, ':k' => $key]
            )->queryScalar();

            if ($row !== false && $row !== null) {
                if (!isset(self::$storage[$section])) {
                    self::$storage[$section] = [];
                }
                self::$storage[$section][$key] = $row;
                return $row;
            }
        } catch (\Exception $e) {
            // Таблица ещё не создана — возвращаем default
        }

        return $default;
    }

    /**
     * Установка настройки (сохраняет в БД)
     * @param string $section Секция настроек
     * @param string $key Ключ настройки
     * @param mixed $value Значение
     */
    public function set($section, $key, $value)
    {
        // Обновляем кеш в памяти
        if (!isset(self::$storage[$section])) {
            self::$storage[$section] = [];
        }
        self::$storage[$section][$key] = $value;

        // Сохраняем в БД (INSERT OR UPDATE)
        try {
            $dbValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;

            $exists = Yii::$app->db->createCommand(
                'SELECT COUNT(*) FROM {{%app_setting}} WHERE section = :s AND `key` = :k',
                [':s' => $section, ':k' => $key]
            )->queryScalar();

            if ($exists) {
                Yii::$app->db->createCommand()->update('{{%app_setting}}', [
                    'value' => $dbValue,
                    'updated_at' => time(),
                ], ['section' => $section, 'key' => $key])->execute();
            } else {
                Yii::$app->db->createCommand()->insert('{{%app_setting}}', [
                    'section' => $section,
                    'key' => $key,
                    'value' => $dbValue,
                    'updated_at' => time(),
                ])->execute();
            }
        } catch (\Exception $e) {
            // Таблица ещё не создана — работаем только с кешем в памяти
            Yii::warning('Settings::set failed to persist: ' . $e->getMessage(), 'settings');
        }
    }

    public function invalidateCompanyCache()
    {
        $this->_company = null;
    }
}
