<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\admin\models\User as AdminUser;
use app\backend\modules\account\models\Customer;

/**
 * CMP-401 / CMP-352 п.6 — перегенерация auth_key всем покупателям (customer)
 * и сотрудникам админки (user) после утечки логов с сессионными данными.
 *
 * НЕ ЗАПУСКАТЬ НА ПРОДЕ без явного решения совета по CMP-352. Запускать в одном
 * окне обслуживания вместе с заменой COOKIE_VALIDATION_KEY — см.
 * docs/security/cmp-401-cookie-rotation-runbook.md.
 *
 * Использование:
 *   php yii rotate-auth-keys                 — dry-run (по умолчанию), в БД ничего не пишет
 *   php yii rotate-auth-keys --dry-run=0      — реальный запуск, одна транзакция на весь прогон
 */
class RotateAuthKeysController extends Controller
{
    /**
     * @var bool По умолчанию true — команда ничего не меняет, пока явно не передан --dry-run=0.
     */
    public $dryRun = true;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['dryRun']);
    }

    public function optionAliases()
    {
        return ['d' => 'dryRun'];
    }

    public function actionIndex(): int
    {
        $this->stdout('Режим: ' . ($this->dryRun ? 'DRY-RUN (изменений не будет)' : 'ПРИМЕНЕНИЕ') . "\n");

        $customerCount = Customer::find()->count();
        $adminCount = AdminUser::find()->count();

        $this->stdout(sprintf("Найдено покупателей (customer): %d\n", $customerCount));
        $this->stdout(sprintf("Найдено сотрудников админки (user): %d\n", $adminCount));

        if ($this->dryRun) {
            $this->stdout("Dry-run: auth_key не изменён ни у одной записи.\n");
            $this->stdout("Для реального запуска: php yii rotate-auth-keys --dry-run=0\n");
            return ExitCode::OK;
        }

        $updatedCustomers = 0;
        $updatedAdmins = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach (Customer::find()->each() as $customer) {
                /** @var Customer $customer */
                $customer->generateAuthKey();
                if (!$customer->save(false, ['auth_key'])) {
                    throw new \RuntimeException("Не удалось сохранить auth_key для customer #{$customer->id}");
                }
                $updatedCustomers++;
            }

            foreach (AdminUser::find()->each() as $admin) {
                /** @var AdminUser $admin */
                $admin->generateAuthKey();
                if (!$admin->save(false, ['auth_key'])) {
                    throw new \RuntimeException("Не удалось сохранить auth_key для admin user #{$admin->id}");
                }
                $updatedAdmins++;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr('ОШИБКА, транзакция откачена: ' . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(sprintf(
            "Готово. Обновлено покупателей: %d, сотрудников админки: %d.\n",
            $updatedCustomers,
            $updatedAdmins
        ));
        $this->stdout(
            "Важно: это НЕ завершает уже открытые PHP-сессии покупателей (session['customer_id']) — " .
            "авторизация витрины идёт через сессию, а не через identity-cookie (см. CMP-401 п.1а). " .
            "Для полного релогина покупателей нужна отдельная очистка серверного session storage.\n"
        );

        return ExitCode::OK;
    }
}
