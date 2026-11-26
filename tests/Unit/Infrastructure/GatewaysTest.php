<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\OrderQuery;
use WooPanel\Application\ProductQuery;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\ProductDraft;
use WooPanel\Domain\ProductStatus;
use WooPanel\Domain\StockStatus;
use WooPanel\Infrastructure\WooCommerce\Mapper;
use WooPanel\Infrastructure\WooCommerce\RestClient;
use WooPanel\Infrastructure\WooCommerce\WooOrderGateway;
use WooPanel\Infrastructure\WooCommerce\WooProductGateway;
use WooPanel\Infrastructure\WooCommerce\WooReportGateway;
use WooPanel\Infrastructure\WooCommerce\WooStoreConnector;
use WooPanel\Tests\Support\FakeHttpClient;
use WooPanel\Tests\Support\Fixtures;

final class GatewaysTest extends TestCase
{
    private FakeHttpClient $http;
    private RestClient $client;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $this->client = new RestClient($this->http, 'https://tienda.example', new Credentials('gestora', 'clave'), []);
    }

    public function testMapsAnOrder(): void
    {
        $order = Mapper::order(Fixtures::orderPayload());

        self::assertSame(OrderStatus::Processing, $order->status);
        self::assertSame('2026-11-20T15:15:00+00:00', $order->createdAt->format('c'));
        self::assertSame('$ 112.000 COP', $order->total->format());
        self::assertSame('Laura Gómez', $order->customerName());
        self::assertSame('Dejar en portería & avisar', $order->customerNote);
        self::assertSame('Taza de cerámica', $order->items[0]->name);
        self::assertSame(3, $order->itemCount());
    }

    public function testShowsUnknownPluginStatusesAsOnHold(): void
    {
        self::assertSame(OrderStatus::OnHold, Mapper::order(Fixtures::orderPayload(1, 'wc-shipped'))->status);
    }

    public function testMapsAProductAndDecodesItsText(): void
    {
        $product = Mapper::product(Fixtures::productPayload());

        self::assertSame('Bolso de lona – estampado', $product->name);
        self::assertSame('Lona gruesa, hecho a mano.', $product->shortDescription);
        self::assertSame(12, $product->stockQuantity);
        self::assertSame('https://tienda.example/wp-content/uploads/bolso.jpg', $product->imageUrl);
        self::assertSame(StockStatus::InStock, $product->stockStatus);
    }

    public function testListsOrdersWithFiltersAndTotals(): void
    {
        $this->http->queue(FakeHttpClient::json([Fixtures::orderPayload()], 200, ['X-WP-Total' => '21', 'X-WP-TotalPages' => '2']));

        $page = (new WooOrderGateway($this->client))->list(new OrderQuery(2, OrderStatus::OnHold, 'laura'));

        self::assertSame(['page' => '2', 'per_page' => '20', 'orderby' => 'date', 'order' => 'desc', 'status' => 'on-hold', 'search' => 'laura'], $this->http->lastQuery());
        self::assertSame([21, 2, 2], [$page->total, $page->totalPages, $page->page]);
        self::assertCount(1, $page->items);
    }

    public function testMissingOrderIsNull(): void
    {
        $this->http->queue(FakeHttpClient::json(['code' => 'woocommerce_rest_shop_order_invalid_id'], 404));

        self::assertNull((new WooOrderGateway($this->client))->find(404));
    }

    public function testTrashedProductsAreTreatedAsMissing(): void
    {
        $this->http->queue(FakeHttpClient::json(['status' => 'trash'] + Fixtures::productPayload()));

        self::assertNull((new WooProductGateway($this->client))->find(77));
    }

    public function testAddsCustomerNotes(): void
    {
        $this->http->queue(FakeHttpClient::json(['id' => 3], 201));

        (new WooOrderGateway($this->client))->addNote(1042, 'Ya salió tu pedido', true);

        self::assertStringEndsWith('/wc/v3/orders/1042/notes', $this->http->last()->url);
        self::assertSame('{"note":"Ya salió tu pedido","customer_note":true}', $this->http->last()->body);
    }

    public function testCreatesProductsWithTheirImage(): void
    {
        $this->http->queue(FakeHttpClient::json(Fixtures::productPayload(), 201));

        (new WooProductGateway($this->client))->create(new ProductDraft('Bolso', '', '59000', '', ProductStatus::Publish), 31);

        $body = json_decode((string) $this->http->last()->body, true);
        self::assertIsArray($body);
        self::assertSame([['id' => 31]], $body['images']);
        self::assertSame('POST', $this->http->last()->method);
    }

    public function testListsAllProductStatuses(): void
    {
        $this->http->queue(FakeHttpClient::json([Fixtures::productPayload()]));

        (new WooProductGateway($this->client))->list(new ProductQuery(1, 'bolso', StockStatus::OutOfStock));

        self::assertSame('any', $this->http->lastQuery()['status']);
        self::assertSame('outofstock', $this->http->lastQuery()['stock_status']);
    }

    public function testTrashKeepsTheProductRecoverable(): void
    {
        $this->http->queue(FakeHttpClient::json(Fixtures::productPayload()));

        (new WooProductGateway($this->client))->trash(77);

        self::assertSame('DELETE', $this->http->last()->method);
        self::assertSame([], $this->http->lastQuery());
    }

    public function testBuildsTheDashboardSummary(): void
    {
        $this->http
            ->route('GET', 'wc/v3/reports/sales', FakeHttpClient::json([['total_sales' => '1250000.00', 'total_orders' => 18]]))
            ->route('GET', 'wc/v3/reports/orders/totals', FakeHttpClient::json([['slug' => 'processing', 'total' => 3], ['slug' => 'on-hold', 'total' => 2]]))
            ->route('GET', 'wc/v3/products', FakeHttpClient::json([Fixtures::productPayload()], 200, ['X-WP-Total' => '4']))
            ->route('GET', 'wc/v3/orders', FakeHttpClient::json([Fixtures::orderPayload()]))
            ->route('GET', 'wc/v3/settings/general/woocommerce_currency', FakeHttpClient::json(['id' => 'woocommerce_currency', 'value' => 'COP']));

        $summary = (new WooReportGateway($this->client))->summary();

        self::assertSame('$ 1.250.000 COP', $summary->salesThisMonth->format());
        self::assertSame(18, $summary->ordersThisMonth);
        self::assertSame(5, $summary->ordersToAttend());
        self::assertSame(4, $summary->outOfStockProducts);
        self::assertCount(1, $summary->recentOrders);
    }

    public function testAuthenticatesShopManagers(): void
    {
        $this->http->queue(FakeHttpClient::json(['id' => 7, 'name' => 'Marta &amp; Co', 'capabilities' => ['manage_woocommerce' => true]]));

        $user = (new WooStoreConnector($this->http, 'https://tienda.example', []))->authenticate(new Credentials('marta', 'clave'));

        self::assertSame([7, 'Marta & Co'], [$user->id, $user->displayName]);
        self::assertSame(['context' => 'edit'], $this->http->lastQuery());
    }

    public function testRejectsUsersWithoutTheShopCapability(): void
    {
        $this->http->queue(FakeHttpClient::json(['id' => 8, 'name' => 'Cliente', 'capabilities' => ['read' => true]]));

        $this->expectException(MissingCapability::class);
        (new WooStoreConnector($this->http, 'https://tienda.example', []))->authenticate(new Credentials('cliente', 'clave'));
    }
}
