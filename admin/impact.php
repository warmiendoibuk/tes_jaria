<?php
// C:\xampp\htdocs\jaria\admin\impact.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$upload_dir = __DIR__ . '/../assets/images/banners/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

$available_icons = [
    'bi-mortarboard-fill'   => 'Topi Kelulusan / Pendidikan (Beasiswa & Sekolah)',
    'bi-scissors'           => 'Gunting / Kerajinan (Pelatihan Jahit & Skill)',
    'bi-piggy-bank-fill'    => 'Celengan / Uang (Tabungan Kemandirian)',
    'bi-recycle'            => 'Daur Ulang (Tekstil / Eco-friendly)',
    'bi-rocket-takeoff-fill'=> 'Roket (Semangat / Wirausaha)',
    'bi-house-heart-fill'   => 'Panti / Rumah Cinta (Fasilitas & Tempat Tinggal)',
    'bi-heart-fill'         => 'Hati / Kebaikan (Sosial)',
    'bi-book-fill'          => 'Buku / Pembelajaran',
    'bi-award-fill'         => 'Piala / Prestasi',
    'bi-lightbulb-fill'     => 'Lampu / Ide & Kreativitas',
    'bi-cash-coin'          => 'Koin / Finansial',
    'bi-people-fill'        => 'Masyarakat / Komunitas',
    'bi-hand-thumbs-up-fill'=> 'Jempol / Dukungan',
    'bi-stars'              => 'Bintang / Harapan Masa Depan'
];

// PROSES REORDER BANNER (NAIK / TURUN)
if (isset($_GET['reorder_banner']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    $current = $pdo->prepare("SELECT * FROM impact_banners WHERE id = ?");
    $current->execute([$id]);
    $curr_banner = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_banner) {
        $curr_order = (int)$curr_banner['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM impact_banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM impact_banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_banner = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_banner) {
            $pdo->prepare("UPDATE impact_banners SET sort_order = ? WHERE id = ?")->execute([$target_banner['sort_order'], $curr_banner['id']]);
            $pdo->prepare("UPDATE impact_banners SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_banner['id']]);
            $message = "Urutan banner berhasil dipindahkan!";
        }
    }
}

// PROSES REORDER PILAR (NAIK / TURUN)
if (isset($_GET['reorder_item']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    $current = $pdo->prepare("SELECT * FROM impact_items WHERE id = ?");
    $current->execute([$id]);
    $curr_item = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM impact_items WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM impact_items WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE impact_items SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE impact_items SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            $message = "Urutan pilar dampak berhasil dipindahkan!";
        }
    }
}

// CRUD BANNER
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
                $filename = 'banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO impact_banners (image_path, badge_text, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order]);
        $message = "Banner Carousel berhasil ditambahkan!";
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
                $filename = 'banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                    $stmt = $pdo->prepare("UPDATE impact_banners SET image_path = ?, badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order, $id]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE impact_banners SET badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$badge_text, $title, $description, $sort_order, $id]);
        }
        $message = "Banner Carousel berhasil diperbarui!";
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM impact_banners WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Banner Carousel berhasil dihapus!";
    }
}

// CRUD PILAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_item'])) {
    $action = $_POST['action_item'];

    if ($action === 'create' || $action === 'update') {
        $icon       = trim($_POST['icon']);
        $color      = trim($_POST['color']);
        $title      = trim($_POST['title']);
        $short_desc = trim($_POST['short_desc']);
        $full_desc  = trim($_POST['full_desc']);
        $metrics    = trim($_POST['metrics']);
        $sort_order = (int)$_POST['sort_order'];

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO impact_items (icon, color, title, short_desc, full_desc, metrics, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$icon, $color, $title, $short_desc, $full_desc, $metrics, $sort_order]);
            $message = "Pilar Dampak Sosial berhasil ditambahkan!";
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE impact_items SET icon = ?, color = ?, title = ?, short_desc = ?, full_desc = ?, metrics = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$icon, $color, $title, $short_desc, $full_desc, $metrics, $sort_order, $id]);
            $message = "Pilar Dampak Sosial berhasil diperbarui!";
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM impact_items WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Pilar Dampak Sosial berhasil dihapus!";
    }
}

