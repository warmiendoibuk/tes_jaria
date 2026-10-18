<?php
// C:\xampp\htdocs\jaria\admin\login.php
session_start();

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, langsung ke Panel Utama CMS (index.php)
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Kredensial Login Admin Utama
$default_user = 'admin';
$default_pass = 'admin123'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === $default_user && $password === $default_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username']  = $username;
        
        // Langsung diarahkan ke Panel Utama CMS (index.php)
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — JARIA Craft</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b1329; /* Warna Dasar Deep Navy Blue */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin: 0;
        }

        /* BACKGROUND AMBIENT GLOW ANIMATION (BLUE GRADIENT) */
        .glow-bg {
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.45;
            z-index: 1;
            animation: pulseGlow 8s infinite alternate ease-in-out;
        }
        .glow-1 {
            top: -15%;
            left: -10%;
            background: #1d4ed8; /* Royal Blue */
        }
        .glow-2 {
            bottom: -15%;
            right: -10%;
            background: #0284c7; /* Cyan / Bright Blue */
            animation-delay: 4s;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.35; }
            100% { transform: scale(1.25) translate(35px, 35px); opacity: 0.6; }
        }

        /* LOGIN CARD STYLING & ANIMATION */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 1.75rem;
            width: 100%;
            max-width: 430px;
            padding: 2.75rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(2, 132, 199, 0.25), 0 0 35px rgba(13, 110, 253, 0.2);
            animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            box-shadow: 0 30px 60px -12px rgba(2, 132, 199, 0.35), 0 0 45px rgba(13, 110, 253, 0.3);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* LOGO & BADGE */
        .brand-badge {
            background: linear-gradient(135deg, #0d6efd, #0284c7);
            color: #ffffff;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* INPUT GROUPS & TOGGLE PASSWORD */
        .form-label {
            color: #374151;
            font-size: 0.85rem;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 1rem;
            color: #9ca3af;
            font-size: 1.1rem;
            z-index: 5;
            transition: color 0.2s ease;
        }

        .input-group-custom .form-control {
            padding-left: 2.8rem;
            padding-right: 2.8rem;
            height: 48px;
            border-radius: 0.85rem;
            border: 1.5px solid #e5e7eb;
            background-color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }

        .input-group-custom .form-control:focus {
            background-color: #ffffff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
        }

        .input-group-custom .form-control:focus + .input-icon {
            color: #0d6efd;
        }

        /* TOGGLE SHOW HIDE EYE ICON */
        .btn-toggle-pwd {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            border-radius: 0.5rem;
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .btn-toggle-pwd:hover {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.08);
        }

        /* CAPSLOCK WARNING ALERT */
        #capsWarning {
            display: none;
            font-size: 0.78rem;
            color: #d97706;
            background-color: #fffbebf5;
            border: 1px solid #fde68a;
            border-radius: 0.6rem;
            padding: 0.4rem 0.75rem;
            margin-top: 0.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SUBMIT BUTTON */
        .btn-login {
            height: 48px;
            background: linear-gradient(135deg, #0d6efd, #0284c7);
            border: none;
            border-radius: 50rem;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.35);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .btn-login:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 12px 25px rgba(2, 132, 199, 0.45);
            background: linear-gradient(135deg, #1d4ed8, #0369a1);
        }

        .btn-login:active {
            transform: translateY(0) scale(0.98);
        }

        /* BACK LINK */
        .back-link {
            color: #6b7280;
            font-size: 0.85rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>

<!-- BACKGROUND AMBIENT GLOW EFFECT (BLUE THEME) -->
<div class="glow-bg glow-1"></div>
<div class="glow-bg glow-2"></div>

<!-- MAIN LOGIN CARD -->
<div class="login-card">
    <div class="text-center mb-4">
        <span class="badge brand-badge px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
            <i class="bi bi-shield-lock-fill me-1"></i> Panel Admin Utama
        </span>
        <h3 class="fw-extrabold text-dark mb-1" style="letter-spacing: -0.5px;">JARIA Craft</h3>
        <!-- Teks Keterangan Modul CMS Admin -->
        <p class="text-muted small mb-0" style="line-height: 1.5;">Kelola Produk, Berita & Artikel, FAQ, Dampak Sosial & Banner Web</p>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger rounded-3 py-2.5 px-3 small text-center mb-4 border-0 shadow-sm d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
        <!-- USERNAME FIELD -->
        <div class="mb-3">
            <label class="form-label fw-bold mb-1.5">Username Admin</label>
            <div class="input-group-custom">
                <i class="bi bi-person-fill input-icon"></i>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
        </div>

        <!-- PASSWORD FIELD WITH TOGGLE & CAPSLOCK WARNING -->
        <div class="mb-4">
            <label class="form-label fw-bold mb-1.5">Password Admin</label>
            <div class="input-group-custom">
                <i class="bi bi-key-fill input-icon"></i>
                <input type="password" id="passwordInput" name="password" class="form-control" placeholder="Masukkan password" required>
                <button type="button" id="togglePassword" class="btn-toggle-pwd" title="Tampilkan/Sembunyikan Password" tabindex="-1">
                    <i class="bi bi-eye-slash-fill" id="toggleIcon"></i>
                </button>
            </div>
            
            <!-- CAPSLOCK DETECTOR ALERT -->
            <div id="capsWarning">
                <i class="bi bi-arrow-up-circle-fill me-1"></i> <strong>Caps Lock Aktif!</strong> Perhatikan huruf kapital Anda.
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button type="submit" class="btn btn-primary btn-login w-100 text-white mb-3">
            Masuk Sistem Admin <i class="bi bi-arrow-right-short fs-5 align-middle ms-1"></i>
        </button>
    </form>

    <!-- BACK TO MAIN SITE -->
    <div class="text-center mt-4 pt-2 border-top border-light">
        <a href="../index.php" class="back-link text-decoration-none fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Website Utama
        </a>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("passwordInput");
    const togglePasswordBtn = document.getElementById("togglePassword");
    const toggleIcon = document.getElementById("toggleIcon");
    const capsWarning = document.getElementById("capsWarning");

    // 1. FITUR TOGGLE SHOW / HIDE PASSWORD
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", function () {
            const isPassword = passwordInput.getAttribute("type") === "password";
            
            // Ubah tipe input
            passwordInput.setAttribute("type", isPassword ? "text" : "password");
            
            // Ubah ikon mata
            if (isPassword) {
                toggleIcon.classList.remove("bi-eye-slash-fill");
                toggleIcon.classList.add("bi-eye-fill");
                togglePasswordBtn.style.color = "#0d6efd";
            } else {
                toggleIcon.classList.remove("bi-eye-fill");
                toggleIcon.classList.add("bi-eye-slash-fill");
                togglePasswordBtn.style.color = "#9ca3af";
            }
        });
    }

    // 2. FITUR DETEKSI CAPSLOCK REALTIME
    if (passwordInput && capsWarning) {
        function checkCapsLock(event) {
            if (event.getModifierState && event.getModifierState("CapsLock")) {
                capsWarning.style.display = "block";
            } else {
                capsWarning.style.display = "none";
            }
        }

        passwordInput.addEventListener("keyup", checkCapsLock);
        passwordInput.addEventListener("keydown", checkCapsLock);
        passwordInput.addEventListener("focus", checkCapsLock);
        passwordInput.addEventListener("blur", function () {
            capsWarning.style.display = "none";
        });
    }
});
</script>
</body>
</html>