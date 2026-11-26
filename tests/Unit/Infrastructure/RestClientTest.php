<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreError;
use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Domain\Credentials;
use WooPanel\Infrastructure\Http\HttpResponse;
use WooPanel\Infrastructure\Http\TransportFailure;
use WooPanel\Infrastructure\WooCommerce\RestClient;
use WooPanel\Tests\Support\FakeHttpClient;

final class RestClientTest extends TestCase
{
    private FakeHttpClient $http;
    private RestClient $client;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $this->client = new RestClient($this->http, 'https://tienda.example/', new Credentials('gestora', 'abcd efgh'), [0, 0]);
    }

    public function testSendsBasicAuthAndBuildsTheUrl(): void
    {
        $this->http->queue(FakeHttpClient::json([['id' => 1]], 200, ['X-WP-Total' => '41', 'X-WP-TotalPages' => '3']));

        $response = $this->client->get('wc/v3/orders', ['page' => 2, 'search' => 'laura gómez']);

        $request = $this->http->last();
        self::assertSame('GET', $request->method);
        self::assertSame('https://tienda.example/wp-json/wc/v3/orders?page=2&search=laura%20g%C3%B3mez', $request->url);
        self::assertSame('Basic ' . base64_encode('gestora:abcdefgh'), $request->headers['Authorization']);
        self::assertArrayNotHasKey('Content-Type', $request->headers);
        self::assertSame([41, 3], [$response->total, $response->totalPages]);
        self::assertSame([['id' => 1]], $response->objects());
    }

    public function testSendsJsonBodies(): void
    {
        $this->http->queue(FakeHttpClient::json(['id' => 5]));

        $this->client->put('wc/v3/orders/5', ['status' => 'completed']);

        self::assertSame('application/json', $this->http->last()->headers['Content-Type']);
        self::assertSame('{"status":"completed"}', $this->http->last()->body);
    }

    public function testRetriesIdempotentCallsOnTransientErrors(): void
    {
        $this->http->queue(
            new TransportFailure('connection reset'),
            new HttpResponse(503),
            FakeHttpClient::json(['ok' => true]),
        );

        $response = $this->client->get('wc/v3/orders');

        self::assertCount(3, $this->http->requests);
        self::assertSame(['ok' => true], $response->object());
    }

    public function testGivesUpAfterTheLastRetry(): void
    {
        $this->http->queue(new HttpResponse(502), new HttpResponse(502), new HttpResponse(502));

        try {
            $this->client->delete('wc/v3/products/9');
            self::fail('expected StoreUnavailable');
        } catch (StoreUnavailable $e) {
            self::assertSame('DELETE wc/v3/products/9: status 502', $e->getMessage());
            self::assertCount(3, $this->http->requests);
        }
    }

    public function testNeverRetriesPosts(): void
    {
        $this->http->queue(new HttpResponse(503), FakeHttpClient::json(['id' => 1]));

        try {
            $this->client->post('wc/v3/orders/5/notes', ['note' => 'hola']);
            self::fail('expected StoreUnavailable');
        } catch (StoreUnavailable) {
            self::assertCount(1, $this->http->requests);
        }
    }

    public function testUploadsSendTheRawFile(): void
    {
        $this->http->queue(FakeHttpClient::json(['id' => 31], 201));

        $this->client->upload('wp/v2/media', 'binary', 'image/png', 'bolso.png');

        $request = $this->http->last();
        self::assertSame('binary', $request->body);
        self::assertSame('image/png', $request->headers['Content-Type']);
        self::assertSame('attachment; filename="bolso.png"', $request->headers['Content-Disposition']);
    }

    /**
     * @return iterable<string, array{HttpResponse, class-string<StoreError>}>
     */
    public static function failures(): iterable
    {
        yield 'wrong password' => [FakeHttpClient::json(['code' => 'incorrect_password', 'message' => 'x'], 401), InvalidCredentials::class];
        yield 'shop manager role missing' => [FakeHttpClient::json(['code' => 'woocommerce_rest_cannot_view'], 403), MissingCapability::class];
        yield 'validation error' => [FakeHttpClient::json(['code' => 'woocommerce_rest_product_invalid_id', 'message' => 'ID no válido.'], 400), StoreRejected::class];
        yield 'not found' => [FakeHttpClient::json(['code' => 'rest_no_route'], 404), StoreRejected::class];
        yield 'redirect to https' => [new HttpResponse(301, ['Location' => 'https://otra.example']), StoreUnavailable::class];
        yield 'html instead of json' => [new HttpResponse(200, [], '<html>mantenimiento</html>'), StoreUnavailable::class];
    }

    /**
     * @param class-string<StoreError> $expected
     */
    #[DataProvider('failures')]
    public function testMapsStoreAnswersToErrors(HttpResponse $answer, string $expected): void
    {
        $this->http->queue($answer);

        $this->expectException($expected);
        $this->client->get('wc/v3/products/1');
    }

    public function testRejectionsCarryTheStoreMessage(): void
    {
        $this->http->queue(FakeHttpClient::json(['code' => 'product_invalid_sku', 'message' => 'El SKU <strong>ya existe</strong> &amp; está en uso.'], 400));

        try {
            $this->client->post('wc/v3/products', []);
            self::fail('expected StoreRejected');
        } catch (StoreRejected $e) {
            self::assertSame('El SKU ya existe & está en uso.', $e->getMessage());
            self::assertSame([400, 'product_invalid_sku'], [$e->status, $e->errorCode]);
        }
    }
}
