<?php
declare(strict_types=1);

/**
 * Legacy PDO Connection Adapter
 * 
 * Provides backwards compatibility for legacy scripts by wrapping the modern Database service.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Config\Database;

try {
    $db = Database::getPdo();
    $Nombre_Sitio = defined('SITE_NAME') ? SITE_NAME : (getenv('APP_NAME') ?: 'WooCommerce Fast Admin');
    $Web_Autor = 'https://github.com/KevinAndrey96/woo-php-admin-panel';
    $Autor_Sitio = 'Kevin Andrey Herrera Silva';
} catch (\Throwable $e) {
    die("Database connection failed: " . $e->getMessage());
}
