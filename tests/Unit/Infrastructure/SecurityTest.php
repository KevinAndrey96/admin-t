<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use WooPanel\Domain\Credentials;
use WooPanel\Infrastructure\Security\CredentialVault;
use WooPanel\Infrastructure\Security\FileLoginThrottle;

final class SecurityTest extends TestCase
{
    public function testSealedCredentialsRoundTrip(): void
    {
        $vault = CredentialVault::fromEncodedKey(CredentialVault::generateKey());

        $sealed = $vault->seal(new Credentials('gestora', 'abcd efgh'));
        $opened = $vault->open($sealed);

        self::assertStringNotContainsString('abcd', (string) base64_decode($sealed, true));
        self::assertNotNull($opened);
        self::assertSame(['gestora', 'abcdefgh'], [$opened->username, $opened->applicationPassword]);
    }

    public function testRejectsTamperedOrForeignValues(): void
    {
        $vault = CredentialVault::fromEncodedKey(CredentialVault::generateKey());
        $sealed = $vault->seal(new Credentials('gestora', 'abcd'));
        $raw = (string) base64_decode($sealed, true);
        $raw[30] = $raw[30] === 'a' ? 'b' : 'a';

        self::assertNull($vault->open(base64_encode($raw)));
        self::assertNull($vault->open('no-es-base64'));
        self::assertNull(CredentialVault::fromEncodedKey(CredentialVault::generateKey())->open($sealed));
    }

    public function testRequiresAThirtyTwoByteKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CredentialVault::fromEncodedKey('base64:' . base64_encode('corta'));
    }

    public function testThrottleBlocksAfterTheLimitAndResets(): void
    {
        $directory = sys_get_temp_dir() . '/woo-panel-test-' . bin2hex(random_bytes(4));
        $throttle = new FileLoginThrottle($directory, 3, 900);

        try {
            for ($i = 0; $i < 2; ++$i) {
                $throttle->recordFailure('ip:203.0.113.9');
            }
            self::assertFalse($throttle->tooManyAttempts('ip:203.0.113.9'));

            $throttle->recordFailure('ip:203.0.113.9');
            self::assertTrue($throttle->tooManyAttempts('ip:203.0.113.9'));
            self::assertFalse($throttle->tooManyAttempts('ip:198.51.100.1'));

            $throttle->clear('ip:203.0.113.9');
            self::assertFalse($throttle->tooManyAttempts('ip:203.0.113.9'));
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testThrottleForgetsOldFailures(): void
    {
        $directory = sys_get_temp_dir() . '/woo-panel-test-' . bin2hex(random_bytes(4));
        $throttle = new FileLoginThrottle($directory, 1, 0);

        try {
            $throttle->recordFailure('user:gestora');
            self::assertFalse($throttle->tooManyAttempts('user:gestora'));
        } finally {
            self::removeDirectory($directory);
        }
    }

    private static function removeDirectory(string $directory): void
    {
        $files = glob($directory . '/*');
        foreach ($files === false ? [] : $files as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
