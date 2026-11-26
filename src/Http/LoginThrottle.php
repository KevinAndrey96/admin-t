<?php

declare(strict_types=1);

namespace WooPanel\Http;

interface LoginThrottle
{
    public function tooManyAttempts(string $key): bool;

    public function recordFailure(string $key): void;

    public function clear(string $key): void;
}
