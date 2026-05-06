<?php
require_once 'config_web.php';
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$user = getWebUser($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 5px; }
        .filter-btn { padding: 10px 20px; border-radius: 12px; background: var(--bg-hover); color: var(--text-secondary); border: 1px solid var(--border); cursor: pointer; transition: 0.3s; white-space: nowrap; font-weight: 600; font-size: 0.8rem; }
        .filter-btn.active { background: var(--accent-glow); color: var(--accent); border-color: var(--accent); }
        .history-card { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 20px; margin-bottom: 15px; display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 20px; transition: 0.3s; }
        .history-card:hover { transform: translateX(5px); border-color: var(--accent); }
        .game-badge { width: 45px; height: 45px; border-radius: 12px; background: var(--bg-input); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .info-group h4 { font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; }
        .info-group p { font-size: 0.75rem; color: var(--text-muted); }
        .price-group { text-align: right; }
        .price-group .amount { font-size: 1rem; font-weight: 800; color: white; }
        @media (max-width: 768px) {
            .history-card { grid-template-columns: auto 1fr; }
            .price-group, .status-group { grid-column: 2; text-align: left; }
        }
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
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="store.php" class="nav-link"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="history.php" class="nav-link active"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
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
                <h2 class="page-title">Purchase History</h2>
                <div class="topbar-user">
                    <div class="user-info"><span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span></div>
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                </div>
            </header>

            <div class="page-content">
                <div class="filter-tabs">
                    <button class="filter-btn active" onclick="loadHistory('all')">All</button>
                    <button class="filter-btn" onclick="loadHistory('pending')">Pending</button>
                    <button class="filter-btn" onclick="loadHistory('completed')">Paid</button>
                </div>
                <div id="history-container"></div>
            </div>
        </main>
    </div>

    <!-- PAYMENT MODAL -->
    <div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="max-width:450px; width:100%; text-align:center;">
            <h3>Complete Payment</h3>
            <div id="qr-container" style="margin: 20px 0; background:white; padding:10px; border-radius:15px; display:inline-block;"></div>
            
            <div style="margin-bottom: 20px;">
                <button onclick="downloadQR()" class="btn btn-ghost" style="border: 1px solid var(--border); width: 100%; justify-content: center; gap: 8px;">
                    <i class="fas fa-download"></i> DOWNLOAD QRIS
                </button>
            </div>

            <div id="payment-status-text" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Checking status...</div>
            <button id="btn-manual-check" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; justify-content: center;"><i class="fas fa-sync-alt"></i> RE-CHECK PAYMENT</button>
            <button onclick="closeModal()" class="btn btn-ghost" style="width:100%; border:1px solid var(--border);">CLOSE</button>
        </div>
    </div>

    <script>
        function downloadQR() {
            const img = document.querySelector('#qr-container img');
            if(!img) return;
            window.location.href = `api/download_qr.php?url=${encodeURIComponent(img.src)}`;
        }

        function toggleSidebar() { 
document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        async function loadHistory(filter = 'all') {
            const container = document.getElementById('history-container');
            container.innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            const resp = await fetch(`api/store.php?action=get_history&filter=${filter}`);
            const orders = await resp.json();
            
            if (!orders.length) { container.innerHTML = '<p style="text-align:center; color:var(--text-muted); margin-top:50px;">No records found.</p>'; return; }

            container.innerHTML = orders.map(o => {
                let statusBadge = `<span class="badge badge-${o.status}">${o.status.toUpperCase()}</span>`;
                let actionBtn = '';
                const isRedeem = o.key_type === 'redeem';

                if (o.status === 'pending' && !isRedeem) {
                    actionBtn = `<button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.7rem;" onclick="reOpenPayment('${o.qr_url}', '${o.order_id}')">PAY / CHECK</button>`;
                }
                if (isRedeem) {
                    statusBadge = `<span class="badge" style="background:rgba(99,102,241,0.2);color:#a5b4fc;border:1px solid rgba(99,102,241,0.4);">🎁 REDEEM</span>`;
                }

                const amountDisplay = isRedeem
                    ? `<span style="color:#a5b4fc; font-weight:800; font-size:0.9rem;">🎁 GRATIS</span>`
                    : `Rp ${new Intl.NumberFormat('id-ID').format(o.amount)}`;

                // Calculate key expiry
                let expiryInfo = '';
                let isExpired = false;
                if (o.status === 'completed' && o.licence && o.licence !== 'PENDING_ADMIN') {
                    const createdMs  = new Date(o.created_at).getTime();
                    const expiryDate = new Date(createdMs + (o.duration * 24 * 60 * 60 * 1000));
                    const now        = new Date();
                    isExpired  = expiryDate < now;

                    const expiryStr = expiryDate.toLocaleString('id-ID', {
                        day: '2-digit', month: 'long', year: 'numeric',
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    });

                    const diffMs   = expiryDate - now;
                    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                    const diffHrs  = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

                    let countdownBadge = '';
                    if (isExpired) {
                        countdownBadge = `<span style="background:rgba(255,71,87,0.12);color:var(--danger);border:1px solid rgba(255,71,87,0.3);border-radius:6px;font-size:0.65rem;font-weight:700;padding:2px 8px;">EXPIRED</span>`;
                    } else if (diffDays === 0) {
                        countdownBadge = `<span style="background:rgba(255,170,0,0.12);color:var(--warning);border:1px solid rgba(255,170,0,0.3);border-radius:6px;font-size:0.65rem;font-weight:700;padding:2px 8px;">⏳ ${diffHrs}j lagi</span>`;
                    } else {
                        countdownBadge = `<span style="background:rgba(0,212,170,0.1);color:var(--accent);border:1px solid rgba(0,212,170,0.25);border-radius:6px;font-size:0.65rem;font-weight:700;padding:2px 8px;">✅ ${diffDays} hari lagi</span>`;
                    }

                    expiryInfo = `
                        <div style="margin-top:8px; padding:8px 10px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:8px; display:inline-flex; flex-direction:column; gap:4px;">
                            <div style="font-size:0.68rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">⏰ Key Expires</div>
                            <div style="font-size:0.75rem; color:${isExpired ? 'var(--danger)' : 'white'}; font-weight:700;">${expiryStr}</div>
                            <div>${countdownBadge}</div>
                        </div>`;
                }

                return `
                    <div class="history-card">
                        <div class="game-badge">
                            <img src="img/${o.game_type === 'ff' ? 'ff' : 'ffmax'}.png" alt="${o.game_type.toUpperCase()}"
                                 style="width:38px; height:38px; border-radius:10px; object-fit:cover;">
                        </div>
                        <div class="info-group">
                            <h4>${o.game_type.toUpperCase()} - ${o.duration} Days${isRedeem ? ' <span style="font-size:0.65rem;color:#a5b4fc;">(Tukar Balance)</span>' : ''}</h4>
                            <p>${new Date(o.created_at).toLocaleString('id-ID', {day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'})}</p>
                            ${o.licence ? `<code style="background:black; padding:4px 8px; font-size:0.7rem; color:var(--accent); border-radius:5px;">${o.licence}</code>` : ''}
                            ${expiryInfo}
                        </div>
                        <div class="price-group">
                            <div class="amount">${amountDisplay}</div>
                            <div style="font-size:0.6rem; color:var(--text-muted);">#${o.order_id}</div>
                        </div>
                        <div class="status-group" style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                            ${statusBadge}
                            ${actionBtn}
                            ${o.status === 'completed' && o.licence && o.licence !== 'PENDING_ADMIN' ? `
                                <button class="btn ${isExpired ? 'btn-primary' : 'btn-ghost'}" style="padding: 6px 12px; font-size: 0.7rem; ${isExpired ? '' : 'border-color: var(--accent); color: var(--accent);'} width: 100%;" onclick="openExtendModal('${o.licence}', '${o.game_type}')">
                                    <i class="fas fa-sync-alt"></i> ${isExpired ? 'EXTEND EXPIRED KEY' : 'EXTEND KEY'}
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function reOpenPayment(qrUrl, orderId) {
            document.getElementById('payment-modal').style.display = 'flex';
            document.getElementById('qr-container').innerHTML = `<img src="${qrUrl}" width="200">`;
            startPaymentCheck(orderId);
        }

        async function openExtendModal(licence, gameType) {
            const prices = {
                '1': 15000, '2': 30000, '3': 40000, '4': 50000, '5': 60000, '6': 70000,
                '7': 80000, '8': 90000, '10': 100000, '15': 150000, '20': 180000, '30': 250000
            };

            const { value: days } = await Swal.fire({
                title: 'Perpanjang License',
                html: `
                    <div style="text-align:left; margin-bottom:15px;">
                        <label style="font-size:0.8rem; color:var(--text-muted);">Pilih Durasi Perpanjangan:</label>
                        <select id="extend-duration" class="swal2-select" style="width:100%; margin:10px 0; background:#1a1a26; color:white; border:1px solid var(--border);">
                            ${Object.entries(prices).map(([d, p]) => `<option value="${d}">${d} Hari - Rp ${p.toLocaleString()}</option>`).join('')}
                        </select>
                        <div style="font-size:0.7rem; color:var(--accent); background:rgba(0,212,170,0.1); padding:10px; border-radius:8px; margin-top:5px;">
                            <i class="fas fa-info-circle"></i> Key: <b>${licence}</b><br>
                            Masa aktif akan ditambahkan ke sisa hari yang ada.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'LANJUTKAN PEMBAYARAN',
                cancelButtonText: 'BATAL',
                background: '#1a1a26', color: '#fff',
                confirmButtonColor: 'var(--accent)',
                preConfirm: () => {
                    return document.getElementById('extend-duration').value;
                }
            });

            if (days) {
                Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                const formData = new FormData();
                formData.append('action', 'create_extend_order');
                formData.append('licence', licence);
                formData.append('game_type', gameType);
                formData.append('duration', days);
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                try {
                    const resp = await fetch('api/store.php', { method: 'POST', body: formData });
                    const res = await resp.json();
                    
                    if (res.success) {
                        Swal.close();
                        reOpenPayment(res.qr_url, res.order_id);
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal menghubungi server', 'error');
                }
            }
        }

        function startPaymentCheck(orderId) {
            if (window.paymentInterval) clearInterval(window.paymentInterval);
            const checkFn = async (isManual = false) => {
                const resp = await fetch(`api/store.php?action=check_status&order_id=${orderId}`);
                const data = await resp.json();
                if (data.status === 'paid') {
                    clearInterval(window.paymentInterval);
                    document.getElementById('payment-status-text').innerHTML = `<div style="color:var(--accent); font-weight:800;">SUCCESS! PLEASE REFRESH PAGE.</div>`;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Pembayaran Berhasil!',
                        html: `
                            <div style="text-align:left; background:rgba(255,255,255,0.05); padding:15px; border-radius:12px;">
                                <p style="margin-bottom:10px;">License kamu telah berhasil diperpanjang.</p>
                                <div style="margin-bottom:8px;">
                                    <label style="font-size:0.7rem; color:var(--text-muted); display:block;">LICENSE KEY</label>
                                    <code style="background:black; padding:5px 10px; color:var(--accent); display:block; border-radius:5px; margin-top:4px;">${data.licence}</code>
                                </div>
                                <p style="font-size:0.8rem; color:var(--accent);">Silakan gunakan key tersebut di aplikasi.</p>
                            </div>
                        `,
                        confirmButtonText: 'MANTAP!',
                        background: '#1a1a26',
                        color: '#fff'
                    }).then(() => location.reload());
                } else if (isManual) {
                    Swal.fire({ icon: 'info', title: 'Pending', text: 'No payment found yet.', background: '#1a1a26', color: '#fff' });
                }
            };
            window.paymentInterval = setInterval(() => checkFn(false), 5000);
            document.getElementById('btn-manual-check').onclick = () => {
                const btn = document.getElementById('btn-manual-check'); 
                btn.disabled = true; btn.innerHTML = 'CHECKING...';
                checkFn(true).finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync-alt"></i> RE-CHECK PAYMENT'; });
            };
        }

        function closeModal() { document.getElementById('payment-modal').style.display = 'none'; clearInterval(window.paymentInterval); }

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

        loadHistory('all');
    </script>
</body>
</html>
