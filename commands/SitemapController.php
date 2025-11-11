<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\services\Sitemap\SitemapGenerator;
use app\components\SitemapNotifier;

/**
 * Консольная команда для работы с sitemap.
 *
 * Использование:
 * php yii sitemap/generate            # принудительная генерация
 * php yii sitemap/generate --ifPending # генерация только при установленном флаге
 * php yii sitemap/status               # статус очереди
 */
class SitemapController extends Controller
{
    /** @var bool Генерировать только если установлен флаг pending */
    public bool $ifPending = false;

    /** @var string|null Принудительный базовый URL */
    public ?string $baseUrl = null;

    public function options($actionID)
    {
        return match ($actionID) {
            'generate' => ['ifPending', 'baseUrl'],
            default => parent::options($actionID),
        };
    }

    public function optionAliases()
    {
        return [
            'p' => 'ifPending',
            'b' => 'baseUrl',
        ];
    }

    /**
     * Сгенерировать sitemap.xml и sitemap-images.xml
     */
    public function actionGenerate(): int
    {
        if ($this->ifPending && !SitemapNotifier::isPending()) {
            $this->stdout("Список задач пуст — генерация не требуется.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Генерация sitemap...\n", Console::FG_GREEN);

        $baseUrl = $this->baseUrl ? rtrim($this->baseUrl, '/') : null;
        $generator = new SitemapGenerator($baseUrl);
        $generator->generate();

        SitemapNotifier::reset();

        $sitemapPath = Yii::getAlias('@webroot/sitemap.xml');
        $imagePath = Yii::getAlias('@webroot/sitemap-images.xml');

        $this->stdout("✓ sitemap.xml: {$sitemapPath}\n", Console::FG_GREEN);
        $this->stdout("✓ sitemap-images.xml: {$imagePath}\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Показать статус очереди генерации
     */
    public function actionStatus(): int
    {
        $pending = SitemapNotifier::isPending();
        $lastRun = SitemapNotifier::getLastRun();

        if ($pending) {
            $timestamp = SitemapNotifier::getPendingTimestamp();
            $this->stdout("✳︎ Ожидает регенерации с " . ($timestamp ? date('Y-m-d H:i', $timestamp) : 'неизвестно') . "\n", Console::FG_YELLOW);
        } else {
            $this->stdout("Нет ожидающих задач регенерации.\n", Console::FG_GREEN);
        }

        if ($lastRun) {
            $this->stdout("Последняя успешная генерация: " . date('Y-m-d H:i', $lastRun) . "\n");
        } else {
            $this->stdout("Генерация ещё не выполнялась.\n", Console::FG_YELLOW);
        }

        $this->stdout("Пути: " . Yii::getAlias('@webroot/sitemap.xml') . ", " . Yii::getAlias('@webroot/sitemap-images.xml') . "\n");

        return ExitCode::OK;
    }
}
