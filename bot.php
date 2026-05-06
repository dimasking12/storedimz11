<?php
// bot.php - Complete Telegram Bot with Fixed Point Redemption System and Broadcast Feature
// UPDATED: Status = 1 (active), UUID = NULL for new licenses and extends
require_once 'config.php';

// Log incoming request
$input = file_get_contents('php://input');
file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] Input: " . $input . "\n", FILE_APPEND);

$update = json_decode($input, true);

if (!$update) {
    logMessage("No update data received");
    exit;
}

// Extract basic info
$chatId = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? '');
$text = $update['message']['text'] ?? '';
$firstName = $update['message']['chat']['first_name'] ?? 'User';
$messageId = $update['message']['message_id'] ?? '';

logMessage("Processing - ChatID: $chatId, Text: $text, Name: $firstName");

/**
 * MAIN BOT FUNCTIONS
 */
function sendMessage($chatId, $text, $replyMarkup = null, $disableWebPagePreview = true) {
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => $disableWebPagePreview
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
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
    $result = file_get_contents($url, false, $context);
    
    logMessage("Sent message to $chatId: " . substr($text, 0, 100));
    return $result;
}

function sendMessageWithImage($chatId, $text, $replyMarkup = null) {
    $photoResult = sendPhoto($chatId, WELCOME_IMAGE, $text, $replyMarkup);
    $photoData = json_decode($photoResult, true);
    
    if ($photoData && $photoData['ok']) {
        return $photoResult;
    } else {
        logMessage("Failed to send photo, falling back to text message");
        return sendSimpleMessage($chatId, $text, $replyMarkup);
    }
}

/**
 * SMART MESSAGE EDITING - Detects if message contains photo or text
 */
function editMessageSmart($chatId, $messageId, $text, $replyMarkup = null) {
    $captionResult = editMessageCaption($chatId, $messageId, $text, $replyMarkup);
    $captionData = json_decode($captionResult, true);
    
    if ($captionData && $captionData['ok']) {
        logMessage("Successfully edited message caption - Chat: $chatId, Message: $messageId");
        return $captionResult;
    }
    
    $textResult = editMessageText($chatId, $messageId, $text, $replyMarkup);
    $textData = json_decode($textResult, true);
    
    if ($textData && $textData['ok']) {
        logMessage("Successfully edited message text - Chat: $chatId, Message: $messageId");
        return $textResult;
    }
    
    logMessage("Both edit methods failed, sending new message - Chat: $chatId, Message: $messageId");
    return sendMessageWithImage($chatId, $text, $replyMarkup);
}

function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result;
}

function editMessageCaption($chatId, $messageId, $caption, $replyMarkup = null) {
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageCaption";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result;
}

function deleteMessage($chatId, $messageId) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage?chat_id=$chatId&message_id=$messageId";
    $result = file_get_contents($url);
    logMessage("Deleted message $messageId from $chatId: " . $result);
    return $result;
}

function answerCallbackQuery($callbackId, $text = '') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";
    $data = ['callback_query_id' => $callbackId];
    
    if (!empty($text)) {
        $data['text'] = $text;
    }
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    logMessage("Answered callback query: " . $result);
    return $result;
}

function sendPhoto($chatId, $photoUrl, $caption = '', $replyMarkup = null, $scheduleDelete = true) {
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoUrl,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
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
    $result = file_get_contents($url, false, $context);
    
    $resultArray = json_decode($result, true);
    if ($resultArray && $resultArray['ok']) {
        $photoMessageId = $resultArray['result']['message_id'];
        logMessage("Photo sent to $chatId with message_id: $photoMessageId");
        
        if ($scheduleDelete) {
            scheduleAutoDelete($chatId, $photoMessageId, ORDER_TIMEOUT, 'pending');
            startRealTimePaymentCheck($chatId, $photoMessageId);
        }
    }
    
    return $result;
}

/**
 * REAL-TIME PAYMENT CHECKING FUNCTIONS
 */
function startRealTimePaymentCheck($chatId, $messageId) {
    $checkFile = __DIR__ . '/payment_checks.json';
    $checks = [];
    
    if (file_exists($checkFile)) {
        $checks = json_decode(file_get_contents($checkFile), true) ?? [];
    }
    
    $startTime = time();
    $endTime = $startTime + ORDER_TIMEOUT;
    
    $checks[] = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'last_check' => $startTime,
        'status' => 'active'
    ];
    
    file_put_contents($checkFile, json_encode($checks, JSON_PRETTY_PRINT));
    logMessage("Started real-time payment check for Chat: $chatId, Message: $messageId");
}

function processRealTimePaymentChecks() {
    $checkFile = __DIR__ . '/payment_checks.json';
    
    if (!file_exists($checkFile)) {
        return;
    }
    
    $checks = json_decode(file_get_contents($checkFile), true) ?? [];
    $currentTime = time();
    $activeChecks = [];
    
    foreach ($checks as $check) {
        if ($check['status'] !== 'active') {
            continue;
        }
        
        $chatId = $check['chat_id'];
        $messageId = $check['message_id'];
        
        if ($currentTime >= $check['end_time']) {
            logMessage("Payment check expired for Chat: $chatId, Message: $messageId");
            continue;
        }
        
        if ($currentTime >= ($check['last_check'] + PAYMENT_CHECK_INTERVAL)) {
            $order = getPendingOrder($chatId);
            
            if ($order) {
                $paymentStatus = checkPaymentStatus($order['deposit_code']);
                
                if ($paymentStatus) {
                    logMessage("Real-time payment detected for Chat: $chatId");
                    processSuccessfulPayment($chatId, $messageId, $order);
                    
                    $check['status'] = 'completed';
                    $activeChecks[] = $check;
                    continue;
                }
            }
            
            $check['last_check'] = $currentTime;
            logMessage("Real-time check performed for Chat: $chatId at " . date('H:i:s'));
        }
        
        $activeChecks[] = $check;
    }
    
    file_put_contents($checkFile, json_encode($activeChecks, JSON_PRETTY_PRINT));
}

function processSuccessfulPayment($chatId, $qrMessageId, $order) {
    if ($order['key_type'] == 'extend') {
        // For extend: licence is stored in manual_username field
        $licence = $order['manual_username'];
        $gameType = $order['game_type'];
        
        logMessage("Processing EXTEND payment - Chat: $chatId, Licence: $licence, Game: $gameType, Duration: {$order['duration']} days");
        
        if (extendUserLicense($licence, $order['duration'], $gameType)) {
            logMessage("EXTEND SUCCESS - License extended for: $licence, Status set to 1, UUID set to NULL");
            
            $userData = getUserByLicence($licence, $gameType);
            
            if ($userData) {
                $newExpDate = date('d-m-Y H:i:s', strtotime($userData['expDate']));
                sendExtendSuccess($chatId, $userData, $order['duration'], $newExpDate);
                updateOrderStatus($order['deposit_code'], 'completed');
                
                logMessage("EXTEND COMPLETED - User notified and order marked as completed for: $licence");
            } else {
                logMessage("ERROR: User data not found after extend for licence: $licence");
            }
        } else {
            logMessage("ERROR: Failed to extend license for: $licence");
        }
    } else {
        if ($order['key_type'] == 'manual') {
            // Manual key: licence is stored in manual_username
            $licence = $order['manual_username'];
        } else {
            // Random key: generate random licence
            $credentials = generateRandomCredentials();
            $licence = $credentials['licence'];
        }
        
        $table = ($order['game_type'] == 'ff') ? 'freefire' : 'ffmax';
        
        logMessage("Processing NEW LICENSE purchase - Chat: $chatId, Licence: $licence, Game: {$order['game_type']}, Duration: {$order['duration']} days, Status: 1 (active), UUID: NULL");
        
        if (saveLicenseToDatabase($table, $licence, $order['duration'], MERCHANT_CODE)) {
            sendLicenseToUser($chatId, $order['game_type'], $order['duration'], $licence, $order['key_type']);
            updateOrderStatus($order['deposit_code'], 'completed');
            
            logMessage("NEW LICENSE COMPLETED - License saved and user notified for: $licence");
        } else {
            logMessage("ERROR: Failed to save new license for: $licence");
        }
    }
}

