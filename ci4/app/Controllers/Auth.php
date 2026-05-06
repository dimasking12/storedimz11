<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Login page
     */
    public function login()
    {
        // Already logged in? Redirect to dashboard
        if (session()->get('user_id')) {
            return redirect()->to('/ci4/public/dashboard');
        }

        return view('auth/login', [
            'title' => 'Login - DIMZSTORE',
            'csrf_token' => csrf_hash(),
        ]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/ci4/public/auth/login');
    }

    /**
     * Profile page
     */
    public function profile()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/ci4/public/auth/login');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            session()->destroy();
            return redirect()->to('/ci4/public/auth/login');
        }

        return view('auth/profile', [
            'title' => 'My Profile - DIMZSTORE',
            'user' => $user,
            'isAdmin' => ($user['role'] === 'admin'),
        ]);
    }

    /**
     * API: Handle login
     */
    public function doLogin(): ResponseInterface
    {
        // CSRF validation
        if (!$this->validate(['csrf_token' => 'required'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
        }

        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';

        if (empty($username) || empty($password)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username dan password wajib diisi!'
            ]);
        }

        $user = $this->userModel->where('username', $username)->first();

        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username tidak ditemukan!'
            ]);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password salah!'
            ]);
        }

        // Set session
        session()->set([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'logged_in' => true,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Selamat datang kembali, ' . esc($user['username']) . '!',
            'role' => $user['role'],
        ]);
    }

    /**
     * API: Handle register
     */
    public function doRegister(): ResponseInterface
    {
        // CSRF validation
        if (!$this->validate(['csrf_token' => 'required'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
        }

        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';
        $waNumber = trim($this->request->getPost('wa_number') ?? '');
        $telegramUser = trim($this->request->getPost('telegram_user') ?? '');

        // Validation
        if (empty($username) || empty($password) || empty($waNumber) || empty($telegramUser)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Semua field wajib diisi!'
            ]);
        }

        if (strlen($username) < 4) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username minimal 4 karakter!'
            ]);
        }

        if (strlen($password) < 6) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password minimal 6 karakter!'
            ]);
        }

        // Check if username exists
        if ($this->userModel->where('username', $username)->first()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username sudah digunakan, coba yang lain!'
            ]);
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $userId = $this->userModel->insert([
            'username' => $username,
            'password' => $hashedPassword,
            'wa_number' => $waNumber,
            'telegram_user' => $telegramUser,
            'role' => 'user',
            'points' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Registrasi gagal. Coba lagi nanti.'
            ]);
        }

        // Auto login after register
        session()->set([
            'user_id' => $userId,
            'username' => $username,
            'role' => 'user',
            'logged_in' => true,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Registrasi berhasil! Selamat datang, ' . esc($username) . '!'
        ]);
    }
}
