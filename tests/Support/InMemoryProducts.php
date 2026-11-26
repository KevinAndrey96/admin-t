<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\Error\StoreError;
use WooPanel\Application\Port\ProductGateway;
use WooPanel\Application\ProductQuery;
use WooPanel\Domain\Page;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductDraft;

final class InMemoryProducts implements ProductGateway
{
    /** @var array<int, Product> */
    public array $items = [];

    /** @var list<array{?int, ProductDraft, ?int}> */
    public array $saved = [];

    /** @var list<int> */
    public array $trashed = [];

    /** Fails only the next create or update, after lookups succeeded. */
    public ?StoreError $saveFailure = null;

    public function __construct(private readonly InMemoryStore $store) {}

    public function list(ProductQuery $query): Page
    {
        $this->store->failIfAsked();
        $items = array_values(array_filter($this->items, static fn(Product $p): bool => $query->stockStatus === null || $p->stockStatus === $query->stockStatus));

        return new Page($items, $query->page, \count($items), 1);
    }

    public function find(int $id): ?Product
    {
        $this->store->failIfAsked();

        return $this->items[$id] ?? null;
    }

    public function create(ProductDraft $draft, ?int $imageId): Product
    {
        return $this->save(null, $draft, $imageId);
    }

    public function update(int $id, ProductDraft $draft, ?int $imageId): Product
    {
        return $this->save($id, $draft, $imageId);
    }

    public function trash(int $id): void
    {
        $this->store->failIfAsked();
        $this->trashed[] = $id;
    }

    private function save(?int $id, ProductDraft $draft, ?int $imageId): Product
    {
        $this->store->failIfAsked();
        if ($this->saveFailure !== null) {
            throw $this->saveFailure;
        }
        $this->saved[] = [$id, $draft, $imageId];
        $product = Fixtures::product($id ?? 900);
        $this->items[$product->id] = $product;

        return $product;
    }
}
