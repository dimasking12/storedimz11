<?php
// config.php - Configuration file with database functions
// UPDATED: Status = 1 (active), UUID = NULL for new licenses and extends
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
date_default_timezone_set('Asia/Jakarta');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'vipc7892_Dimas1120');
define('DB_PASSWORD', 'dimasahm12#');
define('DB_NAME', 'vipc7892_Dimz_db');

// API Configuration
define('API_KEY', 'AjnQBMAhSZ4kJhqp');
define('MERCHANT_CODE', 'DIMZ1945');

// Bot Configuration
define('BOT_TOKEN', '8068557641:AAFtzz8RQznufSuMaZq7NTA7oDwXcNi2huc');
define('ADMIN_CHAT_ID', '6201552432');

// Path Configuration
define('WELCOME_IMAGE', 'https://vip1120.site/botdimas/img/contoh1.jpg');

// Timeout Configuration - 25 minutes (1500 seconds)
define('ORDER_TIMEOUT', 1500);

// Real-time check interval - 20 seconds
define('PAYMENT_CHECK_INTERVAL', 20);

// Broadcast waiting timeout - 5 minutes (300 seconds)
define('BROADCAST_TIMEOUT', 300);

// Price Configuration
$prices = [
    '1' => 15000,
    '2' => 30000,
    '3' => 40000,
    '4' => 50000,
    '5' => 60000,
    '6' => 70000,
    '7' => 80000,
    '8' => 90000,
    '10' => 100000,
    '15' => 150000,
    '20' => 180000,
    '30' => 250000
];

// Point Configuration
$point_rules = [
    '1' => 1,
    '2' => 1,
    '3' => 2,
    '4' => 3,
    '5' => 4,
    '6' => 4,
    '7' => 5,
    '8' => 5,
    '10' => 6,
    '15' => 8,
    '20' => 10,
    '30' => 15
];

// Point redemption rates (points needed per day)
define('POINTS_PER_DAY', 12);

function logMessage($message) {
    $logFile = __DIR__ . '/bot_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

function sendSimpleMessage($chatId, $text, $replyMarkup = null) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    logMessage("Message sent to $chatId: " . substr($text, 0, 50));
    return $result;
}

function notifyAdmin($message) {
    return sendSimpleMessage(ADMIN_CHAT_ID, $message);
}

