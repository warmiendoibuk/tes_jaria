<?php
// C:\xampp\htdocs\jaria\catalog.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Katalog Produk — JARIA Craft";

// 1. SCAN BANNER CAROUSEL HEADER
$banner_dir = __DIR__ . '/assets/images/banners/';
$banner_files = [];

if (is_dir($banner_dir)) {
    $files = scandir($banner_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $file)) {
            $banner_files[] = 'assets/images/banners/' . $file;
        }
    }
}

if (empty($banner_files)) {
    $banner_files = ['assets/images/banners/banners1.jpg'];
}

// 2. PARAMETER SEARCH, CATEGORY, SORT
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query    = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by         = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';

// 3. DAFTAR KATEGORI DEFAULT
$default_categories = ['Bros', 'Keychain', 'Sling Bag', 'Kerajinan Lainnya'];

// 4. AMBIL PRODUK DARI DATABASE (HANYA YANG STATUSNYA AKTIF / IS_ACTIVE = 1)
$all_products = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name_rel 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.is_active = 1
                         ORDER BY p.id DESC");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_products = [];
}

// 5. FILTERING DI LEVEL PHP
$products = [];

foreach ($all_products as $p) {
    $p_name = $p['name'] ?? ($p['nama_produk'] ?? ($p['nama'] ?? ''));
    $p_cat  = !empty($p['category_name_rel']) ? $p['category_name_rel'] : ($p['category'] ?? ($p['kategori'] ?? ''));
    $p_desc = $p['description'] ?? ($p['deskripsi'] ?? '');

    // A. FILTER PENCARIAN (SEARCH)
    if (!empty($search_query)) {
        $match_name = (stripos($p_name, $search_query) !== false);
        $match_cat  = (stripos($p_cat, $search_query) !== false);
        $match_desc = (stripos($p_desc, $search_query) !== false);

        if (!$match_name && !$match_cat && !$match_desc) {
            continue;
        }
    }

    // B. FILTER KATEGORI
    if (!empty($category_filter)) {
        if (strcasecmp($category_filter, 'Kerajinan Lainnya') === 0) {
            $is_bros = (stripos($p_cat, 'Bros') !== false);
            $is_keychain = (stripos($p_cat, 'Keychain') !== false || stripos($p_cat, 'Gantungan') !== false);
            $is_slingbag = (stripos($p_cat, 'Sling') !== false || stripos($p_cat, 'Tas') !== false);

            if ($is_bros || $is_keychain || $is_slingbag) {
                continue;
            }
        } else {
            $match_cat_exact = (stripos($p_cat, $category_filter) !== false);
            $match_name_cat  = (stripos($p_name, $category_filter) !== false);

            if (!$match_cat_exact && !$match_name_cat) {
                continue;
            }
        }
    }

    $products[] = $p;
}

// 6. PENGURUTAN (SORTING)
if ($sort_by === 'name_asc') {
    usort($products, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
} elseif ($sort_by === 'name_desc') {
    usort($products, fn($a, $b) => strcasecmp($b['name'] ?? '', $a['name'] ?? ''));
} elseif ($sort_by === 'price_low') {
    usort($products, fn($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));
} elseif ($sort_by === 'price_high') {
    usort($products, fn($a, $b) => ($b['price'] ?? 0) <=> ($a['price'] ?? 0));
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

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-hero-badge { animation: fadeInUp 0.6s ease-out forwards; }
        .animate-hero-title { animation: fadeInUp 0.8s ease-out 0.2s forwards; opacity: 0; }
        .animate-hero-desc  { animation: fadeInUp 0.8s ease-out 0.4s forwards; opacity: 0; }
        .animate-search     { opacity: 0; animation: fadeInUp 0.6s ease-out 0.15s forwards; }
        .animate-sidebar    { opacity: 0; animation: fadeInUp 0.6s ease-out 0.25s forwards; }

        .hero-title-box {
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            padding: 0.85rem 1.4rem;
            border-radius: 1rem;
            border-left: 5px solid #0d6efd;
            display: inline-block;
        }

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

        .product-img-wrapper {
            overflow: hidden;
            position: relative;
            height: 230px;
            display: block;
        }

        .product-img { 
            height: 100%; 
            width: 100%;
            object-fit: cover; 
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.08);
        }

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

        .filter-card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            transition: box-shadow 0.3s ease;
        }

        .category-link {
            display: block;
            padding: 0.7rem 1.1rem;
            border-radius: 0.85rem;
            color: #4b5563;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .category-link:hover, .category-link.active {
            background-color: #0d6efd;
            color: #ffffff !important;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }

        .btn-bubble-bounce {
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
        }

        .btn-bubble-bounce:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.2);
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI MODULAR DARI INCLUDES/NAVBAR.PHP -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="catalogBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($banner_files as $index => $banner): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($banner); ?>" alt="Banner JARIA">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div class="animate-hero-badge mb-3">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-bag-heart-fill text-danger me-1"></i> Karya Tangan Pilihan
                </span>
            </div>
            
            <div class="animate-hero-title mb-3">
                <div class="hero-title-box">
                    <h1 class="display-6 fw-extrabold text-white mb-0">Katalog Kerajinan Perca</h1>
                </div>
            </div>

            <p class="lead text-white-50 mb-0 fs-5 animate-hero-desc">
                Temukan karya tangan unik bernilai sosial buatan adik-adik Panti Asuhan Al-Jihad Surabaya.
            </p>
        </div>
    </div>
