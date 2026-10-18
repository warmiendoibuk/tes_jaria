<?php
// C:\xampp\htdocs\jaria\admin\gallery\index.php
session_start();

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Cek Akses Login Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// OTOMATISASI BUAT TABEL & PENYESUAIAN KOLOM DATABASE
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL DEFAULT 'image',
        title VARCHAR(255) NOT NULL,
        caption TEXT NULL,
        file_path VARCHAR(255) NULL,
        media_url TEXT NULL,
        sort_order INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Otomatis tambah kolom sort_order jika belum ada
    $check_col = $pdo->query("SHOW COLUMNS FROM gallery LIKE 'sort_order'");
    if ($check_col->rowCount() == 0) {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN sort_order INT NOT NULL DEFAULT 1 AFTER media_url");
    }

    // Otomatis tambah kolom is_active jika belum ada
    $check_act = $pdo->query("SHOW COLUMNS FROM gallery LIKE 'is_active'");
    if ($check_act->rowCount() == 0) {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER sort_order");
    }
} catch (PDOException $e) {}

$message = '';
$upload_dir = __DIR__ . '/../../assets/images/gallery/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// FUNGSI KHUSUS RAPIKAN URUTAN GALERI AGAR TIDAK BENTROK (1, 2, 3, 4...)
function sanitize_and_reorder_gallery($pdo) {
    $stmt = $pdo->query("SELECT id FROM gallery ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $new_order = 1;
    $updateStmt = $pdo->prepare("UPDATE gallery SET sort_order = ? WHERE id = ?");
    foreach ($items as $item) {
        $updateStmt->execute([$new_order, $item['id']]);
        $new_order++;
    }
}

// FUNGSI FORMAT TANGGAL INDONESIA REAL-TIME
function format_tanggal_indo($datetime) {
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    $hari_arr = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan_arr = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $hari = $hari_arr[date('l', $time)];
    $tgl = date('d', $time);
    $bln = $bulan_arr[(int)date('m', $time)];
    $thn = date('Y', $time);
    $jam = date('H:i', $time);

    return "$hari, $tgl $bln $thn — $jam WIB";
}

// PROSES REORDER MEDIA GALERI (NAIK / TURUN)
if (isset($_GET['reorder_gallery']) && isset($_GET['id']) && isset($_GET['dir'])) {
    $id = (int)$_GET['id'];
    $dir = $_GET['dir'];
    
    sanitize_and_reorder_gallery($pdo);
    
    $current = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
    $current->execute([$id]);
    $curr_item = $current->fetch(PDO::FETCH_ASSOC);

    if ($curr_item) {
        $curr_order = (int)$curr_item['sort_order'];
        if ($dir === 'up') {
            $target = $pdo->prepare("SELECT * FROM gallery WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $target = $pdo->prepare("SELECT * FROM gallery WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $target->execute([$curr_order]);
        $target_item = $target->fetch(PDO::FETCH_ASSOC);

        if ($target_item) {
            $pdo->prepare("UPDATE gallery SET sort_order = ? WHERE id = ?")->execute([$target_item['sort_order'], $curr_item['id']]);
            $pdo->prepare("UPDATE gallery SET sort_order = ? WHERE id = ?")->execute([$curr_order, $target_item['id']]);
            
            sanitize_and_reorder_gallery($pdo);
            $message = "Urutan media galeri berhasil dipindahkan!";
        }
    }
}

// PROSES CRUD TAMBAH & EDIT MEDIA GALERI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_gallery'])) {
    $action    = $_POST['action_gallery'];
    $type      = function_exists('sanitize') ? sanitize($_POST['type'] ?? 'image') : htmlspecialchars(trim($_POST['type'] ?? 'image'));
    $title     = function_exists('sanitize') ? sanitize($_POST['title'] ?? '') : htmlspecialchars(trim($_POST['title'] ?? ''));
    $caption   = function_exists('sanitize') ? sanitize($_POST['caption'] ?? '') : htmlspecialchars(trim($_POST['caption'] ?? ''));
    $media_url = trim($_POST['media_url'] ?? '');
    $sort_order= (int)($_POST['sort_order'] ?? 1);
    $file_path = $_POST['existing_file_path'] ?? NULL;

    // Handle Upload Foto Baru
    if ($type === 'image' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'gal_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                $file_path = 'assets/images/gallery/' . $filename;
            }
        }
    }

    if ($action === 'create') {
        try {
            $pdo->prepare("UPDATE gallery SET sort_order = sort_order + 1 WHERE sort_order >= ?")->execute([$sort_order]);

            $stmt = $pdo->prepare("INSERT INTO gallery (type, title, caption, file_path, media_url, sort_order, created_at) VALUES (:type, :title, :caption, :file_path, :media_url, :sort_order, NOW())");
            $stmt->execute([
                'type'       => $type,
                'title'      => $title,
                'caption'    => $caption,
                'file_path'  => $file_path,
                'media_url'  => $media_url,
                'sort_order' => $sort_order
            ]);
            sanitize_and_reorder_gallery($pdo);
            $message = "Media galeri baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $message = "Gagal menyimpan: " . $e->getMessage();
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $pdo->prepare("UPDATE gallery SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")->execute([$sort_order, $id]);

            $stmt = $pdo->prepare("UPDATE gallery SET type = :type, title = :title, caption = :caption, file_path = :file_path, media_url = :media_url, sort_order = :sort_order WHERE id = :id");
            $stmt->execute([
                'type'       => $type,
                'title'      => $title,
                'caption'    => $caption,
                'file_path'  => $file_path,
                'media_url'  => $media_url,
                'sort_order' => $sort_order,
                'id'         => $id
            ]);
            sanitize_and_reorder_gallery($pdo);
            $message = "Media galeri berhasil diperbarui!";
        } catch (PDOException $e) {
            $message = "Gagal memperbarui: " . $e->getMessage();
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            sanitize_and_reorder_gallery($pdo);
            $message = "Media galeri berhasil dihapus!";
        } catch (PDOException $e) {}
    }
}

