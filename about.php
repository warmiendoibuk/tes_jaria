<?php
// C:\xampp\htdocs\jaria\about.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Kisah Kami — JARIA Craft | Jahit Ceria Panti Asuhan Al-Jihad";

// 1. AMBIL NARASI UTAMA DARI DATABASE (CMS)
$story_settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM story_settings WHERE id = 1");
    $story_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($story_settings)) {
    $story_settings = [
        'badge_text' => 'Kolaborasi Sosial & Pemberdayaan',
        'title_prefix' => 'Di Balik Sentuhan Karya',
        'title_highlight' => 'JARIA Craft',
        'lead_text' => 'JARIA merupakan singkatan dari <span class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-0.5 rounded-3 fw-bold">Jahit Ceria</span> — sebuah inisiatif wirausaha sosial yang menghadirkan keceriaan dan keterampilan melalui setiap lembar karya kain perca.',
        'paragraph_1' => 'Program ini merupakan wujud aksi nyata dalam mata kuliah <strong>Proyek Kewirausahaan Sosial</strong> yang diinisiasi oleh <strong>Mahasiswa Ilmu Komunikasi Kelas E1 Semester 5 UIN Sunan Ampel Surabaya (UINSA)</strong> berkolaborasi secara langsung dengan <strong>anak-anak Panti Asuhan Al-Jihad Surabaya</strong>.',
        'paragraph_2' => 'Melalui kegiatan olah kain perca menjadi berbagai produk kerajinan tangan bernilai jual, kami tidak hanya memanfaatkan kembali limbah kain konveksi, melainkan juga menanamkan jiwa kewirausahaan, kreativitas, dan rasa percaya diri pada adik-adik panti.',
        'btn_1_text' => 'Lihat Karya Kerajinan',
        'btn_1_link' => 'catalog.php',
        'btn_2_text' => 'Dampak Sosial',
        'btn_2_link' => 'impact.php',
        'card_title' => 'Sinergi Pendidikan & Sosial'
    ];
}

