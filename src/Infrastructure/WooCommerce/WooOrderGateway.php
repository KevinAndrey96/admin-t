<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\OrderQuery;
use WooPanel\Application\Port\OrderGateway;
use WooPanel\Domain\Order;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Page;

final readonly class WooOrderGateway implements OrderGateway
{
    public function __construct(private RestClient $client) {}

    public function list(OrderQuery $query): Page
    {
        $params = ['page' => $query->page, 'per_page' => $query->perPage, 'orderby' => 'date', 'order' => 'desc'];
        if ($query->status !== null) {
            $params['status'] = $query->status->value;
        }
        if ($query->search !== '') {
            $params['search'] = $query->search;
        }

        $response = $this->client->get('wc/v3/orders', $params);
        $orders = array_map(Mapper::order(...), $response->objects());

        return new Page($orders, $query->page, $response->total ?? \count($orders), max(1, $response->totalPages ?? 1));
    }

    public function find(int $id): ?Order
    {
        try {
            $data = $this->client->get('wc/v3/orders/' . $id)->object();
        } catch (StoreRejected $e) {
            if ($e->status === 404) {
                return null;
            }

            throw $e;
        }

        return Payload::string($data, 'status') === 'trash' ? null : Mapper::order($data);
    }

    public function changeStatus(int $id, OrderStatus $status): Order
    {
        return Mapper::order($this->client->put('wc/v3/orders/' . $id, ['status' => $status->value])->object());
    }

    public function notes(int $orderId): array
    {
        return array_map(Mapper::note(...), $this->client->get(\sprintf('wc/v3/orders/%d/notes', $orderId))->objects());
    }

    public function addNote(int $orderId, string $text, bool $visibleToCustomer): void
    {
        $this->client->post(\sprintf('wc/v3/orders/%d/notes', $orderId), ['note' => $text, 'customer_note' => $visibleToCustomer]);
    }
}
