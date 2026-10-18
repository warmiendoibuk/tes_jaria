<?php
// C:\xampp\htdocs\jaria\admin\products.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Kelola Produk — JARIA Admin";

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$message = '';
$message_type = '';

// 1. ACTION: DELETE PRODUK
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt_del = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = :id");
        $stmt_del->execute(['id' => $delete_id]);
        $message = "Produk berhasil dinonaktifkan dari katalog.";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Gagal menghapus produk: " . $e->getMessage();
        $message_type = "danger";
    }
}

function upload_product_image($file_key) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES[$file_key]['tmp_name'];
        $file_name  = $_FILES[$file_key]['name'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext= ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
            $upload_dir    = dirname(__DIR__) . '/assets/images/products/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                return $new_file_name;
            }
        }
    }
    return null;
}

// 2. ACTION: ADD / EDIT PRODUK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $name         = sanitize($_POST['name'] ?? '');
    $price        = (float)($_POST['price'] ?? 0);
    $stock        = (int)($_POST['stock'] ?? 0);
    $description  = sanitize($_POST['description'] ?? '');
    $material     = sanitize($_POST['material'] ?? '');
    $size         = sanitize($_POST['size'] ?? '');
    $weight       = (int)($_POST['weight'] ?? 0);
    $video_url    = sanitize($_POST['video_url'] ?? '');

    $img1 = sanitize($_POST['existing_image1'] ?? 'default-product.jpg');
    $img2 = sanitize($_POST['existing_image2'] ?? '');
    $img3 = sanitize($_POST['existing_image3'] ?? '');

    $uploaded_img1 = upload_product_image('product_image1');
    if ($uploaded_img1) $img1 = $uploaded_img1;

    $uploaded_img2 = upload_product_image('product_image2');
    if ($uploaded_img2) $img2 = $uploaded_img2;

    $uploaded_img3 = upload_product_image('product_image3');
    if ($uploaded_img3) $img3 = $uploaded_img3;

    $clean_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    if (empty($clean_slug)) $clean_slug = 'product';
    $slug = $clean_slug . '-' . time();

    if (!empty($name) && $price > 0 && $category_id > 0) {
        try {
            if ($product_id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET 
                                        category_id = :cat, name = :name, slug = :slug, price = :price, 
                                        stock = :stock, description = :desc, material = :mat, 
                                        size = :size, weight = :weight, image = :img1, image2 = :img2, image3 = :img3, video_url = :vid 
                                       WHERE id = :id");
                $stmt->execute([
                    'cat'   => $category_id,
                    'name'  => $name,
                    'slug'  => $slug,
                    'price' => $price,
                    'stock' => $stock,
                    'desc'  => $description,
                    'mat'   => $material,
                    'size'  => $size,
                    'weight'=> $weight,
                    'img1'  => $img1,
                    'img2'  => $img2,
                    'img3'  => $img3,
                    'vid'   => $video_url,
                    'id'    => $product_id
                ]);
                $message = "Data produk <strong>{$name}</strong> berhasil diperbarui!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, price, stock, description, material, size, weight, image, image2, image3, video_url, is_active) 
                                       VALUES (:cat, :name, :slug, :price, :stock, :desc, :mat, :size, :weight, :img1, :img2, :img3, :vid, 1)");
                $stmt->execute([
                    'cat'   => $category_id,
                    'name'  => $name,
                    'slug'  => $slug,
                    'price' => $price,
                    'stock' => $stock,
                    'desc'  => $description,
                    'mat'   => $material,
                    'size'  => $size,
                    'weight'=> $weight,
                    'img1'  => $img1,
                    'img2'  => $img2,
                    'img3'  => $img3,
                    'vid'   => $video_url
                ]);
                $message = "Produk baru <strong>{$name}</strong> berhasil ditambahkan!";
            }
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Terjadi kesalahan: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Mohon lengkapi data wajib (Nama, Kategori, Harga).";
        $message_type = "warning";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$products   = $pdo->query("SELECT p.*, c.name as category_name 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.is_active = 1 
                           ORDER BY p.id DESC")->fetchAll();
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
            0% { opacity: 0; transform: translateY(20px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .animated-card {
            animation: cardPopIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            transition: transform 0.35s ease, box-shadow 0.35s ease !important;
        }
        .animated-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08) !important;
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

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            transform: scale(0.95) translateY(-20px);
            opacity: 0;
        }
        .modal.show .modal-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
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
                <i class="bi bi-box-seam fs-5"></i>
            </div>
            <div>
                <h2 class="fw-extrabold text-primary mb-0" style="font-size: 1.65rem;">Manajemen Produk Kerajinan</h2>
                <p class="text-secondary small mb-0">Kelola item produk, stok, foto carousel, dan tautan video showcase.</p>
            </div>
        </div>
        
        <!-- POSISI TOMBOL NAVIGASI DI SEBELAH KANAN -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="index.php" class="btn btn-back-main rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                &larr; Kembali ke Panel Utama
            </a>
            <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABEL PRODUK -->
    <div class="bg-white rounded-4 shadow-sm border p-3 p-md-4 animated-card">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" id="badgeCountProducts"><?php echo count($products); ?> Produk Ditemukan</span>
            </div>
            <div class="table-search-box w-100 w-md-auto" style="min-width: 250px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableProducts" placeholder="Cari nama produk, kategori..." onkeyup="filterProducts()">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="pb-3 fw-bold">Foto Utama</th>
                        <th class="pb-3 fw-bold">Nama Produk</th>
                        <th class="pb-3 fw-bold">Kategori</th>
                        <th class="pb-3 fw-bold">Harga</th>
                        <th class="pb-3 fw-bold text-center">Stok</th>
                        <th class="pb-3 fw-bold text-center">Media Video</th>
                        <th class="pb-3 fw-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableProductsBody">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <tr class="border-bottom">
                                <td style="width: 70px;">
                                    <?php $img_path = dirname(__DIR__) . "/assets/images/products/" . ($p['image'] ?: 'default-product.jpg'); ?>
                                    <img src="<?php echo file_exists($img_path) ? '../assets/images/products/' . htmlspecialchars($p['image']) : '../assets/images/products/default-product.jpg'; ?>" class="rounded-3 border object-fit-cover" width="52" height="52" alt="Produk">
                                </td>
                                <td class="py-3">
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <small class="text-muted d-block text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars(substr($p['description'], 0, 40)) . '...'; ?></small>
                                </td>
                                <td class="py-3"><span class="badge bg-light text-secondary border rounded-pill px-3 py-1.5 fw-semibold"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                                <td class="py-3 fw-bold text-primary"><?php echo function_exists('format_rupiah') ? format_rupiah($p['price']) : 'Rp ' . number_format($p['price'], 0, ',', '.'); ?></td>
                                <td class="py-3 text-center">
                                    <?php if ((int)$p['stock'] === 0): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold">Stok Habis</span>
                                    <?php elseif ((int)$p['stock'] <= 3): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold"><?php echo (int)$p['stock']; ?> pcs</span>
                                    <?php else: ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1.5 fw-bold"><?php echo (int)$p['stock']; ?> pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if (!empty($p['video_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($p['video_url']); ?>" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold text-nowrap"><i class="bi bi-play-circle me-1"></i>Lihat Video</a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-1.5" onclick="editProduct(<?php echo htmlspecialchars(json_encode($p)); ?>)" title="Edit Produk">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <a href="products.php?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1.5" onclick="return confirm('Hapus produk ini?')" title="Hapus Produk">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data produk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FORM PRODUK (FULL RESPONSIVE GRID) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="products.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white p-3 px-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <input type="hidden" name="save_product" value="1">
                    <input type="hidden" name="product_id" id="form_product_id" value="0">
                    <input type="hidden" name="existing_image1" id="form_existing_image1" value="default-product.jpg">
                    <input type="hidden" name="existing_image2" id="form_existing_image2" value="">
                    <input type="hidden" name="existing_image3" id="form_existing_image3" value="">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold small">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="form_name" class="form-control rounded-3" required placeholder="Contoh: Keychain Perca Batik">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" id="form_category_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Harga Rp <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="form_price" class="form-control rounded-3 fw-bold" required min="0" placeholder="15000">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Jumlah Stok <span class="text-danger">*</span></label>
                            <input type="number" name="stock" id="form_stock" class="form-control rounded-3" required min="0" placeholder="10">
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <h6 class="fw-bold text-dark mb-2 small"><i class="bi bi-images me-1 text-primary"></i>Foto Produk Carousel (Maksimal 3 Foto)</h6>
                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Foto Utama (Foto 1)</label>
                                <input type="file" name="product_image1" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Foto 2 (Pendukung)</label>
                                <input type="file" name="product_image2" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Foto 3 (Detail)</label>
                                <input type="file" name="product_image3" class="form-control form-control-sm" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">URL Video Showcase (YouTube / Instagram / TikTok Link)</label>
                        <input type="url" name="video_url" id="form_video_url" class="form-control rounded-3" placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Bahan Kain</label>
                            <input type="text" name="material" id="form_material" class="form-control rounded-3" placeholder="Perca Katun">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Ukuran Dimensi</label>
                            <input type="text" name="size" id="form_size" class="form-control rounded-3" placeholder="7 cm x 5 cm">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Berat (gram)</label>
                            <input type="number" name="weight" id="form_weight" class="form-control rounded-3" placeholder="30">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi Lengkap Produk</label>
                        <textarea name="description" id="form_description" class="form-control rounded-3" rows="3" placeholder="Jelaskan keunikan kerajinan ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Tambah Produk Baru';
    document.getElementById('form_product_id').value = '0';
    document.getElementById('form_existing_image1').value = 'default-product.jpg';
    document.getElementById('form_existing_image2').value = '';
    document.getElementById('form_existing_image3').value = '';
    document.getElementById('form_name').value = '';
    document.getElementById('form_category_id').value = '';
    document.getElementById('form_price').value = '';
    document.getElementById('form_stock').value = '';
    document.getElementById('form_material').value = '';
    document.getElementById('form_size').value = '';
    document.getElementById('form_weight').value = '';
    document.getElementById('form_description').value = '';
    document.getElementById('form_video_url').value = '';
}

function editProduct(product) {
    document.getElementById('modalTitle').innerText = 'Edit Produk: ' + product.name;
    document.getElementById('form_product_id').value = product.id;
    document.getElementById('form_existing_image1').value = product.image || 'default-product.jpg';
    document.getElementById('form_existing_image2').value = product.image2 || '';
    document.getElementById('form_existing_image3').value = product.image3 || '';
    document.getElementById('form_name').value = product.name;
    document.getElementById('form_category_id').value = product.category_id;
    document.getElementById('form_price').value = product.price;
    document.getElementById('form_stock').value = product.stock;
    document.getElementById('form_material').value = product.material || '';
    document.getElementById('form_size').value = product.size || '';
    document.getElementById('form_weight').value = product.weight || '';
    document.getElementById('form_description').value = product.description || '';
    document.getElementById('form_video_url').value = product.video_url || '';

    var myModal = new bootstrap.Modal(document.getElementById('productModal'));
    myModal.show();
}

function filterProducts() {
    const input = document.getElementById('searchTableProducts');
    const filter = input.value.toLowerCase().trim();
    const tbody = document.getElementById('tableProductsBody');
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

    const badge = document.getElementById('badgeCountProducts');
    if (badge) {
        if (filter === '') {
            badge.innerText = totalRows + ' Produk Ditemukan';
        } else {
            badge.innerText = 'Menampilkan ' + visibleCount + ' dari ' + totalRows + ' Produk';
        }
    }
}
</script>
</body>
</html>