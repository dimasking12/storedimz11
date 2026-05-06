<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'web_users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['username', 'email', 'password', 'points', 'role'];

    /**
     * Tambahkan poin (untuk reward order sukses).
     */
    public function addPoints(int $userId, int $points): bool
    {
        if ($points <= 0) {
            return true;
        }

        return $this->db->table($this->table)
                        ->where('id', $userId)
                        ->set('points', 'points + ' . (int) $points, false)
                        ->update();
    }

    public function getUsername(int $userId): ?string
    {
        $row = $this->select('username')->find($userId);
        return $row['username'] ?? null;
    }
}
