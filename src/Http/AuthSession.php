<?php

declare(strict_types=1);

namespace WooPanel\Http;

use WooPanel\Domain\StoreUser;

final readonly class AuthSession
{
    public function __construct(
        public StoreUser $user,
        public string $sealedCredentials,
        public int $signedInAt,
        public int $lastSeenAt,
    ) {}

    public function isExpired(int $now, int $idleTimeoutSeconds, int $absoluteTimeoutSeconds): bool
    {
        return $now - $this->lastSeenAt > $idleTimeoutSeconds || $now - $this->signedInAt > $absoluteTimeoutSeconds;
    }
}
