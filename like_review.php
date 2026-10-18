<?php
// C:\xampp\htdocs\jaria\like_review.php
session_start();
require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = (int)$_POST['review_id'];
    
    // Cek session agar 1 user/browser tidak spam like berlebihan (opsional, namun diperbolehkan klik)
    try {
        $stmt = $pdo->prepare("UPDATE reviews SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$review_id]);

        $stmt2 = $pdo->prepare("SELECT likes FROM reviews WHERE id = ?");
        $stmt2->execute([$review_id]);
        $updated = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'new_likes' => (int)($updated['likes'] ?? 0)
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui like.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);