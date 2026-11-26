<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Http\LoginThrottle;

final class MemoryThrottle implements LoginThrottle
{
    /** @var array<string, int> */
    public array $failures = [];

    public function __construct(private readonly int $maxAttempts = 3) {}

    public function tooManyAttempts(string $key): bool
    {
        return ($this->failures[$key] ?? 0) >= $this->maxAttempts;
    }

    public function recordFailure(string $key): void
    {
        $this->failures[$key] = ($this->failures[$key] ?? 0) + 1;
    }

    public function clear(string $key): void
    {
        unset($this->failures[$key]);
    }
}
