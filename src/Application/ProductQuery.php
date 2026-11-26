<?php

declare(strict_types=1);

namespace WooPanel\Application;

use WooPanel\Domain\StockStatus;

final readonly class ProductQuery
{
    public const int PER_PAGE = 20;

    public int $page;
    public string $search;

    public function __construct(
        int $page = 1,
        string $search = '',
        public ?StockStatus $stockStatus = null,
        public int $perPage = self::PER_PAGE,
    ) {
        $this->page = max(1, $page);
        $this->search = mb_substr(trim($search), 0, 100);
    }
}
