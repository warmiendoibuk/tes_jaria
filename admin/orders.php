<?php
// C:\xampp\htdocs\jaria\admin\orders.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title = "Pesanan Masuk — JARIA Admin";

// 1. CREATE: TAMBAH PESANAN MANUAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    $cust_name  = sanitize($_POST['customer_name'] ?? '');
    $phone      = sanitize($_POST['customer_phone'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = (int)($_POST['quantity'] ?? 1);
    $status     = sanitize($_POST['payment_status'] ?? 'pending');

    if (!empty($cust_name) && $product_id > 0 && $qty > 0) {
        try {
            $pdo->beginTransaction();

            $stmt_p = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt_p->execute(['id' => $product_id]);
            $prod = $stmt_p->fetch(PDO::FETCH_ASSOC);

            if ($prod) {
                $unit_price   = (float)$prod['price'];
                $total_amount = $unit_price * $qty;
                $order_code   = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));

                $stmt_cols = $pdo->query("SHOW COLUMNS FROM orders");
                $cols = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);
                
                $phone_col = 'customer_phone';
                if (in_array('whatsapp', $cols)) $phone_col = 'whatsapp';
                elseif (in_array('phone', $cols)) $phone_col = 'phone';
                elseif (in_array('no_hp', $cols)) $phone_col = 'no_hp';

                $sql_ins = "INSERT INTO orders (order_number, customer_name, {$phone_col}, total_amount, payment_status, created_at) 
                            VALUES (:ord_num, :cust_name, :phone, :total, :status, NOW())";
                $stmt_ord = $pdo->prepare($sql_ins);
                $stmt_ord->execute([
                    'ord_num'   => $order_code,
                    'cust_name' => $cust_name,
                    'phone'     => $phone,
                    'total'     => $total_amount,
                    'status'    => $status
                ]);
                $new_order_id = $pdo->lastInsertId();

                $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)");
                $stmt_item->execute([
                    'oid'   => $new_order_id,
                    'pid'   => $product_id,
                    'qty'   => $qty,
                    'price' => $unit_price
                ]);

                $stmt_stock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - :qty) WHERE id = :pid");
                $stmt_stock->execute(['qty' => $qty, 'pid' => $product_id]);

                if (in_array(strtolower($status), ['paid', 'lunas'])) {
                    $desc = "Penjualan Otomatis #{$order_code} ({$cust_name}) - {$prod['name']} (x{$qty})";
                    $stmt_ins_cash = $pdo->prepare("INSERT INTO cash_transactions (type, category, amount, description, transaction_date, created_at) VALUES ('income', 'Penjualan Produk', :amount, :desc, NOW(), NOW())");
                    $stmt_ins_cash->execute(['amount' => $total_amount, 'desc' => $desc]);
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Pesanan baru berhasil ditambahkan!";
            } else {
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Produk tidak ditemukan.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error_msg'] = "Gagal menambah pesanan: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Nama pemesan, produk, dan jumlah wajib diisi.";
    }
    header("Location: orders.php");
    exit;
}

// 2. UPDATE PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_order') {
    $order_id  = (int)$_POST['order_id'];
    $cust_name = sanitize($_POST['customer_name'] ?? '');
    $phone      = sanitize($_POST['customer_phone'] ?? '');
    $total_amt = (float)($_POST['total_amount'] ?? 0);
    $status    = sanitize($_POST['payment_status'] ?? 'pending');

    try {
        $stmt_cols = $pdo->query("SHOW COLUMNS FROM orders");
        $cols = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

        $update_fields = ["customer_name = :cname", "total_amount = :total", "payment_status = :status"];
        $params = [
            'cname'  => $cust_name,
            'total'  => $total_amt,
            'status' => $status,
            'id'     => $order_id
        ];

        if (in_array('whatsapp', $cols)) {
            $update_fields[] = "whatsapp = :phone_wa";
            $params['phone_wa'] = $phone;
        }
        if (in_array('customer_phone', $cols)) {
            $update_fields[] = "customer_phone = :phone_cust";
            $params['phone_cust'] = $phone;
        }
        if (in_array('phone', $cols)) {
            $update_fields[] = "phone = :phone_p";
            $params['phone_p'] = $phone;
        }
        if (in_array('no_hp', $cols)) {
            $update_fields[] = "no_hp = :phone_hp";
            $params['phone_hp'] = $phone;
        }

        $sql_upd = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = :id";
        $stmt_upd = $pdo->prepare($sql_upd);
        $stmt_upd->execute($params);

        $_SESSION['success_msg'] = "Detail pesanan berhasil diperbarui!";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal mengedit pesanan: " . $e->getMessage();
    }
    header("Location: orders.php");
    exit;
}

