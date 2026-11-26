<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\Port\ProductGateway;
use WooPanel\Application\ProductQuery;
use WooPanel\Domain\Page;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductDraft;

final readonly class WooProductGateway implements ProductGateway
{
    public function __construct(private RestClient $client) {}

    public function list(ProductQuery $query): Page
    {
        $params = ['page' => $query->page, 'per_page' => $query->perPage, 'status' => 'any', 'orderby' => 'date', 'order' => 'desc'];
        if ($query->search !== '') {
            $params['search'] = $query->search;
        }
        if ($query->stockStatus !== null) {
            $params['stock_status'] = $query->stockStatus->value;
        }

        $response = $this->client->get('wc/v3/products', $params);
        $products = array_map(Mapper::product(...), $response->objects());

        return new Page($products, $query->page, $response->total ?? \count($products), max(1, $response->totalPages ?? 1));
    }

    public function find(int $id): ?Product
    {
        try {
            $data = $this->client->get('wc/v3/products/' . $id)->object();
        } catch (StoreRejected $e) {
            if ($e->status === 404) {
                return null;
            }

            throw $e;
        }

        return Payload::string($data, 'status') === 'trash' ? null : Mapper::product($data);
    }

    public function create(ProductDraft $draft, ?int $imageId): Product
    {
        return Mapper::product($this->client->post('wc/v3/products', self::body($draft, $imageId))->object());
    }

    public function update(int $id, ProductDraft $draft, ?int $imageId): Product
    {
        return Mapper::product($this->client->put('wc/v3/products/' . $id, self::body($draft, $imageId))->object());
    }

    public function trash(int $id): void
    {
        $this->client->delete('wc/v3/products/' . $id);
    }

    /**
     * @return array<string, mixed>
     */
    private static function body(ProductDraft $draft, ?int $imageId): array
    {
        $body = $draft->toPayload();
        if ($imageId !== null) {
            $body['images'] = [['id' => $imageId]];
        }

        return $body;
    }
}
