<?php

/**
 * CompanySettings — Модель настроек компании
 * 
 * НАЗНАЧЕНИЕ:
 * Реквизиты компании для документов и публичных страниц:
 * название, УНП, адрес, банковские реквизиты, контакты.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название компании
 * - unp: УНП (идентификационный номер)
 * - address: юридический адрес
 * - bank: название банка
 * - bic: БИК банка
 * - account: расчётный счёт
 * - phone: телефон
 * - email: email
 * - offer_url: URL договора оферты
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - DashboardController/admin (редактирование настроек)
 * - Публичные страницы (контакты, оферта)
 * - Документы (чеки, накладные)
 * 
 * ОСОБЕННОСТИ:
 * - Singleton: одна запись на всю систему
 * - Автоматическое обновление updated_at
 */
namespace app\backend\modules\admin\models;

use yii\db\ActiveRecord;

class CompanySettings extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%company_settings}}';
    }

    /**
     * Получить настройки компании
     * @return array
     */
    public static function getSettings()
    {
        try {
            $settings = self::find()->one();
            if ($settings) {
                return [
                    'name' => $settings->name,
                    'unp' => $settings->unp,
                    'address' => $settings->address,
                    'bank' => $settings->bank,
                    'bic' => $settings->bic,
                    'account' => $settings->account,
                    'phone' => $settings->phone,
                    'email' => $settings->email,
                    'offer_url' => $settings->offer_url,
                ];
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки
        }
        
        // Дефолтные настройки
        return [
            'name' => 'СНИКЕРХЭД',
            'unp' => '',
            'address' => '',
            'bank' => '',
            'bic' => '',
            'account' => '',
            'phone' => '',
            'email' => '',
            'offer_url' => '',
        ];
    }

    public function rules()
    {
        return [
            [['name', 'unp', 'address', 'bank', 'bic', 'account', 'phone', 'email'], 'required'],
            [['name', 'address', 'bank', 'email', 'offer_url'], 'string', 'max' => 255],
            [['unp', 'bic', 'phone'], 'string', 'max' => 50],
            [['account'], 'string', 'max' => 64],
            ['email', 'email'],
            [['updated_at'], 'integer'],
        ];
    }
}
