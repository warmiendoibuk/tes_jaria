<?php
// C:\xampp\htdocs\jaria\admin\contact.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "CMS Kelola Kontak & Sosmed — Admin JARIA Craft";
$message = '';
$error = '';

$upload_dir = __DIR__ . '/../assets/images/banners/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// PILIHAN IKON SOSMED POPULER
$available_social_icons = [
    'bi-instagram'    => 'Instagram',
    'bi-tiktok'       => 'TikTok',
    'bi-youtube'      => 'YouTube',
    'bi-facebook'     => 'Facebook',
    'bi-threads'      => 'Threads',
    'bi-twitter-x'    => 'X / Twitter',
    'bi-linkedin'     => 'LinkedIn',
    'bi-pinterest'    => 'Pinterest',
    'bi-telegram'     => 'Telegram',
    'bi-discord'      => 'Discord',
    'bi-globe'        => 'Website / Blog',
    'bi-share-fill'   => 'Lainnya / Umum'
];

// FUNGSI AUTO RE-ORDER AGAR ANGKANYA SANGAT RAPI (1, 2, 3...) TANPA BENTROK
function sanitize_and_reorder_contact_banners($pdo) {
    $stmt = $pdo->query("SELECT id FROM contact_banners ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $new_order = 1;
    $upStmt = $pdo->prepare("UPDATE contact_banners SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $upStmt->execute([$new_order, $item['id']]);
        $new_order++;
    }
}

function sanitize_and_reorder_contact_socials($pdo) {
    $stmt = $pdo->query("SELECT id FROM contact_socials ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $new_order = 1;
    $upStmt = $pdo->prepare("UPDATE contact_socials SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $upStmt->execute([$new_order, $item['id']]);
        $new_order++;
    }
}

// REORDER BANNERS (NAIK / TURUN)
if (isset($_GET['reorder_banner']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    sanitize_and_reorder_contact_banners($pdo);
    
    $curr = $pdo->prepare("SELECT * FROM contact_banners WHERE id = ?");
    $curr->execute([$id]);
    $curr_item = $curr->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        $target = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM contact_banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM contact_banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE contact_banners SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE contact_banners SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            sanitize_and_reorder_contact_banners($pdo);
            $message = "Urutan banner kontak berhasil digeser!";
        }
    }
}

// REORDER SOSMED (NAIK / TURUN)
if (isset($_GET['reorder_social']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    sanitize_and_reorder_contact_socials($pdo);
    
    $curr = $pdo->prepare("SELECT * FROM contact_socials WHERE id = ?");
    $curr->execute([$id]);
    $curr_item = $curr->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        $target = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM contact_socials WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM contact_socials WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE contact_socials SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE contact_socials SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            sanitize_and_reorder_contact_socials($pdo);
            $message = "Urutan media sosial berhasil digeser!";
        }
    }
}

