<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table         = 'vouchers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['code', 'discount_percent', 'max_uses', 'current_uses'];

    public function findUsableByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Increment current_uses & return voucher row, atau null jika tidak valid.
     */
    public function consume(string $code): ?array
    {
        $row = $this->findUsableByCode($code);
        if (! $row) {
            return null;
        }
        if ((int) $row['current_uses'] >= (int) $row['max_uses']) {
            return null;
        }

        $this->where('id', $row['id'])
             ->set('current_uses', 'current_uses + 1', false)
             ->update();

        return $row;
    }
}
