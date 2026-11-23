<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private int $port;
    private string $charset;

    private function __construct()
    {
        $this->resolveCredentials();
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getPdo(): PDO
    {
        return self::getInstance()->getConnection();
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    private function resolveCredentials(): void
    {
        // Check for WordPress wp-config.php in parent directories
        $candidatePaths = [
            __DIR__ . '/../../../../wp-config.php',
            __DIR__ . '/../../../wp-config.php',
            __DIR__ . '/../../wp-config.php',
            __DIR__ . '/../wp-config.php',
        ];

        foreach ($candidatePaths as $wpConfig) {
            if (file_exists($wpConfig)) {
                @require_once $wpConfig;
                break;
            }
        }

        $this->host = defined('DB_HOST') ? (string)DB_HOST : (getenv('DB_HOST') ?: 'db');
        $this->dbname = defined('DB_NAME') ? (string)DB_NAME : (getenv('DB_NAME') ?: 'ufun');
        $this->username = defined('DB_USER') ? (string)DB_USER : (getenv('DB_USER') ?: 'ufun_user');
        $this->password = defined('DB_PASSWORD') ? (string)DB_PASSWORD : (getenv('DB_PASSWORD') ?: 'ufun_password');
        $this->port = (int)(getenv('DB_PORT') ?: 3306);
        $this->charset = defined('DB_CHARSET') && DB_CHARSET ? (string)DB_CHARSET : 'utf8mb4';

        // Split host and port if host format is "hostname:3306"
        if (str_contains($this->host, ':')) {
            [$h, $p] = explode(':', $this->host, 2);
            $this->host = $h;
            $this->port = (int)$p;
        }
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->dbname,
            $this->charset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getTablePrefix(): string
    {
        global $table_prefix;
        if (!empty($table_prefix)) {
            return (string)$table_prefix;
        }
        return (string)(getenv('DB_TABLE_PREFIX') ?: 'wp_');
    }
}
