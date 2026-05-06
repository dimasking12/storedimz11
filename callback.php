<?php
// callback.php - UPDATED with Web Integration
require_once 'config.php';

// Fungsi untuk mengeksekusi cronjob
function executeCronJob() {
    error_log("Cronjob started at " . date('Y-m-d H:i:s'));
    
    $conn = getDBConnection();
    
    // Cari order yang pending lebih dari 6 jam
    $timeLimit = date('Y-m-d H:i:s', strtotime('-6 hours'));
    $stmt = $conn->prepare("SELECT * FROM pending_orders WHERE status = 'pending' AND created_at < ?");
    $stmt->bind_param("s", $timeLimit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $processedCount = 0;
    $failedCount = 0;
    
    if ($result->num_rows > 0) {
        error_log("Found " . $result->num_rows . " pending orders to check");
        
        while ($order = $result->fetch_assoc()) {
            error_log("Checking order: " . $order['deposit_code']);
            
            // Cek status pembayaran untuk order yang lama
            $paymentStatus = checkPaymentStatus($order['deposit_code']);
            
            if ($paymentStatus) {
                // Payment successful
                if (processPayment($order, $conn)) {
                    $processedCount++;
                    error_log("Order " . $order['deposit_code'] . " processed successfully");
                } else {
                    $failedCount++;
                    error_log("Failed to process order: " . $order['deposit_code']);
                }
            } else {
                // Cek lebih detail di payment gateway jika ada
                if (function_exists('checkPaymentGateway')) {
                    $gatewayStatus = checkPaymentGateway($order['deposit_code']);
                    
                    if ($gatewayStatus === 'paid' || $gatewayStatus === 'success') {
                        if (processPayment($order, $conn)) {
                            $processedCount++;
                            error_log("Order " . $order['deposit_code'] . " processed via gateway check");
                        }
                    } elseif ($gatewayStatus === 'expired' || $gatewayStatus === 'failed' || $gatewayStatus === 'canceled') {
                        // Update status menjadi expired/failed
                        $stmt2 = $conn->prepare("UPDATE pending_orders SET status = ? WHERE deposit_code = ?");
                        $status = ($gatewayStatus === 'expired') ? 'expired' : 'failed';
                        $stmt2->bind_param("ss", $status, $order['deposit_code']);
                        $stmt2->execute();
                        $stmt2->close();
                        
                        // Kirim notifikasi ke user
                        $message = "❌ <b>PEMBAYARAN GAGAL</b>\n\n";
                        $message .= "Pembayaran untuk kode deposit <code>{$order['deposit_code']}</code> telah {$status}.\n\n";
                        $message .= "Silakan coba lagi dengan membuat order baru.";
                        
                        sendMessage($order['chat_id'], $message);
                        $failedCount++;
                        error_log("Order " . $order['deposit_code'] . " marked as " . $status);
                    }
                } else {
                    // Jika tidak ada fungsi checkPaymentGateway, cek manual dari database atau timeout
                    $orderTime = strtotime($order['created_at']);
                    $currentTime = time();
                    $diffMinutes = round(($currentTime - $orderTime) / 60);
                    
                    // Jika lebih dari 6 jam, mark as expired
                    if ($diffMinutes > 360) {
                        $stmt2 = $conn->prepare("UPDATE pending_orders SET status = 'failed' WHERE deposit_code = ?");
                        $stmt2->bind_param("s", $order['deposit_code']);
                        $stmt2->execute();
                        $stmt2->close();
                        
                        sendMessage($order['chat_id'], "❌ <b>PEMBAYARAN GAGAL</b>\n\nOrder dengan kode <code>{$order['deposit_code']}</code> telah gagal/kadaluarsa (lebih dari 6 jam).\n\nSilakan buat order baru.");
                        $failedCount++;
                        error_log("Order " . $order['deposit_code'] . " expired (older than 2 hours)");
                    }
                }
            }
        }
    }
    
    // ============ WEB INTEGRATION: Check Web Pending Orders ============
    $webProcessedCount = 0;
    $webFailedCount = 0;
    
    // Cek web_pending_orders yang pending lebih dari 6 jam
    $stmt = $conn->prepare("SELECT * FROM web_pending_orders WHERE status = 'pending' AND created_at < ?");
    $stmt->bind_param("s", $timeLimit);
    $stmt->execute();
    $webResult = $stmt->get_result();
    
    if ($webResult->num_rows > 0) {
        error_log("Found " . $webResult->num_rows . " web pending orders to check");
        
        while ($webOrder = $webResult->fetch_assoc()) {
            error_log("Checking web order: " . $webOrder['deposit_code']);
            
            // Cek status pembayaran
            $paymentStatus = checkPaymentStatus($webOrder['deposit_code']);
            
            if ($paymentStatus) {
                // Payment successful for web order
                if (processWebPayment($webOrder, $conn)) {
                    $webProcessedCount++;
                    error_log("Web order " . $webOrder['deposit_code'] . " processed successfully");
                } else {
                    $webFailedCount++;
                    error_log("Failed to process web order: " . $webOrder['deposit_code']);
                }
            } else {
                // Check if expired
                if (strtotime($webOrder['expired_at']) < time()) {
                    $stmt2 = $conn->prepare("UPDATE web_pending_orders SET status = 'failed' WHERE deposit_code = ?");
                    $stmt2->bind_param("s", $webOrder['deposit_code']);
                    $stmt2->execute();
                    $stmt2->close();
                    $webFailedCount++;
                    error_log("Web order " . $webOrder['deposit_code'] . " expired");
                }
            }
        }
    }

    // ============ RENTAL ORDERS: Check Expired (6 Hours) ============
    $stmt = $conn->prepare("SELECT * FROM web_rental_orders WHERE status = 'pending' AND created_at < ?");
    $stmt->bind_param("s", $timeLimit);
    $stmt->execute();
    $rentalResult = $stmt->get_result();
    if ($rentalResult->num_rows > 0) {
        while ($rentalOrder = $rentalResult->fetch_assoc()) {
            // Check status first
            if (!checkPaymentStatus($rentalOrder['kode_deposit'])) {
                $stmt2 = $conn->prepare("UPDATE web_rental_orders SET status = 'expired' WHERE order_id = ?");
                $stmt2->bind_param("s", $rentalOrder['order_id']);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    }
    
    // Bersihkan order yang sudah expired lebih dari 7 hari (opsional)
    $cleanupDate = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = $conn->prepare("DELETE FROM pending_orders WHERE (status = 'expired' OR status = 'failed') AND created_at < ?");
    $stmt->bind_param("s", $cleanupDate);
    $stmt->execute();
    $deletedCount = $stmt->affected_rows;
    
    // Bersihkan web_pending_orders yang sudah expired lebih dari 7 hari
    $stmt = $conn->prepare("DELETE FROM web_pending_orders WHERE (status = 'expired' OR status = 'failed' OR status = 'cancelled') AND created_at < ?");
    $stmt->bind_param("s", $cleanupDate);
    $stmt->execute();
    $webDeletedCount = $stmt->affected_rows;
    
    $stmt->close();
    $conn->close();
    
    error_log("Cronjob finished. Bot - Processed: {$processedCount}, Failed: {$failedCount}, Cleaned: {$deletedCount}");
    error_log("Cronjob finished. Web - Processed: {$webProcessedCount}, Failed: {$webFailedCount}, Cleaned: {$webDeletedCount}");
    
    // Return result untuk CLI output
    return [
        'timestamp' => date('Y-m-d H:i:s'),
        'bot' => [
            'processed' => $processedCount,
            'failed' => $failedCount,
            'cleaned' => $deletedCount
        ],
        'web' => [
            'processed' => $webProcessedCount,
            'failed' => $webFailedCount,
            'cleaned' => $webDeletedCount
        ]
    ];
}

// Fungsi untuk memproses pembayaran (BOT)
function processPayment($order, $conn) {
    try {
        // Generate credentials
        $credentials = generateRandomCredentials();
        $table = ($order['game_type'] == 'ff') ? 'freefire' : 'ffmax';
        
        // Save to game database
        if (saveToDatabase($table, $credentials['username'], $credentials['password'], $order['duration'], MERCHANT_CODE)) {
            
            // Send credentials to user
            $message = "🎉 <b>PEMBAYARAN BERHASIL!</b>\n\n";
            $message .= "Terima kasih telah membeli lisensi " . strtoupper($order['game_type']) . "\n";
            $message .= "Durasi: " . $order['duration'] . " Hari\n\n";
            $message .= "📱 <b>AKUN ANDA:</b>\n";
            $message .= "Username: <code>" . $credentials['username'] . "</code>\n";
            $message .= "Password: <code>" . $credentials['password'] . "</code>\n\n";
            $message .= "⏰ <b>MASA AKTIF:</b>\n";
            $message .= "Berlaku hingga: " . date('d-m-Y H:i', strtotime("+" . $order['duration'] . " days")) . " WIB\n\n";
            $message .= "Selamat bermain! 🎮";
            
            $sent = sendMessage($order['chat_id'], $message);
            
            // Update order status
            $stmt = $conn->prepare("UPDATE pending_orders SET status = 'completed' WHERE deposit_code = ?");
            $stmt->bind_param("s", $order['deposit_code']);
            $stmt->execute();
            $stmt->close();
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Error processing payment for order {$order['deposit_code']}: " . $e->getMessage());
    }
    
    return false;
}

// ============ WEB INTEGRATION: Process Web Payment ============
function processWebPayment($webOrder, $conn) {
    try {
        $licence = $webOrder['licence'];
        
        // If random key, generate licence
        if ($webOrder['key_type'] == 'random' && empty($licence)) {
            $licence = generateRandomLicence();
            $maxAttempts = 10;
            $attempts = 0;
            while (isLicenceExistsInGame($licence, $webOrder['game_type']) && $attempts < $maxAttempts) {
                $licence = generateRandomLicence();
                $attempts++;
            }
        }
        
        // Save to game database
        $table = ($webOrder['game_type'] == 'ff') ? 'freefire' : 'ffmax';
        $success = false;

        if ($webOrder['key_type'] == 'extend') {
            if (extendUserLicense($licence, $webOrder['duration'], $webOrder['game_type'])) {
                $success = true;
            }
        } else {
            if (saveLicenceToGame($licence, $webOrder['duration'], $webOrder['game_type'], MERCHANT_CODE)) {
                $success = true;
            }
        }
        
        if ($success) {
            $pointsEarned = isset($webOrder['points_earned']) ? $webOrder['points_earned'] : calculatePointsForDuration($webOrder['duration']);
            
            // Update user points in web_users
            $stmt = $conn->prepare("UPDATE web_users SET points = points + ? WHERE id = ?");
            $stmt->bind_param("ii", $pointsEarned, $webOrder['user_id']);
            $stmt->execute();
            $stmt->close();
            
            // Save point transaction
            $stmt = $conn->prepare("INSERT INTO web_point_transactions (user_id, points, type, reason) VALUES (?, ?, 'earn', ?)");
            $stmt->bind_param("iis", $webOrder['user_id'], $pointsEarned, "Pembelian {$webOrder['duration']} hari");
            $stmt->execute();
            $stmt->close();
            
            // Update web order status
            $stmt = $conn->prepare("UPDATE web_pending_orders SET status = 'completed', licence = ? WHERE deposit_code = ?");
            $stmt->bind_param("ss", $licence, $webOrder['deposit_code']);
            $stmt->execute();
            $stmt->close();
            
            // Save to web order history
            $stmt = $conn->prepare("INSERT INTO web_order_history (user_id, order_id, game_type, duration, amount, licence, points_earned, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
            $stmt->bind_param("issisii", $webOrder['user_id'], $webOrder['order_id'], $webOrder['game_type'], $webOrder['duration'], $webOrder['amount'], $licence, $pointsEarned);
            $stmt->execute();
            $stmt->close();
            
            error_log("Web payment processed - Order: {$webOrder['order_id']}, Licence: $licence, Points: $pointsEarned");
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Error processing web payment for order {$webOrder['deposit_code']}: " . $e->getMessage());
    }
    
    return false;
}

// Fungsi untuk cek licence exists di game
function isLicenceExistsInGame($licence, $gameType) {
    $conn = getDBConnection();
    if (!$conn) return false;
    $table = ($gameType == 'ff') ? 'freefire' : 'ffmax';
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE licence = ?");
    $stmt->bind_param("s", $licence);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return ($row['count'] > 0);
}

// Fungsi untuk generate random licence
function generateRandomLicence() {
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $licence = '';
    for ($i = 0; $i < 2; $i++) {
        $licence .= $letters[rand(0, strlen($letters) - 1)];
    }
    for ($i = 0; $i < 2; $i++) {
        $licence .= $numbers[rand(0, strlen($numbers) - 1)];
    }
    return $licence;
}

// Fungsi untuk save licence ke game database
function saveLicenceToGame($licence, $duration, $gameType, $reference) {
    $conn = getDBConnection();
    if (!$conn) return false;
    $table = ($gameType == 'ff') ? 'freefire' : 'ffmax';
    $expDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
    $expDays = intval($duration);
    $uuid = "";
    $status = 2;
    $gameId = ($gameType == 'ff') ? 1 : 2;
    
    $stmt = $conn->prepare("INSERT INTO $table (licence, uuid, expDate, expDays, status, game_id, reference, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssiiss", $licence, $uuid, $expDate, $expDays, $status, $gameId, $reference);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}


// ============ WEB API ENDPOINTS ============

// Handle web API requests (untuk create_order.php dan check_payment.php)
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    header('Content-Type: application/json');
    
    // API: Create Order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/api/create_order') !== false) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        // Process web order creation
        $result = createWebOrder($data);
        echo json_encode($result);
        exit;
    }
    
    // API: Check Payment
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/api/check_payment') !== false) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        $orderId = $params['order_id'] ?? '';
        
        if (empty($orderId)) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID required']);
            exit;
        }
        
        $result = checkWebPayment($orderId);
        echo json_encode($result);
        exit;
    }
}

// Fungsi untuk membuat web order
function createWebOrder($data) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Silakan login terlebih dahulu'];
    }
    
    $gameType = $data['game_type'];
    $duration = $data['duration'];
    $amount = $data['amount'];
    $keyType = $data['key_type'];
    $manualLicence = $data['manual_licence'] ?? null;
    
    $userId = $_SESSION['user_id'];
    $orderId = 'WEB' . time() . rand(100, 999);
    
    // Check manual licence if needed
    if ($keyType == 'manual') {
        if (empty($manualLicence)) {
            return ['success' => false, 'message' => 'Licence key tidak boleh kosong'];
        }
        
        if (isLicenceExistsInGame($manualLicence, $gameType)) {
            return ['success' => false, 'message' => 'Licence key sudah digunakan!'];
        }
    }
    
    // Create payment
    $payment = createPayment($orderId, $amount);
    
    if (!$payment || !$payment['status']) {
        return ['success' => false, 'message' => 'Gagal membuat pembayaran'];
    }
    
    $paymentData = $payment['data'];
    $expiredAt = date('Y-m-d H:i:s', strtotime('+25 minutes'));
    
    $conn = getDBConnection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    $stmt = $conn->prepare("INSERT INTO web_pending_orders (order_id, user_id, game_type, duration, amount, deposit_code, key_type, licence, qr_url, expired_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sisissssss", $orderId, $userId, $gameType, $duration, $amount, $paymentData['kode_deposit'], $keyType, $manualLicence, $paymentData['link_qr'], $expiredAt);
    
    if ($stmt->execute()) {
        $result = [
            'success' => true,
            'order_id' => $orderId,
            'qr_url' => $paymentData['link_qr'],
            'amount' => $amount,
            'deposit_code' => $paymentData['kode_deposit']
        ];
    } else {
        $result = ['success' => false, 'message' => 'Gagal menyimpan pesanan'];
    }
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Fungsi untuk cek web payment
function checkWebPayment($orderId) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        return ['status' => 'error', 'message' => 'Unauthorized'];
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Database connection failed'];
    }
    
    // Get order
    $stmt = $conn->prepare("SELECT * FROM web_pending_orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("si", $orderId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        $conn->close();
        return ['status' => 'not_found', 'message' => 'Order not found'];
    }
    
    // Check if expired
    if (strtotime($order['expired_at']) < time()) {
        $stmt = $conn->prepare("UPDATE web_pending_orders SET status = 'expired' WHERE order_id = ?");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return ['status' => 'expired', 'message' => 'Order expired'];
    }
    
    // Check payment status from API
    $paymentStatus = checkPaymentStatus($order['deposit_code']);
    
    if ($paymentStatus) {
        // Process payment
        if (processWebPayment($order, $conn)) {
            // Get updated order with licence
            $stmt = $conn->prepare("SELECT licence FROM web_pending_orders WHERE order_id = ?");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $updatedOrder = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
            
            return [
                'status' => 'paid',
                'licence' => $updatedOrder['licence']
            ];
        } else {
            $conn->close();
            return ['status' => 'error', 'message' => 'Failed to process payment'];
        }
    }
    
    $conn->close();
    return ['status' => 'pending', 'message' => 'Payment not detected yet'];
}

