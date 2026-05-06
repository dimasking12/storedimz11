<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Default landing untuk CI4 (legacy /dashboard tetap dipakai untuk root site).
$routes->get('/', 'Store::index');

// =====================================================================
// MODUL STORE (License Store) - Migrasi dari /store.php + /api/store.php
// =====================================================================
$routes->group('store', static function ($routes) {
    $routes->get('/', 'Store::index');

    // API JSON (dipanggil dari Javascript di view store)
    $routes->group('api', static function ($routes) {
        $routes->get('products',           'StoreApi::products');
        $routes->post('validate-voucher',  'StoreApi::validateVoucher');
        $routes->post('create-order',      'StoreApi::createOrder',      ['filter' => 'auth']);
        $routes->post('create-extend',     'StoreApi::createExtendOrder',['filter' => 'auth']);
        $routes->get('check-status',       'StoreApi::checkStatus');
    });
});

// Healthcheck (berguna utk monitoring uptime)
$routes->get('healthz', static function () {
    return service('response')->setJSON([
        'status' => 'ok',
        'time'   => date('c'),
    ]);
});
