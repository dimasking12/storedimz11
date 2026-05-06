<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController menyediakan helper umum:
 *  - currentUserId() : ambil user_id dari sesi PHP legacy
 *  - jsonError()/jsonOk() : helper response JSON konsisten
 *
 * Semua controller HARUS extend kelas ini.
 */
abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    protected $helpers = ['url', 'form', 'legacy_session'];

    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Pastikan sesi PHP legacy aktif (helper dari legacy_session_helper).
        legacy_session_start();

        $this->session = session();
    }

    /**
     * Ambil user_id dari $_SESSION['user_id'] (sesi legacy PHP).
     */
    protected function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Apakah user sekarang admin (sesuai legacy isWebAdmin()).
     */
    protected function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    protected function jsonOk(array $data = [], int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge(['success' => true], $data));
    }

    protected function jsonError(string $message, int $status = 400, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra));
    }
}