/**
 * AUTO DELETE SCHEDULING FUNCTIONS
 */
function scheduleAutoDelete($chatId, $messageId, $delaySeconds, $type = 'pending') {
    $scheduleFile = __DIR__ . '/schedule.json';
    $schedules = [];
    
    if (file_exists($scheduleFile)) {
        $schedules = json_decode(file_get_contents($scheduleFile), true) ?? [];
    }
    
    $deleteTime = time() + $delaySeconds;
    
    $schedules[] = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'delete_time' => $deleteTime,
        'scheduled_at' => date('Y-m-d H:i:s'),
        'type' => $type
    ];
    
    file_put_contents($scheduleFile, json_encode($schedules, JSON_PRETTY_PRINT));
    logMessage("Scheduled auto delete for message $messageId in $delaySeconds seconds (Type: $type)");
}

function processAutoDelete() {
    $scheduleFile = __DIR__ . '/schedule.json';
    
    if (!file_exists($scheduleFile)) {
        return;
    }
    
    $schedules = json_decode(file_get_contents($scheduleFile), true) ?? [];
    $currentTime = time();
    $remainingSchedules = [];
    $deletedCount = 0;
    
    foreach ($schedules as $schedule) {
        if ($currentTime >= $schedule['delete_time'] && $schedule['type'] == 'pending') {
            $chatId = $schedule['chat_id'];
            $order = getPendingOrder($chatId);
            
            if (!$order || $order['status'] == 'pending') {
                deleteMessage($schedule['chat_id'], $schedule['message_id']);
                $deletedCount++;
                logMessage("Auto deleted cancelled/failed QR message " . $schedule['message_id'] . " from " . $schedule['chat_id']);
            } else {
                logMessage("Skipping deletion of completed order message " . $schedule['message_id']);
            }
        } else {
            $remainingSchedules[] = $schedule;
        }
    }
    
    if ($deletedCount > 0) {
        file_put_contents($scheduleFile, json_encode($remainingSchedules, JSON_PRETTY_PRINT));
        logMessage("Auto delete processed: $deletedCount cancelled/failed QR messages deleted");
    }
}

function cancelAutoDelete($chatId, $messageId) {
    $scheduleFile = __DIR__ . '/schedule.json';
    
    if (!file_exists($scheduleFile)) {
        return;
    }
    
    $schedules = json_decode(file_get_contents($scheduleFile), true) ?? [];
    $remainingSchedules = [];
    $cancelled = false;
    
    foreach ($schedules as $schedule) {
        if ($schedule['chat_id'] == $chatId && $schedule['message_id'] == $messageId) {
            $cancelled = true;
            logMessage("Cancelled auto delete for message $messageId from $chatId");
            continue;
        }
        $remainingSchedules[] = $schedule;
    }
    
    if ($cancelled) {
        file_put_contents($scheduleFile, json_encode($remainingSchedules, JSON_PRETTY_PRINT));
    }
    
    return $cancelled;
}

function showMainMenu($chatId, $text = null, $messageId = null) {
    $userPoints = getUserPoints($chatId);
    
    if ($text) {
        $message = $text;
    } else {
        $message = "🏠 <b>Menu Utama</b>\n\n";
        $message .= "💰 <b>Point Anda:</b> $userPoints points\n\n";
        $message .= "Silakan pilih menu yang diinginkan:";
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🛒 Beli Lisensi Baru', 'callback_data' => 'new_order']
            ],
            [
                ['text' => '⏰ Extend Masa Aktif', 'callback_data' => 'extend_user']
            ],
            [
                ['text' => '🎁 Tukar Point', 'callback_data' => 'redeem_points']
            ],
            [
                ['text' => 'ℹ️ Bantuan', 'callback_data' => 'help']
            ]
        ]
    ];
    
    if ($messageId) {
        $result = editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
    } else {
        $result = sendMessageWithImage($chatId, $message, json_encode($keyboard));
    }
    
    return $result;
}

function getBackButton($previousAction = '') {
    $buttons = [];
    
    if ($previousAction) {
        $buttons[] = [['text' => '↩️ Kembali', 'callback_data' => $previousAction]];
    }
    
    $buttons[] = [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']];
    
    return json_encode([
        'inline_keyboard' => $buttons
    ]);
}

function sendLicenseToUser($chatId, $gameType, $duration, $licence, $keyType = 'random', $qrMessageId = null) {
    $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
    $expiryDate = date('d-m-Y H:i:s', strtotime("+$duration days"));
    
    $pointsEarned = calculatePointsForDuration($duration);
    addUserPoints($chatId, $pointsEarned, "Pembelian lisensi $duration hari");
    
    $userPoints = getUserPoints($chatId);
    
    $message = "🎉 <b>PEMBAYARAN BERHASIL!</b>\n\n";
    $message .= "Terima kasih telah membeli lisensi <b>$gameName</b>\n";
    $message .= "Durasi: <b>$duration Hari</b>\n";
    $message .= "Tipe Key: <b>" . ($keyType == 'manual' ? 'MANUAL' : 'RANDOM') . "</b>\n\n";
    $message .= "📱 <b>AKUN ANDA:</b>\n";
    $message .= "Licence: <code>" . $licence . "</code>\n\n";
    $message .= "⏰ <b>MASA AKTIF:</b>\n";
    $message .= "Berlaku hingga: <b>$expiryDate WIB</b>\n\n";
    $message .= "🎁 <b>REWARD POINT:</b>\n";
    $message .= "Anda mendapatkan <b>$pointsEarned points</b>\n";
    $message .= "Total point Anda: <b>$userPoints points</b>\n\n";
    $message .= "✨ <b>Selamat bermain!</b> 🎮\n\n";
    $message .= "📁 <b>Untuk file dan tutorial instalasi:</b>\n";
    $message .= "Klik tombol '📁 File & Cara Pasang' di bawah";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📁 File & Cara Pasang', 'url' => 'https://t.me/+RY2yMHn_jts3YzA1']
            ],
            [
                ['text' => '🔄 Beli Lagi', 'callback_data' => 'new_order'],
                ['text' => '🎁 Tukar Point', 'callback_data' => 'redeem_points']
            ],
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
            ]
        ]
    ];
    
    $result = sendPhoto($chatId, WELCOME_IMAGE, $message, json_encode($keyboard), false);
    
    $adminMessage = "💰 <b>PEMBELIAN BERHASIL!</b>\n\n";
    $adminMessage .= "User ID: <code>$chatId</code>\n";
    $adminMessage .= "Jenis Game: <b>$gameName</b>\n";
    $adminMessage .= "Durasi: <b>$duration Hari</b>\n";
    $adminMessage .= "Tipe Key: <b>" . ($keyType == 'manual' ? 'MANUAL' : 'RANDOM') . "</b>\n";
    $adminMessage .= "Licence: <code>" . $licence . "</code>\n";
    $adminMessage .= "Point Diberikan: <b>$pointsEarned points</b>\n";
    $adminMessage .= "Masa Aktif: <b>$expiryDate WIB</b>\n";
    $adminMessage .= "Waktu: " . date('d-m-Y H:i:s');
    
    notifyAdmin($adminMessage);
    
    return $result;
}

