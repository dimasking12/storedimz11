<?php
// api/admin.php
header('Content-Type: application/json');
require_once '../config_web.php';

if (!isWebAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRF($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_product') {
    $game = $_POST['game_type'] ?? '';
    $name = $_POST['name'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $price = $_POST['price'] ?? '';
    $points = $_POST['points_reward'] ?? '';
    $delivery = $_POST['delivery_type'] ?? 'auto';

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO products (game_type, name, duration, price, points_reward, delivery_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiis", $game, $name, $duration, $price, $points, $delivery);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $conn->close();
}

elseif ($action === 'fulfill_order') {
    $order_id = $_POST['order_id'] ?? '';
    $licence = $_POST['licence'] ?? '';

    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE web_pending_orders SET licence = ?, needs_fulfillment = 0 WHERE order_id = ?");
    $stmt->bind_param("ss", $licence, $order_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $conn->close();
}

elseif ($action === 'delete_product') {
    $id = $_POST['id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    $conn->close();
}

elseif ($action === 'add_voucher') {
    $code = strtoupper($_POST['code'] ?? '');
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $max_uses = intval($_POST['max_uses'] ?? 1);

    if (empty($code) || $discount_percent <= 0 || $max_uses <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid voucher data']);
        exit;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO vouchers (code, discount_percent, max_uses) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $code, $discount_percent, $max_uses);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Voucher code already exists']);
    }
    $conn->close();
}

elseif ($action === 'get_vouchers') {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM vouchers ORDER BY created_at DESC");
    $vouchers = [];
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
    echo json_encode($vouchers);
    $conn->close();
}

elseif ($action === 'get_point_settings') {
    $rules = getPointRules();
    echo json_encode($rules);
}

elseif ($action === 'save_point_settings') {
    $rules = $_POST['rules'] ?? '';
    if (setWebSetting('extend_point_rules', $rules)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

elseif ($action === 'delete_voucher') {
    $id = $_POST['id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    $conn->close();
}

elseif ($action === 'add_news') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $type = $_POST['type'] ?? 'info';
    $imageUrl = null;
    $fileUrl = null;
    $fileName = null;

    // Handle Image Upload
    $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedImages)) {
            $newName = 'news_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $uploadPath = '../img/news/' . $newName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imageUrl = 'img/news/' . $newName;
            }
        }
    }

    // Handle File Upload
    $allowedFiles = ['zip', 'rar', 'pdf', 'txt'];
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $originalName = $_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedFiles)) {
            $newName = 'file_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $uploadPath = '../uploads/news/' . $newName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath)) {
                $fileUrl = 'uploads/news/' . $newName;
                $fileName = $originalName;
            }
        }
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO web_news (title, content, type, image_url, file_url, file_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $content, $type, $imageUrl, $fileUrl, $fileName);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'News posted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $conn->close();
}

elseif ($action === 'update_user_password') {
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (!$user_id || !$new_password) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }
    
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE web_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $conn->close();
}

elseif ($action === 'delete_news') {
    $id = $_POST['id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM web_news WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    $conn->close();
}

elseif ($action === 'add_rental') {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $duration = $_POST['duration'] ?? '';
    $imageUrl = null;

    $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedImages)) {
            $newName = 'rental_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../img/news/' . $newName)) {
                $imageUrl = 'img/news/' . $newName;
            }
        }
    }

    $email = $_POST['email'] ?? '';
    $backupCodes = $_POST['backup_codes'] ?? '';

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO web_rentals (title, description, price, duration, image_url, email, backup_codes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissss", $title, $desc, $price, $duration, $imageUrl, $email, $backupCodes);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $conn->close();
}

elseif ($action === 'delete_rental') {
    $id = $_POST['id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM web_rentals WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    $conn->close();
}

elseif ($action === 'delete_user') {
    $id = $_POST['id'] ?? '';
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM web_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    }
    $conn->close();
}

