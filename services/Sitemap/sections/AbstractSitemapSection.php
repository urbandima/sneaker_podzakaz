<?php

namespace app\services\Sitemap\sections;

use XMLWriter;

abstract class AbstractSitemapSection implements SitemapSectionInterface
{
    protected string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function writeUrl(XMLWriter $writer, string $path, array $options = []): int
    {
        $loc = $this->absoluteUrl($path);

        $writer->startElement('url');
        $writer->writeElement('loc', $loc);

        if (!empty($options['lastmod'])) {
            $writer->writeElement('lastmod', $this->formatDate($options['lastmod']));
        }

        if (!empty($options['changefreq'])) {
            $writer->writeElement('changefreq', $options['changefreq']);
        }

        if (!empty($options['priority'])) {
            $writer->writeElement('priority', $this->formatPriority($options['priority']));
        }

        if (!empty($options['images']) && is_array($options['images'])) {
            foreach ($options['images'] as $image) {
                $this->writeImage($writer, $image);
            }
        }

        $writer->endElement();

        return 1;
    }

    protected function formatDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return date('Y-m-d', (int)$value);
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }

        return date('Y-m-d');
    }

    protected function formatPriority($priority): string
    {
        if (is_numeric($priority)) {
            $priority = number_format((float)$priority, 1, '.', '');
        }

        return (string)$priority;
    }

    protected function absoluteUrl(string $path): string
    {
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    protected function writeImage(XMLWriter $writer, array $image): void
    {
        if (empty($image['loc'])) {
            return;
        }

        $writer->startElement('image:image');
        $writer->writeElement('image:loc', $this->absoluteUrl($image['loc']));

        if (!empty($image['title'])) {
            $writer->writeElement('image:title', $image['title']);
        }

        if (!empty($image['caption'])) {
            $writer->writeElement('image:caption', $image['caption']);
        }

        $writer->endElement();
    }
}
