<?php
// C:\xampp\htdocs\jaria\ajax_testimonial.php
session_start();
require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'add') {
    $name    = trim($_POST['name'] ?? '');
    $role    = trim($_POST['role'] ?? 'Pembeli');
    $rating  = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if (empty($name) || empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama dan ulasan wajib diisi!']);
        exit;
    }

    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = 'avatar_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename);
            $avatar_path = 'uploads/avatars/' . $filename;
        }
    }

    try {
        // Coba simpan ke kolom comment / message secara otomatis
        try {
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, role, avatar, rating, comment, likes_count, is_approved, created_at) VALUES (:name, :role, :avatar, :rating, :comment, 0, 1, NOW())");
            $stmt->execute([
                'name'    => $name,
                'role'    => empty($role) ? 'Pembeli' : $role,
                'avatar'  => $avatar_path,
                'rating'  => $rating,
                'comment' => $comment
            ]);
        } catch (PDOException $ex) {
            // Fallback jika nama kolom di DB adalah 'message'
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, role, avatar, rating, message, likes_count, is_approved, created_at) VALUES (:name, :role, :avatar, :rating, :comment, 0, 1, NOW())");
            $stmt->execute([
                'name'    => $name,
                'role'    => empty($role) ? 'Pembeli' : $role,
                'avatar'  => $avatar_path,
                'rating'  => $rating,
                'comment' => $comment
            ]);
        }
        
        $testimonial_id = $pdo->lastInsertId();

        // UPLOAD BANYAK FOTO (MULTIPLE UPLOAD)
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $upload_photos_dir = __DIR__ . '/uploads/testimonials/';
            if (!is_dir($upload_photos_dir)) mkdir($upload_photos_dir, 0777, true);

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (in_array($ext, $allowed)) {
                        $p_filename = 'photo_' . time() . '_' . $key . '_' . rand(100, 999) . '.' . $ext;
                        if (move_uploaded_file($tmp_name, $upload_photos_dir . $p_filename)) {
                            $stmt_photo = $pdo->prepare("INSERT INTO testimonial_photos (testimonial_id, photo_path) VALUES (:tid, :path)");
                            $stmt_photo->execute([
                                'tid'  => $testimonial_id,
                                'path' => 'uploads/testimonials/' . $p_filename
                            ]);
                        }
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Ulasan Anda berhasil ditambahkan!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

// AKSI LIKE DENGAN PROTEKSI CEK LIKED (CEK SPAM)
if ($action === 'like') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID ulasan tidak valid.']);
        exit;
    }

    // Inisialisasi array session liked
    if (!isset($_SESSION['liked_testimonials'])) {
        $_SESSION['liked_testimonials'] = [];
    }

    // Cek apakah user/device ini sudah pernah like ulasan ini
    if (in_array($id, $_SESSION['liked_testimonials'])) {
        echo json_encode(['status' => 'already_liked', 'message' => 'Anda sudah menyukai ulasan ini!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE testimonials SET likes_count = likes_count + 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $stmt2 = $pdo->prepare("SELECT likes_count FROM testimonials WHERE id = :id");
        $stmt2->execute(['id' => $id]);
        $likes = $stmt2->fetchColumn();

        // Tandai di session bahwa ulasan ID ini sudah disukai
        $_SESSION['liked_testimonials'][] = $id;

        echo json_encode(['status' => 'success', 'likes' => (int)$likes]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}