<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter: pastikan user sudah login (cek $_SESSION['user_id']
 * yang di-set legacy login.php).
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('legacy_session');
        legacy_session_start();

        if (! isset($_SESSION['user_id'])) {
            // Untuk request JSON, balas 401; selain itu redirect ke /login.
            if ($request->isAJAX() || $request->getHeaderLine('Accept') === 'application/json') {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['success' => false, 'message' => 'Unauthorized']);
            }

            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