// Otomatis bersihkan urutan ganda
sanitize_and_reorder_gallery($pdo);

// AMBIL SEMUA DATA GALERI
$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$next_gallery_order = count($items) + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri & Dokumentasi (CMS) — Admin JARIA Craft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .gallery-thumb { width: 110px; height: 65px; object-fit: cover; border-radius: 8px; }
        
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
    <!-- HEADER BAR DESAIN EKSKLUSIF SAMA DENGAN IMPACT.PHP -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-images me-2"></i>Kelola Galeri & Dokumentasi (CMS)</h3>
            <p class="text-muted small mb-0">Atur Foto & Video Dokumentasi Kegiatan secara Runtut dan Fleksibel</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="../index.php" class="btn btn-back-dashboard rounded-pill">
                &leftarrow; Kembali ke Dashboard
            </a>
            <a href="../../about.php" target="_blank" class="btn btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-eye-fill me-1"></i> Lihat Halaman Depan
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECTION DAFTAR MEDIA TERPASANG -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-collection-play-fill me-2"></i>Daftar Media Dokumentasi Terpasang</h5>
            <button class="btn btn-light text-primary btn-sm rounded-pill fw-bold" onclick="openAddMediaModal(<?php echo $next_gallery_order; ?>)">
                <i class="bi bi-plus-circle me-1"></i>Tambah Media Baru
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="110">Urutan</th>
                            <th width="130">Preview</th>
                            <th>Judul Kegiatan & Caption</th>
                            <th>Tanggal Dibuat</th>
                            <th width="100">Jenis</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada media dokumentasi terpasang.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-secondary"><?php echo $item['sort_order']; ?></span>
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="?reorder_gallery=1&id=<?php echo $item['id']; ?>&dir=up" class="btn btn-light btn-xs p-0 px-1 border" title="Naikkan Urutan"><i class="bi bi-chevron-up"></i></a>
                                                <a href="?reorder_gallery=1&id=<?php echo $item['id']; ?>&dir=down" class="btn btn-light btn-xs p-0 px-1 border" title="Turunkan Urutan"><i class="bi bi-chevron-down"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['type'] === 'image' && !empty($item['file_path'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($item['file_path']); ?>" class="gallery-thumb shadow-sm border">
                                        <?php else: ?>
                                            <div class="bg-primary bg-opacity-10 text-primary gallery-thumb border d-flex align-items-center justify-content-center fw-bold">
                                                <i class="bi bi-play-btn-fill fs-3"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="d-block text-dark"><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <small class="text-muted d-block text-truncate" style="max-width: 320px;"><?php echo htmlspecialchars($item['caption'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-primary fw-semibold" style="font-size: 0.8rem;">
                                            <i class="bi bi-clock me-1"></i><?php echo format_tanggal_indo($item['created_at'] ?? ''); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['type'] === 'image' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo $item['type'] === 'image' ? 'success' : 'danger'; ?> border border-<?php echo $item['type'] === 'image' ? 'success' : 'danger'; ?> border-opacity-25 px-2.5 py-1 rounded-pill small">
                                            <?php echo strtoupper($item['type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick='editMedia(<?php echo json_encode($item); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus media galeri ini?')">
                                            <input type="hidden" name="action_gallery" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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

<!-- MODAL POP-UP FORM TAMBAH / EDIT MEDIA GALERI -->
<div class="modal fade" id="modalMedia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0">
            <input type="hidden" name="action_gallery" id="modal_action" value="create">
            <input type="hidden" name="id" id="modal_id" value="0">
            <input type="hidden" name="existing_file_path" id="modal_existing_file" value="">

            <div class="modal-header bg-primary text-white" id="modal_header">
                <h5 class="modal-title fw-bold" id="modal_title_text">Tambah Media Galeri Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Jenis Media</label>
                        <select name="type" id="modal_type" class="form-select" onchange="toggleInputs()">
                            <option value="image">Foto Dokumentasi (Upload File)</option>
                            <option value="video">Video Preview (Link Embed Youtube/Drive)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Urutan Tampil (*Sort Order)</label>
                        <input type="number" name="sort_order" id="modal_sort" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Judul Kegiatan / Media</label>
                        <input type="text" name="title" id="modal_title" class="form-control" placeholder="Contoh: Pendampingan Menjahit Kain Perca" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Keterangan / Caption</label>
                        <textarea name="caption" id="modal_caption" class="form-control" rows="3" placeholder="Tuliskan cerita singkat tentang foto/video..."></textarea>
                    </div>

                    <div class="col-12" id="photoInputBlock">
                        <label class="form-label fw-bold">Upload Foto</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-1" id="file_status_text"></small>
                    </div>

                    <div class="col-12 d-none" id="videoInputBlock">
                        <label class="form-label fw-bold">Link Embed Video (YouTube / Google Drive)</label>
                        <input type="url" name="media_url" id="modal_media_url" class="form-control" placeholder="Contoh: https://www.youtube.com/embed/XXXXXX">
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="modal_submit_btn">Simpan ke Galeri Web</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleInputs() {
    const type = document.getElementById('modal_type').value;
    document.getElementById('photoInputBlock').classList.toggle('d-none', type !== 'image');
    document.getElementById('videoInputBlock').classList.toggle('d-none', type === 'image');
}

function openAddMediaModal(nextOrder) {
    document.getElementById('modal_action').value = 'create';
    document.getElementById('modal_id').value = 0;
    document.getElementById('modal_type').value = 'image';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_caption').value = '';
    document.getElementById('modal_media_url').value = '';
    document.getElementById('modal_existing_file').value = '';
    document.getElementById('modal_sort').value = nextOrder;
    document.getElementById('file_status_text').innerText = '';

    document.getElementById('modal_title_text').innerText = 'Tambah Media Galeri Baru';
    document.getElementById('modal_header').className = 'modal-header bg-primary text-white';
    document.getElementById('modal_submit_btn').className = 'btn btn-primary rounded-pill px-4 fw-bold';
    document.getElementById('modal_submit_btn').innerText = 'Simpan ke Galeri Web';

    toggleInputs();
    new bootstrap.Modal(document.getElementById('modalMedia')).show();
}

function editMedia(item) {
    document.getElementById('modal_action').value = 'update';
    document.getElementById('modal_id').value = item.id;
    document.getElementById('modal_type').value = item.type;
    document.getElementById('modal_title').value = item.title;
    document.getElementById('modal_caption').value = item.caption || '';
    document.getElementById('modal_media_url').value = item.media_url || '';
    document.getElementById('modal_existing_file').value = item.file_path || '';
    document.getElementById('modal_sort').value = item.sort_order;

    if (item.file_path) {
        document.getElementById('file_status_text').innerText = 'File saat ini: ' + item.file_path + ' (Biarkan kosong jika tidak diganti)';
    } else {
        document.getElementById('file_status_text').innerText = '';
    }

    document.getElementById('modal_title_text').innerText = 'Edit Media Galeri';
    document.getElementById('modal_header').className = 'modal-header bg-warning text-dark';
    document.getElementById('modal_submit_btn').className = 'btn btn-warning rounded-pill px-4 fw-bold';
    document.getElementById('modal_submit_btn').innerText = 'Perbarui Media Galeri';

    toggleInputs();
    new bootstrap.Modal(document.getElementById('modalMedia')).show();
}
</script>
</body>
</html>