<?php

namespace app\services\Sitemap\sections;

use XMLWriter;
use app\models\Product;

/**
 * Секция изображений товаров для image sitemap
 * Включает только top-N популярных товаров с изображениями
 */
class ImageSection extends AbstractSitemapSection
{
    private string $filename;
    private int $batchSize;
    private int $topProducts;
    private int $imageLimit;

    public function __construct(string $baseUrl, string $filename = 'sitemap-images.xml')
    {
        parent::__construct($baseUrl);
        $config = \Yii::$app->params['sitemap'] ?? [];
        $this->filename = $filename;
        $this->batchSize = $config['imageBatchSize'] ?? 500;
        $this->topProducts = $config['topProductsForImages'] ?? 1000;
        $this->imageLimit = $config['imageLimit'] ?? 5;
    }

    public function write(XMLWriter $writer): int
    {
        // Image sitemap генерируется отдельным файлом, поэтому здесь ничего не добавляем
        return 0;
    }

    public function generate(): void
    {
        $filePath = \Yii::getAlias('@webroot/' . $this->filename);
        $tempPath = $filePath . '.tmp';

        try {
            $writer = new XMLWriter();
            if (!$writer->openURI($tempPath)) {
                throw new \RuntimeException("Unable to open temporary image sitemap file: $tempPath");
            }

            $writer->setIndent(true);
            $writer->startDocument('1.0', 'UTF-8');

            $writer->startElement('urlset');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

            $query = Product::find()
                ->alias('p')
                ->innerJoin('product_image pi', 'pi.product_id = p.id')
                ->select(['p.id', 'p.slug', 'p.name'])
                ->with(['images' => function($q) {
                    $q->select(['product_id', 'image', 'is_main', 'sort_order'])
                        ->orderBy(['is_main' => SORT_DESC, 'sort_order' => SORT_ASC])
                        ->limit($this->imageLimit);
                }])
                ->where(['p.is_active' => 1])
                ->groupBy(['p.id'])
                ->orderBy(['p.views_count' => SORT_DESC])
                ->limit($this->topProducts);

            foreach ($query->batch($this->batchSize) as $products) {
                foreach ($products as $product) {
                    if (empty($product->images)) {
                        continue;
                    }

                    $writer->startElement('url');
                    $writer->writeElement('loc', $this->absoluteUrl('/catalog/product/' . $product->slug));

                    foreach ($product->images as $image) {
                        if (empty($image->image)) {
                            continue;
                        }
                        $writer->startElement('image:image');
                        $writer->writeElement('image:loc', $this->absoluteUrl($image->image));
                        $writer->writeElement('image:title', $product->name);
                        $writer->endElement();
                    }

                    $writer->endElement();
                }
            }

            $writer->endElement();
            $writer->endDocument();
            $writer->flush();

            if (!@rename($tempPath, $filePath)) {
                throw new \RuntimeException("Failed to rename image sitemap file from $tempPath to $filePath");
            }
        } catch (\Throwable $e) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            throw $e;
        }
    }
}
