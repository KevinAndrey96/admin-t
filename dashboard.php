<?php
declare(strict_types=1);

require_once __DIR__ . '/Layouts/Header.php';

use App\Services\OrderService;
use App\Services\ProductService;

$productService = new ProductService();
$orderService = new OrderService();

$stats = $orderService->getStats();
$totalProducts = $productService->countProducts();
$products = $productService->getAll(300);
$mediaList = $productService->getMediaList(60);
?>

<!-- Metric KPI Cards -->
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
                    <span class="text-muted small text-uppercase font-weight-bold">Pendientes / Espera</span>
                    <h3 class="font-weight-bold text-warning mt-1 mb-0"><?=$stats['pending_orders']?></h3>
                </div>
                <div class="rounded-circle bg-warning text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-clock-o font-20"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card stat-card bg-white p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase font-weight-bold">Productos Activos</span>
                    <h3 class="font-weight-bold text-dark mt-1 mb-0"><?=$totalProducts?></h3>
                </div>
                <div class="rounded-circle bg-info text-white p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fa fa-cube font-20"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Products Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    <i class="fa fa-cubes text-primary mr-2 font-18"></i>
                    <h5 class="mb-0 font-weight-bold">Catalogo de Productos</h5>
                </div>
                <div>
                    <button class="btn btn-outline-secondary btn-sm mr-2" data-toggle="collapse" data-target="#collapseUpload">
                        <i class="fa fa-cloud-upload mr-1"></i> Subir Imagenes
                    </button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAddProduct">
                        <i class="fa fa-plus mr-1"></i> Nuevo Producto
                    </button>
                </div>
            </div>

            <!-- Upload Dropzone Accordion -->
            <div class="collapse border-bottom p-4 bg-light" id="collapseUpload">
                <h6 class="font-weight-bold mb-2"><i class="fa fa-image mr-1"></i> Subir Imagenes al Repositorio de Medios</h6>
                <p class="text-muted small mb-3">Las imagenes subidas quedaran registradas en la biblioteca de WordPress y estaran disponibles para asociar como foto de producto.</p>
                <form action="api/upload.php" class="dropzone" id="productDropzone">
                    <div class="dz-message text-center py-4">
                        <i class="fa fa-cloud-upload fa-3x text-muted mb-2"></i>
                        <h5>Arrastra tus imagenes aqui o haz clic para examinar</h5>
                        <span class="text-muted small">Formatos soportados: JPG, PNG, WEBP (Max 10MB)</span>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="productsTable" class="table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 70px;">Foto</th>
                                <th>Nombre</th>
                                <th>Categorias</th>
                                <th style="width: 120px;">Precio</th>
                                <th>Descripcion Corta</th>
                                <th style="width: 130px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td class="font-weight-bold text-muted">#<?=$prod['ID']?></td>
                                    <td>
                                        <?php if (!empty($prod['thumbnail_url'])): ?>
                                            <img src="<?=e($prod['thumbnail_url'])?>" alt="Thumb" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                                <i class="fa fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?=e($prod['post_title'])?></strong>
                                        <?php if (!empty($prod['guid'])): ?>
                                            <div>
                                                <a href="<?=e($prod['guid'])?>" target="_blank" rel="noopener" class="small text-muted">
                                                    <i class="fa fa-external-link mr-1"></i>Ver en tienda
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($prod['categories'])): ?>
                                            <?php foreach (explode(',', $prod['categories']) as $cat): ?>
                                                <span class="badge badge-light border text-secondary mb-1"><?=e(trim($cat))?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin categoria</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-weight-bold text-dark">
                                        <?=format_currency($prod['price'])?>
                                    </td>
                                    <td class="text-muted small">
                                        <?=e(mb_strimwidth($prod['post_excerpt'] ?: strip_tags($prod['post_content']), 0, 80, '...'))?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-edit mr-1" data-id="<?=$prod['ID']?>" title="Editar Producto">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?=$prod['ID']?>" data-name="<?=e($prod['post_title'])?>" title="Eliminar Producto">
                                            <i class="fa fa-trash"></i>
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

