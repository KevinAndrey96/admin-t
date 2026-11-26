<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\Http;

final readonly class CurlHttpClient implements HttpClient
{
    public function __construct(
        private int $timeoutSeconds = 15,
        private int $connectTimeoutSeconds = 5,
    ) {}

    public function send(HttpRequest $request): HttpResponse
    {
        $handle = curl_init($request->url);
        $headers = [];
        $headerLines = [];
        foreach ($request->headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            \CURLOPT_CUSTOMREQUEST => $request->method,
            \CURLOPT_HTTPHEADER => $headerLines,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS_STR => 'http,https',
            \CURLOPT_TIMEOUT => $this->timeoutSeconds,
            \CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            \CURLOPT_HEADERFUNCTION => static function (\CurlHandle $handle, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (\count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return \strlen($line);
            },
        ]);
        if ($request->body !== null) {
            curl_setopt($handle, \CURLOPT_POSTFIELDS, $request->body);
        }

        $body = curl_exec($handle);
        if (!\is_string($body)) {
            throw new TransportFailure(\sprintf('%s %s: %s', $request->method, self::redact($request->url), curl_error($handle)));
        }

        /** @var array<string, string> $headers */
        return new HttpResponse(curl_getinfo($handle, \CURLINFO_RESPONSE_CODE), $headers, $body);
    }

    private static function redact(string $url): string
    {
        return (string) strtok($url, '?');
    }
}
