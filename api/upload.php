<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

use App\Services\AuthService;
use App\Services\UploadService;

if (!AuthService::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibio ningun archivo']);
    exit;
}

try {
    $uploadService = new UploadService();
    $result = $uploadService->handleUpload($_FILES['file']);

    echo json_encode([
        'success' => true,
        'message' => 'Imagen subida exitosamente',
        'data'    => $result,
        'id'      => $result['id'],
        'url'     => $result['url'],
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
