<?php

declare(strict_types=1);

namespace WooPanel\Application;

use WooPanel\Application\Port\MediaGateway;
use WooPanel\Application\Port\OrderGateway;
use WooPanel\Application\Port\ProductGateway;
use WooPanel\Application\Port\ReportGateway;

final readonly class Store
{
    public function __construct(
        public OrderGateway $orders,
        public ProductGateway $products,
        public ReportGateway $reports,
        public MediaGateway $media,
    ) {}
}
