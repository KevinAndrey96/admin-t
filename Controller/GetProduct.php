<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;
use App\Services\ProductService;

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

$service = new ProductService();
$product = $service->getById($id);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'No encontrado']);
    exit;
}

echo json_encode([
    'id'        => $product['ID'],
    'name'      => $product['post_title'],
    'price'     => $product['price'],
    'short'     => $product['post_excerpt'],
    'long'      => $product['post_content'],
    'foto'      => $product['thumbnail_url'],
    'thumbnail' => $product['thumbnail_id'],
]);
