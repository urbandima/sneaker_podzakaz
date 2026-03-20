<?php

/**
 * LoyaltyBalanceWidget — Виджет баланса баллов лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * Отображение баланса баллов лояльности в header сайта.
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * <?= LoyaltyBalanceWidget::widget() ?>
 */
namespace app\frontend\widgets;

use Yii;
use yii\base\Widget;
use app\backend\modules\loyalty\services\LoyaltyService;

class LoyaltyBalanceWidget extends Widget
{
    /**
     * @var bool Показывать только иконку без текста
     */
    public $iconOnly = false;

    /**
     * @var string CSS класс для контейнера
     */
    public $containerClass = 'loyalty-balance-widget';

    public function run()
    {
        // Проверяем авторизацию
        if (Yii::$app->user->isGuest) {
            return '';
        }

        $customerId = Yii::$app->user->id;
        $loyaltyService = new LoyaltyService();
        
        try {
            $balance = $loyaltyService->getCustomerBalance($customerId);
            $level = $loyaltyService->getCustomerLevel($customerId);
            $info = $loyaltyService->getCustomerInfo($customerId);
        } catch (\Exception $e) {
            Yii::error('Ошибка получения баланса лояльности: ' . $e->getMessage(), 'loyalty');
            return '';
        }

        return $this->render('loyalty-balance', [
            'balance' => $balance,
            'level' => $level,
            'info' => $info,
            'iconOnly' => $this->iconOnly,
            'containerClass' => $this->containerClass,
        ]);
    }
}
