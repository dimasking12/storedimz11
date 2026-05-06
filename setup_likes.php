<?php
require_once 'config.php';

$conn = getDBConnection();

// 1. Add likes_count column to web_news table if not exists
$checkColumn = $conn->query("SHOW COLUMNS FROM web_news LIKE 'likes_count'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE web_news ADD COLUMN likes_count INT DEFAULT 0");
    echo "Added likes_count column to web_news table.<br>";
}

// 2. Create news_likes table
$createTable = "CREATE TABLE IF NOT EXISTS web_news_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (news_id, user_id),
    FOREIGN KEY (news_id) REFERENCES web_news(id) ON DELETE CASCADE
)";

if ($conn->query($createTable)) {
    echo "Table news_likes created or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup complete!";
?>
