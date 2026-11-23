<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;
use App\Services\ProductService;

AuthService::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $service = new ProductService();
        $service->create($_POST);
        header('Location: ../dashboard.php?success=created');
        exit;
    } catch (\Throwable $e) {
        header('Location: ../dashboard.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
