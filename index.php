<?php
// C:\xampp\htdocs\jaria\index.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Beranda — JARIA Craft | Jahit Ceria Panti Asuhan Al-Jihad";

// 1. AMBIL HERO SLIDES DARI DATABASE (DYNAMIC CMS)
$hero_slides = [];
try {
    $stmt = $pdo->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $hero_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($hero_slides)) {
    $hero_slides[] = [
        'image' => 'banners1.jpg',
        'badge_text_1' => 'Wirausaha Sosial Pemberdayaan',
        'badge_text_2' => 'Ilkom E1 S5 UINSA × Panti Asuhan Al-Jihad',
        'title' => 'Sentuhan Karya Jahit Ceria',
        'description' => 'Sinergi kreatif Mahasiswa Ilmu Komunikasi Kelas E1 Semester 5 UINSA bersama adik-adik Panti Asuhan Al-Jihad Surabaya dalam menyulap limbah kain perca menjadi kerajinan tangan bernilai tinggi dan berdaya dampak.',
        'btn_text_1' => 'Jelajahi Katalog',
        'btn_link_1' => 'catalog.php',
        'btn_text_2' => 'Kisah Kami →',
        'btn_link_2' => 'about.php'
    ];
}

// 2. AMBIL KATEGORI PILIHAN DINAMIS CMS (HOME CATEGORIES)
$home_categories = [];
try {
    $stmt_cat = $pdo->query("SELECT * FROM home_categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $home_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($home_categories)) {
    $home_categories = [
        ['title' => 'Bros Cantik', 'category_keyword' => 'Bros', 'icon_class' => 'bi-flower1'],
        ['title' => 'Gantung Kunci', 'category_keyword' => 'Keychain', 'icon_class' => 'bi-key-fill'],
        ['title' => 'Tas Selempang', 'category_keyword' => 'Sling Bag', 'icon_class' => 'bi-bag-heart-fill'],
        ['title' => 'Karya Lainnya', 'category_keyword' => 'Kerajinan Lainnya', 'icon_class' => 'bi-stars']
    ];
}

// 3. AMBIL PRODUK UNGGULAN DARI DATABASE (MAKSIMAL 6 PRODUK)
$featured_products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 6");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// 4. AMBIL 3 ULASAN TERATAS (MOST LIKED & TERBARU)
$top_comments = [];
try {
    $stmt_top = $pdo->query("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY likes_count DESC, created_at DESC LIMIT 3");
    $top_comments = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
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

        /* HERO BANNER CAROUSEL */
        .hero-section {
            position: relative;
            min-height: 460px;
            overflow: hidden;
            background-color: #111126;
            border-radius: 1.5rem;
        }

        .hero-bg-carousel { width: 100%; height: 100%; }
        .hero-bg-carousel .carousel-inner,
        .hero-bg-carousel .carousel-item { height: 100%; min-height: 460px; position: relative; }
        .hero-bg-carousel img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.45; z-index: 1; }

        .hero-overlay-content { position: relative; z-index: 5; color: #ffffff; padding: 3.5rem 2.5rem; max-width: 880px; }
        .hero-title-box { background: rgba(0, 0, 0, 0.55); backdrop-filter: blur(6px); padding: 0.8rem 1.4rem; border-radius: 1rem; border-left: 5px solid #0d6efd; display: inline-block; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .carousel-item.active .animate-hero-badge { animation: fadeInUp 0.6s ease-out forwards; }
        .carousel-item.active .animate-hero-title { animation: fadeInUp 0.8s ease-out 0.15s forwards; opacity: 0; }
        .carousel-item.active .animate-hero-desc  { animation: fadeInUp 0.8s ease-out 0.3s forwards; opacity: 0; }
        .carousel-item.active .animate-hero-btn   { animation: fadeInUp 0.8s ease-out 0.45s forwards; opacity: 0; }

        /* KARTU PRODUK DENGAN EFEK HOVER GLOWING & SHADOW MODERN */
        .product-card { 
            border: 1.5px solid rgba(13, 110, 253, 0.15); 
            border-radius: 1.25rem; 
            background: #ffffff; 
            overflow: hidden;
            position: relative;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .product-card:hover { 
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.28), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        .product-img-wrapper { overflow: hidden; position: relative; height: 220px; display: block; }
        .product-img { height: 100%; width: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .product-card:hover .product-img { transform: scale(1.08); }

        /* TOMBOL DETAIL PRODUK HOVER INTERAKTIF */
        .btn-product-detail {
            border: 1.5px solid #0d6efd;
            color: #0d6efd;
            background: transparent;
            transition: all 0.3s ease;
        }
        .product-card:hover .btn-product-detail {
            background-color: #0d6efd;
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(13, 110, 253, 0.4);
        }

        /* EFEK HOVER KATEGORI INTERAKTIF */
        .category-card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1.5px solid rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #2b2b2b;
            display: block;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .category-card:hover { 
            border-color: #0d6efd;
            color: #0d6efd; 
            box-shadow: 0 18px 35px rgba(13, 110, 253, 0.22), 0 0 15px rgba(13, 110, 253, 0.15);
        }
        .category-icon { 
            width: 60px; 
            height: 60px; 
            background: #e8eaff; 
            color: #4f46e5; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.6rem; 
            margin: 0 auto 1rem; 
            transition: all 0.35s ease; 
        }
        .category-card:hover .category-icon { 
            transform: scale(1.18) rotate(10deg); 
            background: #0d6efd; 
            color: #ffffff; 
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3);
        }

        .pill-badge-blue { background-color: #e8eaff; color: #4f46e5; font-size: 0.82rem; font-weight: 700; padding: 0.4rem 1rem; border-radius: 50rem; display: inline-flex; align-items: center; gap: 0.4rem; }

        /* ULASAN CARD STYLING BERANDA DENGAN TILT HOVER */
        .review-card-home {
            background: #ffffff;
            border: 1.5px solid rgba(0, 0, 0, 0.08);
            border-radius: 1.25rem;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 6px 18px rgba(0,0,0,0.03);
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .review-card-home:hover {
            border-color: #0d6efd;
            box-shadow: 0 22px 42px rgba(13, 110, 253, 0.22), 0 0 18px rgba(13, 110, 253, 0.15);
        }

        .rank-badge-home {
            position: absolute;
            top: -12px; right: 18px;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #111; font-weight: 800; font-size: 0.75rem;
            padding: 0.3rem 0.8rem; border-radius: 50rem;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.4);
            z-index: 2;
        }

        .carousel-review-img {
            height: 190px;
            width: 100%;
            object-fit: cover;
            border-radius: 0.85rem;
            cursor: pointer;
        }

        .text-truncate-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* LIGHTBOX POPUP STYLING MODERN */
        .modal-lightbox .modal-content {
            background: rgba(18, 18, 24, 0.95) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1.5rem;
        }
        .lightbox-img {
            max-height: 60vh;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            margin: 0 auto;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .lightbox-caption-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.2rem;
            padding: 1.25rem;
            color: #fff;
            text-align: left;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL CMS DINAMIS -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="homeBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="assets/images/banners/<?php echo htmlspecialchars($slide['image']); ?>" 
                             alt="<?php echo htmlspecialchars($slide['title']); ?>"
                             onerror="this.src='assets/images/banners/banners1.jpg';">

                        <div class="hero-overlay-content">
                            <div class="animate-hero-badge mb-3 d-flex flex-wrap align-items-center gap-2">
                                <?php if (!empty($slide['badge_text_1'])): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-heart-fill text-danger me-1"></i> <?php echo htmlspecialchars($slide['badge_text_1']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($slide['badge_text_2'])): ?>
                                    <span class="badge bg-white bg-opacity-25 text-white backdrop-blur border border-white border-opacity-50 px-3 py-2 rounded-pill fw-semibold shadow-sm">
                                        <i class="bi bi-mortarboard-fill text-warning me-1"></i> <?php echo htmlspecialchars($slide['badge_text_2']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="animate-hero-title mb-3">
                                <div class="hero-title-box">
                                    <h1 class="display-5 fw-extrabold text-white mb-0"><?php echo htmlspecialchars($slide['title']); ?></h1>
                                </div>
                            </div>

                            <?php if (!empty($slide['description'])): ?>
                                <p class="lead text-white-50 mb-4 fs-5 animate-hero-desc" style="line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($slide['description'])); ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-3 animate-hero-btn">
                                <?php if (!empty($slide['btn_text_1'])): ?>
                                    <a href="<?php echo htmlspecialchars($slide['btn_link_1'] ?: 'catalog.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-sm">
                                        <i class="bi bi-bag-check-fill me-2"></i> <?php echo htmlspecialchars($slide['btn_text_1']); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($slide['btn_text_2'])): ?>
                                    <a href="<?php echo htmlspecialchars($slide['btn_link_2'] ?: 'about.php'); ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-bold">
                                        <?php echo htmlspecialchars($slide['btn_text_2']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($hero_slides) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#homeBgCarousel" data-bs-slide="prev" style="z-index: 10;">
                    <span class="carousel-control-prev-icon bg-dark bg-opacity-50 rounded-circle p-3"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homeBgCarousel" data-bs-slide="next" style="z-index: 10;">
                    <span class="carousel-control-next-icon bg-dark bg-opacity-50 rounded-circle p-3"></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KATEGORI PILIHAN DINAMIS CMS -->
<div class="container py-5">
    <div class="text-center mb-4">
        <span class="pill-badge-blue mb-2"><i class="bi bi-grid-fill"></i> Kategori Produk</span>
        <h2 class="display-6 fw-extrabold text-dark">Pilihan Kerajinan Perca</h2>
    </div>

    <div class="row g-4">
        <?php foreach ($home_categories as $hc): ?>
            <div class="col-6 col-md-3">
                <a href="catalog.php?category=<?php echo urlencode($hc['category_keyword']); ?>" class="category-card js-tilt">
                    <div class="category-icon"><i class="bi <?php echo htmlspecialchars($hc['icon_class'] ?: 'bi-stars'); ?>"></i></div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($hc['title']); ?></h6>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- PRODUK UNGGULAN TERBARU -->
<div class="container pb-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <span class="pill-badge-blue mb-2"><i class="bi bi-fire"></i> Karya Terpopuler</span>
            <h2 class="display-6 fw-extrabold text-dark mb-0">Produk Kerajinan Terbaru</h2>
        </div>
        <a href="catalog.php" class="btn btn-outline-primary rounded-pill fw-bold px-3.5 py-2">
            Lihat Semua &rarr;
        </a>
    </div>

    <?php if (!empty($featured_products)): ?>
        <div class="row g-4">
            <?php foreach ($featured_products as $p): 
                $p_name  = $p['name'] ?? ($p['nama_produk'] ?? ($p['nama'] ?? 'Produk Kerajinan'));
                $p_cat   = $p['category'] ?? ($p['kategori'] ?? 'Perca');
                $p_stock = (int)($p['stock'] ?? ($p['stok'] ?? 0));
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card product-card js-tilt shadow-sm h-100">
                        <a href="product.php?id=<?php echo $p['id']; ?>" class="product-img-wrapper">
                            <img src="assets/images/products/<?php echo !empty($p['image']) ? htmlspecialchars($p['image']) : 'default.jpg'; ?>" 
                                 class="product-img" 
                                 alt="<?php echo htmlspecialchars($p_name); ?>" 
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=JARIA+Craft';">
                        </a>
                        
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-2 px-2.5 py-1.5 rounded-pill"><?php echo htmlspecialchars($p_cat); ?></span>
                                <h5 class="fw-bold text-dark mb-1">
                                    <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($p_name); ?>
                                    </a>
                                </h5>
                                <p class="fw-extrabold text-primary fs-5 mb-2"><?php echo format_rupiah($p['price']); ?></p>

                                <div class="mb-3">
                                    <?php if ($p_stock <= 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 rounded-pill small">
                                            <i class="bi bi-x-circle me-1"></i>Stok Habis
                                        </span>
                                    <?php elseif ($p_stock <= 3): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 rounded-pill small">
                                            <i class="bi bi-exclamation-circle me-1"></i>Sisa <?php echo $p_stock; ?> pcs
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 rounded-pill small">
                                            <i class="bi bi-box-seam me-1"></i>Stok: <?php echo $p_stock; ?> pcs
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-product-detail w-100 rounded-pill fw-bold py-2 mt-2">
                                <i class="bi bi-eye me-1"></i> Detail Produk
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-4">
            <i class="bi bi-bag-x fs-1 text-muted d-block mb-2"></i>
            <h5 class="fw-bold text-dark">Belum Ada Produk Ditampilkan</h5>
            <p class="text-muted small mb-0">Produk kerajinan sedang disiapkan. Silakan cek kembali dalam beberapa saat.</p>
        </div>
    <?php endif; ?>
</div>

<!-- SEKSI ULASAN & TESTIMONI TOP 3 DENGAN CAROUSEL FOTO AUTO-SLIDE & LIGHTBOX -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-pill mb-2">⭐ Kata Mereka</span>
                <h2 class="fw-extrabold text-dark mb-0">Ulasan & Testimoni Komunitas</h2>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddReview">
                    <i class="bi bi-pencil-square me-1"></i> Beri Ulasan
                </button>
                <a href="reviews.php" class="btn btn-outline-primary rounded-pill fw-bold px-4">
                    Lihat Semua Ulasan →
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($top_comments)): ?>
                <?php foreach ($top_comments as $idx => $tc): 
                    $pesan_ulasan = $tc['comment'] ?? ($tc['message'] ?? ($tc['testimonial'] ?? ''));
                    $likes = (int)($tc['likes_count'] ?? 0);

                    // Ambil foto-foto ulasan dari tabel testimonial_photos
                    $photos = [];
                    try {
                        $stmt_p =$pdo->prepare("SELECT photo_path FROM testimonial_photos WHERE testimonial_id = :tid");
                        $stmt_p->execute(['tid' =>$tc['id']]);
                        $photos =$stmt_p->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {}
                ?>
                    <div class="col-12 col-md-4">
                        <div class="review-card-home js-tilt h-100 d-flex flex-column justify-content-between" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, 0)">
                            <!-- BADGE MAHKOTA PERINGKAT TOP 3 -->
                            <?php if ($idx === 0): ?>
                                <span class="rank-badge-home"><i class="bi bi-trophy-fill me-1"></i> #1 Terfavorit</span>
                            <?php elseif ($idx === 1): ?>
                                <span class="rank-badge-home"><i class="bi bi-award-fill me-1"></i> #2 Terfavorit</span>
                            <?php elseif ($idx === 2): ?>
                                <span class="rank-badge-home"><i class="bi bi-star-fill me-1"></i> #3 Terfavorit</span>
                            <?php endif; ?>

                            <div>
                                <!-- PROFIL USER -->
                                <div class="d-flex align-items-center gap-2.5 mb-3">
                                    <img src="<?php echo !empty($tc['avatar']) ? htmlspecialchars($tc['avatar']) : 'https://via.placeholder.com/150?text=User'; ?>" 
                                         class="rounded-circle border object-fit-cover" style="width: 48px; height: 48px;" alt="Avatar">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($tc['name']); ?></h6>
                                        <span class="badge bg-light text-secondary border small"><?php echo htmlspecialchars($tc['role'] ?: 'Pembeli'); ?></span>
                                    </div>
                                </div>

                                <!-- RATING -->
                                <div class="text-warning mb-2">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star-fill <?php echo $i <= ($tc['rating'] ?? 5) ? '' : 'text-muted opacity-25'; ?>"></i>
                                    <?php endfor; ?>
                                </div>

                                <!-- TEXT TEKS KOMENTAR -->
                                <p class="text-secondary small mb-3 text-truncate-3">"<?php echo htmlspecialchars($pesan_ulasan); ?>"</p>

                                <!-- CAROUSEL FOTO KARTU BERANDA (AUTO-SLIDE 3 DETIK) -->
                                <?php if (!empty($photos)): ?>
                                    <div class="mb-3" onclick="event.stopPropagation();">
                                        <?php if (count($photos) === 1): ?>
                                            <img src="<?php echo htmlspecialchars($photos[0]); ?>" class="carousel-review-img border shadow-sm" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, 0)">
                                        <?php else: ?>
                                            <div id="homeCarouselRev<?php echo $tc['id']; ?>" class="carousel slide shadow-sm rounded-3 overflow-hidden border" data-bs-ride="carousel" data-bs-interval="3000">
                                                <div class="carousel-inner">
                                                    <?php foreach ($photos as $p_idx =>$p_img): ?>
                                                        <div class="carousel-item <?php echo $p_idx === 0 ? 'active' : ''; ?>">
                                                            <img src="<?php echo htmlspecialchars($p_img); ?>" class="d-block w-100 carousel-review-img" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, <?php echo$p_idx; ?>)">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#homeCarouselRev<?php echo $tc['id']; ?>" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon bg-dark bg-opacity-50 rounded-circle p-2"></span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#homeCarouselRev<?php echo $tc['id']; ?>" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon bg-dark bg-opacity-50 rounded-circle p-2"></span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- FOOTER DATE & LIKE BUTTON -->
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted" onclick="event.stopPropagation();">
                                <span><i class="bi bi-clock me-1"></i><?php echo date('d M Y, H:i', strtotime($tc['created_at'])); ?></span>
                                <button type="button" class="btn btn-sm btn-light rounded-pill border fw-bold text-danger px-3" onclick="likeComment(<?php echo $tc['id']; ?>, this)">
                                    <i class="bi bi-heart-fill me-1"></i><span class="like-count"><?php echo $likes; ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted">Belum ada ulasan. Jadilah yang pertama memberikan ulasan!</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- MODAL FORM REVIEW/ULASAN MULTIPLE FOTO DINAMIS -->
<div class="modal fade" id="modalAddReview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form id="formReview" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-chat-heart-fill me-2"></i>Beri Ulasan & Testimoni</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Anda <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Anisa Rahma" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Sebagai <span class="text-muted">(Status)</span></label>
                            <input type="text" name="role" class="form-control rounded-3" placeholder="Misal: Pembeli / Pendukung">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Rating Bintang</label>
                            <select name="rating" class="form-select rounded-3 fw-bold text-warning">
                                <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                                <option value="4">⭐⭐⭐⭐ (4/5)</option>
                                <option value="3">⭐⭐⭐ (3/5)</option>
                                <option value="2">⭐⭐ (2/5)</option>
                                <option value="1">⭐ (1/5)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Foto Profil <span class="text-muted">(Opsional)</span></label>
                        <input type="file" name="avatar" class="form-control rounded-3" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Ulasan / Pesan <span class="text-danger">*</span></label>
                        <textarea name="comment" class="form-control rounded-3" rows="3" placeholder="Tuliskan pengalaman atau dukungan Anda..." required></textarea>
                    </div>
                    
                    <!-- INPUT MULTIPLE FOTO -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Unggah Foto Dokumentasi / Produk <span class="text-muted">(Pilih Banyak Sekaligus)</span></label>
                        <input type="file" name="photos[]" class="form-control rounded-3 mb-2" accept="image/*" multiple>
                        <small class="text-muted d-block"><i class="bi bi-info-circle me-1"></i>Tekan <strong>Ctrl</strong> (di PC) atau tahan gambar (di HP) untuk memilih banyak foto sekaligus.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Kirim Ulasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LIGHTBOX POPUP FOTO + CAPTION DESKRIPSI -->
<div class="modal fade modal-lightbox" id="modalGalleryViewer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-3 p-md-4 position-relative">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"></button>
            
            <div id="lightboxCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner" id="lightboxCarouselInner"></div>
                
                <button class="carousel-control-prev" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark bg-opacity-75 rounded-circle p-3"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#lightboxCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark bg-opacity-75 rounded-circle p-3"></span>
                </button>
            </div>

            <!-- CAPTION BOX DESKRIPSI ULASAN -->
            <div class="lightbox-caption-box mt-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img id="popAvatar" src="" class="rounded-circle border border-2 border-white object-fit-cover" style="width: 45px; height: 45px;">
                    <div>
                        <h6 class="fw-bold text-white mb-0" id="popName"></h6>
                        <span class="badge bg-warning text-dark fw-bold px-2 py-0.5 rounded-pill small" id="popRole"></span>
                    </div>
                    <div class="ms-auto text-warning fs-6" id="popStars"></div>
                </div>
                <p class="text-white-50 mb-0 fs-6" id="popComment" style="line-height: 1.6; white-space: pre-line;"></p>
            </div>
        </div>
    </div>
</div>

<!-- PEMANGGILAN FOOTER DINAMIS DATABASE -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- VANILLA TILT JS LIBRARY -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

<script>
// INISIALISASI EFEK 3D TILT UNTUK SEMUA ELEMENT .js-tilt
VanillaTilt.init(document.querySelectorAll(".js-tilt"), {
    max: 18,
    speed: 400,
    glare: true,
    "max-glare": 0.25,
    scale: 1.03
});

// FUNGSI MEMBUKA LIGHTBOX POPUP SLIDER DENGAN CAPTION DI BERANDA
function openGalleryModal(data, photos, activeIndex) {
    document.getElementById('popName').innerText = data.name || 'Audiens';
    document.getElementById('popRole').innerText = data.role || 'Pembeli';
    document.getElementById('popAvatar').src = data.avatar || 'https://via.placeholder.com/150?text=User';
    document.getElementById('popComment').innerText = data.comment || data.message || '';

    let starsHtml = '';
    let rating = parseInt(data.rating || 5);
    for(let i=1; i<=5; i++) {
        starsHtml += `<i class="bi bi-star-fill ${i <= rating ? 'text-warning' : 'text-white-50 opacity-25'} me-1"></i>`;
    }
    document.getElementById('popStars').innerHTML = starsHtml;

    let innerBox = document.getElementById('lightboxCarouselInner');
    let itemsHtml = '';
    
    if (!photos || photos.length === 0) {
        photos = [data.avatar || 'https://via.placeholder.com/600x400?text=Ulasan+JARIA+Craft'];
    }

    photos.forEach((p, idx) => {
        itemsHtml += `
            <div class="carousel-item ${idx === activeIndex ? 'active' : ''}">
                <div class="text-center">
                    <img src="${p}" class="img-fluid lightbox-img">
                </div>
            </div>
        `;
    });
    innerBox.innerHTML = itemsHtml;

    var myCarousel = document.querySelector('#lightboxCarousel');
    var carousel = new bootstrap.Carousel(myCarousel, {
        interval: 3000,
        ride: 'carousel'
    });
    carousel.cycle();

    new bootstrap.Modal(document.getElementById('modalGalleryViewer')).show();
}

// LIKE DENGAN LOCALSTORAGE ANTI-SPAM
function likeComment(id, btn) {
    let likedItems = JSON.parse(localStorage.getItem('liked_testimonials') || '[]');
    if (likedItems.includes(id)) {
        alert('Anda sudah menyukai ulasan ini!');
        return;
    }

    let formData = new FormData();
    formData.append('action', 'like');
    formData.append('id', id);

    fetch('ajax_testimonial.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            btn.querySelector('.like-count').innerText = data.likes;
            likedItems.push(id);
            localStorage.setItem('liked_testimonials', JSON.stringify(likedItems));
            btn.disabled = true;
            btn.classList.add('opacity-50');
        } else if (data.status === 'already_liked') {
            alert(data.message);
        }
    });
}

// SUBMIT REVIEW
document.getElementById('formReview').addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);

    fetch('ajax_testimonial.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if(data.status === 'success') {
            location.reload();
        }
    });
});
</script>
</body>
</html>