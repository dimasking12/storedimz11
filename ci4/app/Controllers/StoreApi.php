<?php

namespace App\Controllers;

use App\Libraries\PaymentService;
use App\Libraries\TelegramNotifier;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\VoucherModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * StoreApi - JSON endpoint untuk modul Store.
 * Migrasi dari /api/store.php legacy.
 *
 * Semua mutasi (POST) sudah dilindungi CSRF Filter (lihat Config/Filters.php).
 * Auth filter di Routes.php memastikan endpoint create-* hanya bisa dipakai
 * user yang sudah login.
 */
class StoreApi extends BaseController
{
    /**
     * GET store/api/products?game=ff|ffmax
     */
    public function products(): ResponseInterface
    {
        $game     = $this->request->getGet('game');
        $products = (new ProductModel())->listActive($game);

        return $this->response->setJSON($products);
    }

    /**
     * POST store/api/validate-voucher
     * body: code=ABCDEFG
     */
    public function validateVoucher(): ResponseInterface
    {
        $code    = strtoupper((string) $this->request->getPost('code'));
        $voucher = (new VoucherModel())->findUsableByCode($code);

        if (! $voucher) {
            return $this->jsonError('Kode voucher tidak valid.');
        }

        if ($voucher['current_uses'] >= $voucher['max_uses']) {
            return $this->jsonError('Voucher sudah mencapai batas penggunaan.');
        }

        return $this->jsonOk(['discount' => (int) $voucher['discount_percent']]);
    }

    /**
     * POST store/api/create-order
     */
    public function createOrder(): ResponseInterface
    {
        $userId = $this->currentUserId();
        if (! $userId) {
            return $this->jsonError('Unauthorized', 401);
        }

        if ($this->isPaymentMaintenance()) {
            return $this->jsonError(
                'Sistem pembayaran ditutup sementara (23:50 - 00:15 WIB) untuk proses rekap data. Silakan coba lagi nanti.'
            );
        }

        $productId    = (int) $this->request->getPost('product_id');
        $type         = (string) ($this->request->getPost('type') ?? 'random');
        $licenceInput = trim((string) $this->request->getPost('licence'));
        $deliveryType = (string) ($this->request->getPost('delivery_type') ?? 'auto');
        $voucherCode  = strtoupper((string) $this->request->getPost('voucher_code'));

        $productModel = new ProductModel();
        $product      = $productModel->find($productId);

        if (! $product) {
            return $this->jsonError('Product not found', 404);
        }

        // Manual delivery: pastikan licence yg user input belum dipakai.
        if ($deliveryType === 'manual' && $licenceInput !== '') {
            $gameTable = $product['game_type'] === 'ff' ? 'freefire' : 'ffmax';
            if ($productModel->licenceExists($licenceInput, $gameTable)) {
                return $this->jsonError('License already exists! Please use a unique key.');
            }
        }

        // Diskon voucher
        $amount = (int) $product['price'];
        if ($voucherCode !== '') {
            $voucher = (new VoucherModel())->consume($voucherCode);
            if ($voucher) {
                $amount = (int) round($amount * (100 - $voucher['discount_percent']) / 100);
            }
        }

        $orderId = $this->generateOrderId('WS');

        try {
            $payment = (new PaymentService())->createPayment($orderId, $amount);
        } catch (Throwable $e) {
            log_message('error', '[Store] payment gateway error: ' . $e->getMessage());
            return $this->jsonError('Payment gateway error');
        }

        if (! $payment || empty($payment['status'])) {
            return $this->jsonError('Payment gateway error');
        }

        $orderModel = new OrderModel();
        $orderModel->insertPending([
            'order_id'          => $orderId,
            'user_id'           => $userId,
            'game_type'         => $product['game_type'],
            'duration'          => (string) $product['duration'],
            'amount'            => $amount,
            'deposit_code'      => $payment['data']['kode_deposit'],
            'key_type'          => $type,
            'licence'           => $licenceInput,
            'qr_url'            => $payment['data']['link_qr'],
            'expired_at'        => date('Y-m-d H:i:s', strtotime('+6 hours')),
            'needs_fulfillment' => $deliveryType === 'manual' ? 1 : 0,
            'points_earned'     => (int) ($product['points_reward'] ?? 0),
        ]);

        return $this->jsonOk([
            'order_id' => $orderId,
            'qr_url'   => $payment['data']['link_qr'],
            'amount'   => $amount,
        ]);
    }

