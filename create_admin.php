<?php
require_once 'config_web.php';

$username = 'dimas';
$password = 'admin';
$hashed = password_hash($password, PASSWORD_BCRYPT);
$wa = '08123456789';
$tg = '@dimas1120';

$conn = getDBConnection();
if (!$conn) die("Connection failed");

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM web_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Update existing user to admin
    $stmt = $conn->prepare("UPDATE web_users SET password = ?, role = 'admin' WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) echo "User 'dimas' updated to Admin with password 'admin'";
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO web_users (username, password, wa_number, telegram_user, role, points, created_at) VALUES (?, ?, ?, ?, 'admin', 999999, NOW())");
    $stmt->bind_param("ssss", $username, $hashed, $wa, $tg);
    if ($stmt->execute()) echo "Admin user 'dimas' created successfully with password 'admin'";
}

$conn->close();
?>
