<?php
// C:\xampp\htdocs\jaria\contact.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Kontak & Lokasi — JARIA Craft";

// 1. AMBIL BANNER CAROUSEL
$banner_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM contact_banners ORDER BY sort_order ASC, id ASC");
    $banner_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($banner_items)) {
    $banner_items = [
        [
            'image_path' => 'assets/images/banners/banners1.jpg',
            'badge_text' => 'Kesekretariatan & Mitra Pemberdayaan',
            'title' => 'Hubungi Kami & Lokasi Mitra',
            'description' => 'Ingin memesan produk souvenir custom atau bersilaturahmi dengan panti asuhan? Kami siap menyambut Anda.'
        ]
    ];
}

// 2. AMBIL DATA PENGATURAN KONTAK
$cs = [];
try {
    $stmt = $pdo->query("SELECT * FROM contact_settings WHERE id = 1");
    $cs = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($cs)) {
    $cs = [
        'badge_section' => 'Informasi Alamat Lengkap & Layanan',
        'title_section' => 'Lokasi & Kontak Penanggung Jawab',
        'subtitle_section' => 'Hubungi tim mahasiswa atau pengurus panti asuhan untuk konsultasi produk & kerja sama.',
        'institution_name' => 'Yayasan & Panti Asuhan Al-Jihad',
        'institution_sub' => 'Mitra Pemberdayaan Kerajinan Perca JARIA Craft',
        'address' => 'Jl. Jemursari Utara III No.9, Jemur Wonosari, Kecamatan Wonocolo, Kota Surabaya, Jawa Timur 60237',
        'email' => 'jaria.craft@gmail.com',
        'wa_number' => '6285704545150',
        'wa_contact_person' => 'Azizah',
        'wa_display_text' => '+62 857-0454-5150',
        'website_url' => 'https://yayasanaljihad.org/tentang-kami/profil/',
        'initiator_text' => 'Mahasiswa Ilmu Komunikasi Kelas E1 Semester 5 UIN Sunan Ampel Surabaya (UINSA)',
        'maps_embed_url' => 'https://maps.google.com/maps?q=-7.324513,112.7362044&hl=id&z=17&output=embed'
    ];
}

