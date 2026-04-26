<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Currency rate update command.
 *
 * Add to crontab:
 *   0 * /4 * * * /path/to/yii currency/update >> /tmp/currency.log 2>&1
 */
class CurrencyController extends Controller
{
    /**
     * Update CNY/BYN rate via CurrencyService.
     */
    public function actionUpdate(): int
    {
        if (!Yii::$app->has('currency')) {
            $this->stderr("CurrencyService component not configured.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $service = Yii::$app->currency;
        $rate = $service->getCnyToBynRate();
        $info = $service->getCurrencyInfo();

        $this->stdout(sprintf(
            "CNY/BYN rate: %.4f (source: %s, updated: %s)\n",
            $rate,
            $info['source'] ?? 'unknown',
            $info['updated_at'] ?? 'n/a'
        ));

        return ExitCode::OK;
    }
}
