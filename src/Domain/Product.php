<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sku,
        public ProductStatus $status,
        public string $regularPrice,
        public string $salePrice,
        public bool $manageStock,
        public ?int $stockQuantity,
        public StockStatus $stockStatus,
        public string $shortDescription,
        public ?string $imageUrl,
        public string $permalink,
    ) {}

    public function isOnSale(): bool
    {
        return $this->salePrice !== '';
    }

    /** Values that prefill the edit form. */
    public function toDraft(): ProductDraft
    {
        return new ProductDraft(
            $this->name,
            $this->sku,
            $this->regularPrice,
            $this->salePrice,
            $this->status,
            $this->manageStock,
            $this->stockQuantity,
            $this->shortDescription,
        );
    }
}
