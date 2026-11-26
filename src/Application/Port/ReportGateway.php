<?php

declare(strict_types=1);

namespace WooPanel\Application\Port;

use WooPanel\Domain\StoreSummary;

interface ReportGateway
{
    public function summary(): StoreSummary;
}
