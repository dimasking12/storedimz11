<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'web_pending_orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'order_id', 'user_id', 'game_type', 'duration', 'amount',
        'deposit_code', 'key_type', 'licence', 'qr_url', 'expired_at',
        'needs_fulfillment', 'points_earned', 'status',
    ];

    public function findByOrderId(string $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertPending(array $data): bool
    {
        $data['status'] = $data['status'] ?? 'pending';
        return (bool) $this->insert($data);
    }

    public function markExpired(string $orderId): bool
    {
        return $this->where('order_id', $orderId)->set(['status' => 'expired'])->update();
    }

    /**
     * Proses fulfillment setelah pembayaran sukses.
     * Return licence yang harus ditampilkan ke user, atau null jika gagal.
     *
     * @param array<string, mixed> $order
     */
    public function fulfill(array $order): ?string
    {
        $gameTable = $order['game_type'] === 'ff' ? 'freefire' : 'ffmax';
        $licence   = $order['licence'];

        if ($order['needs_fulfillment'] == 1 && ! empty($order['licence']) && $order['licence'] !== 'PENDING_ADMIN') {
            // Manual delivery: simpan licence yang user input ke tabel game
            if (! $this->saveLicense($gameTable, $order['licence'], $order['duration'])) {
                return null;
            }
        } elseif ($order['key_type'] === 'extend') {
            if (! $this->extendLicense($gameTable, $licence, $order['duration'])) {
                return null;
            }
        } else {
            // Auto delivery: generate random key
            $licence = $this->generateRandomLicence();
            if (! $this->saveLicense($gameTable, $licence, $order['duration'])) {
                return null;
            }
        }

        // Update status order
        $this->where('order_id', $order['order_id'])->set([
            'status'  => 'completed',
            'licence' => $licence,
        ])->update();

        return $licence;
    }

    private function generateRandomLicence(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $licence = '';
        for ($i = 0; $i < 2; $i++) {
            $licence .= $letters[random_int(0, 25)];
        }
        for ($i = 0; $i < 2; $i++) {
            $licence .= $numbers[random_int(0, 9)];
        }
        return $licence;
    }

    private function saveLicense(string $table, string $licence, string $duration): bool
    {
        $merchant = env('payment.merchantCode', 'DIMZ1945');

        return $this->db->table($table)->insert([
            'licence'    => $licence,
            'duration'   => (int) $duration,
            'merchant'   => $merchant,
            'status'     => 1,
            'uuid'       => null,
            'created_at' => date('Y-m-d H:i:s'),
            'expired_at' => date('Y-m-d H:i:s', strtotime("+{$duration} days")),
        ]);
    }

    private function extendLicense(string $table, string $licence, string $duration): bool
    {
        $row = $this->db->table($table)->where('licence', $licence)->get()->getRowArray();
        if (! $row) {
            return false;
        }

        $base       = strtotime($row['expired_at']) > time() ? strtotime($row['expired_at']) : time();
        $newExpired = date('Y-m-d H:i:s', strtotime("+{$duration} days", $base));

        return $this->db->table($table)
                        ->where('licence', $licence)
                        ->update(['expired_at' => $newExpired, 'status' => 1]);
    }
}
