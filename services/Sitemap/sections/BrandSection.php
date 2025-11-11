<?php

namespace app\services\Sitemap\sections;

use XMLWriter;
use app\models\Brand;

class BrandSection extends AbstractSitemapSection
{
    public function write(XMLWriter $writer): int
    {
        $brands = Brand::find()
            ->select(['slug', 'updated_at'])
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $count = 0;
        foreach ($brands as $brand) {
            $count += $this->writeUrl($writer, '/catalog/brand/' . $brand->slug, [
                'priority' => 0.8,
                'changefreq' => 'weekly',
                'lastmod' => $brand->updated_at,
            ]);
        }

        return $count;
    }
}
