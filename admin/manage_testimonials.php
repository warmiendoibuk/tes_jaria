<?php
// C:\xampp\htdocs\jaria\admin\manage_testimonials.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit; 
}
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$message = '';
$upload_dir = dirname(__DIR__) . '/assets/images/banners/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

$page_title = "Kelola Ulasan & Testimoni — Admin JARIA";

// ==========================================
// 1. FITUR BANNER CAROUSEL TESTIMONI (CRUD & REORDER)
// ==========================================

// REORDER BANNER (NAIK / TURUN)
if (isset($_GET['reorder_banner']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    $current = $pdo->prepare("SELECT * FROM testimonial_banners WHERE id = ?");
    $current->execute([$id]);
    $curr_banner = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_banner) {
        $curr_order = (int)$curr_banner['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM testimonial_banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM testimonial_banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_banner = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_banner) {
            $pdo->prepare("UPDATE testimonial_banners SET sort_order = ? WHERE id = ?")->execute([$target_banner['sort_order'], $curr_banner['id']]);
            $pdo->prepare("UPDATE testimonial_banners SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_banner['id']]);
            $_SESSION['success_msg'] = "Urutan banner testimoni berhasil dipindahkan!";
        }
    }
    header("Location: manage_testimonials.php");
    exit;
}

// CRUD BANNER TESTIMONI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_banner'])) {
    $action = $_POST['action_banner'];

    if ($action === 'create') {
        $badge_text  = trim($_POST['badge_text']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $sort_order  = (int)$_POST['sort_order'];
        $image_path  = 'assets/images/banners/banner_testi_default.jpg';

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'banner_testi_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                }
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO testimonial_banners (image_path, badge_text, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order]);
            $_SESSION['success_msg'] = "Banner Carousel Testimoni berhasil ditambahkan!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal menambah banner: " . $e->getMessage();
        }
    }

    if ($action === 'update') {
        $id          = (int)$_POST['id'];
        $badge_text  = trim($_POST['badge_text']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $sort_order  = (int)$_POST['sort_order'];

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'banner_testi_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                    $stmt = $pdo->prepare("UPDATE testimonial_banners SET image_path = ?, badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order, $id]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE testimonial_banners SET badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$badge_text, $title, $description, $sort_order, $id]);
        }
        $_SESSION['success_msg'] = "Banner Carousel Testimoni berhasil diperbarui!";
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM testimonial_banners WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_msg'] = "Banner Carousel Testimoni berhasil dihapus!";
    }

    header("Location: manage_testimonials.php");
    exit;
}

// ==========================================
// 2. MODERASI DAFTAR ULASAN / TESTIMONI
// ==========================================

// HAPUS ULASAN
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM testimonials WHERE id = :id")->execute(['id' => $id]);
    $_SESSION['success_msg'] = "Ulasan berhasil dihapus!";
    header("Location: manage_testimonials.php");
    exit;
}

// TOGGLE TAMPIL / SEMBUNYIKAN
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE testimonials SET is_approved = NOT is_approved WHERE id = :id")->execute(['id' => $id]);
    $_SESSION['success_msg'] = "Status visibilitas ulasan berhasil diperbarui!";
    header("Location: manage_testimonials.php");
    exit;
}

// EDIT ULASAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_testimonial'])) {
    $id          = (int)$_POST['id'];
    $name        = trim($_POST['name'] ?? '');
    $role        = trim($_POST['role'] ?? '');
    $rating      = (int)($_POST['rating'] ?? 5);
    $comment     = trim($_POST['comment'] ?? '');
    $likes_count = (int)($_POST['likes_count'] ?? 0);

    if ($id > 0 && !empty($name) && !empty($comment)) {
        try {
            $stmt = $pdo->prepare("UPDATE testimonials SET name = ?, role = ?, rating = ?, comment = ?, likes_count = ? WHERE id = ?");
            $stmt->execute([$name, $role, $rating, $comment, $likes_count, $id]);
            $_SESSION['success_msg'] = "Ulasan berhasil diperbarui!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal memperbarui ulasan: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Nama dan isi ulasan wajib diisi.";
    }
    header("Location: manage_testimonials.php");
    exit;
}

// AMBIL DATA BANNER (BUAT TABEL JIKA BELUM ADA SISTEMATIS)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS testimonial_banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        badge_text VARCHAR(100) DEFAULT 'Apresiasi & Testimoni',
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        sort_order INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $banners = $pdo->query("SELECT * FROM testimonial_banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banners = [];
}

