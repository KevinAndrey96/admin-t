<?php

declare(strict_types=1);

namespace WooPanel\Http;

interface Session
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /** New session ID with the same data, to prevent session fixation on login. */
    public function regenerate(): void;

    public function clear(): void;
}
