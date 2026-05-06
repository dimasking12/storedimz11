<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\VoucherModel;
use App\Models\NewsModel;
use App\Models\RentalModel;
use App\Models\SettingsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Admin extends BaseController
{
    protected ProductModel $productModel;
    protected UserModel $userModel;
    protected OrderModel $orderModel;
    protected VoucherModel $voucherModel;
    protected NewsModel $newsModel;
    protected RentalModel $rentalModel;
    protected SettingsModel $settingsModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
        $this->voucherModel = new VoucherModel();
        $this->newsModel = new NewsModel();
        $this->rentalModel = new RentalModel();
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Admin Dashboard
     */
    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/ci4/public/auth/login');
        }

        return view('admin/index', [
            'title' => 'Admin Hub - DIMZSTORE',
            'csrf_token' => csrf_hash(),
        ]);
    }

    // ==================== PRODUCTS ====================

    public function addProduct(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $data = [
            'game_type' => $this->request->getPost('game_type'),
            'name' => $this->request->getPost('name'),
            'duration' => (int) $this->request->getPost('duration'),
            'price' => (int) $this->request->getPost('price'),
            'points_reward' => (int) $this->request->getPost('points_reward'),
            'delivery_type' => $this->request->getPost('delivery_type') ?? 'auto',
        ];

        if ($this->productModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Product added successfully']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to add product']);
    }

    public function deleteProduct(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $id = $this->request->getPost('id');
        if ($this->productModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function getProducts(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([]);
        }

        $products = $this->productModel->findAll();
        return $this->response->setJSON($products);
    }

    // ==================== VOUCHERS ====================

    public function addVoucher(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $code = strtoupper(trim($this->request->getPost('code') ?? ''));
        $discountPercent = (int) $this->request->getPost('discount_percent');
        $maxUses = (int) $this->request->getPost('max_uses');

        if (empty($code) || $discountPercent <= 0 || $maxUses <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid voucher data']);
        }

        // Check if voucher exists
        if ($this->voucherModel->where('code', $code)->first()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Voucher code already exists']);
        }

        $data = [
            'code' => $code,
            'discount_percent' => $discountPercent,
            'max_uses' => $maxUses,
            'used_count' => 0,
        ];

        if ($this->voucherModel->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to create voucher']);
    }

    public function deleteVoucher(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $id = $this->request->getPost('id');
        if ($this->voucherModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function getVouchers(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([]);
        }

        $vouchers = $this->voucherModel->orderBy('created_at', 'DESC')->findAll();
        return $this->response->setJSON($vouchers);
    }

    // ==================== NEWS ====================

    public function addNews(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $title = $this->request->getPost('title');
        $content = $this->request->getPost('content');
        $type = $this->request->getPost('type') ?? 'info';
        $imageUrl = null;
        $fileUrl = null;
        $fileName = null;

        // Handle Image Upload
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower($image->getExtension());
            if (in_array($ext, $allowedImages)) {
                $newName = 'news_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $image->move(ROOTPATH . '../img/news/', $newName);
                $imageUrl = 'img/news/' . $newName;
            }
        }

        // Handle File Upload
        $attachment = $this->request->getFile('attachment');
        if ($attachment && $attachment->isValid() && !$attachment->hasMoved()) {
            $allowedFiles = ['zip', 'rar', 'pdf', 'txt'];
            $ext = strtolower($attachment->getExtension());
            if (in_array($ext, $allowedFiles)) {
                $originalName = $attachment->getClientName();
                $newName = 'file_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $attachment->move(ROOTPATH . '../uploads/news/', $newName);
                $fileUrl = 'uploads/news/' . $newName;
                $fileName = $originalName;
            }
        }

        $data = [
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'image_url' => $imageUrl,
            'file_url' => $fileUrl,
            'file_name' => $fileName,
        ];

        if ($this->newsModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'News posted successfully']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to post news']);
    }

    public function deleteNews(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $id = $this->request->getPost('id');
        if ($this->newsModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function getNews(): ResponseInterface
    {
        $news = $this->newsModel->orderBy('created_at', 'DESC')->findAll();
        return $this->response->setJSON($news);
    }

    // ==================== USERS ====================

    public function getUsers(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([]);
        }

        $users = $this->userModel->select('id, username, wa_number, telegram_user, role, points, created_at')
            ->orderBy('created_at', 'DESC')
            ->findAll();
        return $this->response->setJSON($users);
    }

    public function deleteUser(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $id = $this->request->getPost('id');
        if ($this->userModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function updateUserPassword(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = $this->request->getPost('user_id');
        $newPassword = $this->request->getPost('new_password');

        if (!$userId || !$newPassword) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing data']);
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->userModel->update($userId, ['password' => $hashed])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Password updated successfully']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to update password']);
    }

    // ==================== RENTALS ====================

    public function addRental(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $imageUrl = null;

        // Handle Image Upload
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower($image->getExtension());
            if (in_array($ext, $allowedImages)) {
                $newName = 'rental_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $image->move(ROOTPATH . '../img/news/', $newName);
                $imageUrl = 'img/news/' . $newName;
            }
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'price' => (int) $this->request->getPost('price'),
            'duration' => $this->request->getPost('duration'),
            'image_url' => $imageUrl,
            'email' => $this->request->getPost('email'),
            'backup_codes' => $this->request->getPost('backup_codes'),
            'status' => 'available',
        ];

        if ($this->rentalModel->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to add rental']);
    }

    public function deleteRental(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $id = $this->request->getPost('id');
        if ($this->rentalModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function getRentals(): ResponseInterface
    {
        $rentals = $this->rentalModel->orderBy('created_at', 'DESC')->findAll();
        return $this->response->setJSON($rentals);
    }

    // ==================== ORDERS ====================

    public function fulfillOrder(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $orderId = $this->request->getPost('order_id');
        $licence = $this->request->getPost('licence');

        $result = $this->orderModel->where('order_id', $orderId)
            ->set(['licence' => $licence, 'needs_fulfillment' => 0])
            ->update();

        if ($result) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function getTransactions(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([]);
        }

        $orders = $this->orderModel->getAllHistory();
        return $this->response->setJSON($orders);
    }

    // ==================== NOTIFICATIONS ====================

    public function getNotifSettings(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false]);
        }

        return $this->response->setJSON([
            'success' => true,
            'notif_bot_token' => $this->settingsModel->get('notif_bot_token', ''),
            'notif_chat_id' => $this->settingsModel->get('notif_chat_id', ''),
        ]);
    }

    public function saveNotifSettings(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $token = trim($this->request->getPost('notif_bot_token') ?? '');
        $chatId = trim($this->request->getPost('notif_chat_id') ?? '');

        if (empty($token) || empty($chatId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Token dan Chat ID tidak boleh kosong!']);
        }

        $this->settingsModel->set('notif_bot_token', $token);
        $this->settingsModel->set('notif_chat_id', $chatId);

        return $this->response->setJSON(['success' => true, 'message' => 'Pengaturan notifikasi berhasil disimpan!']);
    }

    public function testNotif(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $token = trim($this->request->getPost('notif_bot_token') ?? '');
        $chatId = trim($this->request->getPost('notif_chat_id') ?? '');

        if (empty($token) || empty($chatId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Masukkan Token dan Chat ID terlebih dahulu!']);
        }

        $text = "✅ <b>Test Notifikasi DIMZSTORE</b>\n\n"
              . "🎉 Koneksi berhasil!\n"
              . "Bot Token & Chat ID valid.\n\n"
              . "Notifikasi order sukses akan masuk ke sini setiap ada pembelian. 🚀";

        $telegram = new \App\Libraries\TelegramNotifier($token, $chatId);
        $result = $telegram->sendRaw($text);

        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => 'Pesan test berhasil dikirim! Cek Telegram kamu.']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengirim pesan. Periksa token & chat ID.']);
    }

    // ==================== POINT SETTINGS ====================

    public function getPointSettings(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([]);
        }

        $rules = $this->settingsModel->get('extend_point_rules', '{}');
        return $this->response->setJSON(json_decode($rules, true) ?: []);
    }

    public function savePointSettings(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $rules = $this->request->getPost('rules');
        $this->settingsModel->set('extend_point_rules', $rules);

        return $this->response->setJSON(['success' => true]);
    }

    // ==================== REVENUE STATS ====================

    public function getRevenueStats(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON(['success' => false]);
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

        $db = \Config\Database::connect();

        // Total Revenue & Sales
        $query = $db->query("
            SELECT COALESCE(SUM(amount), 0) as total_revenue, COUNT(*) as total_sales 
            FROM (
                SELECT amount, created_at FROM web_pending_orders WHERE status = 'completed'
                UNION ALL
                SELECT amount, created_at FROM web_rental_orders WHERE status = 'completed'
            ) AS comb
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$startDate, $endDate]);
        $summary = $query->getRowArray();

        // Daily Stats
        $dailyQuery = $db->query("
            SELECT DATE(created_at) as date, COALESCE(SUM(amount), 0) as daily_revenue, COUNT(*) as daily_sales 
            FROM (
                SELECT amount, created_at FROM web_pending_orders WHERE status = 'completed'
                UNION ALL
                SELECT amount, created_at FROM web_rental_orders WHERE status = 'completed'
            ) AS comb
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate]);
        $daily = $dailyQuery->getResultArray();

        // Game Breakdown
        $gameQuery = $db->query("
            SELECT game_type, COALESCE(SUM(amount), 0) as revenue 
            FROM (
                SELECT game_type, amount, created_at FROM web_pending_orders WHERE status = 'completed'
                UNION ALL
                SELECT 'RENTAL' as game_type, amount, created_at FROM web_rental_orders WHERE status = 'completed'
            ) AS comb
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY game_type
        ", [$startDate, $endDate]);
        $games = $gameQuery->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'summary' => [
                'total_revenue' => (int)($summary['total_revenue'] ?? 0),
                'total_sales' => (int)($summary['total_sales'] ?? 0),
            ],
            'daily' => $daily,
            'games' => $games,
        ]);
    }

    // ==================== HELPERS ====================

    protected function isAdmin(): bool
    {
        return session()->get('role') === 'admin';
    }
}
