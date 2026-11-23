<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

use App\Services\AuthService;
use App\Services\OrderService;

if (!AuthService::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$orderService = new OrderService();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['ID']) ? (int)$_GET['ID'] : 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de orden no valido']);
            exit;
        }

        $order = $orderService->getById($id);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data'    => $order,
            // Backwards compatibility keys
            'nom' => $order['customer_name'],
            'cor' => $order['email'],
            'tel' => $order['phone'],
            'dir' => $order['address'] . ' (' . $order['postcode'] . ')',
            'ciu' => $order['city'] . ', ' . $order['state'] . ' - ' . $order['country'],
            'ipp' => $order['ip'],
        ]);
        exit;
    }

    if ($method === 'POST') {
        $id = (int)($_POST['id'] ?? ($_POST['ID'] ?? 0));
        $status = (string)($_POST['status'] ?? ($_POST['STATUS'] ?? ''));

        if ($id <= 0 || $status === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID y estado son requeridos']);
            exit;
        }

        $orderService->updateStatus($id, $status);

        echo json_encode([
            'success' => true,
            'message' => 'Estado de la orden actualizado exitosamente',
            'id'      => $id,
            'status'  => $status,
        ]);
        exit;
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
