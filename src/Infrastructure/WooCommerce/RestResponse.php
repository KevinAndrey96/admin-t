<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

final readonly class RestResponse
{
    public function __construct(
        public mixed $data,
        public ?int $total = null,
        public ?int $totalPages = null,
    ) {}

    /**
     * @return array<array-key, mixed>
     */
    public function object(): array
    {
        return Payload::map($this->data);
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    public function objects(): array
    {
        return array_map(Payload::map(...), Payload::list($this->data));
    }
}
