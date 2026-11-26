<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\Http;

interface HttpClient
{
    /**
     * Sends the request and returns any HTTP answer, including 4xx and 5xx.
     *
     * @throws TransportFailure when no HTTP answer arrived (DNS, TLS, timeout, connection reset)
     */
    public function send(HttpRequest $request): HttpResponse;
}
