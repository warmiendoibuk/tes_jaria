<?php
// C:\xampp\htdocs\jaria\admin\manage_hero.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "CMS Kelola Lengkap Beranda — Admin JARIA Craft";
$upload_dir = __DIR__ . '/../assets/images/banners/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$error = '';

$available_pages = [
    'catalog.php' => 'Katalog Produk',
    'about.php'   => 'Kisah Kami',
    'impact.php'  => 'Dampak Sosial',
    'contact.php' => 'Kontak',
    'faq.php'     => 'FAQ',
    'cart.php'    => 'Keranjang Belanja'
];

$available_icons = [
    'bi-flower1'        => 'Bunga (Bros)',
    'bi-key-fill'       => 'Kunci (Gantung Kunci)',
    'bi-bag-heart-fill' => 'Tas / Pouch',
    'bi-stars'          => 'Bintang / Karya Lainnya',
    'bi-scissors'       => 'Gunting / Kerajinan',
    'bi-heart-fill'     => 'Hati / Gift',
    'bi-gem'            => 'Aksesoris / Permata',
    'bi-gift-fill'      => 'Kado / Souvenir'
];

// FUNGSI AUTO-REORDER 1, 2, 3...
function reorder_slides($pdo) {
    $stmt = $pdo->query("SELECT id FROM hero_slides ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 1;
    $up = $pdo->prepare("UPDATE hero_slides SET sort_order = ? WHERE id = ?");
    foreach ($items as $it) { $up->execute([$n++, $it['id']]); }
}

function reorder_categories($pdo) {
    $stmt = $pdo->query("SELECT id FROM home_categories ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 1;
    $up = $pdo->prepare("UPDATE home_categories SET sort_order = ? WHERE id = ?");
    foreach ($items as $it) { $up->execute([$n++, $it['id']]); }
}

// PROSES REORDER HERO SLIDE (NAIK/TURUN)
if (isset($_GET['reorder_slide']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    reorder_slides($pdo);

    $curr = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $curr->execute([$id]);
    $c = $curr->fetch(PDO::FETCH_ASSOC);

    if ($c) {
        $curr_o = (int)$c['sort_order'];
        $tgt = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM hero_slides WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM hero_slides WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $tgt->execute([$curr_o]);
        $t = $tgt->fetch(PDO::FETCH_ASSOC);

        if ($t) {
            $pdo->prepare("UPDATE hero_slides SET sort_order = ? WHERE id = ?")->execute([$t['sort_order'], $c['id']]);
            $pdo->prepare("UPDATE hero_slides SET sort_order = ? WHERE id = ?")->execute([$curr_o, $t['id']]);
            reorder_slides($pdo);
            $message = "Urutan banner beranda berhasil digeser!";
        }
    }
}

// PROSES REORDER KATEGORI (NAIK/TURUN)
if (isset($_GET['reorder_cat']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    reorder_categories($pdo);

    $curr = $pdo->prepare("SELECT * FROM home_categories WHERE id = ?");
    $curr->execute([$id]);
    $c = $curr->fetch(PDO::FETCH_ASSOC);

    if ($c) {
        $curr_o = (int)$c['sort_order'];
        $tgt = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM home_categories WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM home_categories WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $tgt->execute([$curr_o]);
        $t = $tgt->fetch(PDO::FETCH_ASSOC);

        if ($t) {
            $pdo->prepare("UPDATE home_categories SET sort_order = ? WHERE id = ?")->execute([$t['sort_order'], $c['id']]);
            $pdo->prepare("UPDATE home_categories SET sort_order = ? WHERE id = ?")->execute([$curr_o, $t['id']]);
            reorder_categories($pdo);
            $message = "Urutan kartu kategori berhasil digeser!";
        }
    }
}

// CRUD HERO BANNER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_slide'])) {
    $action = $_POST['action_slide'];
    $slide_id = (int)($_POST['slide_id'] ?? 0);
    
    $badge_text_1 = trim($_POST['badge_text_1'] ?? '');
    $badge_text_2 = trim($_POST['badge_text_2'] ?? '');
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $btn_text_1   = trim($_POST['btn_text_1'] ?? '');
    $btn_link_1   = trim($_POST['btn_link_1'] ?? 'catalog.php');
    $btn_text_2   = trim($_POST['btn_text_2'] ?? '');
    $btn_link_2   = trim($_POST['btn_link_2'] ?? 'about.php');
    $sort_order   = (int)($_POST['sort_order'] ?? 1);
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    if ($action === 'save') {
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $image_name = 'hero_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
            }
        }

        if ($slide_id > 0) {
            $pdo->prepare("UPDATE hero_slides SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $slide_id]);
            if (!empty($image_name)) {
                $stmt = $pdo->prepare("UPDATE hero_slides SET image=?, badge_text_1=?, badge_text_2=?, title=?, description=?, btn_text_1=?, btn_link_1=?, btn_text_2=?, btn_link_2=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$image_name, $badge_text_1, $badge_text_2, $title, $description, $btn_text_1, $btn_link_1, $btn_text_2, $btn_link_2, $sort_order, $is_active, $slide_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE hero_slides SET badge_text_1=?, badge_text_2=?, title=?, description=?, btn_text_1=?, btn_link_1=?, btn_text_2=?, btn_link_2=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$badge_text_1, $badge_text_2, $title, $description, $btn_text_1, $btn_link_1, $btn_text_2, $btn_link_2, $sort_order, $is_active, $slide_id]);
            }
            $message = "Slide banner berhasil diperbarui!";
        } else {
            if (empty($image_name)) { $image_name = 'banners1.jpg'; }
            $pdo->prepare("UPDATE hero_slides SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
            $stmt = $pdo->prepare("INSERT INTO hero_slides (image, badge_text_1, badge_text_2, title, description, btn_text_1, btn_link_1, btn_text_2, btn_link_2, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$image_name, $badge_text_1, $badge_text_2, $title, $description, $btn_text_1, $btn_link_1, $btn_text_2, $btn_link_2, $sort_order, $is_active]);
            $message = "Slide banner baru berhasil ditambahkan!";
        }
        reorder_slides($pdo);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
        $stmt->execute([$slide_id]);
        reorder_slides($pdo);
        $message = "Slide banner berhasil dihapus!";
    }
}

// CRUD KATEGORI BERANDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_category'])) {
    $action = $_POST['action_category'];
    $cat_id = (int)($_POST['cat_id'] ?? 0);
    $title  = trim($_POST['cat_title'] ?? '');
    $keyword= trim($_POST['category_keyword'] ?? '');
    $icon   = trim($_POST['icon_class'] ?? 'bi-stars');
    $order  = (int)($_POST['cat_sort_order'] ?? 1);
    $active = isset($_POST['cat_is_active']) ? 1 : 0;

    if ($action === 'save') {
        if ($cat_id > 0) {
            $stmt = $pdo->prepare("UPDATE home_categories SET title=?, category_keyword=?, icon_class=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $keyword, $icon, $order, $active, $cat_id]);
            $message = "Kartu Kategori berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO home_categories (title, category_keyword, icon_class, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $keyword, $icon, $order, $active]);
            $message = "Kartu Kategori baru berhasil ditambahkan!";
        }
        reorder_categories($pdo);
    } elseif ($action === 'delete') {
        $stmt =$pdo->prepare("DELETE FROM home_categories WHERE id = ?");
        $stmt->execute([$cat_id]);
        reorder_categories($pdo);$message = "Kartu Kategori berhasil dihapus!";
    }
}

reorder_slides($pdo);
reorder_categories($pdo);

$slides     =$pdo->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories =$pdo->query("SELECT * FROM home_categories ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$next_slide_order = count($slides) + 1;
$next_cat_order   = count($categories) + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .banner-thumb { width: 110px; height: 60px; object-fit: cover; border-radius: 8px; }
        .btn-back-dashboard { border: 1px solid #6c757d; color: #495057; background: transparent; font-weight: 600; padding: 0.45rem 1.25rem; transition: all 0.2s; }
        .btn-back-dashboard:hover { background: #6c757d; color: #fff; }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-sliders me-2"></i>Kelola Lengkap Konten Beranda (CMS)</h3>
            <p class="text-muted small mb-0">Atur Hero Banner Carousel & Kartu Kategori Produk Secara Dinamis</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-back-dashboard rounded-pill">&leftarrow; Kembali ke Dashboard</a>
            <a href="../index.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
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

    <!-- 1. SECTION HERO BANNER CAROUSEL -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>1. Carousel Banner Header Beranda</h5>
            <button class="btn btn-light text-primary btn-sm rounded-pill fw-bold" onclick="openAddSlideModal(<?php echo $next_slide_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Slide Banner
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Urutan</th>
                            <th width="130">Foto Banner</th>
                            <th>Judul Banner & Deskripsi</th>
                            <th>Badges / Label</th>
                            <th width="90">Status</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slides)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada slide banner beranda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($slides as$s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $s['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_slide=1&id=<?php echo $s['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_slide=1&id=<?php echo $s['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td><img src="../assets/images/banners/<?php echo htmlspecialchars($s['image']); ?>" class="banner-thumb shadow-sm" onerror="this.src='https://via.placeholder.com/110x60?text=No+Image';"></td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($s['title']); ?></strong>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;"><?php echo htmlspecialchars($s['description']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['badge_text_1'])): ?><span class="badge bg-warning text-dark me-1"><?php echo htmlspecialchars($s['badge_text_1']); ?></span><?php endif; ?>
                                        <?php if (!empty($s['badge_text_2'])): ?><span class="badge bg-secondary"><?php echo htmlspecialchars($s['badge_text_2']); ?></span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $s['is_active'] ? '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Aktif</span>' : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1 rounded-pill">Nonaktif</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editSlide(<?php echo json_encode($s); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus slide banner ini?')">
                                            <input type="hidden" name="action_slide" value="delete">
                                            <input type="hidden" name="slide_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash-fill"></i></button>
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

    <!-- 2. SECTION KARTU KATEGORI BERANDA -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-grid-fill me-2"></i>2. Kartu Pilihan Kategori Produk Beranda</h5>
            <button class="btn btn-primary btn-sm rounded-pill fw-bold" onclick="openAddCatModal(<?php echo $next_cat_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Kategori Baru
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Urutan</th>
                            <th width="70">Ikon</th>
                            <th>Judul Kategori (Di Kartu)</th>
                            <th>Keyword Filter Katalog (`category=...`)</th>
                            <th width="90">Status</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada kategori beranda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as$cat): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $cat['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_cat=1&id=<?php echo $cat['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_cat=1&id=<?php echo $cat['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bg-primary bg-opacity-10 text-primary p-2 text-center rounded-3 fs-5">
                                            <i class="bi <?php echo htmlspecialchars($cat['icon_class']); ?>"></i>
                                        </div>
                                    </td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($cat['title']); ?></strong></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($cat['category_keyword']); ?></span></td>
                                    <td>
                                        <?php echo $cat['is_active'] ? '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Aktif</span>' : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1 rounded-pill">Nonaktif</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editCategory(<?php echo json_encode($cat); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori beranda ini?')">
                                            <input type="hidden" name="action_category" value="delete">
                                            <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash-fill"></i></button>
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

<!-- MODAL FORM BANNER CAROUSEL -->
<div class="modal fade" id="slideModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_slide" value="save">
            <input type="hidden" name="slide_id" id="modal_slide_id" value="0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Slide Banner</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Judul Banner <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modal_title" class="form-control" placeholder="Sentuhan Karya Jahit Ceria" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Urutan Tampil</label>
                        <input type="number" name="sort_order" id="modal_sort_order" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Label Kiri (Badge Kuning)</label>
                        <input type="text" name="badge_text_1" id="modal_badge_1" class="form-control" placeholder="Wirausaha Sosial Pemberdayaan">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Label Kanan (Badge Transparan)</label>
                        <input type="text" name="badge_text_2" id="modal_badge_2" class="form-control" placeholder="Ilkom E1 S5 UINSA × Panti Asuhan Al-Jihad">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Singkat</label>
                        <textarea name="description" id="modal_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tulisan Tombol Utama</label>
                        <input type="text" name="btn_text_1" id="modal_btn_text_1" class="form-control" placeholder="Jelajahi Katalog">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tujuan Halaman (Tombol 1)</label>
                        <select name="btn_link_1" id="modal_btn_link_1" class="form-select">
                            <?php foreach ($available_pages as $file =>$label): ?>
                                <option value="<?php echo $file; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tulisan Tombol Sekunder</label>
                        <input type="text" name="btn_text_2" id="modal_btn_text_2" class="form-control" placeholder="Kisah Kami &rarr;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tujuan Halaman (Tombol 2)</label>
                        <select name="btn_link_2" id="modal_btn_link_2" class="form-select">
                            <?php foreach ($available_pages as $file =>$label): ?>
                                <option value="<?php echo $file; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Foto Banner</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4 d-flex align-items-center pt-3">
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="is_active" id="modal_is_active" value="1" checked>
                            <label class="form-check-label fs-6 fw-bold" for="modal_is_active">Aktifkan Banner</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Banner</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL FORM KATEGORI BERANDA -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_category" value="save">
            <input type="hidden" name="cat_id" id="cat_id" value="0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="catModalTitle">Tambah Kategori Beranda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul Kartu Kategori</label>
                    <input type="text" name="cat_title" id="cat_title" class="form-control" placeholder="Contoh: Bros Cantik" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Keyword Filter Katalog (`category=...`)</label>
                    <input type="text" name="category_keyword" id="category_keyword" class="form-control" placeholder="Contoh: Bros / Keychain / Sling Bag" required>
                    <small class="text-muted">Harus sesuai dengan nama kategori di database produk.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Ikon</label>
                    <select name="icon_class" id="icon_class" class="form-select fw-semibold">
                        <?php foreach ($available_icons as $icon_code =>$icon_name): ?>
                            <option value="<?php echo $icon_code; ?>"><?php echo $icon_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="cat_sort_order" id="cat_sort_order" class="form-control" required>
                </div>
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input" type="checkbox" name="cat_is_active" id="cat_is_active" value="1" checked>
                    <label class="form-check-label fs-6 fw-bold" for="cat_is_active">Tampilkan di Beranda</label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddSlideModal(nextOrder) {
    document.getElementById('modalTitle').innerText = 'Tambah Slide Banner Beranda';
    document.getElementById('modal_slide_id').value = 0;
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_sort_order').value = nextOrder;
    document.getElementById('modal_badge_1').value = '';
    document.getElementById('modal_badge_2').value = '';
    document.getElementById('modal_description').value = '';
    document.getElementById('modal_btn_text_1').value = '';
    document.getElementById('modal_btn_link_1').value = 'catalog.php';
    document.getElementById('modal_btn_text_2').value = '';
    document.getElementById('modal_btn_link_2').value = 'about.php';
    document.getElementById('modal_is_active').checked = true;
    new bootstrap.Modal(document.getElementById('slideModal')).show();
}

function editSlide(s) {
    document.getElementById('modalTitle').innerText = 'Edit Slide Banner Beranda';
    document.getElementById('modal_slide_id').value = s.id;
    document.getElementById('modal_title').value = s.title;
    document.getElementById('modal_sort_order').value = s.sort_order;
    document.getElementById('modal_badge_1').value = s.badge_text_1;
    document.getElementById('modal_badge_2').value = s.badge_text_2;
    document.getElementById('modal_description').value = s.description;
    document.getElementById('modal_btn_text_1').value = s.btn_text_1;
    document.getElementById('modal_btn_link_1').value = s.btn_link_1 || 'catalog.php';
    document.getElementById('modal_btn_text_2').value = s.btn_text_2;
    document.getElementById('modal_btn_link_2').value = s.btn_link_2 || 'about.php';
    document.getElementById('modal_is_active').checked = (parseInt(s.is_active) === 1);
    new bootstrap.Modal(document.getElementById('slideModal')).show();
}

function openAddCatModal(nextOrder) {
    document.getElementById('catModalTitle').innerText = 'Tambah Kategori Beranda';
    document.getElementById('cat_id').value = 0;
    document.getElementById('cat_title').value = '';
    document.getElementById('category_keyword').value = '';
    document.getElementById('icon_class').value = 'bi-stars';
    document.getElementById('cat_sort_order').value = nextOrder;
    document.getElementById('cat_is_active').checked = true;
    new bootstrap.Modal(document.getElementById('catModal')).show();
}

function editCategory(c) {
    document.getElementById('catModalTitle').innerText = 'Edit Kategori Beranda';
    document.getElementById('cat_id').value = c.id;
    document.getElementById('cat_title').value = c.title;
    document.getElementById('category_keyword').value = c.category_keyword;
    document.getElementById('icon_class').value = c.icon_class;
    document.getElementById('cat_sort_order').value = c.sort_order;
    document.getElementById('cat_is_active').checked = (parseInt(c.is_active) === 1);
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
</script>
</body>
</html>