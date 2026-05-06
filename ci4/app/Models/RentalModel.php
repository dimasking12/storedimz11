<?php

namespace App\Models;

use CodeIgniter\Model;

class RentalModel extends Model
{
    protected $table = 'web_rentals';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title',
        'description',
        'price',
        'duration',
        'image_url',
        'email',
        'backup_codes',
        'status',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get available rentals
     */
    public function getAvailable(): array
    {
        return $this->where('status', 'available')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Mark rental as rented
     */
    public function markAsRented(int $id): bool
    {
        return $this->update($id, ['status' => 'rented']);
    }

    /**
     * Mark rental as available
     */
    public function markAsAvailable(int $id): bool
    {
        return $this->update($id, ['status' => 'available']);
    }
}
