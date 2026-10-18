<?php
// C:\xampp\htdocs\jaria\admin\site_settings.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/settings_helper.php';

$page_title = "Pengaturan Logo, QRIS, Navbar & Footer — Admin JARIA";

// FUNGSI HELPER: Bikin Slug dari Judul (Contoh: "Galeri Produk" -> "galeri-produk")
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// 1. PROSES REORDER NAVBAR MENU (NAIK / TURUN)
if (isset($_GET['reorder_nav']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id  = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    $current = $pdo->prepare("SELECT * FROM navbar_menu WHERE id = ?");
    $current->execute([$id]);
    $curr_nav = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_nav) {
        $curr_order = (int)$curr_nav['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM navbar_menu WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM navbar_menu WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_nav = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_nav) {
            $pdo->prepare("UPDATE navbar_menu SET sort_order = ? WHERE id = ?")->execute([$target_nav['sort_order'], $curr_nav['id']]);
            $pdo->prepare("UPDATE navbar_menu SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_nav['id']]);
            $_SESSION['success_msg'] = "Urutan menu Navbar berhasil dipindahkan!";
        }
    }
    header("Location: site_settings.php");
    exit;
}

// 2. PROSES REORDER FOOTER LINK (NAIK / TURUN)
if (isset($_GET['reorder_foot']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id  = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    $current = $pdo->prepare("SELECT * FROM footer_links WHERE id = ?");
    $current->execute([$id]);
    $curr_foot = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_foot) {
        $curr_order = (int)$curr_foot['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM footer_links WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM footer_links WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_foot = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_foot) {
            $pdo->prepare("UPDATE footer_links SET sort_order = ? WHERE id = ?")->execute([$target_foot['sort_order'], $curr_foot['id']]);
            $pdo->prepare("UPDATE footer_links SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_foot['id']]);
            $_SESSION['success_msg'] = "Urutan tautan Footer berhasil dipindahkan!";
        }
    }
    header("Location: site_settings.php");
    exit;
}

// 3. PROSES UPDATE LOGO, QRIS, & FOOTER KONTAK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general_settings'])) {
    
    // Upload Logo
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
            $filename = 'logo_' . time() . '.' . $ext;
            $dest = dirname(__DIR__) . '/assets/images/' . $filename;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value=?");
                $stmt->execute(['assets/images/' . $filename, 'assets/images/' . $filename]);
            }
        }
    }

    // Upload QRIS
    if (isset($_FILES['site_qris']) && $_FILES['site_qris']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['site_qris']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $filename = 'qris_' . time() . '.' . $ext;
            $dest = dirname(__DIR__) . '/assets/images/' . $filename;
            if (move_uploaded_file($_FILES['site_qris']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_qris', ?) ON DUPLICATE KEY UPDATE setting_value=?");
                $stmt->execute(['assets/images/' . $filename, 'assets/images/' . $filename]);
            }
        }
    }

    // Update Teks
    $text_keys = ['qris_account_name', 'footer_about_text', 'footer_phone', 'footer_email', 'footer_address', 'footer_copyright_text', 'footer_subtext'];
    foreach ($text_keys as $k) {
        if (isset($_POST[$k])) {
            $v = trim($_POST[$k]);
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
            $stmt->execute([$k, $v, $v]);
        }
    }

    $_SESSION['success_msg'] = "Pengaturan Logo, QRIS, dan Footer berhasil disimpan!";
    header("Location: site_settings.php");
    exit;
}