// 3. UPDATE STATUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status   = sanitize($_POST['payment_status']);

    try {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $order_id]);

        if (in_array(strtolower($status), ['paid', 'lunas'])) {
            $stmt_o = $pdo->prepare("SELECT o.*, GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as prods
                                     FROM orders o
                                     LEFT JOIN order_items oi ON o.id = oi.order_id
                                     LEFT JOIN products p ON oi.product_id = p.id
                                     WHERE o.id = :id GROUP BY o.id");
            $stmt_o->execute(['id' => $order_id]);
            $ord_info = $stmt_o->fetch(PDO::FETCH_ASSOC);

            if ($ord_info) {
                $ord_code = $ord_info['order_number'] ?? ($ord_info['order_code'] ?? 'ORD-'.$ord_info['id']);
                $cust     = $ord_info['customer_name'] ?? 'Pelanggan';
                $p_desc   = !empty($ord_info['prods']) ? $ord_info['prods'] : 'Produk Kerajinan';
                $desc     = "Penjualan Otomatis #{$ord_code} ({$cust}) - {$p_desc}";

                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM cash_transactions WHERE description LIKE :code");
                $stmt_check->execute(['code' => '%' . $ord_code . '%']);

                if ($stmt_check->fetchColumn() == 0) {
                    $stmt_ins = $pdo->prepare("INSERT INTO cash_transactions (type, category, amount, description, transaction_date, created_at) 
                                               VALUES ('income', 'Penjualan Produk', :amount, :desc, NOW(), NOW())");
                    $stmt_ins->execute([
                        'amount' => $ord_info['total_amount'],
                        'desc'   => $desc
                    ]);
                }
            }
        }

        $_SESSION['success_msg'] = "Status pesanan berhasil diperbarui!";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal memperbarui status: " . $e->getMessage();
    }
    header("Location: orders.php");
    exit;
}

// 4. DELETE PESANAN
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_get = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $stmt_get->execute(['id' => $del_id]);
        $order = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $pdo->beginTransaction();

            $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :id");
            $stmt_items->execute(['id' => $del_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            $stmt_restore_stock = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
            foreach ($items as $it) {
                if ($it['product_id'] > 0 && $it['quantity'] > 0) {
                    $stmt_restore_stock->execute([
                        'qty' => $it['quantity'],
                        'pid' => $it['product_id']
                    ]);
                }
            }

            $stmt_del_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = :id");
            $stmt_del_items->execute(['id' => $del_id]);

            $ord_num = $order['order_number'] ?? ($order['order_code'] ?? '');
            if (!empty($ord_num)) {
                $stmt_del_cash = $pdo->prepare("DELETE FROM cash_transactions WHERE description LIKE :ord_pattern");
                $stmt_del_cash->execute(['ord_pattern' => '%' . $ord_num . '%']);
            }

            $stmt_del_ord = $pdo->prepare("DELETE FROM orders WHERE id = :id");
            $stmt_del_ord->execute(['id' => $del_id]);

            $pdo->commit();
            $_SESSION['success_msg'] = "Pesanan berhasil dihapus dan stok produk telah dikembalikan!";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = "Gagal menghapus pesanan: " . $e->getMessage();
    }
    header("Location: orders.php");
    exit;
}

// AMBIL DAFTAR PESANAN
$orders = [];
try {
    $sql = "SELECT o.*, 
                   GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_names,
                   GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR '<br>') as product_details
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            GROUP BY o.id
            ORDER BY o.created_at DESC";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$all_products = [];
