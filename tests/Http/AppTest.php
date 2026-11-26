<?php

declare(strict_types=1);

namespace WooPanel\Tests\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductStatus;
use WooPanel\Domain\StockStatus;
use WooPanel\Domain\StoreUser;
use WooPanel\Http\App;
use WooPanel\Http\AppConfig;
use WooPanel\Http\Request;
use WooPanel\Http\Response;
use WooPanel\Http\SessionState;
use WooPanel\Infrastructure\Security\CredentialVault;
use WooPanel\Tests\Support\ArraySession;
use WooPanel\Tests\Support\FakeConnector;
use WooPanel\Tests\Support\Fixtures;
use WooPanel\Tests\Support\MemoryThrottle;
use WooPanel\Tests\Support\RecordingReporter;
use WooPanel\View\View;

final class AppTest extends TestCase
{
    private FakeConnector $connector;
    private CredentialVault $vault;
    private MemoryThrottle $throttle;
    private RecordingReporter $reporter;
    private ArraySession $session;
    private App $app;

    protected function setUp(): void
    {
        $root = \dirname(__DIR__, 2);
        $config = AppConfig::fromEnvironment([
            'WOOCOMMERCE_URL' => 'http://wordpress',
            'WOOCOMMERCE_PUBLIC_URL' => 'https://tienda.example',
            'STORE_NAME' => 'Tienda de prueba',
            'APP_KEY' => CredentialVault::generateKey(),
        ]);
        $this->connector = new FakeConnector();
        $this->vault = CredentialVault::fromEncodedKey($config->appKey);
        $this->throttle = new MemoryThrottle();
        $this->reporter = new RecordingReporter();
        $this->session = new ArraySession();
        $this->app = new App(
            $this->connector,
            $this->vault,
            $this->throttle,
            new View($root . '/templates', $root . '/public', $config->timezone),
            $config,
            $this->reporter,
        );

        $this->connector->memory->orders->items[1042] = Fixtures::order(1042);
        $this->connector->memory->products->items[77] = Fixtures::product(77);
    }

    public function testGuestsAreSentToTheLoginPage(): void
    {
        $response = $this->get('/pedidos');

        self::assertSame(303, $response->status);
        self::assertSame('/ingresar', $response->headers['Location']);
    }

    public function testLoginPageHasACsrfToken(): void
    {
        $response = $this->get('/ingresar');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('name="_token" value="' . $this->token() . '"', $response->body);
        self::assertStringContainsString('contraseña de aplicación', $response->body);
    }

    public function testSignsInAShopManager(): void
    {
        $this->get('/ingresar');

        $response = $this->post('/ingresar', ['usuario' => FakeConnector::USERNAME, 'clave_aplicacion' => FakeConnector::PASSWORD]);

        self::assertSame(303, $response->status);
        self::assertSame('/', $response->headers['Location']);
        self::assertSame(1, $this->session->regenerations);
        $auth = (new SessionState($this->session))->auth();
        self::assertNotNull($auth);
        self::assertSame('Marta Gestora', $auth->user->displayName);
        self::assertStringNotContainsString('abcd', $auth->sealedCredentials);
    }

    public function testWrongPasswordsAreThrottled(): void
    {
        $this->get('/ingresar');

        for ($i = 0; $i < 3; ++$i) {
            $response = $this->post('/ingresar', ['usuario' => 'gestora', 'clave_aplicacion' => 'equivocada']);
            self::assertSame(422, $response->status);
            self::assertStringContainsString('incorrectos', $response->body);
        }
        $blocked = $this->post('/ingresar', ['usuario' => FakeConnector::USERNAME, 'clave_aplicacion' => FakeConnector::PASSWORD]);

        self::assertSame(429, $blocked->status);
        self::assertNull((new SessionState($this->session))->auth());
    }

    public function testLoginWithoutTheShopRoleIsForbidden(): void
    {
        $this->get('/ingresar');
        $this->connector->authFailure = new MissingCapability('lacks manage_woocommerce');

        $response = $this->post('/ingresar', ['usuario' => 'cliente', 'clave_aplicacion' => 'clave']);

        self::assertSame(403, $response->status);
        self::assertStringContainsString('no tiene permiso', $response->body);
    }

