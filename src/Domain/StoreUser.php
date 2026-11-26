<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class StoreUser
{
    public function __construct(
        public int $id,
        public string $displayName,
    ) {}
}
