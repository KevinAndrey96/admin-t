<?php
declare(strict_types=1);

namespace App\Helpers;

class Security
{
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
    }

    public static function sanitizeString(?string $value): string
    {
        return trim((string)($value ?? ''));
    }

    public static function sanitizeFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $cleaned = preg_replace('/[^\d.,]/', '', (string)$value);
        $cleaned = str_replace(',', '.', $cleaned);
        return (float)$cleaned;
    }

    public static function sanitizeSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
