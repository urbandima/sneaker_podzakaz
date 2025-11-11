<?php

namespace app\services\Sitemap\sections;

use XMLWriter;
use app\models\Category;

class CategorySection extends AbstractSitemapSection
{
    public function write(XMLWriter $writer): int
    {
        $categories = Category::find()
            ->select(['slug', 'updated_at'])
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $count = 0;
        foreach ($categories as $category) {
            $count += $this->writeUrl($writer, '/catalog/category/' . $category->slug, [
                'priority' => 0.8,
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at,
            ]);
        }

        return $count;
    }
}
