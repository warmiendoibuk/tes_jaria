<?php
// C:\xampp\htdocs\jaria\checkout.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Checkout — JARIA Craft";

// Ambil Keranjang Belanja dari Session
$raw_cart = $_SESSION['cart'] ?? [];

if (empty($raw_cart)) {
    header("Location: catalog.php");
    exit;
}

// AMBIL DETAIL PRODUK DARI DATABASE BERDASARKAN ID DI KERANJANG
$cart_items = [];
$total_amount = 0;

foreach ($raw_cart as $product_id => $item) {
    $qty = is_array($item) ? (int)($item['quantity'] ?? 1) : (int)$item;
    $p_id = (int)$product_id;

    if ($p_id > 0 && $qty > 0) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $p_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $subtotal = (float)$product['price'] * $qty;
                $total_amount += $subtotal;
                
                $cart_items[] = [
                    'id'       => $product['id'],
                    'name'     => $product['name'],
                    'price'    => (float)$product['price'],
                    'qty'      => $qty,
                    'subtotal' => $subtotal
                ];
            }
        } catch (PDOException $e) {
            // Abaikan jika ID tidak valid
        }
    }
}

if (empty($cart_items)) {
    unset($_SESSION['cart']);
    header("Location: catalog.php");
    exit;
}

$error_msg = '';
$success_order_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $whatsapp      = sanitize($_POST['whatsapp'] ?? '');

    if (!empty($customer_name) && !empty($whatsapp)) {
        try {
            $pdo->beginTransaction();

            // Buat Kode Unik Order (Contoh: ORD-20261008-X1Y2)
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));

            // DETEKSI KOLOM DI TABEL ORDERS
            $all_columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);

            // Tentukan Kolom Kode Order
            $insert_cols = [];
            $insert_vals = [];
            $params = [
                ':name'  => $customer_name,
                ':total' => $total_amount
            ];

            // Isi order_code & order_number sekaligus jika ada
            if (in_array('order_code', $all_columns)) {
                $insert_cols[] = 'order_code';
                $insert_vals[] = ':order_code';
                $params[':order_code'] = $order_number;
            }
            if (in_array('order_number', $all_columns)) {
                $insert_cols[] = 'order_number';
                $insert_vals[] = ':order_number';
                $params[':order_number'] = $order_number;
            }

            // Tentukan Kolom Nama
            $insert_cols[] = 'customer_name';
            $insert_vals[] = ':name';

            // Tentukan Kolom WhatsApp / Phone
            $wa_col = 'whatsapp';
            if (in_array('whatsapp', $all_columns)) {
                $wa_col = 'whatsapp';
            } elseif (in_array('phone', $all_columns)) {
                $wa_col = 'phone';
            } elseif (in_array('phone_number', $all_columns)) {
                $wa_col = 'phone_number';
            } elseif (in_array('no_hp', $all_columns)) {
                $wa_col = 'no_hp';
            }

            $insert_cols[] = $wa_col;
            $insert_vals[] = ':wa';
            $params[':wa'] = $whatsapp;

            // Total Amount
            $insert_cols[] = 'total_amount';
            $insert_vals[] = ':total';

            // Status Pembayaran (Langsung Paid/Lunas)
            $insert_cols[] = 'payment_status';
            $insert_vals[] = "'paid'";

            // Created At
            $insert_cols[] = 'created_at';
            $insert_vals[] = "NOW()";

            // EKSEKUSI QUERY INSERT ADAPTIF
            $sql = "INSERT INTO orders (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $insert_vals) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $order_id = $pdo->lastInsertId();

            // Simpan Detail Item Pesanan
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (:order_id, :product_id, :product_name, :price, :qty)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - :qty) WHERE id = :pid");

            foreach ($cart_items as $item) {
                $stmt_item->execute([
                    'order_id'     => $order_id,
                    'product_id'   => $item['id'],
                    'product_name' => $item['name'],
                    'price'        => $item['price'],
                    'qty'          => $item['qty']
                ]);

                // Potong Stok Produk
                $stmt_stock->execute(['qty' => $item['qty'], 'pid' => $item['id']]);
            }

            // Catat Pemasukan Kas Otomatis ke Laporan Keuangan
            $stmt_cash = $pdo->prepare("INSERT INTO cash_transactions (type, category, amount, description, transaction_date, created_at) VALUES ('income', 'Penjualan Produk', :amount, :desc, CURDATE(), NOW())");
            $stmt_cash->execute([
                'amount' => $total_amount,
                'desc'   => "Penjualan Otomatis " . $order_number . " (" . $customer_name . ")"
            ]);

            $pdo->commit();

            // Kosongkan Keranjang Belanja
            unset($_SESSION['cart']);

            $success_order_number = $order_number;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Gagal memproses pesanan: " . $e->getMessage();
        }
    } else {
        $error_msg = "Mohon lengkapi Nama Lengkap dan Nomor WhatsApp Anda.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfbf7; }
        .qris-box {
            border: 2px dashed #0d6efd;
            background: #f8fafd;
            border-radius: 1rem;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            
            <?php if (!empty($success_order_number)): ?>
                <!-- NOTIFIKASI SUKSES SETELAH PEMBAYARAN -->
                <div class="card border-0 shadow-lg rounded-4 text-center p-5 bg-white">
                    <div class="mb-3 text-success">
                        <i class="bi bi-check-circle-fill display-1"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Pesanan Berhasil Dibuat!</h3>
                    <p class="text-muted">Kode Pesanan: <strong class="text-primary"><?php echo $success_order_number; ?></strong></p>
                    <p class="small text-muted mb-4">Pembayaran Anda telah terverifikasi <strong class="text-success">Paid (Lunas)</strong>. Tim JARIA Craft akan segera memproses pesanan Anda.</p>
                    <div>
                        <a href="catalog.php" class="btn btn-primary rounded-pill px-4 fw-bold">Kembali Belanja</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- FORM CHECKOUT & QRIS -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h4 class="fw-bold text-dark mb-3"><i class="bi bi-bag-check me-2 text-primary"></i>Formulir Pemesanan</h4>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger rounded-3 small mb-3"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <!-- RINGKASAN PRODUK YANG DIBELI -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Ringkasan Belanja</label>
                        <ul class="list-group list-group-flush border rounded-3 overflow-hidden">
                            <?php foreach ($cart_items as $ci): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($ci['name']); ?></span>
                                        <small class="text-muted d-block"><?php echo $ci['qty']; ?> x <?php echo format_rupiah($ci['price']); ?></small>
                                    </div>
                                    <span class="fw-bold text-primary"><?php echo format_rupiah($ci['subtotal']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- TAMPILAN QRIS PEMBAYARAN -->
                    <div class="qris-box p-4 text-center mb-4">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                            <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold">QRIS NATIONAL</span>
                            <span class="fw-bold text-dark">Panti Asuhan Al-Jihad / JARIA Craft</span>
                        </div>
                        <p class="small text-muted mb-3">Scan QR Code di bawah ini menggunakan GoPay, OVO, ShopeePay, Dana, BCA, atau Mobile Banking pilihan Anda:</p>
                        
                        <!-- Gambar QRIS -->
                        <div class="bg-white d-inline-block p-3 rounded-4 shadow-sm border mb-3">
                            <img src="assets/images/qris.png" alt="QRIS JARIA Craft" class="img-fluid rounded-3" style="max-width: 230px;" onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=JARIA_CRAFT_PANTI_AL_JIHAD';">
                        </div>

                        <div class="bg-primary bg-opacity-10 p-2.5 rounded-3 border border-primary border-opacity-25">
                            <span class="text-secondary small d-block mb-1">Total yang Harus Dibayar:</span>
                            <h3 class="fw-bold text-primary mb-0"><?php echo format_rupiah($total_amount); ?></h3>
                        </div>
                    </div>

                    <!-- FORM DATA PEMESAN -->
                    <form action="checkout.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap Pemesan</label>
                            <input type="text" name="customer_name" class="form-control rounded-3" required placeholder="Masukkan nama Anda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor WhatsApp Aktif</label>
                            <input type="text" name="whatsapp" class="form-control rounded-3" required placeholder="08xxxxxxxxxx">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold mt-2">
                            <i class="bi bi-qr-code-scan me-1"></i> Konfirmasi Sudah Bayar QRIS
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>