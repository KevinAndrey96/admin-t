<?php

declare(strict_types=1);

namespace WooPanel\Http;

final readonly class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public string $body = '',
        public array $headers = [],
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** 303 after a form post, 301 for moved legacy URLs. */
    public static function redirect(string $location, int $status = 303): self
    {
        return new self($status, '', ['Location' => $location]);
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self($this->status, $this->body, [...$headers, ...$this->headers]);
    }

    public function send(bool $withBody): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        if ($withBody) {
            echo $this->body;
        }
    }
}