function sendExtendSuccess($chatId, $userData, $duration, $newExpDate, $qrMessageId = null) {
    $gameName = ($userData['game_type'] == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
    
    $currentExp = date('d-m-Y H:i:s', strtotime($userData['expDate']));
    
    $pointsEarned = calculatePointsForDuration($duration);
    addUserPoints($chatId, $pointsEarned, "Extend lisensi $duration hari");
    
    $userPoints = getUserPoints($chatId);
    
    $message = "🎉 <b>EXTEND BERHASIL!</b>\n\n";
    $message .= "Akun Anda berhasil di-extend\n";
    $message .= "Jenis: <b>$gameName</b>\n";
    $message .= "Licence: <code>" . $userData['licence'] . "</code>\n";
    $message .= "Durasi Tambahan: <b>$duration Hari</b>\n";
    $message .= "Masa Aktif Lama: <b>$currentExp WIB</b>\n";
    $message .= "Masa Aktif Baru: <b>$newExpDate WIB</b>\n\n";
    $message .= "🎁 <b>REWARD POINT:</b>\n";
    $message .= "Anda mendapatkan <b>$pointsEarned points</b>\n";
    $message .= "Total point Anda: <b>$userPoints points</b>\n\n";
    $message .= "✨ <b>Selamat bermain!</b> 🎮\n\n";
    $message .= "📁 <b>Untuk file dan tutorial instalasi:</b>\n";
    $message .= "Klik tombol '📁 File & Cara Pasang' di bawah";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📁 File & Cara Pasang', 'url' => 'https://t.me/+RY2yMHn_jts3YzA1']
            ],
            [
                ['text' => '🔄 Extend Lagi', 'callback_data' => 'extend_user'],
                ['text' => '🎁 Tukar Point', 'callback_data' => 'redeem_points']
            ],
            [
                ['text' => '🔄 Beli Baru', 'callback_data' => 'new_order']
            ],
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
            ]
        ]
    ];
    
    $result = sendPhoto($chatId, WELCOME_IMAGE, $message, json_encode($keyboard), false);
    
    $adminMessage = "⏰ <b>EXTEND BERHASIL!</b>\n\n";
    $adminMessage .= "User ID: <code>$chatId</code>\n";
    $adminMessage .= "Jenis Game: <b>$gameName</b>\n";
    $adminMessage .= "Licence: <code>" . $userData['licence'] . "</code>\n";
    $adminMessage .= "Durasi: <b>$duration Hari</b>\n";
    $adminMessage .= "Point Diberikan: <b>$pointsEarned points</b>\n";
    $adminMessage .= "Masa Aktif Baru: <b>$newExpDate WIB</b>\n";
    $adminMessage .= "Waktu: " . date('d-m-Y H:i:s');
    
    notifyAdmin($adminMessage);
    
    return $result;
}

/**
 * POINT REDEMPTION FUNCTIONS
 */
function showRedeemPointsMenu($chatId, $messageId = null) {
    $userPoints = getUserPoints($chatId);
    
    $message = "🎁 <b>TUKAR POINT</b>\n\n";
    $message .= "💰 <b>Point Anda:</b> $userPoints points\n\n";
    $message .= "📊 <b>Rate Penukaran:</b>\n";
    $message .= "• 1 Hari = 12 points\n";
    $message .= "• 2 Hari = 24 points\n";
    $message .= "• 3 Hari = 36 points\n";
    $message .= "• 7 Hari = 84 points\n\n";
    $message .= "Pilih durasi yang ingin ditukar:";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '1 Hari - 12 points', 'callback_data' => 'redeem_1'],
                ['text' => '2 Hari - 24 points', 'callback_data' => 'redeem_2']
            ],
            [
                ['text' => '3 Hari - 36 points', 'callback_data' => 'redeem_3'],
                ['text' => '7 Hari - 84 points', 'callback_data' => 'redeem_7']
            ],
            [
                ['text' => '↩️ Kembali', 'callback_data' => 'main_menu']
            ]
        ]
    ];
    
    if ($messageId) {
        $result = editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
    } else {
        $result = sendMessageWithImage($chatId, $message, json_encode($keyboard));
    }
    
    return $result;
}

function processPointRedemption($chatId, $duration, $messageId) {
    $userPoints = getUserPoints($chatId);
    $pointsNeeded = calculatePointsNeededForDays($duration);
    
    if ($userPoints < $pointsNeeded) {
        $message = "❌ <b>Point tidak cukup!</b>\n\n";
        $message .= "Point yang dibutuhkan: <b>$pointsNeeded points</b>\n";
        $message .= "Point Anda: <b>$userPoints points</b>\n\n";
        $message .= "Silakan kumpulkan point lebih banyak dengan melakukan pembelian.";
        
        editMessageSmart($chatId, $messageId, $message, getBackButton('redeem_points'));
        return;
    }
    
    $message = "🎮 <b>PILIH JENIS GAME</b>\n\n";
    $message .= "Anda akan menukar <b>$pointsNeeded points</b> untuk lisensi <b>$duration hari</b>\n\n";
    $message .= "Pilih jenis Free Fire:";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎮 FREE FIRE', 'callback_data' => "redeem_ff"],
                ['text' => '⚡ FREE FIRE MAX', 'callback_data' => "redeem_ffmax"]
            ],
            [
                ['text' => '↩️ Kembali', 'callback_data' => 'redeem_points']
            ]
        ]
    ];
    
    saveUserState($chatId, 'waiting_redeem_game', [
        'duration' => $duration,
        'points_needed' => $pointsNeeded
    ]);
    
    editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
}

function completePointRedemption($chatId, $gameType, $duration, $messageId) {
    $pointsNeeded = calculatePointsNeededForDays($duration);
    $userPoints = getUserPoints($chatId);
    
    logMessage("DEBUG: Starting point redemption - Chat: $chatId, Game: $gameType, Duration: $duration, Points Needed: $pointsNeeded, User Points: $userPoints");
    
    if ($userPoints < $pointsNeeded) {
        $message = "❌ <b>Point tidak cukup!</b>\n\n";
        $message .= "Point yang dibutuhkan: <b>$pointsNeeded points</b>\n";
        $message .= "Point Anda: <b>$userPoints points</b>";
        
        logMessage("ERROR: Insufficient points for redemption - Chat: $chatId, Needed: $pointsNeeded, Has: $userPoints");
        editMessageSmart($chatId, $messageId, $message, getBackButton('redeem_points'));
        return;
    }
    
    $credentials = generateRedeemCredentials();
    $licence = $credentials['licence'];
    $table = ($gameType == 'ff') ? 'freefire' : 'ffmax';
    
    logMessage("DEBUG: Generated licence - Licence: " . $licence);
    
    $maxAttempts = 10;
    $attempts = 0;
    while (isLicenceExists($licence, $table) && $attempts < $maxAttempts) {
        $credentials = generateRedeemCredentials();
        $licence = $credentials['licence'];
        $attempts++;
        logMessage("DEBUG: Licence exists, regenerating... Attempt: $attempts, New Licence: " . $licence);
    }
    
    if ($attempts >= $maxAttempts) {
        $message = "❌ <b>Gagal generate licence unik!</b>\n\n";
        $message .= "Silakan coba lagi.";
        
        logMessage("ERROR: Failed to generate unique licence after $maxAttempts attempts");
        editMessageSmart($chatId, $messageId, $message, getBackButton('redeem_points'));
        return;
    }
    
    $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
    
    logMessage("DEBUG: Attempting to redeem points - Chat: $chatId, Points: $pointsNeeded");
    if (!redeemUserPoints($chatId, $pointsNeeded, "Penukaran lisensi $duration hari")) {
        $message = "❌ <b>Gagal menukar point!</b>\n\n";
        $message .= "Terjadi kesalahan sistem. Silakan coba lagi.";
        
        logMessage("ERROR: Failed to redeem points - Chat: $chatId, Points: $pointsNeeded");
        editMessageSmart($chatId, $messageId, $message, getBackButton('redeem_points'));
        return;
    }
    
    logMessage("SUCCESS: Points redeemed successfully - Chat: $chatId, Points: $pointsNeeded");
    
    logMessage("DEBUG: Attempting to save license to database - Table: $table");
    if (saveLicenseToDatabase($table, $licence, $duration, 'DIMZ1945')) {
        $expiryDate = date('d-m-Y H:i:s', strtotime("+$duration days"));
        $newUserPoints = getUserPoints($chatId);
        
        $message = "🎉 <b>PENUKARAN POINT BERHASIL!</b>\n\n";
        $message .= "Anda berhasil menukar <b>$pointsNeeded points</b>\n";
        $message .= "Untuk lisensi <b>$gameName</b> selama <b>$duration hari</b>\n\n";
        $message .= "📱 <b>AKUN ANDA:</b>\n";
        $message .= "Licence: <code>" . $licence . "</code>\n";
        $message .= "Tipe Key: <b>REDEEM (AUTO RANDOM)</b>\n\n";
        $message .= "⏰ <b>MASA AKTIF:</b>\n";
        $message .= "Berlaku hingga: <b>$expiryDate WIB</b>\n\n";
        $message .= "🎮 <b>JENIS GAME:</b> $gameName\n";
        $message .= "💰 <b>SISA POINT:</b> $newUserPoints points\n\n";
        $message .= "✨ <b>Selamat bermain!</b> 🎮\n\n";
        $message .= "📁 <b>Untuk file dan tutorial instalasi:</b>\n";
        $message .= "Klik tombol '📁 File & Cara Pasang' di bawah";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📁 File & Cara Pasang', 'url' => 'https://t.me/+RY2yMHn_jts3YzA1']
                ],
                [
                    ['text' => '🎁 Tukar Lagi', 'callback_data' => 'redeem_points'],
                    ['text' => '🛒 Beli Lisensi', 'callback_data' => 'new_order']
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                ]
            ]
        ];
        
        logMessage("SUCCESS: License created successfully - Chat: $chatId, Licence: " . $licence . ", Table: $table, Status: 1, UUID: NULL");
        
        sendPhoto($chatId, WELCOME_IMAGE, $message, json_encode($keyboard), false);
        
        $adminMessage = "🎁 <b>PENUKARAN POINT BARU!</b>\n\n";
        $adminMessage .= "User ID: <code>$chatId</code>\n";
        $adminMessage .= "Jenis Game: <b>$gameName</b>\n";
        $adminMessage .= "Durasi: <b>$duration Hari</b>\n";
        $adminMessage .= "Tipe Key: <b>REDEEM (AUTO RANDOM)</b>\n";
        $adminMessage .= "Licence: <code>" . $licence . "</code>\n";
        $adminMessage .= "Point Ditukar: <b>$pointsNeeded points</b>\n";
        $adminMessage .= "Masa Aktif: <b>$expiryDate WIB</b>\n";
        $adminMessage .= "Waktu: " . date('d-m-Y H:i:s');
        
        notifyAdmin($adminMessage);
        
    } else {
        logMessage("ERROR: Failed to save license, refunding points - Chat: $chatId, Points: $pointsNeeded");
        addUserPoints($chatId, $pointsNeeded, "Refund gagal penukaran");
        
        $message = "❌ <b>Gagal membuat lisensi!</b>\n\n";
        $message .= "Point telah dikembalikan. Silakan coba lagi.";
        
        editMessageSmart($chatId, $messageId, $message, getBackButton('redeem_points'));
    }
}

