<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Http\ErrorReporter;
use WooPanel\Http\Request;

final class RecordingReporter implements ErrorReporter
{
    /** @var list<\Throwable> */
    public array $errors = [];

    public function report(\Throwable $error, Request $request): void
    {
        $this->errors[] = $error;
    }
}
