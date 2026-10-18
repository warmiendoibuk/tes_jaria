<?php
// C:\xampp\htdocs\jaria\admin\news.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit; 
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title = "CMS Berita & Blog SEO — Admin JARIA";

// Buat Tabel news_banners jika belum ada
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS news_banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        badge_text_1 VARCHAR(100) DEFAULT 'Kabar & Artikel',
        badge_text_2 VARCHAR(100) DEFAULT 'Seputar JARIA Craft',
        title VARCHAR(255) NOT NULL,
        description TEXT,
        sort_order INT DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1
    )");
} catch (PDOException $e) {}

// Helper Slug SEO
function create_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug . '-' . time();
}

// FUNGSI AUTO-REORDER URUTAN BANNER BERITA
function reorder_news_banners($pdo) {
    $stmt = $pdo->query("SELECT id FROM news_banners ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 1;
    $up = $pdo->prepare("UPDATE news_banners SET sort_order = ? WHERE id = ?");
    foreach ($items as $it) { 
        $up->execute([$n++, $it['id']]); 
    }
}

// PROSES REORDER SLIDE BANNER BERITA (NAIK/TURUN)
if (isset($_GET['reorder_banner']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    reorder_news_banners($pdo);

    $curr = $pdo->prepare("SELECT * FROM news_banners WHERE id = ?");
    $curr->execute([$id]);
    $c = $curr->fetch(PDO::FETCH_ASSOC);

    if ($c) {
        $curr_o = (int)$c['sort_order'];
        $tgt = ($dir === 'up') 
            ? $pdo->prepare("SELECT * FROM news_banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $pdo->prepare("SELECT * FROM news_banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $tgt->execute([$curr_o]);
        $t = $tgt->fetch(PDO::FETCH_ASSOC);

        if ($t) {
            $pdo->prepare("UPDATE news_banners SET sort_order = ? WHERE id = ?")->execute([$t['sort_order'], $c['id']]);
            $pdo->prepare("UPDATE news_banners SET sort_order = ? WHERE id = ?")->execute([$curr_o, $t['id']]);
            reorder_news_banners($pdo);
            $_SESSION['success_msg'] = "Urutan banner berita berhasil digeser!";
        }
    }
    header("Location: news.php");
    exit;
}

// 1. TAMBAH / EDIT BANNER CAROUSEL BERITA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $b_id           = (int)($_POST['banner_id'] ?? 0);
    $badge_text_1   = trim($_POST['badge_text_1'] ?? '');
    $badge_text_2   = trim($_POST['badge_text_2'] ?? '');
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $sort_order     = (int)($_POST['sort_order'] ?? 1);

    $image_path = $_POST['existing_banner_image'] ?? 'assets/images/banners/banners1.jpg';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__) . '/assets/images/banners/';
        if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $filename = 'news_banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/banners/' . $filename;
            }
        }
    }

    if ($b_id > 0) {
        $pdo->prepare("UPDATE news_banners SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $b_id]);
        $stmt = $pdo->prepare("UPDATE news_banners SET image_path=?, badge_text_1=?, badge_text_2=?, title=?, description=?, sort_order=? WHERE id=?");
        $stmt->execute([$image_path, $badge_text_1, $badge_text_2, $title, $description, $sort_order, $b_id]);
        $_SESSION['success_msg'] = "Banner header berita berhasil diperbarui!";
    } else {
        $pdo->prepare("UPDATE news_banners SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);
        $stmt = $pdo->prepare("INSERT INTO news_banners (image_path, badge_text_1, badge_text_2, title, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$image_path, $badge_text_1, $badge_text_2, $title, $description, $sort_order]);
        $_SESSION['success_msg'] = "Slide Banner berita baru berhasil ditambahkan!";
    }
    reorder_news_banners($pdo);
    header("Location: news.php");
    exit;
}

// HAPUS BANNER
if (isset($_GET['delete_banner'])) {
    $b_id = (int)$_GET['delete_banner'];
    $pdo->prepare("DELETE FROM news_banners WHERE id = ?")->execute([$b_id]);
    reorder_news_banners($pdo);
    $_SESSION['success_msg'] = "Slide banner berhasil dihapus!";
    header("Location: news.php");
    exit;
}

// 2. TAMBAH KATEGORI BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['category_name'] ?? '');
    if (!empty($cat_name)) {
        $cat_slug = create_slug($cat_name);
        try {
            $stmt = $pdo->prepare("INSERT INTO news_categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$cat_name, $cat_slug]);
            $_SESSION['success_msg'] = "Kategori '$cat_name' berhasil ditambahkan!";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal menambah kategori.";
        }
    }
    header("Location: news.php");
    exit;
}

// 3. HAPUS KATEGORI
if (isset($_GET['delete_category'])) {
    $cat_id = (int)$_GET['delete_category'];
    $pdo->prepare("DELETE FROM news_categories WHERE id = :id")->execute(['id' => $cat_id]);
    $_SESSION['success_msg'] = "Kategori berhasil dihapus!";
    header("Location: news.php");
    exit;
}

// 4. TAMBAH / EDIT BERITA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_news'])) {
    $id               = (int)($_POST['id'] ?? 0);
    $category_id      = (int)($_POST['category_id'] ?? 0);
    $title            = trim($_POST['title'] ?? '');
    $content          = $_POST['content'] ?? ''; 
    $hashtags         = trim($_POST['hashtags'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $is_published     = isset($_POST['is_published']) ? 1 : 0;
    
    // Normalisasi link gambar dalam konten
    $content = str_replace('../assets/images/news/', 'assets/images/news/', $content);

    if (!empty($hashtags)) {
        $tags_arr = explode(' ', str_replace(',', ' ', $hashtags));
        $formatted_tags = [];
        foreach ($tags_arr as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $formatted_tags[] = (strpos($tag, '#') === 0) ? $tag : '#' . $tag;
            }
        }
        $hashtags = implode(' ', array_unique($formatted_tags));
    }

    $featured_image = $_POST['existing_image'] ?? '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__) . '/assets/images/news/';
        if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $filename = 'news_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = 'assets/images/news/' . $filename;
            }
        }
    }

    if (!empty($title) && !empty($content) && $category_id > 0) {
        if ($id > 0) {
            $sql = "UPDATE news SET category_id=?, title=?, content=?, featured_image=?, hashtags=?, meta_description=?, is_published=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category_id, $title, $content, $featured_image, $hashtags, $meta_description, $is_published, $id]);
            $_SESSION['success_msg'] = "Berita/Artikel berhasil diperbarui!";
        } else {
            $slug = create_slug($title);
            $sql = "INSERT INTO news (category_id, title, slug, content, featured_image, hashtags, meta_description, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category_id, $title, $slug, $content, $featured_image, $hashtags, $meta_description, $is_published]);
            $_SESSION['success_msg'] = "Berita/Artikel baru berhasil diterbitkan!";
        }
    } else {
        $_SESSION['error_msg'] = "Judul, Kategori, dan Isi Artikel wajib diisi.";
    }

    header("Location: news.php");
    exit;
}