// 4. OTOMATIS MEMBUAT HALAMAN BARU (TEMPLATE MENYERTAKAN HEADER + NAVBAR + FOOTER)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new_page'])) {
    $page_title_input = trim($_POST['page_title']);
    $add_to_navbar    = isset($_POST['add_to_navbar']) ? 1 : 0;
    $add_to_footer    = isset($_POST['add_to_footer']) ? 1 : 0;
    $footer_section   = trim($_POST['footer_section'] ?? 'Navigasi Utama');

    if (!empty($page_title_input)) {
        $slug = slugify($page_title_input);
        $file_name = $slug . '.php';
        $file_path = dirname(__DIR__) . '/' . $file_name;

        // Bikin File PHP Otomatis jika belum ada
        if (!file_exists($file_path)) {
            $template_content = '<?php
// Halaman Otomatis: ' . $page_title_input . '
session_start();
require_once __DIR__ . \'/includes/database.php\';
require_once __DIR__ . \'/includes/functions.php\';
require_once __DIR__ . \'/includes/settings_helper.php\';

$page_title = "' . addslashes($page_title_input) . ' — JARIA Craft";
include __DIR__ . \'/includes/header.php\';
include __DIR__ . \'/includes/navbar.php\';
?>

<div class="container py-5 min-vh-100">
    <div class="text-center py-5">
        <h1 class="fw-bold text-primary mb-3">' . htmlspecialchars($page_title_input) . '</h1>
        <p class="text-muted lead">Selamat datang di halaman ' . htmlspecialchars($page_title_input) . '.</p>
    </div>
</div>

<?php include __DIR__ . \'/includes/footer.php\'; ?>';
            file_put_contents($file_path, $template_content);
        }

        // Tambah ke Navbar Otomatis jika dicentang
        if ($add_to_navbar) {
            $stmt_max_nav = $pdo->query("SELECT MAX(sort_order) FROM navbar_menu");
            $max_nav = $stmt_max_nav->fetchColumn();
            $nav_sort = ($max_nav !== false && $max_nav !== null) ? $max_nav + 1 : 1;
            
            $pdo->prepare("INSERT INTO navbar_menu (title, url, sort_order) VALUES (?, ?, ?)")
                ->execute([$page_title_input, $file_name, $nav_sort]);
        }

        // Tambah ke Footer Otomatis jika dicentang
        if ($add_to_footer) {
            $stmt_max_foot = $pdo->prepare("SELECT MAX(sort_order) FROM footer_links WHERE section_name = ?");
            $stmt_max_foot->execute([$footer_section]);
            $max_foot = $stmt_max_foot->fetchColumn();
            $foot_sort = ($max_foot !== false && $max_foot !== null) ? $max_foot + 1 : 1;

            $pdo->prepare("INSERT INTO footer_links (section_name, title, url, sort_order) VALUES (?, ?, ?, ?)")
                ->execute([$footer_section, $page_title_input, $file_name, $foot_sort]);
        }

        $_SESSION['success_msg'] = "Halaman baru '$page_title_input' berhasil dibuat & ditambahkan otomatis!";
    }
    header("Location: site_settings.php");
    exit;
}

// 5. SIMPAN / UPDATE LINK MANUAL (LINK BARU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_custom_link'])) {
    $target_type = $_POST['target_type']; // 'navbar' atau 'footer'
    $title       = trim($_POST['title']);
    $url         = trim($_POST['url']);
    $id          = (int)($_POST['link_id'] ?? 0);

    if ($target_type === 'navbar') {
        if ($id > 0) {
            $pdo->prepare("UPDATE navbar_menu SET title=?, url=? WHERE id=?")->execute([$title, $url, $id]);
        } else {
            $max = $pdo->query("SELECT MAX(sort_order) FROM navbar_menu")->fetchColumn();
            $pdo->prepare("INSERT INTO navbar_menu (title, url, sort_order) VALUES (?, ?, ?)")->execute([$title, $url, ($max ? $max + 1 : 1)]);
        }
    } else {
        $section = trim($_POST['section_name'] ?? 'Navigasi Utama');
        if ($id > 0) {
            $pdo->prepare("UPDATE footer_links SET section_name=?, title=?, url=? WHERE id=?")->execute([$section, $title, $url, $id]);
        } else {
            $max = $pdo->query("SELECT MAX(sort_order) FROM footer_links")->fetchColumn();
            $pdo->prepare("INSERT INTO footer_links (section_name, title, url, sort_order) VALUES (?, ?, ?, ?)")->execute([$section, $title, $url, ($max ? $max + 1 : 1)]);
        }
    }
    $_SESSION['success_msg'] = "Tautan berhasil disimpan!";
    header("Location: site_settings.php");
    exit;
}

