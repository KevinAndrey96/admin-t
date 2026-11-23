<?php
declare(strict_types=1);

require_once __DIR__ . '/Layouts/Header.php';

use App\Services\OrderService;

$orderService = new OrderService();
$orders = $orderService->getAll(300);
$stats = $orderService->getStats();
?>

<!-- Orders Metric Cards -->
<div class="row mb-4">
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card stat-card bg-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase font-weight-bold">Ventas Totales</span>
                    <h3 class="font-weight-bold text-dark mt-1 mb-0"><?=format_currency($stats['total_sales'])?></h3>
                </div>
                <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-dollar font-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card stat-card bg-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase font-weight-bold">Total Pedidos</span>
                    <h3 class="font-weight-bold text-dark mt-1 mb-0"><?=$stats['total_orders']?></h3>
                </div>
                <div class="rounded-circle bg-primary text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-shopping-cart font-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card stat-card bg-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase font-weight-bold">Completados</span>
                    <h3 class="font-weight-bold text-success mt-1 mb-0"><?=$stats['completed_orders']?></h3>
                </div>
                <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-check-circle font-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card stat-card bg-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase font-weight-bold">Por Gestionar</span>
                    <h3 class="font-weight-bold text-warning mt-1 mb-0"><?=$stats['pending_orders']?></h3>
                </div>
                <div class="rounded-circle bg-warning text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-clock-o font-20"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    <i class="fa fa-shopping-cart text-primary mr-2 font-18"></i>
                    <h5 class="mb-0 font-weight-bold">Pedidos y Transacciones WooCommerce</h5>
                </div>
                <span class="badge badge-light border text-muted">Actualizaciones en Tiempo Real</span>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="ordersTable" class="table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Orden</th>
                                <th>Cliente</th>
                                <th style="width: 140px;">Fecha</th>
                                <th style="width: 130px;">Estado</th>
                                <th style="width: 130px;">Total</th>
                                <th style="width: 110px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="font-weight-bold text-primary">#<?=$order['ID']?></td>
                                    <td>
                                        <strong><?=e($order['customer_name'])?></strong>
                                        <?php if (!empty($order['email'])): ?>
                                            <div class="text-muted small"><?=e($order['email'])?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?=App\Helpers\Format::datetime($order['post_date'])?>
                                    </td>
                                    <td>
                                        <?=status_badge($order['post_status'])?>
                                    </td>
                                    <td class="font-weight-bold text-dark">
                                        <?=format_currency($order['total_amount'])?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-view-order" data-id="<?=$order['ID']?>" title="Ver Detalles de la Orden">
                                            <i class="fa fa-eye mr-1"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Order Details -->
