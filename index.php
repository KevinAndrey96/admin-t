<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

use App\Services\AuthService;

if (AuthService::isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Acceso al Panel - WooCommerce Fast Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            background: #ffffff;
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 30px 20px 20px;
            text-align: center;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 14px;
            height: auto;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="container px-3">
    <div class="login-card mx-auto">
        <div class="login-header">
            <div class="login-brand-icon">
                <i class="fa fa-shopping-bag"></i>
            </div>
            <h4 class="font-weight-bold text-dark mb-1">WooCommerce Panel</h4>
            <p class="text-muted small mb-0">Micro-Admin Standalone & Gestor de Pedidos</p>
        </div>

        <div class="card-body p-4">
            <?php if ($error === 'invalid_credentials'): ?>
                <div class="alert alert-danger py-2 px-3 small border-0" role="alert">
                    <i class="fa fa-exclamation-triangle mr-1"></i> Usuario o contrasena incorrectos.
                </div>
            <?php elseif ($msg === 'logged_out'): ?>
                <div class="alert alert-info py-2 px-3 small border-0" role="alert">
                    <i class="fa fa-info-circle mr-1"></i> Sesion cerrada correctamente.
                </div>
            <?php endif; ?>

            <form action="Controller/Login.php" method="POST">
                <?=csrf_field()?>
                <div class="form-group mb-3">
                    <label class="font-weight-medium small text-muted mb-1" for="user">Usuario o Correo WP</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fa fa-user text-muted"></i></span>
                        </div>
                        <input type="text" class="form-control border-left-0" id="user" name="USER" placeholder="admin o admin@tienda.com" required autofocus>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="font-weight-medium small text-muted mb-1" for="pass">Contrasena</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fa fa-lock text-muted"></i></span>
                        </div>
                        <input type="password" class="form-control border-left-0" id="pass" name="PASS" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block mb-3">
                    <i class="fa fa-sign-in mr-2"></i>Iniciar Sesion
                </button>
            </form>
        </div>
        <div class="card-footer bg-light text-center py-3 border-0 small text-muted">
            <i class="fa fa-shield text-success mr-1"></i> Autenticacion directa contra base de datos WordPress
        </div>
    </div>
</div>

</body>
</html>
