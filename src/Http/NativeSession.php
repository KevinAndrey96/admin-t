<?php

declare(strict_types=1);

namespace WooPanel\Http;

/**
 * PHP session with hardened cookie settings. Start it once per request, before any output.
 */
final class NativeSession implements Session
{
    public const string COOKIE = 'woo_panel';

    public static function start(bool $secureCookie, int $lifetimeSeconds): self
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_name(self::COOKIE);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secureCookie,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start([
                'use_strict_mode' => true,
                'use_only_cookies' => true,
                'gc_maxlifetime' => $lifetimeSeconds,
            ]);
        }

        return new self();
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function clear(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
