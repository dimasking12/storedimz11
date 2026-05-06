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
    <title>License Store - DIMZSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
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
                    <li><a href="/dashboard" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                    <li><a href="/store" class="nav-link active"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                    <li><a href="/rental" class="nav-link"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                    <?php if(!$is_guest): ?>
                    <li><a href="/history" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
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

        <main class="main-content">
            <header class="mobile-header">
                <div class="sidebar-brand" style="padding: 0;"><div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div><h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1></div>
                <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </header>

            <header class="topbar">
                <h2 class="page-title">Store Dashboard</h2>
                <div class="topbar-user">
                    <div class="user-info"><span class="user-name"><?php echo $is_guest ? 'Guest User' : htmlspecialchars($user['username']); ?></span></div>
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                </div>
            </header>

            <div class="page-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Available Balance</span>
                        <div class="stat-value">Rp <?php echo $is_guest ? '0' : number_format($user['points'], 0, ',', '.'); ?></div>
                        <i class="fas fa-wallet stat-icon"></i>
                    </div>
                </div>

                <div class="card-header" style="margin-bottom: 2rem;">
                    <h3 class="card-title"><i class="fas fa-tag"></i> SELECT PREMIUM LICENSE</h3>
                </div>

                <div class="product-grid" id="product-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;"></div>
            </div>
        </main>
    </div>

    <!-- CHOICE MODAL -->
    <div id="choice-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="max-width:400px; width:100%;">
            <h3 style="text-align:center; margin-bottom:20px;">Delivery Method</h3>
            
            <!-- VOUCHER SECTION -->
            <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 212, 170, 0.05); border-radius: 12px; border: 1px solid rgba(0, 212, 170, 0.2);">
                <div style="font-weight: 700; color: var(--accent); margin-bottom: 10px; font-size: 0.85rem;"><i class="fas fa-ticket-alt"></i> KODE VOUCHER (OPSIONAL)</div>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="voucher-input" class="form-control" placeholder="Masukkan Kode" style="text-transform: uppercase;">
                    <button class="btn btn-primary" onclick="applyVoucher()" id="btn-apply-voucher" style="white-space: nowrap;">TERAPKAN</button>
                </div>
                <div id="voucher-status" style="font-size: 0.75rem; margin-top: 8px; font-weight: 600;"></div>
            </div>

            <div style="display:flex; flex-direction:column; gap:15px;">
                <button onclick="confirmPurchase('auto')" class="stat-card" style="width:100%; text-align:left; cursor:pointer;">
                    <div style="font-weight:800; color:var(--accent);">AUTOMATIC (INSTANT)</div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">Key generated & delivered immediately.</div>
                </button>
                <div id="manual-option-container" class="stat-card" style="width:100%; padding: 15px;">
                    <div style="font-weight:800; color:var(--warning); margin-bottom:10px;">MANUAL (REQUEST KEY)</div>
                    <input type="text" id="manual-licence-input" class="form-control" style="font-size:0.8rem; margin-bottom:10px;" placeholder="Enter your License/Hardware ID here...">
                    <button onclick="validateManualPurchase()" class="btn btn-primary" style="width:100%; font-size:0.75rem;">SELECT MANUAL & PAY</button>
                </div>
            </div>
            <button onclick="closeChoiceModal()" class="btn btn-ghost" style="margin-top:20px; width:100%;">Cancel</button>
        </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div id="payment-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2001; align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="max-width:450px; width:100%; text-align:center;">
            <h3>Scan QRIS to Purchase</h3>
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

    <!-- RENEW MODAL -->
    <div id="extend-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="max-width:500px; width:100%;">
            <div class="card-header"><h3 class="card-title">Extend Your License</h3><button onclick="closeExtendModal()" class="btn btn-ghost">×</button></div>
            <div class="form-group"><label class="form-label">EXISTING LICENSE KEY</label><input type="text" id="extend-licence" class="form-control" placeholder="Paste your key here"></div>
            <div class="form-group">
                <label class="form-label">GAME TYPE</label>
                <select id="extend-game-type" class="form-control" onchange="loadExtendProducts()">
                    <option value="ff">Free Fire (FF)</option>
                    <option value="ffmax">Free Fire MAX</option>
                </select>
            </div>
            <!-- Voucher for extend -->
            <div style="margin-bottom: 15px; padding: 12px; background: rgba(0,212,170,0.05); border-radius: 10px; border: 1px solid rgba(0,212,170,0.2);">
                <div style="font-weight:700; color:var(--accent); margin-bottom:8px; font-size:0.8rem;"><i class="fas fa-ticket-alt"></i> KODE VOUCHER (OPSIONAL)</div>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="extend-voucher-input" class="form-control" placeholder="Masukkan Kode" style="text-transform:uppercase; font-size:0.85rem;">
                    <button class="btn btn-primary" onclick="applyExtendVoucher()" id="btn-apply-extend-voucher" style="white-space:nowrap; font-size:0.8rem;">TERAPKAN</button>
                </div>
                <div id="extend-voucher-status" style="font-size:0.75rem; margin-top:6px; font-weight:600;"></div>
            </div>
            <div id="extend-products"></div>
        </div>
    </div>

    <script>
        const isGuest = <?php echo $is_guest ? 'true' : 'false'; ?>;
        let currentProductId = null, isExtending = false, selectedGame = 'ff';
        let activeVoucher = '';
        let activeDiscount = 0;

        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        function escapeHTML(str) {
            const p = document.createElement('p');
            p.textContent = str;
            return p.innerHTML;
        }

        async function loadProducts() {
            const resp = await fetch('api/store.php?action=get_products');
            const products = await resp.json();
            document.getElementById('product-container').innerHTML = products.map(p => `
                <div class="stat-card" style="padding: 2rem;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                        <img src="img/${p.game_type === 'ff' ? 'ff' : 'ffmax'}.png" alt="${p.game_type.toUpperCase()}"
                             style="width:48px; height:48px; border-radius:12px; object-fit:cover; box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                        <span class="badge ${p.game_type === 'ff' ? 'badge-success' : 'badge-pending'}">${p.game_type.toUpperCase()}</span>
                    </div>
                    <h4 style="font-size: 1.25rem; font-weight:800; margin-bottom:15px;">${escapeHTML(p.name)}</h4>
                    <div style="font-size: 1.5rem; font-weight:800; color:var(--accent); margin-bottom:2rem;">Rp ${new Intl.NumberFormat('id-ID').format(p.price)}</div>
                    <button class="btn btn-primary" style="width:100%; justify-content:center;" onclick="startPurchase(${p.id})">PURCHASE NOW</button>
                </div>
            `).join('');
        }

        function startPurchase(productId) {
            if (isGuest) {
                Swal.fire({
                    title: 'Login Required',
                    text: 'Silakan login atau daftar untuk melanjutkan pembelian.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'LOGIN SEKARANG',
                    cancelButtonText: 'NANTI SAJA',
                    background: '#1a1a26', color: '#fff', confirmButtonColor: 'var(--accent)'
                }).then((result) => { if (result.isConfirmed) window.location.href = 'login.php'; });
                return;
            }
            currentProductId = productId;
            isExtending = false;
            activeVoucher = '';
            activeDiscount = 0;
            document.getElementById('voucher-input').value = '';
            document.getElementById('voucher-status').innerHTML = '';
            document.getElementById('btn-apply-voucher').disabled = false;
            document.getElementById('choice-modal').style.display = 'flex';
        }

        async function applyVoucher() {
            const code = document.getElementById('voucher-input').value.trim();
            const statusDiv = document.getElementById('voucher-status');
            if (!code) return;
            
            const fd = new FormData();
            fd.append('action', 'validate_voucher');
            fd.append('code', code);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            
            const btn = document.getElementById('btn-apply-voucher');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const resp = await fetch('api/store.php', { method: 'POST', body: fd });
            const data = await resp.json();
            
            btn.innerHTML = 'TERAPKAN';
            
            if (data.success) {
                activeVoucher = code;
                activeDiscount = data.discount;
                statusDiv.style.color = '#00d4aa';
                statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Voucher berhasil diterapkan! Diskon ${data.discount}%.`;
            } else {
                activeVoucher = '';
                activeDiscount = 0;
                btn.disabled = false;
                statusDiv.style.color = '#ff4757';
                statusDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
            }
        }

        function closeChoiceModal() { document.getElementById('choice-modal').style.display = 'none'; }

        function validateManualPurchase() {
            const manualKey = document.getElementById('manual-licence-input').value;
            if (!manualKey) return Swal.fire({ icon: 'warning', title: 'License required!', text: 'Please enter your license key for manual process.', background: '#1a1a26', color:'#fff' });
            confirmPurchase('manual', manualKey);
        }

        async function confirmPurchase(deliveryType, manualKey = '') {
            closeChoiceModal();

            const formData = new FormData();
            formData.append('product_id', currentProductId);
            formData.append('type', 'random');
            formData.append('licence', manualKey);
            formData.append('delivery_type', deliveryType);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            if (activeVoucher) formData.append('voucher_code', activeVoucher);

            Swal.fire({
                title: 'Memproses...', text: 'Membuat order pembayaran...', allowOutsideClick: false,
                background: '#1a1a26', color: '#fff', didOpen: () => Swal.showLoading()
            });

            try {
                const resp = await fetch('api/store.php?action=create_order', { method: 'POST', body: formData });
                const data = await resp.json();
                Swal.close();
                if (data.success) {
                    showPaymentModal(data.qr_url, data.order_id);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message || 'Server error', background: '#1a1a26', color: '#fff' });
                }
            } catch(e) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Gagal menghubungi server!', text: 'Periksa koneksi internet kamu.', background: '#1a1a26', color: '#fff' });
            }
        }

        function downloadQR() {
            const img = document.querySelector('#qr-container img');
            if(!img) return;
            window.location.href = `api/download_qr.php?url=${encodeURIComponent(img.src)}`;
        }

        function showPaymentModal(qrUrl, orderId) {
            document.getElementById('payment-modal').style.display = 'flex';
            document.getElementById('qr-container').innerHTML = `<img src="${qrUrl}" width="220">`;
            startPaymentCheck(orderId);
        }

        function startPaymentCheck(orderId) {
            if (window.paymentInterval) clearInterval(window.paymentInterval);
            const checkFn = async (isManual = false) => {
                const resp = await fetch(`api/store.php?action=check_status&order_id=${orderId}`);
                const data = await resp.json();
                if (data.status === 'paid') {
                    clearInterval(window.paymentInterval);
                    document.getElementById('btn-manual-check').style.display = 'none';
                    
                    document.getElementById('payment-status-text').innerHTML = `
                        <div style="padding:20px; border:1px solid var(--accent); border-radius:12px; margin-top:10px; background:rgba(0,212,170,0.05);">
                            <div style="color:var(--accent); font-weight:800; font-size:1.1rem; margin-bottom:10px;">PEMBAYARAN SUKSES!</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:10px;">LINSENSI AKTIF ANDA:</div>
                            <code style="display:block; margin:10px 0; font-size:0.85rem; background:black; padding:12px; border-radius:8px; border:1px solid var(--border); color:white;">${data.licence}</code>
                            <button class="btn btn-primary" style="width:100%; margin-top:10px;" onclick="navigator.clipboard.writeText('${data.licence}'); Swal.fire({title:'Copied!', icon:'success', timer:1000, showConfirmButton:false, background:'#1a1a26', color:'#fff'})">
                                <i class="fas fa-copy"></i> SALIN KUNCI
                            </button>
                        </div>
                    `;
                }
 else if (isManual) {
                    Swal.fire({ icon: 'info', title: 'Pending', background: '#1a1a26', color: '#fff' });
                }
            };
            window.paymentInterval = setInterval(() => checkFn(false), 5000);
            document.getElementById('btn-manual-check').onclick = () => {
                const btn = document.getElementById('btn-manual-check'); btn.disabled = true;
                checkFn(true).finally(() => { btn.disabled = false; });
            };
        }

        let extendVoucher = '';
        let extendDiscount = 0;

        function openExtendModal() {
            extendVoucher = '';
            extendDiscount = 0;
            document.getElementById('extend-voucher-input').value = '';
            document.getElementById('extend-voucher-status').innerHTML = '';
            document.getElementById('btn-apply-extend-voucher').disabled = false;
            document.getElementById('extend-modal').style.display = 'flex';
            loadExtendProducts();
        }
        function closeExtendModal() { document.getElementById('extend-modal').style.display = 'none'; }

        async function applyExtendVoucher() {
            const code = document.getElementById('extend-voucher-input').value.trim();
            const statusDiv = document.getElementById('extend-voucher-status');
            if (!code) return;
            const fd = new FormData();
            fd.append('action', 'validate_voucher');
            fd.append('code', code);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            const btn = document.getElementById('btn-apply-extend-voucher');
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const resp = await fetch('api/store.php', { method: 'POST', body: fd });
            const data = await resp.json();
            btn.innerHTML = 'TERAPKAN';
            if (data.success) {
                extendVoucher = code; extendDiscount = data.discount;
                statusDiv.style.color = '#00d4aa';
                statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Diskon ${data.discount}% diterapkan!`;
            } else {
                extendVoucher = ''; extendDiscount = 0;
                btn.disabled = false;
                statusDiv.style.color = '#ff4757';
                statusDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
            }
        }

        async function loadExtendProducts() {
            const gameType = document.getElementById('extend-game-type').value;
            const resp = await fetch(`api/store.php?action=get_products&game=${gameType}`);
            const products = await resp.json();
            if (!products.length) {
                document.getElementById('extend-products').innerHTML = '<div style="text-align:center; color:var(--text-muted); padding:20px;">Tidak ada produk tersedia.</div>';
                return;
            }
            document.getElementById('extend-products').innerHTML = products.map(p => `
                <div style="background:var(--bg-input); padding:15px; border-radius:10px; margin-bottom:10px; cursor:pointer; border:1px solid transparent; transition:border 0.2s;"
                     onmouseover="this.style.border='1px solid var(--accent)'" onmouseout="this.style.border='1px solid transparent'"
                     onclick="confirmExtend('${p.game_type}', '${p.duration}', ${p.price})">
                    <div style="font-weight:700;">${escapeHTML(p.name)}</div>
                    <div style="color:var(--accent); font-weight:800;">Rp ${new Intl.NumberFormat('id-ID').format(p.price)}</div>
                </div>
            `).join('');
        }

        async function confirmExtend(gameType, duration, price) {
            const licence = document.getElementById('extend-licence').value.trim();
            if (!licence) {
                return Swal.fire({ icon: 'warning', title: 'Masukkan License Key!', text: 'Isi dulu license key yang ingin di-extend.', background: '#1a1a26', color: '#fff' });
            }

            closeExtendModal();

            const formData = new FormData();
            formData.append('action', 'create_extend_order');
            formData.append('licence', licence);
            formData.append('game_type', gameType);
            formData.append('duration', duration);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            if (extendVoucher) formData.append('voucher_code', extendVoucher);

            const loadingAlert = Swal.fire({
                title: 'Memproses...', text: 'Membuat order extend...', allowOutsideClick: false,
                background: '#1a1a26', color: '#fff', didOpen: () => Swal.showLoading()
            });

            try {
                const resp = await fetch('api/store.php', { method: 'POST', body: formData });
                const data = await resp.json();
                Swal.close();

                if (data.success) {
                    showPaymentModal(data.qr_url, data.order_id, true);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message || 'Server error', background: '#1a1a26', color: '#fff' });
                }
            } catch(e) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Gagal menghubungi server!', text: 'Periksa koneksi internet kamu.', background: '#1a1a26', color: '#fff' });
            }
        }

        loadProducts();
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
