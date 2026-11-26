<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Port\ReportGateway;
use WooPanel\Domain\Money;
use WooPanel\Domain\StoreSummary;

final readonly class WooReportGateway implements ReportGateway
{
    private const int RECENT_ORDERS = 5;

    public function __construct(private RestClient $client) {}

    public function summary(): StoreSummary
    {
        $sales = $this->client->get('wc/v3/reports/sales', ['period' => 'month'])->objects()[0] ?? [];

        $byStatus = [];
        foreach ($this->client->get('wc/v3/reports/orders/totals')->objects() as $row) {
            $byStatus[Payload::string($row, 'slug')] = Payload::int($row, 'total');
        }

        $outOfStock = $this->client->get('wc/v3/products', ['stock_status' => 'outofstock', 'per_page' => 1, 'status' => 'publish']);
        $recent = $this->client->get('wc/v3/orders', ['per_page' => self::RECENT_ORDERS, 'orderby' => 'date', 'order' => 'desc']);

        return new StoreSummary(
            new Money(Payload::string($sales, 'total_sales'), $this->currency()),
            Payload::int($sales, 'total_orders'),
            $byStatus,
            $outOfStock->total ?? \count($outOfStock->objects()),
            array_map(Mapper::order(...), $recent->objects()),
        );
    }

    private function currency(): string
    {
        $currency = Payload::string($this->client->get('wc/v3/settings/general/woocommerce_currency')->object(), 'value');

        return $currency !== '' ? $currency : 'COP';
    }
}
