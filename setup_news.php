<?php
require_once 'config_web.php';
if (!isWebAdmin()) die("Unauthorized");

$conn = getDBConnection();
$sql = "CREATE TABLE IF NOT EXISTS web_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info', 'update', 'warning') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table web_news created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
$conn->close();
?>
