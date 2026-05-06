<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
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
                <li><a href="/dashboard" class="nav-link"><i class="fas fa-home"></i> <span>Dashboard News</span></a></li>
                <li><a href="/store"     class="nav-link active"><i class="fas fa-shopping-cart"></i> <span>License Store</span></a></li>
                <li><a href="/rental"    class="nav-link"><i class="fas fa-key"></i> <span>Rental Akun</span></a></li>
                <?php if (! $isGuest): ?>
                <li><a href="/history" class="nav-link"><i class="fas fa-history"></i> <span>Purchase History</span></a></li>
                <li><a href="/redeem"  class="nav-link"><i class="fas fa-gift"></i> <span>Tukar Balance</span></a></li>
                <?php endif ?>
            </ul>
            <div class="menu-header">Account Details</div>
            <ul>
                <?php if (! $isGuest): ?>
                <li><a href="/profile" class="nav-link"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
                <?php endif ?>
                <li><a href="javascript:showSupportOptions()" class="nav-link"><i class="fas fa-headset"></i> <span>Technical Support</span></a></li>
                <?php if (! $isGuest && $isAdmin): ?>
                <li><a href="/admin" class="nav-link" style="color: #ffaa00;"><i class="fas fa-user-shield"></i> <span>Admin Control</span></a></li>
                <?php endif ?>
                <?php if (! $isGuest): ?>
                <li><a href="/logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> <span>Sign Out</span></a></li>
                <?php else: ?>
                <li><a href="/login" class="nav-link" style="color: var(--accent);"><i class="fas fa-sign-in-alt"></i> <span>Login / Register</span></a></li>
                <?php endif ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mobile-header">
            <div class="sidebar-brand" style="padding: 0;">
                <div class="brand-icon" style="width:32px; height:32px;"><i class="fas fa-bolt"></i></div>
                <h1 class="brand-name" style="font-size:1rem;">DIMZ<span>STORE</span></h1>
            </div>
            <button class="btn btn-ghost" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        </header>

        <header class="topbar">
            <h2 class="page-title">Store Dashboard</h2>
            <div class="topbar-user">
                <div class="user-info">
                    <span class="user-name">
                        <?= $isGuest ? 'Guest User' : esc($user['username'] ?? 'User') ?>
                    </span>
                </div>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            </div>
        </header>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Available Balance</span>
                    <div class="stat-value">
                        Rp <?= $isGuest ? '0' : number_format((int) ($user['points'] ?? 0), 0, ',', '.') ?>
                    </div>
                    <i class="fas fa-wallet stat-icon"></i>
                </div>
            </div>

            <div class="card-header" style="margin-bottom: 2rem;">
                <h3 class="card-title"><i class="fas fa-tag"></i> SELECT PREMIUM LICENSE</h3>
            </div>

            <div class="product-grid" id="product-container"
                 style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;"></div>
        </div>
    </main>
</div>

