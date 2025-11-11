<?php

namespace app\services\Sitemap\sections;

use XMLWriter;

class HomeSection extends AbstractSitemapSection
{
    public function write(XMLWriter $writer): int
    {
        $count = 0;

        $count += $this->writeUrl($writer, '/', [
            'priority' => 1.0,
            'changefreq' => 'daily',
            'lastmod' => time(),
        ]);

        $count += $this->writeUrl($writer, '/catalog', [
            'priority' => 0.9,
            'changefreq' => 'daily',
            'lastmod' => time(),
        ]);

        return $count;
    }
}
