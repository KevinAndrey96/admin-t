<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

use App\Helpers\Security;
use App\Services\AuthService;
use App\Services\ProductService;

if (!AuthService::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$productService = new ProductService();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['ID']) ? (int)$_GET['ID'] : 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no valido']);
            exit;
        }

        $product = $productService->getById($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data'    => $product,
            // Backwards compatibility keys
            'id'        => $product['ID'],
            'name'      => $product['post_title'],
            'price'     => $product['price'],
            'short'     => $product['post_excerpt'],
            'long'      => $product['post_content'],
            'foto'      => $product['thumbnail_url'],
            'thumbnail' => $product['thumbnail_id'],
        ]);
        exit;
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? ($_POST['ACTION'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        // Allow delete/update if action is specified or inferred
        if (isset($_POST['IDENTIFICADOR']) || isset($_POST['id']) && $action === '') {
            $action = 'update';
        }

        if ($action === 'create') {
            $newId = $productService->create($_POST);
            echo json_encode([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'id'      => $newId,
            ]);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? ($_POST['IDENTIFICADOR'] ?? 0));
            if ($id <= 0) {
                throw new \InvalidArgumentException('ID de producto requerido para actualizar');
            }
            $productService->update($id, $_POST);
            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'id'      => $id,
            ]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? ($_POST['ID'] ?? 0));
            if ($id <= 0) {
                throw new \InvalidArgumentException('ID de producto requerido para eliminar');
            }
            $productService->delete($id);
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado exitosamente',
                'id'      => $id,
            ]);
            exit;
        }

        throw new \InvalidArgumentException('Accion no reconocida');
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