</div>

<!-- KONTEN UTAMA KATALOG -->
<div class="container pb-5">
    
    <!-- PENCARIAN & TOMBOL RESET -->
    <div class="row mb-4 justify-content-end animate-search">
        <div class="col-md-7 col-lg-5">
            <form action="catalog.php" method="GET" class="d-flex gap-2">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
                    <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari nama atau jenis produk..." value="<?php echo htmlspecialchars($search_query); ?>">
                    
                    <?php if (!empty($search_query)): ?>
                        <a href="catalog.php<?php echo !empty($category_filter) ? '?category='.urlencode($category_filter) : ''; ?>" class="btn btn-light text-muted d-flex align-items-center px-3" title="Hapus Pencarian">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </a>
                    <?php endif; ?>

                    <button class="btn btn-primary px-4 fw-bold" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <!-- SIDEBAR FILTER KATEGORI -->
        <div class="col-lg-3 animate-sidebar">
            <div class="filter-card p-4 sticky-lg-top" style="top: 90px; z-index: 10;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-funnel text-primary me-2"></i>Filter Kategori</h6>
                    <?php if (!empty($category_filter) || !empty($search_query)): ?>
                        <a href="catalog.php" class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle text-decoration-none px-2 py-1 small">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex flex-column gap-1.5 mb-4">
                    <a href="catalog.php" class="category-link <?php echo empty($category_filter) ? 'active' : ''; ?>">
                       Semua Kategori
                    </a>
                    
                    <?php foreach ($default_categories as $cat): ?>
                        <a href="catalog.php?category=<?php echo urlencode($cat); ?>" 
                           class="category-link <?php echo (strcasecmp($category_filter, $cat) === 0) ? 'active' : ''; ?>">
                           <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <hr class="text-muted opacity-25">

                <!-- PENGURUTAN -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sort-down text-primary me-2"></i>Urutkan</h6>
                <form action="catalog.php" method="GET">
                    <?php if(!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <?php endif; ?>
                    <?php if(!empty($search_query)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                    
                    <select name="sort" class="form-select rounded-3 border-secondary border-opacity-25 py-2" onchange="this.form.submit()">
                        <option value="latest" <?php echo $sort_by === 'latest' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Nama: A - Z (A ke Z)</option>
                        <option value="name_desc" <?php echo $sort_by === 'name_desc' ? 'selected' : ''; ?>>Nama: Z - A (Z ke A)</option>
                        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Harga: Termurah</option>
                        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Harga: Termahal</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- LIST PRODUK -->
        <div class="col-lg-9">
            <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $p): 
                        $p_name  = $p['name'] ?? ($p['nama_produk'] ?? ($p['nama'] ?? 'Produk Kerajinan'));
                        $p_cat   = !empty($p['category_name_rel']) ? $p['category_name_rel'] : ($p['category'] ?? ($p['kategori'] ?? 'Perca'));
                        $p_stock = (int)($p['stock'] ?? ($p['stok'] ?? 0));
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card product-card js-tilt shadow-sm h-100">
                                <a href="product.php?id=<?php echo $p['id']; ?>" class="product-img-wrapper">
                                    <img src="assets/images/products/<?php echo !empty($p['image']) ? htmlspecialchars($p['image']) : 'default-product.jpg'; ?>" 
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
                                        <p class="fw-extrabold text-primary fs-5 mb-2">Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></p>

                                        <!-- INDIKATOR STOK -->
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
                <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-4 animate-sidebar">
                    <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
                    <h5 class="fw-bold text-dark">Produk Tidak Ditemukan</h5>
                    <p class="text-muted small">Coba cari dengan kata kunci lain atau pilih kategori yang berbeda.</p>
                    <a href="catalog.php" class="btn btn-primary rounded-pill px-4 fw-bold mt-2 btn-bubble-bounce">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Semua Filter
                    </a>
                </div>
            <?php endif; ?>
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
// INISIALISASI EFEK 3D TILT UNTUK SEMUA PRODUK DI KATALOG
VanillaTilt.init(document.querySelectorAll(".js-tilt"), {
    max: 18,            // Derajat kemiringan maksimal
    speed: 400,         // Kecepatan transisi pergerakan
    glare: true,        // Efek pantulan kilat cahaya
    "max-glare": 0.25,  // Kecerahan kilat cahaya
    scale: 1.03         // Efek membesar halus saat kursor mengarah ke kartu
});
</script>
</body>
</html>