<?php
// fix_db.php - Menjalankan perbaikan database otomatis
require_once 'config_web.php';

$conn = getDBConnection();
if (!$conn) {
    die("Gagal koneksi database!");
}

echo "<pre>";
echo "Memulai perbaikan database...\n";

// 1. Tabel News
echo "Memeriksa tabel berita...\n";
$conn->query("CREATE TABLE IF NOT EXISTS web_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info', 'update', 'warning') DEFAULT 'info',
    image_url VARCHAR(255),
    file_url VARCHAR(255),
    file_name VARCHAR(255),
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Tabel Likes (Penyebab bug Guest Dashboard)
echo "Memeriksa tabel likes...\n";
$conn->query("CREATE TABLE IF NOT EXISTS web_news_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (news_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 3. Tambah kolom image_url jika belum ada di web_news
$res = $conn->query("SHOW COLUMNS FROM web_news LIKE 'image_url'");
if ($res->num_rows == 0) {
    echo "Menambahkan kolom image_url ke web_news...\n";
    $conn->query("ALTER TABLE web_news ADD image_url VARCHAR(255)");
}

// 4. Pastikan setidaknya ada 1 berita contoh agar Guest tidak melihat halaman kosong
$res = $conn->query("SELECT id FROM web_news LIMIT 1");
if ($res->num_rows == 0) {
    echo "Menambahkan berita contoh...\n";
    $conn->query("INSERT INTO web_news (title, content, type) VALUES ('Selamat Datang!', 'Selamat datang di DIMZSTORE. Silakan cek menu License Store untuk produk terbaru kami.', 'info')");
}

echo "\nPERBAIKAN SELESAI!\n";
echo "Halaman Dashboard News Anda sekarang harusnya sudah muncul normal untuk Guest.\n";
echo "Silakan hapus file fix_db.php ini demi keamanan.\n";
echo "</pre>";
$conn->close();
?>
