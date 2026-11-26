<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class OrderItem
{
    public function __construct(
        public string $name,
        public string $sku,
        public int $quantity,
        public Money $total,
    ) {}
}
