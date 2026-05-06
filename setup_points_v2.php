<?php
require_once 'config_web.php';
$conn = getDBConnection();

// Add points_earned to web_pending_orders
$conn->query("ALTER TABLE web_pending_orders ADD COLUMN points_earned INT DEFAULT 0");

echo "Database updated: Added points_earned to web_pending_orders.";
$conn->close();
?>
