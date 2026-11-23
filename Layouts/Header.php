<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\AuthService;

AuthService::requireAuth();

$currentUser = AuthService::getCurrentUser();
$activePage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$siteName = getenv('APP_NAME') ?: 'WooCommerce Fast Admin';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?=e($siteName)?> - Panel Administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?=csrf_token()?>">

    <link rel="icon" type="image/png" href="images/favicon.png">

    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pixeden-stroke-7-icon@1.2.3/pe-icon-7-stroke/dist/pe-icon-7-stroke.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Theme Styles -->
    <link rel="stylesheet" href="assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f2f7;
        }
        .left-panel {
            background: #1e293b;
        }
        .navbar .navbar-nav li > a {
            color: #94a3b8;
            font-weight: 500;
            padding: 12px 15px;
            border-radius: 6px;
            margin: 2px 10px;
            transition: all 0.2s ease;
        }
        .navbar .navbar-nav li > a:hover,
        .navbar .navbar-nav li.active > a {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }
        .navbar .navbar-nav li.active > a {
            background: #2563eb;
            color: #ffffff !important;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
        }
        .table thead th {
            border-top: none;
            background: #f8fafc;
            color: #475569;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            object-fit: cover;
        }
        .dropzone {
            border: 2px dashed #cbd5e1 !important;
            border-radius: 8px !important;
            background: #f8fafc !important;
        }
    </style>
</head>
<body>

    <!-- Left Navigation Sidebar -->
    <aside id="left-panel" class="left-panel">
        <nav class="navbar navbar-expand-sm navbar-default">
            <div id="main-menu" class="main-menu collapse navbar-collapse">
                <div class="navbar-header text-center py-3">
                    <a class="navbar-brand text-white font-weight-bold" href="dashboard.php">
                        <i class="fa fa-shopping-bag mr-2 text-primary"></i>WooPanel
                    </a>
                </div>
                <ul class="nav navbar-nav w-100">
                    <li class="menu-title text-muted text-uppercase small px-3 mt-3 mb-2">Administracion</li>
                    <li class="<?=$activePage === 'dashboard.php' ? 'active' : ''?>">
                        <a href="dashboard.php">
                            <i class="menu-icon fa fa-cube mr-2"></i>Productos
                        </a>
                    </li>
                    <li class="<?=$activePage === 'transactions.php' ? 'active' : ''?>">
                        <a href="transactions.php">
                            <i class="menu-icon fa fa-shopping-cart mr-2"></i>Pedidos & Ventas
                        </a>
                    </li>

                    <li class="menu-title text-muted text-uppercase small px-3 mt-4 mb-2">Sistema</li>
                    <li>
                        <a href="logout.php" class="text-danger">
                            <i class="menu-icon fa fa-power-off mr-2 text-danger"></i>Cerrar Sesion
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Right Content Panel -->
    <div id="right-panel" class="right-panel">
        <!-- Header Topbar -->
        <header id="header" class="header bg-white border-bottom py-2 px-4 d-flex justify-content-between align-items-center">
            <div class="top-left d-flex align-items-center">
                <a id="menuToggle" class="menutoggle mr-3 text-secondary" role="button">
                    <i class="fa fa-bars"></i>
                </a>
                <span class="badge badge-success px-2 py-1 mr-2"><i class="fa fa-circle mr-1"></i>En linea</span>
                <span class="text-muted small d-none d-md-inline">WooCommerce Micro-Admin Conectado</span>
            </div>
            <div class="top-right d-flex align-items-center">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle text-dark font-weight-medium d-flex align-items-center" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="user-avatar rounded-circle mr-2" src="images/admin.jpg" alt="Avatar" onerror="this.src='images/avatar/1.jpg'">
                        <span class="d-none d-md-inline"><?=e($currentUser['displayName'])?></span>
                    </a>
                    <div class="user-menu dropdown-menu dropdown-menu-right shadow-sm border-0">
                        <h6 class="dropdown-header"><?=e($currentUser['email'] ?: $currentUser['username'])?></h6>
                        <a class="dropdown-item" href="dashboard.php"><i class="fa fa-cube mr-2"></i>Productos</a>
                        <a class="dropdown-item" href="transactions.php"><i class="fa fa-shopping-cart mr-2"></i>Pedidos</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-power-off mr-2"></i>Cerrar Sesion</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content py-4 px-4">
            <div class="animated fadeIn">
