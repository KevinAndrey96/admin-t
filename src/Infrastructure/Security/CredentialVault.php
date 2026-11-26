<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\Security;

use WooPanel\Domain\Credentials;

/**
 * Encrypts the application password kept in the session, so a leaked session file is not a usable credential.
 */
final readonly class CredentialVault
{
    private function __construct(#[\SensitiveParameter] private string $key) {}

    /**
     * @param string $encodedKey "base64:" followed by 32 random bytes in base64, as `make key` prints
     */
    public static function fromEncodedKey(#[\SensitiveParameter] string $encodedKey): self
    {
        $key = base64_decode(str_starts_with($encodedKey, 'base64:') ? substr($encodedKey, 7) : $encodedKey, true);
        if ($key === false || \strlen($key) !== \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException('APP_KEY must be 32 random bytes encoded as base64');
        }

        return new self($key);
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(sodium_crypto_secretbox_keygen());
    }

    public function seal(Credentials $credentials): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = json_encode([$credentials->username, $credentials->applicationPassword], \JSON_THROW_ON_ERROR);

        return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->key));
    }

    /** Returns null when the value was tampered with or sealed with another key. */
    public function open(string $sealed): ?Credentials
    {
        $raw = base64_decode($sealed, true);
        if ($raw === false || \strlen($raw) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $plain = sodium_crypto_secretbox_open(
            substr($raw, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key,
        );
        if ($plain === false) {
            return null;
        }

        $parts = json_decode($plain, true);
        if (!\is_array($parts) || !\is_string($parts[0] ?? null) || !\is_string($parts[1] ?? null)) {
            return null;
        }

        return new Credentials($parts[0], $parts[1]);
    }
}
