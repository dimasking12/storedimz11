<?php
require_once 'config_web.php';
$is_guest = !isset($_SESSION['user_id']);
$user = !$is_guest ? getWebUser($_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Dashboard - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .news-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; margin-bottom: 20px; transition: 0.3s; position: relative; overflow: hidden; }
        .news-card:hover { border-color: var(--accent); transform: translateY(-3px); }
        .news-banner { margin: -25px -25px 20px -25px; height: 180px; overflow: hidden; }
        .news-banner img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .news-card:hover .news-banner img { transform: scale(1.05); }
        .news-type { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 4px 12px; border-radius: 100px; margin-bottom: 12px; display: inline-block; }
        .type-info { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .type-update { background: rgba(0, 212, 170, 0.1); color: var(--accent); }
        .type-warning { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .news-title { font-size: 1.25rem; font-weight: 800; margin-bottom: 10px; color: white; }
        .news-content { color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; }
        .news-footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-muted); }
        .like-btn { display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.3s; padding: 6px 12px; border-radius: 100px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); }
        .like-btn:hover { background: rgba(255, 71, 87, 0.1); border-color: var(--danger); }
        .like-btn.active { background: rgba(255, 71, 87, 0.15); border-color: var(--danger); color: var(--danger); }
        .like-btn i { font-size: 1rem; }
        .status-sold { background: rgba(255, 71, 87, 0.2); color: var(--danger); font-weight: 800; border: 1px solid var(--danger); }
        
        /* Image Preview Modal */
        .image-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s ease; }
        .image-modal.active { display: flex; opacity: 1; }
        .image-modal-content { max-width: 95%; max-height: 90vh; border-radius: 16px; border: 2px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.5); transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .image-modal.active .image-modal-content { transform: scale(1); }
        .image-modal-close { position: absolute; top: 25px; right: 25px; font-size: 1.5rem; color: white; cursor: pointer; width: 45px; height: 45px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .image-modal-close:hover { background: var(--danger); transform: rotate(90deg); }
        .news-banner img { cursor: zoom-in; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-bolt"></i></div>
                <h1 class="brand-name">DIMZ<span>STORE</span></h1>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-header">Main Menu</div>
                <ul>
                    <li><a href="/dashboard" class="nav-link active"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="/store" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="/rental" class="nav-link"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                    <?php if(!$is_guest): ?>
                    <li><a href="/history" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                    <li><a href="/history_rental" class="nav-link"><i class="fas fa-clock-rotate-left"></i> <span>Riwayat Rental</span></a></li>
                    <li><a href="/redeem" class="nav-link"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                    <?php endif; ?>
                </ul>

                <div class="menu-header">Account Details</div>
                <ul>
                    <?php if(!$is_guest): ?>
                    <li><a href="/profile" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <?php endif; ?>
                    <li><a href="javascript:showSupportOptions()" class="nav-link"><i class="fas fa-headset"></i> <span>Technical Support</span></a></li>
                    <?php if(!$is_guest && isWebAdmin()): ?>
                    <li><a href="/admin" class="nav-link" style="color: #ffaa00;"><i class="fas fa-user-shield"></i> <span>Admin Control</span></a></li>
                    <?php endif; ?>
                    <?php if(!$is_guest): ?>
                    <li><a href="/logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                    <?php else: ?>
                    <li><a href="/login" class="nav-link" style="color: var(--accent);"><i class="fas fa-sign-in-alt"></i> <span>Login / Register</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;">
                    <div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div>
                    <h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1>
                </div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title">Announcements</h2>
                <div class="topbar-user">
                    <div class="user-info">
                        <span class="user-name"><?php echo $is_guest ? 'Guest User' : htmlspecialchars($user['username']); ?></span>
                        <span class="user-role"><?php echo $is_guest ? 'Guest' : 'Member'; ?></span>
                    </div>
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                </div>
            </header>

            <div class="page-content">
                <div class="card-header" style="margin-bottom: 2rem;">
                    <h3 class="card-title"><i class="fas fa-bullhorn"></i> LATEST UPDATES</h3>
                </div>

                <div id="news-container">
                    <!-- News loaded via JS -->
                    <div style="text-align:center; padding: 50px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Cookie Consent -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <strong>🍪 Pengaturan Cookie</strong>
            Kami menggunakan cookie untuk meningkatkan pengalaman Anda di panel DIMZSTORE. Apakah Anda mengizinkan penggunaan cookie?
        </div>
        <div class="cookie-actions">
            <button class="btn btn-primary cookie-btns" onclick="setCookieChoice(true)">IZINKAN</button>
            <button class="btn btn-ghost cookie-btns" style="border-color:var(--border);" onclick="setCookieChoice(false)">TOLAK</button>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <div class="image-modal-close"><i class="fas fa-times"></i></div>
        <img src="" id="modalImg" class="image-modal-content" onclick="event.stopPropagation()">
    </div>

    <script>
        const isGuest = <?php echo $is_guest ? 'true' : 'false'; ?>;
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            background: '#1a1a26', color: '#fff'
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function escapeHTML(str) {
            const p = document.createElement('p');
            p.textContent = str;
            return p.innerHTML;
        }

        async function loadNews() {
            const resp = await fetch('api/store.php?action=get_news');
            const news = await resp.json();
            const container = document.getElementById('news-container');
            container.innerHTML = '';

            if (!news.length) {
                container.innerHTML = '<div class="news-card"><p style="text-align:center; color:var(--text-muted);">No news available at the moment.</p></div>';
                return;
            }

            news.forEach(n => {
                const hasImage = n.image_url ? `<div class="news-banner" onclick="openImageModal('${n.image_url}')"><img src="${n.image_url}" alt="Banner" loading="lazy"></div>` : '';
                const hasFile = n.file_url ? `
                    <div style="margin-top: 15px;">
                        <a href="download.php?id=${n.id}" class="btn btn-ghost" style="font-size: 0.75rem; padding: 8px 15px; border-color: var(--accent); color: var(--accent);">
                            <i class="fas fa-download"></i> Unduh Lampiran
                        </a>
                    </div>
                ` : '';

                const card = `
                    <div class="news-card">
                        ${hasImage}
                        <div style="padding: ${n.image_url ? '0 5px 5px 5px' : '0'}">
                            <span class="news-type type-${n.type}">${n.type}</span>
                            <h4 class="news-title">${escapeHTML(n.title)}</h4>
                            <div class="news-content">${escapeHTML(n.content).replace(/\n/g, '<br>')}</div>
                            ${hasFile}
                            <div class="news-footer">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <span><i class="far fa-clock"></i> ${new Date(n.created_at).toLocaleString('id-ID', {day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit'})}</span>
                                    <div class="like-btn ${n.user_liked ? 'active' : ''}" onclick="toggleLike(this, ${n.id})">
                                        <i class="${n.user_liked ? 'fas' : 'far'} fa-heart"></i>
                                        <span class="like-count">${n.likes_count || 0}</span>
                                    </div>
                                </div>
                                <span>Official Update <i class="fas fa-check-circle" style="color:var(--accent);"></i></span>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += card;
            });
        }

        async function toggleLike(btn, newsId) {
            if (isGuest) {
                Swal.fire({
                    title: 'Login Required',
                    text: 'Silakan login untuk memberikan like pada pengumuman.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'LOGIN',
                    cancelButtonText: 'NANTI',
                    background: '#1a1a26', color: '#fff'
                }).then(r => { if(r.isConfirmed) window.location.href='login.php'; });
                return;
            }

            try {
                const fd = new FormData();
                fd.append('action', 'toggle_like_news');
                fd.append('news_id', newsId);
                fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                const resp = await fetch('api/store.php', { method: 'POST', body: fd });
                const text = await resp.text();
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    console.error('Server Response:', text);
                    Swal.fire('Error', 'Server memberikan respon tidak valid. Cek console (F12).', 'error');
                    return;
                }

                if (data.success) {
                    const icon = btn.querySelector('i');
                    const countSpan = btn.querySelector('.like-count');
                    
                    if (data.liked) {
                        btn.classList.add('active');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        Toast.fire({ icon: 'success', title: 'Berhasil menyukai!' });
                    } else {
                        btn.classList.remove('active');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        Toast.fire({ icon: 'info', title: 'Batal menyukai.' });
                    }
                    countSpan.innerText = data.count;
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Gagal memberikan like.' });
                }
            } catch (err) {
                console.error('Fetch Error:', err);
                Toast.fire({ icon: 'error', title: 'Koneksi ke server gagal.' });
            }
        }

        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            document.getElementById('modalImg').src = src;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

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
                // Initial show after 2s
                setTimeout(showCookieNudge, 2000);
                // Periodic nudge every 30s
                cookieInterval = setInterval(showCookieNudge, 30000);
            } else {
                document.getElementById('cookieBanner').style.display = 'none';
            }
        });

        loadNews();
        function showSupportOptions() {
            Swal.fire({
                title: 'Technical Support',
                text: 'Silahkan pilih layanan bantuan:',
                background: '#1a1a26', color: '#fff',
                showConfirmButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fab fa-whatsapp"></i> WhatsApp',
                denyButtonText: '<i class="fab fa-telegram"></i> Telegram',
                confirmButtonColor: '#25D366',
                denyButtonColor: '#0088cc',
            }).then((result) => {
                if (result.isConfirmed) window.open('https://wa.me/6283850791311', '_blank');
                else if (result.isDenied) window.open('https://t.me/dimasvip1120', '_blank');
            });
        }
    </script>
</body>
</html>
