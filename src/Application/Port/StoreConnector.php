<?php

declare(strict_types=1);

namespace WooPanel\Application\Port;

use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Application\Store;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\StoreUser;

interface StoreConnector
{
    /**
     * Confirms the credentials belong to someone allowed to manage the store.
     *
     * @throws InvalidCredentials
     * @throws MissingCapability
     * @throws StoreUnavailable
     */
    public function authenticate(Credentials $credentials): StoreUser;

    /** Gateways that act on the store on behalf of the given user. */
    public function connect(Credentials $credentials): Store;
}