// 3. AMBIL DAFTAR MEDIA SOSIAL DINAMIS
$social_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM contact_socials ORDER BY sort_order ASC, id ASC");
    $social_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        /* HERO CAROUSEL BANNER */
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

        /* KARTU INFORMASI DENGAN EFEK 3D TILT & GLOWING BORDER */
        .info-card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1.5px solid rgba(13, 110, 253, 0.15);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .info-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.28), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        .map-container {
            border-radius: 1.25rem;
            overflow: hidden;
            border: 1.5px solid rgba(13, 110, 253, 0.15);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03);
            height: 100%;
            min-height: 480px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .map-container:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.28), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        /* MEMBANTU SEMUA ELEMEN LINK/TOMBOL BISA DIKLIK DI DALAM 3D TILT */
        .interactive-link {
            position: relative;
            z-index: 10;
            transform: translateZ(30px);
            display: inline-block;
            pointer-events: auto !important;
        }

        .pill-badge-blue {
            background-color: #e8eaff; color: #4f46e5; font-size: 0.82rem; font-weight: 700;
            padding: 0.4rem 1rem; border-radius: 50rem; display: inline-flex; align-items: center; gap: 0.4rem;
        }

        /* SOSMED BUTTON DYNAMIC */
        .social-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.15rem;
            border-radius: 50rem;
            font-weight: 700;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .social-chip:hover {
            opacity: 0.85;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* STYLES TOMBOL WHATSAPP BISA DIKLIK TANPA GLITCH */
        .btn-wa-action {
            position: relative;
            z-index: 10;
            transform: translateZ(35px);
            pointer-events: auto !important;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-wa-action:hover {
            background-color: #157347 !important;
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.35) !important;
        }

        .map-container iframe {
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="contactBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($banner_items as $index => $b): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($b['image_path']); ?>" alt="Banner Kontak JARIA">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div id="contactTextCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner">
                    <?php foreach ($banner_items as $index => $b): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php if (!empty($b['badge_text'])): ?>
                                <div class="animate-hero-badge mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars($b['badge_text']); ?>
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

<!-- KONTEN UTAMA KONTAK & LOKASI -->
<div class="container py-4 pb-5">
    
    <div class="text-center mb-5">
        <?php if (!empty($cs['badge_section'])): ?>
            <span class="pill-badge-blue mb-2"><i class="bi bi-pin-map-fill"></i> <?php echo htmlspecialchars($cs['badge_section']); ?></span>
        <?php endif; ?>
        <h2 class="display-6 fw-extrabold text-dark"><?php echo htmlspecialchars($cs['title_section']); ?></h2>
        <p class="text-muted small"><?php echo htmlspecialchars($cs['subtitle_section']); ?></p>
    </div>

    <div class="row g-4 mb-5">
        <!-- Rincian Alamat, Kontak, Email, & Sosmed -->
        <div class="col-lg-6">
            <div class="info-card js-tilt p-4 p-md-5 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 fs-3">
                            <i class="bi bi-house-heart-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($cs['institution_name']); ?></h4>
                            <small class="text-muted"><?php echo htmlspecialchars($cs['institution_sub']); ?></small>
                        </div>
                    </div>

                    <!-- ALAMAT -->
                    <div class="mb-3.5">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Alamat Resmi Utama:</strong>
                        <p class="text-secondary small ms-4 mb-0 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($cs['address'])); ?>
                        </p>
                    </div>

                    <!-- WHATSAPP -->
                    <div class="mb-3.5">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-whatsapp text-success me-2"></i>Kontak WhatsApp Admin / PJ:</strong>
                        <p class="text-secondary small ms-4 mb-0">
                            <strong><?php echo htmlspecialchars($cs['wa_display_text']); ?></strong> 
                            (a.n. <strong><?php echo htmlspecialchars($cs['wa_contact_person']); ?></strong>)
                        </p>
                    </div>

                    <!-- EMAIL RESMI -->
                    <?php if (!empty($cs['email'])): ?>
                        <div class="mb-3.5">
                            <strong class="d-block text-dark mb-1"><i class="bi bi-envelope-fill text-warning me-2"></i>Email Resmi JARIA:</strong>
                            <p class="text-secondary small ms-4 mb-0">
                                <a href="mailto:<?php echo htmlspecialchars($cs['email']); ?>" class="interactive-link text-decoration-none fw-bold text-dark">
                                    <?php echo htmlspecialchars($cs['email']); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- MEDIA SOSIAL DINAMIS -->
                    <?php if (!empty($social_items)): ?>
                        <div class="mb-3.5">
                            <strong class="d-block text-dark mb-2"><i class="bi bi-share-fill text-primary me-2"></i>Media Sosial Resmi:</strong>
                            <div class="d-flex flex-wrap gap-2 ms-4">
                                <?php foreach ($social_items as $soc): 
                                    $theme = !empty($soc['color_theme']) ? $soc['color_theme'] : 'primary';
                                    $icon  = !empty($soc['icon']) ? $soc['icon'] : 'bi-share-fill';
                                ?>
                                    <a href="<?php echo htmlspecialchars($soc['url']); ?>" target="_blank" class="interactive-link social-chip bg-<?php echo $theme; ?> bg-opacity-10 text-<?php echo $theme; ?> border border-<?php echo $theme; ?> border-opacity-25">
                                        <i class="bi <?php echo htmlspecialchars($icon); ?>"></i> <?php echo htmlspecialchars($soc['platform_name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- WEBSITE -->
                    <?php if (!empty($cs['website_url'])): ?>
                        <div class="mb-3.5">
                            <strong class="d-block text-dark mb-1"><i class="bi bi-globe me-2 text-primary"></i>Website Resmi Yayasan:</strong>
                            <p class="text-secondary small ms-4 mb-0">
                                <a href="<?php echo htmlspecialchars($cs['website_url']); ?>" target="_blank" class="interactive-link text-decoration-none fw-semibold text-primary">
                                    <?php echo htmlspecialchars($cs['website_url']); ?> &rarr;
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- INISIATOR -->
                    <div class="mb-3">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Inisiator Proyek:</strong>
                        <p class="text-secondary small ms-4 mb-0">
                            <?php echo htmlspecialchars($cs['initiator_text']); ?>
                        </p>
                    </div>
                </div>

                <!-- Tombol Chat WhatsApp Direct -->
                <?php 
                    $clean_wa = preg_replace('/[^0-9]/', '', $cs['wa_number']);
                    $wa_url = "https://wa.me/" . $clean_wa . "?text=" . urlencode("Halo Kak " . $cs['wa_contact_person'] . ", saya ingin bertanya mengenai produk JARIA Craft");
                ?>
                <div class="pt-3 border-top mt-4">
                    <a href="<?php echo $wa_url; ?>" target="_blank" class="btn btn-success btn-wa-action w-100 rounded-pill py-3 fw-bold shadow-sm">
                        <i class="bi bi-whatsapp fs-5 me-2"></i> Chat WhatsApp via <?php echo htmlspecialchars($cs['wa_contact_person']); ?> (<?php echo htmlspecialchars($cs['wa_display_text']); ?>)
                    </a>
                </div>
            </div>
        </div>

        <!-- Google Maps Embed Canvas -->
        <div class="col-lg-6">
            <div class="map-container js-tilt">
                <iframe class="w-100 h-100 border-0" 
                        src="<?php echo htmlspecialchars($cs['maps_embed_url']); ?>" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                </iframe>
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
// INISIALISASI EFEK 3D TILT UNTUK KARTU KONTAK DAN MAPS (.js-tilt)
VanillaTilt.init(document.querySelectorAll(".js-tilt"), {
    max: 10,            // Derajat kemiringan maksimal
    speed: 400,         // Kecepatan transisi pergerakan
    glare: true,        // Efek pantulan kilat cahaya
    "max-glare": 0.15,  // Kecerahan kilat cahaya
    scale: 1.01         // Efek membesar halus
});

// SINKRONISASI CAROUSEL BANNER GAMBAR & TEKS
var bgCarousel = document.getElementById('contactBgCarousel');
var textCarousel = document.getElementById('contactTextCarousel');

if (bgCarousel && textCarousel) {
    var textBsCarousel = new bootstrap.Carousel(textCarousel);
    bgCarousel.addEventListener('slide.bs.carousel', function (e) {
        textBsCarousel.to(e.to);
    });
}
</script>
</body>
</html>