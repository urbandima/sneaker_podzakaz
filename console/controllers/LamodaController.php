<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * LamodaController — парсинг товаров с Lamoda BY
 *
 * php yii lamoda/parse --url="https://www.lamoda.by/..." --limit=50
 */
class LamodaController extends Controller
{
    /** URL каталога Lamoda для парсинга */
    public string $url = '';

    /** Максимум товаров за один запуск */
    public int $limit = 50;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['url', 'limit']);
    }

    public function optionAliases(): array
    {
        return ['u' => 'url', 'l' => 'limit'];
    }

    /**
     * Запустить парсинг каталога Lamoda BY.
     *
     * @return int
     */
    public function actionParse(): int
    {
        $url = $this->url ?: Yii::$app->settings->get('lamoda', 'last_url', '');

        if (!$url) {
            $this->stderr("Ошибка: укажите --url\n");
            return ExitCode::USAGE;
        }

        $this->stdout("=== Lamoda BY Parser ===\n");
        $this->stdout("URL: {$url}\n");
        $this->stdout("Лимит: {$this->limit} товаров\n\n");

        Yii::$app->settings->set('lamoda', 'parse_status', 'running');
        Yii::$app->settings->set('lamoda', 'last_url', $url);

        $scriptPath = Yii::getAlias('@app/scripts/parse_lamoda.php');

        if (!file_exists($scriptPath)) {
            $this->stderr("Скрипт парсера не найден: {$scriptPath}\n");
            return ExitCode::SOFTWARE;
        }

        $cmd = sprintf('php %s %s %d', escapeshellarg($scriptPath), escapeshellarg($url), (int)$this->limit);
        passthru($cmd, $exitCode);

        return $exitCode === 0 ? ExitCode::OK : ExitCode::SOFTWARE;
    }
}
