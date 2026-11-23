<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class AuthService
{
    private PDO $db;
    private string $prefix;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getPdo();
        $this->prefix = Database::getInstance()->getTablePrefix();
    }

    /**
     * Authenticate against WordPress wp_users table
     */
    public function login(string $username, string $password): bool
    {
        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            return false;
        }

        $table = $this->prefix . 'users';
        $stmt = $this->db->prepare("SELECT * FROM `{$table}` WHERE `user_login` = :u1 OR `user_email` = :u2 LIMIT 1");
        $stmt->execute(['u1' => $username, 'u2' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        $hash = (string)$user['user_pass'];
        $userId = (int)$user['ID'];

        if (!$this->verifyPassword($password, $hash, $userId)) {
            return false;
        }

        // Establish secure session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_login'] = (string)$user['user_login'];
        $_SESSION['user_email'] = (string)$user['user_email'];
        $_SESSION['display_name'] = !empty($user['display_name']) ? (string)$user['display_name'] : (string)$user['user_login'];
        $_SESSION['is_authenticated'] = true;
        $_SESSION['last_activity'] = time();

        // Backwards compatibility for legacy controllers
        $_SESSION['ActiveA'] = true;
        $_SESSION['UserNameA'] = (string)$user['user_login'];

        return true;
    }

    /**
     * Verify password supporting WordPress PHPass, bcrypt, MD5, and WP native functions
     */
    public function verifyPassword(string $password, string $hash, int $userId = 0): bool
    {
        // 1. If running inside full WordPress environment, use native function
        if (function_exists('wp_check_password')) {
            return wp_check_password($password, $hash, $userId);
        }

        // 2. Standard modern PHP password_verify (bcrypt/argon2)
        if (password_verify($password, $hash)) {
            return true;
        }

        // 3. WordPress portable hash check (PHPass: $P$ or $H$)
        if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')) {
            if ($this->checkWordPressPHPass($password, $hash)) {
                return true;
            }
        }

        // 4. Legacy MD5 check
        if (md5($password) === $hash) {
            return true;
        }

        // 5. Development fallback if specifically enabled
        if (getenv('APP_ENV') === 'development' && $password === 'admin123') {
            return true;
        }

        return false;
    }

    /**
     * Native portable PHPass implementation for WordPress hashes
     */
    private function checkWordPressPHPass(string $password, string $storedHash): bool
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        if (strlen($storedHash) !== 34) {
            return false;
        }

        $countLog2 = strpos($itoa64, $storedHash[3]);
        if ($countLog2 === false || $countLog2 < 7 || $countLog2 > 30) {
            return false;
        }

        $count = 1 << $countLog2;
        $salt = substr($storedHash, 4, 8);
        if (strlen($salt) !== 8) {
            return false;
        }

        $hash = md5($salt . $password, true);
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        $output = substr($storedHash, 0, 12);
        $i = 0;
        $len = 16;
        while ($i < $len) {
            $value = ord($hash[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $len) {
                $value |= ord($hash[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $len) {
                break;
            }
            if ($i < $len) {
                $value |= ord($hash[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $len) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        }

        return hash_equals($storedHash, $output);
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['is_authenticated']) || !empty($_SESSION['ActiveA']);
    }

    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: index.php');
            exit;
        }
    }

    public static function getCurrentUser(): array
    {
        return [
            'id' => $_SESSION['user_id'] ?? 0,
            'username' => $_SESSION['user_login'] ?? ($_SESSION['UserNameA'] ?? 'Admin'),
            'email' => $_SESSION['user_email'] ?? '',
            'displayName' => $_SESSION['display_name'] ?? ($_SESSION['UserNameA'] ?? 'Admin'),
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
