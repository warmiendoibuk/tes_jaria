<?php
// C:\xampp\htdocs\jaria\cart.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Keranjang Belanja — JARIA Craft";

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// PROSES AKSI KERANJANG (TAMBAH / UPDATE / REMOVE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action     = $_POST['action'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = (int)($_POST['quantity'] ?? 1);

    if ($action === 'add' && $product_id > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'update' && $product_id > 0) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'remove' && $product_id > 0) {
        unset($_SESSION['cart'][$product_id]);
        header("Location: cart.php");
        exit;
    }
}

// AMBIL DATA PRODUK DI KERANJANG DARI DATABASE
$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $p) {
                $qty = (int)$_SESSION['cart'][$p['id']];
                $subtotal = $p['price'] * $qty;
                $total_price += $subtotal;

                // Cek gambar
                $img_name = !empty($p['image']) ? $p['image'] : 'default-product.jpg';
                $img_path = 'assets/images/products/' . $img_name;

                $cart_items[] = [
                    'id'       => $p['id'],
                    'name'     => $p['name'],
                    'price'    => $p['price'],
                    'stock'    => (int)($p['stock'] ?? 0),
                    'image'    => $img_path,
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];
            }
        } catch (PDOException $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfbf7; color: #2b2b2b; }
        
        /* KARTU KERANJANG MODERN DENGAN ANIMASI HOVER SINAR GLOW */
        .cart-card { 
            background: #ffffff; 
            border-radius: 1.25rem; 
            border: 1.5px solid rgba(13, 110, 253, 0.1); 
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }
        .cart-card:hover {
            transform: translateY(-4px);
            border-color: rgba(13, 110, 253, 0.35);
            box-shadow: 0 12px 28px rgba(13, 110, 253, 0.15), 0 0 18px rgba(13, 110, 253, 0.08) !important;
        }

        .product-img-thumb { 
            width: 75px; 
            height: 75px; 
            object-fit: cover; 
            border-radius: 0.85rem; 
            transition: transform 0.3s ease;
        }
        .cart-item-row:hover .product-img-thumb {
            transform: scale(1.05);
        }

        /* BADGE STOK MERAH / HIJAU PRESISI */
        .badge-stock-danger {
            background-color: #ffeef0;
            color: #dc3545;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .badge-stock-success {
            background-color: #e8f5e9;
            color: #198754;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }

        /* ANIMASI HOVER TOMBOL */
        .btn-bubble-bounce { 
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .btn-bubble-bounce:hover { 
            transform: translateY(-2px) scale(1.02); 
            box-shadow: 0 8px 18px rgba(13, 110, 253, 0.25) !important;
        }

        .cart-item-row {
            transition: background-color 0.2s ease;
        }
        .cart-item-row:hover {
            background-color: rgba(13, 110, 253, 0.015);
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5">
    <!-- TOMBOL KEMBALI KE BERANDA -->
    <div class="mb-4">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3.5 py-2 fw-bold text-decoration-none btn-bubble-bounce">
            <i class="bi bi-house-door-fill me-1"></i> Kembali ke Beranda
        </a>
    </div>

    <h2 class="display-6 fw-extrabold text-dark mb-4">
        <i class="bi bi-cart-check text-primary me-2"></i>Keranjang Belanja Anda
    </h2>

    <?php if (empty($cart_items)): ?>
        <div class="cart-card p-5 text-center my-4 shadow-sm">
            <i class="bi bi-cart-x text-muted display-1 mb-3 d-block"></i>
            <h4 class="fw-bold text-dark mb-2">Keranjang Belanja Anda Kosong</h4>
            <p class="text-secondary mb-4">Anda belum menambahkan produk kerajinan perca ke dalam keranjang.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="index.php" class="btn btn-outline-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm btn-bubble-bounce">
                    <i class="bi bi-house-door me-1"></i> Kembali ke Beranda
                </a>
                <a href="catalog.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm btn-bubble-bounce">
                    <i class="bi bi-bag-plus me-1"></i> Lihat Katalog Produk
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-card p-4 mb-4 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle border-0 mb-0">
                            <thead>
                                <tr class="text-secondary border-bottom">
                                    <th scope="col" class="pb-3 fw-bold">Produk</th>
                                    <th scope="col" class="pb-3 fw-bold">Harga</th>
                                    <th scope="col" class="pb-3 fw-bold text-center">Jumlah</th>
                                    <th scope="col" class="pb-3 fw-bold text-end">Subtotal</th>
                                    <th scope="col" class="pb-3 fw-bold text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr class="border-bottom cart-item-row">
                                        <td class="py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img-thumb" onerror="this.src='https://via.placeholder.com/80?text=JARIA';">
                                                <div>
                                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    
                                                    <!-- INDIKATOR STOK MERAH / HIJAU SESUAI BERANDA & KATALOG -->
                                                    <?php if ($item['stock'] <= 3): ?>
                                                        <span class="badge-stock-danger">
                                                            <i class="bi bi-exclamation-circle-fill"></i> Sisa <?php echo $item['stock']; ?> pcs
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge-stock-success">
                                                            <i class="bi bi-box-seam-fill"></i> Stok: <?php echo $item['stock']; ?> pcs
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 fw-semibold">
                                            Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="py-3">
                                            <form action="cart.php" method="POST" class="d-flex align-items-center justify-content-center">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control text-center rounded-pill px-2 shadow-sm" style="width: 75px;" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="py-3 text-end fw-bold text-primary fs-6">
                                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="py-3 text-end">
                                            <form action="cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0 border-0 btn-bubble-bounce" title="Hapus Barang">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="cart-card p-4 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3">Ringkasan Belanja</h5>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between mb-2 fs-6">
                        <span class="text-secondary">Total Barang</span>
                        <span class="fw-bold text-dark"><?php echo array_sum($_SESSION['cart']); ?> pcs</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-5">
                        <strong class="text-dark">Total Pembayaran</strong>
                        <strong class="text-primary fs-4">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></strong>
                    </div>
                    <a href="checkout.php" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm mb-2.5 btn-bubble-bounce">
                        Lanjut ke Pembayaran <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="catalog.php" class="btn btn-outline-secondary w-100 rounded-pill py-2.5 fw-bold btn-bubble-bounce">
                        <i class="bi bi-arrow-left me-1"></i> Tambah Produk Lain
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 border-top mt-5">
    <div class="container text-center">
        <p class="mb-1 text-white-50 small">&copy; <?php echo date('Y'); ?> <strong>JARIA Craft</strong> (Jahit Ceria) — Panti Asuhan Al-Jihad Surabaya.</p>
        <small class="text-muted">Proyek Kewirausahaan Sosial | Mahasiswa Ilmu Komunikasi E1 Semester 5 UIN Sunan Ampel Surabaya</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>