function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            logMessage("Database connection failed: " . $conn->connect_error);
            return false;
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        logMessage("Database exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if licence exists in database
 * NEW SCHEMA: uses 'licence' column instead of 'username'
 */
function isLicenceExists($licence, $table = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        if ($table) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE licence = ?");
            $stmt->bind_param("s", $licence);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'];
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM freefire WHERE licence = ?");
            $stmt->bind_param("s", $licence);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $countFF = $row['count'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM ffmax WHERE licence = ?");
            $stmt->bind_param("s", $licence);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $countFFMax = $row['count'];
            $stmt->close();

            $count = $countFF + $countFFMax;
        }
        $conn->close();
        return ($count > 0);
    } catch (Exception $e) {
        logMessage("Error in isLicenceExists: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

/**
 * Generate random credentials - returns licence key
 * Format: 2 letters + 2 numbers (e.g., AB12)
 */
function generateRandomCredentials() {
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $licence = '';
    for ($i = 0; $i < 2; $i++) {
        $licence .= $letters[rand(0, strlen($letters) - 1)];
    }
    for ($i = 0; $i < 2; $i++) {
        $licence .= $numbers[rand(0, strlen($numbers) - 1)];
    }
    return ['licence' => $licence];
}

/**
 * Generate redeem credentials with "redeem" prefix
 * Format: redeemXXX where XXX = random numbers and letters
 */
function generateRedeemCredentials() {
    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $licence = 'redeem';
    $licence .= $numbers[rand(0, strlen($numbers) - 1)];
    for ($i = 0; $i < 2; $i++) {
        $licence .= $letters[rand(0, strlen($letters) - 1)];
    }
    return ['licence' => $licence];
}

/**
 * Get user by licence key
 * NEW SCHEMA: uses 'licence' column
 */
function getUserByLicence($licence, $gameType = null) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        if ($gameType == 'ff') {
            $stmt = $conn->prepare("SELECT *, 'ff' as game_type FROM freefire WHERE licence = ?");
            $stmt->bind_param("s", $licence);
        } elseif ($gameType == 'ffmax') {
            $stmt = $conn->prepare("SELECT *, 'ffmax' as game_type FROM ffmax WHERE licence = ?");
            $stmt->bind_param("s", $licence);
        } else {
            $stmt = $conn->prepare("SELECT *, 'ff' as game_type FROM freefire WHERE licence = ?
                                   UNION ALL
                                   SELECT *, 'ffmax' as game_type FROM ffmax WHERE licence = ?
                                   LIMIT 1");
            $stmt->bind_param("ss", $licence, $licence);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
            return $user;
        }
        $stmt->close();
        $conn->close();
        return false;
    } catch (Exception $e) {
        logMessage("Error in getUserByLicence: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

/**
 * Extend user license
 * UPDATED: Sets status = 1 and uuid = NULL when extending
 */
function extendUserLicense($licence, $duration, $gameType) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        if ($gameType == 'ff') {
            $stmt = $conn->prepare("SELECT expDate, expDays FROM freefire WHERE licence = ?");
        } elseif ($gameType == 'ffmax') {
            $stmt = $conn->prepare("SELECT expDate, expDays FROM ffmax WHERE licence = ?");
        } else {
            return false;
        }
        $stmt->bind_param("s", $licence);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $currentExpDate = $user['expDate'];
            $currentExpDays = intval($user['expDays']);
            $stmt->close();

            $newExpDays = $currentExpDays + intval($duration);

            if (strtotime($currentExpDate) < time()) {
                $newExpDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
                logMessage("Extend from NOW - Licence: $licence, Current: $currentExpDate, New: $newExpDate");
            } else {
                $newExpDate = date('Y-m-d H:i:s', strtotime("$currentExpDate +$duration days"));
                logMessage("Extend from EXISTING - Licence: $licence, Current: $currentExpDate, New: $newExpDate");
            }

            // UPDATE: Set status = 1 (active) and uuid = NULL
            if ($gameType == 'ff') {
                $stmt = $conn->prepare("UPDATE freefire SET expDate = ?, expDays = ?, status = 1, uuid = NULL WHERE licence = ?");
            } elseif ($gameType == 'ffmax') {
                $stmt = $conn->prepare("UPDATE ffmax SET expDate = ?, expDays = ?, status = 1, uuid = NULL WHERE licence = ?");
            }
            $stmt->bind_param("sis", $newExpDate, $newExpDays, $licence);
            $result = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $conn->close();
            
            if ($affected > 0) {
                logMessage("SUCCESS: License extended - Licence: $licence, Duration: $duration days, New ExpDays: $newExpDays, Status set to 1, UUID set to NULL");
                return true;
            } else {
                logMessage("WARNING: No rows affected when extending license - Licence: $licence");
                return false;
            }
        }
        $stmt->close();
        $conn->close();
        return false;
    } catch (Exception $e) {
        logMessage("Error in extendUserLicense: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function saveUserState($chatId, $state, $data = []) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $jsonData = json_encode($data);
        $stmt = $conn->prepare("SELECT id FROM user_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE user_states SET state = ?, data = ?, error_count = 0, updated_at = NOW() WHERE chat_id = ?");
            $stmt->bind_param("sss", $state, $jsonData, $chatId);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_states (chat_id, state, data, error_count, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())");
            $stmt->bind_param("sss", $chatId, $state, $jsonData);
        }
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("User state saved - Chat: $chatId, State: $state");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in saveUserState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function getUserState($chatId) {
    $conn = getDBConnection();
    if (!$conn) return null;
    try {
        $stmt = $conn->prepare("SELECT state, data, error_count FROM user_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $state = [
                'state' => $row['state'],
                'data' => json_decode($row['data'], true),
                'error_count' => $row['error_count']
            ];
        } else {
            $state = null;
        }
        $stmt->close();
        $conn->close();
        return $state;
    } catch (Exception $e) {
        logMessage("Error in getUserState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return null;
    }
}

function clearUserState($chatId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("DELETE FROM user_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("User state cleared - Chat: $chatId");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in clearUserState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function updateUserErrorCount($chatId, $errorCount) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("UPDATE user_states SET error_count = ?, updated_at = NOW() WHERE chat_id = ?");
        $stmt->bind_param("is", $errorCount, $chatId);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("User error count updated - Chat: $chatId, Error Count: $errorCount");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in updateUserErrorCount: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function resetUserErrorCount($chatId) {
    return updateUserErrorCount($chatId, 0);
}

function getUserPoints($chatId) {
    $conn = getDBConnection();
    if (!$conn) return 0;
    try {
        $stmt = $conn->prepare("SELECT points FROM user_points WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $points = $row['points'];
        } else {
            $points = 0;
            $stmt2 = $conn->prepare("INSERT INTO user_points (chat_id, points, created_at, updated_at) VALUES (?, 0, NOW(), NOW())");
            $stmt2->bind_param("s", $chatId);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
        $conn->close();
        return $points;
    } catch (Exception $e) {
        logMessage("Error in getUserPoints: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return 0;
    }
}

function addUserPoints($chatId, $points, $reason = '') {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("INSERT INTO user_points (chat_id, points, created_at, updated_at)
                               VALUES (?, ?, NOW(), NOW())
                               ON DUPLICATE KEY UPDATE points = points + ?, updated_at = NOW()");
        $stmt->bind_param("sii", $chatId, $points, $points);
        $result = $stmt->execute();
        $stmt->close();
        if ($result && !empty($reason)) {
            $stmt2 = $conn->prepare("INSERT INTO point_transactions (chat_id, points, type, reason, created_at) VALUES (?, ?, 'earn', ?, NOW())");
            $stmt2->bind_param("sis", $chatId, $points, $reason);
            $stmt2->execute();
            $stmt2->close();
        }
        $conn->close();
        logMessage("Points added - Chat: $chatId, Points: $points, Reason: $reason");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in addUserPoints: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function redeemUserPoints($chatId, $points, $reason = '') {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $currentPoints = getUserPoints($chatId);
        if ($currentPoints < $points) {
            logMessage("Insufficient points - Chat: $chatId, Current: $currentPoints, Needed: $points");
            return false;
        }
        $stmt = $conn->prepare("UPDATE user_points SET points = points - ?, updated_at = NOW() WHERE chat_id = ?");
        $stmt->bind_param("is", $points, $chatId);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($result && $affected > 0 && !empty($reason)) {
            $stmt2 = $conn->prepare("INSERT INTO point_transactions (chat_id, points, type, reason, created_at) VALUES (?, ?, 'redeem', ?, NOW())");
            $stmt2->bind_param("sis", $chatId, $points, $reason);
            $stmt2->execute();
            $stmt2->close();
        }
        $conn->close();
        logMessage("Points redeemed - Chat: $chatId, Points: $points, Reason: $reason");
        return ($result && $affected > 0);
    } catch (Exception $e) {
        logMessage("Error in redeemUserPoints: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

// ===== WEB SETTINGS HELPERS =====
function getWebSetting($key, $default = '') {
    $conn = getDBConnection();
    if (!$conn) return $default;
    $stmt = $conn->prepare("SELECT `value` FROM web_settings WHERE `key` = ?");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $row ? $row['value'] : $default;
    }
    $conn->close();
    return $default;
}

function setWebSetting($key, $value) {
    $conn = getDBConnection();
    if (!$conn) return false;
    $stmt = $conn->prepare("INSERT INTO web_settings (`key`, `value`) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()");
    if ($stmt) {
        $stmt->bind_param("ss", $key, $value);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $result;
    }
    $conn->close();
    return false;
}

function getPointRules() {
    $json = getWebSetting('extend_point_rules');
    if ($json) {
        $rules = json_decode($json, true);
        if ($rules) return $rules;
    }
    global $point_rules;
    return $point_rules;
}

function calculatePointsForDuration($duration) {
    $point_rules = getPointRules();
    return isset($point_rules[$duration]) ? $point_rules[$duration] : 0;
}

function calculatePointsNeededForDays($days) {
    return $days * POINTS_PER_DAY;
}

function createPayment($orderId, $amount) {
    $url = "https://cvqris-ariepulsa.my.id/qris/?action=get-deposit&kode=" . urlencode($orderId) . "&nominal=" . $amount . "&apikey=" . API_KEY;
    logMessage("Creating payment: " . $url);
    $response = file_get_contents($url);
    logMessage("Payment response: " . $response);
    return json_decode($response, true);
}

function checkPaymentStatus($depositCode) {
    $url = "https://cvqris-ariepulsa.my.id/qris/?action=get-mutasi&kode=" . urlencode($depositCode) . "&apikey=" . API_KEY;
    logMessage("Checking payment status: " . $url);
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    logMessage("Payment status response: " . $response);
    if ($data && $data['status'] && isset($data['data']['status']) && $data['data']['status'] == 'Success') {
        return $data['data'];
    }
    return false;
}

function savePendingOrder($orderId, $chatId, $gameType, $duration, $amount, $depositCode, $keyType, $manualUsername = '', $manualPassword = '') {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("INSERT INTO pending_orders (order_id, chat_id, game_type, duration, amount, deposit_code, key_type, manual_username, manual_password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssssissss", $orderId, $chatId, $gameType, $duration, $amount, $depositCode, $keyType, $manualUsername, $manualPassword);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        
        $logType = ($keyType == 'extend') ? 'EXTEND' : 'ORDER';
        logMessage("$logType saved - Order: $orderId, Chat: $chatId, Type: $gameType, Duration: $duration, Amount: $amount, KeyType: $keyType");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in savePendingOrder: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function getPendingOrder($chatId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("SELECT * FROM pending_orders WHERE chat_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $order;
    } catch (Exception $e) {
        logMessage("Error in getPendingOrder: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function updateOrderStatus($depositCode, $status) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("UPDATE pending_orders SET status = ?, updated_at = NOW() WHERE deposit_code = ?");
        $stmt->bind_param("ss", $status, $depositCode);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("Order status updated - Deposit Code: $depositCode, Status: $status");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in updateOrderStatus: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

/**
 * LICENSE MANAGEMENT FUNCTIONS
 * UPDATED: Status = 1 (active), UUID = NULL for new licenses
 */
function saveLicenseToDatabase($table, $licence, $duration, $reference) {
    $conn = getDBConnection();
    if (!$conn) {
        logMessage("ERROR: Database connection failed in saveLicenseToDatabase");
        return false;
    }
    try {
        if (isLicenceExists($licence, $table)) {
            logMessage("ERROR: Licence already exists in table $table: " . $licence);
            $conn->close();
            return false;
        }

        $expDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
        $expDays = intval($duration);
        $uuid = null;  // Set UUID to NULL
        $status = 1;   // Set status to 1 (active)
        $gameId = ($table == 'freefire') ? 1 : 2;

        logMessage("DEBUG: Attempting to save license - Table: $table, Licence: $licence, Duration: $duration, Status: $status, UUID: NULL");

        $stmt = $conn->prepare("INSERT INTO $table (licence, uuid, expDate, expDays, status, game_id, reference, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            logMessage("ERROR: Prepare failed - " . $conn->error);
            $conn->close();
            return false;
        }

        $stmt->bind_param("sssiiss", $licence, $uuid, $expDate, $expDays, $status, $gameId, $reference);
        $result = $stmt->execute();

        if (!$result) {
            logMessage("ERROR: Execute failed - " . $stmt->error);
            $stmt->close();
            $conn->close();
            return false;
        }

        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        if ($result && $affected > 0) {
            logMessage("SUCCESS: License saved to $table - Licence: $licence, Duration: $duration days, ExpDays: $expDays, Status: $status, UUID: NULL, GameID: $gameId");
            return true;
        } else {
            logMessage("ERROR: Failed to save license to $table - Affected rows: $affected");
            return false;
        }
    } catch (Exception $e) {
        logMessage("EXCEPTION in saveLicenseToDatabase: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function cleanupExpiredOrders() {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $expiredTime = date('Y-m-d H:i:s', time() - ORDER_TIMEOUT);
        $stmt = $conn->prepare("DELETE FROM pending_orders WHERE status = 'pending' AND created_at < ?");
        if (!$stmt) {
            $conn->close();
            return false;
        }
        $stmt->bind_param("s", $expiredTime);
        $result = $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        $conn->close();
        if ($deleted > 0) {
            logMessage("Cleaned up $deleted expired orders (older than $expiredTime)");
        }
        return $deleted;
    } catch (Exception $e) {
        logMessage("Error in cleanupExpiredOrders: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

// ============================================================================
// BROADCAST FEATURE FUNCTIONS
// ============================================================================

function saveBotUser($chatId, $firstName = '', $username = '') {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("SELECT id FROM bot_users WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE bot_users SET first_name = ?, username = ?, last_active = NOW(), is_active = 1 WHERE chat_id = ?");
            $stmt->bind_param("sss", $firstName, $username, $chatId);
        } else {
            $stmt = $conn->prepare("INSERT INTO bot_users (chat_id, first_name, username, first_started, last_active, is_active) VALUES (?, ?, ?, NOW(), NOW(), 1)");
            $stmt->bind_param("sss", $chatId, $firstName, $username);
        }
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("Bot user saved/updated - Chat: $chatId, Name: $firstName");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in saveBotUser: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function getAllBotUsers() {
    $conn = getDBConnection();
    if (!$conn) return [];
    try {
        $stmt = $conn->prepare("SELECT chat_id, first_name FROM bot_users WHERE is_active = 1 ORDER BY last_active DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        $conn->close();
        logMessage("Retrieved " . count($users) . " active bot users for broadcast");
        return $users;
    } catch (Exception $e) {
        logMessage("Error in getAllBotUsers: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return [];
    }
}

function getTotalBotUsers() {
    $conn = getDBConnection();
    if (!$conn) return 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bot_users WHERE is_active = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total = $row['total'];
        $stmt->close();
        $conn->close();
        return $total;
    } catch (Exception $e) {
        logMessage("Error in getTotalBotUsers: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return 0;
    }
}

function saveAdminState($chatId, $state, $data = []) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $data['broadcast_started_at'] = time();
        $jsonData = json_encode($data);
        $stmt = $conn->prepare("SELECT id FROM admin_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE admin_states SET state = ?, data = ?, updated_at = NOW() WHERE chat_id = ?");
            $stmt->bind_param("sss", $state, $jsonData, $chatId);
        } else {
            $stmt = $conn->prepare("INSERT INTO admin_states (chat_id, state, data, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sss", $chatId, $state, $jsonData);
        }
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("Admin state saved - Chat: $chatId, State: $state");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in saveAdminState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function getAdminState($chatId) {
    $conn = getDBConnection();
    if (!$conn) return null;
    try {
        $stmt = $conn->prepare("SELECT state, data FROM admin_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $decodedData = json_decode($row['data'], true);
            if (strpos($row['state'], 'waiting_broadcast') === 0) {
                $startedAt = $decodedData['broadcast_started_at'] ?? 0;
                if ($startedAt > 0 && (time() - $startedAt) > BROADCAST_TIMEOUT) {
                    logMessage("Admin broadcast state expired - Chat: $chatId, Started: " . date('H:i:s', $startedAt));
                    clearAdminState($chatId);
                    return null;
                }
            }
            $state = [
                'state' => $row['state'],
                'data' => $decodedData
            ];
        } else {
            $state = null;
        }
        $stmt->close();
        $conn->close();
        return $state;
    } catch (Exception $e) {
        logMessage("Error in getAdminState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return null;
    }
}

function clearAdminState($chatId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("DELETE FROM admin_states WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("Admin state cleared - Chat: $chatId");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in clearAdminState: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function saveBroadcastHistory($adminChatId, $broadcastType, $messageType, $totalUsers, $successCount, $failedCount) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("INSERT INTO broadcast_history (admin_chat_id, broadcast_type, message_type, total_users, success_count, failed_count, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssiii", $adminChatId, $broadcastType, $messageType, $totalUsers, $successCount, $failedCount);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        logMessage("Broadcast history saved - Admin: $adminChatId, Type: $broadcastType, Success: $successCount/$totalUsers");
        return $result;
    } catch (Exception $e) {
        logMessage("Error in saveBroadcastHistory: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function markUserInactive($chatId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("UPDATE bot_users SET is_active = 0 WHERE chat_id = ?");
        $stmt->bind_param("s", $chatId);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $result;
    } catch (Exception $e) {
        logMessage("Error in markUserInactive: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
        return false;
    }
}

function isAdmin($chatId) {
    return ($chatId == ADMIN_CHAT_ID);
}

function forwardPhotoToUser($chatId, $photoFileId, $caption = '') {
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoFileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $decoded = json_decode($result, true);
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

function forwardVideoToUser($chatId, $videoFileId, $caption = '') {
    $data = [
        'chat_id' => $chatId,
        'video' => $videoFileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendVideo";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $decoded = json_decode($result, true);
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

function forwardDocumentToUser($chatId, $documentFileId, $caption = '') {
    $data = [
        'chat_id' => $chatId,
        'document' => $documentFileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendDocument";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $decoded = json_decode($result, true);
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

function forwardAudioToUser($chatId, $audioFileId, $caption = '') {
    $data = [
        'chat_id' => $chatId,
        'audio' => $audioFileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendAudio";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $decoded = json_decode($result, true);
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

function sendTextToUser($chatId, $text) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    $decoded = json_decode($result, true);
    return ($decoded && isset($decoded['ok']) && $decoded['ok'] === true);
}

function isBroadcastRunning() {
    $lockFile = __DIR__ . '/broadcast_lock.json';
    if (!file_exists($lockFile)) {
        return false;
    }
    $lockData = json_decode(file_get_contents($lockFile), true);
    if (!$lockData) {
        return false;
    }
    if (isset($lockData['started_at']) && (time() - $lockData['started_at']) > 600) {
        @unlink($lockFile);
        logMessage("Removed stale broadcast lock");
        return false;
    }
    return true;
}

function setBroadcastLock($broadcastId) {
    $lockFile = __DIR__ . '/broadcast_lock.json';
    $lockData = [
        'broadcast_id' => $broadcastId,
        'started_at' => time()
    ];
    file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT));
    logMessage("Broadcast lock set - ID: $broadcastId");
}

function removeBroadcastLock() {
    $lockFile = __DIR__ . '/broadcast_lock.json';
    if (file_exists($lockFile)) {
        @unlink($lockFile);
        logMessage("Broadcast lock removed");
    }
}

function broadcastToAllUsers($messageType, $fileId, $caption, $broadcastType) {
    $broadcastId = 'BC_' . time() . '_' . rand(1000, 9999);
    if (isBroadcastRunning()) {
        logMessage("WARNING: Another broadcast is already running, skipping");
        return ['total' => 0, 'success' => 0, 'failed' => 0];
    }
    setBroadcastLock($broadcastId);
    $users = getAllBotUsers();
    $totalUsers = count($users);
    $successCount = 0;
    $failedCount = 0;
    $trackingFile = __DIR__ . '/broadcast_tracking_' . $broadcastId . '.json';
    $sentUsers = [];
    logMessage("Starting broadcast $broadcastId - Type: $broadcastType, Message Type: $messageType, Total Users: $totalUsers");
    foreach ($users as $user) {
        $chatId = $user['chat_id'];
        if (in_array($chatId, $sentUsers)) {
            logMessage("SKIP: Already sent to user $chatId in this broadcast");
            continue;
        }
        try {
            $success = false;
            $finalCaption = $caption;
            if ($broadcastType == 'adds') {
                $finalCaption = "🔔 <b>NOTIFIKASI PENTING!</b>\n\n" . $caption;
            }
            switch ($messageType) {
                case 'photo':
                    $success = forwardPhotoToUser($chatId, $fileId, $finalCaption);
                    break;
                case 'video':
                    $success = forwardVideoToUser($chatId, $fileId, $finalCaption);
                    break;
                case 'document':
                    $success = forwardDocumentToUser($chatId, $fileId, $finalCaption);
                    break;
                case 'audio':
                    $success = forwardAudioToUser($chatId, $fileId, $finalCaption);
                    break;
                case 'text':
                    $success = sendTextToUser($chatId, $finalCaption);
                    break;
            }
            $sentUsers[] = $chatId;
            if ($success) {
                $successCount++;
                logMessage("Broadcast $broadcastId sent to user: $chatId");
            } else {
                $failedCount++;
                logMessage("Broadcast $broadcastId failed for user: $chatId");
                markUserInactive($chatId);
            }
            file_put_contents($trackingFile, json_encode($sentUsers));
            usleep(50000);
        } catch (Exception $e) {
            $failedCount++;
            $sentUsers[] = $chatId;
            logMessage("Exception broadcasting $broadcastId to $chatId: " . $e->getMessage());
        }
    }
    removeBroadcastLock();
    if (file_exists($trackingFile)) {
        @unlink($trackingFile);
    }
    logMessage("Broadcast $broadcastId completed - Success: $successCount, Failed: $failedCount, Total: $totalUsers");
    return ['total' => $totalUsers, 'success' => $successCount, 'failed' => $failedCount];
}

function cleanupExpiredBroadcastStates() {
    $conn = getDBConnection();
    if (!$conn) return;
    try {
        $stmt = $conn->prepare("SELECT chat_id, data FROM admin_states WHERE state LIKE 'waiting_broadcast_%'");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data = json_decode($row['data'], true);
            $startedAt = $data['broadcast_started_at'] ?? 0;
            if ($startedAt > 0 && (time() - $startedAt) > BROADCAST_TIMEOUT) {
                $deleteChatId = $row['chat_id'];
                $deleteStmt = $conn->prepare("DELETE FROM admin_states WHERE chat_id = ?");
                if (!$deleteStmt) continue;
                $deleteStmt->bind_param("s", $deleteChatId);
                $deleteStmt->execute();
                $deleteStmt->close();
                logMessage("Auto-cleaned expired broadcast state for admin: $deleteChatId");
                sendSimpleMessage($deleteChatId, "⏰ <b>Mode broadcast telah expired!</b>\n\nAnda tidak mengirim konten dalam 5 menit.\nMode broadcast otomatis dibatalkan.\n\nGunakan /pengumuman atau /adds untuk memulai lagi.");
            }
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        logMessage("Error in cleanupExpiredBroadcastStates: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        if ($conn) $conn->close();
    }
}

// Run cleanup when config is loaded
cleanupExpiredOrders();
cleanupExpiredBroadcastStates();

?>