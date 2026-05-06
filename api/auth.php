<?php
// api/auth.php
header('Content-Type: application/json');
require_once '../config_web.php';

$action = $_POST['action'] ?? '';

// CSRF Validation for POST requests
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRF($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($action) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi!']);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal. Hubungi admin.']);
    exit;
}

if ($action === 'register') {
    $wa      = trim($_POST['wa_number'] ?? '');
    $telegram = trim($_POST['telegram_user'] ?? '');

    if (empty($wa) || empty($telegram)) {
        echo json_encode(['success' => false, 'message' => 'Nomor WA dan Username Telegram wajib diisi!']);
        exit;
    }

    if (strlen($username) < 4) {
        echo json_encode(['success' => false, 'message' => 'Username minimal 4 karakter!']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter!']);
        exit;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM web_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan, coba yang lain!']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO web_users (username, password, wa_number, telegram_user, role, points, created_at) VALUES (?, ?, ?, ?, 'user', 0, NOW())");
    $stmt->bind_param("ssss", $username, $hashedPassword, $wa, $telegram);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        echo json_encode(['success' => true, 'message' => 'Registrasi berhasil! Selamat datang, ' . htmlspecialchars($username) . '!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registrasi gagal: ' . $conn->error]);
    }
    $stmt->close();

} elseif ($action === 'login') {
    $stmt = $conn->prepare("SELECT id, username, password, role FROM web_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            echo json_encode([
                'success'  => true,
                'message'  => 'Selamat datang kembali, ' . htmlspecialchars($user['username']) . '!',
                'role'     => $user['role']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan!']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}

$conn->close();
?>
