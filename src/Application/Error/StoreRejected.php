<?php

declare(strict_types=1);

namespace WooPanel\Application\Error;

/**
 * The store refused the request (4xx). The message is WooCommerce's own and is safe to show the user.
 */
final class StoreRejected extends StoreError
{
    public function __construct(string $message, public readonly int $status, public readonly string $errorCode)
    {
        parent::__construct($message);
    }
}
