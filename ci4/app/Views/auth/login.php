<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?= esc($csrf_token) ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-bolt"></i>
                </div>
                <h1 class="auth-title">DIMZ<span>STORE</span></h1>
                <p class="auth-subtitle">Premium License Management Hub</p>
            </div>

            <!-- Tab Switcher -->
            <div style="display: flex; gap: 10px; background: var(--bg-input); padding: 5px; border-radius: 12px; margin-bottom: 25px;">
                <button class="btn btn-block" id="tab-login" onclick="switchTab('login')" style="background: var(--accent); color: var(--bg-primary); padding: 10px;">LOGIN</button>
                <button class="btn btn-block" id="tab-register" onclick="switchTab('register')" style="background: transparent; color: var(--text-secondary); padding: 10px;">REGISTER</button>
            </div>

            <!-- LOGIN -->
            <div id="section-login" class="auth-form-content">
                <form id="login-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">USERNAME</label>
                        <div style="position: relative;">
                            <i class="fas fa-user" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                            <input type="text" class="form-control" name="username" id="login-username" placeholder="Enter your username" style="padding-left: 45px;" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PASSWORD</label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                            <input type="password" class="form-control" name="password" id="login-password" placeholder="••••••••" style="padding-left: 45px;" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="btn-login" style="margin-top: 10px;">
                        MASUK SEKARANG <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- REGISTER -->
            <div id="section-register" class="auth-form-content" style="display: none;">
                <form id="register-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">USERNAME</label>
                        <input type="text" class="form-control" name="username" id="reg-username" placeholder="Min. 4 chars" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PASSWORD</label>
                        <input type="password" class="form-control" name="password" id="reg-password" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">WHATSAPP</label>
                        <input type="text" class="form-control" name="wa_number" id="reg-wa" placeholder="0812..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">TELEGRAM</label>
                        <input type="text" class="form-control" name="telegram_user" id="reg-tg" placeholder="@username" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="btn-register" style="margin-top: 10px;">
                        DAFTAR AKUN <i class="fas fa-plus"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cookie Consent -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <strong>Pengaturan Cookie</strong>
            Kami menggunakan cookie untuk meningkatkan pengalaman Anda di panel DIMZSTORE. Apakah Anda mengizinkan penggunaan cookie?
        </div>
        <div class="cookie-actions">
            <button class="btn btn-primary cookie-btns" onclick="setCookieChoice(true)">IZINKAN</button>
            <button class="btn btn-ghost cookie-btns" style="border-color:var(--border);" onclick="setCookieChoice(false)">TOLAK</button>
        </div>
    </div>

    <script>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            background: '#1a1a26', color: '#fff'
        });

        const BASE_URL = '/ci4/public';

        function switchTab(tab) {
            const loginSection = document.getElementById('section-login');
            const registerSection = document.getElementById('section-register');
            const loginTab = document.getElementById('tab-login');
            const registerTab = document.getElementById('tab-register');

            if (tab === 'login') {
                loginSection.style.display = 'block';
                registerSection.style.display = 'none';
                loginTab.style.background = 'var(--accent)';
                loginTab.style.color = 'var(--bg-primary)';
                registerTab.style.background = 'transparent';
                registerTab.style.color = 'var(--text-secondary)';
            } else {
                loginSection.style.display = 'none';
                registerSection.style.display = 'block';
                loginTab.style.background = 'transparent';
                loginTab.style.color = 'var(--text-secondary)';
                registerTab.style.background = 'var(--accent)';
                registerTab.style.color = 'var(--bg-primary)';
            }
        }

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-login');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROSES...';

            const formData = new FormData(e.target);

            try {
                const resp = await fetch(BASE_URL + '/auth/do-login', { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    Toast.fire({ icon: 'success', title: 'Login sukses!' });
                    setTimeout(() => window.location.href = BASE_URL + '/dashboard', 1000);
                } else {
                    Toast.fire({ icon: 'error', title: data.message });
                    btn.disabled = false;
                    btn.innerHTML = 'MASUK SEKARANG <i class="fas fa-arrow-right"></i>';
                }
            } catch (err) {
                Toast.fire({ icon: 'error', title: 'Error koneksi!' });
                btn.disabled = false;
                btn.innerHTML = 'MASUK SEKARANG <i class="fas fa-arrow-right"></i>';
            }
        });

        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-register');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROSES...';

            const formData = new FormData(e.target);

            try {
                const resp = await fetch(BASE_URL + '/auth/do-register', { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Registrasi Berhasil!', text: 'Silakan login.', background: '#1a1a26', color: '#fff' });
                    switchTab('login');
                } else {
                    Toast.fire({ icon: 'error', title: data.message });
                }
                btn.disabled = false;
                btn.innerHTML = 'DAFTAR AKUN <i class="fas fa-plus"></i>';
            } catch (err) {
                Toast.fire({ icon: 'error', title: 'Error koneksi!' });
                btn.disabled = false;
                btn.innerHTML = 'DAFTAR AKUN <i class="fas fa-plus"></i>';
            }
        });
        
        // Cookie Logic
        let cookieInterval = null;
        let nudgeTimeout = null;

        function setCookieChoice(allowed) {
            localStorage.setItem('cookieConsent', allowed ? 'allowed' : 'declined');
            const banner = document.getElementById('cookieBanner');
            banner.classList.remove('active');
            
            clearInterval(cookieInterval);
            clearTimeout(nudgeTimeout);
            
            setTimeout(() => {
                if (localStorage.getItem('cookieConsent')) {
                    banner.style.display = 'none';
                }
            }, 600);

            if(allowed) {
                Toast.fire({ icon: 'success', title: 'Terima kasih telah mengizinkan!' });
            }
        }

        function showCookieNudge() {
            if (localStorage.getItem('cookieConsent')) {
                clearInterval(cookieInterval);
                return;
            }
            const banner = document.getElementById('cookieBanner');
            banner.classList.remove('active');
            
            nudgeTimeout = setTimeout(() => {
                if (!localStorage.getItem('cookieConsent')) {
                    banner.classList.add('active');
                }
            }, 100);
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('cookieConsent')) {
                setTimeout(showCookieNudge, 2000);
                cookieInterval = setInterval(showCookieNudge, 30000);
            } else {
                document.getElementById('cookieBanner').style.display = 'none';
            }
        });
    </script>
</body>
</html>
