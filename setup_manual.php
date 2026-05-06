<?php
require_once 'config_web.php';
$conn = getDBConnection();

// Add delivery_type to products
$conn->query("ALTER TABLE products ADD COLUMN delivery_type ENUM('auto', 'manual') DEFAULT 'auto'");

// Add process status to orders to know if manual needs action
$conn->query("ALTER TABLE web_pending_orders ADD COLUMN needs_fulfillment TINYINT(1) DEFAULT 0");

echo "Database updated for Manual/Auto delivery.";
$conn->close();
?>