    public function testLoginWithoutCsrfTokenIsRefused(): void
    {
        $this->get('/ingresar');

        $response = $this->app->handle(new Request('POST', '/ingresar', [], ['usuario' => FakeConnector::USERNAME, 'clave_aplicacion' => FakeConnector::PASSWORD]), $this->session);

        self::assertSame(303, $response->status);
        self::assertNull((new SessionState($this->session))->auth());
    }

    public function testDashboardShowsTheMonth(): void
    {
        $this->signIn();

        $response = $this->get('/');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('$ 1.250.000 COP', $response->body);
        self::assertStringContainsString('Marta Gestora', $response->body);
        self::assertStringContainsString('#1042', $response->body);
        self::assertStringContainsString('aria-current="page"', $response->body);
    }

    public function testListsAndFiltersOrders(): void
    {
        $this->signIn();

        $response = $this->get('/pedidos', ['estado' => 'processing', 'buscar' => 'laura', 'pagina' => '2']);

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Laura Gómez', $response->body);
        $query = $this->connector->memory->orders->lastQuery;
        self::assertNotNull($query);
        self::assertSame([OrderStatus::Processing, 'laura', 2], [$query->status, $query->search, $query->page]);
    }

    public function testShowsAnOrder(): void
    {
        $this->signIn();

        $response = $this->get('/pedidos/1042');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Pedido #1042', $response->body);
        self::assertStringContainsString('Calle 45 # 12-30', $response->body);
        self::assertStringContainsString('Pedido recibido', $response->body);
    }

    public function testUnknownOrderIs404(): void
    {
        $this->signIn();

        self::assertSame(404, $this->get('/pedidos/999')->status);
    }

    public function testChangesTheOrderStatus(): void
    {
        $this->signIn();

        $response = $this->post('/pedidos/1042/estado', ['estado' => 'completed']);

        self::assertSame('/pedidos/1042', $response->headers['Location']);
        self::assertSame(OrderStatus::Completed, $this->connector->memory->orders->items[1042]->status);
        self::assertStringContainsString('Completado', $this->get('/pedidos/1042')->body);
    }

    public function testRefusesDraftStatus(): void
    {
        $this->signIn();

        $this->post('/pedidos/1042/estado', ['estado' => 'checkout-draft']);

        self::assertSame(OrderStatus::Processing, $this->connector->memory->orders->items[1042]->status);
    }

    public function testAddsCustomerNotes(): void
    {
        $this->signIn();

        $response = $this->post('/pedidos/1042/notas', ['nota' => '  Tu pedido salió hoy  ', 'para_cliente' => '1']);

        self::assertSame('/pedidos/1042#notas', $response->headers['Location']);
        self::assertSame([[1042, 'Tu pedido salió hoy', true]], $this->connector->memory->orders->notesAdded);
    }

    public function testFormsWithoutCsrfTokenAreRefused(): void
    {
        $this->signIn();

        $response = $this->app->handle(new Request('POST', '/pedidos/1042/estado', [], ['estado' => 'cancelled', '_token' => 'falso']), $this->session);

        self::assertSame(403, $response->status);
        self::assertSame(OrderStatus::Processing, $this->connector->memory->orders->items[1042]->status);
    }

    public function testInvalidProductFormIsShownAgainWithErrors(): void
    {
        $this->signIn();

        $response = $this->post('/productos', ['name' => 'Taza', 'regular_price' => 'gratis', 'status' => 'publish']);

        self::assertSame(422, $response->status);
        self::assertStringContainsString('value="Taza"', $response->body);
        self::assertStringContainsString('aria-invalid="true"', $response->body);
        self::assertSame([], $this->connector->memory->products->saved);
    }

    public function testCreatesAProduct(): void
    {
        $this->signIn();

        $response = $this->post('/productos', ['name' => 'Taza', 'regular_price' => '35000', 'status' => 'draft']);

        self::assertSame('/productos/900', $response->headers['Location']);
        self::assertSame('Taza', $this->connector->memory->products->saved[0][1]->name);
    }

    public function testStoreValidationMessagesReachTheForm(): void
    {
        $this->signIn();
        $this->connector->memory->products->saveFailure = new StoreRejected('El SKU ya existe.', 400, 'product_invalid_sku');

        $response = $this->post('/productos/77', ['name' => 'Bolso', 'regular_price' => '59000', 'status' => 'publish']);

        self::assertSame(422, $response->status);
        self::assertStringContainsString('El SKU ya existe.', $response->body);
        self::assertStringContainsString('value="Bolso"', $response->body);
    }

