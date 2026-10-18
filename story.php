<?php
// C:\xampp\htdocs\jaria\story.php
$page_title = "Kisah Kami — JARIA Craft";

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Daftar 10 Foto Galeri Kegiatan & Deskripsinya
$gallery_items = [
    ['file' => 'gallery1.jpg', 'title' => 'Sesi Pelatihan Jahit', 'desc' => 'Adik-adik panti belajar teknik dasar menjahit kain perca.'],
    ['file' => 'gallery2.jpg', 'title' => 'Pemilihan Material Perca', 'desc' => 'Memilih corak kain perca berkualitas untuk bahan kerajinan.'],
    ['file' => 'gallery3.jpg', 'title' => 'Pembuatan Kerajinan Bros', 'desc' => 'Proses pembentukan aksesoris bros perca yang unik.'],
    ['file' => 'gallery4.jpg', 'title' => 'Pendampingan Mahasiswa', 'desc' => 'Mahasiswa Ilmu Komunikasi E1 UINSA mendampingi proses produksi.'],
    ['file' => 'gallery5.jpg', 'title' => 'Quality Control Produk', 'desc' => 'Memastikan kerapian dan kerapian jahitan setiap produk.'],
    ['file' => 'gallery6.jpg', 'title' => 'Keceriaan Adik Panti', 'desc' => 'Semangat dan senyum adik-adik Panti Asuhan Al-Jihad saat berkarya.'],
    ['file' => 'gallery7.jpg', 'title' => 'Pengemasan Produk (Packaging)', 'desc' => 'Proses pengemasan ramah lingkungan yang siap dipasarkan.'],
    ['file' => 'gallery8.jpg', 'title' => 'Sesi Foto Katalog Produk', 'desc' => 'Pengambilan foto produk kerajinan untuk tampilan katalog online.'],
    ['file' => 'gallery9.jpg', 'title' => 'Diskusi Manajemen Usaha', 'desc' => 'Penyusunan alur pencatatan keuangan dan stok barang.'],
    ['file' => 'gallery10.jpg', 'title' => 'Foto Bersama Tim JARIA', 'desc' => 'Sinergi Mahasiswa UINSA dan Pengurus Panti Asuhan Al-Jihad.']
];

// Cek Keberadaan Gambar Galeri
function get_gallery_url($filename) {
    $path = __DIR__ . '/assets/images/gallery/' . $filename;
    if (file_exists($path)) {
        return 'assets/images/gallery/' . $filename;
    }
    return 'assets/images/products/default-product.jpg';
}
?>

