<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['USER'] ?? ($_POST['username'] ?? '');
    $pass = $_POST['PASS'] ?? ($_POST['password'] ?? '');

    $auth = new AuthService();
    if ($auth->login($user, $pass)) {
        header('Location: ../dashboard.php');
        exit;
    }

    header('Location: ../index.php?error=invalid_credentials');
    exit;
}

header('Location: ../index.php');
exit;
