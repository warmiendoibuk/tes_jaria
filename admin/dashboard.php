<?php
// C:\xampp\htdocs\jaria\admin\dashboard.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Dashboard Utama — JARIA Admin";

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ambil Ringkasan Statistik dari Database
try {
    // 1. Total Produk & Stok Rendah
    $stmt_prod = $pdo->query("SELECT COUNT(*) as total_prod, SUM(CASE WHEN stock <= 3 THEN 1 ELSE 0 END) as low_stock FROM products WHERE is_active = 1");
    $stat_prod = $stmt_prod->fetch();

    // 2. Total Pesanan Pending
    $stmt_orders = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE payment_status = 'Pending' OR payment_status = 'pending'");
    $stat_orders = $stmt_orders->fetch();

    // 3. Total Pemasukan Lunas
    $stmt_income = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE payment_status IN ('Paid', 'paid', 'Lunas', 'lunas')");
    $stat_income = $stmt_income->fetch();

    // 4. Ambil 10 Pesanan Terbaru
    $sql_recent = "SELECT o.*, 
                          (SELECT GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') 
                           FROM order_items oi2 
                           JOIN products p2 ON oi2.product_id = p2.id 
                           JOIN categories c ON p2.category_id = c.id 
                           WHERE oi2.order_id = o.id) as category_names,
                          (SELECT GROUP_CONCAT(CONCAT(p3.name, ' (x', oi3.quantity, ')') SEPARATOR '<br>') 
                           FROM order_items oi3 
                           JOIN products p3 ON oi3.product_id = p3.id 
                           WHERE oi3.order_id = o.id) as product_details
                   FROM orders o
                   ORDER BY o.id DESC LIMIT 10";
                    
    $stmt_recent = $pdo->query($sql_recent);
    $recent_orders = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $stat_prod = ['total_prod' => 0, 'low_stock' => 0];
    $stat_orders = ['pending_orders' => 0];
    $stat_income = ['total_revenue' => 0];
    $recent_orders = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc; 
            color: #0f172a; 
            overflow-x: hidden;
        }

        .content-container { 
            max-width: 1320px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem; 
            animation: fadeInPage 0.4s ease-out forwards;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes cardPopIn {
            0% { opacity: 0; transform: translateY(25px) scale(0.92); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-stat {
            animation: cardPopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease, filter 0.35s ease !important;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .card-stat-1 { animation-delay: 0.05s; }
        .card-stat-2 { animation-delay: 0.12s; }
        .card-stat-3 { animation-delay: 0.19s; }
        .card-stat-4 { animation-delay: 0.26s; }

        .card-stat:hover {
            transform: translateY(-8px) scale(1.02) !important;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18) !important;
            filter: brightness(1.05);
        }

        .stat-icon-wrapper i {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
            display: inline-block;
        }
        .card-stat:hover .stat-icon-wrapper i {
            transform: scale(1.25) rotate(-8deg);
            opacity: 0.85 !important;
        }

        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f1f5f9 !important; }

        .btn { transition: all 0.25s ease; }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* STYLE PERBAIKAN: TOMBOL BERUBAH WARNA ABU-ABU GELAP SAAT DIKLIK/DIPENCET */
        .btn-back-main {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            transition: background-color 0.1s ease, color 0.1s ease !important;
        }
        .btn-back-main:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
        }
        .btn-back-main:active, 
        .btn-back-main:focus,
        .btn-back-main:focus-visible {
            background-color: #6c757d !important;
            color: #ffffff !important;
            border-color: #5a6268 !important;
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.4) !important;
        }

        @media (max-width: 768px) {
            .content-container { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body>

<!-- KONTEN UTAMA TANPA SIDEBAR -->
<div class="content-container">
    <!-- HEADER HALAMAN DENGAN NAVIGASI DUA TOMBOL -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-grid-1x2-fill fs-5"></i>
            </div>
            <div>
                <h2 class="fw-extrabold text-primary mb-0" style="font-size: 1.65rem;">Dashboard Ringkasan</h2>
                <p class="text-secondary small mb-0">Ringkasan statistik produk, penjualan, dan arus pesanan JARIA Craft.</p>
            </div>
        </div>
        
        <!-- TOMBOL NAVIGASI DI SEBELAH KANAN (TANPA TOMBOL KELUAR) -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="index.php" class="btn btn-back-main rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                &larr; Kembali ke Panel Utama
            </a>
            <a href="../index.php" target="_blank" class="btn btn-outline-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                <i class="bi bi-eye me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <!-- 4 KARTU STATISTIK -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stat card-stat-1 bg-primary text-white shadow-sm p-3 border-0 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 small fw-semibold mb-1">Total Produk Active</h6>
                        <h3 class="fw-extrabold mb-0"><?php echo (int)($stat_prod['total_prod'] ?? 0); ?> Items</h3>
                    </div>
                    <div class="stat-icon-wrapper"><i class="bi bi-box-seam display-6 opacity-50"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stat card-stat-2 bg-warning text-dark shadow-sm p-3 border-0 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark-50 small fw-bold mb-1">Pesanan Pending</h6>
                        <h3 class="fw-extrabold mb-0"><?php echo (int)($stat_orders['pending_orders'] ?? 0); ?> Order</h3>
                    </div>
                    <div class="stat-icon-wrapper"><i class="bi bi-clock-history display-6 opacity-50 text-dark"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stat card-stat-3 bg-success text-white shadow-sm p-3 border-0 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 small fw-semibold mb-1">Total Pendapatan</h6>
                        <h3 class="fw-extrabold mb-0"><?php echo function_exists('format_rupiah') ? format_rupiah($stat_income['total_revenue'] ?? 0) : 'Rp ' . number_format($stat_income['total_revenue'] ?? 0, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="stat-icon-wrapper"><i class="bi bi-cash-stack display-6 opacity-50"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stat card-stat-4 bg-danger text-white shadow-sm p-3 border-0 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 small fw-semibold mb-1">Stok Menipis (&lt;=3)</h6>
                        <h3 class="fw-extrabold mb-0"><?php echo (int)($stat_prod['low_stock'] ?? 0); ?> Produk</h3>
                    </div>
                    <div class="stat-icon-wrapper"><i class="bi bi-exclamation-triangle display-6 opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL PESANAN TERBARU -->
    <div class="bg-white rounded-4 shadow-sm p-3 p-md-4 border mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Pesanan Masuk Terbaru</h5>
                <span class="badge bg-primary rounded-pill px-3 py-1" id="badgeCountRecent"><?php echo count($recent_orders); ?> Data Ditemukan</span>
            </div>
            
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                <div class="input-group input-group-sm" style="min-width: 280px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableRecent" placeholder="Cari pemesan, kode, produk..." onkeyup="filterRecentTable()">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="pb-3 fw-bold text-center" style="width: 50px;">No</th>
                        <th class="pb-3 fw-bold">Kode Order</th>
                        <th class="pb-3 fw-bold">Nama Pemesan</th>
                        <th class="pb-3 fw-bold">No. WhatsApp</th>
                        <th class="pb-3 fw-bold">Jenis Produk</th>
                        <th class="pb-3 fw-bold">Nama Produk Dipesan</th>
                        <th class="pb-3 fw-bold">Total</th>
                        <th class="pb-3 fw-bold text-center">Status Bayar</th>
                        <th class="pb-3 fw-bold text-end">Tanggal</th>
                    </tr>
                </thead>
                <tbody id="tableRecentBody">
                    <?php if (!empty($recent_orders)): ?>
                        <?php $no = 1; foreach ($recent_orders as $ord): 
                            $ord_code = $ord['order_code'] ?? ($ord['order_number'] ?? 'ORD-' . $ord['id']);
                            $phone    = $ord['whatsapp'] ?? ($ord['customer_phone'] ?? ($ord['phone'] ?? ($ord['no_hp'] ?? '08123435325')));
                            $st       = strtolower($ord['payment_status'] ?? 'pending');
                            $cat      = !empty($ord['category_names']) ? $ord['category_names'] : 'Kerajinan';
                            $prods    = !empty($ord['product_details']) ? $ord['product_details'] : '-';
                        ?>
                            <tr class="border-bottom">
                                <td class="py-3 text-center fw-bold text-secondary"><?php echo $no++; ?></td>
                                <td class="py-3"><strong class="text-primary">#<?php echo htmlspecialchars($ord_code); ?></strong></td>
                                <td class="py-3 fw-semibold text-dark"><?php echo htmlspecialchars($ord['customer_name'] ?? '-'); ?></td>
                                <td class="py-3">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $phone); ?>" target="_blank" class="text-success text-decoration-none fw-bold small">
                                        <i class="bi bi-whatsapp me-1"></i><?php echo htmlspecialchars($phone); ?>
                                    </a>
                                </td>
                                <td class="py-3"><span class="badge bg-light text-secondary border rounded-pill px-2 py-1 fw-bold"><?php echo htmlspecialchars($cat); ?></span></td>
                                <td class="py-3 small text-dark fw-medium"><?php echo $prods; ?></td>
                                <td class="py-3 fw-bold text-dark"><?php echo function_exists('format_rupiah') ? format_rupiah($ord['total_amount'] ?? 0) : 'Rp ' . number_format($ord['total_amount'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="py-3 text-center">
                                    <?php if (in_array($st, ['paid', 'lunas'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-end small text-secondary"><?php echo function_exists('format_tanggal') ? format_tanggal($ord['created_at'] ?? date('Y-m-d')) : date('d M Y', strtotime($ord['created_at'] ?? date('Y-m-d'))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Belum ada transaksi pesanan masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterRecentTable() {
    const filter = document.getElementById('searchTableRecent').value.toLowerCase();
    const tbody = document.getElementById('tableRecentBody');
    const rows = tbody.getElementsByTagName('tr');
    let visibleCount = 0;
    let totalRows = 0;

    for (let i = 0; i < rows.length; i++) {
        if (rows[i].getElementsByTagName('td').length <= 1) continue;
        
        totalRows++;
        const text = rows[i].textContent || rows[i].innerText;
        
        if (text.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
            visibleCount++;
        } else {
            rows[i].style.display = "none";
        }
    }

    const badgeElem = document.getElementById('badgeCountRecent');
    if (badgeElem) {
        if (filter.trim() === '') {
            badgeElem.innerText = totalRows + ' Data Ditemukan';
        } else {
            badgeElem.innerText = 'Menampilkan ' + visibleCount + ' dari ' + totalRows + ' Data';
        }
    }
}
</script>
</body>
</html>