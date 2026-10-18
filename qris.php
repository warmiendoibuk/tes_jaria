<?php
// C:\xampp\htdocs\jaria\qris.php
$page_title = "Pembayaran QRIS — JARIA Craft";

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings_helper.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: catalog.php");
    exit;
}

// Fetch Data Pesanan dari Database
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: catalog.php");
        exit;
    }

    // Fetch Item Pesanan
    $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :oid");
    $stmt_items->execute(['oid' => $order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header("Location: catalog.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Ambil Setting QRIS & Nomor WA secara Dinamis dari Database
$site_qris      = get_site_setting('site_qris', 'assets/images/qris.png');
$qris_account   = get_site_setting('qris_account_name', 'JARIA CRAFT');
$footer_phone   = get_site_setting('footer_phone', '+62 812-3456-7890');
$clean_wa_phone = preg_replace('/[^0-9]/', '', $footer_phone);

if (empty($clean_wa_phone)) {
    $clean_wa_phone = "6281234567890";
}

// Format Pesan WhatsApp Konfirmasi
$wa_message = "Halo Admin JARIA Craft, saya ingin konfirmasi pembayaran QRIS untuk Pesanan #ORD-" . str_pad($order['id'], 4, '0', STR_PAD_LEFT) . " atas nama " . $order['customer_name'] . " sebesar " . format_rupiah($order['total_amount']) . ".";
$wa_link    = "https://wa.me/" . $clean_wa_phone . "?text=" . urlencode($wa_message);
?>

<div class="container py-5" style="max-width: 750px;">
    <div class="text-center mb-4">
        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill fw-bold mb-2">
            <i class="bi bi-check-circle me-1"></i> Pesanan Berhasil Dibuat
        </span>
        <h3 class="fw-bold text-dark">Selesaikan Pembayaran QRIS</h3>
        <p class="text-secondary">Scan kode QRIS di bawah ini menggunakan GoPay, OVO, Dana, ShopeePay, atau Mobile Banking pilihan Anda.</p>
    </div>

    <div class="card border-0 shadow-lg rounded-4 p-4" style="background: #ffffff;">
        <div class="row align-items-center gy-4">
            <!-- TAMPILAN GAMBAR QRIS DINAMIS -->
            <div class="col-md-6 text-center">
                <div class="p-3 bg-light rounded-4 border d-inline-block shadow-sm">
                    <img src="<?php echo htmlspecialchars($site_qris); ?>" class="img-fluid rounded-3" style="max-width: 250px;" alt="QRIS JARIA Craft" onerror="this.src='assets/images/qris.png'">
                    <div class="mt-2 text-center">
                        <small class="fw-bold text-dark d-block"><?php echo htmlspecialchars($qris_account); ?></small>
                        <small class="text-muted" style="font-size: 0.75rem;">NMS: ID10293847561</small>
                    </div>
                </div>
            </div>

            <!-- DETAIL BUKTI & INFORMASI NOMINAL -->
            <div class="col-md-6">
                <div class="mb-3">
                    <small class="text-muted d-block">Kode Transaksi</small>
                    <h5 class="fw-bold text-dark">#ORD-<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></h5>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Nama Pemesan</small>
                    <strong class="text-dark fs-6"><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                </div>

                <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 mb-4">
                    <small class="text-primary fw-bold d-block">TOTAL PEMBAYARAN</small>
                    <h3 class="fw-bold text-primary mb-0"><?php echo format_rupiah($order['total_amount']); ?></h3>
                </div>

                <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-lg w-100 rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-whatsapp me-2"></i> Konfirmasi ke WhatsApp Admin
                </a>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-house me-1"></i> Kembali ke Beranda
            </a>
            <small class="text-muted"><i class="bi bi-shield-check text-success me-1"></i> Pembayaran Aman & Langsung Terverifikasi</small>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>