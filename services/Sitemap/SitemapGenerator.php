<?php

namespace app\services\Sitemap;

use XMLWriter;
use Yii;
use app\services\Sitemap\sections\SitemapSectionInterface;
use app\services\Sitemap\sections\HomeSection;
use app\services\Sitemap\sections\StaticSection;
use app\services\Sitemap\sections\BrandSection;
use app\services\Sitemap\sections\CategorySection;
use app\services\Sitemap\sections\ProductSection;
use app\services\Sitemap\sections\FilterSection;
use app\services\Sitemap\sections\ImageSection;

/**
 * Сервис генерации sitemap.xml
 *
 * Возможности:
 * - Потоковая генерация XML для минимизации памяти
 * - Атомарная замена файлов
 * - Валидация XML (опционально)
 * - Метрики производительности
 */
class SitemapGenerator
{
    private const SITEMAP_PATH = '@webroot/sitemap.xml';
    private const IMAGE_SITEMAP_PATH = '@webroot/sitemap-images.xml';
    private const TEMP_SUFFIX = '.tmp';

    private XMLWriter $writer;
    private string $baseUrl;
    private int $urlCount = 0;
    private float $startTime;
    private int $startMemory;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $this->resolveBaseUrl($baseUrl);
    }

    /**
     * Сгенерировать sitemap.xml во временный файл и атомарно заменить основной
     * 
     * @throws \RuntimeException если генерация не удалась
     */
    public function generate(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        $this->urlCount = 0;

        try {
            $this->generateMainSitemap();
            $this->generateImageSitemap();
            $this->logMetrics();
        } catch (\Throwable $e) {
            \Yii::error(sprintf(
                'Sitemap generation failed: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), __METHOD__);
            throw new \RuntimeException('Sitemap generation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Секции sitemap
     *
     * @return SitemapSectionInterface[]
     */
    private function getSections(): array
    {
        return [
            new HomeSection($this->baseUrl),
            new StaticSection($this->baseUrl),
            new BrandSection($this->baseUrl),
            new CategorySection($this->baseUrl),
            new ProductSection($this->baseUrl),
            new FilterSection($this->baseUrl),
        ];
    }

    private function generateMainSitemap(): void
    {
        $filePath = \Yii::getAlias(self::SITEMAP_PATH);
        $tempPath = $filePath . self::TEMP_SUFFIX;

        try {
            $this->writer = new XMLWriter();
            if (!$this->writer->openURI($tempPath)) {
                throw new \RuntimeException("Failed to open temporary file: $tempPath");
            }

            $this->writer->setIndent(true);
            $this->writer->startDocument('1.0', 'UTF-8');

            $this->writer->startElement('urlset');
            $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

            foreach ($this->getSections() as $section) {
                $this->urlCount += $section->write($this->writer);
            }

            // Ссылка на image sitemap
            $this->urlCount += $this->writeImageSitemapLink();

            $this->writer->endElement(); // urlset
            $this->writer->endDocument();
            $this->writer->flush();

            if (!@rename($tempPath, $filePath)) {
                throw new \RuntimeException("Failed to rename sitemap file from $tempPath to $filePath");
            }

            $this->validateXmlIfEnabled($filePath);

            \Yii::info("Main sitemap generated: $filePath", __METHOD__);
        } catch (\Throwable $e) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw $e;
        }
    }

    private function writeImageSitemapLink(): int
    {
        $this->writer->startElement('url');
        $this->writer->writeElement('loc', $this->absoluteUrl('/sitemap-images.xml'));
        $this->writer->writeElement('priority', '0.4');
        $this->writer->writeElement('changefreq', 'weekly');
        $this->writer->endElement();

        return 1;
    }

    private function generateImageSitemap(): void
    {
        try {
            $imageSection = new ImageSection($this->baseUrl, 'sitemap-images.xml');
            $imageSection->generate();
            \Yii::info('Image sitemap generated successfully', __METHOD__);
        } catch (\Throwable $e) {
            \Yii::error('Image sitemap generation failed: ' . $e->getMessage(), __METHOD__);
            // Не прерываем генерацию основного sitemap
        }
    }

    /**
     * Валидация XML если включена в конфиге
     */
    private function validateXmlIfEnabled(string $filePath): void
    {
        $enableValidation = \Yii::$app->params['sitemap']['enableValidation'] ?? false;
        
        if (!$enableValidation || !class_exists('DOMDocument')) {
            return;
        }

        try {
            $dom = new \DOMDocument();
            if (!@$dom->load($filePath)) {
                throw new \RuntimeException('Invalid XML structure');
            }
            
            \Yii::info('Sitemap XML validation passed', __METHOD__);
        } catch (\Throwable $e) {
            \Yii::warning('Sitemap XML validation failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Логирование метрик производительности
     */
    private function logMetrics(): void
    {
        $duration = microtime(true) - $this->startTime;
        $memoryUsed = memory_get_usage() - $this->startMemory;
        $peakMemory = memory_get_peak_usage();

        \Yii::info(sprintf(
            'Sitemap generated successfully | Duration: %.2fs | URLs: %d | Memory: %s | Peak: %s',
            $duration,
            $this->urlCount,
            $this->formatBytes($memoryUsed),
            $this->formatBytes($peakMemory)
        ), __METHOD__);
    }

    /**
     * Форматирование байтов в читаемый формат
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Получить базовый URL (hostInfo)
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    private function resolveBaseUrl(?string $baseUrl): string
    {
        if ($baseUrl) {
            return rtrim($baseUrl, '/');
        }

        $hostInfo = Yii::$app->params['frontendUrl'] ?? env('FRONTEND_URL');

        if (!$hostInfo && Yii::$app->has('request')) {
            $request = Yii::$app->get('request');
            if (method_exists($request, 'getHostInfo')) {
                $hostInfo = $request->getHostInfo();
            }
        }

        if (!$hostInfo) {
            $hostInfo = 'https://sneakerhead.by';
        }

        return rtrim($hostInfo, '/');
    }
}