// Handle payment notification from payment gateway (CALLBACK ASLI)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log("Callback received: " . print_r($data, true));
    
    if ($data && isset($data['kode_deposit'])) {
        $depositCode = $data['kode_deposit'];
        
        // Check payment status
        $paymentStatus = checkPaymentStatus($depositCode);
        
        if ($paymentStatus) {
            // Payment successful
            $conn = getDBConnection();
            
            // Cek di pending_orders (BOT)
            $stmt = $conn->prepare("SELECT * FROM pending_orders WHERE deposit_code = ? AND status = 'pending'");
            $stmt->bind_param("s", $depositCode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $order = $result->fetch_assoc();
                processPayment($order, $conn);
                error_log("Callback processed BOT order: " . $depositCode);
            } else {
                // Cek di web_pending_orders (WEB)
                $stmt2 = $conn->prepare("SELECT * FROM web_pending_orders WHERE deposit_code = ? AND status = 'pending'");
                $stmt2->bind_param("s", $depositCode);
                $stmt2->execute();
                $webResult = $stmt2->get_result();
                
                if ($webResult->num_rows > 0) {
                    $webOrder = $webResult->fetch_assoc();
                    processWebPayment($webOrder, $conn);
                    error_log("Callback processed WEB order: " . $depositCode);
                } else {
                    error_log("Order not found or already processed: " . $depositCode);
                }
                $stmt2->close();
            }
            
            $stmt->close();
            $conn->close();
        } else {
            error_log("Payment status false for: " . $depositCode);
        }
    }
    
    http_response_code(200);
    echo "OK";
    exit;
}