// 2. AMBIL POIN SINERGI DARI DATABASE (CMS)
$story_points = [];
try {
    $stmt = $pdo->query("SELECT * FROM story_points WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $story_points = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// 3. AMBIL DATA GALERI DOKUMENTASI (BERURUTAN DARI CMS)
$gallery_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC");
    $gallery_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

function format_tanggal_indo_about($datetime) {
    if (empty($datetime)) return '';
    $time = strtotime($datetime);
    $hari_arr = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan_arr = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $hari = $hari_arr[date('l', $time)] ?? '';
    $tgl = date('d', $time);
    $bln = $bulan_arr[(int)date('m', $time)] ?? '';
    $thn = date('Y', $time);
    $jam = date('H:i', $time);

    return "$hari, $tgl $bln $thn — $jam WIB";
}

function get_gallery_url($path_or_filename) {
    if (empty($path_or_filename)) {
        return 'https://via.placeholder.com/800x480?text=Dokumentasi+JARIA+Craft';
    }
    if (str_starts_with($path_or_filename, 'assets/')) {
        return $path_or_filename;
    }
    $clean_filename = basename($path_or_filename);
    return 'assets/images/gallery/' . $clean_filename;
}

function analyze_social_media($url) {
    if (empty($url)) return ['type' => 'none', 'url' => ''];

    if (str_contains($url, 'youtu.be/') || str_contains($url, 'youtube.com/')) {
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|shorts\/|watch\?.+&v=))([\w-]{11})/', $url, $matches);
        if (!empty($matches[1])) {
            return ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/' . $matches[1] . '?enablejsapi=1&rel=0'];
        }
    }

    if (str_contains($url, 'tiktok.com')) {
        preg_match('/video\/(\d+)/', $url, $matches);
        return ['type' => 'tiktok', 'url' => $url, 'video_id' => $matches[1] ?? ''];
    }

    if (str_contains($url, 'instagram.com')) {
        preg_match('/(?:p|reel|reels)\/([A-Za-z0-9_-]+)/', $url, $matches);
        $post_id = $matches[1] ?? '';
        return ['type' => 'instagram', 'url' => 'https://www.instagram.com/p/' . $post_id . '/embed/'];
    }

    if (str_contains($url, 'drive.google.com/')) {
        return ['type' => 'drive', 'url' => str_replace(['/view', '/edit'], '/preview', $url)];
    }

    return ['type' => 'iframe_generic', 'url' => $url];
}
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfbf7; color: #2b2b2b; }
        .navbar-brand-img { height: 38px; object-fit: contain; }

        @keyframes customFadeInUp {
            0% { opacity: 0; transform: translateY(30px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .animate-fade-in { animation: customFadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .delay-1 { animation-delay: 0.15s; }
        .delay-2 { animation-delay: 0.3s; }

        .story-title { font-size: 2.8rem; font-weight: 800; line-height: 1.25; color: #111827; }
        .story-lead { font-size: 1.2rem; font-weight: 700; color: #0d6efd; line-height: 1.6; }
        .story-body-text { font-size: 1.02rem; color: #4b5563; line-height: 1.8; }

        /* ANIMASI MELAYANG KONTINU */
        @keyframes floatContinuous {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* ANIMASI SPARKLING */
        @keyframes sparkleTwinkle {
            0%, 100% { opacity: 0.2; transform: scale(0.8) rotate(0deg); }
            50% { opacity: 1; transform: scale(1.25) rotate(180deg); }
        }

        .sparkle-icon {
            position: absolute;
            color: #ffea79;
            font-size: 1.1rem;
            animation: sparkleTwinkle 2.5s infinite ease-in-out;
            pointer-events: none;
        }

        .sparkle-1 { top: 15px; right: 20px; animation-delay: 0s; }
        .sparkle-2 { bottom: 20px; left: 25px; animation-delay: 1.2s; }

        .story-card-box {
            position: relative;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border-radius: 1.75rem;
            padding: 2.5rem 2.25rem;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 18px 40px rgba(13, 110, 253, 0.25), 0 0 25px rgba(13, 110, 253, 0.15);
            animation: floatContinuous 4.5s ease-in-out infinite;
            transition: box-shadow 0.4s ease, border-color 0.4s ease;
            overflow: hidden;
        }

        .story-card-box:hover {
            animation-play-state: paused;
            box-shadow: 0 25px 55px rgba(13, 110, 253, 0.4), 0 0 35px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .interactive-point-item {
            position: relative;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .interactive-point-item:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2), 0 0 15px rgba(255, 234, 121, 0.3);
        }

        .icon-check-wrapper {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #ffc107;
            flex-shrink: 0;
            margin-top: 0.1rem;
            filter: drop-shadow(0 0 6px rgba(255, 193, 7, 0.5));
        }

        .point-toggle-icon { 
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); 
            color: rgba(255, 255, 255, 0.7);
            flex-shrink: 0;
            margin-top: 0.25rem;
        }
        .interactive-point-item.active .point-toggle-icon { 
            transform: rotate(90deg); 
            color: #ffc107;
        }

        .smooth-detail-wrapper {
            max-height: 0; opacity: 0; overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease, margin-top 0.4s ease, padding-top 0.4s ease;
            margin-top: 0; padding-top: 0;
        }

        .interactive-point-item.active .smooth-detail-wrapper {
            max-height: 500px; opacity: 1; margin-top: 1rem; padding-top: 0.85rem;
        }

        /* GAMBAR CAROUSEL */
        .gallery-card-img {
            height: 500px;
            object-fit: cover;
            cursor: zoom-in;
            transition: transform 0.4s ease;
        }

        .gallery-card-img:hover {
            transform: scale(1.02);
        }

        /* TOMBOL PANAH CAROUSEL DIPERINGKAS TINGGINYA AGAR TIDAK MENUTUPI KONTROL VIDEO */
        .carousel-control-prev, .carousel-control-next {
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            border-radius: 50%;
        }
        .carousel-control-prev { left: 15px; }
        .carousel-control-next { right: 15px; }

        /* CAPTION OVERLAY GAMBAR */
        .carousel-caption-overlay {
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.85) 100%);
            position: absolute; bottom: 0; left: 0; right: 0; 
            padding: 20px 20px 15px 20px; 
            color: #fff;
            pointer-events: none;
            z-index: 5;
        }

        /* HEADER CAPTION KHUSUS VIDEO (DIPINDAH KE ATAS UTK BEBAS KLIK) */
        .video-header-info {
            background: #111827;
            padding: 12px 18px;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .media-player-box {
            height: 450px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #000000;
            color: #ffffff;
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            overflow: hidden;
            position: relative;
        }

        /* IFRAME FULL INTERAKTIF TANPA PENGHALANG */
        .carousel-video-iframe {
            width: 100%;
            height: 450px;
            border: 0;
            position: relative;
            z-index: 1;
        }

        .modal-fullscreen-img {
            max-height: 80vh;
            object-fit: contain;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4 animate-fade-in">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none fw-semibold">Beranda</a></li>
            <li class="breadcrumb-item active fw-bold text-secondary" aria-current="page">Kisah Kami</li>
        </ol>
    </nav>

    <!-- HEADER NARASI UTAMA -->
    <div class="row align-items-center gy-5 mb-5">
        <div class="col-lg-7 animate-fade-in delay-1">
            <?php if (!empty($story_settings['badge_text'])): ?>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
                    <i class="bi bi-heart-fill me-1"></i> <?php echo htmlspecialchars($story_settings['badge_text']); ?>
                </span>
            <?php endif; ?>

            <h1 class="story-title mb-3">
                <?php echo htmlspecialchars($story_settings['title_prefix']); ?> 
                <span class="text-primary"><?php echo htmlspecialchars($story_settings['title_highlight']); ?></span>
            </h1>
            
            <?php if (!empty($story_settings['lead_text'])): ?>
                <p class="story-lead mb-3"><?php echo $story_settings['lead_text']; ?></p>
            <?php endif; ?>

            <?php if (!empty($story_settings['paragraph_1'])): ?>
                <p class="story-body-text mb-3"><?php echo $story_settings['paragraph_1']; ?></p>
            <?php endif; ?>

            <?php if (!empty($story_settings['paragraph_2'])): ?>
                <p class="story-body-text mb-4"><?php echo $story_settings['paragraph_2']; ?></p>
            <?php endif; ?>

            <div class="d-flex gap-3 flex-wrap">
                <?php if (!empty($story_settings['btn_1_text'])): ?>
                    <a href="<?php echo htmlspecialchars($story_settings['btn_1_link'] ?: 'catalog.php'); ?>" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                        <i class="bi bi-bag-check me-1"></i> <?php echo htmlspecialchars($story_settings['btn_1_text']); ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($story_settings['btn_2_text'])): ?>
                    <a href="<?php echo htmlspecialchars($story_settings['btn_2_link'] ?: 'impact.php'); ?>" class="btn btn-outline-primary rounded-pill px-4 py-2.5 fw-bold">
                        <?php echo htmlspecialchars($story_settings['btn_2_text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- KARTU BIRU UTAMA -->
        <div class="col-lg-5 animate-fade-in delay-2">
            <div class="story-card-box">
                <i class="bi bi-stars sparkle-icon sparkle-1"></i>
                <i class="bi bi-sparkles sparkle-icon sparkle-2"></i>

                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-white-50">
                    <h4 class="fw-extrabold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-mortarboard-fill text-warning"></i><?php echo htmlspecialchars($story_settings['card_title']); ?>
                    </h4>
                    <span class="badge bg-white text-primary fw-bold small px-3 py-1.5 rounded-pill shadow-sm"><i class="bi bi-cursor-fill me-1"></i>Klik Poin</span>
                </div>
                
                <?php if (!empty($story_points)): ?>
                    <div class="d-flex flex-column gap-3" id="storyPointList">
                        <?php foreach ($story_points as $p): ?>
                            <div class="interactive-point-item" onclick="togglePointAccordion(this)">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-check-wrapper">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                        <div>
                                            <strong class="fs-6 text-white d-block mb-1"><?php echo htmlspecialchars($p['title']); ?></strong>
                                            <p class="mb-0 text-white-80 small leading-relaxed"><?php echo htmlspecialchars($p['description']); ?></p>
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right point-toggle-icon fs-6"></i>
                                </div>

                                <div class="smooth-detail-wrapper">
                                    <div class="border-top border-white-25 pt-2 text-white-90 small">
                                        <div class="bg-black bg-opacity-25 p-3 rounded-3 mt-1">
                                            <i class="bi bi-info-circle text-warning me-1"></i>
                                            <?php echo nl2br(htmlspecialchars(!empty($p['detail_text']) ? $p['detail_text'] : $p['description'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CAROUSEL DOKUMENTASI KEGIATAN -->
    <div class="my-5 pt-3">
        <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill fw-bold mb-2">DOKUMENTASI KEGIATAN</span>
            <h2 class="fw-extrabold text-dark">Galeri Dokumentasi JARIA Craft</h2>
            <p class="text-muted"><i class="bi bi-zoom-in me-1"></i>Klik foto untuk memperbesar. Video YouTube dapat diputar & diatur volumenya secara langsung.</p>
        </div>

        <div class="bg-white p-3 p-md-4 rounded-4 shadow-sm border">
            <?php if (!empty($gallery_items)): ?>
                <div id="storyGalleryCarousel" class="carousel slide carousel-fade overflow-hidden rounded-3" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-indicators" style="z-index: 12;">
                        <?php foreach ($gallery_items as $idx => $g): ?>
                            <button type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide-to="<?php echo $idx; ?>" class="<?php echo $idx === 0 ? 'active' : ''; ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <div class="carousel-inner">
                        <?php foreach ($gallery_items as $idx => $g): 
                            $type      = $g['type'] ?? 'image';
                            $img_file  = $g['file_path'] ?? ($g['file'] ?? '');
                            $img_src   = get_gallery_url($img_file); 
                            $media     = analyze_social_media($g['media_url'] ?? '');
                            $g_title   = $g['title'] ?? 'Dokumentasi Kegiatan';
                            $g_desc    = $g['caption'] ?? ($g['description'] ?? '');
                            $g_waktu   = format_tanggal_indo_about($g['created_at'] ?? '');
                        ?>
                            <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <?php if ($type === 'video'): ?>
                                    <!-- HEADER INFORMASI DOKUMENTASI VIDEO -->
                                    <div class="video-header-info d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-white">
                                                <i class="bi bi-play-circle-fill text-danger me-2"></i><?php echo htmlspecialchars($g_title); ?>
                                            </h6>
                                            <?php if (!empty($g_desc)): ?>
                                                <small class="text-white-50 d-block text-truncate" style="max-width: 500px;"><?php echo htmlspecialchars($g_desc); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($g_waktu)): ?>
                                            <small class="text-warning fw-semibold"><i class="bi bi-clock me-1"></i><?php echo $g_waktu; ?></small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="media-player-box">
                                        <?php if ($media['type'] === 'tiktok'): ?>
                                            <!-- TIKTOK PLAYER -->
                                            <div class="p-3 text-center" id="tiktok-container-<?php echo $idx; ?>">
                                                <i class="bi bi-tiktok text-danger display-3 mb-2"></i>
                                                <h5 class="fw-bold mb-1 text-white"><?php echo htmlspecialchars($g_title); ?></h5>
                                                <p class="text-white-50 small mb-3"><?php echo htmlspecialchars($g_desc); ?></p>
                                                <button class="btn btn-danger rounded-pill px-4 py-2 fw-bold" onclick="loadTikTokEmbed(<?php echo $idx; ?>, '<?php echo htmlspecialchars($media['url']); ?>', '<?php echo htmlspecialchars($media['video_id']); ?>')">
                                                    <i class="bi bi-play-circle-fill me-2"></i>Muat & Putar Video TikTok
                                                </button>
                                            </div>
                                        <?php elseif ($media['type'] === 'instagram'): ?>
                                            <!-- INSTAGRAM PLAYER -->
                                            <iframe src="<?php echo htmlspecialchars($media['url']); ?>" class="carousel-video-iframe" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
                                        <?php else: ?>
                                            <!-- YOUTUBE & DRIVE (SUDAH BEBAS KLIK KONTROL & FULLSCREEN) -->
                                            <iframe src="<?php echo htmlspecialchars($media['url']); ?>" class="carousel-video-iframe" title="<?php echo htmlspecialchars($g_title); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- GAMBAR BISA DIKLIK UNTUK FULLSCREEN LIGHTBOX -->
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                         class="d-block w-100 gallery-card-img" 
                                         alt="<?php echo htmlspecialchars($g_title); ?>"
                                         onclick="openLightboxModal(<?php echo $idx; ?>)">
                                    
                                    <div class="carousel-caption-overlay text-start">
                                        <h5 class="fw-bold mb-1 text-white">
                                            <i class="bi bi-camera-fill text-warning me-2"></i><?php echo htmlspecialchars($g_title); ?>
                                        </h5>
                                        <?php if (!empty($g_desc)): ?>
                                            <p class="mb-1 text-white-80 small"><?php echo htmlspecialchars($g_desc); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($g_waktu)): ?>
                                            <small class="text-warning fw-semibold d-block mt-1" style="font-size: 0.8rem;">
                                                <i class="bi bi-clock me-1"></i><?php echo $g_waktu; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="carousel-control-prev bg-dark bg-opacity-75" type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next bg-dark bg-opacity-75" type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-images text-muted fs-1 d-block mb-2" style="opacity: 0.4;"></i>
                    <p class="text-secondary mb-0">Belum Ada Dokumentasi yang Ditampilkan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL LIGHTBOX POP-UP FULLSCREEN -->
<div class="modal fade" id="galleryLightBoxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark text-white border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom border-secondary pb-3">
                <h6 class="modal-title fw-bold text-light"><i class="bi bi-images text-warning me-2"></i>Dokumentasi Kegiatan JARIA Craft</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="modalGalleryCarousel" class="carousel slide carousel-fade" data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($gallery_items as $idx => $g): 
                            $type      = $g['type'] ?? 'image';
                            $img_file  = $g['file_path'] ?? ($g['file'] ?? '');
                            $img_src   = get_gallery_url($img_file); 
                            $media     = analyze_social_media($g['media_url'] ?? '');
                            $g_title   = $g['title'] ?? 'Dokumentasi Kegiatan';
                            $g_desc    = $g['caption'] ?? ($g['description'] ?? '');
                            $g_waktu   = format_tanggal_indo_about($g['created_at'] ?? '');
                        ?>
                            <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <?php if ($type === 'video'): ?>
                                    <div class="ratio ratio-16x9 rounded my-2 mx-auto" style="max-width: 850px;">
                                        <iframe src="<?php echo htmlspecialchars($media['url']); ?>" title="<?php echo htmlspecialchars($g_title); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="img-fluid modal-fullscreen-img mb-3" alt="<?php echo htmlspecialchars($g_title); ?>">
                                <?php endif; ?>

                                <h4 class="fw-bold text-warning mb-2"><?php echo htmlspecialchars($g_title); ?></h4>
                                <?php if (!empty($g_desc)): ?>
                                    <p class="text-white-50 fs-6 mb-2" style="max-width: 700px; margin: 0 auto;"><?php echo htmlspecialchars($g_desc); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($g_waktu)): ?>
                                    <small class="text-info fw-semibold d-block mt-2">
                                        <i class="bi bi-clock me-1"></i><?php echo $g_waktu; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="carousel-control-prev" type="button" data-bs-target="#modalGalleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-secondary bg-opacity-75 p-3 rounded-circle"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#modalGalleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-secondary bg-opacity-75 p-3 rounded-circle"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER DINAMIS -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var carouselEl = document.querySelector('#storyGalleryCarousel');
    if (carouselEl) {
        var myCarousel = new bootstrap.Carousel(carouselEl, {
            interval: 5000,
            ride: 'carousel',
            pause: 'hover'
        });

        // HENTIKAN GESER OTOMATIS JIKA PENGGUNA SEDANG MEMUTAR VIDEO
        carouselEl.addEventListener('slide.bs.carousel', function () {
            var activeIframe = carouselEl.querySelector('.carousel-item.active iframe');
            if (activeIframe) {
                activeIframe.src = activeIframe.src; // Reset video jika berganti slide
            }
        });
    }
});

function togglePointAccordion(element) {
    const allItems = document.querySelectorAll('.interactive-point-item');
    const isCurrentlyActive = element.classList.contains('active');
    
    allItems.forEach(item => { item.classList.remove('active'); });

    if (!isCurrentlyActive) {
        element.classList.add('active');
    }
}

function openLightboxModal(slideIndex) {
    var modalEl = document.getElementById('galleryLightBoxModal');
    var modalCarousel = document.getElementById('modalGalleryCarousel');
    
    var carousel = bootstrap.Carousel.getOrCreateInstance(modalCarousel);
    carousel.to(slideIndex);

    var modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function loadTikTokEmbed(index, videoUrl, videoId) {
    var container = document.getElementById('tiktok-container-' + index);
    if (!container) return;

    container.innerHTML = '<blockquote class="tiktok-embed" cite="' + videoUrl + '" data-video-id="' + videoId + '" style="max-width: 325px; min-width: 280px; width: 100%; margin: 0 auto;"><section></section></blockquote>';

    if (!document.getElementById('tiktok-sdk-script')) {
        var script = document.createElement('script');
        script.id = 'tiktok-sdk-script';
        script.src = 'https://www.tiktok.com/embed.js';
        script.async = true;
        document.body.appendChild(script);
    } else if (window.tiktokEmbed) {
        window.tiktokEmbed.lib.render();
    }
}
</script>
</body>
</html>