<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\Port\ReportGateway;
use WooPanel\Domain\Money;
use WooPanel\Domain\StoreSummary;

final readonly class InMemoryReports implements ReportGateway
{
    public function __construct(private InMemoryStore $store) {}

    public function summary(): StoreSummary
    {
        $this->store->failIfAsked();

        return new StoreSummary(
            new Money('1250000', 'COP'),
            18,
            ['processing' => 3, 'on-hold' => 1, 'completed' => 14],
            2,
            array_values($this->store->orders->items),
        );
    }
}
