<?php

namespace App\Models;

use CodeIgniter\Model;

class RentalOrderModel extends Model
{
    protected $table = 'web_rental_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'rental_id',
        'order_id',
        'amount',
        'status',
        'qr_url',
        'expire_time',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get user's rental history
     */
    public function getUserHistory(int $userId): array
    {
        return $this->select('web_rental_orders.*, web_rentals.title, web_rentals.duration, web_users.username')
            ->join('web_rentals', 'web_rentals.id = web_rental_orders.rental_id', 'left')
            ->join('web_users', 'web_users.id = web_rental_orders.user_id', 'left')
            ->where('web_rental_orders.user_id', $userId)
            ->orderBy('web_rental_orders.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get all rental history for admin
     */
    public function getAllHistory(): array
    {
        return $this->select('web_rental_orders.*, web_rentals.title, web_rentals.duration, web_users.username')
            ->join('web_rentals', 'web_rentals.id = web_rental_orders.rental_id', 'left')
            ->join('web_users', 'web_users.id = web_rental_orders.user_id', 'left')
            ->orderBy('web_rental_orders.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get pending order by order_id
     */
    public function getByOrderId(string $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }

    /**
     * Mark order as completed
     */
    public function markCompleted(string $orderId): bool
    {
        return $this->where('order_id', $orderId)
            ->set(['status' => 'completed'])
            ->update();
    }
}
