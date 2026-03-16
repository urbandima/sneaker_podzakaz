<?php

namespace app\api\v1\resources;

use yii\base\BaseObject;
use app\backend\modules\catalog\models\Product;

/**
 * API ресурс товара (DTO)
 */
class ProductResource extends BaseObject
{
    public int $id;
    public string $name;
    public string $slug;
    public ?string $sku;
    public float $price;
    public float $price_cny;
    public ?string $image;
    public ?string $description;
    public ?array $brand;
    public ?array $category;
    public array $sizes;
    public array $images;

    public function __construct(Product $product)
    {
        parent::__construct([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'price_cny' => (float) $product->price_cny,
            'image' => $product->mainImage?->url,
            'description' => $product->description,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'sizes' => array_map(function($size) {
                return [
                    'id' => $size->id,
                    'size' => $size->size,
                    'stock' => $size->stock,
                ];
            }, $product->sizes),
            'images' => array_map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                    'is_main' => $image->is_main,
                ];
            }, $product->images),
        ]);
    }

    /**
     * Коллекция ресурсов
     */
    public static function collection($dataProvider): array
    {
        $resources = [];
        foreach ($dataProvider->getModels() as $model) {
            $resources[] = new self($model);
        }
        return [
            'items' => $resources,
            'pagination' => [
                'total' => $dataProvider->getTotalCount(),
                'page' => $dataProvider->getPagination()->getPage() + 1,
                'per_page' => $dataProvider->getPagination()->getPageSize(),
            ],
        ];
    }
}
