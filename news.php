<?php
// C:\xampp\htdocs\jaria\news.php
session_start();
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Berita & Artikel Inspirasi — JARIA Craft";

// Filter Parameters
$category_slug = sanitize($_GET['category'] ?? '');
$tag           = sanitize($_GET['tag'] ?? '');
$article_slug  = sanitize($_GET['read'] ?? '');

// 1. DETAIL ARTIKEL (JIKA DIKLIK BACA)
$article = null;
if (!empty($article_slug)) {
    $stmt_art = $pdo->prepare("SELECT n.*, c.name as category_name, c.slug as category_slug 
                               FROM news n 
                               JOIN news_categories c ON n.category_id = c.id 
                               WHERE n.slug = :slug AND n.is_published = 1 LIMIT 1");
    $stmt_art->execute(['slug' => $article_slug]);
    $article = $stmt_art->fetch(PDO::FETCH_ASSOC);

    if ($article) {
        $pdo->prepare("UPDATE news SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => $article['id']]);
        $page_title = htmlspecialchars($article['title']) . " — JARIA Craft";
    }
}

// 2. AMBIL BANNER SLIDES BERITA (DYNAMIC CMS)
$news_slides = [];
try {
    $stmt_slide = $pdo->query("SELECT * FROM news_banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $news_slides = $stmt_slide->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($news_slides)) {
    $news_slides[] = [
        'image_path' => 'assets/images/banners/banners1.jpg',
        'badge_text_1' => 'Kabar & Artikel',
        'badge_text_2' => 'Seputar JARIA Craft',
        'title' => 'Kabar & Inspirasi JARIA',
        'description' => 'Ikuti berita terbaru, kegiatan sosial santri panti, dan artikel edukasi kerajinan perca yang berdaya dampak.'
    ];
}

// AMBIL KATEGORI UNTUK MENU FILTER
$categories = $pdo->query("SELECT * FROM news_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. QUERY DAFTAR BERITA
$where = ["n.is_published = 1"];
$params = [];

if (!empty($category_slug)) {
    $where[] = "c.slug = :cat_slug";
    $params['cat_slug'] = $category_slug;
}

if (!empty($tag)) {
    $where[] = "n.hashtags LIKE :tag";
    $params['tag'] = '%' . $tag . '%';
}

$where_sql = implode(' AND ', $where);
$sql_list = "SELECT n.*, c.name as category_name, c.slug as category_slug 
             FROM news n 
             JOIN news_categories c ON n.category_id = c.id 
             WHERE {$where_sql} 
             ORDER BY n.created_at DESC";
$stmt_list = $pdo->prepare($sql_list);
$stmt_list->execute($params);
$news_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <?php if ($article): ?>
        <meta name="description" content="<?php echo htmlspecialchars($article['meta_description'] ?: substr(strip_tags($article['content']), 0, 150)); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($article['title']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($article['meta_description']); ?>">
        <?php if ($article['featured_image']): ?>
            <meta property="og:image" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/' . $article['featured_image']; ?>">
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap 5 & Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfbf7; color: #2b2b2b; }

        /* HERO CAROUSEL HEADER BANNER (PRESISI SAMA PERSIS SEPERTI IMPACT.PHP & FAQ.PHP) */
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

        /* PERATAN GAMBAR DARI CKEDITOR & POP-UP LIGHTBOX */
        .article-content figure.image,
        .article-content figure.align-center,
        .article-content figure.image-style-align-center {
            display: table !important;
            margin: 1.5rem auto !important;
            text-align: center !important;
            clear: both;
        }

        .article-content figure.align-left {
            float: left !important;
            margin: 0.5rem 1.5rem 1rem 0 !important;
        }

        .article-content figure.align-right {
            float: right !important;
            margin: 0.5rem 0 1rem 1.5rem !important;
        }

        .article-content img { 
            max-width: 100%; 
            height: auto; 
            border-radius: 12px; 
            display: block;
            margin: 0 auto !important;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .article-content img:hover {
            transform: scale(1.015);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .article-content p[style*="text-align: center"],
        .article-content p[style*="text-align:center"] {
            text-align: center !important;
        }
        .article-content p[style*="text-align: center"] img,
        .article-content p[style*="text-align:center"] img {
            display: inline-block !important;
            margin: 0 auto !important;
        }

        .article-content iframe { max-width: 100%; border-radius: 12px; }
        .hashtag-link { text-decoration: none; color: #0284c7; font-weight: 600; font-size: 0.9rem; margin-right: 0.5rem; }
        .hashtag-link:hover { text-decoration: underline; color: #0369a1; }
    </style>
</head>
<body>

<!-- NAVBAR NAVIGASI TERINTEGRASI -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="newsBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($news_slides as $index => $slide): 
                    $raw_img = $slide['image_path'] ?? ($slide['image'] ?? 'assets/images/banners/banners1.jpg');
                    $img = (strpos($raw_img, 'assets/') === 0) ? $raw_img : 'assets/images/banners/' . $raw_img;
                ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($img); ?>" 
                             alt="<?php echo htmlspecialchars($slide['title']); ?>"
                             onerror="this.src='assets/images/banners/banners1.jpg';">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div id="newsTextCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner">
                    <?php foreach ($news_slides as $index => $slide): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="animate-hero-badge mb-3 d-flex flex-wrap align-items-center gap-2">
                                <?php if (!empty($slide['badge_text_1'])): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-newspaper me-1"></i> <?php echo htmlspecialchars($slide['badge_text_1']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($slide['badge_text_2'])): ?>
                                    <span class="badge bg-white bg-opacity-25 text-white backdrop-blur border border-white border-opacity-50 px-3 py-2 rounded-pill fw-semibold shadow-sm">
                                        <i class="bi bi-stars text-warning me-1"></i> <?php echo htmlspecialchars($slide['badge_text_2']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="animate-hero-title mb-3">
                                <div class="hero-title-box">
                                    <h1 class="display-6 fw-extrabold text-white mb-0"><?php echo htmlspecialchars($slide['title']); ?></h1>
                                </div>
                            </div>

                            <?php if (!empty($slide['description'])): ?>
                                <p class="lead text-white-50 mb-0 fs-5 animate-hero-desc">
                                    <?php echo htmlspecialchars($slide['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- JIKA MEMBACA DETAIL ARTIKEL -->
    <?php if ($article): ?>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="news.php">Berita</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['category_name']); ?></li>
                    </ol>
                </nav>

                <h1 class="fw-extrabold text-dark mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small mb-4 pb-3 border-bottom">
                    <span><i class="bi bi-folder-fill text-primary me-1"></i><?php echo htmlspecialchars($article['category_name']); ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y, H:i', strtotime($article['created_at'])); ?> WIB</span>
                    <span><i class="bi bi-eye me-1"></i><?php echo $article['views_count']; ?>x Dibaca</span>
                </div>

                <?php if ($article['featured_image']): ?>
                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" class="w-100 rounded-4 shadow-sm mb-4 object-fit-cover article-featured-img" style="max-height: 450px;">
                <?php endif; ?>

                <!-- KONTEN ARTIKEL -->
                <div class="article-content bg-white p-4 p-md-5 rounded-4 shadow-sm mb-4 leading-relaxed">
                    <?php echo $article['content']; ?>
                </div>

                <!-- HASHTAG SEO SECTION -->
                <?php if (!empty($article['hashtags'])): ?>
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <strong class="d-block small text-muted mb-2"><i class="bi bi-tags-fill me-1"></i>Hashtag Terkait SEO:</strong>
                        <?php 
                        $tags = explode(' ', $article['hashtags']);
                        foreach ($tags as $t): 
                            $clean_tag = str_replace('#', '', trim($t));
                            if (!empty($clean_tag)):
                        ?>
                            <a href="news.php?tag=<?php echo urlencode($clean_tag); ?>" class="hashtag-link">#<?php echo htmlspecialchars($clean_tag); ?></a>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endif; ?>

                <a href="news.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">&larr; Kembali ke Semua Berita</a>
            </div>
        </div>

    <!-- JIKA MENAMPILKAN DAFTAR ARTIKEL (BLOG GRID) -->
    <?php else: ?>

        <!-- NAVBAR KATEGORI & FILTER HASHTAG -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
            <div class="d-flex flex-wrap gap-2">
                <a href="news.php" class="btn <?php echo empty($category_slug) && empty($tag) ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3 py-1.5 fw-bold btn-sm">Semua Berita</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="news.php?category=<?php echo $cat['slug']; ?>" class="btn <?php echo $category_slug === $cat['slug'] ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3 py-1.5 fw-bold btn-sm">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($tag)): ?>
                <span class="badge bg-info text-white px-3 py-2 rounded-pill">
                    Filter Tag: #<?php echo htmlspecialchars($tag); ?> <a href="news.php" class="text-white text-decoration-none ms-2">&times;</a>
                </span>
            <?php endif; ?>
        </div>

        <!-- GRID BERITA -->
        <div class="row g-4">
            <?php if (!empty($news_list)): ?>
                <?php foreach ($news_list as $n): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                            <?php if ($n['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($n['featured_image']); ?>" class="card-img-top object-fit-cover" style="height: 200px;">
                            <?php else: ?>
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height: 200px;"><i class="bi bi-newspaper fs-1"></i></div>
                            <?php endif; ?>

                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <span class="badge bg-light text-primary border rounded-pill px-3 mb-2 small"><?php echo htmlspecialchars($n['category_name']); ?></span>
                                    <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($n['title']); ?></h5>
                                    <p class="text-secondary small line-clamp-3 mb-3">
                                        <?php echo htmlspecialchars(substr(strip_tags($n['content']), 0, 120)) . '...'; ?>
                                    </p>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                        <span><i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y', strtotime($n['created_at'])); ?></span>
                                        <span><i class="bi bi-eye me-1"></i><?php echo $n['views_count']; ?></span>
                                    </div>
                                    <a href="news.php?read=<?php echo $n['slug']; ?>" class="btn btn-primary rounded-pill w-100 fw-bold">Baca Selengkapnya &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    Belum ada berita/artikel dalam kategori ini.
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<!-- MODAL POP-UP LIGHTBOX GAMBAR BESAR -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-transparent border-0 shadow-none">
      <div class="modal-body text-center p-0 position-relative">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="z-index: 1056; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem;"></button>
        <img id="lightboxImage" src="" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

<!-- FOOTER DINAMIS -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Aktifkan fitur pop-up klik gambar besar untuk foto di dalam artikel dan foto utama
    const articleImages = document.querySelectorAll('.article-content img, .article-featured-img');
    const lightboxModalEl = document.getElementById('imageLightboxModal');
    
    if (lightboxModalEl && articleImages.length > 0) {
        const lightboxModal = new bootstrap.Modal(lightboxModalEl);
        const lightboxImg = document.getElementById('lightboxImage');

        articleImages.forEach(img => {
            img.addEventListener('click', function () {
                lightboxImg.src = this.src;
                lightboxModal.show();
            });
        });
    }

    // SINKRONISASI CAROUSEL BANNER GAMBAR & TEKS
    var bgCarousel = document.getElementById('newsBgCarousel');
    var textCarousel = document.getElementById('newsTextCarousel');

    if (bgCarousel && textCarousel) {
        var textBsCarousel = new bootstrap.Carousel(textCarousel);
        bgCarousel.addEventListener('slide.bs.carousel', function (e) {
            textBsCarousel.to(e.to);
        });
    }
});
</script>
</body>
</html>