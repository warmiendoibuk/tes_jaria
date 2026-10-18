<?php
// C:\xampp\htdocs\jaria\admin\manage_story.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "CMS Kelola Halaman Kisah Kami — Admin JARIA Craft";
$message = '';
$error = '';

$available_pages = [
    'catalog.php' => 'Katalog Produk',
    'impact.php'  => 'Dampak Sosial',
    'contact.php' => 'Kontak',
    'faq.php'     => 'FAQ',
    'cart.php'    => 'Keranjang Belanja'
];

// FUNGSI UNTUK MERAPIKAN URUTAN SEHINGGA TIDAK BENTROK (1, 2, 3, 4...)
function sanitize_and_reorder_points($pdo) {
    $stmt = $pdo->query("SELECT id FROM story_points ORDER BY sort_order ASC, id ASC");
    $points = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $new_order = 1;
    $updateStmt = $pdo->prepare("UPDATE story_points SET sort_order = ? WHERE id = ?");
    foreach ($points as $p) {
        $updateStmt->execute([$new_order, $p['id']]);
        $new_order++;
    }
}

// PROSES REORDER POIN SINERGI (NAIK / TURUN VIA TOMBOL PANAH)
if (isset($_GET['reorder_point']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    sanitize_and_reorder_points($pdo);
    
    $current = $pdo->prepare("SELECT * FROM story_points WHERE id = ?");
    $current->execute([$id]);
    $curr_point = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_point) {
        $curr_order = (int)$curr_point['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM story_points WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM story_points WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_point = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_point) {
            $pdo->prepare("UPDATE story_points SET sort_order = ? WHERE id = ?")->execute([$target_point['sort_order'], $curr_point['id']]);
            $pdo->prepare("UPDATE story_points SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_point['id']]);
            
            sanitize_and_reorder_points($pdo);
            $message = "Urutan poin sinergi berhasil dipindahkan!";
        }
    }
}

// PROSES SIMPAN SETTINGS & POIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if ($_POST['action_type'] === 'save_settings') {
        $badge_text      = trim($_POST['badge_text'] ?? '');
        $title_prefix    = trim($_POST['title_prefix'] ?? '');
        $title_highlight = trim($_POST['title_highlight'] ?? '');
        $lead_text       = trim($_POST['lead_text'] ?? '');
        $paragraph_1     = trim($_POST['paragraph_1'] ?? '');
        $paragraph_2     = trim($_POST['paragraph_2'] ?? '');
        $btn_1_text      = trim($_POST['btn_1_text'] ?? '');
        $btn_1_link      = trim($_POST['btn_1_link'] ?? 'catalog.php');
        $btn_2_text      = trim($_POST['btn_2_text'] ?? '');
        $btn_2_link      = trim($_POST['btn_2_link'] ?? 'impact.php');
        $card_title      = trim($_POST['card_title'] ?? '');

        $stmt = $pdo->prepare("UPDATE story_settings SET badge_text=?, title_prefix=?, title_highlight=?, lead_text=?, paragraph_1=?, paragraph_2=?, btn_1_text=?, btn_1_link=?, btn_2_text=?, btn_2_link=?, card_title=? WHERE id=1");
        $stmt->execute([$badge_text, $title_prefix, $title_highlight, $lead_text, $paragraph_1, $paragraph_2, $btn_1_text, $btn_1_link, $btn_2_text, $btn_2_link, $card_title]);
        $message = "Narasi & Pengaturan Teks Kisah Kami berhasil diperbarui!";
    }
    
    elseif ($_POST['action_type'] === 'save_point') {
        $point_id    = (int)($_POST['point_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $detail_text = trim($_POST['detail_text'] ?? '');
        $sort_order  = (int)($_POST['sort_order'] ?? 1);
        $is_active   = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($description)) {
            $error = "Judul poin dan ringkasan wajib diisi!";
        } else {
            if ($point_id > 0) {
                // GESER URUTAN DATA LAIN SUPAYA POSITION BARU BISA DITEMPATI
                $pdo->prepare("UPDATE story_points SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $point_id]);
                
                // UPDATE POIN
                $stmt = $pdo->prepare("UPDATE story_points SET title=?, description=?, detail_text=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $description, $detail_text, $sort_order, $is_active, $point_id]);
                $message = "Poin sinergi berhasil diperbarui!";
            } else {
                // GESER DATA YANG ADA DI ATAS / SAMA DENGAN NOMOR URUT PILIHAN
                $pdo->prepare("UPDATE story_points SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);

                // INSERT DATA BARU DI URUTAN TERSEBUT
                $stmt = $pdo->prepare("INSERT INTO story_points (title, description, detail_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $detail_text, $sort_order, $is_active]);
                $message = "Poin sinergi baru berhasil ditambahkan!";
            }
            
            // RAPIKAN KEMBALI URUTAN PENUH
            sanitize_and_reorder_points($pdo);
        }
    } 
    
    elseif ($_POST['action_type'] === 'delete_point') {
        $point_id = (int)($_POST['point_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM story_points WHERE id = ?");
        $stmt->execute([$point_id]);
        
        sanitize_and_reorder_points($pdo);
        $message = "Poin sinergi berhasil dihapus!";
    }
}

sanitize_and_reorder_points($pdo);

$stmt = $pdo->query("SELECT * FROM story_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM story_points ORDER BY sort_order ASC, id ASC");
$points = $stmt->fetchAll(PDO::FETCH_ASSOC);

$next_point_order = count($points) + 1;
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
    <!-- HEADER BAR -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-journal-text me-2"></i>CMS Kelola Halaman Kisah Kami</h3>
            <p class="text-muted small mb-0">Kelola narasi teks utama, tombol, dan poin interaktif kartu biru secara rinci</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-back-dashboard rounded-pill">
                &leftarrow; Kembali ke Dashboard
            </a>
            <a href="../about.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
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

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM NARASI UTAMA (KIRI) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-primary text-white py-3 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>Kelola Narasi Utama</h5>
                </div>
                <div class="card-body p-4">
                    <form action="manage_story.php" method="POST">
                        <input type="hidden" name="action_type" value="save_settings">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Label Badge Atas</label>
                            <input type="text" name="badge_text" class="form-control" value="<?php echo htmlspecialchars($settings['badge_text'] ?? ''); ?>" placeholder="Kolaborasi Sosial & Pemberdayaan">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold">Judul Awal</label>
                                <input type="text" name="title_prefix" class="form-control" value="<?php echo htmlspecialchars($settings['title_prefix'] ?? ''); ?>" placeholder="Di Balik Sentuhan Karya">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Judul Highlight (Warna Biru)</label>
                                <input type="text" name="title_highlight" class="form-control" value="<?php echo htmlspecialchars($settings['title_highlight'] ?? ''); ?>" placeholder="JARIA Craft">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Paragraf Pembuka (Teks Biru / Lead)</label>
                            <textarea name="lead_text" class="form-control" rows="3" placeholder="Tulis paragraf lead..."><?php echo htmlspecialchars($settings['lead_text'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Paragraf Narasi Pertama</label>
                            <textarea name="paragraph_1" class="form-control" rows="3" placeholder="Tulis narasi paragraf 1..."><?php echo htmlspecialchars($settings['paragraph_1'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Paragraf Narasi Kedua</label>
                            <textarea name="paragraph_2" class="form-control" rows="3" placeholder="Tulis narasi paragraf 2..."><?php echo htmlspecialchars($settings['paragraph_2'] ?? ''); ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teks Tombol 1</label>
                                <input type="text" name="btn_1_text" class="form-control" value="<?php echo htmlspecialchars($settings['btn_1_text'] ?? ''); ?>" placeholder="Lihat Karya Kerajinan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tujuan Halaman 1</label>
                                <select name="btn_1_link" class="form-select">
                                    <?php foreach ($available_pages as $file => $label): ?>
                                        <option value="<?php echo $file; ?>" <?php echo ($settings['btn_1_link'] ?? '') === $file ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teks Tombol 2</label>
                                <input type="text" name="btn_2_text" class="form-control" value="<?php echo htmlspecialchars($settings['btn_2_text'] ?? ''); ?>" placeholder="Dampak Sosial">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tujuan Halaman 2</label>
                                <select name="btn_2_link" class="form-select">
                                    <?php foreach ($available_pages as $file => $label): ?>
                                        <option value="<?php echo $file; ?>" <?php echo ($settings['btn_2_link'] ?? '') === $file ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Judul Kartu Biru Kanan</label>
                            <input type="text" name="card_title" class="form-control" value="<?php echo htmlspecialchars($settings['card_title'] ?? ''); ?>" placeholder="Sinergi Pendidikan & Sosial">
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="bi bi-floppy me-1"></i> Simpan Narasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- POIN-POIN SINERGI INTERAKTIF (KANAN) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2"></i>Poin Interaktif Kartu</h5>
                    <button class="btn btn-primary btn-sm rounded-pill fw-bold" onclick="resetPointForm(<?php echo $next_point_order; ?>)">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Poin
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Urutan</th>
                                    <th>Judul & Penjelasan</th>
                                    <th>Status</th>
                                    <th width="100" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($points)): ?>
                                    <?php foreach ($points as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-secondary"><?php echo $p['sort_order']; ?></span>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <a href="?reorder_point=1&id=<?php echo $p['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan Urutan"><i class="bi bi-chevron-up"></i></a>
                                                        <a href="?reorder_point=1&id=<?php echo $p['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan Urutan"><i class="bi bi-chevron-down"></i></a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="d-block text-dark small"><?php echo htmlspecialchars($p['title']); ?></strong>
                                                <small class="text-muted d-block text-truncate" style="max-width: 170px;"><?php echo htmlspecialchars($p['description']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($p['is_active']): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill small">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded-pill small">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editPoint(<?php echo json_encode($p); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                <form action="manage_story.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus poin ini?')">
                                                    <input type="hidden" name="action_type" value="delete_point">
                                                    <input type="hidden" name="point_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash-fill"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">Belum ada poin sinergi. Klik "Tambah Poin".</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM POIN INTERAKTIF -->
<div class="modal fade" id="pointModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="manage_story.php" method="POST">
                <input type="hidden" name="action_type" value="save_point">
                <input type="hidden" name="point_id" id="modal_point_id" value="0">

                <div class="modal-header bg-dark text-white" id="modal_point_header">
                    <h5 class="modal-title fw-bold" id="modalPointTitle">Kelola Poin Sinergi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul Poin / Isu <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modal_point_title" class="form-control" placeholder="Contoh: Arti Nama JARIA:" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ringkasan Singkat <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="modal_point_desc" class="form-control" placeholder="Singkatan dari Jahit Ceria, simbol semangat & keceriaan." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Penjelasan Lengkap (Tampil saat Dipencet/Klik)</label>
                        <textarea name="detail_text" id="modal_point_detail" class="form-control" rows="3" placeholder="Tulis penjelasan lengkap detail poin di sini..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                            <input type="number" name="sort_order" id="modal_point_sort" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" name="is_active" id="modal_point_active" value="1" checked>
                                <label class="form-check-label fs-6 fw-bold" for="modal_point_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="modal_point_submit_btn">Simpan Poin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetPointForm(nextOrder) {
    document.getElementById('modalPointTitle').innerText = 'Tambah Poin Sinergi Baru';
    document.getElementById('modal_point_header').className = 'modal-header bg-dark text-white';
    document.getElementById('modal_point_submit_btn').className = 'btn btn-primary rounded-pill px-4 fw-bold';
    document.getElementById('modal_point_submit_btn').innerText = 'Simpan Poin Baru';
    
    document.getElementById('modal_point_id').value = 0;
    document.getElementById('modal_point_title').value = '';
    document.getElementById('modal_point_desc').value = '';
    document.getElementById('modal_point_detail').value = '';
    document.getElementById('modal_point_sort').value = nextOrder || 1;
    document.getElementById('modal_point_active').checked = true;

    new bootstrap.Modal(document.getElementById('pointModal')).show();
}

function editPoint(data) {
    document.getElementById('modalPointTitle').innerText = 'Edit Poin Sinergi';
    document.getElementById('modal_point_header').className = 'modal-header bg-warning text-dark';
    document.getElementById('modal_point_submit_btn').className = 'btn btn-warning rounded-pill px-4 fw-bold';
    document.getElementById('modal_point_submit_btn').innerText = 'Perbarui Poin';

    document.getElementById('modal_point_id').value = data.id;
    document.getElementById('modal_point_title').value = data.title;
    document.getElementById('modal_point_desc').value = data.description;
    document.getElementById('modal_point_detail').value = data.detail_text || '';
    document.getElementById('modal_point_sort').value = data.sort_order;
    document.getElementById('modal_point_active').checked = (parseInt(data.is_active) === 1);
    
    new bootstrap.Modal(document.getElementById('pointModal')).show();
}
</script>
</body>
</html>