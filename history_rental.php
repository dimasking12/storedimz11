<?php
require_once 'config_web.php';
if (!isset($_SESSION['user_id'])) redirect('login.php');
$user = getWebUser($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Rental - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
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
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="store.php" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="rental.php" class="nav-link"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                    <li><a href="history_rental.php" class="nav-link active"><i class="fas fa-clock-rotate-left"></i> <span>Riwayat Rental</span></a></li>
                    <li><a href="redeem.php" class="nav-link"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                </ul>
                <div class="menu-header">Account Details</div>
                <ul>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                    <li><a href="javascript:showSupportOptions()" class="nav-link"><i class="fas fa-headset"></i> <span>Technical Support</span></a></li>
                    <?php if(isWebAdmin()): ?>
                    <li><a href="admin.php" class="nav-link" style="color: #ffaa00;"><i class="fas fa-user-shield"></i> <span>Admin Control</span></a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;"><div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div><h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1></div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title">Rental History</h2>
                <div class="topbar-user">
                    <div class="user-info text-right">
                        <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="user-role">Member</span>
                    </div>
                </div>
            </header>

            <div class="page-content">
                <div class="card-header" style="margin-bottom: 25px;">
                    <h3 class="card-title"><i class="fas fa-list-ul"></i> MY RENTALS</h3>
                </div>

                <div id="rental-history-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <div class="card" style="text-align:center; padding:30px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadHistory() {
            const resp = await fetch('api/store.php?action=get_rental_history');
            const data = await resp.json();
            const container = document.getElementById('rental-history-container');
            
            if(!data || !data.length) {
                container.innerHTML = '<div class="card" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada riwayat rental.</div>';
                return;
            }

            container.innerHTML = data.map(h => `
                <div class="card history-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                        <div>
                            <div class="order-id">Order #${h.order_id}</div>
                            <h4 style="margin:5px 0; color:white;">${h.title}</h4>
                        </div>
                        <span class="badge badge-${h.status}">${h.status.toUpperCase()}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted); font-size:0.85rem;"><i class="far fa-clock"></i> ${h.duration}</span>
                        <div style="text-align:right;">
                            <div style="color:var(--accent); font-weight:700;">Rp ${parseInt(h.amount).toLocaleString()}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">${new Date(h.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    ${h.status === 'completed' ? `
                    <button class="btn btn-primary" style="width:100%; margin-top:15px; justify-content:center; gap:8px;" onclick='showAccountDetails(${JSON.stringify(h)})'>
                        <i class="fas fa-key"></i> LIHAT DETAIL AKUN
                    </button>
                    ` : ''}
                    ${h.status === 'pending' ? `
                    <button class="btn btn-ghost" style="width:100%; margin-top:15px; justify-content:center; gap:8px; border-color:var(--warning); color:var(--warning);" onclick="showRentalQR('${h.qris_url}', ${h.amount})">
                        <i class="fas fa-qrcode"></i> BAYAR SEKARANG
                    </button>
                    ` : ''}
                </div>
            `).join('');
        }

        function showAccountDetails(order) {
            Swal.fire({
                title: 'Detail Akun Rental',
                html: `
                    <div style="text-align:left; background:rgba(255,255,255,0.05); padding:15px; border-radius:12px; margin-top:10px;">
                        <div style="margin-bottom:12px;">
                            <label style="font-size:0.7rem; color:var(--text-muted); display:block; margin-bottom:4px;">EMAIL / USERNAME</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="text" value="${order.email}" readonly style="background:rgba(0,0,0,0.3); border:1px solid var(--border); color:white; padding:8px; border-radius:6px; flex:1; font-size:0.9rem;">
                                <button onclick="copyToClipboard('${order.email}')" class="btn btn-ghost" style="padding:8px;"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:0.7rem; color:var(--text-muted); display:block; margin-bottom:4px;">8x BACKUP CODES</label>
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <textarea readonly style="background:rgba(0,0,0,0.3); border:1px solid var(--border); color:var(--accent); padding:8px; border-radius:6px; flex:1; font-size:0.85rem; height:80px; font-family:monospace;">${order.backup_codes}</textarea>
                                <button onclick="copyToClipboard('${order.backup_codes}')" class="btn btn-ghost" style="padding:8px;"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <p style="font-size:0.7rem; color:var(--warning); margin-top:10px;"><i class="fas fa-info-circle"></i> Gunakan backup code jika akun meminta verifikasi 2 langkah.</p>
                    </div>
                `,
                confirmButtonText: 'TUTUP',
                background: '#1a1a26', color: '#fff',
                width: '400px'
            });
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.showValidationMessage('Berhasil disalin!');
                setTimeout(() => Swal.resetValidationMessage(), 1500);
            });
        }

        function showRentalQR(url, amt) {
            Swal.fire({
                title: 'Lanjutkan Pembayaran',
                html: `<img src="${url}" style="width:250px; border-radius:10px; margin-bottom:15px;"><br>Total: <b>Rp ${amt.toLocaleString()}</b>`,
                background: '#1a1a26', color: '#fff'
            });
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        loadHistory();
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
