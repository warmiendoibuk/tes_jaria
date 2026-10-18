<?php
// C:\xampp\htdocs\jaria\impact.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Dampak Sosial — JARIA Craft";

// 1. AMBIL BANNER CAROUSEL DARI DATABASE CMS
$banner_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM impact_banners ORDER BY sort_order ASC, id ASC");
    $banner_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($banner_items)) {
    $banner_items = [
        [
            'image_path' => 'assets/images/banners/banners1.jpg',
            'badge_text' => 'Jejak Kebaikan Pembelian Anda',
            'title' => 'Dampak Sosial JARIA Craft',
            'description' => 'Setiap produk yang Anda beli bukan sekadar kerajinan, melainkan investasi bagi kemandirian dan pendidikan anak-anak Panti Asuhan Al-Jihad Surabaya.'
        ]
    ];
}

// 2. AMBIL PILAR DAMPAK SOSIAL DARI DATABASE CMS
$impact_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM impact_items ORDER BY sort_order ASC, id ASC");
    $impact_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
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

        /* HERO CAROUSEL HEADER BANNER */
        .hero-section {
            position: relative;
            min-height: 360px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background-color: #111126;
            border-radius: 1.5rem;
        }
        .hero-bg-carousel {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1;
        }
        .hero-bg-carousel .carousel-item { height: 100%; }
        .hero-bg-carousel img {
            width: 100%;
            height: 360px;
            object-fit: cover;
            opacity: 0.55;
        }
        .hero-overlay-content {
            position: relative;
            z-index: 3;
            color: #ffffff;
            padding: 2.5rem 2rem;
            max-width: 800px;
        }
        .hero-title-box {
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            padding: 0.85rem 1.4rem;
            border-radius: 1rem;
            border-left: 5px solid #0d6efd;
            display: inline-block;
        }

        /* ANIMASI TEKS CAROUSEL FADE-IN-UP */
        @keyframes heroTextFadeUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-hero-badge { animation: heroTextFadeUp 0.6s ease-out forwards; }
        .animate-hero-title { animation: heroTextFadeUp 0.8s ease-out 0.2s forwards; opacity: 0; }
        .animate-hero-desc  { animation: heroTextFadeUp 0.8s ease-out 0.4s forwards; opacity: 0; }

        /* PERSPECTIVE WRAPPER UNTUK EFEK 3D TILT */
        .card-3d-wrapper { perspective: 1000px; }

        /* STYLING KARTU DAMPAK DENGAN EFEK 3D TILT REALISTIS & PADDING LEGA */
        .impact-card {
            background: #ffffff;
            border: 1.5px solid rgba(13, 110, 253, 0.12);
            border-radius: 1.35rem;
            position: relative;
            padding: 2.25rem 1.85rem !important;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }

        .impact-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.2), 0 0 20px rgba(13, 110, 253, 0.12);
        }

        .impact-icon-wrapper {
            width: 65px; height: 65px;
            border-radius: 1.15rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.85rem;
            margin-bottom: 1.35rem;
            transform: translateZ(30px);
            transition: transform 0.3s ease;
        }

        .impact-card h5 { transform: translateZ(25px); margin-bottom: 0.85rem; line-height: 1.35; }
        .impact-card p { transform: translateZ(15px); line-height: 1.7; margin-bottom: 1.5rem; }
        .impact-card-footer { transform: translateZ(20px); }

        .pill-badge-red {
            background-color: #ffebe9; color: #dc3545; font-size: 0.82rem; font-weight: 700;
            padding: 0.4rem 1rem; border-radius: 50rem; display: inline-flex; align-items: center; gap: 0.4rem;
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="impactBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($banner_items as $index => $b): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($b['image_path']); ?>" alt="Banner Dampak Sosial JARIA">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div id="impactTextCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner">
                    <?php foreach ($banner_items as $index => $b): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php if (!empty($b['badge_text'])): ?>
                                <div class="animate-hero-badge mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-heart-fill text-danger me-1"></i> <?php echo htmlspecialchars($b['badge_text']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="animate-hero-title mb-3">
                                <div class="hero-title-box">
                                    <h1 class="display-6 fw-extrabold text-white mb-0"><?php echo htmlspecialchars($b['title']); ?></h1>
                                </div>
                            </div>

                            <?php if (!empty($b['description'])): ?>
                                <p class="lead text-white-50 mb-0 fs-5 animate-hero-desc">
                                    <?php echo htmlspecialchars($b['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KONTEN UTAMA PILAR DAMPAK SOSIAL -->
<div class="container py-4 pb-5">
    <div class="text-center mb-5">
        <span class="pill-badge-red mb-2"><i class="bi bi-award-fill"></i> Niat Baik Mengubah Masa Depan</span>
        <h2 class="display-6 fw-extrabold text-dark"><?php echo count($impact_items); ?> Pilar Dampak Pembelian Anda</h2>
        <p class="text-muted small">Arahkan kursor untuk merasakan efek 3D Tilt, atau klik pilar untuk melihat rincian lengkapnya.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($impact_items as $idx => $item): 
            $modal_id = 'impactModal_' . $item['id'];
        ?>
            <div class="col-md-6 col-lg-4 card-3d-wrapper">
                <div class="card impact-card js-3d-tilt h-100 d-flex flex-column justify-content-between" onclick="showImpactDetail('<?php echo $modal_id; ?>')">
                    <div>
                        <div class="impact-icon-wrapper bg-<?php echo htmlspecialchars($item['color']); ?> bg-opacity-10 text-<?php echo htmlspecialchars($item['color']); ?>">
                            <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                        </div>

                        <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="text-secondary small leading-relaxed"><?php echo htmlspecialchars($item['short_desc']); ?></p>
                    </div>

                    <div class="mt-auto impact-card-footer d-flex align-items-center justify-content-between pt-3 border-top border-light">
                        <span class="badge bg-light text-primary border rounded-pill px-3 py-1.5 small fw-semibold">
                            <i class="bi bi-info-circle me-1"></i> Klik Rincian
                        </span>
                        <i class="bi bi-arrow-right-circle-fill fs-5 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- MODAL DETAIL POP-UP BESAR -->
            <div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                        <div class="modal-header bg-primary text-white p-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary rounded-3 p-2 fs-3">
                                    <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-extrabold mb-0"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <small class="text-white-50">Program Pemberdayaan Panti Asuhan Al-Jihad</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4 p-md-5">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-body-text text-primary me-2"></i>Penjelasan Lengkap Program:</h6>
                            <p class="text-secondary leading-relaxed mb-4" style="font-size: 1rem; line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($item['full_desc'])); ?>
                            </p>

                            <?php if (!empty($item['metrics'])): ?>
                                <div class="bg-light rounded-3 p-3.5 border-start border-4 border-primary mb-3">
                                    <strong class="d-block text-dark small mb-1"><i class="bi bi-graph-up-arrow text-success me-1"></i> Indikator Keberhasilan & Dampak Real:</strong>
                                    <span class="text-muted small"><?php echo htmlspecialchars($item['metrics']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="modal-footer bg-light border-0 px-4 py-3 justify-content-between">
                            <small class="text-muted"><i class="bi bi-heart-fill text-danger me-1"></i> Terima kasih atas dukungan Anda!</small>
                            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Tutup Rincian</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FOOTER DINAMIS -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- VANILLA TILT JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tiltElements = document.querySelectorAll(".js-3d-tilt");
    if (typeof VanillaTilt !== "undefined" && tiltElements.length > 0) {
        VanillaTilt.init(tiltElements, {
            max: 18,
            speed: 400,
            glare: true,
            "max-glare": 0.25,
            scale: 1.03,
            perspective: 1000
        });
    }
});

function showImpactDetail(modalId) {
    var impactModal = new bootstrap.Modal(document.getElementById(modalId));
    impactModal.show();
}

var bgCarousel = document.getElementById('impactBgCarousel');
var textCarousel = document.getElementById('impactTextCarousel');

if (bgCarousel && textCarousel) {
    var textBsCarousel = new bootstrap.Carousel(textCarousel);
    bgCarousel.addEventListener('slide.bs.carousel', function (e) {
        textBsCarousel.to(e.to);
    });
}
</script>
</body>
</html>