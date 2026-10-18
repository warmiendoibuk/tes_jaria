<?php
// C:\xampp\htdocs\jaria\admin\includes\admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/includes/auth.php';
check_admin_login();

$page_title = isset($page_title) ? $page_title . " — Admin JARIA" : "Dashboard Admin — JARIA";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background-color: #1E3A8A; color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.75); padding: 0.75rem 1.25rem; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffffff; background-color: rgba(255, 255, 255, 0.15); border-radius: 8px; }
        .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR NAVIGASI ADMIN -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
            <div class="d-flex align-items-center mb-4 px-2">
                <span class="fs-4 fw-bold text-white me-2">JARIA</span>
                <span class="badge bg-primary text-white border">Admin</span>
            </div>
            <ul class="nav flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" href="/jaria/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'products.php') ? 'active' : ''; ?>" href="/jaria/admin/products.php">
                        <i class="bi bi-box-seam me-2"></i> Kelola Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'orders.php') ? 'active' : ''; ?>" href="/jaria/admin/orders.php">
                        <i class="bi bi-cart-check me-2"></i> Pesanan Masuk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'financials.php') ? 'active' : ''; ?>" href="/jaria/admin/financials.php">
                        <i class="bi bi-journal-text me-2"></i> Laporan Keuangan
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="/jaria/admin/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                    </a>
                </li>
            </ul>
        </nav>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
                <h1 class="h3 fw-bold text-dark"><?php echo str_replace(" — Admin JARIA", "", $page_title); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-white text-dark border p-2 shadow-sm">
                        <i class="bi bi-person-circle me-1 text-primary"></i> Login sebagai: <strong><?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin'); ?></strong>
                    </span>
                </div>
            </div>