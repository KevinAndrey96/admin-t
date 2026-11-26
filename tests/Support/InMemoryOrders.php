<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\OrderQuery;
use WooPanel\Application\Port\OrderGateway;
use WooPanel\Domain\Order;
use WooPanel\Domain\OrderNote;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Page;

final class InMemoryOrders implements OrderGateway
{
    /** @var array<int, Order> */
    public array $items = [];

    /** @var list<array{int, string, bool}> */
    public array $notesAdded = [];

    public ?OrderQuery $lastQuery = null;

    public function __construct(private readonly InMemoryStore $store) {}

    public function list(OrderQuery $query): Page
    {
        $this->store->failIfAsked();
        $this->lastQuery = $query;
        $items = array_values(array_filter($this->items, static fn(Order $o): bool => $query->status === null || $o->status === $query->status));

        return new Page($items, $query->page, \count($items), 1);
    }

    public function find(int $id): ?Order
    {
        $this->store->failIfAsked();

        return $this->items[$id] ?? null;
    }

    public function changeStatus(int $id, OrderStatus $status): Order
    {
        $this->store->failIfAsked();
        $this->items[$id] = Fixtures::order($id, $status);

        return $this->items[$id];
    }

    public function notes(int $orderId): array
    {
        $this->store->failIfAsked();

        return [new OrderNote(1, 'Pedido recibido', 'WooCommerce', new \DateTimeImmutable('2026-11-20T15:20:00Z'), false)];
    }

    public function addNote(int $orderId, string $text, bool $visibleToCustomer): void
    {
        $this->store->failIfAsked();
        $this->notesAdded[] = [$orderId, $text, $visibleToCustomer];
    }
}
