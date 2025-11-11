<?php

namespace app\services\Sitemap\sections;

use XMLWriter;

interface SitemapSectionInterface
{
    public function write(XMLWriter $writer): int;
}