// 5. HAPUS BERITA
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM news WHERE id = :id")->execute(['id' => $id]);
    $_SESSION['success_msg'] = "Berita berhasil dihapus!";
    header("Location: news.php");
    exit;
}

reorder_news_banners($pdo);

// AMBIL DATA
$banners    = $pdo->query("SELECT * FROM news_banners ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM news_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sql_news   = "SELECT n.*, c.name as category_name 
               FROM news n 
               JOIN news_categories c ON n.category_id = c.id 
               ORDER BY n.created_at DESC";
$news_list  = $pdo->query($sql_news)->fetchAll(PDO::FETCH_ASSOC);

$next_banner_order = count($banners) + 1;

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- CKEDITOR 4 -->
    <script src="https://cdn.ckeditor.com/4.22.1/full-all/ckeditor.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .content-container { max-width: 1320px; margin: 0 auto; padding: 2.5rem 1.5rem; }
        
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

        .banner-thumb { width: 100px; height: 55px; object-fit: cover; border-radius: 8px; }
        .btn-xs { padding: 0px 4px; font-size: 10px; line-height: 1; }

        .cke_notification_warning { display: none !important; }
    </style>
</head>
<body class="py-4">

<div class="content-container">
    <!-- HEADER CMS -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-newspaper me-2"></i>Kelola Berita & Blog SEO (CMS)</h3>
            <p class="text-muted small mb-0">Atur Carousel Header Banner Berita, Artikel Blog, & Kategori secara Fleksibel</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-back-dashboard rounded-pill">
                &leftarrow; Kembali ke Dashboard
            </a>
            <a href="../news.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-eye-fill me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 fw-bold mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECTION CAROUSEL BANNER HEADER BERITA -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-images me-2"></i>Carousel Banner Header Berita</h5>
            <button class="btn btn-light text-primary btn-sm rounded-pill fw-bold" onclick="openAddBannerModal(<?php echo $next_banner_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Slide Banner
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Urutan</th>
                            <th width="120">Foto</th>
                            <th>Judul Banner</th>
                            <th>Badge & Deskripsi</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada slide banner kustom. (Menggunakan tampilan default)</td></tr>
                        <?php else: ?>
                            <?php foreach ($banners as $b): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $b['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_banner=1&id=<?php echo $b['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td><img src="../<?php echo htmlspecialchars($b['image_path']); ?>" class="banner-thumb shadow-sm" onerror="this.src='../assets/images/banners/banners1.jpg';"></td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($b['title']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-warning text-dark small mb-1"><?php echo htmlspecialchars($b['badge_text_1']); ?></span>
                                        <?php if (!empty($b['badge_text_2'])): ?><span class="badge bg-secondary small mb-1"><?php echo htmlspecialchars($b['badge_text_2']); ?></span><?php endif; ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($b['description']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editBanner(<?php echo json_encode($b); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <a href="?delete_banner=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus slide banner ini?')"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION KELOLA ARTIKEL & KATEGORI BERITA -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-journal-text me-2"></i>Daftar Artikel & Berita</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="bi bi-folder-plus me-1"></i> Kelola Kategori
                </button>
                <button class="btn btn-primary btn-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#newsModal0">
                    <i class="bi bi-plus-circle me-1"></i> Tulis Artikel Baru
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0 mb-0">
                    <thead>
                        <tr class="text-secondary small border-bottom">
                            <th>Foto</th>
                            <th>Judul Artikel</th>
                            <th>Kategori</th>
                            <th>Hashtag SEO</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($news_list)): ?>
                            <?php foreach ($news_list as $item): ?>
                                <tr class="border-bottom">
                                    <td style="width: 80px;">
                                        <?php if ($item['featured_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['featured_image']); ?>" class="rounded-3 object-fit-cover" width="60" height="45">
                                        <?php else: ?>
                                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted" style="width: 60px; height: 45px;"><i class="bi bi-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark text-wrap d-block"><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <small class="text-muted"><i class="bi bi-eye me-1"></i><?php echo $item['views_count']; ?>x dibaca</small>
                                    </td>
                                    <td><span class="badge bg-light text-primary border rounded-pill px-3"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                    <td><small class="text-info fw-bold"><?php echo htmlspecialchars($item['hashtags'] ?: '-'); ?></small></td>
                                    <td>
                                        <?php if ($item['is_published']): ?>
                                            <span class="badge bg-success rounded-pill px-2.5">Terbit</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-2.5">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5" data-bs-toggle="modal" data-bs-target="#newsModal<?php echo $item['id']; ?>">Edit</button>
                                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5" onclick="return confirm('Hapus artikel ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada artikel berita yang dibuat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM BANNER CAROUSEL BERITA -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="news.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_banner" value="1">
                <input type="hidden" name="banner_id" id="modal_banner_id" value="0">
                <input type="hidden" name="existing_banner_image" id="modal_existing_banner_image" value="">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="bannerModalTitle">Tambah Slide Banner Carousel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Foto Banner Latar</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Badge Teks 1</label>
                            <input type="text" name="badge_text_1" id="modal_badge_text_1" class="form-control" placeholder="Kabar & Artikel">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Badge Teks 2</label>
                            <input type="text" name="badge_text_2" id="modal_badge_text_2" class="form-control" placeholder="Seputar JARIA Craft">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Banner Utama</label>
                        <input type="text" name="title" id="modal_banner_title" class="form-control" placeholder="Judul Banner..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi Singkat Banner</label>
                        <textarea name="description" id="modal_banner_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Urutan Tampil (*Sort Order)</label>
                        <input type="number" name="sort_order" id="modal_banner_sort_order" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Slide</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KELOLA KATEGORI BERITA -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 px-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-folder-fill me-2"></i>Kelola Kategori Berita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="news.php" method="POST" class="mb-4">
                    <input type="hidden" name="add_category" value="1">
                    <label class="form-label fw-bold small text-secondary">Tambah Kategori Baru</label>
                    <div class="input-group">
                        <input type="text" name="category_name" class="form-control rounded-start-3" placeholder="Contoh: Liputan Media, Workshop" required>
                        <button class="btn btn-primary rounded-end-3 fw-bold" type="submit">Tambah</button>
                    </div>
                </form>

                <label class="form-label fw-bold small text-secondary">Daftar Kategori Saat Ini</label>
                <div class="list-group rounded-3">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $c): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></span>
                                <a href="?delete_category=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0.5" onclick="return confirm('Hapus kategori ini?')">&times; Hapus</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-muted text-center py-3">Belum ada kategori.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM ARTIKEL BERITA -->
<?php 
$modal_items = array_merge([['id' => 0, 'title' => '', 'category_id' => '', 'content' => '', 'featured_image' => '', 'hashtags' => '#JariaCraft #KerajinanPerca', 'meta_description' => '', 'is_published' => 1]], $news_list);
foreach ($modal_items as $m): 
?>
<div class="modal fade" id="newsModal<?php echo $m['id']; ?>" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="news.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white p-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i><?php echo $m['id'] > 0 ? 'Edit Artikel Berita' : 'Tulis Artikel Berita Baru'; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <input type="hidden" name="save_news" value="1">
                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($m['featured_image']); ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-secondary">Judul Artikel / Berita <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3 fw-bold" value="<?php echo htmlspecialchars($m['title']); ?>" placeholder="Judul artikel..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Kategori Berita <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $m['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Isi Artikel / Konten Blog <span class="text-danger">*</span></label>
                        <textarea name="content" id="editor_<?php echo $m['id']; ?>" class="form-control"><?php echo htmlspecialchars($m['content']); ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary"><i class="bi bi-hash text-primary"></i> Hashtag SEO Google</label>
                            <input type="text" name="hashtags" class="form-control rounded-3" value="<?php echo htmlspecialchars($m['hashtags']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Foto Sampul Utama (Header Banner)</label>
                            <input type="file" name="featured_image" class="form-control rounded-3" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Meta Description SEO</label>
                        <textarea name="meta_description" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($m['meta_description']); ?></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_published" id="pub_<?php echo $m['id']; ?>" <?php echo $m['is_published'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold small text-dark" for="pub_<?php echo $m['id']; ?>">Publikasikan Artikel Ini Secara Langsung</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan & Publikasikan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
if (window.bootstrap && bootstrap.Modal) {
    bootstrap.Modal.prototype._enforceFocus = function() {};
}

function openAddBannerModal(nextOrder) {
    document.getElementById('bannerModalTitle').innerText = 'Tambah Slide Banner Carousel';
    document.getElementById('modal_banner_id').value = 0;
    document.getElementById('modal_existing_banner_image').value = '';
    document.getElementById('modal_badge_text_1').value = 'Kabar & Artikel';
    document.getElementById('modal_badge_text_2').value = 'Seputar JARIA Craft';
    document.getElementById('modal_banner_title').value = '';
    document.getElementById('modal_banner_description').value = '';
    document.getElementById('modal_banner_sort_order').value = nextOrder;
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function editBanner(b) {
    document.getElementById('bannerModalTitle').innerText = 'Edit Banner Carousel';
    document.getElementById('modal_banner_id').value = b.id;
    document.getElementById('modal_existing_banner_image').value = b.image_path;
    document.getElementById('modal_badge_text_1').value = b.badge_text_1;
    document.getElementById('modal_badge_text_2').value = b.badge_text_2;
    document.getElementById('modal_banner_title').value = b.title;
    document.getElementById('modal_banner_description').value = b.description;
    document.getElementById('modal_banner_sort_order').value = b.sort_order;
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

document.addEventListener("DOMContentLoaded", function () {
    const textareas = document.querySelectorAll('textarea[id^="editor_"]');
    textareas.forEach(textarea => {
        const editor = CKEDITOR.replace(textarea.id, {
            height: 420,
            baseHref: '../',
            
            // Mengaktifkan plugin alignment & image2
            extraPlugins: 'image2,uploadimage,embed,autoembed,iframe,justify',
            removePlugins: 'image',
            
            filebrowserUploadUrl: 'upload_image.php',
            filebrowserUploadMethod: 'form',
            uploadUrl: 'upload_image.php',
            
            // Alignment khusus gambar di CKEditor
            image2_alignClasses: [ 'align-left', 'align-center', 'align-right' ],
            image2_disableResizer: false,
            
            // Inject Style CSS khusus untuk editor
            contentsCss: [
                'https://cdn.ckeditor.com/4.22.1/full-all/contents.css',
                'data:text/css;charset=utf-8,' + encodeURIComponent(
                    'figure.image { display: table !important; margin: 1rem auto !important; max-width: 100%; clear: both; } ' +
                    'figure.image img { max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 0 auto; } ' +
                    'figure.align-left, figure.image-style-align-left { float: left !important; margin: 0.5rem 1.5rem 1rem 0 !important; } ' +
                    'figure.align-right, figure.image-style-align-right { float: right !important; margin: 0.5rem 0 1rem 1.5rem !important; } ' +
                    'figure.align-center, figure.image-style-align-center, .align-center { display: table !important; margin-left: auto !important; margin-right: auto !important; text-align: center !important; } ' +
                    'figure.align-center img { margin: 0 auto !important; display: block !important; }'
                )
            ],
            
            allowedContent: true,
            fontSize_sizes: '8pt/8pt;9pt/9pt;10pt/10pt;11pt/11pt;12pt/12pt;14pt/14pt;16pt/16pt;18pt/18pt;20pt/20pt;24pt/24pt;28pt/28pt;36pt/36pt;48pt/48pt;72pt/72pt',
            font_names: 'Plus Jakarta Sans/Plus Jakarta Sans, sans-serif;' +
                       'Poppins/Poppins, sans-serif;' +
                       'Inter/Inter, sans-serif;' +
                       'Open Sans/Open Sans, sans-serif;' +
                       'Arial/Arial, Helvetica, sans-serif;' +
                       'Times New Roman/Times New Roman, Times, serif;' +
                       'Georgia/Georgia, serif;' +
                       'Courier New/Courier New, Courier, monospace;',
            toolbar: [
                { name: 'document', items: [ 'Source', '-', 'Undo', 'Redo' ] },
                { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
                { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
                { name: 'insert', items: [ 'Image', 'Embed', 'Iframe', 'Table', 'HorizontalRule', 'SpecialChar' ] },
                { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                { name: 'tools', items: [ 'Maximize' ] }
            ]
        });

        editor.on('change', function() {
            editor.updateElement();
        });
    });
});
</script>
</body>
</html>