    /**
     * POST store/api/create-extend
     */
    public function createExtendOrder(): ResponseInterface
    {
        $userId = $this->currentUserId();
        if (! $userId) {
            return $this->jsonError('Unauthorized', 401);
        }
        if ($this->isPaymentMaintenance()) {
            return $this->jsonError('Sistem pembayaran ditutup sementara (23:50 - 00:15 WIB).');
        }

        $licence  = trim((string) $this->request->getPost('licence'));
        $gameType = trim((string) $this->request->getPost('game_type'));
        $duration = trim((string) $this->request->getPost('duration'));
        $voucher  = strtoupper((string) $this->request->getPost('voucher_code'));

        if ($licence === '' || $gameType === '' || $duration === '') {
            return $this->jsonError('Data tidak lengkap (licence/game_type/duration kosong).');
        }

        $productModel = new ProductModel();
        $product      = $productModel->findByGameDuration($gameType, $duration);
        if (! $product) {
            return $this->jsonError("Produk tidak ditemukan (game={$gameType}, duration={$duration}).");
        }

        if (! $productModel->licenceExists($licence, $gameType === 'ff' ? 'freefire' : 'ffmax')) {
            return $this->jsonError('Licence not found');
        }

        $amount = (int) $product['price'];
        if ($voucher !== '') {
            $row = (new VoucherModel())->consume($voucher);
            if ($row) {
                $amount = (int) round($amount * (100 - $row['discount_percent']) / 100);
            }
        }

        $orderId = $this->generateOrderId('EX');

        try {
            $payment = (new PaymentService())->createPayment($orderId, $amount);
        } catch (Throwable $e) {
            log_message('error', '[Store] extend payment gateway error: ' . $e->getMessage());
            return $this->jsonError('Payment gateway error');
        }

        if (! $payment || empty($payment['status'])) {
            return $this->jsonError('Payment gateway error');
        }

        (new OrderModel())->insertPending([
            'order_id'          => $orderId,
            'user_id'           => $userId,
            'game_type'         => $gameType,
            'duration'          => $duration,
            'amount'            => $amount,
            'deposit_code'      => $payment['data']['kode_deposit'],
            'key_type'          => 'extend',
            'licence'           => $licence,
            'qr_url'            => $payment['data']['link_qr'],
            'expired_at'        => date('Y-m-d H:i:s', strtotime('+6 hours')),
            'needs_fulfillment' => 0,
            'points_earned'     => $this->calculatePointsForDuration($duration),
        ]);

        return $this->jsonOk([
            'order_id' => $orderId,
            'qr_url'   => $payment['data']['link_qr'],
            'amount'   => $amount,
        ]);
    }

    /**
     * GET store/api/check-status?order_id=WS...
     */
    public function checkStatus(): ResponseInterface
    {
        $orderId = (string) $this->request->getGet('order_id');
        if ($orderId === '') {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->findByOrderId($orderId);

        if (! $order) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        if ($order['status'] === 'completed') {
            return $this->response->setJSON([
                'status'  => 'paid',
                'licence' => $order['needs_fulfillment'] == 1 ? 'PENDING_ADMIN' : $order['licence'],
            ]);
        }

        // Cek ke gateway
        try {
            $paid = (new PaymentService())->checkPaymentStatus($order['deposit_code']);
        } catch (Throwable $e) {
            log_message('error', '[Store] checkStatus error: ' . $e->getMessage());
            $paid = false;
        }

        if (! $paid) {
            // Expired?
            if (strtotime($order['expired_at']) < time()) {
                $orderModel->markExpired($orderId);
                return $this->response->setJSON(['status' => 'expired']);
            }
            return $this->response->setJSON(['status' => 'pending']);
        }

        // Pembayaran berhasil - proses fulfillment
        $licence = $orderModel->fulfill($order);

        if ($licence !== null) {
            // Reward points
            (new UserModel())->addPoints((int) $order['user_id'], (int) $order['points_earned']);

            // Notifikasi Telegram
            try {
                $username = (new UserModel())->getUsername((int) $order['user_id']) ?? "User #{$order['user_id']}";
                (new TelegramNotifier())->sendOrderSuccess($order, $username, $licence);
            } catch (Throwable $e) {
                log_message('warning', '[Store] telegram notify failed: ' . $e->getMessage());
            }

            return $this->response->setJSON(['status' => 'paid', 'licence' => $licence]);
        }

        return $this->response->setJSON(['status' => 'pending']);
    }

    // ----------------- helpers -----------------

    private function isPaymentMaintenance(): bool
    {
        $now = date('H:i');
        return ($now >= '23:50' || $now <= '00:15');
    }

    private function generateOrderId(string $prefix): string
    {
        return $prefix . date('YmdHis') . random_int(100, 999);
    }

    private function calculatePointsForDuration(string $duration): int
    {
        $rules = [
            '1' => 1, '2' => 1, '3' => 2, '4' => 3, '5' => 4,
            '6' => 4, '7' => 5, '8' => 5, '10' => 6, '15' => 8,
            '20' => 10, '30' => 15,
        ];
        return $rules[$duration] ?? 0;
    }
}