<div class="modal fade" id="modalOrderDetails" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="orderModalTitle">
                    <i class="fa fa-file-text-o mr-2"></i>Detalles de la Orden
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <!-- Customer Details Grid -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                            <i class="fa fa-user mr-1 text-primary"></i> Informacion del Comprador
                        </h6>
                        <div class="row small">
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Nombre Completo:</span>
                                <strong id="det_name" class="text-dark">---</strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Correo Electronico:</span>
                                <strong id="det_email" class="text-dark">---</strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Telefono:</span>
                                <strong id="det_phone" class="text-dark">---</strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Direccion:</span>
                                <strong id="det_address" class="text-dark">---</strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Ciudad / Region:</span>
                                <strong id="det_city" class="text-dark">---</strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <span class="text-muted d-block">Direccion IP:</span>
                                <strong id="det_ip" class="text-dark">---</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <h6 class="font-weight-bold text-dark mb-2">
                    <i class="fa fa-cubes mr-1 text-primary"></i> Productos Comprados
                </h6>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center" style="width: 80px;">Cant.</th>
                                <th class="text-right" style="width: 110px;">P. Unitario</th>
                                <th class="text-right" style="width: 120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="det_items_body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                        <tfoot class="bg-white font-weight-bold">
                            <tr>
                                <td colspan="3" class="text-right text-uppercase small">Total Facturado:</td>
                                <td class="text-right text-dark font-16" id="det_total_val">$ 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Status Update Section -->
                <div class="card border-0 bg-light p-3">
                    <h6 class="font-weight-bold text-dark mb-2">
                        <i class="fa fa-exchange mr-1 text-primary"></i> Actualizar Estado del Pedido
                    </h6>
                    <form action="Controller/Status.php" method="POST" id="formStatusUpdate" class="d-flex flex-wrap gap-2">
                        <?=csrf_field()?>
                        <input type="hidden" name="ID" id="status_order_id">
                        
                        <button type="submit" name="STATUS" value="wc-completed" class="btn btn-sm btn-success mr-2 mb-2">
                            <i class="fa fa-check-circle mr-1"></i> Completado
                        </button>
                        <button type="submit" name="STATUS" value="wc-processing" class="btn btn-sm btn-primary mr-2 mb-2">
                            <i class="fa fa-refresh mr-1"></i> En Proceso
                        </button>
                        <button type="submit" name="STATUS" value="wc-on-hold" class="btn btn-sm btn-warning mr-2 mb-2">
                            <i class="fa fa-clock-o mr-1"></i> En Espera
                        </button>
                        <button type="submit" name="STATUS" value="wc-cancelled" class="btn btn-sm btn-secondary mr-2 mb-2">
                            <i class="fa fa-ban mr-1"></i> Cancelado
                        </button>
                        <button type="submit" name="STATUS" value="trash" class="btn btn-sm btn-danger mb-2">
                            <i class="fa fa-trash mr-1"></i> Papelera
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/Layouts/Footer.php'; ?>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Buscar pedidos:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ pedidos",
            infoEmpty: "Mostrando 0 a 0 de 0 pedidos",
            infoFiltered: "(filtrado de _MAX_ totales)",
            zeroRecords: "No se encontraron pedidos coincidentes",
            paginate: {
                first: "Primero",
                last: "Ultimo",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });

    // Handle View Order Click
    $(document).on('click', '.btn-view-order', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Cargando orden...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.getJSON('api/orders.php', { id: id }, function(res) {
            Swal.close();
            if (res.success && res.data) {
                const order = res.data;
                $('#orderModalTitle').html(`<i class="fa fa-file-text-o mr-2"></i>Orden #${order.id} - Detalles`);
                $('#status_order_id').val(order.id);

                $('#det_name').text(order.customer_name || 'Desconocido');
                $('#det_email').text(order.email || 'N/A');
                $('#det_phone').text(order.phone || 'N/A');
                $('#det_address').text(order.address || 'N/A');
                $('#det_city').text(`${order.city || ''} ${order.state || ''} ${order.country || ''}`.trim() || 'N/A');
                $('#det_ip').text(order.ip || 'N/A');

                // Render items
                const tbody = $('#det_items_body');
                tbody.empty();

                let totalCalculated = 0;
                if (order.items && order.items.length > 0) {
                    order.items.forEach(function(item) {
                        totalCalculated += item.total;
                        const unitPrice = item.quantity > 0 ? (item.total / item.quantity) : item.total;
                        tbody.append(`
                            <tr>
                                <td>
                                    <strong>${item.name}</strong>
                                    <div class="small text-muted">ID Ref: #${item.id}</div>
                                </td>
                                <td class="text-center font-weight-bold">${item.quantity}</td>
                                <td class="text-right">$ ${Number(unitPrice).toLocaleString('es-CO', {minimumFractionDigits: 2})}</td>
                                <td class="text-right font-weight-bold">$ ${Number(item.total).toLocaleString('es-CO', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append(`
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <em>No hay desglose de productos para esta orden o se uso checkout rapido.</em>
                            </td>
                        </tr>
                    `);
                    totalCalculated = order.total;
                }

                $('#det_total_val').text(`$ ${Number(order.total || totalCalculated).toLocaleString('es-CO', {minimumFractionDigits: 2})}`);
                $('#modalOrderDetails').modal('show');
            } else {
                Swal.fire('Error', res.message || 'No se pudo cargar la orden', 'error');
            }
        }).fail(function() {
            Swal.close();
            Swal.fire('Error', 'Error al consultar la orden', 'error');
        });
    });
});
</script>
