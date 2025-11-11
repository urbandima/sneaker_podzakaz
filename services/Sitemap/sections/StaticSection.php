<?php

namespace app\services\Sitemap\sections;

use XMLWriter;

class StaticSection extends AbstractSitemapSection
{
    private array $staticPages = [
        ['/site/about', 'monthly', 0.6],
        ['/site/contacts', 'monthly', 0.6],
        ['/site/track', 'monthly', 0.5],
        ['/site/offer-agreement', 'yearly', 0.3],
        ['/site/payment-instruction', 'yearly', 0.3],
    ];

    public function write(XMLWriter $writer): int
    {
        $timestamp = time();
        $count = 0;
        foreach ($this->staticPages as [$path, $changefreq, $priority]) {
            $count += $this->writeUrl($writer, $path, [
                'priority' => $priority,
                'changefreq' => $changefreq,
                'lastmod' => $timestamp,
            ]);
        }

        return $count;
    }
}
