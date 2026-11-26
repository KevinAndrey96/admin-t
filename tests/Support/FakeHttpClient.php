<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Infrastructure\Http\HttpClient;
use WooPanel\Infrastructure\Http\HttpRequest;
use WooPanel\Infrastructure\Http\HttpResponse;
use WooPanel\Infrastructure\Http\TransportFailure;

/**
 * Answers from a queue (in order) or from routes ("GET wc/v3/orders" => response), and records every request.
 */
final class FakeHttpClient implements HttpClient
{
    /** @var list<HttpRequest> */
    public array $requests = [];

    /** @var list<HttpResponse|TransportFailure> */
    private array $queue = [];

    /** @var array<string, HttpResponse> */
    private array $routes = [];

    public function queue(HttpResponse|TransportFailure ...$answers): self
    {
        array_push($this->queue, ...$answers);

        return $this;
    }

    public function route(string $method, string $route, HttpResponse $response): self
    {
        $this->routes[$method . ' ' . $route] = $response;

        return $this;
    }

    /**
     * @param array<array-key, mixed>|list<mixed> $data
     * @param array<string, string> $headers
     */
    public static function json(array $data, int $status = 200, array $headers = []): HttpResponse
    {
        return new HttpResponse($status, $headers, json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;

        if ($this->queue !== []) {
            $answer = array_shift($this->queue);
            if ($answer instanceof TransportFailure) {
                throw $answer;
            }

            return $answer;
        }

        $path = (string) parse_url($request->url, \PHP_URL_PATH);
        $route = substr($path, (int) strpos($path, '/wp-json/') + \strlen('/wp-json/'));

        return $this->routes[$request->method . ' ' . $route]
            ?? throw new \LogicException(\sprintf('no fake answer for %s %s', $request->method, $route));
    }

    public function last(): HttpRequest
    {
        return $this->requests[array_key_last($this->requests) ?? throw new \LogicException('no requests were sent')];
    }

    /**
     * @return array<string, string>
     */
    public function lastQuery(): array
    {
        parse_str((string) parse_url($this->last()->url, \PHP_URL_QUERY), $query);

        /** @var array<string, string> $query */
        return $query;
    }
}
