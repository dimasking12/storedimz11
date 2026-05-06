<?php

namespace App\Libraries;

use Config\Services;

/**
 * TelegramNotifier: kirim notifikasi order ke admin via Telegram bot.
 * Mengganti sendOrderNotificationToTelegram() di config_web.php legacy.
 */
class TelegramNotifier
{
    private string $botToken;
    private string $chatId;

    public function __construct()
    {
        $this->botToken = (string) env('telegram.botToken', '');
        $this->chatId   = (string) env('telegram.adminChatId', '');
    }

    /**
     * @param array<string, mixed> $order
     */
    public function sendOrderSuccess(array $order, string $username, string $licence): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            return false;
        }

        $gameLabel   = strtoupper((string) ($order['game_type'] ?? '-'));
        $duration    = (string) ($order['duration'] ?? '-');
        $amount      = 'Rp ' . number_format((int) ($order['amount'] ?? 0), 0, ',', '.');
        $orderId     = (string) ($order['order_id'] ?? '-');
        $keyType     = ($order['key_type'] ?? '') === 'extend' ? 'Extend' : 'New Key';
        $needsFulfil = ! empty($order['needs_fulfillment']) && (int) $order['needs_fulfillment'] === 1
                       ? 'MANUAL (Perlu Diisi Admin)'
                       : 'Otomatis';
        $waktu       = date('d/m/Y H:i:s');

        $text = "<b>ORDER SUKSES</b>\n"
              . "----------------------------\n"
              . "<b>User:</b> " . esc($username) . "\n"
              . "<b>Game:</b> {$gameLabel}\n"
              . "<b>Durasi:</b> {$duration} hari\n"
              . "<b>Nominal:</b> {$amount}\n"
              . "<b>Tipe:</b> {$keyType}\n"
              . "<b>Delivery:</b> {$needsFulfil}\n"
              . "<b>Licence:</b> <code>" . esc($licence) . "</code>\n"
              . "<b>Order ID:</b> <code>{$orderId}</code>\n"
              . "<b>Waktu:</b> {$waktu}";

        return $this->send($text);
    }

    private function send(string $text): bool
    {
        $client = Services::curlrequest([
            'timeout'         => 8,
            'connect_timeout' => 5,
        ]);

        try {
            $response = $client->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'form_params' => [
                        'chat_id'                  => $this->chatId,
                        'text'                     => $text,
                        'parse_mode'               => 'HTML',
                        'disable_web_page_preview' => true,
                    ],
                    'http_errors' => false,
                ]
            );
        } catch (\Throwable $e) {
            log_message('warning', '[TelegramNotifier] send failed: ' . $e->getMessage());
            return false;
        }

        $data = json_decode((string) $response->getBody(), true);
        return is_array($data) && ! empty($data['ok']);
    }
}
