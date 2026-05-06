<?php
/**
 * --------------------------------------------------------------------
 *  CodeIgniter 4 Front Controller - DIMZSTORE
 * --------------------------------------------------------------------
 * Semua request masuk ke file ini dan dirutekan oleh CI4.
 */

// Path ke folder yang harus diakses dari front controller
$pathsPath = realpath(FCPATH . '../app/Config/Paths.php');

// Jika tidak ada Paths.php, framework belum terinstall (`composer install`).
if ($pathsPath === false) {
    header('Content-Type: text/plain; charset=utf-8', true, 503);
    echo "CodeIgniter 4 belum terinstall.\n";
    echo "Jalankan 'composer install' di dalam folder /ci4/ terlebih dahulu.\n";
    exit;
}

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require $pathsPath;

$paths = new Config\Paths();

// Bootstrap CI4
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);
$app->run();
