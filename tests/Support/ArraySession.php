<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Http\Session;

final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    public array $data = [];

    public int $regenerations = 0;

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        ++$this->regenerations;
    }

    public function clear(): void
    {
        $this->data = [];
        ++$this->regenerations;
    }
}
