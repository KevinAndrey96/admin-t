<?php

declare(strict_types=1);

namespace WooPanel\Http;

interface ErrorReporter
{
    public function report(\Throwable $error, Request $request): void;
}
