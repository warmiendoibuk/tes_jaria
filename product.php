<?php
// C:\xampp\htdocs\jaria\product.php
session_start();

$page_title = "Detail Produk";

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: catalog.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = :id AND p.is_active = 1 LIMIT 1");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: catalog.php");
        exit;
    }
} catch (PDOException $e) {
    header("Location: catalog.php");
    exit;
}

$page_title = $product['name'];

// FUNGSI DETEKSI MEDIA & SOSMED EMBED
function get_product_media_info($url) {
    if (empty($url)) return null;
    $url = trim($url);

    // 1. YOUTUBE
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]+)/i', $url, $m)) {
        return [
            'type' => 'iframe', 
            'embed' => 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=0&rel=0', 
            'original' => $url,
            'name' => 'YouTube', 
            'icon' => 'bi-youtube',
            'btn_color' => 'btn-danger'
        ];
    }
    
    // 2. GOOGLE DRIVE
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i', $url, $m)) {
        return [
            'type' => 'iframe', 
            'embed' => 'https://drive.google.com/file/d/' . $m[1] . '/preview', 
            'original' => $url,
            'name' => 'Google Drive', 
            'icon' => 'bi-google-drive',
            'btn_color' => 'btn-primary'
        ];
    }

    // 3. TIKTOK & INSTAGRAM (External Redirect/Embed Link)
    $name = 'Media Sosial';
    $icon = 'bi-play-circle-fill';
    $btn_color = 'btn-dark';

    if (strpos($url, 'tiktok.com') !== false) { 
        $name = 'TikTok Showcase Video'; 
        $icon = 'bi-tiktok'; 
        $btn_color = 'btn-dark';
    } elseif (strpos($url, 'instagram.com') !== false) { 
        $name = 'Instagram Reel Showcase'; 
        $icon = 'bi-instagram'; 
        $btn_color = 'btn-danger';
    }

    return [
        'type' => 'external', 
        'embed' => $url, 
        'original' => $url,
        'name' => $name, 
        'icon' => $icon,
        'btn_color' => $btn_color
    ];
}

$video_info = !empty($product['video_url']) ? get_product_media_info($product['video_url']) : null;

// KUMPULKAN FOTO PRODUK VALID
$images = [];
$dir_path = __DIR__ . '/assets/images/products/';

if (!empty($product['image']) && file_exists($dir_path . $product['image'])) {
    $images[] = 'assets/images/products/' . $product['image'];
}
if (!empty($product['image2']) && file_exists($dir_path . $product['image2'])) {
    $images[] = 'assets/images/products/' . $product['image2'];
}
if (!empty($product['image3']) && file_exists($dir_path . $product['image3'])) {
    $images[] = 'assets/images/products/' . $product['image3'];
}

if (empty($images)) {
    $images[] = 'assets/images/products/default-product.jpg';
}

