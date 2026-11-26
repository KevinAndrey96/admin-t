<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\Http;

final readonly class HttpRequest
{
    /**
     * @param non-empty-string $method
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public ?string $body = null,
    ) {}
}