$banners = $pdo->query("SELECT * FROM impact_banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$items   = $pdo->query("SELECT * FROM impact_items ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// MENGHITUNG URUTAN OTOMATIS BERIKUTNYA
$next_banner_order = 1;
if (!empty($banners)) {
    $max_b = max(array_column($banners, 'sort_order'));
    $next_banner_order = $max_b + 1;
}

$next_item_order = 1;
if (!empty($items)) {
    $max_i = max(array_column($items, 'sort_order'));
    $next_item_order = $max_i + 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dampak Sosial — CMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .banner-thumb { width: 120px; height: 65px; object-fit: cover; border-radius: 8px; }
        .icon-preview-box {
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; border-radius: 12px;
        }
        
        /* DESAIN KUSTOM TOMBOL KEMBALI SEPERTI DI SCREENSHOT */
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
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-heart-pulse-fill me-2"></i>Kelola Dampak Sosial (CMS)</h3>
            <p class="text-muted small mb-0">Atur Carousel Banner Header & Pilar Dampak Sosial secara Fleksibel</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- TOMBOL KEMBALI KE DASHBOARD UTAMA (PANEL ADMIN) -->
            <a href="index.php" class="btn btn-back-dashboard rounded-pill">
                &leftarrow; Kembali ke Dashboard
            </a>
            <!-- TOMBOL LIHAT HALAMAN DEPAN -->
            <a href="../impact.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-eye-fill me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECTION CAROUSEL BANNER HEADER -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>Carousel Banner Header</h5>
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
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada slide banner.</td></tr>
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

    <!-- SECTION PILAR DAMPAK SOSIAL -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Pilar Dampak Sosial (Kartu & Modal Pop-Up)</h5>
            <button class="btn btn-primary btn-sm rounded-pill fw-bold" onclick="openAddItemModal(<?php echo $next_item_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Pilar Baru
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="110">Urutan</th>
                            <th width="80">Ikon</th>
                            <th>Judul Pilar</th>
                            <th>Deskripsi Singkat</th>
                            <th width="130" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pilar dampak sosial.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $item['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_item=1&id=<?php echo $item['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan Urutan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_item=1&id=<?php echo $item['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan Urutan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bg-<?php echo $item['color']; ?> text-white p-2 text-center rounded-3 fs-5">
                                            <i class="bi <?php echo $item['icon']; ?>"></i>
                                        </div>
                                    </td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($item['short_desc']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editItem(<?php echo json_encode($item); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pilar dampak ini?')">
                                            <input type="hidden" name="action_item" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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
                    <input type="text" name="badge_text" class="form-control" value="Jejak Kebaikan Pembelian Anda">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul Banner</label>
                    <input type="text" name="title" class="form-control" value="Dampak Sosial JARIA Craft" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Deskripsi Tekstual</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Urutan Tampil (*Otomatis Terisi)</label>
                    <input type="number" name="sort_order" id="add_banner_sort" class="form-control" value="1" required>
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

<!-- MODAL TAMBAH & EDIT PILAR -->
<div class="modal fade" id="modalAddItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_item" id="item_action" value="create">
            <input type="hidden" name="id" id="item_id">
            <div class="modal-header bg-dark text-white" id="item_modal_header">
                <h5 class="modal-title fw-bold" id="item_modal_title">Tambah Pilar Dampak Sosial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-bold">Pilih Gambar Ikon Program</label>
                        <select name="icon" id="item_icon" class="form-select fw-semibold" onchange="updateIconPreview()">
                            <?php foreach ($available_icons as $icon_class => $icon_label): ?>
                                <option value="<?php echo $icon_class; ?>"><?php echo htmlspecialchars($icon_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">Contoh Tampilan Ikon</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-preview-box bg-primary text-white" id="icon_preview_bg">
                                <i class="bi bi-mortarboard-fill" id="icon_preview_i"></i>
                            </div>
                            <span class="small text-muted" id="icon_preview_text">Ikon Pilihan</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Warna Tema Kartu</label>
                        <select name="color" id="item_color" class="form-select" onchange="updateIconPreview()">
                            <option value="primary">Primary (Biru)</option>
                            <option value="success">Success (Hijau)</option>
                            <option value="warning">Warning (Kuning/Oranye)</option>
                            <option value="info">Info (Biru Muda)</option>
                            <option value="danger">Danger (Merah)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Urutan Tampil (*Otomatis Terisi)</label>
                        <input type="number" name="sort_order" id="item_sort" class="form-control" value="1" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Judul Pilar / Nama Program</label>
                        <input type="text" name="title" id="item_title" class="form-control" placeholder="Contoh: Beasiswa & Perlengkapan Sekolah" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Singkat (Tampil di Kartu Depan)</label>
                        <textarea name="short_desc" id="item_short_desc" class="form-control" rows="2" placeholder="Penjelasan singkat 1-2 kalimat..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Penjelasan Lengkap (Tampil saat Kartu Diklik / Pop-up)</label>
                        <textarea name="full_desc" id="item_full_desc" class="form-control" rows="4" placeholder="Penjelasan detail cerita dampak sosial..." required></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Indikator Keberhasilan (Hasil Nyata)</label>
                        <input type="text" name="metrics" id="item_metrics" class="form-control" placeholder="Contoh: Mendukung SPP bulanan untuk 15 anak panti">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan Pilar Dampak</button>
            </div>
        </form>
    </div>
</div>

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

function openAddItemModal(nextOrder) {
    document.getElementById('item_action').value = 'create';
    document.getElementById('item_id').value = '';
    document.getElementById('item_title').value = '';
    document.getElementById('item_short_desc').value = '';
    document.getElementById('item_full_desc').value = '';
    document.getElementById('item_metrics').value = '';
    document.getElementById('item_sort').value = nextOrder;
    
    document.getElementById('item_modal_title').innerText = 'Tambah Pilar Dampak Sosial';
    updateIconPreview();
    new bootstrap.Modal(document.getElementById('modalAddItem')).show();
}

function editItem(item) {
    document.getElementById('item_action').value = 'update';
    document.getElementById('item_id').value = item.id;
    document.getElementById('item_icon').value = item.icon;
    document.getElementById('item_color').value = item.color;
    document.getElementById('item_title').value = item.title;
    document.getElementById('item_sort').value = item.sort_order;
    document.getElementById('item_short_desc').value = item.short_desc;
    document.getElementById('item_full_desc').value = item.full_desc;
    document.getElementById('item_metrics').value = item.metrics;

    document.getElementById('item_modal_title').innerText = 'Edit Pilar Dampak Sosial';
    updateIconPreview();
    new bootstrap.Modal(document.getElementById('modalAddItem')).show();
}

function updateIconPreview() {
    var iconSelect = document.getElementById('item_icon').value;
    var colorSelect = document.getElementById('item_color').value;
    
    var previewI = document.getElementById('icon_preview_i');
    var previewBg = document.getElementById('icon_preview_bg');
    
    previewI.className = 'bi ' + iconSelect;
    previewBg.className = 'icon-preview-box text-white bg-' + colorSelect;
}
</script>
</body>
</html>