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
    <title>Rental Akun - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .rental-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .rental-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 0; overflow: hidden; transition: 0.3s; position: relative; display: flex; flex-direction: column; }
        .rental-card:hover { border-color: var(--accent); transform: translateY(-5px); }
        .rental-banner { width: 100%; height: 200px; cursor: pointer; overflow: hidden; background: #000; }
        .rental-banner img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .rental-banner:hover img { transform: scale(1.05); }
        .rental-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .rental-title { font-size: 1.15rem; font-weight: 800; color: white; margin-bottom: 10px; }
        .rental-desc { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 15px; flex: 1; }
        .rental-info { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid var(--border); }
        .rental-price { color: var(--accent); font-weight: 800; font-size: 1.1rem; }
        .rental-dur { font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; }
        .sold-overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.73); display:flex; align-items:center; justify-content:center; z-index: 10; backdrop-filter: blur(2px); pointer-events: none; }
        .sold-badge { background: var(--danger); color: white; padding: 10px 30px; font-weight: 900; transform: rotate(-15deg); border: 3px solid white; box-shadow: 0 0 30px rgba(255,71,87,0.6); }

        /* Image Preview Modal */
        .image-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s ease; }
        .image-modal.active { display: flex; opacity: 1; }
        .modal-content { max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); transform: scale(0.9); transition: 0.3s ease; }
        .image-modal.active .modal-content { transform: scale(1); }
        .close-modal { position: absolute; top: 20px; right: 20px; color: white; font-size: 2rem; cursor: pointer; opacity: 0.7; transition: 0.3s; }
        .close-modal:hover { opacity: 1; transform: rotate(90deg); }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <div class="app-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-bolt"></i></div>
                <h1 class="brand-name">DIMZ<span>STORE</span></h1>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-header">Main Menu</div>
                <ul>
                    <li><a href="dashboard" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="store" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="rental" class="nav-link active"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                    <?php if(!$is_guest): ?>
                    <li><a href="history" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                    <li><a href="history_rental" class="nav-link"><i class="fas fa-clock-rotate-left"></i> <span>Riwayat Rental</span></a></li>
                    <li><a href="redeem" class="nav-link"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                    <?php endif; ?>
                </ul>
                <div class="menu-header">Account Details</div>
                <ul>
                    <?php if(!$is_guest): ?>
                    <li><a href="profile" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <?php endif; ?>
                    <li><a href="javascript:showSupportOptions()" class="nav-link"><i class="fas fa-headset"></i> <span>Technical Support</span></a></li>
                    <?php if(!$is_guest && isWebAdmin()): ?>
                    <li><a href="admin" class="nav-link" style="color: #ffaa00;"><i class="fas fa-user-shield"></i> <span>Admin Control</span></a></li>
                    <?php endif; ?>
                    <?php if(!$is_guest): ?>
                    <li><a href="logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                    <?php else: ?>
                    <li><a href="login" class="nav-link" style="color: var(--accent);"><i class="fas fa-sign-in-alt"></i> <span>Login / Register</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;"><div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div><h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1></div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title">Rental Account Store</h2>
                <div class="topbar-user">
                    <div class="user-info text-right">
                        <span class="user-name"><?php echo $is_guest ? 'Guest User' : htmlspecialchars($user['username']); ?></span>
                        <span class="user-role"><?php echo $is_guest ? 'Guest' : 'Member'; ?></span>
                    </div>
                </div>
            </header>

            <div class="page-content">
                <div class="card-header" style="margin-bottom: 25px;">
                    <h3 class="card-title"><i class="fas fa-key"></i> AVAILABLE RENTALS</h3>
                    <p style="color:var(--text-muted); font-size:0.85rem;">Sewa akun sultan sekarang juga dengan sistem QRIS otomatis.</p>
                </div>

                <div id="rental-container" class="rental-grid">
                    <div style="grid-column: 1/-1; text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                </div>
            </div>
        </main>
    </div>

    <!-- PAYMENT MODAL (SAMA DENGAN STORE) -->
    <div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2001; align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="max-width:450px; width:100%; text-align:center;">
            <h3>Scan QRIS to Rental</h3>
            <div id="qr-container" style="margin: 20px 0; background:white; padding:10px; border-radius:15px; display:inline-block;"></div>
            
            <div style="margin-bottom: 20px;">
                <button onclick="downloadQR()" class="btn btn-ghost" style="border: 1px solid var(--border); width: 100%; justify-content: center; gap: 8px;">
                    <i class="fas fa-download"></i> DOWNLOAD QRIS (SAVE TO GALLERY)
                </button>
            </div>

            <div id="payment-status-text" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Awaiting verification...</div>
            <button id="btn-manual-check" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; justify-content: center;">
                <i class="fas fa-sync-alt"></i> CEK PEMBAYARAN MANUAL
            </button>
            <button onclick="location.reload()" class="btn btn-ghost" style="width:100%; border:1px solid var(--border);">CLOSE</button>
        </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div class="image-modal" id="imageModal" onclick="closeImageModal()">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="modalImg">
    </div>

    <script>
        const isGuest = <?php echo $is_guest ? 'true' : 'false'; ?>;
        function toggleSidebar() { 
            document.getElementById('sidebar').classList.toggle('active'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }

        function escapeHTML(str) {
            const p = document.createElement('p');
            p.textContent = str;
            return p.innerHTML;
        }

        async function loadRentals() {
            const resp = await fetch('api/store.php?action=get_rentals');
            const rentals = await resp.json();
            const container = document.getElementById('rental-container');
            
            if(!rentals.length) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px; color:var(--text-muted);">Belum ada akun rental tersedia.</div>';
                return;
            }

            container.innerHTML = rentals.map(r => {
                const isSold = r.status === 'sold';
                const imgUrl = r.image_url || 'https://via.placeholder.com/400x200?text=No+Image';
                return `
                    <div class="rental-card">
                        ${isSold ? '<div class="sold-overlay"><div class="sold-badge">RENTED / SOLD</div></div>' : ''}
                        <div class="rental-banner" onclick="openImageModal('${imgUrl}')">
                            <img src="${imgUrl}" alt="Account" loading="lazy">
                        </div>
                        <div class="rental-body">
                            <h4 class="rental-title">${escapeHTML(r.title)}</h4>
                            <div class="rental-desc">${escapeHTML(r.description).replace(/\n/g, '<br>')}</div>
                            <div class="rental-info">
                                <div>
                                    <div class="rental-price">Rp ${parseInt(r.price).toLocaleString()}</div>
                                    <div class="rental-dur"><i class="far fa-clock"></i> ${r.duration}</div>
                                </div>
                                <button class="btn btn-primary" onclick="rentAccount(${r.id}, '${r.title}', ${r.price})" ${isSold ? 'disabled' : ''}>
                                    ${isSold ? 'SUDAH DISEWA' : 'SEWA SEKARANG'}
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
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
            document.body.style.overflow = 'auto';
        }

        function downloadQR() {
            const img = document.querySelector('#qr-container img');
            if(!img) return;
            window.location.href = `api/download_qr.php?url=${encodeURIComponent(img.src)}`;
        }

        async function rentAccount(id, title, price) {
            if (isGuest) {
                Swal.fire({
                    title: 'Login Required',
                    text: 'Silakan login atau daftar untuk melakukan sewa akun.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'LOGIN SEKARANG',
                    cancelButtonText: 'NANTI SAJA',
                    background: '#1a1a26', color: '#fff', confirmButtonColor: 'var(--accent)'
                }).then((result) => { if (result.isConfirmed) window.location.href = 'login.php'; });
                return;
            }
            const result = await Swal.fire({
                title: 'Konfirmasi Sewa',
                html: `Kamu akan menyewa: <b>${title}</b><br>Seharga: <b style="color:var(--accent);">Rp ${price.toLocaleString()}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                background: '#1a1a26', color: '#fff'
            });

            if (result.isConfirmed) {
                try {
                    const fd = new FormData(); 
                    fd.append('action', 'create_rental_order');
                    fd.append('rental_id', id);
                    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                    const resp = await fetch('api/store.php', { method: 'POST', body: fd });
                    const text = await resp.text();
                    
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showPaymentModal(data.qris_url, data.order_id);
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    } catch (parseError) {
                        console.error('SERVER RESPONSE ERROR:', text);
                        Swal.fire('Server Error!', 'Data dari server tidak valid. Cek console log (F12) untuk detail.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Koneksi Gagal!', 'Tidak dapat terhubung ke server. Coba lagi nanti.', 'error');
                }
            }
        }

        function showPaymentModal(qrUrl, orderId) {
            document.getElementById('payment-modal').style.display = 'flex';
            document.getElementById('qr-container').innerHTML = `<img src="${qrUrl}" width="220">`;
            startPaymentCheck(orderId);
        }

        function startPaymentCheck(orderId) {
            if (window.paymentInterval) clearInterval(window.paymentInterval);
            const checkFn = async (isManual = false) => {
                const resp = await fetch(`api/store.php?action=check_rental_status&order_id=${orderId}`);
                const data = await resp.json();
                
                if (data.status === 'paid') {
                    clearInterval(window.paymentInterval);
                    document.getElementById('btn-manual-check').style.display = 'none';
                    document.getElementById('payment-status-text').innerHTML = `
                        <div style="padding:20px; border:1px solid var(--accent); border-radius:12px; margin-top:10px; background:rgba(0,212,170,0.05); text-align:left;">
                            <div style="color:var(--accent); font-weight:800; font-size:1.1rem; margin-bottom:10px; text-align:center;">SEWA BERHASIL! 🎉</div>
                            
                            <div style="margin-bottom:12px; background:rgba(0,0,0,0.2); padding:10px; border-radius:8px;">
                                <label style="font-size:0.65rem; color:var(--text-muted); display:block; margin-bottom:4px; text-transform:uppercase;">Email / Username</label>
                                <div style="color:white; font-weight:600; font-family:monospace; display:flex; justify-content:space-between; align-items:center;">
                                    <span>${data.email}</span>
                                    <i class="fas fa-copy" style="cursor:pointer;" onclick="navigator.clipboard.writeText('${data.email}'); Swal.fire({title:'Copied!',timer:1000,showConfirmButton:false,background:'#1a1a26',color:'#fff'})"></i>
                                </div>
                            </div>

                            <div style="margin-bottom:15px; background:rgba(0,0,0,0.2); padding:10px; border-radius:8px;">
                                <label style="font-size:0.65rem; color:var(--text-muted); display:block; margin-bottom:4px; text-transform:uppercase;">Backup Codes (8x)</label>
                                <div style="color:var(--accent); font-size:0.85rem; font-family:monospace; white-space:pre-wrap;">${data.backup_codes}</div>
                            </div>

                            <p style="font-size:0.7rem; color:var(--text-muted); line-height:1.4;">Data di atas juga tersimpan di menu <b>Riwayat Rental</b>.</p>
                            
                            <button class="btn btn-primary" style="width:100%; margin-top:15px;" onclick="window.location.href='history_rental.php'">
                                <i class="fas fa-history"></i> CEK RIWAYAT RENTAL
                            </button>
                        </div>
                    `;
                } else if (isManual) {
                    Swal.fire({ icon: 'info', title: 'Pending', text: 'Pembayaran belum terdeteksi. Silakan tunggu atau coba scan ulang.', background: '#1a1a26', color: '#fff' });
                }
            };
            window.paymentInterval = setInterval(() => checkFn(false), 5000);
            document.getElementById('btn-manual-check').onclick = () => {
                const btn = document.getElementById('btn-manual-check'); btn.disabled = true;
                checkFn(true).finally(() => { btn.disabled = false; });
            };
        }

        loadRentals();
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