// PROCESS AUTO DELETE AND REAL-TIME CHECK ON EVERY REQUEST
processAutoDelete();
processRealTimePaymentChecks();

// ============================================================================
// SECTION: HANDLE ADMIN BROADCAST CONTENT
// ============================================================================

$adminState = getAdminState($chatId);
if ($adminState && (strpos($adminState['state'], 'waiting_broadcast') === 0)) {
    
    // Check if admin sent /cancel or /clear command
    if (!empty($text) && (strpos($text, '/cancel') === 0 || strpos($text, '/clear') === 0)) {
        clearAdminState($chatId);
        sendSimpleMessage($chatId, "✅ <b>Mode broadcast dibatalkan.</b>\n\nAnda dapat menggunakan /pengumuman atau /adds untuk memulai broadcast baru.");
        exit;
    }
    
    $broadcastType = str_replace('waiting_broadcast_', '', $adminState['state']);
    
    $messageType = '';
    $fileId = '';
    $caption = '';
    
    // Detect message type
    if (isset($update['message']['photo'])) {
        $messageType = 'photo';
        $photos = $update['message']['photo'];
        $fileId = end($photos)['file_id'];
        $caption = $update['message']['caption'] ?? '';
    } elseif (isset($update['message']['video'])) {
        $messageType = 'video';
        $fileId = $update['message']['video']['file_id'];
        $caption = $update['message']['caption'] ?? '';
    } elseif (isset($update['message']['document'])) {
        $messageType = 'document';
        $fileId = $update['message']['document']['file_id'];
        $caption = $update['message']['caption'] ?? '';
    } elseif (isset($update['message']['audio'])) {
        $messageType = 'audio';
        $fileId = $update['message']['audio']['file_id'];
        $caption = $update['message']['caption'] ?? '';
    } elseif (isset($update['message']['text'])) {
        // Skip if it's a command (other than /cancel and /clear which are handled above)
        if (strpos($update['message']['text'], '/') === 0) {
            $messageType = '';
        } else {
            $messageType = 'text';
            $caption = $update['message']['text'];
        }
    }
    
    if (!empty($messageType)) {
        // Clear admin state IMMEDIATELY to prevent re-triggering
        clearAdminState($chatId);
        
        // Check if another broadcast is already running
        if (isBroadcastRunning()) {
            sendSimpleMessage($chatId, "⚠️ <b>Broadcast sedang berjalan!</b>\n\nSilakan tunggu broadcast sebelumnya selesai.");
            exit;
        }
        
        // Confirm broadcast
        $confirmMessage = "📤 <b>KONFIRMASI BROADCAST</b>\n\n";
        $confirmMessage .= "Tipe: <b>" . ($broadcastType == 'pengumuman' ? 'Pengumuman' : 'Notifikasi/Iklan') . "</b>\n";
        $confirmMessage .= "Format: <b>" . strtoupper($messageType) . "</b>\n";
        
        if (!empty($caption)) {
            $confirmMessage .= "Caption: " . substr($caption, 0, 100) . (strlen($caption) > 100 ? '...' : '') . "\n";
        }
        
        $confirmMessage .= "\n⏳ <b>Memulai broadcast...</b>";
        
        sendSimpleMessage($chatId, $confirmMessage);
        
        // Start broadcasting (each user gets exactly 1 message)
        $result = broadcastToAllUsers($messageType, $fileId, $caption, $broadcastType);
        
        // Save history
        saveBroadcastHistory($chatId, $broadcastType, $messageType, $result['total'], $result['success'], $result['failed']);
        
        // Send result to admin
        $successRate = ($result['total'] > 0) ? round(($result['success'] / $result['total']) * 100, 2) : 0;
        
        $resultMessage = "✅ <b>BROADCAST SELESAI!</b>\n\n";
        $resultMessage .= "📊 <b>Statistik:</b>\n";
        $resultMessage .= "• Total Pengguna: {$result['total']}\n";
        $resultMessage .= "• Berhasil: {$result['success']} ✅\n";
        $resultMessage .= "• Gagal: {$result['failed']} ❌\n";
        $resultMessage .= "• Success Rate: {$successRate}%\n\n";
        $resultMessage .= "Waktu: " . date('d-m-Y H:i:s');
        
        sendSimpleMessage($chatId, $resultMessage);
        
        exit;
    }
}

/**
 * HANDLE TEXT MESSAGES
 */
