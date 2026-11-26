<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\StoreError;
use WooPanel\Application\Port\StoreConnector;
use WooPanel\Application\Store;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\StoreUser;

/**
 * Accepts one username and application password; anything else is rejected like WordPress would.
 */
final class FakeConnector implements StoreConnector
{
    public const string USERNAME = 'gestora';
    public const string PASSWORD = 'abcd efgh ijkl mnop qrst uvwx';

    public ?StoreError $authFailure = null;

    /** @var list<Credentials> */
    public array $connections = [];

    public function __construct(public readonly InMemoryStore $memory = new InMemoryStore()) {}

    public function authenticate(Credentials $credentials): StoreUser
    {
        if ($this->authFailure !== null) {
            throw $this->authFailure;
        }
        if ($credentials->username !== self::USERNAME || $credentials->applicationPassword !== str_replace(' ', '', self::PASSWORD)) {
            throw new InvalidCredentials('incorrect_password');
        }

        return new StoreUser(7, 'Marta Gestora');
    }

    public function connect(Credentials $credentials): Store
    {
        $this->connections[] = $credentials;

        return $this->memory->store();
    }
}
