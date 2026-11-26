<?php

declare(strict_types=1);

namespace WooPanel\Http;

final readonly class AppConfig
{
    public const string REPOSITORY_URL = 'https://github.com/KevinAndrey96/php-woocommerce-admin-panel';

    public function __construct(
        public string $storeUrl,
        public string $storePublicUrl,
        public string $storeName,
        public string $appKey,
        public \DateTimeZone $timezone,
        public bool $secureCookies,
        public bool $trustProxy,
        public string $storagePath,
        public int $idleTimeoutSeconds = 1800,
        public int $absoluteTimeoutSeconds = 28800,
    ) {}

    /**
     * @param array<string, string> $env
     *
     * @throws \InvalidArgumentException when a required variable is missing or malformed
     */
    public static function fromEnvironment(array $env): self
    {
        $storeUrl = self::url($env, 'WOOCOMMERCE_URL');
        $publicUrl = ($env['WOOCOMMERCE_PUBLIC_URL'] ?? '') !== '' ? self::url($env, 'WOOCOMMERCE_PUBLIC_URL') : $storeUrl;

        $appKey = $env['APP_KEY'] ?? '';
        if ($appKey === '') {
            throw new \InvalidArgumentException('APP_KEY is required, generate one with `make key`');
        }

        try {
            $timezone = new \DateTimeZone(($env['APP_TIMEZONE'] ?? '') !== '' ? $env['APP_TIMEZONE'] : 'America/Bogota');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('APP_TIMEZONE is not a valid timezone', 0, $e);
        }

        $storeName = trim($env['STORE_NAME'] ?? '');

        return new self(
            $storeUrl,
            $publicUrl,
            $storeName !== '' ? $storeName : 'Mi tienda',
            $appKey,
            $timezone,
            ($env['APP_SECURE_COOKIES'] ?? 'true') !== 'false',
            ($env['APP_TRUST_PROXY'] ?? 'false') === 'true',
            ($env['APP_STORAGE'] ?? '') !== '' ? rtrim($env['APP_STORAGE'], '/') : sys_get_temp_dir() . '/woo-panel',
        );
    }

    /** Origin (scheme, host and port) of the public store, allowed as an image source in the CSP. */
    public function storeOrigin(): string
    {
        $parts = parse_url($this->storePublicUrl);
        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    /**
     * @param array<string, string> $env
     */
    private static function url(array $env, string $name): string
    {
        $url = rtrim($env[$name] ?? '', '/');
        $scheme = parse_url($url, \PHP_URL_SCHEME);
        if (!\in_array($scheme, ['http', 'https'], true) || filter_var($url, \FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(\sprintf('%s must be an http(s) URL', $name));
        }

        return $url;
    }
}
