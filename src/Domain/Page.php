<?php

declare(strict_types=1);

namespace WooPanel\Domain;

/**
 * One page of a listing, with the totals WooCommerce reports in its X-WP-Total headers.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $total,
        public int $totalPages,
    ) {}

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }
}
