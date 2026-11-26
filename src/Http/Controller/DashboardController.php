<?php

declare(strict_types=1);

namespace WooPanel\Http\Controller;

use WooPanel\Application\Port\ReportGateway;
use WooPanel\Domain\OrderStatus;
use WooPanel\Http\Responder;
use WooPanel\Http\Response;

final readonly class DashboardController
{
    public function __construct(
        private ReportGateway $reports,
        private Responder $respond,
    ) {}

    public function show(): Response
    {
        return $this->respond->page('dashboard', [
            'summary' => $this->reports->summary(),
            'statuses' => OrderStatus::assignable(),
        ], 'Resumen');
    }
}