    public function testTrashesAProduct(): void
    {
        $this->signIn();

        $response = $this->post('/productos/77/eliminar');

        self::assertSame('/productos', $response->headers['Location']);
        self::assertSame([77], $this->connector->memory->products->trashed);
    }

    public function testRevokedPasswordSignsTheUserOut(): void
    {
        $this->signIn();
        $this->connector->memory->failure = new InvalidCredentials('application password revoked');

        $response = $this->get('/pedidos');

        self::assertSame('/ingresar', $response->headers['Location']);
        self::assertNull((new SessionState($this->session))->auth());
    }

    public function testStoreOutageShowsA503AndIsReported(): void
    {
        $this->signIn();
        $this->connector->memory->failure = new StoreUnavailable('GET wc/v3/orders: status 502');

        $response = $this->get('/pedidos');

        self::assertSame(503, $response->status);
        self::assertCount(1, $this->reporter->errors);
    }

    public function testIdleSessionsExpire(): void
    {
        $this->signIn();
        $auth = $this->session->data['auth'];
        self::assertIsArray($auth);
        $auth['seen'] = time() - 3600;
        $this->session->data['auth'] = $auth;

        $response = $this->get('/');

        self::assertSame('/ingresar', $response->headers['Location']);
        self::assertNull((new SessionState($this->session))->auth());
    }

    public function testLogsOut(): void
    {
        $this->signIn();

        $response = $this->post('/salir');

        self::assertSame('/ingresar', $response->headers['Location']);
        self::assertNull((new SessionState($this->session))->auth());
    }

    /**
     * @return iterable<string, array{string, string, int, string}>
     */
    public static function plainRoutes(): iterable
    {
        yield 'legacy dashboard' => ['GET', '/dashboard.php', 301, '/'];
        yield 'legacy orders' => ['GET', '/transactions.php', 301, '/pedidos'];
        yield 'trailing slash' => ['GET', '/pedidos/', 301, '/pedidos'];
        yield 'health check' => ['GET', '/healthz', 200, ''];
    }

    #[DataProvider('plainRoutes')]
    public function testPlainRoutes(string $method, string $path, int $status, string $location): void
    {
        $response = $this->app->handle(new Request($method, $path), $this->session);

        self::assertSame($status, $response->status);
        self::assertSame($location, $response->headers['Location'] ?? '');
    }

    public function testWrongMethodIs405(): void
    {
        $this->signIn();

        $response = $this->app->handle(new Request('DELETE', '/productos/77'), $this->session);

        self::assertSame(405, $response->status);
        self::assertSame('GET, POST', $response->headers['Allow']);
    }

    public function testSendsSecurityHeaders(): void
    {
        $response = $this->app->handle(new Request('GET', '/ingresar', secure: true), $this->session);

        self::assertStringContainsString("script-src 'self'", $response->headers['Content-Security-Policy']);
        self::assertStringContainsString('https://tienda.example', $response->headers['Content-Security-Policy']);
        self::assertSame('no-store', $response->headers['Cache-Control']);
        self::assertSame('DENY', $response->headers['X-Frame-Options']);
        self::assertArrayHasKey('Strict-Transport-Security', $response->headers);
    }

    public function testEscapesStoreData(): void
    {
        $this->signIn();
        $this->connector->memory->products->items[78] = new Product(78, '<script>alert(1)</script>', '', ProductStatus::Draft, '', '', false, null, StockStatus::InStock, '', null, '');

        $body = $this->get('/productos')->body;

        self::assertStringNotContainsString('<script>alert(1)</script>', $body);
        self::assertStringContainsString('&lt;script&gt;', $body);
    }

    private function signIn(): void
    {
        (new SessionState($this->session))->signIn(
            new StoreUser(7, 'Marta Gestora'),
            $this->vault->seal(new Credentials(FakeConnector::USERNAME, FakeConnector::PASSWORD)),
        );
    }

    private function token(): string
    {
        return (new SessionState($this->session))->csrfToken();
    }

    /**
     * @param array<string, string> $query
     */
    private function get(string $path, array $query = []): Response
    {
        return $this->app->handle(new Request('GET', $path, $query), $this->session);
    }

    /**
     * @param array<string, string> $body
     */
    private function post(string $path, array $body = []): Response
    {
        return $this->app->handle(new Request('POST', $path, [], ['_token' => $this->token(), ...$body], [], '203.0.113.9'), $this->session);
    }
}