$stock_qty = (int)($product['stock'] ?? 0);
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfbf7; color: #2b2b2b; }
        
        .product-card-container {
            background: #ffffff;
            border-radius: 1.5rem;
            border: 1.5px solid rgba(13, 110, 253, 0.1);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .product-card-container:hover {
            transform: translateY(-3px);
            border-color: rgba(13, 110, 253, 0.35);
            box-shadow: 0 12px 28px rgba(13, 110, 253, 0.12) !important;
        }

        .zoom-image { 
            cursor: pointer; 
            transition: transform 0.3s ease; 
        }
        .zoom-image:hover { 
            transform: scale(1.03); 
        }
        
        .carousel-item-container {
            position: relative;
        }
        .zoom-badge {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        /* BADGE STOK MERAH / HIJAU PRESISI */
        .badge-stock-danger {
            background-color: #ffeef0;
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: 1px solid rgba(220, 53, 69, 0.25);
        }

        .badge-stock-success {
            background-color: #e8f5e9;
            color: #198754;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: 1px solid rgba(25, 135, 84, 0.25);
        }

        /* ANIMASI HOVER TOMBOL */
        .btn-bubble-bounce { 
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .btn-bubble-bounce:hover { 
            transform: translateY(-2px) scale(1.02); 
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25) !important;
        }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none fw-semibold">Beranda</a></li>
            <li class="breadcrumb-item"><a href="catalog.php" class="text-decoration-none fw-semibold">Katalog</a></li>
            <li class="breadcrumb-item active fw-bold" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row gy-4">
        <!-- SLIDER FOTO & VIDEO PRODUK (KIRI) -->
        <div class="col-lg-6">
            <div class="product-card-container p-4">
                <div id="productCarousel" class="carousel slide" data-bs-ride="false">
                    <?php 
                    $show_iframe_in_carousel = ($video_info && $video_info['type'] === 'iframe');
                    $total_slides = count($images) + ($show_iframe_in_carousel ? 1 : 0); 
                    ?>

                    <?php if ($total_slides > 1): ?>
                        <div class="carousel-indicators mb-2">
                            <?php foreach ($images as $idx => $img): ?>
                                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?php echo $idx; ?>" class="<?php echo $idx === 0 ? 'active' : ''; ?>"></button>
                            <?php endforeach; ?>
                            <?php if ($show_iframe_in_carousel): ?>
                                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?php echo count($images); ?>" class="bg-danger" title="Video Showcase"></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="carousel-inner rounded-4 overflow-hidden border">
                        <?php foreach ($images as $idx => $img): ?>
                            <div class="carousel-item carousel-item-container <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($img); ?>" 
                                     class="d-block w-100 object-fit-cover zoom-image" 
                                     style="height: 410px;" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onclick="openImageModal('<?php echo htmlspecialchars($img); ?>')">
                                <div class="zoom-badge"><i class="bi bi-search me-1"></i>Klik untuk Membesarkan</div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($show_iframe_in_carousel): ?>
                            <div class="carousel-item">
                                <iframe src="<?php echo htmlspecialchars($video_info['embed']); ?>" class="w-100" style="height: 410px; border:0;" allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_slides > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon bg-dark bg-opacity-75 rounded-circle p-2.5"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon bg-dark bg-opacity-75 rounded-circle p-2.5"></span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- JIKA VIDEO BERASAL DARI INSTAGRAM REEL / TIKTOK -->
                <?php if ($video_info && $video_info['type'] === 'external'): ?>
                    <div class="mt-3 p-3 bg-light rounded-4 border text-center">
                        <p class="small text-muted mb-2 fw-semibold">Produk ini memiliki video showcase di <?php echo $video_info['name']; ?>:</p>
                        <a href="<?php echo htmlspecialchars($video_info['original']); ?>" target="_blank" class="btn <?php echo $video_info['btn_color']; ?> btn-sm rounded-pill px-4 fw-bold btn-bubble-bounce">
                            <i class="bi <?php echo $video_info['icon']; ?> me-1"></i> Tonton Video Showcase
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DETAIL & FORM KANAN -->
        <div class="col-lg-6">
            <div class="product-card-container p-4 p-md-5">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill mb-2 fw-semibold">
                    <i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($product['category_name'] ?? 'Perca'); ?>
                </span>
                
                <h2 class="fw-extrabold text-dark mb-2"><?php echo htmlspecialchars($product['name']); ?></h2>
                <h3 class="fw-extrabold text-primary mb-4">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h3>

                <div class="p-3 bg-light rounded-4 mb-4 border">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle-fill text-primary me-2"></i>Spesifikasi:</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1.5"><strong>Bahan:</strong> <?php echo htmlspecialchars($product['material'] ?: 'Perca Katun'); ?></li>
                        <li class="mb-1.5"><strong>Ukuran:</strong> <?php echo htmlspecialchars($product['size'] ?: '-'); ?></li>
                        <li class="mb-1.5"><strong>Berat:</strong> <?php echo (int)($product['weight'] ?? 1); ?> gram</li>
                        <li>
                            <strong>Stok Tersedia:</strong> 
                            <!-- INDIKATOR MERAH SISA <= 3 PCS SESUAI BERANDA & KATALOG -->
                            <?php if ($stock_qty <= 3): ?>
                                <span class="badge-stock-danger ms-1">
                                    <i class="bi bi-exclamation-circle-fill"></i> Sisa <?php echo $stock_qty; ?> pcs
                                </span>
                            <?php else: ?>
                                <span class="badge-stock-success ms-1">
                                    <i class="bi bi-box-seam-fill"></i> <?php echo $stock_qty; ?> pcs
                                </span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>

                <?php if (!empty($product['description'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-1">Deskripsi Produk:</h6>
                        <p class="text-secondary small leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- FORM TAMBAH KE KERANJANG -->
                <?php if ($stock_qty > 0): ?>
                    <form action="cart.php" method="POST" class="d-flex gap-3 pt-2">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="quantity" class="form-control text-center fw-bold rounded-4 border-2 shadow-sm" style="width: 90px;" value="1" min="1" max="<?php echo $stock_qty; ?>">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1 rounded-pill fw-bold fs-6 py-3 shadow-sm btn-bubble-bounce">
                            <i class="bi bi-cart-plus-fill me-2 fs-5 align-middle"></i> Tambah ke Keranjang
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg w-100 rounded-pill py-3 fw-bold" disabled>Stok Habis</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POPUP PREVIEW GAMBAR -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3 bg-dark p-2 rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="modalImage" src="" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 border-top mt-5">
    <div class="container text-center">
        <p class="mb-1 text-white-50 small">&copy; <?php echo date('Y'); ?> <strong>JARIA Craft</strong> (Jahit Ceria) — Panti Asuhan Al-Jihad Surabaya.</p>
        <small class="text-muted">Proyek Kewirausahaan Sosial | Mahasiswa Ilmu Komunikasi E1 Semester 5 UIN Sunan Ampel Surabaya</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
    myModal.show();
}
</script>
</body>
</html>