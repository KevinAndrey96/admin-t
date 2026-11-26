<?php

declare(strict_types=1);

namespace WooPanel\Http;

use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Application\Port\StoreConnector;
use WooPanel\Application\SaveProduct;
use WooPanel\Application\Store;
use WooPanel\Http\Controller\AuthController;
use WooPanel\Http\Controller\DashboardController;
use WooPanel\Http\Controller\OrderController;
use WooPanel\Http\Controller\ProductController;
use WooPanel\Infrastructure\Security\CredentialVault;
use WooPanel\View\View;

/**
 * Front controller: routing, sign-in guard, CSRF, error pages and security headers.
 */
final readonly class App
{
    private const array LEGACY_REDIRECTS = [
        '/index.php' => '/ingresar',
        '/dashboard.php' => '/',
        '/transactions.php' => '/pedidos',
        '/Logout.php' => '/ingresar',
    ];

    public function __construct(
        private StoreConnector $connector,
        private CredentialVault $vault,
        private LoginThrottle $throttle,
        private View $view,
        private AppConfig $config,
        private ErrorReporter $reporter,
    ) {}

    public function handle(Request $request, Session $session): Response
    {
        $state = new SessionState($session);

        try {
            $response = $this->route($request, $state);
        } catch (\Throwable $e) {
            $this->reporter->report($e, $request);
            $response = $this->responder($state, '')->error(500, 'Algo salió mal', 'Ocurrió un error inesperado. Ya quedó registrado; intenta de nuevo en unos minutos.');
        }

        return $response->withHeaders($this->securityHeaders($request));
    }

    private function route(Request $request, SessionState $state): Response
    {
        $path = $request->path;
        if ($path !== '/' && str_ends_with($path, '/')) {
            return Response::redirect(rtrim($path, '/'), 301);
        }
        if (isset(self::LEGACY_REDIRECTS[$path])) {
            return Response::redirect(self::LEGACY_REDIRECTS[$path], 301);
        }
        if ($path === '/healthz') {
            return Response::text('ok');
        }

        if ($path === '/ingresar') {
            return $this->login($request, $state);
        }

        $auth = $state->auth();
        if ($auth === null) {
            return Response::redirect('/ingresar');
        }

        $credentials = $this->vault->open($auth->sealedCredentials);
        if ($credentials === null || $auth->isExpired(time(), $this->config->idleTimeoutSeconds, $this->config->absoluteTimeoutSeconds)) {
            $state->signOut();
            $state->flash('info', 'Tu sesión expiró. Ingresa de nuevo para continuar.');

            return Response::redirect('/ingresar');
        }
        $state->touch();

        $respond = $this->responder($state, self::section($path), $auth);
        if ($request->isPost() && !$state->isValidCsrf($request->input('_token'))) {
            return $respond->error(403, 'Formulario vencido', 'El formulario expiró por seguridad. Vuelve a la página anterior, recárgala e inténtalo de nuevo.');
        }

        try {
            return $this->dispatch($request, $state, $respond, $this->connector->connect($credentials));
        } catch (InvalidCredentials) {
            $state->signOut();
            $state->flash('error', 'WordPress rechazó tu contraseña de aplicación; puede que la hayan revocado. Ingresa de nuevo.');

            return Response::redirect('/ingresar');
        } catch (MissingCapability) {
            return $respond->error(403, 'Sin permiso', 'Tu usuario de WordPress no tiene permiso para esta acción en la tienda.');
        } catch (StoreUnavailable $e) {
            $this->reporter->report($e, $request);

            return $respond->error(503, 'La tienda no responde', 'No pudimos comunicarnos con WooCommerce. Revisa que la tienda esté en línea e intenta de nuevo.');
        }
    }

    private function login(Request $request, SessionState $state): Response
    {
        $respond = $this->responder($state, 'login');
        $controller = new AuthController($this->connector, $this->vault, $this->throttle, $state, $respond, $this->reporter);

        return match ($request->method) {
            'GET', 'HEAD' => $controller->showLogin(),
            'POST' => $state->isValidCsrf($request->input('_token'))
                ? $controller->login($request)
                : $respond->redirect('/ingresar', 'error', 'El formulario expiró por seguridad. Inténtalo de nuevo.'),
            default => self::methodNotAllowed(['GET', 'POST']),
        };
    }

    private function dispatch(Request $request, SessionState $state, Responder $respond, Store $store): Response
    {
        $orders = static fn(): OrderController => new OrderController($store->orders, $respond);
        $products = static fn(): ProductController => new ProductController($store->products, new SaveProduct($store->products, $store->media), $respond);

        /** @var list<array{string, string, \Closure(int): Response}> $routes */
        $routes = [
            ['GET', '#^/$#', static fn(): Response => (new DashboardController($store->reports, $respond))->show()],
            ['GET', '#^/pedidos$#', static fn(): Response => $orders()->index($request)],
            ['GET', '#^/pedidos/(\d{1,10})$#', static fn(int $id): Response => $orders()->show($id)],
            ['POST', '#^/pedidos/(\d{1,10})/estado$#', static fn(int $id): Response => $orders()->changeStatus($request, $id)],
            ['POST', '#^/pedidos/(\d{1,10})/notas$#', static fn(int $id): Response => $orders()->addNote($request, $id)],
            ['GET', '#^/productos$#', static fn(): Response => $products()->index($request)],
            ['POST', '#^/productos$#', static fn(): Response => $products()->store($request)],
            ['GET', '#^/productos/nuevo$#', static fn(): Response => $products()->create()],
            ['GET', '#^/productos/(\d{1,10})$#', static fn(int $id): Response => $products()->edit($id)],
            ['POST', '#^/productos/(\d{1,10})$#', static fn(int $id): Response => $products()->update($request, $id)],
            ['POST', '#^/productos/(\d{1,10})/eliminar$#', static fn(int $id): Response => $products()->trash($id)],
            ['POST', '#^/salir$#', fn(): Response => (new AuthController($this->connector, $this->vault, $this->throttle, $state, $respond, $this->reporter))->logout()],
        ];

        $method = $request->isHead() ? 'GET' : $request->method;
        $allowed = [];
        foreach ($routes as [$routeMethod, $pattern, $handler]) {
            if (preg_match($pattern, $request->path, $matches) !== 1) {
                continue;
            }
            if ($routeMethod === $method) {
                return $handler((int) ($matches[1] ?? 0));
            }
            $allowed[] = $routeMethod;
        }

        if ($allowed !== []) {
            return self::methodNotAllowed($allowed);
        }

        return $respond->error(404, 'Página no encontrada', 'La dirección que buscas no existe en el panel.');
    }

    private function responder(SessionState $state, string $section, ?AuthSession $auth = null): Responder
    {
        return new Responder($this->view, $state, $this->config, $section, $auth?->user);
    }

    private static function section(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/pedidos') => 'orders',
            str_starts_with($path, '/productos') => 'products',
            default => 'dashboard',
        };
    }

    /**
     * @param list<string> $allowed
     */
    private static function methodNotAllowed(array $allowed): Response
    {
        return new Response(405, 'Método no permitido', ['Allow' => implode(', ', array_unique($allowed)), 'Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * @return array<string, string>
     */
    private function securityHeaders(Request $request): array
    {
        $imageSources = trim("'self' data: blob: https: " . $this->config->storeOrigin());
        $headers = [
            'Content-Security-Policy' => "default-src 'self'; img-src {$imageSources}; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cache-Control' => 'no-store',
        ];
        if ($request->secure) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }
}
