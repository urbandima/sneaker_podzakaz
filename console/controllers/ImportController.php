<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\admin\models\import\ImportSource;
use app\backend\modules\admin\models\import\ImportTask;
use app\backend\modules\admin\models\import\ImportLog;
use app\backend\modules\admin\models\import\ImportProductPrice;
use app\backend\modules\admin\services\import\ImportService;
use app\backend\modules\admin\services\import\ProxyService;
use app\backend\modules\admin\services\import\CaptchaService;
use app\backend\modules\admin\services\import\NbrbRateService;

/**
 * ImportController — Консольный контроллер для импорта товаров
 * 
 * Actions:
 * - run: Запуск импорта из всех активных источников
 * - source: Запуск импорта из конкретного источника
 * - update-prices: Обновление лучших цен
 * - clean-logs: Очистка старых логов
 * - update-rates: Обновление курсов валют
 */
class ImportController extends Controller
{
    /**
     * Лимит товаров за запуск
     * @var int
     */
    public $limit = 100;

    /**
     * Отправлять уведомления
     * @var bool
     */
    public $notify = true;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['limit', 'notify']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases()
    {
        return [
            'l' => 'limit',
            'n' => 'notify',
        ];
    }

    /**
     * Запуск импорта из всех активных источников
     * @return int
     */
    public function actionRun()
    {
        $this->stdout("=== Запуск автоматического импорта ===\n");
        
        // Обновляем курсы валют
        $this->updateRates();
        
        // Получаем активные источники
        $sources = ImportSource::getActiveSources();
        
        if (empty($sources)) {
            $this->stdout("Нет активных источников для импорта\n");
            return ExitCode::OK;
        }

        $this->stdout("Найдено активных источников: " . count($sources) . "\n");

        $importService = $this->createImportService();
        $results = [];

        foreach ($sources as $source) {
            $this->stdout("\n--- Источник: {$source->name} ---\n");
            
            try {
                $task = $importService->runImport($source->id, [
                    'limit' => $this->limit,
                ]);

                $this->stdout("Задача #{$task->id} создана\n");
                $this->stdout("Статус: {$task->getStatusLabel()}\n");
                $this->stdout("Импортировано: {$task->imported_count}\n");
                $this->stdout("Обновлено: {$task->updated_count}\n");
                $this->stdout("Ошибок: {$task->failed_count}\n");

                $results[$source->name] = [
                    'success' => $task->status === ImportTask::STATUS_COMPLETED,
                    'task_id' => $task->id,
                    'imported' => $task->imported_count,
                    'updated' => $task->updated_count,
                    'errors' => $task->failed_count,
                ];
            } catch (\Exception $e) {
                $this->stderr("Ошибка: {$e->getMessage()}\n");
                $results[$source->name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Итоги
        $this->stdout("\n=== Итоги ===\n");
        $totalImported = array_sum(array_column($results, 'imported'));
        $totalUpdated = array_sum(array_column($results, 'updated'));
        $totalErrors = array_sum(array_column($results, 'errors'));
        
        $this->stdout("Всего импортировано: {$totalImported}\n");
        $this->stdout("Всего обновлено: {$totalUpdated}\n");
        $this->stdout("Всего ошибок: {$totalErrors}\n");

        // Уведомления
        if ($this->notify) {
            $this->sendNotification($results);
        }

        return ExitCode::OK;
    }

    /**
     * Запуск импорта из конкретного источника
     * @param string $code Код источника (lamoda, dewu, zalando, stockx)
     * @return int
     */
    public function actionSource($code)
    {
        $source = ImportSource::findByCode($code);
        
        if (!$source) {
            $this->stderr("Источник не найден: {$code}\n");
            return ExitCode::DATAERR;
        }

        if (!$source->is_active) {
            $this->stderr("Источник неактивен: {$source->name}\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("=== Импорт из {$source->name} ===\n");

        $importService = $this->createImportService();

        try {
            $task = $importService->runImport($source->id, [
                'limit' => $this->limit,
            ]);

            $this->stdout("Задача #{$task->id}\n");
            $this->stdout("Статус: {$task->getStatusLabel()}\n");
            $this->stdout("Импортировано: {$task->imported_count}\n");
            $this->stdout("Обновлено: {$task->updated_count}\n");
            $this->stdout("Ошибок: {$task->failed_count}\n");
            $this->stdout("Дубликатов: {$task->duplicate_count}\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Ошибка: {$e->getMessage()}\n");
            return ExitCode::SOFTWARE;
        }
    }

    /**
     * Обновление лучших цен для всех товаров
     * @return int
     */
    public function actionUpdatePrices()
    {
        $this->stdout("=== Обновление лучших цен ===\n");

        $importService = $this->createImportService();
        $updated = $importService->updateBestPrices();

        $this->stdout("Обновлено товаров: {$updated}\n");

        return ExitCode::OK;
    }

    /**
     * Очистка старых логов
     * @param int $days Удалять логи старше N дней
     * @return int
     */
    public function actionCleanLogs($days = 30)
    {
        $this->stdout("=== Очистка старых логов ===\n");
        $this->stdout("Удаление логов старше {$days} дней...\n");

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Удаляем логи
        $logsDeleted = ImportLog::deleteAll(['<', 'created_at', $date]);

        // Удаляем старые цены
        $pricesDeleted = ImportProductPrice::cleanOldPrices($days);

        $this->stdout("Удалено логов: {$logsDeleted}\n");
        $this->stdout("Удалено записей цен: {$pricesDeleted}\n");

        return ExitCode::OK;
    }

    /**
     * Обновление курсов валют от НБ РБ
     * @return int
     */
    public function actionUpdateRates()
    {
        $this->stdout("=== Обновление курсов валют ===\n");

        $results = $this->updateRates();

        foreach ($results as $currency => $data) {
            if ($data['success']) {
                $this->stdout("{$currency}: {$data['rate']} BYN\n");
            } else {
                $this->stderr("{$currency}: ошибка - {$data['error']}\n");
            }
        }

        return ExitCode::OK;
    }

    /**
     * Проверка состояния источников
     * @return int
     */
    public function actionCheck()
    {
        $this->stdout("=== Проверка источников ===\n");

        $sources = ImportSource::find()->all();

        foreach ($sources as $source) {
            $this->stdout("\n{$source->name} ({$source->code}):\n");
            $this->stdout("  Активен: " . ($source->is_active ? 'Да' : 'Нет') . "\n");
            $this->stdout("  Последний запуск: " . ($source->last_run_at ?: 'Никогда') . "\n");
            $this->stdout("  Успешных запусков: {$source->successful_runs}\n");
            $this->stdout("  Ошибок: {$source->failed_runs}\n");
            $this->stdout("  Товаров спарсено: {$source->total_products_parsed}\n");

            // Проверяем API ключи CAPTCHA
            if ($source->captcha_api_key) {
                $this->stdout("  CAPTCHA: настроен ({$source->captcha_service})\n");
            } else {
                $this->stdout("  CAPTCHA: не настроен\n");
            }

            // Проверяем прокси
            if ($source->proxy_enabled) {
                $proxies = $source->getProxyListArray();
                $this->stdout("  Прокси: " . count($proxies) . " шт.\n");
            }
        }

        return ExitCode::OK;
    }

    /**
     * Создать сервис импорта
     * @return ImportService
     */
    protected function createImportService()
    {
        $service = new ImportService();
        
        $service->proxyService = new ProxyService();
        $service->captchaService = new CaptchaService();
        $service->currencyService = new NbrbRateService();

        return $service;
    }

    /**
     * Обновить курсы валют
     * @return array
     */
    protected function updateRates()
    {
        $currencyService = new NbrbRateService();
        
        if ($currencyService->needsUpdate()) {
            return $currencyService->updateAllRates();
        }

        return [];
    }

    /**
     * Отправить уведомление о результатах импорта
     * @param array $results
     */
    protected function sendNotification($results)
    {
        // TODO: Реализовать отправку уведомлений (email, telegram, etc.)
        Yii::info("Import results: " . json_encode($results), 'import');
    }
}
