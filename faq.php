<?php
// C:\xampp\htdocs\jaria\faq.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "FAQ — Pertanyaan Sering Diajukan | JARIA Craft";

// 1. AMBIL BANNER CAROUSEL DARI DATABASE
$banner_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM faq_banners ORDER BY sort_order ASC, id ASC");
    $banner_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fallback jika database belum terisi
if (empty($banner_items)) {
    $banner_items = [
        [
            'image_path' => 'assets/images/banners/banners1.jpg',
            'badge_text' => 'Bantuan & Informasi',
            'title' => 'Pertanyaan Sering Diajukan',
            'description' => 'Temukan jawaban lengkap seputar pemesanan, produk kerajinan perca, dan dampak sosial pemberdayaan JARIA Craft.'
        ]
    ];
}

// 2. AMBIL DAFTAR FAQ DARI DATABASE
$faqs = [];
try {
    $stmt = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        /* HERO CAROUSEL BANNER & ANIMASI TEKS */
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

        /* ANIMASI TEKS CAROUSEL FADE-IN-UP DARI BAWAH */
        @keyframes heroTextFadeUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-hero-badge { animation: heroTextFadeUp 0.6s ease-out forwards; }
        .animate-hero-title { animation: heroTextFadeUp 0.8s ease-out 0.2s forwards; opacity: 0; }
        .animate-hero-desc  { animation: heroTextFadeUp 0.8s ease-out 0.4s forwards; opacity: 0; }

        /* STYLE DAN HOVER ACCORDION FAQ DENGAN EFEK 3D TILT & GLOWING BORDER */
        .accordion-item {
            border: 1.5px solid rgba(13, 110, 253, 0.15);
            border-radius: 1.25rem !important;
            overflow: hidden;
            margin-bottom: 1.2rem;
            background: #ffffff;
            box-shadow: 0 6px 18px rgba(0,0,0,0.03);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }

        .accordion-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.28), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        .accordion-button {
            font-weight: 700;
            padding: 1.3rem 1.5rem;
            font-size: 1.05rem;
            background-color: #ffffff;
            color: #111827;
            border-radius: 1.25rem !important;
        }

        .accordion-button:not(.collapsed) {
            background-color: #f0f7ff;
            color: #0d6efd;
            box-shadow: none;
        }

        .accordion-body {
            padding: 1.25rem 1.5rem 1.5rem;
            font-size: 0.98rem;
            line-height: 1.7;
            color: #4b5563;
        }

        /* KOTAK BANTUAN FAQ DENGAN GLOWING SHADOW & TILT */
        .faq-help-box {
            background: #ffffff;
            border: 1.5px solid rgba(13, 110, 253, 0.2);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            margin-top: 3rem;
            text-align: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }

        .faq-help-box:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.25), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        .btn-bubble-bounce {
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
        }

        .btn-bubble-bounce:hover {
            transform: scale(1.04);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="faqBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($banner_items as $index => $b): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($b['image_path']); ?>" alt="Banner FAQ">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div id="faqTextCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner">
                    <?php foreach ($banner_items as $index => $b): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php if (!empty($b['badge_text'])): ?>
                                <div class="animate-hero-badge mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-question-circle-fill text-danger me-1"></i> <?php echo htmlspecialchars($b['badge_text']); ?>
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

<!-- KONTEN UTAMA FAQ -->
<div class="container py-4 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $index => $faq): 
                    $icon_class = !empty($faq['icon']) ? $faq['icon'] : 'bi-question-circle-fill';
                    $color_class = !empty($faq['color']) ? $faq['color'] : 'primary';
                ?>
                    <div class="accordion-item js-tilt">
                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                            <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                <i class="bi <?php echo htmlspecialchars($icon_class); ?> text-<?php echo htmlspecialchars($color_class); ?> me-3 fs-5"></i>
                                <?php echo htmlspecialchars($faq['question']); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <span class="badge bg-<?php echo htmlspecialchars($color_class); ?> bg-opacity-10 text-<?php echo htmlspecialchars($color_class); ?> mb-2 px-3 py-1 rounded-pill small">
                                    <?php echo htmlspecialchars($faq['category']); ?>
                                </span>
                                <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- KOTAK BANTUAN KONTAK TAMBAHAN -->
            <div class="faq-help-box js-tilt">
                <i class="bi bi-headset fs-1 text-primary d-block mb-2"></i>
                <h4 class="fw-bold text-dark mb-2">Masih Memiliki Pertanyaan Lain?</h4>
                <p class="text-secondary small mb-4">Tim kami siap membantu menjawab pertanyaan dan kebutuhan custom pesanan Anda secara langsung.</p>
                <a href="contact.php" class="btn btn-primary btn-bubble-bounce rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                    <i class="bi bi-chat-dots-fill me-1"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER DINAMIS -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- VANILLA TILT JS LIBRARY -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

<script>
// INISIALISASI EFEK 3D TILT UNTUK SEMUA ITEM ACCORDION DAN KOTAK BANTUAN (.js-tilt)
VanillaTilt.init(document.querySelectorAll(".js-tilt"), {
    max: 12,            // Derajat kemiringan maksimal
    speed: 400,         // Kecepatan transisi pergerakan
    glare: true,        // Efek pantulan kilat cahaya
    "max-glare": 0.18,  // Kecerahan kilat cahaya
    scale: 1.015        // Efek membesar halus saat kursor mengarah
});

// SINKRONISASI CAROUSEL BANNER GAMBAR & TEKS
var bgCarousel = document.getElementById('faqBgCarousel');
var textCarousel = document.getElementById('faqTextCarousel');

if (bgCarousel && textCarousel) {
    var textBsCarousel = new bootstrap.Carousel(textCarousel);
    bgCarousel.addEventListener('slide.bs.carousel', function (e) {
        textBsCarousel.to(e.to);
    });
}
</script>
</body>
</html>