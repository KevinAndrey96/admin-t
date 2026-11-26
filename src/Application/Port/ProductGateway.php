<?php

declare(strict_types=1);

namespace WooPanel\Application\Port;

use WooPanel\Application\ProductQuery;
use WooPanel\Domain\Page;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductDraft;

interface ProductGateway
{
    /**
     * @return Page<Product>
     */
    public function list(ProductQuery $query): Page;

    public function find(int $id): ?Product;

    public function create(ProductDraft $draft, ?int $imageId): Product;

    public function update(int $id, ProductDraft $draft, ?int $imageId): Product;

    /** Moves the product to the WordPress trash, where it can still be restored. */
    public function trash(int $id): void;
}