if (!empty($text)) {
    if (strpos($text, '/start') === 0) {
        clearUserState($chatId);
        clearAdminState($chatId);
        
        $username = $update['message']['chat']['username'] ?? '';
        saveBotUser($chatId, $firstName, $username);
        
        logMessage("User $chatId started bot");
        
        $userPoints = getUserPoints($chatId);
        
        $welcomeMessage = "🎮 <b>Selamat Datang, $firstName!</b>\n\n";
        $welcomeMessage .= "✨ <b>BOT PEMBELIAN LISENSI FREE FIRE</b> ✨\n\n";
        $welcomeMessage .= "💰 <b>Point Anda:</b> $userPoints points\n\n";
        $welcomeMessage .= "🛒 <b>Fitur yang tersedia:</b>\n";
        $welcomeMessage .= "• Beli lisensi baru (Random/Manual)\n";
        $welcomeMessage .= "• Extend masa aktif akun\n";
        $welcomeMessage .= "• Tukar point dengan lisensi gratis\n";
        $welcomeMessage .= "• Support Free Fire & Free Fire MAX\n";
        $welcomeMessage .= "• Pembayaran QRIS otomatis\n\n";
        $welcomeMessage .= "💰 <b>Harga mulai dari Rp 15.000</b>\n";
        $welcomeMessage .= "🎁 <b>Dapatkan point untuk setiap pembelian!</b>\n\n";
        $welcomeMessage .= "⏰ <b>Pembayaran otomatis terdeteksi dalam 25 menit!</b>\n\n";
        $welcomeMessage .= "Silakan pilih menu di bawah:";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Beli Lisensi Baru', 'callback_data' => 'new_order']
                ],
                [
                    ['text' => '⏰ Extend Masa Aktif', 'callback_data' => 'extend_user'],
                    ['text' => '🎁 Tukar Point', 'callback_data' => 'redeem_points']
                ],
                [
                    ['text' => 'ℹ️ Bantuan', 'callback_data' => 'help']
                ]
            ]
        ];
        
        sendMessageWithImage($chatId, $welcomeMessage, json_encode($keyboard));
    }
    elseif (strpos($text, '/menu') === 0) {
        showMainMenu($chatId, "🏠 <b>Menu Utama</b>\n\nSilakan pilih menu yang diinginkan:");
    }
    elseif (strpos($text, '/points') === 0) {
        $userPoints = getUserPoints($chatId);
        $message = "💰 <b>POINT ANDA</b>\n\n";
        $message .= "Total Point: <b>$userPoints points</b>\n\n";
        $message .= "📊 <b>Cara mendapatkan point:</b>\n";
        $message .= "• Beli lisensi 1 hari = 1 point\n";
        $message .= "• Beli lisensi 3 hari = 2 point\n";
        $message .= "• Beli lisensi 5 hari = 4 point\n";
        $message .= "• Beli lisensi 7 hari = 5 point\n";
        $message .= "• Dan seterusnya...\n\n";
        $message .= "🎁 <b>Tukar point dengan lisensi gratis!</b>\n";
        $message .= "12 points = 1 hari lisensi gratis";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎁 Tukar Point', 'callback_data' => 'redeem_points']
                ],
                [
                    ['text' => '🛒 Beli Lisensi', 'callback_data' => 'new_order']
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                ]
            ]
        ];
        
        sendMessageWithImage($chatId, $message, json_encode($keyboard));
    }
    // ============================================================================
    // ADMIN BROADCAST COMMANDS - FIXED VERSION
    // ============================================================================
    elseif (strpos($text, '/pengumuman') === 0) {
        if (!isAdmin($chatId)) {
            sendSimpleMessage($chatId, "❌ <b>Akses Ditolak!</b>\n\nPerintah ini hanya untuk admin.");
            exit;
        }
        
        if (isBroadcastRunning()) {
            sendSimpleMessage($chatId, "⚠️ <b>Broadcast sedang berjalan!</b>\n\nSilakan tunggu broadcast sebelumnya selesai terlebih dahulu.");
            exit;
        }
        
        $totalUsers = getTotalBotUsers();
        $timeoutMinutes = BROADCAST_TIMEOUT / 60;
        
        $message = "📢 <b>MODE PENGUMUMAN</b>\n\n";
        $message .= "Kirim pesan yang ingin Anda broadcast ke semua pengguna.\n\n";
        $message .= "📊 <b>Total Pengguna Aktif:</b> $totalUsers users\n\n";
        $message .= "✅ <b>Anda dapat mengirim:</b>\n";
        $message .= "• Foto dengan caption\n";
        $message .= "• Video dengan caption\n";
        $message .= "• File/Dokumen dengan caption\n";
        $message .= "• Pesan teks biasa\n\n";
        $message .= "⏰ <b>Batas Waktu:</b> {$timeoutMinutes} menit\n";
        $message .= "Mode akan otomatis dibatalkan jika tidak ada konten dalam {$timeoutMinutes} menit.\n\n";
        $message .= "⚠️ <b>Kirim /cancel atau /clear untuk membatalkan</b>";
        
        saveAdminState($chatId, 'waiting_broadcast_pengumuman');
        sendSimpleMessage($chatId, $message);
    }
    elseif (strpos($text, '/adds') === 0) {
        if (!isAdmin($chatId)) {
            sendSimpleMessage($chatId, "❌ <b>Akses Ditolak!</b>\n\nPerintah ini hanya untuk admin.");
            exit;
        }
        
        if (isBroadcastRunning()) {
            sendSimpleMessage($chatId, "⚠️ <b>Broadcast sedang berjalan!</b>\n\nSilakan tunggu broadcast sebelumnya selesai terlebih dahulu.");
            exit;
        }
        
        $totalUsers = getTotalBotUsers();
        $timeoutMinutes = BROADCAST_TIMEOUT / 60;
        
        $message = "🔔 <b>MODE NOTIFIKASI/IKLAN</b>\n\n";
        $message .= "Kirim pesan yang ingin Anda broadcast ke semua pengguna.\n";
        $message .= "Pesan akan dikirim dengan notifikasi khusus.\n\n";
        $message .= "📊 <b>Total Pengguna Aktif:</b> $totalUsers users\n\n";
        $message .= "✅ <b>Anda dapat mengirim:</b>\n";
        $message .= "• Foto dengan caption\n";
        $message .= "• Video dengan caption\n";
        $message .= "• File/Dokumen dengan caption\n";
        $message .= "• Pesan teks biasa\n\n";
        $message .= "⏰ <b>Batas Waktu:</b> {$timeoutMinutes} menit\n";
        $message .= "Mode akan otomatis dibatalkan jika tidak ada konten dalam {$timeoutMinutes} menit.\n\n";
        $message .= "⚠️ <b>Kirim /cancel atau /clear untuk membatalkan</b>";
        
        saveAdminState($chatId, 'waiting_broadcast_adds');
        sendSimpleMessage($chatId, $message);
    }
    elseif (strpos($text, '/cancel') === 0 || strpos($text, '/clear') === 0) {
        // Handle /cancel and /clear for both admin broadcast and user states
        $adminState = getAdminState($chatId);
        if ($adminState && (strpos($adminState['state'], 'waiting_broadcast') === 0)) {
            clearAdminState($chatId);
            sendSimpleMessage($chatId, "✅ <b>Mode broadcast dibatalkan.</b>\n\nAnda dapat menggunakan /pengumuman atau /adds untuk memulai broadcast baru.");
        } else {
            $userState = getUserState($chatId);
            if ($userState) {
                clearUserState($chatId);
                sendSimpleMessage($chatId, "✅ <b>Operasi dibatalkan.</b>\n\nGunakan /start atau /menu untuk kembali ke menu utama.");
            } else {
                sendSimpleMessage($chatId, "ℹ️ <b>Tidak ada operasi yang sedang berjalan.</b>\n\nGunakan /start atau /menu untuk membuka menu.");
            }
        }
    }
    else {
        // Handle state-based messages (manual input for licence)
        $userState = getUserState($chatId);
        
        if ($userState && $userState['state'] == 'waiting_manual_input') {
            // Manual licence input - user sends the licence key directly
            $licence = trim($text);
            
            if (empty($licence)) {
                sendMessageWithImage($chatId, "❌ <b>Licence tidak boleh kosong!</b>\n\nSilakan masukkan licence key Anda:", getBackButton('new_order'));
                exit;
            }
            
            $gameType = $userState['data']['game_type'];
            $table = ($gameType == 'ff') ? 'freefire' : 'ffmax';
            
            if (isLicenceExists($licence, $table)) {
                $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
                $errorMessage = "❌ <b>Licence sudah digunakan di $gameName!</b>\n\n";
                $errorMessage .= "Licence <code>$licence</code> sudah terdaftar di <b>$gameName</b>.\n\n";
                $errorMessage .= "💡 <b>Tips:</b> Gunakan licence yang berbeda\n\n";
                $errorMessage .= "📝 <b>Silakan masukkan licence key baru:</b>";
                
                sendMessageWithImage($chatId, $errorMessage, getBackButton('new_order'));
                exit;
            }
            
            $duration = $userState['data']['duration'];
            $amount = $GLOBALS['prices'][$duration];
            $orderId = 'DIMZ' . time() . rand(100, 999);
            
            $payment = createPayment($orderId, $amount);
            
            if ($payment && $payment['status']) {
                $paymentData = $payment['data'];
                $gameName = strtoupper($gameType);
                
                $message = "💳 <b>PEMBAYARAN $gameName (MANUAL)</b>\n\n";
                $message .= "Jenis: <b>$gameName</b>\n";
                $message .= "Durasi: <b>$duration Hari</b>\n";
                $message .= "Tipe: <b>KEY MANUAL</b>\n";
                $message .= "Licence: <code>$licence</code>\n";
                $message .= "Harga: <b>Rp " . number_format($amount, 0, ',', '.') . "</b>\n";
                $message .= "Order ID: <code>$orderId</code>\n\n";
                $message .= "📱 <b>INSTRUKSI PEMBAYARAN:</b>\n";
                $message .= "1. Scan QR Code di bawah\n";
                $message .= "2. Bayar sesuai amount\n";
                $message .= "3. Pembayaran akan terdeteksi otomatis\n\n";
                $message .= "⏰ <b>Batas Waktu: 25 MENIT</b>\n";
                $message .= "🔄 <b>Cek Otomatis: Setiap 20 detik</b>\n";
                $message .= "QR akan otomatis terhapus setelah 25 menit jika tidak bayar\n";
                $message .= "Expired: " . $paymentData['expired'] . "\n\n";
                $message .= "🚀 <b>Pembayaran akan diproses otomatis!</b>";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔍 Cek Status Manual', 'callback_data' => 'check_payment']
                        ],
                        [
                            ['text' => '❌ Batalkan Pesanan', 'callback_data' => 'cancel_order']
                        ]
                    ]
                ];
                
                // Store licence in manual_username field for compatibility
                savePendingOrder($orderId, $chatId, $gameType, $duration, $amount, $paymentData['kode_deposit'], 'manual', $licence, '');
                clearUserState($chatId);
                
                sendPhoto($chatId, $paymentData['link_qr'], $message, json_encode($keyboard));
            } else {
                sendMessageWithImage($chatId, "❌ Gagal membuat pembayaran. Silakan coba lagi.", getBackButton('new_order'));
                clearUserState($chatId);
            }
        }
        elseif ($userState && $userState['state'] == 'waiting_extend_credentials') {
            // Extend: user sends licence key directly
            $licence = trim($text);
            
            if (empty($licence)) {
                sendMessageWithImage($chatId, "❌ <b>Licence tidak boleh kosong!</b>\n\nSilakan masukkan licence key Anda:", getBackButton('extend_user'));
                exit;
            }
            
            $gameType = $userState['data']['game_type'];
            
            $userData = getUserByLicence($licence, $gameType);
            
            if ($userData) {
                resetUserErrorCount($chatId);
                
                saveUserState($chatId, 'waiting_extend_duration', [
                    'licence' => $licence,
                    'user_data' => $userData,
                    'game_type' => $gameType
                ]);
                
                $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
                $currentExp = date('d-m-Y H:i:s', strtotime($userData['expDate']));
                
                $message = "✅ <b>LICENCE DITEMUKAN!</b>\n\n";
                $message .= "Licence: <code>$licence</code>\n";
                $message .= "Jenis: <b>$gameName</b>\n";
                $message .= "Masa Aktif Saat Ini: <b>$currentExp WIB</b>\n\n";
                $message .= "💰 <b>Pilih Durasi Extend:</b>";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '1 Hari - 15k', 'callback_data' => "extend_duration_1"],
                            ['text' => '2 Hari - 30k', 'callback_data' => "extend_duration_2"],
                            ['text' => '3 Hari - 40k', 'callback_data' => "extend_duration_3"]
                        ],
                        [
                            ['text' => '4 Hari - 50k', 'callback_data' => "extend_duration_4"],
                            ['text' => '5 Hari - 60k', 'callback_data' => "extend_duration_5"],
                            ['text' => '6 Hari - 70k', 'callback_data' => "extend_duration_6"]
                        ],
                        [
                            ['text' => '7 Hari - 80k', 'callback_data' => "extend_duration_7"],
                            ['text' => '8 Hari - 90k', 'callback_data' => "extend_duration_8"],
                            ['text' => '10 Hari - 100k', 'callback_data' => "extend_duration_10"]
                        ],
                        [
                            ['text' => '15 Hari - 150k', 'callback_data' => "extend_duration_15"],
                            ['text' => '20 Hari - 180k', 'callback_data' => "extend_duration_20"],
                            ['text' => '30 Hari - 250k', 'callback_data' => "extend_duration_30"]
                        ],
                        [
                            ['text' => '↩️ Kembali', 'callback_data' => 'extend_type_' . $gameType],
                            ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                        ]
                    ]
                ];
                
                sendMessageWithImage($chatId, $message, json_encode($keyboard));
            } else {
                $currentErrorCount = $userState['error_count'] ?? 0;
                $newErrorCount = $currentErrorCount + 1;
                updateUserErrorCount($chatId, $newErrorCount);
                
                $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
                $errorMessage = "❌ <b>Licence tidak ditemukan di $gameName!</b>\n\n";
                
                if ($newErrorCount >= 2) {
                    $errorMessage .= "⚠️ <b>Anda telah 2 kali melakukan kesalahan.</b>\n";
                    $errorMessage .= "Silakan mulai ulang dari menu utama.\n\n";
                    clearUserState($chatId);
                    sendMessageWithImage($chatId, $errorMessage, getBackButton());
                } else {
                    $errorMessage .= "Silakan coba lagi dengan licence yang benar:\n\n";
                    $errorMessage .= "📝 <b>Masukkan licence key Anda:</b>";
                    sendMessageWithImage($chatId, $errorMessage, getBackButton('extend_user'));
                }
            }
        }
    }
}