<style>
/* STYLE CAROUSEL GALERI & HOVER EFFECTS */
.gallery-card-img {
    height: 420px;
    object-fit: cover;
    cursor: zoom-in;
    transition: transform 0.4s ease;
}
.gallery-card-img:hover {
    transform: scale(1.02);
}
.carousel-caption-overlay {
    background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.85) 100%);
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 25px 20px 20px 20px;
    color: #fff;
}
.modal-preview-img {
    max-height: 80vh;
    object-fit: contain;
}
</style>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kisah Kami</li>
        </ol>
    </nav>

    <!-- HEADER NARASI UTAMA -->
    <div class="row align-items-center gy-4 mb-5">
        <div class="col-lg-7">
            <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill fw-bold mb-3">
                <i class="bi bi-heart-fill text-danger me-1"></i> Kolaborasi Sosial & Pemberdayaan
            </span>
            <h1 class="display-5 fw-bold text-dark mb-3">Di Balik Sentuhan Karya <span class="text-primary">JARIA Craft</span></h1>
            
            <p class="lead text-primary fw-bold fs-5 mb-3">
                JARIA merupakan singkatan dari <span class="bg-primary bg-opacity-10 px-2 py-1 rounded border border-primary border-opacity-25">Jahit Ceria</span> — sebuah inisiatif wirausaha sosial yang menghadirkan keceriaan dan keterampilan melalui setiap lembar karya kain perca.
            </p>

            <p class="text-secondary fs-6 mb-3" style="line-height: 1.8;">
                Program ini merupakan wujud aksi nyata dalam mata kuliah <strong>Proyek Kewirausahaan Sosial</strong> yang diinisiasi oleh <strong>Mahasiswa Ilmu Komunikasi Kelas E1 Semester 5 UIN Sunan Ampel Surabaya (UINSA)</strong> berkolaborasi secara langsung dengan <strong>anak-anak Panti Asuhan Al-Jihad Surabaya</strong>.
            </p>

            <p class="text-secondary fs-6 mb-4" style="line-height: 1.8;">
                Melalui kegiatan olah kain perca menjadi berbagai produk kerajinan tangan bernilai jual, kami tidak hanya memanfaatkan kembali limbah kain konveksi, melainkan juga menanamkan jiwa kewirausahaan, kreativitas, dan rasa percaya diri pada adik-adik panti. Harapan besarnya, proyek ini dapat berjalan secara <strong>berkelanjutan (sustainable)</strong> dan menjadi langkah awal yang baik dalam membekali adik-adik Panti Asuhan Al-Jihad menuju kemandirian di masa depan.
            </p>

            <div class="d-flex gap-3 flex-wrap">
                <a href="catalog.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-bag-check me-1"></i> Lihat Karya Kerajinan
                </a>
                <a href="impact.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold">
                    Dampak Sosial
                </a>
            </div>
        </div>

        <!-- RINGKASAN POINT SINERGI -->
        <div class="col-lg-5">
            <div class="bg-primary text-white p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden">
                <div class="position-relative z-1">
                    <h4 class="fw-bold mb-4 pb-2 border-bottom border-white-50"><i class="bi bi-mortarboard me-2"></i>Sinergi Pendidikan & Sosial</h4>
                    
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5 mt-1"></i>
                            <div>
                                <strong>Arti Nama JARIA:</strong>
                                <p class="mb-0 text-white-80 small">Singkatan dari <em>Jahit Ceria</em>, simbol semangat & keceriaan berkarya.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5 mt-1"></i>
                            <div>
                                <strong>Inisiator Proyek:</strong>
                                <p class="mb-0 text-white-80 small">Mahasiswa Ilmu Komunikasi Kelas E1 Semester 5 UINSA Surabaya.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5 mt-1"></i>
                            <div>
                                <strong>Mitra Pemberdayaan:</strong>
                                <p class="mb-0 text-white-80 small">Anak-anak Panti Asuhan Al-Jihad Surabaya.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5 mt-1"></i>
                            <div>
                                <strong>Mata Kuliah:</strong>
                                <p class="mb-0 text-white-80 small">Proyek Kewirausahaan Sosial.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5 mt-1"></i>
                            <div>
                                <strong>Harapan Utama:</strong>
                                <p class="mb-0 text-white-80 small">Program dapat berkelanjutan & menjadi bekal kemandirian masa depan anak-anak panti.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION GALERI CAROUSEL 10 FOTO (AUTO SLIDE & FULLSCREEN PREVIEW) -->
    <div class="my-5 pt-3">
        <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill fw-bold mb-2">DOKUMENTASI KEGIATAN</span>
            <h2 class="fw-bold text-dark">Galeri Dokumentasi JARIA Craft</h2>
            <p class="text-muted">Klik foto untuk melihat dalam ukuran penuh (fullscreen)</p>
        </div>

        <div class="bg-white p-3 p-md-4 rounded-4 shadow-sm border">
            <div id="storyGalleryCarousel" class="carousel slide carousel-fade overflow-hidden rounded-3" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-indicators">
                    <?php foreach ($gallery_items as $idx => $g): ?>
                        <button type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide-to="<?php echo $idx; ?>" class="<?php echo $idx === 0 ? 'active' : ''; ?>"></button>
                    <?php endforeach; ?>
                </div>

                <div class="carousel-inner">
                    <?php foreach ($gallery_items as $idx => $g): ?>
                        <?php $img_src = get_gallery_url($g['file']); ?>
                        <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                 class="d-block w-100 gallery-card-img" 
                                 alt="<?php echo htmlspecialchars($g['title']); ?>"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#galleryLightBoxModal"
                                 onclick="setModalGallerySlide(<?php echo $idx; ?>)">
                            
                            <div class="carousel-caption-overlay text-start">
                                <h5 class="fw-bold mb-1 text-white"><i class="bi bi-camera me-2 text-warning"></i><?php echo htmlspecialchars($g['title']); ?></h5>
                                <p class="mb-0 text-white-80 small"><?php echo htmlspecialchars($g['desc']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark bg-opacity-50 p-3 rounded-circle"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#storyGalleryCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark bg-opacity-50 p-3 rounded-circle"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LIGHTBOX FULLSCREEN PREVIEW GALERI -->
<div class="modal fade" id="galleryLightBoxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-light"><i class="bi bi-images me-2"></i>Dokumentasi Kegiatan JARIA Craft</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="modalGalleryCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner text-center">
                        <?php foreach ($gallery_items as $idx => $g): ?>
                            <?php $img_src = get_gallery_url($g['file']); ?>
                            <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($img_src); ?>" class="img-fluid rounded modal-preview-img mb-3" alt="Preview foto">
                                <h5 class="fw-bold text-warning mb-1"><?php echo htmlspecialchars($g['title']); ?></h5>
                                <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($g['desc']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="carousel-control-prev" type="button" data-bs-target="#modalGalleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-secondary rounded-circle p-3"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#modalGalleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-secondary rounded-circle p-3"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setModalGallerySlide(slideIndex) {
    var modalCarousel = document.getElementById('modalGalleryCarousel');
    var carousel = new bootstrap.Carousel(modalCarousel);
    carousel.to(slideIndex);
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>