<!-- Modal: Add Product -->
<div class="modal fade" id="modalAddProduct" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-plus-circle mr-2"></i>Crear Nuevo Producto</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="Controller/AddProduct.php" method="POST">
                <?=csrf_field()?>
                <div class="modal-body p-4">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold small">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="NAME" placeholder="Ej: Camiseta Oversize Negra" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">Precio (COP/USD) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="PRICE" placeholder="Ej: 85000" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Imagen Destacada</label>
                        <select name="FOTO" class="form-control">
                            <option value="0">Seleccionar imagen de la biblioteca...</option>
                            <?php foreach ($mediaList as $media): ?>
                                <option value="<?=$media['ID']?>">#<?=$media['ID']?> - <?=e($media['post_title'])?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Puedes subir nuevas fotos usando el modulo de carga superior.</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Descripcion Corta</label>
                        <input type="text" class="form-control" name="SHORT" placeholder="Resumen breve para listados y tarjetas">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Descripcion Detallada</label>
                        <textarea class="form-control" name="LONG" rows="4" placeholder="Caracteristicas, materiales, medidas, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-check mr-1"></i> Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Product -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-pencil mr-2"></i>Editar Producto</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="Controller/UpdateProduct.php" method="POST" id="formEditProduct">
                <?=csrf_field()?>
                <input type="hidden" name="IDENTIFICADOR" id="edit_id">
                <div class="modal-body p-4">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="font-weight-bold small">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="NAME" id="edit_name" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">Precio <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="PRICE" id="edit_price" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Imagen Destacada</label>
                        <select name="FOTO" id="edit_photo" class="form-control">
                            <option value="0">Sin imagen</option>
                            <?php foreach ($mediaList as $media): ?>
                                <option value="<?=$media['ID']?>">#<?=$media['ID']?> - <?=e($media['post_title'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Descripcion Corta</label>
                        <input type="text" class="form-control" name="SHORT" id="edit_short">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Descripcion Detallada</label>
                        <textarea class="form-control" name="LONG" id="edit_long" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark"><i class="fa fa-save mr-1"></i> Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="formDeleteProduct" action="Controller/DeleteProduct.php" method="POST" style="display: none;">
    <?=csrf_field()?>
    <input type="hidden" name="ID" id="delete_id">
</form>

<?php require_once __DIR__ . '/Layouts/Footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#productsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ productos",
            info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
            infoEmpty: "Mostrando 0 a 0 de 0 productos",
            infoFiltered: "(filtrado de _MAX_ totales)",
            zeroRecords: "No se encontraron productos coincidentes",
            paginate: {
                first: "Primero",
                last: "Ultimo",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });

    // Configure Dropzone
    Dropzone.autoDiscover = false;
    if ($('#productDropzone').length) {
        new Dropzone("#productDropzone", {
            paramName: "file",
            maxFilesize: 10,
            acceptedFiles: "image/jpeg,image/png,image/webp,image/gif",
            success: function(file, response) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Imagen subida correctamente',
                    showConfirmButton: false,
                    timer: 2500
                });
            },
            error: function(file, message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de subida',
                    text: typeof message === 'object' ? message.message : message
                });
            }
        });
    }

    // Handle Edit Click
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Cargando producto...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.getJSON('api/products.php', { id: id }, function(res) {
            Swal.close();
            if (res.success && res.data) {
                $('#edit_id').val(res.data.ID);
                $('#edit_name').val(res.data.post_title);
                $('#edit_price').val(res.data.price);
                $('#edit_short').val(res.data.post_excerpt);
                $('#edit_long').val(res.data.post_content);
                $('#edit_photo').val(res.data.thumbnail_id || 0);
                $('#modalEditProduct').modal('show');
            } else {
                Swal.fire('Error', res.message || 'No se pudo cargar el producto', 'error');
            }
        }).fail(function() {
            Swal.close();
            Swal.fire('Error', 'Error de conexion con el servidor', 'error');
        });
    });

    // Handle Delete Click
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: '¿Eliminar producto?',
            text: `¿Estas seguro de eliminar "${name}" (#${id})? Esta accion no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete_id').val(id);
                $('#formDeleteProduct').submit();
            }
        });
    });
});
</script>
