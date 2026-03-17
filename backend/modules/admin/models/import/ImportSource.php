<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;

/**
 * ImportSource — Модель источника импорта
 * 
 * Источники: Lamoda, Dewu, Zalando, StockX
 * 
 * @property int $id
 * @property string $name Название источника
 * @property string $code Код источника (lamoda, dewu, zalando, stockx)
 * @property string $base_url Базовый URL
 * @property bool $is_active Активен ли источник
 * @property int $parse_delay_min Минимальная задержка между запросами (сек)
 * @property int $parse_delay_max Максимальная задержка между запросами (сек)
 * @property bool $proxy_enabled Использовать прокси
 * @property string|null $proxy_list JSON список прокси
 * @property string $proxy_rotation Тип ротации прокси
 * @property string $captcha_service Сервис CAPTCHA
 * @property string|null $captcha_api_key API ключ CAPTCHA
 * @property string|null $captcha_fallback_service Резервный сервис CAPTCHA
 * @property string|null $captcha_fallback_api_key API ключ резервного сервиса
 * @property string $currency_code Код валюты источника
 * @property string|null $settings JSON с настройками
 * @property string|null $last_run_at Последний запуск
 * @property int $total_products_parsed Всего товаров спарсено
 * @property int $successful_runs Успешных запусков
 * @property int $failed_runs Неудачных запусков
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property ImportTask[] $tasks Задачи импорта
 * @property ImportProductPrice[] $productPrices Цены товаров
 * @property ImportCategoryMap[] $categoryMaps Маппинг категорий
 */
class ImportSource extends ActiveRecord
{
    const SOURCE_LAMODA = 'lamoda';
    const SOURCE_DEWU = 'dewu';
    const SOURCE_ZALANDO = 'zalando';
    const SOURCE_STOCKX = 'stockx';

    const CAPTCHA_2CAPTCHA = '2captcha';
    const CAPTCHA_ANTICAPTCHA = 'anticaptcha';
    const CAPTCHA_CAPMONSTER = 'capmonster';

    const PROXY_ROTATION_RANDOM = 'random';
    const PROXY_ROTATION_SEQUENTIAL = 'sequential';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_source}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'code', 'base_url', 'currency_code'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique'],
            [['base_url'], 'string', 'max' => 255],
            [['base_url'], 'url'],
            [['is_active', 'proxy_enabled'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['parse_delay_min', 'parse_delay_max'], 'integer', 'min' => 1, 'max' => 60],
            [['parse_delay_min'], 'default', 'value' => 2],
            [['parse_delay_max'], 'default', 'value' => 5],
            [['proxy_list', 'settings'], 'string'],
            [['proxy_rotation'], 'string', 'max' => 20],
            [['proxy_rotation'], 'in', 'range' => [self::PROXY_ROTATION_RANDOM, self::PROXY_ROTATION_SEQUENTIAL]],
            [['proxy_rotation'], 'default', 'value' => self::PROXY_ROTATION_RANDOM],
            [['captcha_service', 'captcha_fallback_service'], 'string', 'max' => 50],
            [['captcha_service'], 'in', 'range' => [self::CAPTCHA_2CAPTCHA, self::CAPTCHA_ANTICAPTCHA, self::CAPTCHA_CAPMONSTER]],
            [['captcha_service'], 'default', 'value' => self::CAPTCHA_2CAPTCHA],
            [['captcha_api_key', 'captcha_fallback_api_key'], 'string', 'max' => 255],
            [['currency_code'], 'string', 'max' => 3],
            [['total_products_parsed', 'successful_runs', 'failed_runs'], 'integer', 'min' => 0],
            [['last_run_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название источника',
            'code' => 'Код источника',
            'base_url' => 'Базовый URL',
            'is_active' => 'Активен',
            'parse_delay_min' => 'Мин. задержка (сек)',
            'parse_delay_max' => 'Макс. задержка (сек)',
            'proxy_enabled' => 'Использовать прокси',
            'proxy_list' => 'Список прокси',
            'proxy_rotation' => 'Тип ротации прокси',
            'captcha_service' => 'Сервис CAPTCHA',
            'captcha_api_key' => 'API ключ CAPTCHA',
            'captcha_fallback_service' => 'Резервный сервис CAPTCHA',
            'captcha_fallback_api_key' => 'API ключ резервного сервиса',
            'currency_code' => 'Код валюты',
            'settings' => 'Дополнительные настройки',
            'last_run_at' => 'Последний запуск',
            'total_products_parsed' => 'Всего товаров спарсено',
            'successful_runs' => 'Успешных запусков',
            'failed_runs' => 'Неудачных запусков',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * Задачи импорта
     */
    public function getTasks()
    {
        return $this->hasMany(ImportTask::class, ['source_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Цены товаров из источника
     */
    public function getProductPrices()
    {
        return $this->hasMany(ImportProductPrice::class, ['source_id' => 'id']);
    }

    /**
     * Маппинг категорий
     */
    public function getCategoryMaps()
    {
        return $this->hasMany(ImportCategoryMap::class, ['source_id' => 'id']);
    }

    /**
     * Получить список прокси
     * @return array
     */
    public function getProxyListArray()
    {
        if (empty($this->proxy_list)) {
            return [];
        }

        $list = json_decode($this->proxy_list, true);
        return is_array($list) ? $list : [];
    }

    /**
     * Установить список прокси
     * @param array $proxies
     */
    public function setProxyListArray(array $proxies)
    {
        $this->proxy_list = json_encode($proxies, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получить настройки
     * @return array
     */
    public function getSettingsArray()
    {
        if (empty($this->settings)) {
            return [];
        }

        $settings = json_decode($this->settings, true);
        return is_array($settings) ? $settings : [];
    }

    /**
     * Установить настройки
     * @param array $settings
     */
    public function setSettingsArray(array $settings)
    {
        $this->settings = json_encode($settings, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получить случайную задержку
     * @return int
     */
    public function getRandomDelay()
    {
        return rand($this->parse_delay_min, $this->parse_delay_max);
    }

    /**
     * Получить все активные источники
     * @return ImportSource[]
     */
    public static function getActiveSources()
    {
        return static::find()
            ->where(['is_active' => true])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить источник по коду
     * @param string $code
     * @return ImportSource|null
     */
    public static function findByCode($code)
    {
        return static::findOne(['code' => $code]);
    }

    /**
     * Обновить статистику после запуска
     * @param bool $success
     * @param int $productsCount
     */
    public function updateStats($success, $productsCount = 0)
    {
        $this->last_run_at = date('Y-m-d H:i:s');
        $this->total_products_parsed += $productsCount;
        
        if ($success) {
            $this->successful_runs++;
        } else {
            $this->failed_runs++;
        }
        
        $this->save(false, ['last_run_at', 'total_products_parsed', 'successful_runs', 'failed_runs']);
    }
}
