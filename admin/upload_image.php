<?php
// C:\xampp\htdocs\jaria\admin\upload_image.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'uploaded' => 0,
        'error' => ['message' => 'Akses ditolak. Silakan login terlebih dahulu.']
    ]);
    exit;
}

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__) . '/assets/images/news/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($ext, $allowed)) {
        $filename = 'blog_img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
            // Gunakan path relatif dari root domain/aplikasi agar bisa dibaca dari admin & halaman depan
            $url = 'assets/images/news/' . $filename;
            $funcNum = $_GET['CKEditorFuncNum'] ?? null;

            // Jika dipanggil via callback CKEditor standar lama
            if ($funcNum !== null) {
                header('Content-Type: text/html; charset=utf-8');
                echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', 'Gambar berhasil diunggah!');</script>";
                exit;
            }

            // Respon JSON standar untuk CKEditor 4 / Simple Upload
            header('Content-Type: application/json');
            echo json_encode([
                'uploaded' => 1,
                'fileName' => $filename,
                'url'      => $url
            ]);
            exit;
        }
    }
}

// Respon Gagal JSON
header('Content-Type: application/json');
echo json_encode([
    'uploaded' => 0,
    'error' => ['message' => 'Gagal mengunggah file gambar. Pastikan format gambar sesuai (JPG, PNG, WEBP, GIF).']
]);
exit;