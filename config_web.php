<?php
// config_web.php
session_start();

// Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CSRF Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * XSS Protection: Escape HTML output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Protection: Validate token
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Global Input Sanitization
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
    }
    return $data;
}

// Auto-sanitize all request data (Basic protection)
$_GET = sanitizeInput($_GET);
$_POST = sanitizeInput($_POST);

require_once __DIR__ . '/config.php'; // Reuse bot's database config

// Auto-create web_settings table if not exists
function ensureWebSettingsTable() {
    $conn = getDBConnection();
    if (!$conn) return;
    $conn->query("CREATE TABLE IF NOT EXISTS web_settings (
        `key` VARCHAR(100) NOT NULL PRIMARY KEY,
        `value` TEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->close();
}
ensureWebSettingsTable();

function ensureVouchersTable() {
    $conn = getDBConnection();
    if (!$conn) return;
    $conn->query("CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_percent INT NOT NULL,
        max_uses INT NOT NULL DEFAULT 1,
        current_uses INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->close();
}
ensureVouchersTable();

// Database functions for web
function getWebUser($userId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    $stmt = $conn->prepare("SELECT * FROM web_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

function isWebAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Function to generate unique order ID for web
function generateWebOrderID() {
    return 'WS' . date('YmdHis') . rand(100, 999);
}


// ===== TELEGRAM ORDER NOTIFICATION =====
/**
 * Kirim notifikasi orderan sukses ke admin via Telegram.
 * Token & Chat ID diambil dari web_settings (bisa diatur di Admin Panel > Notifications).
 */
function sendOrderNotificationToTelegram($order, $username, $licence) {
    $botToken = getWebSetting('notif_bot_token');
    $chatId   = getWebSetting('notif_chat_id');

    // Fallback ke BOT_TOKEN & ADMIN_CHAT_ID dari config.php jika belum diset
    if (empty($botToken)) $botToken = defined('BOT_TOKEN') ? BOT_TOKEN : '';
    if (empty($chatId))   $chatId   = defined('ADMIN_CHAT_ID') ? ADMIN_CHAT_ID : '';

    if (empty($botToken) || empty($chatId)) return false;

    $gameLabel   = strtoupper($order['game_type']);
    $duration    = $order['duration'];
    $amount      = 'Rp ' . number_format($order['amount'], 0, ',', '.');
    $orderId     = $order['order_id'];
    $keyType     = $order['key_type'] === 'extend' ? '🔄 Extend' : '🆕 New Key';
    $needsFulfil = $order['needs_fulfillment'] == 1 ? '⚠️ MANUAL (Perlu Diisi Admin)' : '✅ Otomatis';
    $licenceText = !empty($licence) ? "<code>{$licence}</code>" : '(belum tersedia)';
    $waktu       = date('d/m/Y H:i:s');

    $text = "🎉 <b>ORDER SUKSES!</b>\n"
          . "━━━━━━━━━━━━━━━━━\n"
          . "👤 <b>User:</b> {$username}\n"
          . "🎮 <b>Game:</b> {$gameLabel}\n"
          . "⏱️ <b>Durasi:</b> {$duration} hari\n"
          . "💰 <b>Nominal:</b> {$amount}\n"
          . "🏷️ <b>Tipe:</b> {$keyType}\n"
          . "📦 <b>Delivery:</b> {$needsFulfil}\n"
          . "🔑 <b>Licence:</b> {$licenceText}\n"
          . "📋 <b>Order ID:</b> <code>{$orderId}</code>\n"
          . "🕐 <b>Waktu:</b> {$waktu}\n"
          . "━━━━━━━━━━━━━━━━━";

    $url  = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = http_build_query([
        'chat_id'                  => $chatId,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 8,
    ]]);
    $result = @file_get_contents($url, false, $ctx);
    $decoded = $result ? json_decode($result, true) : null;
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

/**
 * Kirim notifikasi rental sukses ke admin via Telegram.
 */
function sendRentalNotificationToTelegram($order, $username) {
    $botToken = getWebSetting('notif_bot_token');
    $chatId   = getWebSetting('notif_chat_id');

    if (empty($botToken)) $botToken = defined('BOT_TOKEN') ? BOT_TOKEN : '';
    if (empty($chatId))   $chatId   = defined('ADMIN_CHAT_ID') ? ADMIN_CHAT_ID : '';
    if (empty($botToken) || empty($chatId)) return false;

    $amount      = 'Rp ' . number_format($order['amount'], 0, ',', '.');
    $orderId     = $order['order_id'];
    $account     = $order['title'];
    $duration    = $order['duration'];
    $email       = $order['email'];
    $waktu       = date('d/m/Y H:i:s');

    $text = "🔑 <b>RENTAL SUKSES!</b>\n"
          . "━━━━━━━━━━━━━━━━━\n"
          . "👤 <b>User:</b> {$username}\n"
          . "🎮 <b>Akun:</b> {$account}\n"
          . "⏱️ <b>Masa Sewa:</b> {$duration}\n"
          . "💰 <b>Nominal:</b> {$amount}\n"
          . "📧 <b>Email:</b> <code>{$email}</code>\n"
          . "📋 <b>Order ID:</b> <code>{$orderId}</code>\n"
          . "🕐 <b>Waktu:</b> {$waktu}\n"
          . "━━━━━━━━━━━━━━━━━";

    $url  = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = http_build_query([
        'chat_id'                  => $chatId,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 8,
    ]]);
    $result = @file_get_contents($url, false, $ctx);
    return ($result !== false);
}
?>
