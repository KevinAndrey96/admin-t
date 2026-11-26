<?php

declare(strict_types=1);

namespace WooPanel\Application;

use WooPanel\Application\Port\MediaGateway;
use WooPanel\Application\Port\ProductGateway;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductDraft;

/**
 * Creates or updates a product, uploading its new photo to the media library first.
 */
final readonly class SaveProduct
{
    public function __construct(
        private ProductGateway $products,
        private MediaGateway $media,
    ) {}

    public function __invoke(?int $productId, ProductDraft $draft, ?ImageUpload $image): Product
    {
        $imageId = $image === null ? null : $this->media->upload($image);

        return $productId === null
            ? $this->products->create($draft, $imageId)
            : $this->products->update($productId, $draft, $imageId);
    }
}