// AMBIL DATA ULASAN AUDIENS
$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY likes_count DESC, created_at DESC");
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HITUNG URUTAN OTOMATIS BERIKUTNYA UNTUK BANNER
$next_banner_order = 1;
if (!empty($banners)) {
    $max_b = max(array_column($banners, 'sort_order'));
    $next_banner_order = $max_b + 1;
}

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
            background-color: #f8f9fa; 
            color: #0f172a;
        }

        .banner-thumb { 
            width: 120px; 
            height: 65px; 
            object-fit: cover; 
            border-radius: 8px; 
        }

        /* DESAIN KUSTOM TOMBOL KEMBALI PRESISI SAMA DENGAN IMPACT.PHP */
        .btn-back-dashboard {
            border: 1px solid #6c757d;
            color: #495057;
            background-color: transparent;
            font-weight: 600;
            padding: 0.45rem 1.25rem;
            transition: all 0.2s ease;
        }
        .btn-back-dashboard:hover {
            background-color: #6c757d;
            color: #ffffff;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="py-4">

<div class="container">
    <!-- HEADER NAVIGASI ATAS (PRESISI PRESISI SAMA DENGAN IMPACT.PHP) -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-chat-quote-fill me-2"></i>Kelola Ulasan & Testimoni (CMS)</h3>
            <p class="text-muted small mb-0">Atur Carousel Banner Header Testimoni & Moderasi Ulasan Audiens secara Fleksibel</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- TOMBOL KEMBALI KE DASHBOARD UTAMA -->
            <a href="index.php" class="btn btn-back-dashboard rounded-pill">
                &leftarrow; Kembali ke Dashboard
            </a>
            <!-- TOMBOL LIHAT HALAMAN DEPAN -->
            <a href="../testimonials.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-eye-fill me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECTION 1: CAROUSEL BANNER HEADER TESTIMONI -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>Carousel Banner Header Testimoni</h5>
            <button class="btn btn-light text-primary btn-sm rounded-pill fw-bold" onclick="openAddBannerModal(<?php echo $next_banner_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Slide Banner
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="110">Urutan</th>
                            <th width="140">Foto Banner</th>
                            <th>Badge & Judul</th>
                            <th>Deskripsi Tekstual</th>
                            <th width="130" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada slide banner testimoni.</td></tr>
                        <?php else: ?>
                            <?php foreach ($banners as $b): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $b['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan Urutan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan Urutan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td><img src="../<?php echo htmlspecialchars($b['image_path']); ?>" class="banner-thumb shadow-sm" onerror="this.src='https://via.placeholder.com/120x65?text=No+Image'"></td>
                                    <td>
                                        <span class="badge bg-warning text-dark small mb-1"><?php echo htmlspecialchars($b['badge_text']); ?></span>
                                        <div class="fw-bold"><?php echo htmlspecialchars($b['title']); ?></div>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($b['description']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editBanner(<?php echo json_encode($b); ?>)' title="Edit Banner"><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus slide banner ini?')">
                                            <input type="hidden" name="action_banner" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Banner"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 2: DAFTAR ULASAN & TESTIMONI AUDIENS -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-stars me-2"></i>Daftar Ulasan & Testimoni Audiens</h5>
            <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold fs-6">
                Total: <?php echo count($list); ?> Ulasan
            </span>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th width="180">User & Status</th>
                            <th width="100">Rating</th>
                            <th>Ulasan / Komentar</th>
                            <th width="140">Tanggal</th>
                            <th width="100">Likes</th>
                            <th width="120">Status Tampil</th>
                            <th width="140" class="text-center">Aksi Moderasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($list)): ?>
                            <?php $no = 1; foreach ($list as $item): ?>
                                <tr>
                                    <td class="fw-bold text-secondary"><?php echo $no++; ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['role'] ?: 'Pelanggan'); ?></small>
                                    </td>
                                    <td class="text-warning fw-bold"><i class="bi bi-star-fill me-1"></i><?php echo $item['rating']; ?>/5</td>
                                    <td class="small text-dark" style="max-width: 280px;"><?php echo htmlspecialchars($item['comment']); ?></td>
                                    <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                    <td><span class="badge bg-danger rounded-pill px-2.5 py-1"><i class="bi bi-heart-fill me-1"></i><?php echo $item['likes_count']; ?></span></td>
                                    <td>
                                        <?php if ($item['is_approved']): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-1">Tampil</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-1">Disembunyikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <!-- Edit Icon Circle -->
                                            <button class="btn btn-sm btn-outline-warning rounded-circle" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['id']; ?>" title="Edit Ulasan">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            
                                            <!-- Toggle Visibility Circle -->
                                            <a href="?toggle=<?php echo $item['id']; ?>" class="btn btn-sm <?php echo $item['is_approved'] ? 'btn-outline-secondary' : 'btn-outline-success'; ?> rounded-circle" title="<?php echo $item['is_approved'] ? 'Sembunyikan' : 'Tampilkan'; ?>">
                                                <i class="bi <?php echo $item['is_approved'] ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i>
                                            </a>

                                            <!-- Hapus Icon Circle -->
                                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus ulasan ini?')" title="Hapus Ulasan">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada ulasan dari audiens.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH BANNER CAROUSEL -->