<!-- CHOICE MODAL -->
<div id="choice-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:2000; align-items:center; justify-content:center; padding:20px;">
    <div class="card" style="max-width:400px; width:100%;">
        <h3 style="text-align:center; margin-bottom:20px;">Delivery Method</h3>

        <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 212, 170, 0.05); border-radius: 12px; border: 1px solid rgba(0, 212, 170, 0.2);">
            <div style="font-weight: 700; color: var(--accent); margin-bottom: 10px; font-size: 0.85rem;">
                <i class="fas fa-ticket-alt"></i> KODE VOUCHER (OPSIONAL)
            </div>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="voucher-input" class="form-control" placeholder="Masukkan Kode" style="text-transform: uppercase;">
                <button class="btn btn-primary" onclick="applyVoucher()" id="btn-apply-voucher" style="white-space: nowrap;">TERAPKAN</button>
            </div>
            <div id="voucher-status" style="font-size: 0.75rem; margin-top: 8px; font-weight: 600;"></div>
        </div>

        <div style="display:flex; flex-direction:column; gap:15px;">
            <button onclick="confirmPurchase('auto')" class="stat-card" style="width:100%; text-align:left; cursor:pointer;">
                <div style="font-weight:800; color:var(--accent);">AUTOMATIC (INSTANT)</div>
                <div style="font-size:0.7rem; color:var(--text-muted);">Key generated &amp; delivered immediately.</div>
            </button>
            <div id="manual-option-container" class="stat-card" style="width:100%; padding: 15px;">
                <div style="font-weight:800; color:var(--warning); margin-bottom:10px;">MANUAL (REQUEST KEY)</div>
                <input type="text" id="manual-licence-input" class="form-control" style="font-size:0.8rem; margin-bottom:10px;" placeholder="Enter your License/Hardware ID here...">
                <button onclick="validateManualPurchase()" class="btn btn-primary" style="width:100%; font-size:0.75rem;">SELECT MANUAL &amp; PAY</button>
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
        <div id="payment-status-text" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Awaiting verification...</div>
        <button id="btn-manual-check" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; justify-content: center;">
            <i class="fas fa-sync-alt"></i> CEK PEMBAYARAN MANUAL
        </button>
        <button onclick="location.reload()" class="btn btn-ghost" style="width:100%; border:1px solid var(--border);">CLOSE</button>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const isGuest = <?= $isGuest ? 'true' : 'false' ?>;
    const API_BASE = '<?= esc(site_url('store/api'), 'js') ?>';
    let currentProductId = null, activeVoucher = '', activeDiscount = 0;

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function escapeHTML(str) {
        const p = document.createElement('p'); p.textContent = str; return p.innerHTML;
    }

    async function loadProducts() {
        const resp = await fetch(`${API_BASE}/products`, { credentials: 'same-origin' });
        const products = await resp.json();
        document.getElementById('product-container').innerHTML = products.map(p => `
            <div class="stat-card" style="padding: 2rem;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <img src="/img/${p.game_type === 'ff' ? 'ff' : 'ffmax'}.png" alt="${p.game_type.toUpperCase()}"
                         style="width:48px; height:48px; border-radius:12px; object-fit:cover; box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                    <span class="badge ${p.game_type === 'ff' ? 'badge-success' : 'badge-pending'}">${p.game_type.toUpperCase()}</span>
                </div>
                <h4 style="font-size: 1.25rem; font-weight:800; margin-bottom:15px;">${escapeHTML(p.name)}</h4>
                <div style="font-size: 1.5rem; font-weight:800; color:var(--accent); margin-bottom:2rem;">Rp ${new Intl.NumberFormat('id-ID').format(p.price)}</div>
                <button class="btn btn-primary" style="width:100%; justify-content:center;" onclick="startPurchase(${p.id})">PURCHASE NOW</button>
            </div>`).join('');
    }

    function startPurchase(productId) {
        if (isGuest) {
            Swal.fire({
                title: 'Login Required',
                text: 'Silakan login atau daftar untuk melanjutkan pembelian.',
                icon: 'info', showCancelButton: true,
                confirmButtonText: 'LOGIN SEKARANG', cancelButtonText: 'NANTI SAJA',
                background: '#1a1a26', color: '#fff'
            }).then((r) => { if (r.isConfirmed) window.location.href = '/login'; });
            return;
        }
        currentProductId = productId;
        activeVoucher = ''; activeDiscount = 0;
        document.getElementById('voucher-input').value = '';
        document.getElementById('voucher-status').innerHTML = '';
        document.getElementById('btn-apply-voucher').disabled = false;
        document.getElementById('choice-modal').style.display = 'flex';
    }

    async function applyVoucher() {
        const code = document.getElementById('voucher-input').value.trim();
        const statusDiv = document.getElementById('voucher-status');
        if (!code) return;

        const fd = window.csrfFormData(new FormData());
        fd.append('code', code);

        const btn = document.getElementById('btn-apply-voucher');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const resp = await fetch(`${API_BASE}/validate-voucher`, { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await resp.json();

        btn.innerHTML = 'TERAPKAN';
        if (data.success) {
            activeVoucher = code; activeDiscount = data.discount;
            statusDiv.style.color = '#00d4aa';
            statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Voucher diterapkan! Diskon ${data.discount}%.`;
        } else {
            activeVoucher = ''; activeDiscount = 0; btn.disabled = false;
            statusDiv.style.color = '#ff4757';
            statusDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${escapeHTML(data.message)}`;
        }
    }

    function closeChoiceModal() { document.getElementById('choice-modal').style.display = 'none'; }

    function validateManualPurchase() {
        const manualKey = document.getElementById('manual-licence-input').value;
        if (!manualKey) {
            return Swal.fire({ icon: 'warning', title: 'License required!', background: '#1a1a26', color: '#fff' });
        }
        confirmPurchase('manual', manualKey);
    }

    async function confirmPurchase(deliveryType, manualKey = '') {
        closeChoiceModal();

        const fd = window.csrfFormData(new FormData());
        fd.append('product_id', currentProductId);
        fd.append('type', 'random');
        fd.append('licence', manualKey);
        fd.append('delivery_type', deliveryType);
        if (activeVoucher) fd.append('voucher_code', activeVoucher);

        Swal.fire({ title: 'Memproses...', text: 'Membuat order pembayaran...', allowOutsideClick: false, background: '#1a1a26', color: '#fff', didOpen: () => Swal.showLoading() });

        try {
            const resp = await fetch(`${API_BASE}/create-order`, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await resp.json();
            Swal.close();
            if (data.success) {
                showPaymentModal(data.qr_url, data.order_id);
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message || 'Server error', background: '#1a1a26', color: '#fff' });
            }
        } catch (e) {
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Gagal menghubungi server!', background: '#1a1a26', color: '#fff' });
        }
    }

    function showPaymentModal(qrUrl, orderId) {
        document.getElementById('payment-modal').style.display = 'flex';
        document.getElementById('qr-container').innerHTML = `<img src="${qrUrl}" width="220" alt="QRIS">`;
        startPaymentCheck(orderId);
    }

    function startPaymentCheck(orderId) {
        if (window.paymentInterval) clearInterval(window.paymentInterval);
        const checkFn = async (isManual = false) => {
            const resp = await fetch(`${API_BASE}/check-status?order_id=${encodeURIComponent(orderId)}`, { credentials: 'same-origin' });
            const data = await resp.json();
            if (data.status === 'paid') {
                clearInterval(window.paymentInterval);
                document.getElementById('btn-manual-check').style.display = 'none';
                document.getElementById('payment-status-text').innerHTML = `
                    <div style="padding:20px; border:1px solid var(--accent); border-radius:12px; margin-top:10px; background:rgba(0,212,170,0.05);">
                        <div style="color:var(--accent); font-weight:800; font-size:1.1rem; margin-bottom:10px;">PEMBAYARAN SUKSES!</div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:10px;">LISENSI AKTIF ANDA:</div>
                        <code style="display:block; margin:10px 0; font-size:0.85rem; background:black; padding:12px; border-radius:8px; border:1px solid var(--border); color:white;">${escapeHTML(data.licence)}</code>
                    </div>`;
            } else if (isManual) {
                Swal.fire({ icon: 'info', title: 'Pending', background: '#1a1a26', color: '#fff' });
            }
        };
        window.paymentInterval = setInterval(() => checkFn(false), 5000);
        document.getElementById('btn-manual-check').onclick = () => {
            const btn = document.getElementById('btn-manual-check'); btn.disabled = true;
            checkFn(true).finally(() => { btn.disabled = false; });
        };
    }

    loadProducts();
</script>
<?= $this->endSection() ?>
