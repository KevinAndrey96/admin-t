<?php

declare(strict_types=1);

namespace WooPanel\Application;

use WooPanel\Domain\OrderStatus;

final readonly class OrderQuery
{
    public const int PER_PAGE = 20;

    public int $page;
    public string $search;

    public function __construct(
        int $page = 1,
        public ?OrderStatus $status = null,
        string $search = '',
        public int $perPage = self::PER_PAGE,
    ) {
        $this->page = max(1, $page);
        $this->search = mb_substr(trim($search), 0, 100);
    }
}