// UPDATE PENGATURAN TEKS KONTAK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_settings'])) {
    $badge_section     = trim($_POST['badge_section'] ?? '');
    $title_section     = trim($_POST['title_section'] ?? '');
    $subtitle_section  = trim($_POST['subtitle_section'] ?? '');
    $institution_name  = trim($_POST['institution_name'] ?? '');
    $institution_sub   = trim($_POST['institution_sub'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $wa_number         = trim($_POST['wa_number'] ?? '');
    $wa_contact_person = trim($_POST['wa_contact_person'] ?? '');
    $wa_display_text   = trim($_POST['wa_display_text'] ?? '');
    $website_url       = trim($_POST['website_url'] ?? '');
    $initiator_text    = trim($_POST['initiator_text'] ?? '');
    $maps_embed_url    = trim($_POST['maps_embed_url'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO contact_settings (id, badge_section, title_section, subtitle_section, institution_name, institution_sub, address, email, wa_number, wa_contact_person, wa_display_text, website_url, initiator_text, maps_embed_url) 
                           VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE 
                           badge_section=?, title_section=?, subtitle_section=?, institution_name=?, institution_sub=?, address=?, email=?, wa_number=?, wa_contact_person=?, wa_display_text=?, website_url=?, initiator_text=?, maps_embed_url=?");
    
    $stmt->execute([
        $badge_section, $title_section, $subtitle_section, $institution_name, $institution_sub, $address, $email, $wa_number, $wa_contact_person, $wa_display_text, $website_url, $initiator_text, $maps_embed_url,
        $badge_section, $title_section, $subtitle_section, $institution_name, $institution_sub, $address, $email, $wa_number, $wa_contact_person, $wa_display_text, $website_url, $initiator_text, $maps_embed_url
    ]);

    $message = "Informasi Alamat, Email, & Kontak berhasil diperbarui!";
}

// CRUD MEDIA SOSIAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_social'])) {
    $action = $_POST['action_social'];

    if ($action === 'create' || $action === 'update') {
        $platform_name = trim($_POST['platform_name'] ?? '');
        $icon          = trim($_POST['icon'] ?? 'bi-share-fill');
        $color_theme   = trim($_POST['color_theme'] ?? 'primary');
        $url           = trim($_POST['url'] ?? '');
        $sort_order    = (int)($_POST['sort_order'] ?? 1);

        if ($action === 'create') {
            $pdo->prepare("UPDATE contact_socials SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
            $stmt = $pdo->prepare("INSERT INTO contact_socials (platform_name, icon, color_theme, url, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$platform_name, $icon, $color_theme, $url, $sort_order]);
            $message = "Media Sosial baru berhasil ditambahkan!";
        } else {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE contact_socials SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $id]);
            $stmt = $pdo->prepare("UPDATE contact_socials SET platform_name = ?, icon = ?, color_theme = ?, url = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$platform_name, $icon, $color_theme, $url, $sort_order, $id]);
            $message = "Media Sosial berhasil diperbarui!";
        }
        
        sanitize_and_reorder_contact_socials($pdo);
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM contact_socials WHERE id = ?");
        $stmt->execute([$id]);
        
        sanitize_and_reorder_contact_socials($pdo);
        $message = "Media Sosial berhasil dihapus!";
    }
}

// CRUD SLIDE BANNER CAROUSEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_banner'])) {
    $action = $_POST['action_banner'];

    if ($action === 'create') {
        $badge_text  = trim($_POST['badge_text'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order  = (int)($_POST['sort_order'] ?? 1);
        $image_path  = 'assets/images/banners/banners1.jpg';

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'contact_banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                }
            }
        }

        $pdo->prepare("UPDATE contact_banners SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
        $stmt = $pdo->prepare("INSERT INTO contact_banners (image_path, badge_text, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order]);
        
        sanitize_and_reorder_contact_banners($pdo);
        $message = "Slide Banner Kontak berhasil ditambahkan!";
    }

    if ($action === 'update') {
        $id          = (int)$_POST['id'];
        $badge_text  = trim($_POST['badge_text'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order  = (int)($_POST['sort_order'] ?? 1);

        $pdo->prepare("UPDATE contact_banners SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $id]);

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'contact_banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'assets/images/banners/' . $filename;
                    $stmt = $pdo->prepare("UPDATE contact_banners SET image_path = ?, badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$image_path, $badge_text, $title, $description, $sort_order, $id]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE contact_banners SET badge_text = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$badge_text, $title, $description, $sort_order, $id]);
        }
        
        sanitize_and_reorder_contact_banners($pdo);
        $message = "Slide Banner Kontak berhasil diperbarui!";
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM contact_banners WHERE id = ?");
        $stmt->execute([$id]);
        
        sanitize_and_reorder_contact_banners($pdo);
        $message = "Slide Banner Kontak berhasil dihapus!";
    }
}

// RAPIKAN SEKALI SETIAP DIBUKA
sanitize_and_reorder_contact_banners($pdo);
sanitize_and_reorder_contact_socials($pdo);

$banners  = $pdo->query("SELECT * FROM contact_banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$settings = $pdo->query("SELECT * FROM contact_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$socials  = $pdo->query("SELECT * FROM contact_socials ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$next_banner_order = count($banners) + 1;
$next_social_order = count($socials) + 1;
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
        .banner-thumb { width: 120px; height: 65px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body class="py-4">

<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Kelola Halaman Kontak & Sosmed (CMS)</h3>
            <p class="text-muted small mb-0">Atur Carousel Banner Header, Alamat, Email, Media Sosial, & Google Maps</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary rounded-pill fw-semibold">&larr; Kembali ke Dashboard</a>
            <a href="../contact.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
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

    <!-- 1. SECTION CAROUSEL BANNER HEADER -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>1. Carousel Banner Header Kontak</h5>
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

    <!-- 2. SECTION MEDIA SOSIAL DINAMIS (FULL CRUD) -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-share-fill me-2"></i>2. Daftar Media Sosial Resmi (Bisa Tambah Platform Bebas)</h5>
            <button class="btn btn-primary btn-sm rounded-pill fw-bold" onclick="openAddSocialModal(<?php echo $next_social_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Sosmed Baru
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Urutan</th>
                            <th width="70">Ikon</th>
                            <th>Nama Platform</th>
                            <th>URL Tautan / Link Profil</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($socials)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada media sosial. Klik 'Tambah Sosmed Baru' di atas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($socials as $soc): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $soc['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_social=1&id=<?php echo $soc['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_social=1&id=<?php echo $soc['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="bg-<?php echo !empty($soc['color_theme']) ? $soc['color_theme'] : 'primary'; ?> text-white p-2 text-center rounded-3 fs-5">
                                            <i class="bi <?php echo !empty($soc['icon']) ? $soc['icon'] : 'bi-share-fill'; ?>"></i>
                                        </div>
                                    </td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($soc['platform_name']); ?></strong></td>
                                    <td><a href="<?php echo htmlspecialchars($soc['url']); ?>" target="_blank" class="small text-primary text-decoration-none"><?php echo htmlspecialchars($soc['url']); ?></a></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editSocial(<?php echo json_encode($soc); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus media sosial ini?')">
                                            <input type="hidden" name="action_social" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $soc['id']; ?>">
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

    <!-- 3. SECTION PENGATURAN INFORMASI KONTAK, EMAIL & MAPS -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-card-checklist me-2"></i>3. Pengaturan Alamat Utama, Email, WhatsApp, & Google Maps</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="action_settings" value="1">
                
                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-fonts me-1"></i> Header & Judul Section</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Badge Teks Section</label>
                        <input type="text" name="badge_section" class="form-control" value="<?php echo htmlspecialchars($settings['badge_section'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Judul Utama Section</label>
                        <input type="text" name="title_section" class="form-control" value="<?php echo htmlspecialchars($settings['title_section'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Sub-judul / Deskripsi Singkat</label>
                        <input type="text" name="subtitle_section" class="form-control" value="<?php echo htmlspecialchars($settings['subtitle_section'] ?? ''); ?>" required>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-building me-1"></i> Informasi Lembaga, Alamat, & Email</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nama Yayasan / Lembaga Mitra</label>
                        <input type="text" name="institution_name" class="form-control" value="<?php echo htmlspecialchars($settings['institution_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Keterangan Tambahan Lembaga</label>
                        <input type="text" name="institution_sub" class="form-control" value="<?php echo htmlspecialchars($settings['institution_sub'] ?? ''); ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Alamat Lengkap Resmi</label>
                        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Email Resmi JARIA</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>" placeholder="jaria.craft@gmail.com">
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-whatsapp text-success me-1"></i> Kontak WhatsApp Direct</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nomor WhatsApp (Format Sistem/URL)</label>
                        <input type="text" name="wa_number" class="form-control" value="<?php echo htmlspecialchars($settings['wa_number'] ?? ''); ?>" placeholder="6285704545150" required>
                        <small class="text-muted fs-7">Awalan 62 tanpa spasi/tanda +</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Atas Nama (Penanggung Jawab)</label>
                        <input type="text" name="wa_contact_person" class="form-control" value="<?php echo htmlspecialchars($settings['wa_contact_person'] ?? ''); ?>" placeholder="Azizah" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teks Tampilan Nomor WA</label>
                        <input type="text" name="wa_display_text" class="form-control" value="<?php echo htmlspecialchars($settings['wa_display_text'] ?? ''); ?>" placeholder="+62 857-0454-5150" required>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-link-45deg me-1"></i> Tautan Website & Google Maps Embed</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Website Resmi Yayasan (URL)</label>
                        <input type="url" name="website_url" class="form-control" value="<?php echo htmlspecialchars($settings['website_url'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Inisiator Proyek / Penanggung Jawab Proyek</label>
                        <input type="text" name="initiator_text" class="form-control" value="<?php echo htmlspecialchars($settings['initiator_text'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">URL Google Maps Embed (*src iframe)</label>
                        <textarea name="maps_embed_url" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['maps_embed_url'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="text-end border-top pt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <i class="bi bi-save-fill me-1"></i> Simpan Perubahan Kontak
                    </button>
                </div>
            </form>
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
                    <input type="text" name="badge_text" class="form-control" value="Kesekretariatan & Mitra Pemberdayaan">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul Banner</label>
                    <input type="text" name="title" class="form-control" value="Hubungi Kami & Lokasi Mitra" required>
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

<!-- MODAL TAMBAH & EDIT SOSMED -->
<div class="modal fade" id="modalSocial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_social" id="social_action" value="create">
            <input type="hidden" name="id" id="social_id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="social_modal_title">Tambah Media Sosial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Platform Media Sosial</label>
                    <input type="text" name="platform_name" id="social_name" class="form-control" placeholder="Contoh: Instagram / Threads / LinkedIn" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Ikon</label>
                    <select name="icon" id="social_icon" class="form-select fw-semibold">
                        <?php foreach ($available_social_icons as $icon_class => $icon_label): ?>
                            <option value="<?php echo $icon_class; ?>"><?php echo htmlspecialchars($icon_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Warna Tema Ikon</label>
                    <select name="color_theme" id="social_color" class="form-select">
                        <option value="primary">Primary (Biru)</option>
                        <option value="danger">Danger (Merah / Pink)</option>
                        <option value="dark">Dark (Hitam / Gelap)</option>
                        <option value="success">Success (Hijau)</option>
                        <option value="warning">Warning (Kuning / Oranye)</option>
                        <option value="info">Info (Biru Muda)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">URL Tautan / Link Profil</label>
                    <input type="url" name="url" id="social_url" class="form-control" placeholder="https://instagram.com/jaria.craft" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                    <input type="number" name="sort_order" id="social_sort" class="form-control" value="1">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan Sosmed</button>
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

function openAddSocialModal(nextOrder) {
    document.getElementById('social_action').value = 'create';
    document.getElementById('social_id').value = '';
    document.getElementById('social_name').value = '';
    document.getElementById('social_url').value = '';
    document.getElementById('social_sort').value = nextOrder || 1;
    document.getElementById('social_modal_title').innerText = 'Tambah Media Sosial';
    new bootstrap.Modal(document.getElementById('modalSocial')).show();
}

function editSocial(soc) {
    document.getElementById('social_action').value = 'update';
    document.getElementById('social_id').value = soc.id;
    document.getElementById('social_name').value = soc.platform_name;
    document.getElementById('social_icon').value = soc.icon;
    document.getElementById('social_color').value = soc.color_theme;
    document.getElementById('social_url').value = soc.url;
    document.getElementById('social_sort').value = soc.sort_order;
    document.getElementById('social_modal_title').innerText = 'Edit Media Sosial';
    new bootstrap.Modal(document.getElementById('modalSocial')).show();
}
</script>
</body>
</html>