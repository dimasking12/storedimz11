<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Store Controller - halaman utama License Store.
 *
 * Halaman ini hanya merender HTML (data produk diambil via AJAX ke StoreApi).
 */
class Store extends BaseController
{
    public function index()
    {
        $userId = $this->currentUserId();
        $user   = null;

        if ($userId) {
            $user = (new UserModel())->find($userId);
        }

        return view('store/index', [
            'isGuest'   => $userId === null,
            'user'      => $user,
            'isAdmin'   => $this->isAdmin(),
            'csrfName'  => csrf_token(),
            'csrfHash'  => csrf_hash(),
        ]);
    }
}