try {
    $stmt_p_all = $pdo->query("SELECT id, name, price, stock FROM products WHERE is_active = 1 AND stock > 0 ORDER BY name ASC");
    $all_products = $stmt_p_all->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
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
            min-height: 100vh;
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
            0% { opacity: 0; transform: translateY(20px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .animated-card {
            animation: cardPopIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            transition: transform 0.35s ease, box-shadow 0.35s ease !important;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f1f5f9 !important; }

        .btn { transition: all 0.25s ease; }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* EFEK BERUBAH WARNA ABU-ABU GELAP SAAT DIKLIK/DIPENCET */
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
    <!-- HEADER NAVIGASI APER-NAV DENGAN TOMBOL KEMBALI DI SEBELAH KANAN -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-cart-check fs-5"></i>
            </div>
            <div>
                <h2 class="fw-extrabold text-primary mb-0" style="font-size: 1.65rem;">Daftar Pesanan Masuk</h2>
                <p class="text-secondary small mb-0">Kelola dan verifikasi pesanan yang masuk dari pelanggan.</p>
            </div>
        </div>
        
        <!-- POSISI NAVIGASI DI KANAN HEADER -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="index.php" class="btn btn-back-main rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                &larr; Kembali ke Panel Utama
            </a>
            <button type="button" class="btn btn-primary rounded-pill fw-bold px-3.5 py-2 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCreateOrder">
                <i class="bi bi-plus-circle me-1"></i> Tambah Pesanan
            </button>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABEL UTAMA PESANAN -->
    <div class="bg-white rounded-4 shadow-sm border p-3 p-md-4 animated-card">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold fs-6 shadow-sm" id="badgeCountOrders">
                    Total: <?php echo count($orders); ?> Pesanan
                </span>
            </div>
            <div class="table-search-box w-100 w-md-auto" style="max-width: 350px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableOrders" placeholder="Cari ID order, nama, wa, produk..." onkeyup="filterOrders()">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border-0 mb-0" style="min-width: 1000px;">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="pb-3 fw-bold text-nowrap">ID Order</th>
                        <th class="pb-3 fw-bold text-nowrap">Nama Pemesan</th>
                        <th class="pb-3 fw-bold text-nowrap">No. WhatsApp</th>
                        <th class="pb-3 fw-bold text-nowrap">Jenis Produk</th>
                        <th class="pb-3 fw-bold text-nowrap">Nama Produk Dipesan</th>
                        <th class="pb-3 fw-bold text-nowrap">Total Bayar</th>
                        <th class="pb-3 fw-bold text-nowrap">Tanggal</th>
                        <th class="pb-3 fw-bold text-center text-nowrap">Status Pembayaran</th>
                        <th class="pb-3 fw-bold text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableOrdersBody">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): 
                            $st = strtolower($o['payment_status'] ?? 'pending');
                            $ord_code = $o['order_number'] ?? ($o['order_code'] ?? 'ORD-'.$o['id']);
                            
                            $wa = $o['whatsapp'] ?? ($o['customer_phone'] ?? ($o['phone'] ?? ($o['no_hp'] ?? '')));
                            if (empty($wa) || $wa === '-') {
                                $wa = '08123435325';
                            }

                            $cat   = !empty($o['category_names']) ? $o['category_names'] : 'Kerajinan';
                            $prods = !empty($o['product_details']) ? $o['product_details'] : '-';
                        ?>
                            <tr class="border-bottom">
                                <td class="py-3">
                                    <strong class="text-primary fs-6 text-nowrap">#<?php echo htmlspecialchars($ord_code); ?></strong>
                                </td>
                                <td class="py-3 fw-semibold text-dark text-nowrap"><?php echo htmlspecialchars($o['customer_name'] ?? '-'); ?></td>
                                <td class="py-3 text-nowrap">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa); ?>" target="_blank" class="text-success text-decoration-none fw-bold small">
                                        <i class="bi bi-whatsapp me-1"></i><?php echo htmlspecialchars($wa); ?>
                                    </a>
                                </td>
                                <td class="py-3"><span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 fw-bold text-nowrap"><?php echo htmlspecialchars($cat); ?></span></td>
                                <td class="py-3 small text-dark fw-medium"><?php echo $prods; ?></td>
                                <td class="py-3 fw-extrabold text-dark text-nowrap">
                                    <?php echo function_exists('format_rupiah') ? format_rupiah($o['total_amount']) : 'Rp ' . number_format($o['total_amount'], 0, ',', '.'); ?>
                                </td>
                                <td class="py-3 small text-secondary text-nowrap">
                                    <?php echo !empty($o['created_at']) ? date('d M Y, H:i', strtotime($o['created_at'])) : '-'; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <form action="orders.php" method="POST" class="d-inline-flex">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <select name="payment_status" class="form-select form-select-sm rounded-pill fw-bold border-0 shadow-sm <?php echo in_array($st, ['paid','lunas']) ? 'bg-success text-white' : ($st === 'cancelled' ? 'bg-secondary text-white' : 'bg-warning text-dark'); ?>" onchange="this.form.submit()" style="width: 135px;">
                                            <option value="paid" <?php echo in_array($st, ['paid','lunas']) ? 'selected' : ''; ?>>Paid (Lunas)</option>
                                            <option value="pending" <?php echo $st === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="cancelled" <?php echo $st === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-3 text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-2" data-bs-toggle="modal" data-bs-target="#modalEditOrder<?php echo $o['id']; ?>" title="Edit Pesanan">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="orders.php?delete_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle p-2" onclick="return confirm('Menghapus pesanan ini akan mengembalikan STOK produk dan menghapus catatan kas. Yakin?')" title="Hapus Pesanan">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada pesanan masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT PESANAN -->
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $o): 
        $st = strtolower($o['payment_status'] ?? 'pending');
        $ord_code = $o['order_number'] ?? ($o['order_code'] ?? 'ORD-'.$o['id']);
        $wa = $o['whatsapp'] ?? ($o['customer_phone'] ?? ($o['phone'] ?? ($o['no_hp'] ?? '')));
    ?>
        <div class="modal fade" id="modalEditOrder<?php echo $o['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <form action="orders.php" method="POST">
                        <input type="hidden" name="action" value="edit_order">
                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                        <div class="modal-header bg-primary text-white p-3 px-4">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pesanan #<?php echo htmlspecialchars($ord_code); ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-3 p-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Nama Pemesan</label>
                                <input type="text" name="customer_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($o['customer_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">No. WhatsApp</label>
                                <input type="text" name="customer_phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($wa); ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold small text-secondary">Total Bayar (Rp)</label>
                                    <input type="number" name="total_amount" class="form-control rounded-3 fw-bold" value="<?php echo (float)$o['total_amount']; ?>" required min="0">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold small text-secondary">Status Pembayaran</label>
                                    <select name="payment_status" class="form-select rounded-3 fw-semibold">
                                        <option value="paid" <?php echo in_array($st, ['paid','lunas']) ? 'selected' : ''; ?>>Paid (Lunas)</option>
                                        <option value="pending" <?php echo $st === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="cancelled" <?php echo $st === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- MODAL TAMBAH PESANAN MANUAL -->
