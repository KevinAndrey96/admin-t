<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;
use App\Services\UploadService;

header('Content-Type: application/json; charset=utf-8');

if (!AuthService::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file'])) {
    try {
        $service = new UploadService();
        $res = $service->handleUpload($_FILES['file']);
        echo json_encode(['success' => true, 'id' => $res['id'], 'url' => $res['url']]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'No se envio ningun archivo']);
