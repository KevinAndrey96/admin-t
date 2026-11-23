<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;
use App\Services\OrderService;

AuthService::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['ID'] ?? ($_POST['id'] ?? 0));
    $status = (string)($_POST['STATUS'] ?? ($_POST['status'] ?? ''));

    if ($id > 0 && $status !== '') {
        try {
            $service = new OrderService();
            $service->updateStatus($id, $status);
            header('Location: ../transactions.php?success=status_updated');
            exit;
        } catch (\Throwable $e) {
            header('Location: ../transactions.php?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: ../transactions.php');
exit;
