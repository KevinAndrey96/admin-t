<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\StoreUnavailable;

/**
 * Typed reads over decoded JSON, tolerant to the loose types WordPress sends (numbers as strings, false for empty).
 */
final class Payload
{
    /**
     * @return array<array-key, mixed>
     */
    public static function map(mixed $value): array
    {
        if (!\is_array($value)) {
            throw new StoreUnavailable(\sprintf('expected a json object, got %s', get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new StoreUnavailable(\sprintf('expected a json list, got %s', get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return match (true) {
            \is_string($value) => $value,
            \is_int($value), \is_float($value) => (string) $value,
            default => '',
        };
    }

    /**
     * Text that WordPress returns HTML-encoded (titles, notes, descriptions).
     *
     * @param array<array-key, mixed> $data
     */
    public static function text(array $data, string $key): string
    {
        return trim(html_entity_decode(strip_tags(self::string($data, $key)), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function int(array $data, string $key): int
    {
        return self::nullableInt($data, $key) ?? 0;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return match (true) {
            \is_int($value) => $value,
            \is_string($value) && preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            \is_float($value) => (int) $value,
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        return ($data[$key] ?? false) === true;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function object(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return \is_array($value) ? $value : [];
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<array<array-key, mixed>>
     */
    public static function objects(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, \is_array(...)));
    }

    /**
     * Dates come as "2026-11-20T15:04:05" without offset; the *_gmt variants are UTC.
     *
     * @param array<array-key, mixed> $data
     */
    public static function utcDate(array $data, string $key): \DateTimeImmutable
    {
        $raw = self::string($data, $key . '_gmt');
        if ($raw === '') {
            $raw = self::string($data, $key);
        }

        try {
            return new \DateTimeImmutable($raw !== '' ? $raw : '@0', new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new StoreUnavailable(\sprintf('invalid date "%s" in %s', $raw, $key), 0, $e);
        }
    }
}
