<?php

declare(strict_types=1);

namespace WooPanel\Http\Controller;

use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\OrderQuery;
use WooPanel\Application\Port\OrderGateway;
use WooPanel\Domain\OrderStatus;
use WooPanel\Http\Request;
use WooPanel\Http\Responder;
use WooPanel\Http\Response;

final readonly class OrderController
{
    public const int NOTE_MAX = 2000;

    public function __construct(
        private OrderGateway $orders,
        private Responder $respond,
    ) {}

    public function index(Request $request): Response
    {
        $query = new OrderQuery($request->pageNumber(), OrderStatus::tryFrom($request->query('estado')), $request->query('buscar'));

        return $this->respond->page('orders/index', [
            'page' => $this->orders->list($query),
            'query' => $query,
            'statuses' => OrderStatus::assignable(),
        ], 'Pedidos');
    }

    public function show(int $id): Response
    {
        $order = $this->orders->find($id);
        if ($order === null) {
            return $this->respond->error(404, 'Pedido no encontrado', \sprintf('No existe un pedido con el número %d o fue eliminado.', $id));
        }

        return $this->respond->page('orders/show', [
            'order' => $order,
            'notes' => $this->orders->notes($id),
            'statuses' => OrderStatus::assignable(),
            'noteMax' => self::NOTE_MAX,
        ], 'Pedido #' . $order->number);
    }

    public function changeStatus(Request $request, int $id): Response
    {
        $back = '/pedidos/' . $id;
        $status = OrderStatus::tryFrom($request->input('estado'));
        if ($status === null || $status === OrderStatus::Draft) {
            return $this->respond->redirect($back, 'error', 'Elige un estado válido para el pedido.');
        }

        try {
            $order = $this->orders->changeStatus($id, $status);
        } catch (StoreRejected $e) {
            return $this->respond->redirect($back, 'error', $e->getMessage());
        }

        return $this->respond->redirect($back, 'success', \sprintf('El pedido #%s quedó en «%s».', $order->number, $order->status->label()));
    }

    public function addNote(Request $request, int $id): Response
    {
        $back = '/pedidos/' . $id . '#notas';
        $text = trim($request->input('nota'));
        if ($text === '' || mb_strlen($text) > self::NOTE_MAX) {
            return $this->respond->redirect($back, 'error', \sprintf('La nota debe tener entre 1 y %d caracteres.', self::NOTE_MAX));
        }
        $visibleToCustomer = $request->input('para_cliente') === '1';

        try {
            $this->orders->addNote($id, $text, $visibleToCustomer);
        } catch (StoreRejected $e) {
            return $this->respond->redirect($back, 'error', $e->getMessage());
        }

        return $this->respond->redirect($back, 'success', $visibleToCustomer ? 'Nota enviada al cliente por correo.' : 'Nota interna guardada.');
    }
}
