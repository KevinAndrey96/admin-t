<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Port\StoreConnector;
use WooPanel\Application\Store;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\StoreUser;
use WooPanel\Infrastructure\Http\HttpClient;

final readonly class WooStoreConnector implements StoreConnector
{
    public const string REQUIRED_CAPABILITY = 'manage_woocommerce';

    /**
     * @param list<int> $retryDelaysMs
     */
    public function __construct(
        private HttpClient $http,
        private string $siteUrl,
        private array $retryDelaysMs = [250, 750],
    ) {}

    public function authenticate(Credentials $credentials): StoreUser
    {
        $user = $this->client($credentials)->get('wp/v2/users/me', ['context' => 'edit'])->object();
        if (!Payload::bool(Payload::object($user, 'capabilities'), self::REQUIRED_CAPABILITY)) {
            throw new MissingCapability(\sprintf('user %s lacks %s', $credentials->username, self::REQUIRED_CAPABILITY));
        }

        $name = Payload::text($user, 'name');

        return new StoreUser(Payload::int($user, 'id'), $name !== '' ? $name : $credentials->username);
    }

    public function connect(Credentials $credentials): Store
    {
        $client = $this->client($credentials);

        return new Store(
            new WooOrderGateway($client),
            new WooProductGateway($client),
            new WooReportGateway($client),
            new WpMediaGateway($client),
        );
    }

    private function client(Credentials $credentials): RestClient
    {
        return new RestClient($this->http, $this->siteUrl, $credentials, $this->retryDelaysMs);
    }
}
