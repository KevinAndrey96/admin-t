<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class OrderNote
{
    public function __construct(
        public int $id,
        public string $text,
        public string $author,
        public \DateTimeImmutable $createdAt,
        public bool $visibleToCustomer,
    ) {}
}
