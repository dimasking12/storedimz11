<?php
// api/store.php
header('Content-Type: application/json');
require_once '../config_web.php';

// ===== WEB DEBUG LOGGER =====
function webLog($msg) {
    $logFile = __DIR__ . '/../../web_store_debug.log';
    $ts = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$ts] $msg\n", FILE_APPEND | LOCK_EX);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CSRF Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create_order', 'create_extend_order', 'redeem_points', 'create_rental_order', 'validate_voucher', 'toggle_like_news'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRF($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// MAINTENANCE CHECK (23:50 - 00:15 WIB)
function isPaymentMaintenance() {
    $now = date('H:i');
    return ($now >= '23:50' || $now <= '00:15');
}

// SAFER MIGRATION
$conn = getDBConnection();
if ($conn) {
    // news table
    $res = $conn->query("SHOW TABLES LIKE 'web_news'");
    if ($res && $res->num_rows > 0) {
        $cols = $conn->query("SHOW COLUMNS FROM web_news");
        $existing = []; while($c = $cols->fetch_assoc()) $existing[] = $c['Field'];
        if(!in_array('image_url', $existing)) @$conn->query("ALTER TABLE web_news ADD image_url VARCHAR(255)");
        if(!in_array('file_url', $existing)) @$conn->query("ALTER TABLE web_news ADD file_url VARCHAR(255)");
        if(!in_array('file_name', $existing)) @$conn->query("ALTER TABLE web_news ADD file_name VARCHAR(255)");
    }

    // Rental Tables
    @$conn->query("CREATE TABLE IF NOT EXISTS web_rentals (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, price INT NOT NULL, duration VARCHAR(50), image_url VARCHAR(255), email VARCHAR(255), backup_codes TEXT, status ENUM('available', 'sold') DEFAULT 'available', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    
    // News Likes Table
    @$conn->query("CREATE TABLE IF NOT EXISTS web_news_likes (id INT AUTO_INCREMENT PRIMARY KEY, news_id INT NOT NULL, user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_like (news_id, user_id))");

    // Column checks for web_rentals
    $colsRNTL = $conn->query("SHOW COLUMNS FROM web_rentals");
    $existingRNTL = []; while($c = $colsRNTL->fetch_assoc()) $existingRNTL[] = $c['Field'];
    if(!in_array('email', $existingRNTL)) @$conn->query("ALTER TABLE web_rentals ADD email VARCHAR(255)");
    if(!in_array('backup_codes', $existingRNTL)) @$conn->query("ALTER TABLE web_rentals ADD backup_codes TEXT");

    @$conn->query("CREATE TABLE IF NOT EXISTS web_rental_orders (id INT AUTO_INCREMENT PRIMARY KEY, order_id VARCHAR(50) UNIQUE, user_id INT, rental_id INT, amount INT, status ENUM('pending', 'completed', 'expired') DEFAULT 'pending', qris_url TEXT, kode_deposit VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->close();
}

if ($action === 'validate_voucher') {
    $code = strtoupper($_POST['code'] ?? '');
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT discount_percent, max_uses, current_uses FROM vouchers WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['current_uses'] < $row['max_uses']) {
            echo json_encode(['success' => true, 'discount' => $row['discount_percent']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Voucher sudah mencapai batas penggunaan.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Kode voucher tidak valid.']);
    }
    $conn->close();
}

elseif ($action === 'get_products') {
    $game = $_GET['game'] ?? '';
    $conn = getDBConnection();
    
    if ($game) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE game_type = ? AND status = 1");
        $stmt->bind_param("s", $game);
    } else {
        $stmt = $conn->prepare("SELECT * FROM products WHERE status = 1");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products);
    $conn->close();
}

elseif ($action === 'create_order') {
    if (!isset($_SESSION['user_id'])) { webLog("create_order: no session"); exit; }

    if (isPaymentMaintenance()) {
        echo json_encode(['success' => false, 'message' => 'Sistem pembayaran ditutup sementara (23:50 - 00:15 WIB) untuk proses rekap data. Silakan coba lagi nanti.']);
        exit;
    }

    $productId   = $_POST['product_id'] ?? '';
    $type        = $_POST['type'] ?? 'random';
    $licenceInput= $_POST['licence'] ?? '';
    $deliveryType= $_POST['delivery_type'] ?? 'auto';

    webLog("create_order START: productId=$productId type=$type delivery=$deliveryType user={$_SESSION['user_id']}");

    $conn = getDBConnection();
    if (!$conn) {
        webLog("create_order: DB connection failed");
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    if (!$stmt) { webLog("create_order: prepare SELECT products failed: ".$conn->error); echo json_encode(['success'=>false,'message'=>'DB prepare error']); exit; }
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        webLog("create_order: product not found id=$productId");
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    webLog("create_order: product found: name={$product['name']} game={$product['game_type']} duration={$product['duration']} price={$product['price']}");

    if ($deliveryType === 'manual' && !empty($licenceInput)) {
        $gameTable = ($product['game_type'] === 'ff') ? 'freefire' : 'ffmax';
        if (isLicenceExists($licenceInput, $gameTable)) {
            echo json_encode(['success' => false, 'message' => 'License already exists! Please use a unique key.']);
            exit;
        }
    }

    $orderId = generateWebOrderID();
    $amount  = $product['price'];

    $voucherCode = strtoupper($_POST['voucher_code'] ?? '');
    if (!empty($voucherCode)) {
        $stmtV = $conn->prepare("SELECT id, discount_percent, max_uses, current_uses FROM vouchers WHERE code = ?");
        $stmtV->bind_param("s", $voucherCode);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        if ($rowV = $resV->fetch_assoc()) {
            if ($rowV['current_uses'] < $rowV['max_uses']) {
                $discountAmount = ($amount * $rowV['discount_percent']) / 100;
                $amount = $amount - $discountAmount;
                $stmtU = $conn->prepare("UPDATE vouchers SET current_uses = current_uses + 1 WHERE id = ?");
                $stmtU->bind_param("i", $rowV['id']);
                $stmtU->execute();
                $stmtU->close();
                webLog("create_order: voucher $voucherCode applied, discount={$rowV['discount_percent']}%, amount=$amount");
            }
        }
        $stmtV->close();
    }

    webLog("create_order: calling createPayment orderId=$orderId amount=$amount");
    $payment = createPayment($orderId, $amount);
    webLog("create_order: payment response=".json_encode($payment));

    if ($payment && $payment['status']) {
        $paymentData  = $payment['data'];
        $expiredAt    = date('Y-m-d H:i:s', strtotime('+6 hours'));
        $userId       = $_SESSION['user_id'];
        $needsFulFill = ($deliveryType === 'manual') ? 1 : 0;
        $pointsEarned = intval($product['points_reward'] ?? 0);
        $amount       = intval($amount);
        $gameDuration = (string)$product['duration'];

        webLog("create_order: saving to DB - orderId=$orderId userId=$userId game={$product['game_type']} dur=$gameDuration amount=$amount deposit={$paymentData['kode_deposit']} type=$type needs=$needsFulFill pts=$pointsEarned");

        $sql = "INSERT INTO web_pending_orders (order_id, user_id, game_type, duration, amount, deposit_code, key_type, licence, qr_url, expired_at, needs_fulfillment, points_earned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            webLog("create_order: prepare INSERT failed: ".$conn->error);
            echo json_encode(['success' => false, 'message' => 'DB prepare insert error: '.$conn->error]);
            exit;
        }
        // s=orderId i=userId s=game_type s=duration i=amount s=deposit s=key_type s=licence s=qr_url s=expired_at i=needs i=points
        $fmt = implode('', ['s','i','s','s','i','s','s','s','s','s','i','i']); // 12 params
        $stmt->bind_param($fmt, $orderId, $userId, $product['game_type'], $gameDuration, $amount, $paymentData['kode_deposit'], $type, $licenceInput, $paymentData['link_qr'], $expiredAt, $needsFulFill, $pointsEarned);

        if ($stmt->execute()) {
            webLog("create_order: INSERT SUCCESS orderId=$orderId");
            echo json_encode(['success' => true, 'order_id' => $orderId, 'qr_url' => $paymentData['link_qr'], 'amount' => $amount]);
        } else {
            webLog("create_order: INSERT FAILED: ".$stmt->error." | conn error: ".$conn->error);
            echo json_encode(['success' => false, 'message' => 'DB insert error: '.$stmt->error]);
        }
        $stmt->close();
    } else {
        webLog("create_order: payment gateway FAILED, response=".json_encode($payment));
        echo json_encode(['success' => false, 'message' => 'Payment gateway error']);
    }
    $conn->close();
}

elseif ($action === 'create_extend_order') {
    if (!isset($_SESSION['user_id'])) { webLog("create_extend: no session"); exit; }

    if (isPaymentMaintenance()) {
        echo json_encode(['success' => false, 'message' => 'Sistem pembayaran ditutup sementara (23:50 - 00:15 WIB) untuk proses rekap data. Silakan coba lagi nanti.']);
        exit;
    }

    $licence  = trim($_POST['licence']   ?? '');
    $gameType = trim($_POST['game_type'] ?? '');
    $duration = trim($_POST['duration']  ?? '');

    webLog("create_extend START: licence=$licence game=$gameType duration=$duration user={$_SESSION['user_id']}");

    if (empty($licence) || empty($gameType) || empty($duration)) {
        webLog("create_extend: missing params");
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap (licence/game_type/duration kosong)']);
        exit;
    }

    $conn = getDBConnection();
    if (!$conn) { webLog("create_extend: DB failed"); echo json_encode(['success'=>false,'message'=>'DB error']); exit; }

    // Ambil harga dari produk DB berdasarkan game_type & duration
    $stmtP = $conn->prepare("SELECT * FROM products WHERE game_type = ? AND duration = ? AND status = 1 LIMIT 1");
    if (!$stmtP) { webLog("create_extend: prepare products failed: ".$conn->error); echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
    $stmtP->bind_param("ss", $gameType, $duration);
    $stmtP->execute();
    $product = $stmtP->get_result()->fetch_assoc();
    $stmtP->close();

    webLog("create_extend: product lookup result: ".json_encode($product));

    if (!$product) {
        webLog("create_extend: product NOT found for game=$gameType dur=$duration");
        echo json_encode(['success' => false, 'message' => "Produk tidak ditemukan (game=$gameType, duration=$duration)"]);
        $conn->close(); exit;
    }

    $amount = $product['price'];
    webLog("create_extend: amount=$amount");
    // Verify license exists
    $userLic = getUserByLicence($licence, $gameType);
    if (!$userLic) {
        webLog("create_extend: licence NOT found $licence in $gameType");
        echo json_encode(['success' => false, 'message' => 'Licence not found']);
        $conn->close();
        exit;
    }

    $orderId = 'EX' . date('YmdHis') . rand(100, 999);
    
    $voucherCode = strtoupper($_POST['voucher_code'] ?? '');
    if (!empty($voucherCode)) {
        $stmtV = $conn->prepare("SELECT id, discount_percent, max_uses, current_uses FROM vouchers WHERE code = ?");
        $stmtV->bind_param("s", $voucherCode);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        if ($rowV = $resV->fetch_assoc()) {
            if ($rowV['current_uses'] < $rowV['max_uses']) {
                $discountAmount = ($amount * $rowV['discount_percent']) / 100;
                $amount = $amount - $discountAmount;
                $stmtU = $conn->prepare("UPDATE vouchers SET current_uses = current_uses + 1 WHERE id = ?");
                $stmtU->bind_param("i", $rowV['id']);
                $stmtU->execute();
                $stmtU->close();
                webLog("create_extend: voucher applied $voucherCode, new amount $amount");
            }
        }
        $stmtV->close();
    }

    // Create payment via Arie API
    webLog("create_extend: calling createPayment for $orderId, amount $amount");
    $payment = createPayment($orderId, $amount);
    webLog("create_extend: payment response: ".json_encode($payment));
    
    if ($payment && $payment['status']) {
        $paymentData = $payment['data'];
        $expiredAt = date('Y-m-d H:i:s', strtotime('+6 hours'));
        
        // Save to web_pending_orders
        $pointsEarned = intval(calculatePointsForDuration($duration));
        $amountFinal = intval($amount);
        $stmt = $conn->prepare("INSERT INTO web_pending_orders (order_id, user_id, game_type, duration, amount, deposit_code, key_type, licence, qr_url, expired_at, needs_fulfillment, points_earned) VALUES (?, ?, ?, ?, ?, ?, 'extend', ?, ?, ?, 0, ?)");
        $userId = $_SESSION['user_id'];
        // Build format string programmatically
        // s=orderId, i=userId, s=game_type, s=duration, i=amount, s=deposit_code, s=licence, s=qr_url, s=expired_at, i=points_earned
        $fmtEx = implode('', ['s','i','s','s','i','s','s','s','s','i']); // 10 chars
        $stmt->bind_param($fmtEx, $orderId, $userId, $gameType, $duration, $amountFinal, $paymentData['kode_deposit'], $licence, $paymentData['link_qr'], $expiredAt, $pointsEarned);
        
        if ($stmt->execute()) {
            webLog("create_extend: order saved $orderId");
            echo json_encode([
                'success' => true, 
                'order_id' => $orderId, 
                'qr_url' => $paymentData['link_qr'],
                'amount' => $amountFinal
            ]);
        } else {
            webLog("create_extend: save failed: ".$stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to save order: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment gateway error']);
    }
    $conn->close();
}
elseif ($action === 'check_status') {
    $orderId = $_GET['order_id'] ?? '';
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT * FROM web_pending_orders WHERE order_id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['status' => 'not_found']);
    exit;
}

if ($order['status'] === 'completed') {
    $res = ['status' => 'paid', 'licence' => $order['licence']];
    if ($order['needs_fulfillment'] == 1) $res['licence'] = 'PENDING_ADMIN';
    echo json_encode($res);
    exit;
}

// Check with gateway if still pending
$paymentStatus = checkPaymentStatus($order['deposit_code']);

if ($paymentStatus) {
    // Success! 
    $licence = $order['licence'];
    $duration = $order['duration'];
    $gameType = $order['game_type'];
    $table = ($gameType === 'ff') ? 'freefire' : 'ffmax';
    $userId = $order['user_id'];

    $success = false;
    
    if ($order['needs_fulfillment'] == 1 && !empty($order['licence']) && $order['licence'] !== 'PENDING_ADMIN') {
        // Automatically register the user's MANUAL INPUT key to game database
        if (saveLicenseToDatabase($table, $order['licence'], $duration, MERCHANT_CODE)) {
            $success = true;
            $licence = $order['licence'];
        }
    } else {
        // Automatic License Delivery (Random Key) or Extend
        if ($order['key_type'] === 'extend') {
            if (extendUserLicense($licence, $duration, $gameType)) $success = true;
        } else {
            $credentials = generateRandomCredentials();
            $licence = $credentials['licence'];
            if (saveLicenseToDatabase($table, $licence, $duration, MERCHANT_CODE)) $success = true;
        }
    }

    // Always consider it a success for points if payment is confirmed (Manual Admin Fulfillment)
    if ($order['needs_fulfillment'] == 1 && empty($success)) {
        // For admin fulfillment, we mark as completed even if license is still PENDING_ADMIN
        // This ensures the order shows up as "Paid/Pending Fulfill" and awards points
        $success = true; 
    }

    if ($success) {
        // Update order and points
        $stmt = $conn->prepare("UPDATE web_pending_orders SET status = 'completed', licence = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $licence, $orderId);
        $stmt->execute();
        
        // Points Reward (Use stored value)
        $pointsReward = isset($order['points_earned']) ? $order['points_earned'] : calculatePointsForDuration($duration);
        $stmt = $conn->prepare("UPDATE web_users SET points = points + ? WHERE id = ?");
        $stmt->bind_param("ii", $pointsReward, $userId);
        $stmt->execute();

        // === TELEGRAM NOTIFICATION TO ADMIN ===
        $userStmt = $conn->prepare("SELECT username FROM web_users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
        $buyerUsername = $userRow['username'] ?? "User #$userId";

        $orderNotifData = [
            'order_id'        => $orderId,
            'game_type'       => $gameType,
            'duration'        => $duration,
            'amount'          => $order['amount'],
            'key_type'        => $order['key_type'],
            'needs_fulfillment' => $order['needs_fulfillment'],
        ];
        sendOrderNotificationToTelegram($orderNotifData, $buyerUsername, $licence);
        // ======================================

        echo json_encode(['status' => 'paid', 'licence' => $licence]);
    } else {
        echo json_encode(['status' => 'pending']);
    }
}
 else {
        // Check if expired
        if (strtotime($order['expired_at']) < time()) {
            $stmt = $conn->prepare("UPDATE web_pending_orders SET status = 'expired' WHERE order_id = ?");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            echo json_encode(['status' => 'expired']);
        } else {
            echo json_encode(['status' => 'pending']);
        }
    }
    $conn->close();
}
elseif ($action === 'get_history') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $userId   = $_SESSION['user_id'];
    $filter   = $_GET['filter'] ?? 'all';
    $conn     = getDBConnection();

    $sql = "SELECT order_id, game_type, duration, amount, key_type, licence, qr_url, status, created_at, expired_at
            FROM web_pending_orders WHERE user_id = ?";
    $params = [$userId];
    $types  = "i";

    if ($filter !== 'all') {
        $sql    .= " AND status = ?";
        $params[] = $filter;
        $types  .= "s";
    }

    $sql .= " ORDER BY created_at DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    $conn->close();
    echo json_encode($orders);
}

elseif ($action === 'get_all_history') {
    if (!isWebAdmin()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $conn = getDBConnection();
    // Combined History (Licenses + Rentals)
    $sql = "(SELECT h.order_id, h.game_type, h.duration, h.amount, h.status, h.licence, h.created_at, u.username, h.needs_fulfillment, 'license' as tx_type
             FROM web_pending_orders h 
             JOIN web_users u ON h.user_id = u.id)
            UNION ALL
            (SELECT o.order_id, 'RENTAL' as game_type, r.duration, o.amount, o.status, r.title as licence, o.created_at, u.username, 0 as needs_fulfillment, 'rental' as tx_type
             FROM web_rental_orders o
             JOIN web_users u ON o.user_id = u.id
             JOIN web_rentals r ON o.rental_id = r.id)
            ORDER BY created_at DESC LIMIT 300";
            
    $result = $conn->query($sql);
    $history = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    echo json_encode($history);
    $conn->close();
}

elseif ($action === 'get_news') {
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'] ?? 0;
    
    $sql = "SELECT n.*, 
            (SELECT COUNT(*) FROM web_news_likes WHERE news_id = n.id) as likes_count,
            (SELECT COUNT(*) FROM web_news_likes WHERE news_id = n.id AND user_id = ?) as user_liked
            FROM web_news n 
            ORDER BY n.created_at DESC LIMIT 15";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $news = [];
    while ($row = $result->fetch_assoc()) {
        $row['user_liked'] = (bool)$row['user_liked'];
        $news[] = $row;
    }
    echo json_encode($news);
    $conn->close();
}

elseif ($action === 'toggle_like_news') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Silakan login untuk memberikan like.']);
        exit;
    }
    
    $newsId = intval($_POST['news_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if already liked
    $check = $conn->prepare("SELECT id FROM web_news_likes WHERE news_id = ? AND user_id = ?");
    if (!$check) {
        echo json_encode(['success' => false, 'message' => 'Tabel likes belum ada. Silakan jalankan SQL yang diberikan.']);
        $conn->close(); exit;
    }
    
    $check->bind_param("ii", $newsId, $userId);
    $check->execute();
    $res = $check->get_result();
    
    $liked = false;
    if ($res->num_rows > 0) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM web_news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $newsId, $userId);
        $stmt->execute();
        $liked = false;
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO web_news_likes (news_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $newsId, $userId);
        $stmt->execute();
        $liked = true;
    }
    
    // Get new count
    $countRes = $conn->prepare("SELECT COUNT(*) as count FROM web_news_likes WHERE news_id = ?");
    $countRes->bind_param("i", $newsId);
    $countRes->execute();
    $newCount = $countRes->get_result()->fetch_assoc()['count'];
    
    // Sync back to web_news table
    $upd = $conn->prepare("UPDATE web_news SET likes_count = ? WHERE id = ?");
    $upd->bind_param("ii", $newCount, $newsId);
    $upd->execute();
    
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $newCount]);
    $conn->close();
}

