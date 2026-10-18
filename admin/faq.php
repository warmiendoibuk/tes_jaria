<?php
// C:\xampp\htdocs\jaria\admin\faq.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$upload_dir = __DIR__ . '/../assets/images/banners/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// DAFTAR IKON POPULER UNTUK PILIHAN RAMAH USER
$available_icons = [
    'bi-question-circle-fill' => 'Tanda Tanya / Umum',
    'bi-bag-heart-fill'        => 'Tas / Produk & Bahan',
    'bi-cart-check-fill'       => 'Keranjang / Pemesanan',
    'bi-heart-pulse-fill'      => 'Hati / Dampak Sosial',
    'bi-truck'                 => 'Truk / Pengiriman',
    'bi-scissors'              => 'Gunting / Custom Order',
    'bi-credit-card-fill'      => 'Kartu / Pembayaran',
    'bi-shield-check'          => 'Perisai / Garansi & Keamanan',
    'bi-chat-dots-fill'        => 'Pesan / Bantuan Kontak'
];

// FUNGSI AUTO RE-ORDER AGAR ANGKANYA SANGAT RAPI (1, 2, 3...) TANPA BENTROK
function sanitize_and_reorder_faq_banners($pdo) {
    $stmt = $pdo->query("SELECT id FROM faq_banners ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $new_order = 1;
    $upStmt = $pdo->prepare("UPDATE faq_banners SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $upStmt->execute([$new_order, $item['id']]);
        $new_order++;
    }
}

function sanitize_and_reorder_faqs($pdo) {
    $stmt = $pdo->query("SELECT id FROM faqs ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $new_order = 1;
    $upStmt = $pdo->prepare("UPDATE faqs SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $upStmt->execute([$new_order, $item['id']]);
        $new_order++;
    }
}

// FITUR REORDER BANNERS (NAIK/TURUN)
if (isset($_GET['reorder_banner']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    sanitize_and_reorder_faq_banners($pdo);
    
    $curr = $pdo->prepare("SELECT * FROM faq_banners WHERE id = ?");
    $curr->execute([$id]);
    $curr_item = $curr->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        $target = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM faq_banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM faq_banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE faq_banners SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE faq_banners SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            sanitize_and_reorder_faq_banners($pdo);
            $message = "Urutan banner berhasil digeser!";
        }
    }
}

// FITUR REORDER FAQS (NAIK/TURUN)
if (isset($_GET['reorder_faq']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    sanitize_and_reorder_faqs($pdo);
    
    $curr = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
    $curr->execute([$id]);
    $curr_item = $curr->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        $target = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM faqs WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM faqs WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE faqs SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE faqs SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            sanitize_and_reorder_faqs($pdo);
            $message = "Urutan pertanyaan FAQ berhasil digeser!";
        }
    }
}