// Handle CLI execution (untuk cronjob)
if (php_sapi_name() === 'cli') {
    // Ini diakses dari command line (cronjob)
    echo "=== CRONJOB EXECUTION ===\n";
    echo "Start time: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n\n";
    
    $result = executeCronJob();
    
    echo "Cronjob completed at: " . $result['timestamp'] . "\n";
    echo "\n--- BOT ORDERS ---\n";
    echo "Orders processed: " . $result['bot']['processed'] . "\n";
    echo "Orders failed: " . $result['bot']['failed'] . "\n";
    echo "Old records cleaned: " . $result['bot']['cleaned'] . "\n";
    echo "\n--- WEB ORDERS ---\n";
    echo "Orders processed: " . $result['web']['processed'] . "\n";
    echo "Orders failed: " . $result['web']['failed'] . "\n";
    echo "Old records cleaned: " . $result['web']['cleaned'] . "\n";
    echo "\n=== END CRONJOB ===\n";
    exit;
}

// Handle web access untuk testing (opsional)
if (isset($_GET['test_cron'])) {
    // Hanya untuk testing, bisa dihapus setelah setup
    echo "<pre>";
    echo "=== TEST CRONJOB ===\n";
    echo "Web access for testing only\n\n";
    
    $result = executeCronJob();
    
    echo "Cronjob completed at: " . $result['timestamp'] . "\n";
    echo "\n--- BOT ORDERS ---\n";
    echo "Orders processed: " . $result['bot']['processed'] . "\n";
    echo "Orders failed: " . $result['bot']['failed'] . "\n";
    echo "Old records cleaned: " . $result['bot']['cleaned'] . "\n";
    echo "\n--- WEB ORDERS ---\n";
    echo "Orders processed: " . $result['web']['processed'] . "\n";
    echo "Orders failed: " . $result['web']['failed'] . "\n";
    echo "Old records cleaned: " . $result['web']['cleaned'] . "\n";
    echo "</pre>";
    exit;
}

// Handle web access untuk test API
if (isset($_GET['test_api'])) {
    echo "<pre>";
    echo "=== API TEST MODE ===\n";
    echo "This endpoint is for web API calls\n";
    echo "Available endpoints:\n";
    echo "POST /api/create_order - Create new order\n";
    echo "GET /api/check_payment?order_id=xxx - Check payment status\n";
    echo "</pre>";
    exit;
}
?>