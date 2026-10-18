<?php
// C:\xampp\htdocs\jaria\admin\index.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$page_title = "Panel Utama CMS — Admin JARIA Craft";
$admin_username = $_SESSION['admin_username'] ?? 'admin';
$current_page = 'index.php';
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
    <!-- Google Fonts Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f8fafc; 
            color: #0f172a; 
            overflow-x: hidden;
        }
        
        /* SIDEBAR WARNA BIRU JARIA CONSISTENT */
        .admin-sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1e40af 0%, #1e3a8a 100%);
            color: #ffffff;
            transition: all 0.3s ease;
            z-index: 1040;
            box-shadow: 4px 0 15px rgba(30, 58, 138, 0.15);
        }
        .admin-brand {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            padding: 1.25rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 0.5rem;
            background-color: rgba(0, 0, 0, 0.1);
        }
        .admin-brand img {
            max-height: 36px;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
            background: white;
            padding: 2px;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: #dbeafe;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 0.75rem;
            margin: 0.15rem 0.85rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            transform: translateX(4px);
        }
        .admin-nav-link.active {
            background-color: #ffffff;
            color: #1e40af;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .content-wrapper { 
            flex: 1; 
            padding: 2rem 2.5rem; 
            animation: fadeInPage 0.4s ease-out forwards;
            width: 100%;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* CARD KONTEN DENGAN EFEK HOVER MODERN */
        .dashboard-card {
            background: #ffffff;
            border-radius: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.07);
        }
        .card-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
        }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-teal { background: #e0f2fe; color: #0284c7; }
        .icon-blue { background: #e8eaff; color: #4f46e5; }
        .icon-red { background: #ffebe9; color: #dc3545; }
        .icon-green { background: #e6f4ea; color: #198754; }
        .icon-amber { background: #fef9c3; color: #d97706; }
        .icon-cyan { background: #cff4fc; color: #0dcaf0; }
        .icon-pink { background: #fce7f3; color: #ec4899; }
        .icon-indigo { background: #e0e7ff; color: #4338ca; }

        @media (max-width: 991.98px) {
            body { flex-direction: column; }
            .admin-sidebar { width: 100%; min-height: auto; position: relative; }
            .content-wrapper { padding: 1.25rem 1rem; }
            .mobile-header-nav { 
                display: flex !important; 
                justify-content: space-between; 
                align-items: center; 
                padding: 1rem; 
                background: linear-gradient(90deg, #1e40af 0%, #1e3a8a 100%); 
                color: white; 
            }
            .sidebar-nav-container { display: none; }
            .sidebar-nav-container.show { display: block; }
        }
    </style>
</head>
<body class="d-flex">

<!-- MOBILE NAVBAR HEADER -->
<div class="mobile-header-nav d-none">
    <a href="index.php" class="text-white fw-extrabold text-decoration-none fs-5 d-flex align-items-center gap-2">
        <img src="../assets/images/logo.png" alt="Logo JARIA" style="height: 30px;" onerror="this.onerror=null; this.src='../assets/images/logo.jpg';">
        <span>JARIA Admin</span>
    </a>
    <button class="btn btn-outline-light btn-sm rounded-3" type="button" onclick="toggleMobileSidebar()">
        <i class="bi bi-list fs-5"></i> Menu
    </button>
</div>

<!-- SIDEBAR SEBELAH KIRI -->
<div class="admin-sidebar d-flex flex-column flex-shrink-0" id="adminSidebar">
    <a href="index.php" class="admin-brand d-none d-lg-flex">
        <img src="../assets/images/logo.png" alt="Logo JARIA" onerror="this.onerror=null; this.src='../assets/images/logo.jpg';">
        <span class="me-auto">JARIA Admin</span>
    </a>
    <div class="sidebar-nav-container" id="sidebarNavContent">
        <div class="nav flex-column mb-auto py-2">
            <a href="index.php" class="admin-nav-link active">
                <i class="bi bi-grid-1x2-fill"></i> Panel Utama CMS
            </a>
            <a href="dashboard.php" class="admin-nav-link">
                <i class="bi bi-speedometer2"></i> Dashboard Ringkasan
            </a>
            <a href="site_settings.php" class="admin-nav-link">
                <i class="bi bi-gear-wide-connected"></i> Pengaturan Website
            </a>
            <a href="manage_hero.php" class="admin-nav-link">
                <i class="bi bi-sliders"></i> Banner Beranda CMS
            </a>
            <a href="manage_story.php" class="admin-nav-link">
                <i class="bi bi-journal-text"></i> Halaman Kisah Kami
            </a>
            <a href="gallery/index.php" class="admin-nav-link">
                <i class="bi bi-images"></i> Galeri & Media
            </a>
            <a href="impact.php" class="admin-nav-link">
                <i class="bi bi-heart-pulse-fill"></i> Dampak Sosial CMS
            </a>
            <a href="faq.php" class="admin-nav-link">
                <i class="bi bi-question-circle-fill"></i> Halaman FAQ CMS
            </a>
            <a href="contact.php" class="admin-nav-link">
                <i class="bi bi-geo-alt-fill"></i> Halaman Kontak CMS
            </a>
            <a href="news.php" class="admin-nav-link">
                <i class="bi bi-newspaper"></i> Berita & Blog SEO
            </a>
            <a href="manage_testimonials.php" class="admin-nav-link">
                <i class="bi bi-chat-quote-fill"></i> Ulasan & Testimoni
            </a>
            <a href="financials.php" class="admin-nav-link">
                <i class="bi bi-cash-stack"></i> Laporan Keuangan
            </a>
            <a href="products.php" class="admin-nav-link">
                <i class="bi bi-box-seam"></i> Produk Kerajinan
            </a>
            <a href="orders.php" class="admin-nav-link">
                <i class="bi bi-cart-check"></i> Pesanan Masuk
            </a>
        </div>
        <div class="p-3 border-top border-white border-opacity-25 mt-auto">
            <a href="logout.php" class="admin-nav-link text-white m-0 bg-danger bg-opacity-75 hover-opacity-100">
                <i class="bi bi-box-arrow-left"></i> Keluar (Logout)
            </a>
        </div>
    </div>
</div>

<!-- KONTEN UTAMA -->
<div class="content-wrapper">
    <!-- HEADER BAR PROFIL -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-extrabold text-dark mb-1" style="font-size: 1.75rem;">Panel Navigasi Admin</h2>
            <p class="text-secondary small mb-0">Pilih menu di bawah ini untuk mengelola konten dan sistem JARIA Craft.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-white text-secondary border px-3 py-2.5 rounded-pill fw-bold shadow-sm me-1">
                <i class="bi bi-person-circle text-primary me-1"></i> Halo, <strong class="text-dark"><?php echo htmlspecialchars($admin_username); ?></strong>
            </span>
            <a href="logout.php" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 py-2">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </a>
        </div>
    </div>

    <!-- KARTU MENU DASHBOARD -->
    <div class="row g-4">

        <!-- 1. DASHBOARD RINGKASAN -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-indigo">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Dashboard Ringkasan</h5>
                    <p class="text-muted small mb-3">Statistik & Analitik Sistem</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Lihat ringkasan total penjualan, statistik produk aktif, pesanan pending, dan stok yang menipis.
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5" style="background-color: #4338ca; border-color: #4338ca;">
                    <i class="bi bi-speedometer me-1"></i> Buka Ringkasan
                </a>
            </div>
        </div>

        <!-- 2. PENGATURAN WEBSITE -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-cyan">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Pengaturan Website</h5>
                    <p class="text-muted small mb-3">Logo, QRIS, Navbar & Footer</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Ganti logo brand, unggah foto QRIS terbaru, serta atur menu navigasi & footer secara terpusat.
                    </p>
                </div>
                <a href="site_settings.php" class="btn btn-info text-white rounded-pill w-100 fw-bold py-2.5" style="background-color: #0dcaf0; border-color: #0dcaf0;">
                    <i class="bi bi-sliders me-1"></i> Buka Pengaturan
                </a>
            </div>
        </div>

        <!-- 3. KELOLA BANNER BERANDA -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-purple">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Banner Beranda CMS</h5>
                    <p class="text-muted small mb-3">Teks & Slider Carousel Beranda</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Atur foto slider hero, susunan teks badge, judul narasi, serta tautan tombol utama di Beranda.
                    </p>
                </div>
                <a href="manage_hero.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5" style="background-color: #9333ea; border-color: #9333ea;">
                    <i class="bi bi-gear-fill me-1"></i> Kelola Banner Beranda
                </a>
            </div>
        </div>

        <!-- 4. KELOLA KISAH KAMI -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-teal">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Halaman Kisah Kami</h5>
                    <p class="text-muted small mb-3">Narasi Utama & Poin Sinergi</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Edit teks narasi kolaborasi UINSA × Panti Asuhan Al-Jihad, tombol navigasi, dan poin kartu sinergi.
                    </p>
                </div>
                <a href="manage_story.php" class="btn btn-info text-white rounded-pill w-100 fw-bold py-2.5" style="background-color: #0284c7; border-color: #0284c7;">
                    <i class="bi bi-pencil-square me-1"></i> Kelola Kisah Kami
                </a>
            </div>
        </div>

        <!-- 5. GALERI & MEDIA -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-blue">
                        <i class="bi bi-images"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Galeri & Media</h5>
                    <p class="text-muted small mb-3">Foto & Video Dokumentasi</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Upload foto/video dokumentasi kegiatan untuk carousel galeri di halaman Kisah Kami.
                    </p>
                </div>
                <a href="gallery/index.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5" style="background-color: #4f46e5; border-color: #4f46e5;">
                    <i class="bi bi-gear-fill me-1"></i> Kelola Galeri Web
                </a>
            </div>
        </div>

        <!-- 6. DAMPAK SOSIAL CMS -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-red">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Dampak Sosial CMS</h5>
                    <p class="text-muted small mb-3">Banner Header & Pilar Dampak</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Kelola foto slider banner header dampak sosial serta 6 pilar jejak kebaikan sosial.
                    </p>
                </div>
                <a href="impact.php" class="btn btn-danger rounded-pill w-100 fw-bold py-2.5">
                    <i class="bi bi-heart-fill me-1"></i> Kelola Dampak Sosial
                </a>
            </div>
        </div>

        <!-- 7. HALAMAN FAQ CMS -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-cyan">
                        <i class="bi bi-question-circle-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Halaman FAQ CMS</h5>
                    <p class="text-muted small mb-3">Banner Header & Daftar FAQ</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Atur foto slider banner header serta daftar pertanyaan dan jawaban FAQ secara fleksibel.
                    </p>
                </div>
                <a href="faq.php" class="btn btn-info text-white rounded-pill w-100 fw-bold py-2.5">
                    <i class="bi bi-gear-fill me-1"></i> Kelola FAQ
                </a>
            </div>
        </div>

        <!-- 8. HALAMAN KONTAK CMS -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-pink">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Halaman Kontak CMS</h5>
                    <p class="text-muted small mb-3">Banner Header, Sosmed, & Maps</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Kelola foto slider banner kontak, detail alamat, email, WhatsApp, serta platform media sosial.
                    </p>
                </div>
                <a href="contact.php" class="btn btn-danger rounded-pill w-100 fw-bold py-2.5" style="background-color: #ec4899; border-color: #ec4899;">
                    <i class="bi bi-gear-fill me-1"></i> Kelola Kontak
                </a>
            </div>
        </div>

        <!-- 9. BERITA & BLOG SEO -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-blue">
                        <i class="bi bi-newspaper"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Berita & Blog SEO</h5>
                    <p class="text-muted small mb-3">Artikel, Kategori & Hashtag SEO</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Tulis berita inspirasi kegiatan santri, atur hashtag terindeks Google, embed media, serta format ala Blogspot.
                    </p>
                </div>
                <a href="news.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5" style="background-color: #2563eb; border-color: #2563eb;">
                    <i class="bi bi-pencil-square me-1"></i> Kelola Berita
                </a>
            </div>
        </div>

        <!-- 10. ULASAN & TESTIMONI -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-purple">
                        <i class="bi bi-chat-quote-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Ulasan & Testimoni</h5>
                    <p class="text-muted small mb-3">Moderasi Ulasan & Bintang Rating</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Kelola ulasan masuk dari audiens, atur status visibilitas, edit komentar, serta tingkatkan apresiasi.
                    </p>
                </div>
                <a href="manage_testimonials.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5" style="background-color: #8b5cf6; border-color: #8b5cf6;">
                    <i class="bi bi-chat-square-text-fill me-1"></i> Kelola Ulasan
                </a>
            </div>
        </div>

        <!-- 11. LAPORAN KEUANGAN -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Laporan Keuangan</h5>
                    <p class="text-muted small mb-3">Pemasukan & Pengeluaran</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Catat arus kas penjualan, biaya bahan baku, operasional, serta cetak laporan keuangan.
                    </p>
                </div>
                <a href="financials.php" class="btn btn-success rounded-pill w-100 fw-bold py-2.5">
                    <i class="bi bi-file-earmark-text-fill me-1"></i> Kelola Keuangan
                </a>
            </div>
        </div>

        <!-- 12. PRODUK KERAJINAN -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-amber">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Produk Kerajinan</h5>
                    <p class="text-muted small mb-3">Katalog & Stok Barang</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Tambah produk kerajinan perca baru, atur harga, kategori, serta perbarui stok barang.
                    </p>
                </div>
                <a href="products.php" class="btn btn-warning text-dark rounded-pill w-100 fw-bold py-2.5">
                    <i class="bi bi-bag-plus-fill me-1"></i> Kelola Produk
                </a>
            </div>
        </div>

        <!-- 13. PESANAN MASUK -->
        <div class="col-md-6 col-lg-4">
            <div class="dashboard-card p-4 shadow-sm">
                <div>
                    <div class="card-icon-box icon-teal">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Pesanan Masuk</h5>
                    <p class="text-muted small mb-3">Daftar Transaksi Pelanggan</p>
                    <p class="text-secondary small leading-relaxed mb-4">
                        Pantau pesanan baru dari pelanggan, konfirmasi status pembayaran, serta kelola detail transaksi.
                    </p>
                </div>
                <a href="orders.php" class="btn btn-info text-white rounded-pill w-100 fw-bold py-2.5" style="background-color: #0284c7; border-color: #0284c7;">
                    <i class="bi bi-receipt me-1"></i> Kelola Pesanan
                </a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleMobileSidebar() {
    document.getElementById('sidebarNavContent').classList.toggle('show');
}
</script>
</body>
</html>