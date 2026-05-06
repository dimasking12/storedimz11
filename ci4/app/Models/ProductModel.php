<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'name', 'game_type', 'duration', 'price',
        'points_reward', 'status',
    ];

    /**
     * Ambil produk aktif (status = 1), opsional filter game_type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listActive(?string $game = null): array
    {
        $builder = $this->where('status', 1);

        if ($game) {
            $builder = $builder->where('game_type', $game);
        }

        return $builder->orderBy('CAST(duration AS UNSIGNED)', 'ASC')->findAll();
    }

    public function findByGameDuration(string $game, string $duration): ?array
    {
        return $this->where('game_type', $game)
                    ->where('duration', $duration)
                    ->where('status', 1)
                    ->first();
    }

    /**
     * Cek apakah licence sudah dipakai di tabel game (freefire / ffmax).
     * Memakai prepared statement via Query Builder (anti SQL injection).
     */
    public function licenceExists(string $licence, string $gameTable): bool
    {
        if (! in_array($gameTable, ['freefire', 'ffmax'], true)) {
            return false;
        }

        $count = $this->db->table($gameTable)
                          ->where('licence', $licence)
                          ->countAllResults();

        return $count > 0;
    }
}