elseif ($action === 'get_users') {
    if (!isWebAdmin()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $conn = getDBConnection();
    $result = $conn->query("SELECT id, username, wa_number, telegram_user, points, role, created_at FROM web_users ORDER BY id DESC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
    $conn->close();
}

elseif ($action === 'get_active_licenses') {
    if (!isWebAdmin()) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $conn = getDBConnection();
    // Fetch only completed licenses with user info
    $result = $conn->query("SELECT h.licence, h.duration, h.created_at, u.username 
                            FROM web_pending_orders h 
                            JOIN web_users u ON h.user_id = u.id 
                            WHERE h.status = 'completed' AND h.licence IS NOT NULL AND h.licence != ''
                            ORDER BY h.created_at DESC");
    $licenses = [];
    while ($row = $result->fetch_assoc()) {
        $licenses[] = $row;
    }
    echo json_encode($licenses);
    $conn->close();
}

elseif ($action === 'redeem_points') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Login dulu!']);
        exit;
    }

    $gameType = $_POST['game_type'] ?? '';
    $days     = intval($_POST['days'] ?? 0);

    // Validate game
    if (!in_array($gameType, ['ff', 'ffmax'])) {
        echo json_encode(['success' => false, 'message' => 'Game tidak valid!']);
        exit;
    }

    // Cost map: 1d=12, 3d=36, 7d=84
    $costMap = [1 => 12, 3 => 36, 7 => 84];
    if (!array_key_exists($days, $costMap)) {
        echo json_encode(['success' => false, 'message' => 'Durasi tidak valid!']);
        exit;
    }

    $cost   = $costMap[$days];
    $userId = $_SESSION['user_id'];
    $conn   = getDBConnection();

    // Get fresh balance with lock
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT points, username FROM web_users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$userRow) {
        $conn->rollback(); $conn->close();
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan!']);
        exit;
    }

    $currentBalance = intval($userRow['points']);
    $username       = $userRow['username'];

    if ($currentBalance < $cost) {
        $conn->rollback(); $conn->close();
        echo json_encode(['success' => false, 'message' => "Balance tidak cukup! Kamu punya {$currentBalance}, butuh {$cost}."]);
        exit;
    }

    // Generate unique licence: tukar + 3 chars (letters+digits)
    $chars   = 'abcdefghijklmnopqrstuvwxyz0123456789';
    do {
        $suffix  = '';
        for ($i = 0; $i < 5; $i++) $suffix .= $chars[rand(0, strlen($chars) - 1)];
        $licence = 'tukar' . $suffix;
        $table   = ($gameType === 'ff') ? 'freefire' : 'ffmax';
    } while (isLicenceExists($licence, $table));

    // Save to game DB
    if (!saveLicenseToDatabase($table, $licence, $days, 'REDEEM_POINT')) {
        $conn->rollback(); $conn->close();
        echo json_encode(['success' => false, 'message' => 'Gagal generate licence. Coba lagi!']);
        exit;
    }

    // Deduct balance
    $newBalance = $currentBalance - $cost;
    $stmt = $conn->prepare("UPDATE web_users SET points = ? WHERE id = ?");
    $stmt->bind_param("ii", $newBalance, $userId);
    $stmt->execute();
    $stmt->close();

    // Record to web_pending_orders for history (amount=0 = free redeem)
    $orderId  = 'RD' . date('YmdHis') . rand(100, 999);
    $expiredAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
    $zeroAmt  = 0;
    $stmt = $conn->prepare("INSERT INTO web_pending_orders 
        (order_id, user_id, game_type, duration, amount, deposit_code, key_type, licence, qr_url, expired_at, needs_fulfillment, status)
        VALUES (?, ?, ?, ?, ?, '', 'redeem', ?, '', ?, 0, 'completed')");
    $stmt->bind_param("sisisss", $orderId, $userId, $gameType, $days, $zeroAmt, $licence, $expiredAt);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn->close();

    // Telegram notification
    $notifData = [
        'order_id'         => $orderId,
        'game_type'        => $gameType,
        'duration'         => $days,
        'amount'           => 0,
        'key_type'         => 'redeem',
        'needs_fulfillment' => 0,
    ];
    sendOrderNotificationToTelegram($notifData, $username,
        $licence . " (REDEEM {$cost} balance)");

    echo json_encode([
        'success'     => true,
        'licence'     => $licence,
        'new_balance' => $newBalance,
    ]);
}

elseif ($action === 'get_rentals') {
    // Check for completed rental orders to mark as sold automatically
    $conn = getDBConnection();
    $conn->query("UPDATE web_rentals r JOIN web_rental_orders o ON r.id = o.rental_id SET r.status = 'sold' WHERE o.status = 'completed'");
    
    $res = $conn->query("SELECT * FROM web_rentals ORDER BY created_at DESC");
    $data = [];
    while ($r = $res->fetch_assoc()) $data[] = $r;
    echo json_encode($data);
    $conn->close();
}

elseif ($action === 'create_rental_order') {
    if (!isset($_SESSION['user_id'])) exit;

    if (isPaymentMaintenance()) {
        echo json_encode(['success' => false, 'message' => 'Sistem pembayaran ditutup sementara (23:50 - 00:15 WIB) untuk proses rekap data. Silakan coba lagi nanti.']);
        exit;
    }

    $rental_id = intval($_POST['rental_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM web_rentals WHERE id = ? AND status = 'available'");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $rental = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$rental) {
        echo json_encode(['success' => false, 'message' => 'Akun tidak tersedia atau sudah disewa orang lain.']);
        $conn->close(); exit;
    }
    
    $order_id = "RNT" . date('YmdHis') . rand(100, 999);
    $amount = $rental['price'];
    
    // Create REAL payment via Arie API
    $payment = createPayment($order_id, $amount);
    
    if ($payment && $payment['status']) {
        $paymentData = $payment['data'];
        $qris_url = $paymentData['link_qr'];
        $kode_deposit = $paymentData['kode_deposit'];
        
        $stmt = $conn->prepare("INSERT INTO web_rental_orders (order_id, user_id, rental_id, amount, qris_url, kode_deposit) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisss", $order_id, $user_id, $rental_id, $amount, $qris_url, $kode_deposit);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'order_id' => $order_id, 'qris_url' => $qris_url, 'amount' => $amount]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment gateway error']);
    }
    $conn->close();
}

elseif ($action === 'check_rental_status') {
    $order_id = $_GET['order_id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT o.*, r.email, r.backup_codes, r.title, r.duration, u.username 
                            FROM web_rental_orders o 
                            JOIN web_rentals r ON o.rental_id = r.id 
                            JOIN web_users u ON o.user_id = u.id
                            WHERE o.order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        echo json_encode(['status' => 'not_found']);
    } elseif ($order['status'] === 'completed') {
        echo json_encode([
            'status' => 'paid', 
            'email' => $order['email'], 
            'backup_codes' => $order['backup_codes']
        ]);
    } else {
        // Check with gateway
        if (checkPaymentStatus($order['kode_deposit'])) {
            // Update order and mark rental as sold
            $conn->begin_transaction();
            $stmt = $conn->prepare("UPDATE web_rental_orders SET status = 'completed' WHERE order_id = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE web_rentals SET status = 'sold' WHERE id = ?");
            $stmt->bind_param("i", $order['rental_id']);
            $stmt->execute();
            
            $conn->commit();
            
            // === TELEGRAM NOTIFICATION TO ADMIN ===
            sendRentalNotificationToTelegram($order, $order['username']);
            // ======================================
            
            echo json_encode([
                'status' => 'paid', 
                'email' => $order['email'], 
                'backup_codes' => $order['backup_codes']
            ]);
        } else {
            echo json_encode(['status' => 'pending']);
        }
    }
    $conn->close();
}

elseif ($action === 'get_rental_history') {
    if (!isset($_SESSION['user_id'])) exit;
    $user_id = $_SESSION['user_id'];
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT o.*, r.title, r.duration, r.email, r.backup_codes 
                            FROM web_rental_orders o 
                            JOIN web_rentals r ON o.rental_id = r.id 
                            WHERE o.user_id = ? ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($r = $res->fetch_assoc()) {
        // Only show credentials if completed
        if ($r['status'] !== 'completed') {
            unset($r['email']);
            unset($r['backup_codes']);
        }
        $data[] = $r;
    }
    echo json_encode($data);
    $conn->close();
}

elseif ($action === 'get_all_rental_history') {
    if (!isWebAdmin()) exit;
    $conn = getDBConnection();
    $res = $conn->query("SELECT o.*, r.title, u.username FROM web_rental_orders o JOIN web_rentals r ON o.rental_id = r.id JOIN web_users u ON o.user_id = u.id ORDER BY o.created_at DESC");
    $data = [];
    while ($r = $res->fetch_assoc()) $data[] = $r;
    echo json_encode($data);
    $conn->close();
}
?>