// ===== NOTIFICATION SETTINGS =====
elseif ($action === 'get_notif_settings') {
    echo json_encode([
        'success'         => true,
        'notif_bot_token' => getWebSetting('notif_bot_token', ''),
        'notif_chat_id'   => getWebSetting('notif_chat_id', ''),
    ]);
}

elseif ($action === 'save_notif_settings') {
    $token  = trim($_POST['notif_bot_token'] ?? '');
    $chatId = trim($_POST['notif_chat_id'] ?? '');

    if (empty($token) || empty($chatId)) {
        echo json_encode(['success' => false, 'message' => 'Token dan Chat ID tidak boleh kosong!']);
        exit;
    }

    setWebSetting('notif_bot_token', $token);
    setWebSetting('notif_chat_id', $chatId);
    echo json_encode(['success' => true, 'message' => 'Pengaturan notifikasi berhasil disimpan!']);
}

elseif ($action === 'test_notif') {
    $token  = trim($_POST['notif_bot_token'] ?? '');
    $chatId = trim($_POST['notif_chat_id'] ?? '');

    if (empty($token) || empty($chatId)) {
        echo json_encode(['success' => false, 'message' => 'Masukkan Token dan Chat ID terlebih dahulu!']);
        exit;
    }

    $text = "✅ <b>Test Notifikasi DIMZSTORE</b>\n\n"
          . "🎉 Koneksi berhasil!\n"
          . "Bot Token & Chat ID valid.\n\n"
          . "Notifikasi order sukses akan masuk ke sini setiap ada pembelian. 🚀";

    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = http_build_query([
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 8,
    ]]);
    $result  = @file_get_contents($url, false, $ctx);
    $decoded = $result ? json_decode($result, true) : null;

    if ($decoded && isset($decoded['ok']) && $decoded['ok'] === true) {
        echo json_encode(['success' => true, 'message' => 'Pesan test berhasil dikirim! Cek Telegram kamu.']);
    } else {
        $errMsg = $decoded['description'] ?? 'Gagal mengirim pesan. Periksa token & chat ID.';
        echo json_encode(['success' => false, 'message' => $errMsg]);
    }
}

elseif ($action === 'get_revenue_stats') {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate   = $_GET['end_date'] ?? date('Y-m-d');
    
    $conn = getDBConnection();
    
    // Total Revenue & Sales (Combined)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_revenue, COUNT(*) as total_sales FROM (
        SELECT amount, created_at FROM web_pending_orders WHERE status = 'completed'
        UNION ALL
        SELECT amount, created_at FROM web_rental_orders WHERE status = 'completed'
    ) AS comb
    WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Daily Stats for Graph (Combined)
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COALESCE(SUM(amount), 0) as daily_revenue, COUNT(*) as daily_sales FROM (
        SELECT amount, created_at FROM web_pending_orders WHERE status = 'completed'
        UNION ALL
        SELECT amount, created_at FROM web_rental_orders WHERE status = 'completed'
    ) AS comb
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $dailyResult = $stmt->get_result();
    $daily = [];
    while ($row = $dailyResult->fetch_assoc()) { $daily[] = $row; }
    $stmt->close();
    
    // Game Breakdown for Pie Chart (Combined)
    $stmt = $conn->prepare("SELECT game_type, COALESCE(SUM(amount), 0) as revenue FROM (
        SELECT game_type, amount, created_at FROM web_pending_orders WHERE status = 'completed'
        UNION ALL
        SELECT 'RENTAL' as game_type, amount, created_at FROM web_rental_orders WHERE status = 'completed'
    ) AS comb
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY game_type");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $gameResult = $stmt->get_result();
    $games = [];
    while ($row = $gameResult->fetch_assoc()) { $games[] = $row; }
    $stmt->close();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_revenue' => (int)($summary['total_revenue'] ?? 0),
            'total_sales' => (int)($summary['total_sales'] ?? 0)
        ],
        'daily' => $daily,
        'games' => $games
    ]);
}
?>
