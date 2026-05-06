<?php
require_once 'web/config_web.php';

echo "<h2>Database Setup for Account Rental</h2>";
$conn = getDBConnection();

if (!$conn) {
    die("<p style='color:red;'>FAILED: Database connection error.</p>");
}

// 1. web_rentals
$q1 = "CREATE TABLE IF NOT EXISTS web_rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    duration VARCHAR(50),
    image_url VARCHAR(255),
    status ENUM('available', 'sold') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($q1)) {
    echo "<p style='color:green;'>SUCCESS: Table 'web_rentals' is ready.</p>";
} else {
    echo "<p style='color:red;'>ERROR: Failed to create 'web_rentals': " . $conn->error . "</p>";
}

// 2. web_rental_orders
$q2 = "CREATE TABLE IF NOT EXISTS web_rental_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE,
    user_id INT,
    rental_id INT,
    amount INT,
    status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    qris_url TEXT,
    kode_deposit VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($q2)) {
    echo "<p style='color:green;'>SUCCESS: Table 'web_rental_orders' is ready.</p>";
} else {
    echo "<p style='color:red;'>ERROR: Failed to create 'web_rental_orders': " . $conn->error . "</p>";
}

// 2b. web_rental_orders column check (for existing tables)
$colsRNT = $conn->query("SHOW COLUMNS FROM web_rental_orders");
$existingRNT = []; while($c = $colsRNT->fetch_assoc()) $existingRNT[] = $c['Field'];
if(!in_array('kode_deposit', $existingRNT)) {
    if($conn->query("ALTER TABLE web_rental_orders ADD kode_deposit VARCHAR(100)")) echo "<p style='color:green;'>Added 'kode_deposit' to web_rental_orders.</p>";
}

// 3. web_news columns check
echo "<h3>Checking web_news columns...</h3>";
$cols = $conn->query("SHOW COLUMNS FROM web_news");
$existing = []; while($c = $cols->fetch_assoc()) $existing[] = $c['Field'];

if(!in_array('image_url', $existing)) {
    if($conn->query("ALTER TABLE web_news ADD image_url VARCHAR(255)")) echo "<p style='color:green;'>Added 'image_url' to web_news.</p>";
}
if(!in_array('file_url', $existing)) {
    if($conn->query("ALTER TABLE web_news ADD file_url VARCHAR(255)")) echo "<p style='color:green;'>Added 'file_url' to web_news.</p>";
}
if(!in_array('file_name', $existing)) {
    if($conn->query("ALTER TABLE web_news ADD file_name VARCHAR(255)")) echo "<p style='color:green;'>Added 'file_name' to web_news.</p>";
}

$conn->close();
echo "<hr><p>Setup Complete. You can now use the Rental feature.</p>";
?>