/**
 * HANDLE CALLBACK QUERIES
 */
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $data = $callback['data'];
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $callbackId = $callback['id'];
    
    logMessage("Callback received: $data from $chatId");
    
    try {
        answerCallbackQuery($callbackId);
        
        if ($data == 'main_menu') {
            clearUserState($chatId);
            showMainMenu($chatId, null, $messageId);
        }
        elseif ($data == 'new_order') {
            $message = "👋 <b>Halo!</b>\n\n";
            $message .= "Silakan pilih jenis Free Fire yang ingin Anda beli:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎮 FREE FIRE', 'callback_data' => 'type_ff'],
                        ['text' => '⚡ FREE FIRE MAX', 'callback_data' => 'type_ffmax']
                    ],
                    [
                        ['text' => '↩️ Kembali', 'callback_data' => 'main_menu']
                    ]
                ]
            ];
            
            editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
        }
        elseif ($data == 'extend_user') {
            $message = "🎮 <b>EXTEND MASA AKTIF</b>\n\n";
            $message .= "Pilih jenis Free Fire yang ingin di-extend:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎮 FREE FIRE', 'callback_data' => 'extend_type_ff'],
                        ['text' => '⚡ FREE FIRE MAX', 'callback_data' => 'extend_type_ffmax']
                    ],
                    [
                        ['text' => '↩️ Kembali', 'callback_data' => 'main_menu']
                    ]
                ]
            ];
            
            editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
        }
        elseif ($data == 'redeem_points') {
            showRedeemPointsMenu($chatId, $messageId);
        }
        elseif ($data == 'help') {
            $userPoints = getUserPoints($chatId);
            
            $helpMessage = "ℹ️ <b>BANTUAN</b>\n\n";
            $helpMessage .= "💰 <b>Point Anda:</b> $userPoints points\n\n";
            $helpMessage .= "📝 <b>Cara Penggunaan:</b>\n";
            $helpMessage .= "1. Pilih 'Beli Lisensi Baru' untuk pembelian baru\n";
            $helpMessage .= "2. Pilih 'Extend Masa Aktif' untuk memperpanjang\n";
            $helpMessage .= "3. Pilih 'Tukar Point' untuk lisensi gratis\n";
            $helpMessage .= "4. Ikuti instruksi yang diberikan\n\n";
            $helpMessage .= "🔧 <b>Fitur:</b>\n";
            $helpMessage .= "• Support Free Fire & Free Fire MAX\n";
            $helpMessage .= "• Pembayaran QRIS otomatis\n";
            $helpMessage .= "• Extend masa aktif\n";
            $helpMessage .= "• Key random & manual\n";
            $helpMessage .= "• Sistem point/reward\n\n";
            $helpMessage .= "🎁 <b>Sistem Point:</b>\n";
            $helpMessage .= "• Dapatkan point dari setiap pembelian\n";
            $helpMessage .= "• 12 points = 1 hari lisensi gratis\n";
            $helpMessage .= "• Point tidak memiliki masa kedaluwarsa\n\n";
            $helpMessage .= "⏰ <b>Pembayaran Otomatis:</b>\n";
            $helpMessage .= "• QR berlaku selama 25 menit\n";
            $helpMessage .= "• Cek pembayaran otomatis setiap 20 detik\n";
            $helpMessage .= "• QR terhapus otomatis jika tidak dibayar\n";
            $helpMessage .= "• Pesan sukses tidak akan dihapus\n\n";
            $helpMessage .= "❓ <b>Pertanyaan?</b>\n";
            $helpMessage .= "Hubungi admin jika ada kendala @dimasvip1120";
            
            showMainMenu($chatId, $helpMessage, $messageId);
        }
        elseif (strpos($data, 'type_') === 0) {
            $type = str_replace('type_', '', $data);
            
            $message = "💰 <b>Pilih Durasi Lisensi " . strtoupper($type) . ":</b>\n\n";
            $message .= "Silakan pilih durasi:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '1 Hari - 15k', 'callback_data' => "duration_{$type}_1"],
                        ['text' => '2 Hari - 30k', 'callback_data' => "duration_{$type}_2"],
                        ['text' => '3 Hari - 40k', 'callback_data' => "duration_{$type}_3"]
                    ],
                    [
                        ['text' => '4 Hari - 50k', 'callback_data' => "duration_{$type}_4"],
                        ['text' => '5 Hari - 60k', 'callback_data' => "duration_{$type}_5"],
                        ['text' => '6 Hari - 70k', 'callback_data' => "duration_{$type}_6"]
                    ],
                    [
                        ['text' => '7 Hari - 80k', 'callback_data' => "duration_{$type}_7"],
                        ['text' => '8 Hari - 90k', 'callback_data' => "duration_{$type}_8"],
                        ['text' => '10 Hari - 100k', 'callback_data' => "duration_{$type}_10"]
                    ],
                    [
                        ['text' => '15 Hari - 150k', 'callback_data' => "duration_{$type}_15"],
                        ['text' => '20 Hari - 180k', 'callback_data' => "duration_{$type}_20"],
                        ['text' => '30 Hari - 250k', 'callback_data' => "duration_{$type}_30"]
                    ],
                    [
                        ['text' => '↩️ Kembali', 'callback_data' => 'new_order'],
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                    ]
                ]
            ];
            
            editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
        }
        elseif (strpos($data, 'duration_') === 0) {
            $parts = explode('_', $data);
            $type = $parts[1];
            $duration = $parts[2];
            
            $message = "🔑 <b>Pilih Tipe Key untuk " . strtoupper($type) . ":</b>\n\n";
            $message .= "🎲 <b>RANDOM KEY</b>\n";
            $message .= "• Licence digenerate otomatis\n";
            $message .= "• Format: 2 huruf + 2 angka\n\n";
            $message .= "✏️ <b>MANUAL KEY</b>\n";
            $message .= "• Input licence manual\n";
            $message .= "• Anda bisa menentukan licence sendiri\n\n";
            $message .= "Silakan pilih tipe key:";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎲 RANDOM KEY', 'callback_data' => "keytype_{$type}_{$duration}_random"],
                        ['text' => '✏️ MANUAL KEY', 'callback_data' => "keytype_{$type}_{$duration}_manual"]
                    ],
                    [
                        ['text' => '↩️ Kembali', 'callback_data' => 'type_' . $type],
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                    ]
                ]
            ];
            
            editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
        }
        elseif (strpos($data, 'keytype_') === 0) {
            $parts = explode('_', $data);
            $type = $parts[1];
            $duration = $parts[2];
            $keyType = $parts[3];
            
            if ($keyType == 'random') {
                $amount = $GLOBALS['prices'][$duration];
                $orderId = 'DIMZ' . time() . rand(100, 999);
                
                $payment = createPayment($orderId, $amount);
                
                if ($payment && $payment['status']) {
                    $paymentData = $payment['data'];
                    
                    $message = "💳 <b>PEMBAYARAN " . strtoupper($type) . " (RANDOM)</b>\n\n";
                    $message .= "Jenis: <b>" . strtoupper($type) . "</b>\n";
                    $message .= "Durasi: <b>$duration Hari</b>\n";
                    $message .= "Tipe: <b>KEY RANDOM</b>\n";
                    $message .= "Harga: <b>Rp " . number_format($amount, 0, ',', '.') . "</b>\n";
                    $message .= "Order ID: <code>$orderId</code>\n\n";
                    $message .= "📱 <b>INSTRUKSI PEMBAYARAN:</b>\n";
                    $message .= "1. Scan QR Code di bawah\n";
                    $message .= "2. Bayar sesuai amount\n";
                    $message .= "3. Pembayaran akan terdeteksi otomatis\n\n";
                    $message .= "⏰ <b>Batas Waktu: 25 MENIT</b>\n";
                    $message .= "🔄 <b>Cek Otomatis: Setiap 20 detik</b>\n";
                    $message .= "QR akan otomatis terhapus setelah 25 menit jika tidak bayar\n";
                    $message .= "Expired: " . $paymentData['expired'] . "\n\n";
                    $message .= "🚀 <b>Pembayaran akan diproses otomatis!</b>";
                    
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔍 Cek Status Manual', 'callback_data' => 'check_payment']
                            ],
                            [
                                ['text' => '❌ Batalkan Pesanan', 'callback_data' => 'cancel_order']
                            ]
                        ]
                    ];
                    
                    savePendingOrder($orderId, $chatId, $type, $duration, $amount, $paymentData['kode_deposit'], 'random');
                    
                    sendPhoto($chatId, $paymentData['link_qr'], $message, json_encode($keyboard));
                } else {
                    $errorMsg = "❌ Gagal membuat pembayaran. Silakan coba lagi.";
                    editMessageSmart($chatId, $messageId, $errorMsg, getBackButton('type_' . $type));
                }
            } elseif ($keyType == 'manual') {
                saveUserState($chatId, 'waiting_manual_input', [
                    'game_type' => $type,
                    'duration' => $duration
                ]);
                
                $instruction = "✏️ <b>MASUKKAN LICENCE KEY</b>\n\n";
                $instruction .= "📝 <b>Ketik licence key yang Anda inginkan:</b>\n\n";
                $instruction .= "🎯 <b>Contoh:</b>\n";
                $instruction .= "<code>kambing123</code>\n";
                $instruction .= "<code>player99</code>\n";
                $instruction .= "<code>gamer007</code>\n\n";
                $instruction .= "➡️ Cukup ketik licence key yang Anda inginkan";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '↩️ Kembali', 'callback_data' => 'duration_' . $type . '_' . $duration],
                            ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                        ]
                    ]
                ];
                
                editMessageSmart($chatId, $messageId, $instruction, json_encode($keyboard));
            }
        }
        elseif (strpos($data, 'extend_type_') === 0) {
            $gameType = str_replace('extend_type_', '', $data);
            $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
            
            saveUserState($chatId, 'waiting_extend_credentials', [
                'game_type' => $gameType
            ]);
            
            $message = "✏️ <b>EXTEND $gameName</b>\n\n";
            $message .= "Masukkan <b>LICENCE KEY</b> yang ingin di-extend:\n\n";
            $message .= "🎯 <b>Contoh:</b>\n";
            $message .= "<code>AB12</code>\n";
            $message .= "<code>player123</code>\n\n";
            $message .= "⚠️ <b>Pastikan licence terdaftar di $gameName</b>";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '↩️ Kembali', 'callback_data' => 'extend_user'],
                        ['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']
                    ]
                ]
            ];
            
            editMessageSmart($chatId, $messageId, $message, json_encode($keyboard));
        }
        elseif (strpos($data, 'extend_duration_') === 0) {
            $duration = str_replace('extend_duration_', '', $data);
            $userState = getUserState($chatId);
            
            if ($userState && $userState['state'] == 'waiting_extend_duration') {
                $licence = $userState['data']['licence'];
                $userData = $userState['data']['user_data'];
                $gameType = $userState['data']['game_type'];
                $amount = $GLOBALS['prices'][$duration];
                
                $orderId = 'EXTEND' . time() . rand(100, 999);
                
                $payment = createPayment($orderId, $amount);
                
                if ($payment && $payment['status']) {
                    $paymentData = $payment['data'];
                    $gameName = ($gameType == 'ff') ? 'FREE FIRE' : 'FREE FIRE MAX';
                    $currentExp = date('d-m-Y H:i:s', strtotime($userData['expDate']));
                    
                    // Calculate new exp date
                    if (strtotime($userData['expDate']) < time()) {
                        $newExpDate = date('d-m-Y H:i:s', strtotime("+$duration days"));
                    } else {
                        $newExpDate = date('d-m-Y H:i:s', strtotime($userData['expDate'] . " +$duration days"));
                    }
                    
                    $message = "💳 <b>EXTEND $gameName</b>\n\n";
                    $message .= "Licence: <code>$licence</code>\n";
                    $message .= "Jenis: <b>$gameName</b>\n";
                    $message .= "Durasi: <b>$duration Hari</b>\n";
                    $message .= "Harga: <b>Rp " . number_format($amount, 0, ',', '.') . "</b>\n";
                    $message .= "Masa Aktif Saat Ini: <b>$currentExp WIB</b>\n";
                    $message .= "Masa Aktif Baru: <b>$newExpDate WIB</b>\n";
                    $message .= "Order ID: <code>$orderId</code>\n\n";
                    $message .= "📱 <b>INSTRUKSI PEMBAYARAN:</b>\n";
                    $message .= "1. Scan QR Code di bawah\n";
                    $message .= "2. Bayar sesuai amount\n";
                    $message .= "3. Pembayaran akan terdeteksi otomatis\n\n";
                    $message .= "⏰ <b>Batas Waktu: 25 MENIT</b>\n";
                    $message .= "🔄 <b>Cek Otomatis: Setiap 20 detik</b>\n";
                    $message .= "QR akan otomatis terhapus setelah 25 menit jika tidak bayar\n";
                    $message .= "Expired: " . $paymentData['expired'] . "\n\n";
                    $message .= "🚀 <b>Pembayaran akan diproses otomatis!</b>";
                    
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔍 Cek Status Manual', 'callback_data' => 'check_extend']
                            ],
                            [
                                ['text' => '❌ Batalkan Pesanan', 'callback_data' => 'cancel_order']
                            ]
                        ]
                    ];
                    
                    // Store licence in manual_username field for compatibility
                    savePendingOrder($orderId, $chatId, $gameType, $duration, $amount, $paymentData['kode_deposit'], 'extend', $licence, '');
                    clearUserState($chatId);
                    
                    sendPhoto($chatId, $paymentData['link_qr'], $message, json_encode($keyboard));
                } else {
                    editMessageSmart($chatId, $messageId, "❌ Gagal membuat pembayaran extend. Silakan coba lagi.", getBackButton('extend_user'));
                    clearUserState($chatId);
                }
            }
        }
        // ==================== FIXED POINT REDEMPTION CALLBACKS ====================
        elseif (strpos($data, 'redeem_') === 0 && is_numeric(str_replace('redeem_', '', $data))) {
            $duration = str_replace('redeem_', '', $data);
            processPointRedemption($chatId, $duration, $messageId);
        }
        elseif ($data == 'redeem_ff' || $data == 'redeem_ffmax') {
            $gameType = ($data == 'redeem_ff') ? 'ff' : 'ffmax';
            $userState = getUserState($chatId);
            
            if ($userState && $userState['state'] == 'waiting_redeem_game') {
                $duration = $userState['data']['duration'];
                $pointsNeeded = $userState['data']['points_needed'];
                
                logMessage("DEBUG: Complete redemption - Game: $gameType, Duration: $duration, Chat: $chatId");
                
                completePointRedemption($chatId, $gameType, $duration, $messageId);
                clearUserState($chatId);
            } else {
                editMessageSmart($chatId, $messageId, "❌ <b>Sesi telah berakhir!</b>\n\nSilakan mulai ulang dari menu penukaran point.", getBackButton('redeem_points'));
            }
        }
        // ==================== END FIXED POINT REDEMPTION ====================
        elseif ($data == 'check_payment') {
            $order = getPendingOrder($chatId);
            
            if ($order) {
                $orderTime = strtotime($order['created_at']);
                $currentTime = time();
                $timeDiff = $currentTime - $orderTime;
                
                if ($timeDiff > ORDER_TIMEOUT) {
                    updateOrderStatus($order['deposit_code'], 'expired');
                    editMessageSmart($chatId, $messageId, "❌ <b>Pesanan telah expired!</b>\n\nPembayaran tidak dilakukan dalam waktu 25 menit.\n\nSilakan buat pesanan baru.", getBackButton('new_order'));
                    exit;
                }
                
                $paymentStatus = checkPaymentStatus($order['deposit_code']);
                
                if ($paymentStatus) {
                    processSuccessfulPayment($chatId, $messageId, $order);
                } else {
                    $remainingTime = ORDER_TIMEOUT - $timeDiff;
                    $remainingMinutes = floor($remainingTime / 60);
                    $remainingSeconds = $remainingTime % 60;
                    
                    $statusMessage = "⏳ <b>Status Pembayaran: PENDING</b>\n\n";
                    $statusMessage .= "Pembayaran Anda masih dalam proses.\n\n";
                    $statusMessage .= "⏰ <b>Sisa Waktu:</b> {$remainingMinutes}m {$remainingSeconds}s\n";
                    $statusMessage .= "🔄 <b>Cek otomatis setiap 20 detik</b>\n\n";
                    $statusMessage .= "Silakan tunggu beberapa saat dan coba lagi.";
                    
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔄 Cek Lagi', 'callback_data' => 'check_payment']
                            ],
                            [
                                ['text' => '❌ Batalkan', 'callback_data' => 'cancel_order']
                            ]
                        ]
                    ];
                    
                    editMessageSmart($chatId, $messageId, $statusMessage, json_encode($keyboard));
                }
            } else {
                editMessageSmart($chatId, $messageId, "❌ Tidak ada pesanan pending ditemukan.", getBackButton('new_order'));
            }
        }
        elseif ($data == 'check_extend') {
            $order = getPendingOrder($chatId);
            
            if ($order && $order['key_type'] == 'extend') {
                $orderTime = strtotime($order['created_at']);
                $currentTime = time();
                $timeDiff = $currentTime - $orderTime;
                
                if ($timeDiff > ORDER_TIMEOUT) {
                    updateOrderStatus($order['deposit_code'], 'expired');
                    editMessageSmart($chatId, $messageId, "❌ <b>Pesanan extend telah expired!</b>\n\nPembayaran tidak dilakukan dalam waktu 25 menit.", getBackButton('extend_user'));
                    exit;
                }
                
                $paymentStatus = checkPaymentStatus($order['deposit_code']);
                
                if ($paymentStatus) {
                    processSuccessfulPayment($chatId, $messageId, $order);
                } else {
                    $remainingTime = ORDER_TIMEOUT - $timeDiff;
                    $remainingMinutes = floor($remainingTime / 60);
                    $remainingSeconds = $remainingTime % 60;
                    
                    $statusMessage = "⏳ <b>Status Extend: PENDING</b>\n\n";
                    $statusMessage .= "Pembayaran extend masih dalam proses.\n\n";
                    $statusMessage .= "⏰ <b>Sisa Waktu:</b> {$remainingMinutes}m {$remainingSeconds}s\n";
                    $statusMessage .= "🔄 <b>Cek otomatis setiap 20 detik</b>\n\n";
                    $statusMessage .= "Silakan tunggu beberapa saat dan coba lagi.";
                    
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔄 Cek Lagi', 'callback_data' => 'check_extend']
                            ],
                            [
                                ['text' => '❌ Batalkan', 'callback_data' => 'cancel_order']
                            ]
                        ]
                    ];
                    
                    editMessageSmart($chatId, $messageId, $statusMessage, json_encode($keyboard));
                }
            }
        }
        elseif ($data == 'cancel_order') {
            $order = getPendingOrder($chatId);
            if ($order) {
                updateOrderStatus($order['deposit_code'], 'cancelled');
            }
            clearUserState($chatId);
            editMessageSmart($chatId, $messageId, "❌ Pesanan dibatalkan.", getBackButton());
        }
        
    } catch (Exception $e) {
        logMessage("Error processing callback: " . $e->getMessage());
        sendMessageWithImage($chatId, "❌ <b>Terjadi kesalahan!</b>\n\n Silakan coba lagi atau gunakan menu /start", getBackButton());
    }
}

logMessage("Update processing completed for $chatId");
echo "OK";
?>