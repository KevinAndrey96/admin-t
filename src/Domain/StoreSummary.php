<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class StoreSummary
{
    /**
     * @param array<string, int> $ordersByStatus order status value => number of orders
     * @param list<Order> $recentOrders
     */
    public function __construct(
        public Money $salesThisMonth,
        public int $ordersThisMonth,
        public array $ordersByStatus,
        public int $outOfStockProducts,
        public array $recentOrders,
    ) {}

    public function count(OrderStatus $status): int
    {
        return $this->ordersByStatus[$status->value] ?? 0;
    }

    /** Orders that still need someone from the store to act on them. */
    public function ordersToAttend(): int
    {
        return $this->count(OrderStatus::Processing) + $this->count(OrderStatus::OnHold);
    }
}
