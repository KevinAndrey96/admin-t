<?php

declare(strict_types=1);

namespace WooPanel\Application\Port;

use WooPanel\Application\OrderQuery;
use WooPanel\Domain\Order;
use WooPanel\Domain\OrderNote;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Page;

interface OrderGateway
{
    /**
     * @return Page<Order>
     */
    public function list(OrderQuery $query): Page;

    public function find(int $id): ?Order;

    public function changeStatus(int $id, OrderStatus $status): Order;

    /**
     * @return list<OrderNote>
     */
    public function notes(int $orderId): array;

    public function addNote(int $orderId, string $text, bool $visibleToCustomer): void;
}
