<?php

declare(strict_types=1);

use WooPanel\Http\App;
use WooPanel\Http\AppConfig;
use WooPanel\Http\NativeSession;
use WooPanel\Http\Request;
use WooPanel\Infrastructure\Http\CurlHttpClient;
use WooPanel\Infrastructure\Security\CredentialVault;
use WooPanel\Infrastructure\Security\FileLoginThrottle;
use WooPanel\Infrastructure\StderrErrorReporter;
use WooPanel\Infrastructure\WooCommerce\WooStoreConnector;
use WooPanel\View\View;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url(is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

try {
    $config = AppConfig::fromEnvironment(getenv());
    $vault = CredentialVault::fromEncodedKey($config->appKey);
} catch (InvalidArgumentException $e) {
    error_log('configuration: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'El panel no está configurado. Revisa las variables de entorno.';

    return;
}

$request = Request::fromGlobals($_SERVER, $_GET, $_POST, $_FILES, $config->trustProxy);
$app = new App(
    new WooStoreConnector(new CurlHttpClient(), $config->storeUrl),
    $vault,
    new FileLoginThrottle($config->storagePath . '/throttle'),
    new View($root . '/templates', $root . '/public', $config->timezone),
    $config,
    new StderrErrorReporter(),
);

$session = NativeSession::start($config->secureCookies || $request->secure, $config->absoluteTimeoutSeconds);
$app->handle($request, $session)->send(!$request->isHead());
