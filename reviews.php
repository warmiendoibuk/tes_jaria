<?php
// C:\xampp\htdocs\jaria\reviews.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Ulasan & Testimoni — JARIA Craft";

// 1. AMBIL BANNER CAROUSEL DARI DATABASE (TERHUBUNG KE TESTIMONIAL_BANNERS)
$banner_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM testimonial_banners ORDER BY sort_order ASC, id ASC");
    $banner_items =$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($banner_items)) {$banner_items = [
        [
            'image_path'  => 'assets/images/banners/banners1.jpg',
            'badge_text'  => 'Apresiasi & Testimoni',
            'title'       => 'Ulasan & Testimoni Audiens',
            'description' => 'Dengarkan cerita, apresiasi, dan pengalaman nyata dari para pengunjung serta pendukung pemberdayaan JARIA Craft.'
        ]
    ];
}

// 2. ULASAN TERFAVORIT (TESTIMONIALS)
$reviews = [];
try {
    $stmt =$pdo->query("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY likes_count DESC, created_at DESC");
    $reviews =$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {$reviews = [];
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
        .hero-bg-carousel { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .hero-bg-carousel .carousel-item { height: 100%; }
        .hero-bg-carousel img { width: 100%; height: 360px; object-fit: cover; opacity: 0.55; }
        .hero-overlay-content { position: relative; z-index: 3; color: #ffffff; padding: 2.5rem 2rem; max-width: 800px; }
        .hero-title-box { background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px); padding: 0.85rem 1.4rem; border-radius: 1rem; border-left: 5px solid #0d6efd; display: inline-block; }

        /* ANIMASI TEKS CAROUSEL FADE-IN-UP (PRESISI PERSIS DENGAN IMPACT.PHP) */
        @keyframes heroTextFadeUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-hero-badge { animation: heroTextFadeUp 0.6s ease-out forwards; }
        .animate-hero-title { animation: heroTextFadeUp 0.8s ease-out 0.2s forwards; opacity: 0; }
        .animate-hero-desc  { animation: heroTextFadeUp 0.8s ease-out 0.4s forwards; opacity: 0; }

        /* KARTU ULASAN DENGAN EFEK 3D TILT & GLOWING BORDER */
        .review-card-page {
            background: #ffffff;
            border: 1.5px solid rgba(13, 110, 253, 0.15);
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 6px 18px rgba(0,0,0,0.03);
            position: relative;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .review-card-page:hover {
            border-color: #0d6efd;
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.28), 0 0 25px rgba(13, 110, 253, 0.2);
        }

        .rank-badge-page {
            position: absolute;
            top: -12px; right: 20px;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #111; font-weight: 800; font-size: 0.78rem;
            padding: 0.35rem 0.85rem; border-radius: 50rem;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.4);
            z-index: 2;
        }

        .carousel-page-img {
            height: 220px;
            width: 100%;
            object-fit: cover;
            border-radius: 0.85rem;
            cursor: pointer;
        }

        .btn-like {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 50rem;
            padding: 0.4rem 1rem;
            font-weight: 700;
            font-size: 0.88rem;
            color: #dc3545;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .btn-like:hover { background: #ffebe9; transform: scale(1.05); }

        /* LIGHTBOX POPUP STYLING */
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

<!-- HERO BANNER CAROUSEL HEADER -->
<div class="container my-4">
    <div class="hero-section shadow-lg">
        <div id="reviewBgCarousel" class="carousel slide hero-bg-carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($banner_items as $index =>$b): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($b['image_path']); ?>" alt="Banner Ulasan" onerror="this.src='assets/images/banners/banners1.jpg'">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-overlay-content">
            <div id="reviewTextCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner">
                    <?php foreach ($banner_items as $index =>$b): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php if (!empty($b['badge_text'])): ?>
                                <div class="animate-hero-badge mb-3">
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-star-fill text-danger me-1"></i> <?php echo htmlspecialchars($b['badge_text']); ?>
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

<!-- KONTEN ULASAN AUDIENS -->
<div class="container py-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-extrabold text-dark mb-1">Apresiasi & Testimoni Audiens</h3>
            <p class="text-muted small mb-0">Klik foto atau ulasan untuk membukanya dalam mode tayangan slider penuh!</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddReview">
            <i class="bi bi-pencil-square me-1"></i> Beri Ulasan Sekarang
        </button>
    </div>

    <?php if (!empty($reviews)): ?>
        <div class="row g-4">
            <?php foreach ($reviews as $idx =>$tc): 
                $rating = (int)($tc['rating'] ?? 5);
                $nama =$tc['name'] ?? 'Audiens';
                $role =$tc['role'] ?: 'Pendukung';
                $komentar =$tc['comment'] ?? ($tc['message'] ?? '');$likes = (int)($tc['likes_count'] ?? 0);$avatar = !empty($tc['avatar']) ?$tc['avatar'] : 'https://via.placeholder.com/150?text=User';

                $photos = [];
                try {
                    $stmt_p =$pdo->prepare("SELECT photo_path FROM testimonial_photos WHERE testimonial_id = :tid");
                    $stmt_p->execute(['tid' =>$tc['id']]);
                    $photos =$stmt_p->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {}
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card review-card-page js-tilt h-100 d-flex flex-column justify-content-between" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, 0)">
                        <!-- MAHKOTA RANKING TOP 3 -->
                        <?php if ($idx === 0): ?>
                            <span class="rank-badge-page"><i class="bi bi-trophy-fill text-dark me-1"></i> #1 Terfavorit</span>
                        <?php elseif ($idx === 1): ?>
                            <span class="rank-badge-page"><i class="bi bi-award-fill text-dark me-1"></i> #2 Terfavorit</span>
                        <?php elseif ($idx === 2): ?>
                            <span class="rank-badge-page"><i class="bi bi-star-fill text-dark me-1"></i> #3 Terfavorit</span>
                        <?php endif; ?>

                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2.5">
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" class="rounded-circle border object-fit-cover" style="width: 48px; height: 48px;">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($nama); ?></h6>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border rounded-pill px-2 py-0.5 small" style="font-size: 0.72rem;">
                                            <?php echo htmlspecialchars($role); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-warning ms-auto">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill <?php echo $i <=$rating ? '' : 'text-muted opacity-25'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <p class="text-dark fs-6 mb-3">"<?php echo htmlspecialchars($komentar); ?>"</p>

                            <!-- CAROUSEL FOTO DENGAN OTOMATIS GESER DAN BISA POPUP -->
                            <?php if (!empty($photos)): ?>
                                <div class="mb-3" onclick="event.stopPropagation();">
                                    <?php if (count($photos) === 1): ?>
                                        <img src="<?php echo htmlspecialchars($photos[0]); ?>" class="carousel-page-img rounded-3 border shadow-sm" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, 0)">
                                    <?php else: ?>
                                        <div id="carouselPageRev<?php echo $tc['id']; ?>" class="carousel slide shadow-sm rounded-3 overflow-hidden border" data-bs-ride="carousel" data-bs-interval="3000">
                                            <div class="carousel-inner">
                                                <?php foreach ($photos as $p_idx =>$img_path): ?>
                                                    <div class="carousel-item <?php echo $p_idx === 0 ? 'active' : ''; ?>">
                                                        <img src="<?php echo htmlspecialchars($img_path); ?>" class="d-block w-100 carousel-page-img" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($tc)); ?>, <?php echo htmlspecialchars(json_encode($photos)); ?>, <?php echo$p_idx; ?>)">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselPageRev<?php echo $tc['id']; ?>" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon bg-dark bg-opacity-50 rounded-circle p-2"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselPageRev<?php echo $tc['id']; ?>" data-bs-slide="next">
                                                <span class="carousel-control-next-icon bg-dark bg-opacity-50 rounded-circle p-2"></span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3 border-top border-light mt-auto d-flex align-items-center justify-content-between" onclick="event.stopPropagation();">
                            <span class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo date('d M Y', strtotime($tc['created_at'])); ?></span>
                            <button type="button" class="btn-like" onclick="likeComment(<?php echo $tc['id']; ?>, this)">
                                <i class="bi bi-heart-fill me-1"></i> <span class="like-count"><?php echo $likes; ?></span> Suka
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-4">
            <h5 class="fw-bold text-dark">Belum ada ulasan masuk</h5>
            <p class="text-muted small mb-0">Jadilah yang pertama memberikan ulasan & testimoni!</p>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL FORM ULASAN -->
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

<!-- FOOTER DINAMIS -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- VANILLA TILT JS LIBRARY -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

<script>
// SINKRONISASI SLIDER BACKGROUND DENGAN TEKS BANNER
var reviewBgCarousel = document.getElementById('reviewBgCarousel');
var reviewTextCarousel = document.getElementById('reviewTextCarousel');

if (reviewBgCarousel && reviewTextCarousel) {
    var textBsCarousel = new bootstrap.Carousel(reviewTextCarousel);
    reviewBgCarousel.addEventListener('slide.bs.carousel', function (e) {
        textBsCarousel.to(e.to);
    });
}

// INISIALISASI EFEK 3D TILT PADA SEMUA KARTU ULASAN (.js-tilt)
if (typeof VanillaTilt !== "undefined") {
    VanillaTilt.init(document.querySelectorAll(".js-tilt"), {
        max: 18,
        speed: 400,
        glare: true,
        "max-glare": 0.25,
        scale: 1.03
    });
}

// FUNGSI MEMBUKA LIGHTBOX POPUP SLIDER DENGAN CAPTION
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