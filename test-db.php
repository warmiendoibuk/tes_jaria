<?php
// C:\xampp\htdocs\jaria\test-db.php

require_once __DIR__ . '/includes/database.php';

echo "<h2>Uji Koneksi Database JARIA</h2>";

try {
    // Cek jumlah tabel di jaria_db
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p style='color: green; font-weight: bold;'>Database connected successfully!</p>";
    echo "<p>Tabel yang ditemukan di database <strong>jaria_db</strong>:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Gagal mengambil data: " . $e->getMessage() . "</p>";
}