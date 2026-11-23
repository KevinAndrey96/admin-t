            </div><!-- /.animated -->
        </div><!-- /.content -->

        <footer class="site-footer bg-white border-top py-3 px-4 mt-auto">
            <div class="row align-items-center">
                <div class="col-sm-6 text-muted small">
                    &copy; <?=date('Y')?> <strong>WooCommerce Fast Admin</strong>. Desarrollado por <a href="https://github.com/KevinAndrey96" target="_blank" rel="noopener">Kevin Andrey Herrera</a>.
                </div>
                <div class="col-sm-6 text-sm-right text-muted small">
                    <span class="badge badge-light border">PHP 8.2+ Ready</span>
                    <span class="badge badge-light border">WooCommerce Compatible</span>
                </div>
            </div>
        </footer>
    </div><!-- /#right-panel -->

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>

    <script>
    // Universal URL query notification handler
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        const error = urlParams.get('error');

        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });

        if (success) {
            let msg = 'Accion realizada exitosamente.';
            if (success === 'created') msg = 'Producto creado con exito.';
            if (success === 'updated') msg = 'Producto actualizado con exito.';
            if (success === 'deleted') msg = 'Producto eliminado con exito.';
            if (success === 'status_updated') msg = 'Estado de pedido actualizado con exito.';

            toast.fire({
                icon: 'success',
                title: msg
            });

            // Clean query string from URL without reload
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        if (error) {
            toast.fire({
                icon: 'error',
                title: decodeURIComponent(error)
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</body>
</html>