<div class="modal fade" id="modalAddBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0 shadow">
            <input type="hidden" name="action_banner" value="create">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Slide Banner Testimoni</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Foto Banner</label>
                    <input type="file" name="banner_image" class="form-control rounded-3" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Badge Teks (Kecil di Atas)</label>
                    <input type="text" name="badge_text" class="form-control rounded-3" value="Apresiasi & Kata Mereka">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Judul Banner</label>
                    <input type="text" name="title" class="form-control rounded-3" value="Apa Kata Mereka Tentang JARIA Craft?" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Deskripsi Tekstual</label>
                    <textarea name="description" class="form-control rounded-3" rows="3" required>Dengarkan langsung ulasan dan pengalaman berkesan para pelanggan yang mempercayakan produk kerajinan perca buatan anak panti.</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="sort_order" id="add_banner_sort" class="form-control rounded-3" value="1" required>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Slide</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT BANNER CAROUSEL -->
<div class="modal fade" id="modalEditBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0 shadow">
            <input type="hidden" name="action_banner" value="update">
            <input type="hidden" name="id" id="edit_banner_id">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Slide Banner Testimoni</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Ganti Foto Banner (Opsional)</label>
                    <input type="file" name="banner_image" class="form-control rounded-3" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Badge Teks</label>
                    <input type="text" name="badge_text" id="edit_banner_badge" class="form-control rounded-3">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Judul Banner</label>
                    <input type="text" name="title" id="edit_banner_title" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Deskripsi Tekstual</label>
                    <textarea name="description" id="edit_banner_desc" class="form-control rounded-3" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="sort_order" id="edit_banner_sort" class="form-control rounded-3">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Perbarui Banner</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT ULASAN AUDIENS -->
<?php if (!empty($list)): ?>
    <?php foreach ($list as $item): ?>
        <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <form action="manage_testimonials.php" method="POST">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Ulasan Audiens</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" name="update_testimonial" value="1">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Nama Audiens</label>
                                <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Status / Sebagai</label>
                                <input type="text" name="role" class="form-control rounded-3" value="<?php echo htmlspecialchars($item['role']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Rating</label>
                                <select name="rating" class="form-select rounded-3">
                                    <option value="5" <?php echo $item['rating'] == 5 ? 'selected' : ''; ?>>⭐ 5/5</option>
                                    <option value="4" <?php echo $item['rating'] == 4 ? 'selected' : ''; ?>>⭐ 4/5</option>
                                    <option value="3" <?php echo $item['rating'] == 3 ? 'selected' : ''; ?>>⭐ 3/5</option>
                                    <option value="2" <?php echo $item['rating'] == 2 ? 'selected' : ''; ?>>⭐ 2/5</option>
                                    <option value="1" <?php echo $item['rating'] == 1 ? 'selected' : ''; ?>>⭐ 1/5</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Ulasan / Komentar</label>
                                <textarea name="comment" class="form-control rounded-3" rows="3" required><?php echo htmlspecialchars($item['comment']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Jumlah Suka (Likes)</label>
                                <input type="number" name="likes_count" class="form-control rounded-3" value="<?php echo (int)$item['likes_count']; ?>">
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Perbarui Ulasan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddBannerModal(nextOrder) {
    document.getElementById('add_banner_sort').value = nextOrder;
    new bootstrap.Modal(document.getElementById('modalAddBanner')).show();
}

function editBanner(b) {
    document.getElementById('edit_banner_id').value = b.id;
    document.getElementById('edit_banner_badge').value = b.badge_text;
    document.getElementById('edit_banner_title').value = b.title;
    document.getElementById('edit_banner_desc').value = b.description;
    document.getElementById('edit_banner_sort').value = b.sort_order;
    new bootstrap.Modal(document.getElementById('modalEditBanner')).show();
}
</script>
</body>
</html>