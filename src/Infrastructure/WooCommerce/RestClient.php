<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Domain\Credentials;
use WooPanel\Infrastructure\Http\HttpClient;
use WooPanel\Infrastructure\Http\HttpRequest;
use WooPanel\Infrastructure\Http\HttpResponse;
use WooPanel\Infrastructure\Http\TransportFailure;

/**
 * JSON client for the WordPress REST API authenticated with an application password.
 *
 * Idempotent calls (GET, PUT, DELETE) are retried with backoff on network errors, 429 and 5xx answers.
 * POST is never retried, so an order note or a product is never created twice.
 */
final readonly class RestClient
{
    private const array RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    /**
     * @param list<int> $retryDelaysMs pause before each retry; its length is the number of retries
     */
    public function __construct(
        private HttpClient $http,
        private string $siteUrl,
        private Credentials $credentials,
        private array $retryDelaysMs = [250, 750],
    ) {}

    /**
     * @param array<string, scalar> $query
     */
    public function get(string $route, array $query = []): RestResponse
    {
        return $this->call('GET', $route, $query, null);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function post(string $route, array $body): RestResponse
    {
        return $this->call('POST', $route, [], self::encode($body));
    }

    /**
     * @param array<string, mixed> $body
     */
    public function put(string $route, array $body): RestResponse
    {
        return $this->call('PUT', $route, [], self::encode($body));
    }

    /**
     * @param array<string, scalar> $query
     */
    public function delete(string $route, array $query = []): RestResponse
    {
        return $this->call('DELETE', $route, $query, null);
    }

    /** Sends a raw file body, as the WordPress media endpoint expects. */
    public function upload(string $route, string $contents, string $mimeType, string $filename): RestResponse
    {
        return $this->call('POST', $route, [], $contents, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => \sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * @param 'GET'|'POST'|'PUT'|'DELETE' $method
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     */
    private function call(string $method, string $route, array $query, ?string $body, array $headers = ['Content-Type' => 'application/json']): RestResponse
    {
        $request = new HttpRequest(
            $method,
            $this->url($route, $query),
            [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->credentials->username . ':' . $this->credentials->applicationPassword),
                'User-Agent' => 'php-woocommerce-admin-panel',
                ...($body === null ? [] : $headers),
            ],
            $body,
        );

        $retries = $method === 'POST' ? [] : $this->retryDelaysMs;
        $attempt = 0;
        while (true) {
            try {
                $response = $this->http->send($request);
            } catch (TransportFailure $e) {
                if ($attempt >= \count($retries)) {
                    throw new StoreUnavailable(\sprintf('%s %s: %s', $method, $route, $e->getMessage()), 0, $e);
                }
                $this->pause($retries[$attempt++]);

                continue;
            }

            if (\in_array($response->status, self::RETRYABLE_STATUSES, true) && $attempt < \count($retries)) {
                $this->pause($retries[$attempt++]);

                continue;
            }

            return $this->interpret($method, $route, $response);
        }
    }

    private function interpret(string $method, string $route, HttpResponse $response): RestResponse
    {
        $data = json_decode($response->body, true);

        if ($response->status >= 200 && $response->status < 300) {
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new StoreUnavailable(\sprintf('%s %s: answer is not json', $method, $route));
            }

            return new RestResponse($data, self::intHeader($response, 'X-WP-Total'), self::intHeader($response, 'X-WP-TotalPages'));
        }

        $error = \is_array($data) ? $data : [];
        $code = \is_string($error['code'] ?? null) ? $error['code'] : '';
        $message = \is_string($error['message'] ?? null) ? html_entity_decode(strip_tags($error['message']), \ENT_QUOTES | \ENT_HTML5, 'UTF-8') : '';

        throw match (true) {
            $response->status === 401 => new InvalidCredentials(\sprintf('%s %s: %s', $method, $route, $code !== '' ? $code : 'unauthorized')),
            $response->status === 403 => new MissingCapability(\sprintf('%s %s: %s', $method, $route, $code !== '' ? $code : 'forbidden')),
            $response->status >= 300 && $response->status < 400 => new StoreUnavailable(\sprintf('%s %s: redirected to %s, check WOOCOMMERCE_URL', $method, $route, $response->header('Location') ?? 'unknown')),
            $response->status >= 500 || $response->status === 429 || $response->status < 200 => new StoreUnavailable(\sprintf('%s %s: status %d', $method, $route, $response->status)),
            default => new StoreRejected($message !== '' ? $message : 'La tienda rechazó la solicitud.', $response->status, $code),
        };
    }

    /**
     * @param array<string, scalar> $query
     */
    private function url(string $route, array $query): string
    {
        $url = rtrim($this->siteUrl, '/') . '/wp-json/' . ltrim($route, '/');

        return $query === [] ? $url : $url . '?' . http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
    }

    private function pause(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }

    private static function intHeader(HttpResponse $response, string $name): ?int
    {
        $value = $response->header($name);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function encode(array $body): string
    {
        return json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
    }
}