<div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="orders.php" method="POST">
                <input type="hidden" name="action" value="create_order">
                <div class="modal-header bg-primary text-white p-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Pesanan Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Nama Pemesan <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control rounded-3" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">No. WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="customer_phone" class="form-control rounded-3" placeholder="Contoh: 08123456789" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select rounded-3" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($all_products as $prod_item): ?>
                                <option value="<?php echo $prod_item['id']; ?>">
                                    <?php echo htmlspecialchars($prod_item['name']); ?> — Rp <?php echo number_format($prod_item['price'], 0, ',', '.'); ?> (Stok: <?php echo $prod_item['stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jumlah Dipesan <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control rounded-3" value="1" min="1" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small text-secondary">Status Pembayaran</label>
                            <select name="payment_status" class="form-select rounded-3 fw-semibold">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid (Lunas)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>Buat Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterOrders() {
    const input = document.getElementById('searchTableOrders');
    const filter = input.value.toLowerCase().trim();
    const tbody = document.getElementById('tableOrdersBody');
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

    const badge = document.getElementById('badgeCountOrders');
    if (badge) {
        if (filter === '') {
            badge.innerText = 'Total: ' + totalRows + ' Pesanan';
        } else {
            badge.innerText = 'Menampilkan ' + visibleCount + ' dari ' + totalRows + ' Pesanan';
        }
    }
}
</script>
</body>
</html>