if (isset($_GET['delete_nav'])) {
    $id = (int)$_GET['delete_nav'];
    $pdo->prepare("DELETE FROM navbar_menu WHERE id=?")->execute([$id]);
    $_SESSION['success_msg'] = "Menu Navbar berhasil dihapus!";
    header("Location: site_settings.php");
    exit;
}

if (isset($_GET['delete_foot'])) {
    $id = (int)$_GET['delete_foot'];
    $pdo->prepare("DELETE FROM footer_links WHERE id=?")->execute([$id]);
    $_SESSION['success_msg'] = "Tautan Footer berhasil dihapus!";
    header("Location: site_settings.php");
    exit;
}

// AMBIL DATA
$site_logo   = get_site_setting('site_logo', 'assets/images/logo.png');
$site_qris   = get_site_setting('site_qris', 'assets/images/qris.png');
$qris_acc    = get_site_setting('qris_account_name', 'JARIA CRAFT');
$foot_about  = get_site_setting('footer_about_text');
$foot_phone  = get_site_setting('footer_phone');
$foot_email  = get_site_setting('footer_email');
$foot_addr   = get_site_setting('footer_address');
$foot_copy   = get_site_setting('footer_copyright_text');
$foot_sub    = get_site_setting('footer_subtext');

$nav_items   = $pdo->query("SELECT * FROM navbar_menu ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$foot_items  = $pdo->query("SELECT * FROM footer_links ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; }
        .content-container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; }
        
        .preview-box {
            width: 120px; height: 120px;
            border-radius: 1rem; border: 2px dashed #cbd5e1;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; background-color: #ffffff;
        }
        .preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }

        .card-custom {
            border: none; border-radius: 1.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        .btn-xs { font-size: 0.75rem; line-height: 1; }

        .btn { transition: all 0.25s ease; }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* STYLE BUTTON BACK MAIN SAMA DENGAN DASHBOARD */
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

        /* FLOATING ACTION BUTTON (GOOGLE SITES STYLE) */
        .floating-builder-menu {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
        }
        .fab-main {
            width: 60px; height: 60px;
            border-radius: 50%;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.4);
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="py-4">

<!-- FLOATING GOOGLE SITES MENU BAR (TOMBOL MELAYANG DI POJOK KANAN BAWAH) -->
<div class="floating-builder-menu">
    <button class="btn btn-primary fab-main d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#modalNewPage" title="Tambah Halaman Baru">
        <i class="bi bi-file-earmark-plus-fill"></i>
    </button>
</div>

<div class="content-container">
    <!-- HEADER HALAMAN DENGAN NAVIGASI DUA TOMBOL SAMA SEPERTI DASHBOARD -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-gear-wide-connected fs-5"></i>
            </div>
            <div>
                <h2 class="fw-extrabold text-primary mb-0" style="font-size: 1.65rem;">Pengaturan Website & Pengelola Halaman</h2>
                <p class="text-secondary small mb-0">Kelola identitas visual, struktur menu, dan buat halaman baru secara praktis</p>
            </div>
        </div>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="index.php" class="btn btn-back-main rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                &larr; Kembali ke Panel Utama
            </a>
            <a href="../index.php" target="_blank" class="btn btn-outline-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                <i class="bi bi-eye me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TOMBOL AKSI CEPAT GOOGLE SITES STYLE -->
    <div class="card card-custom p-4 mb-5 bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-plus-circle-fill me-2"></i>Buat Halaman Website Baru</h4>
                <p class="mb-0 text-white-50 small">Ketik nama halamannya, sistem akan otomatis membuatkan halaman dan menampilkannya di website.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light text-primary rounded-pill fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNewPage">
                    <i class="bi bi-file-earmark-plus me-1"></i> + Halaman Baru
                </button>
                <button class="btn btn-outline-light rounded-pill fw-bold px-4" onclick="openCustomLinkModal('navbar')">
                    <i class="bi bi-link-45deg me-1"></i> + Link Baru / Luar
                </button>
            </div>
        </div>
    </div>

    <form action="site_settings.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_general_settings" value="1">

        <!-- SECTION 1: LOGO & QRIS -->
        <div class="row g-4 mb-5">
            <!-- LOGO WEBSITE -->
            <div class="col-md-6">
                <div class="card card-custom p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-image text-primary me-2"></i>Ganti Logo Website</h5>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="preview-box">
                            <img src="../<?php echo htmlspecialchars($site_logo); ?>" alt="Logo Website">
                        </div>
                        <div>
                            <label class="form-label fw-bold small text-secondary">Pilih File Logo Baru</label>
                            <input type="file" name="site_logo" class="form-control rounded-3" accept="image/*">
                            <small class="text-muted d-block mt-1">Format: PNG / SVG / WEBP</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QRIS PEMBAYARAN -->
            <div class="col-md-6">
                <div class="card card-custom p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-qr-code-scan text-success me-2"></i>Ganti QRIS Pembayaran</h5>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="preview-box">
                            <img src="../<?php echo htmlspecialchars($site_qris); ?>" alt="QRIS">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-bold small text-secondary">Unggah Gambar QRIS</label>
                            <input type="file" name="site_qris" class="form-control rounded-3 mb-2" accept="image/*">
                            <input type="text" name="qris_account_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($qris_acc); ?>" placeholder="Nama Akun QRIS">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: TEKS FOOTER & KONTAK -->
        <div class="card card-custom p-4 mb-5">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-layout-text-window-reverse text-warning me-2"></i>Informasi Footer & Kontak</h5>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold small">Tentang Singkat di Footer</label>
                    <textarea name="footer_about_text" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($foot_about); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Nomor WhatsApp / HP</label>
                    <input type="text" name="footer_phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($foot_phone); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Alamat Email</label>
                    <input type="email" name="footer_email" class="form-control rounded-3" value="<?php echo htmlspecialchars($foot_email); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Alamat Panti / Workshop</label>
                    <input type="text" name="footer_address" class="form-control rounded-3" value="<?php echo htmlspecialchars($foot_addr); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Teks Copyright Utama</label>
                    <input type="text" name="footer_copyright_text" class="form-control rounded-3" value="<?php echo htmlspecialchars($foot_copy); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Sub-Teks Copyright</label>
                    <input type="text" name="footer_subtext" class="form-control rounded-3" value="<?php echo htmlspecialchars($foot_sub); ?>">
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan
                </button>
            </div>
        </div>
    </form>

    <!-- SECTION 3: STRUKTUR MENU NAVBAR -->
    <div class="card card-custom p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-columns-reverse text-info me-2"></i>Menu Navigasi Atas (Navbar)</h5>
            <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold" onclick="openCustomLinkModal('navbar')">
                <i class="bi bi-plus-circle me-1"></i> Tambah Link Manual
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="110">Urutan</th>
                        <th>Nama Menu</th>
                        <th>Tautan Halaman</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nav_items as $nav): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-secondary"><?php echo $nav['sort_order']; ?></span>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="?reorder_nav=1&id=<?php echo $nav['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-up"></i></a>
                                        <a href="?reorder_nav=1&id=<?php echo $nav['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-down"></i></a>
                                    </div>
                                </div>
                            </td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($nav['title']); ?></strong></td>
                            <td><code class="text-primary"><?php echo htmlspecialchars($nav['url']); ?></code></td>
                            <td class="text-center">
                                <a href="?delete_nav=<?php echo $nav['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus menu ini?')" title="Hapus">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 4: STRUKTUR MENU FOOTER -->
    <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-link-45deg text-danger me-2"></i>Tautan Footer Bawah</h5>
            <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold" onclick="openCustomLinkModal('footer')">
                <i class="bi bi-plus-circle me-1"></i> Tambah Link Footer
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="110">Urutan</th>
                        <th>Kategori / Seksi</th>
                        <th>Nama Link</th>
                        <th>Tautan Halaman</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($foot_items as $ft): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-secondary"><?php echo $ft['sort_order'] ?? 1; ?></span>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="?reorder_foot=1&id=<?php echo $ft['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-up"></i></a>
                                        <a href="?reorder_foot=1&id=<?php echo $ft['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-down"></i></a>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($ft['section_name']); ?></span></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($ft['title']); ?></strong></td>
                            <td><code class="text-primary"><?php echo htmlspecialchars($ft['url']); ?></code></td>
                            <td class="text-center">
                                <a href="?delete_foot=<?php echo $ft['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus link ini?')" title="Hapus">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL GOOGLE SITES: HALAMAN BARU -->
<div class="modal fade" id="modalNewPage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="site_settings.php" method="POST">
                <input type="hidden" name="create_new_page" value="1">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus-fill me-2"></i>Buat Halaman Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Halaman Baru</label>
                        <input type="text" name="page_title" class="form-control form-control-lg rounded-3" placeholder="Contoh: Galeri Produk, Program Beasiswa, CV" required>
                        <small class="text-muted">Sistem akan otomatis membuatkan halaman web baru sesuai nama ini.</small>
                    </div>

                    <div class="card bg-light border-0 p-3 mb-3 rounded-3">
                        <label class="form-label fw-bold small text-dark mb-2">Tampilkan Halaman Ini Di:</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="add_to_navbar" id="add_nav" value="1" checked>
                            <label class="form-check-label fw-semibold" for="add_nav">
                                Menu Navigasi Atas (Navbar)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="add_to_footer" id="add_foot" value="1" checked>
                            <label class="form-check-label fw-semibold" for="add_foot">
                                Tautan Footer Bawah
                            </label>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold small">Kategori Seksi Footer</label>
                        <input type="text" name="footer_section" class="form-control rounded-3" value="Navigasi Utama" placeholder="Misal: Informasi, Layanan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Selesai & Buat Halaman</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL GOOGLE SITES: LINK BARU / LUAR MANUAL -->
<div class="modal fade" id="modalCustomLink" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="site_settings.php" method="POST">
                <input type="hidden" name="save_custom_link" value="1">
                <input type="hidden" name="target_type" id="link_target_type" value="navbar">
                <input type="hidden" name="link_id" id="link_id" value="0">
                
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="customLinkModalTitle"><i class="bi bi-link-45deg me-2"></i>Tambah Link Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Tampilan Link</label>
                        <input type="text" name="title" id="link_title" class="form-control rounded-3" placeholder="Contoh: WhatsApp Kami, Google Maps" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">URL Tautan / Link Luar</label>
                        <input type="text" name="url" id="link_url" class="form-control rounded-3" placeholder="Contoh: https://wa.me/628123456789 atau contact.php" required>
                    </div>
                    <div class="mb-3 d-none" id="section_input_box">
                        <label class="form-label fw-bold small">Seksi Footer</label>
                        <input type="text" name="section_name" id="link_section" class="form-control rounded-3" value="Navigasi Utama">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan Link</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openCustomLinkModal(type) {
    document.getElementById('link_target_type').value = type;
    document.getElementById('link_title').value = '';
    document.getElementById('link_url').value = '';
    
    if (type === 'footer') {
        document.getElementById('section_input_box').classList.remove('d-none');
        document.getElementById('customLinkModalTitle').innerText = 'Tambah Link Footer Baru';
    } else {
        document.getElementById('section_input_box').classList.add('d-none');
        document.getElementById('customLinkModalTitle').innerText = 'Tambah Link Navbar Baru';
    }
    new bootstrap.Modal(document.getElementById('modalCustomLink')).show();
}
</script>
</body>
</html>