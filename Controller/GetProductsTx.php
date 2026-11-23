<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;
use App\Services\OrderService;

header('Content-Type: application/json; charset=utf-8');

if (!AuthService::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id = isset($_GET['ID']) ? (int)$_GET['ID'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID invalido']);
    exit;
}

$service = new OrderService();
$items = $service->getItems($id);

$output = [];
foreach ($items as $item) {
    $output[] = [
        'id'       => $item['id'],
        'nombre'   => $item['name'],
        'cantidad' => $item['quantity'],
        'valor'    => $item['total'],
    ];
}

echo json_encode($output);