// CRUD BANNER CAROUSEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_banner'])) {
    $action = $_POST['action_banner'];

    if ($action === 'create') {
        $badge_text  = trim($_POST['badge_text']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $sort_order  = (int)$_POST['sort_order'];
        $image_path  = 'assets/images/banners/banners1.jpg';

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'faq_banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                }
            }
        }

        $pdo->prepare("UPDATE faq_banners SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
        $stmt = $pdo->prepare("INSERT INTO faq_banners (image_path, badge_text, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order]);
        
        sanitize_and_reorder_faq_banners($pdo);
        $message = "Banner Carousel FAQ berhasil ditambahkan!";
    }

    if ($action === 'update') {
        $id          = (int)$_POST['id'];
        $badge_text  = trim($_POST['badge_text']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $sort_order  = (int)$_POST['sort_order'];

        $pdo->prepare("UPDATE faq_banners SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $id]);

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'faq_banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                    $stmt = $pdo->prepare("UPDATE faq_banners SET image_path = ?, badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order, $id]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE faq_banners SET badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$badge_text, $title, $description, $sort_order, $id]);
        }
        
        sanitize_and_reorder_faq_banners($pdo);
        $message = "Banner Carousel FAQ berhasil diperbarui!";
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM faq_banners WHERE id = ?");
        $stmt->execute([$id]);
        
        sanitize_and_reorder_faq_banners($pdo);
        $message = "Banner Carousel FAQ berhasil dihapus!";
    }
}

// CRUD FAQS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_faq'])) {
    $action = $_POST['action_faq'];

    if ($action === 'create' || $action === 'update') {
        $category   = trim($_POST['category']);
        $icon       = trim($_POST['icon']);
        $color      = trim($_POST['color']);
        $question   = trim($_POST['question']);
        $answer     = trim($_POST['answer']);
        $sort_order = (int)$_POST['sort_order'];

        if ($action === 'create') {
            $pdo->prepare("UPDATE faqs SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
            $stmt = $pdo->prepare("INSERT INTO faqs (category, icon, color, question, answer, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category, $icon, $color, $question, $answer, $sort_order]);
            $message = "Pertanyaan FAQ berhasil ditambahkan!";
        } else {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE faqs SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $id]);
            $stmt = $pdo->prepare("UPDATE faqs SET category = ?, icon = ?, color = ?, question = ?, answer = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$category, $icon, $color, $question, $answer, $sort_order, $id]);
            $message = "Pertanyaan FAQ berhasil diperbarui!";
        }
        
        sanitize_and_reorder_faqs($pdo);
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->execute([$id]);
        
        sanitize_and_reorder_faqs($pdo);
        $message = "Pertanyaan FAQ berhasil dihapus!";
    }
}

// RAPIKAN SEKALI SETIAP KALI DIBUKA
sanitize_and_reorder_faq_banners($pdo);
sanitize_and_reorder_faqs($pdo);

$banners = $pdo->query("SELECT * FROM faq_banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$faqs_list = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$next_banner_order = count($banners) + 1;
$next_faq_order = count($faqs_list) + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola FAQ — CMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .banner-thumb { width: 120px; height: 65px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-question-circle-fill me-2"></i>Kelola Halaman FAQ (CMS)</h3>
            <p class="text-muted small mb-0">Atur Carousel Banner Header & Daftar Pertanyaan FAQ Sering Diajukan</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary rounded-pill fw-semibold">&larr; Kembali ke Dashboard</a>
            <a href="../faq.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-eye-fill me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECTION CAROUSEL BANNER HEADER -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>Carousel Banner Header FAQ</h5>
            <button class="btn btn-light text-primary btn-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddBanner" onclick="document.getElementById('add_banner_sort').value='<?php echo $next_banner_order; ?>'">
                <i class="bi bi-plus-circle me-1"></i>Tambah Slide Banner
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Urutan</th>
                            <th width="140">Foto Banner</th>
                            <th>Badge & Judul</th>
                            <th>Deskripsi Tekstual</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada slide banner.</td></tr>
                        <?php else: ?>
                            <?php foreach ($banners as $b): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $b['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td><img src="../<?php echo htmlspecialchars($b['image_path']); ?>" class="banner-thumb shadow-sm"></td>
                                    <td>
                                        <span class="badge bg-warning text-dark small mb-1"><?php echo htmlspecialchars($b['badge_text']); ?></span>
                                        <div class="fw-bold"><?php echo htmlspecialchars($b['title']); ?></div>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($b['description']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editBanner(<?php echo json_encode($b); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus slide banner ini?')">
                                            <input type="hidden" name="action_banner" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash-fill"></i></button>
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

    <!-- SECTION DAFTAR FAQ -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-list-task me-2"></i>Daftar Pertanyaan FAQ</h5>
            <button class="btn btn-primary btn-sm rounded-pill fw-bold" onclick="openAddFaqModal(<?php echo $next_faq_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Pertanyaan
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Urutan</th>
                            <th width="70">Ikon</th>
                            <th>Kategori</th>
                            <th>Pertanyaan</th>
                            <th>Jawaban</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faqs_list)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada daftar pertanyaan FAQ.</td></tr>
                        <?php else: ?>
                            <?php foreach ($faqs_list as $f): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $f['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_faq=1&id=<?php echo $f['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_faq=1&id=<?php echo $f['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bg-<?php echo $f['color']; ?> text-white p-2 text-center rounded-3 fs-6">
                                            <i class="bi <?php echo $f['icon']; ?>"></i>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-primary border"><?php echo htmlspecialchars($f['category']); ?></span></td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($f['question']); ?></strong></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($f['answer']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editFaq(<?php echo json_encode($f); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pertanyaan FAQ ini?')">
                                            <input type="hidden" name="action_faq" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash-fill"></i></button>
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
</div>

<!-- MODAL TAMBAH BANNER -->
<div class="modal fade" id="modalAddBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_banner" value="create">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tambah Slide Banner Carousel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Foto Banner</label>
                    <input type="file" name="banner_image" class="form-control" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Badge Teks (Kecil di Atas)</label>
                    <input type="text" name="badge_text" class="form-control" value="Bantuan & Informasi">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul Banner</label>
                    <input type="text" name="title" class="form-control" value="Pertanyaan Sering Diajukan" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Deskripsi Tekstual</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="sort_order" id="add_banner_sort" class="form-control" value="<?php echo $next_banner_order; ?>">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Slide</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT BANNER -->
<div class="modal fade" id="modalEditBanner" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_banner" value="update">
            <input type="hidden" name="id" id="edit_banner_id">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">Edit Slide Banner Carousel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Ganti Foto Banner (Opsional)</label>
                    <input type="file" name="banner_image" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Badge Teks</label>
                    <input type="text" name="badge_text" id="edit_banner_badge" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul Banner</label>
                    <input type="text" name="title" id="edit_banner_title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Deskripsi Tekstual</label>
                    <textarea name="description" id="edit_banner_desc" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="sort_order" id="edit_banner_sort" class="form-control">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Perbarui Banner</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH & EDIT PERTANYAAN FAQ -->
<div class="modal fade" id="modalAddFaq" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_faq" id="faq_action" value="create">
            <input type="hidden" name="id" id="faq_id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="faq_modal_title">Tambah Pertanyaan FAQ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kategori FAQ</label>
                        <input type="text" name="category" id="faq_category" class="form-control" placeholder="Contoh: Pemesanan / Produk" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                        <input type="number" name="sort_order" id="faq_sort" class="form-control" value="1">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold">Pilih Ikon Pertanyaan</label>
                        <select name="icon" id="faq_icon" class="form-select fw-semibold">
                            <?php foreach ($available_icons as $icon_class => $icon_label): ?>
                                <option value="<?php echo $icon_class; ?>"><?php echo htmlspecialchars($icon_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Warna Tema Ikon</label>
                        <select name="color" id="faq_color" class="form-select">
                            <option value="primary">Primary (Biru)</option>
                            <option value="success">Success (Hijau)</option>
                            <option value="warning">Warning (Kuning/Oranye)</option>
                            <option value="info">Info (Biru Muda)</option>
                            <option value="danger">Danger (Merah)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Pertanyaan FAQ</label>
                        <input type="text" name="question" id="faq_question" class="form-control" placeholder="Tuliskan pertanyaan yang sering diajukan..." required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Jawaban FAQ</label>
                        <textarea name="answer" id="faq_answer" class="form-control" rows="4" placeholder="Tuliskan penjelasan jawaban secara ramah..." required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan FAQ</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editBanner(b) {
    document.getElementById('edit_banner_id').value = b.id;
    document.getElementById('edit_banner_badge').value = b.badge_text;
    document.getElementById('edit_banner_title').value = b.title;
    document.getElementById('edit_banner_desc').value = b.description;
    document.getElementById('edit_banner_sort').value = b.sort_order;
    new bootstrap.Modal(document.getElementById('modalEditBanner')).show();
}

function openAddFaqModal(nextOrder) {
    document.getElementById('faq_action').value = 'create';
    document.getElementById('faq_id').value = '';
    document.getElementById('faq_category').value = '';
    document.getElementById('faq_question').value = '';
    document.getElementById('faq_answer').value = '';
    document.getElementById('faq_sort').value = nextOrder || 1;
    document.getElementById('faq_modal_title').innerText = 'Tambah Pertanyaan FAQ';
    new bootstrap.Modal(document.getElementById('modalAddFaq')).show();
}

function editFaq(f) {
    document.getElementById('faq_action').value = 'update';
    document.getElementById('faq_id').value = f.id;
    document.getElementById('faq_category').value = f.category;
    document.getElementById('faq_icon').value = f.icon;
    document.getElementById('faq_color').value = f.color;
    document.getElementById('faq_question').value = f.question;
    document.getElementById('faq_answer').value = f.answer;
    document.getElementById('faq_sort').value = f.sort_order;
    document.getElementById('faq_modal_title').innerText = 'Edit Pertanyaan FAQ';
    new bootstrap.Modal(document.getElementById('modalAddFaq')).show();
}
</script>
</body>
</html>